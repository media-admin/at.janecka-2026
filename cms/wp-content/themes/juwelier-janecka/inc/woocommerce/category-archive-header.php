<?php
/**
 * WooCommerce Produktkategorie-Archiv-Header
 *
 * Reihenfolge (wie Marken-Seiten):
 *   1. Banner fullwidth   → woocommerce_before_main_content priority 8 (vor Container)
 *   2. Titel zentriert    → woocommerce_before_shop_loop priority 4
 *   3. Beschreibung       → woocommerce_before_shop_loop priority 4
 *   4. Breadcrumb         → woocommerce_before_shop_loop priority 4
 *   5. Filter-Bar         → hooks-archive.php priority 5
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;


// ============================================================
// 1. ACF-Felder für product_cat-Terme
// ============================================================

add_action( 'acf/init', 'janecka_register_category_acf_fields' );
function janecka_register_category_acf_fields(): void {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( [
        'key'    => 'group_category_header_fields',
        'title'  => 'Kategorie-Header',
        'fields' => [
            [
                'key'           => 'field_cat_banner',
                'label'         => 'Banner-Bild',
                'name'          => 'cat_banner',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
                'library'       => 'all',
                'instructions'  => 'Breites Bannerbild für die Kategorie-Seite (empfohlen: 1400 × 470 px)',
            ],
            [
                'key'           => 'field_cat_description',
                'label'         => 'Beschreibung',
                'name'          => 'cat_description',
                'type'          => 'wysiwyg',
                'toolbar'       => 'basic',
                'media_upload'  => 0,
                'instructions'  => 'Optionaler Einleitungstext für die Kategorie-Seite.',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'taxonomy',
                    'operator' => '==',
                    'value'    => 'product_cat',
                ],
            ],
        ],
        'menu_order' => 5,
        'active'     => true,
    ] );
}


// ============================================================
// 2. Banner fullwidth — VOR dem .wc-archive Container
//    Priority 8 = vor janecka_wc_open_wrapper (priority 10)
// ============================================================

add_action( 'woocommerce_before_main_content', 'janecka_category_banner_fullwidth', 8 );
function janecka_category_banner_fullwidth(): void {
    if ( ! is_product_category() ) {
        return;
    }

    $term = get_queried_object();
    if ( ! ( $term instanceof WP_Term ) ) {
        return;
    }

    $banner_url = '';
    $acf_banner = get_field( 'cat_banner', 'product_cat_' . $term->term_id );
    if ( ! empty( $acf_banner['url'] ) ) {
        $banner_url = $acf_banner['url'];
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
// 3. Titel + Beschreibung + Breadcrumb
//    Priority 4 = vor Filter-Bar (priority 5)
// ============================================================

add_action( 'woocommerce_before_shop_loop', 'janecka_category_title_description_breadcrumb', 4 );
function janecka_category_title_description_breadcrumb(): void {
    if ( ! is_product_category() ) {
        return;
    }

    $term = get_queried_object();
    if ( ! ( $term instanceof WP_Term ) ) {
        return;
    }

    $tid = $term->term_id;

    // ── Beschreibung ──────────────────────────────────────────
    $description = '';
    $acf_desc    = get_field( 'cat_description', 'product_cat_' . $tid );
    if ( ! empty( $acf_desc ) ) {
        $description = $acf_desc;
    } elseif ( $native = term_description( $tid, 'product_cat' ) ) {
        $description = $native;
    }
    ?>

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
