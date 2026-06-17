<?php
/**
 * WooCommerce Brands Setup
 *
 * - Setzt den Taxonomy-URL-Slug auf /marken/ (statt /product-brand/)
 * - Registriert ACF-Felder für Brand-Terme (Banner-Bild + Logo)
 * - Injiziert Banner + Beschreibung auf Brand-Archivseiten (kein Template-Override)
 * - Stellt Helper-Funktionen für Brand-Logos und Kategorie-Filterung bereit
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;


// ============================================================
// 1. Taxonomy-URL-Slug → /marken/[slug]/
// ============================================================

add_filter( 'woocommerce_taxonomy_args_product_brand', function ( array $args ): array {
    $args['rewrite'] = [
        'slug'         => 'marken',
        'with_front'   => false,
        'hierarchical' => false,
    ];
    return $args;
} );


// ============================================================
// 2. ACF-Felder für product_brand-Terme werden via JSON (group_brand_tag_details.json) geladen.
// ============================================================


// ============================================================
// 3. Banner fullwidth — VOR dem .wc-archive Container
//    Priority 8 = vor janecka_wc_open_wrapper (priority 10)
//    Gleiche Logik wie janecka_category_banner_fullwidth()
// ============================================================

add_action( 'woocommerce_before_main_content', 'janecka_brand_banner_fullwidth', 8 );
function janecka_brand_banner_fullwidth(): void {
    if ( ! is_tax( 'product_brand' ) ) {
        return;
    }

    $term = get_queried_object();
    if ( ! ( $term instanceof WP_Term ) ) {
        return;
    }

    $tid        = $term->term_id;
    $banner_url = '';

    // 1. Migriertes ACF-Feld (Bindestrich-Key, speichert Attachment-ID)
    $banner_id = (int) get_term_meta( $tid, 'brand-banner', true );
    if ( $banner_id ) {
        $banner_url = wp_get_attachment_image_url( $banner_id, 'full' ) ?: '';
    }

    // 2. Neues ACF-Feld (Unterstrich-Key, gibt Array zurück)
    if ( ! $banner_url ) {
        $acf_banner = get_field( 'brand_banner', 'product_brand_' . $tid );
        if ( ! empty( $acf_banner['url'] ) ) {
            $banner_url = $acf_banner['url'];
        }
    }

    if ( ! $banner_url ) {
        return;
    }
    ?>
    <div class="category-archive-banner">
        <img
            src="<?php echo esc_url( $banner_url ); ?>"
            alt="<?php echo esc_attr( $term->name ); ?>"
            class="category-archive-banner__img"
            width="1400"
            height="470"
            loading="eager"
            decoding="async"
        >
    </div>
    <?php
}


// ============================================================
// 4. Brand-Archiv-Header: Titel + Beschreibung + Breadcrumb
//
// Liest in dieser Priorität:
//   Banner:       brand-banner (migriert) → brand_banner (neues ACF-Feld)
//   Logo:         brand-logo-main (migriert) → thumbnail_id
//   Beschreibung: brand-description (migriert) → WC term_description()
// ============================================================

// Anchor-ID für den "#product-grid"-Link in der Marken-Beschreibung
add_action( 'woocommerce_before_shop_loop', 'janecka_brand_product_grid_anchor', 4 );
function janecka_brand_product_grid_anchor(): void {
    if ( ! is_tax( 'product_brand' ) ) {
        return;
    }
    echo '<div id="product-grid"></div>';
}

add_action( 'woocommerce_before_shop_loop', 'janecka_brand_archive_header', 4 );
function janecka_brand_archive_header(): void {
    if ( ! is_tax( 'product_brand' ) ) {
        return;
    }

    $term = get_queried_object();
    if ( ! ( $term instanceof WP_Term ) ) {
        return;
    }

    $tid = $term->term_id;

    // ── Banner-URL ermitteln (für Fallback-Logik) ─────────────
    $banner_url = '';
    $banner_id  = (int) get_term_meta( $tid, 'brand-banner', true );
    if ( $banner_id ) {
        $banner_url = wp_get_attachment_image_url( $banner_id, 'full' ) ?: '';
    }
    if ( ! $banner_url ) {
        $acf_banner = get_field( 'brand_banner', 'product_brand_' . $tid );
        if ( ! empty( $acf_banner['url'] ) ) {
            $banner_url = $acf_banner['url'];
        }
    }

    // ── Beschreibung ──────────────────────────────────────────
    $description = '';
    $acf_desc    = get_term_meta( $tid, 'brand-description', true );
    if ( ! empty( $acf_desc ) ) {
        $description = $acf_desc;
    }
    if ( ! $description ) {
        $description = term_description( $tid, 'product_brand' );
    }

    // ── Logo (nur wenn kein Banner vorhanden) ─────────────────
    $logo_url = '';
    if ( ! $banner_url ) {
        $logo_id = (int) get_term_meta( $tid, 'brand-logo-main', true );
        if ( $logo_id ) {
            $logo_url = wp_get_attachment_image_url( $logo_id, 'large' ) ?: '';
        }
    }

    // Titel immer anzeigen, auch ohne Beschreibung/Logo
    ?>

    <?php if ( $logo_url ) : ?>
    <div class="brand-archive-header__logo-wrap">
        <img
            src="<?php echo esc_url( $logo_url ); ?>"
            alt="<?php echo esc_attr( $term->name ); ?>"
            class="brand-archive-header__logo"
            loading="eager"
            decoding="async"
        >
    </div>
    <?php endif; ?>

    <div class="category-archive-intro">

        <h1 class="category-archive-intro__title">
            <?php echo esc_html( $term->name ); ?>
        </h1>

        <?php if ( $description ) : ?>
        <div class="category-archive-intro__description">
            <?php echo wp_kses( wpautop( $description ), wp_kses_allowed_html( 'post' ) ); ?>
        </div>
        <?php endif; ?>

        <div class="category-archive-intro__breadcrumb">
            <?php woocommerce_breadcrumb(); ?>
        </div>

    </div>

    <?php
}


// ============================================================
// 5. Helper: Brands einer Produktkategorie ermitteln (1 DB-Query)
// ============================================================

/**
 * Gibt alle product_brand-Terme zurück, die Produkte in der
 * angegebenen product_cat-Kategorie (inkl. Unterkategorien) haben.
 *
 * @param  string $category_slug WooCommerce product_cat Slug
 * @return WP_Term[]             Alphabetisch sortierte Brand-Terme
 */
function janecka_get_brands_by_category( string $category_slug ): array {
    global $wpdb;

    if ( empty( $category_slug ) ) {
        return [];
    }

    $cat_term = get_term_by( 'slug', $category_slug, 'product_cat' );
    if ( ! $cat_term ) {
        return [];
    }

    $cat_ids   = get_term_children( $cat_term->term_id, 'product_cat' );
    $cat_ids[] = $cat_term->term_id;
    $cat_ids   = array_map( 'intval', $cat_ids );

    $placeholders = implode( ', ', array_fill( 0, count( $cat_ids ), '%d' ) );

    // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
    $query = $wpdb->prepare(
        "SELECT DISTINCT tt2.term_id
         FROM {$wpdb->term_relationships} tr1
         INNER JOIN {$wpdb->term_taxonomy} tt1
             ON tr1.term_taxonomy_id = tt1.term_taxonomy_id
            AND tt1.taxonomy = 'product_cat'
            AND tt1.term_id IN ({$placeholders})
         INNER JOIN {$wpdb->term_relationships} tr2
             ON tr1.object_id = tr2.object_id
         INNER JOIN {$wpdb->term_taxonomy} tt2
             ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
            AND tt2.taxonomy = 'product_brand'
         INNER JOIN {$wpdb->posts} p
             ON tr1.object_id = p.ID
            AND p.post_type  = 'product'
            AND p.post_status = 'publish'",
        ...$cat_ids
    );
    // phpcs:enable

    $brand_ids = $wpdb->get_col( $query );

    if ( empty( $brand_ids ) ) {
        return [];
    }

    $brands = array_filter(
        array_map( fn( $id ) => get_term( (int) $id, 'product_brand' ), $brand_ids ),
        fn( $t ) => $t instanceof WP_Term
    );

    usort( $brands, fn( $a, $b ) => strcmp( $a->name, $b->name ) );

    return array_values( $brands );
}


// ============================================================
// 6. Helper: Brand-Logo-URL ermitteln
// ============================================================

/**
 * Gibt die Logo-URL einer Marke zurück.
 * Priorität: ACF brand_logo → WC thumbnail_id → leer.
 *
 * @param  WP_Term $term
 * @param  string  $size  WordPress-Bildgröße (default 'large')
 * @return string         URL oder leerer String
 */
function janecka_get_brand_logo_url( WP_Term $term, string $size = 'large' ): string {
    // 1. ACF brand_logo
    $acf_logo = get_field( 'brand_logo', 'product_brand_' . $term->term_id );
    if ( ! empty( $acf_logo['url'] ) ) {
        return $acf_logo['url'];
    }

    // 2. WooCommerce thumbnail_id
    $thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
    if ( $thumbnail_id ) {
        $src = wp_get_attachment_image_src( $thumbnail_id, $size );
        if ( $src ) {
            return $src[0];
        }
    }

    return '';
}


// ============================================================
// 7. Breadcrumb: "Start / Marken / [Brand]" auf Brand-Archivseiten
// ============================================================

add_filter( 'woocommerce_get_breadcrumb', 'janecka_brand_breadcrumb', 20, 2 );
function janecka_brand_breadcrumb( array $crumbs, object $breadcrumb ): array {
    if ( ! is_tax( 'product_brand' ) ) {
        return $crumbs;
    }

    $result = [];
    foreach ( $crumbs as $i => $crumb ) {
        $result[] = $crumb;
        // Nach dem ersten Crumb (= "Start") "Marken" einfügen
        if ( $i === 0 ) {
            $result[] = [
                __( 'Marken', 'juwelier-janecka' ),
                '',
            ];
        }
    }

    return $result;
}