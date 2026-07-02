<?php
/**
 * WooCommerce Single Product Hooks
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

// ===========================================================================
// 1. WRAPPER: Container + eigenes Layout
// ===========================================================================

remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content',  'woocommerce_output_content_wrapper_end', 10 );

add_action( 'woocommerce_before_main_content', 'janecka_single_open_wrapper', 10 );
add_action( 'woocommerce_after_main_content',  'janecka_single_close_wrapper', 10 );

add_filter( 'woocommerce_product_thumbnails_columns', function(): int {
    return 1;
} );

function janecka_single_open_wrapper(): void {
    if ( ! is_product() ) {
        echo '<div class="container">';
        return;
    }
    echo '<div class="container"><div class="single-product-layout">';
}

function janecka_single_close_wrapper(): void {
    if ( ! is_product() ) {
        echo '</div>';
        return;
    }
    echo '</div></div>';
}

// ===========================================================================
// 2. SUMMARY: Reihenfolge
// ===========================================================================

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title',       5  );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating',      10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price',       10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt',     20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta',        40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing',     50 );

add_action( 'woocommerce_single_product_summary', 'janecka_single_brand',                        5  );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title',           10 );
add_action( 'woocommerce_single_product_summary', 'janecka_single_sku_delivery',                 15 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price',           20 );
add_action( 'woocommerce_single_product_summary', 'janecka_single_gzd_info',                     25 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart',     30 );
add_action( 'woocommerce_single_product_summary', 'janecka_single_specs_accordion',              35 );
add_action( 'woocommerce_single_product_summary', 'janecka_single_contact_block',                40 );

// WC Standard-Lagerstand entfernen
add_filter( 'woocommerce_get_stock_html', '__return_empty_string' );

// WC-GZD Lieferzeit vor Meta-Tabelle entfernen
add_filter( 'woocommerce_gzd_product_delivery_time_html', '__return_empty_string' );

// ===========================================================================
// 3. MARKE (Brand) über dem Titel
// ===========================================================================

function janecka_single_brand(): void {
    global $product;

    $brand = '';
    foreach ( [ 'pa_brand', 'pa_marke' ] as $tax ) {
        $terms = wc_get_product_terms( $product->get_id(), $tax, [ 'fields' => 'all' ] );
        if ( ! empty( $terms ) ) {
            $links = array_map( function( $term ) use ( $tax ) {
                return '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
            }, $terms );
            $brand = implode( ', ', $links );
            break;
        }
    }

    if ( ! $brand ) {
        $tags = wc_get_product_terms( $product->get_id(), 'product_tag', [ 'fields' => 'all' ] );
        if ( ! empty( $tags ) ) {
            $links = array_map( function( $term ) {
                return '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
            }, array_slice( $tags, 0, 1 ) );
            $brand = implode( ', ', $links );
        }
    }

    if ( $brand ) {
        echo '<div class="single-product__brand">' . $brand . '</div>';
    }
}

// ===========================================================================
// 4. SKU + LIEFERZEIT
// ===========================================================================

function janecka_single_sku_delivery(): void {
    global $product;

    $sku      = $product->get_sku();
    $delivery = '';

    if ( function_exists( 'wc_gzd_get_product' ) ) {
        $gzd_product = wc_gzd_get_product( $product );
        if ( $gzd_product && method_exists( $gzd_product, 'get_delivery_time' ) ) {
            $delivery_obj = $gzd_product->get_delivery_time();
            if ( $delivery_obj ) {
                $delivery = $delivery_obj->name;
            }
        }
    }

    echo '<div class="single-product__meta-row">';
    if ( $sku ) {
        echo '<span class="single-product__sku">';
        echo '<span class="single-product__meta-label">' . esc_html__( 'Artikelnummer', 'juwelier-janecka' ) . '</span>';
        echo esc_html( $sku );
        echo '</span>';
    }
    echo '</div>';

    global $product;
    $excerpt = $product->get_short_description();
    if ( $excerpt ) {
        echo '<div class="product-short-description">' . wp_kses_post( $excerpt ) . '</div>';
    }
    echo '<div class="single-product__meta-row">';
    if ( $delivery ) {
        echo '<span class="single-product__delivery">';
        echo '<span class="single-product__meta-label">' . esc_html__( 'Lieferzeit', 'juwelier-janecka' ) . '</span>';
        echo esc_html( $delivery );
        echo '</span>';
    }
    echo '</div>';
}

// ===========================================================================
// 5. GZD-INFOS (MwSt + Versand) unter dem Preis
// ===========================================================================

function janecka_single_gzd_info(): void {
    global $product;

    if ( ! function_exists( 'wc_gzd_get_product' ) ) return;

    $gzd = wc_gzd_get_product( $product );
    if ( ! $gzd ) return;

    $tax      = $gzd->get_tax_info();
    $shipping = $gzd->get_shipping_costs_html();

    if ( ! $tax && ! $shipping ) return;

    $parts = [];
    if ( $tax )      $parts[] = wp_kses_post( $tax );
    if ( $shipping ) $parts[] = wp_kses_post( $shipping );

    echo '<p class="single-product__gzd-info">' . implode( ' / ', $parts ) . '</p>';
}

// ===========================================================================
// 6. SPEZIFIKATIONEN (Akkordeon)
// ===========================================================================

// Standard Tabs + Beschreibung entfernen
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

function janecka_single_specs_accordion(): void {
    global $product;

    $description = $product->get_description();
    if ( ! $description ) return;
    ?>
    <div class="single-product__accordion">
        <button
            class="single-product__accordion-trigger js-accordion-trigger"
            type="button"
            aria-expanded="false"
        >
            <span><?php esc_html_e( 'Spezifikationen', 'juwelier-janecka' ); ?></span>
            <span class="single-product__accordion-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="5" x2="12" y2="19" class="accordion-icon__vertical"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </span>
        </button>
        <div class="single-product__accordion-content is-collapsed">
            <div class="single-product__accordion-body">
                <?php echo wp_kses_post( wpautop( $description ) ); ?>
            </div>
        </div>
    </div>
    <?php
}

// ===========================================================================
// 7. KONTAKTBLOCK
// Helper: Passende Filiale(n) für ein Produkt ermitteln
// Logik: Produkt → product_brand Terms → stores Posts mit filiale-marken
// ===========================================================================

/**
 * Gibt alle Stores zurück die mindestens eine Marke des Produkts führen.
 *
 * @param  int   $product_id
 * @return array Array von WP_Post Objekten (stores CPT)
 */
function janecka_get_stores_for_product( int $product_id ): array {
    // Brand-Term-IDs des Produkts ermitteln
    $brand_term_ids = [];
    foreach ( [ 'product_brand', 'pa_brand', 'pa_marke' ] as $tax ) {
        $terms = wc_get_product_terms( $product_id, $tax, [ 'fields' => 'ids' ] );
        if ( ! empty( $terms ) ) {
            $brand_term_ids = $terms;
            break;
        }
    }

    if ( empty( $brand_term_ids ) ) return [];

    // Alle Stores laden
    $stores = get_posts( [
        'post_type'      => 'stores',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    $matching = [];
    foreach ( $stores as $store ) {
        $store_brands = get_post_meta( $store->ID, 'filiale-marken', true );
        if ( empty( $store_brands ) || ! is_array( $store_brands ) ) continue;

        // Überschneidung prüfen
        $store_brand_ids = array_map( 'intval', $store_brands );
        if ( ! empty( array_intersect( $brand_term_ids, $store_brand_ids ) ) ) {
            $matching[] = $store;
        }
    }

    return $matching;
}

function janecka_single_contact_block(): void {
    global $product;

    $stores = janecka_get_stores_for_product( $product->get_id() );

    // Fallback: ersten Store nehmen wenn keine Marken-Zuordnung
    if ( empty( $stores ) ) {
        $all = get_posts( [
            'post_type'      => 'stores',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        ] );
        if ( ! empty( $all ) ) $stores = $all;
    }

    if ( empty( $stores ) ) return;

    // Ersten passenden Store verwenden
    $store       = $stores[0];
    $store_id    = $store->ID;
    $phone       = get_post_meta( $store_id, 'filiale-telefon', true );
    $phone_clean = preg_replace( '/[^+0-9]/', '', $phone );
    $store_url   = get_permalink( $store_id );
    $store_name  = get_the_title( $store_id );

    // Booking Location für diesen Store ermitteln (mlb_location mit gleichem Titel)
    $booking_locations = get_posts( [
        'post_type'      => 'mlb_location',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ] );

    $booking_url = $store_url; // Fallback
    foreach ( $booking_locations as $loc ) {
        // Match: "Janecka 1140" ist enthalten in "Standort Janecka 1140"
        if ( stripos( $loc->post_title, $store_name ) !== false ) {
            $booking_url = get_permalink( $loc->ID );
            break;
        }
    }

    // Brand-Name für "Entdecken Sie die Welt von [Marke]"
    $brand_name = '';
    foreach ( [ 'product_brand', 'pa_brand', 'pa_marke' ] as $tax ) {
        $terms = wc_get_product_terms( $product->get_id(), $tax, [ 'fields' => 'names' ] );
        if ( ! empty( $terms ) ) {
            $brand_name = $terms[0];
            break;
        }
    }
    ?>
    <div class="single-product__contact-block">

        <?php if ( $phone ) : ?>
        <div class="single-product__contact-row">
            <span class="single-product__contact-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.53 2 2 0 0 1 3.6 1.37h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </span>
            <span class="single-product__contact-text">
                <a href="<?php echo esc_url( get_permalink( $store_id ) . '#kontakt' ); ?>" class="single-product__contact-link">
                    <?php esc_html_e( 'Kontaktieren Sie uns', 'juwelier-janecka' ); ?>
                </a>
                <?php esc_html_e( 'oder rufen Sie uns an unter', 'juwelier-janecka' ); ?>
                <a href="tel:<?php echo esc_attr( $phone_clean ); ?>" class="single-product__contact-link">
                    <?php echo esc_html( $phone ); ?>
                </a>
            </span>
        </div>
        <?php endif; ?>

        <div class="single-product__contact-row">
            <span class="single-product__contact-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </span>
            <span class="single-product__contact-text">
                <?php if ( $brand_name ) : ?>
                    <?php printf(
                        esc_html__( 'Entdecken Sie die Welt von %s.', 'juwelier-janecka' ),
                        esc_html( $brand_name )
                    ); ?>
                <?php else : ?>
                    <?php esc_html_e( 'Besuchen Sie uns in unseren Filialen.', 'juwelier-janecka' ); ?>
                <?php endif; ?>
                <a href="<?php echo esc_url( $store_url ); ?>" class="single-product__contact-link">
                    <?php esc_html_e( 'Standorte', 'juwelier-janecka' ); ?>
                </a>
            </span>
        </div>

        <div class="single-product__contact-row">
            <span class="single-product__contact-icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </span>
            <span class="single-product__contact-text">
                <?php esc_html_e( 'Mit einem Experten sprechen.', 'juwelier-janecka' ); ?>
                <a href="<?php echo esc_url( $booking_url ); ?>" class="single-product__contact-link">
                    <?php esc_html_e( 'Einen Termin vereinbaren', 'juwelier-janecka' ); ?>
                </a>
            </span>
        </div>

    </div>
    <?php
}

// ===========================================================================
// 8. WUNSCHLISTE
// ===========================================================================

function janecka_single_wishlist(): void {
    if ( function_exists( 'YITH_WCWL_Frontend' ) ) {
        echo do_shortcode( '[yith_wcwl_add_to_wishlist]' );
    } else {
        echo '<div class="single-product__wishlist">';
        echo '<a href="' . esc_url( home_url( '/meine-wunschliste/' ) ) . '" class="single-product__wishlist-btn">';
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
        echo esc_html__( 'Auf meinen Wunschzettel', 'juwelier-janecka' );
        echo '</a></div>';
    }
}

// ===========================================================================
// 9. MENGE-LABEL
// ===========================================================================

add_filter( 'woocommerce_quantity_input_args', function( array $args ): array {
    $args['input_value'] = $args['input_value'] ?? 1;
    return $args;
} );

add_action( 'woocommerce_before_add_to_cart_quantity', function() {
    echo '<span class="single-product__qty-label">' . esc_html__( 'Menge', 'juwelier-janecka' ) . '</span>';
} );

// ===========================================================================
// 10. RELATED PRODUCTS
// ===========================================================================

add_filter( 'woocommerce_output_related_products_args', function( array $args ): array {
    $args['posts_per_page'] = 4;
    $args['columns']        = 4;
    return $args;
} );

add_filter( 'woocommerce_product_related_products_heading', function(): string {
    return __( 'Ähnliche Produkte', 'juwelier-janecka' );
} );

// ===========================================================================
// 11. ATC BUTTON TEXT
// ===========================================================================

add_filter( 'woocommerce_product_single_add_to_cart_text', function(): string {
    return __( 'In den Warenkorb', 'juwelier-janecka' );
} );

// ===========================================================================
// 12. SCHEMA.ORG
// ===========================================================================

add_action( 'wp_head', 'janecka_single_product_schema' );

function janecka_single_product_schema(): void {
    if ( ! is_product() ) return;

    global $product;
    if ( ! $product instanceof WC_Product ) return;

    $schema = [
        '@context'  => 'https://schema.org/',
        '@type'     => 'Product',
        'name'      => $product->name,
        'image'     => wp_get_attachment_url( $product->get_image_id() ),
        'sku'       => $product->get_sku(),
        'offers'    => [
            '@type'         => 'Offer',
            'url'           => get_permalink(),
            'priceCurrency' => get_woocommerce_currency(),
            'price'         => $product->get_price(),
            'availability'  => $product->is_in_stock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
        ],
    ];

    $brand_terms = wc_get_product_terms( $product->get_id(), 'pa_brand', [ 'fields' => 'names' ] );
    if ( ! empty( $brand_terms ) ) {
        $schema['brand'] = [ '@type' => 'Brand', 'name' => $brand_terms[0] ];
    }

    echo '<script type="application/ld+json">'
        . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
        . '</script>' . "\n";
}

// ===========================================================================
// 13. ACCORDION JS (inline, kein extra File nötig)
// ===========================================================================

add_action( 'wp_footer', 'janecka_single_accordion_script' );

function janecka_single_accordion_script(): void {
    if ( ! is_product() ) return;
    ?>
    <script>
    document.querySelectorAll('.js-accordion-trigger').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            const content  = this.nextElementSibling;
            const icon     = this.querySelector('.accordion-icon__vertical');

            this.setAttribute('aria-expanded', String(!expanded));
            content.classList.toggle('is-collapsed', expanded);
            if (icon) icon.style.display = expanded ? '' : 'none';
        });
    });
    </script>
    <?php
}
// ===========================================================================
// 14. PRODUKTGALERIE: WC-Zoom deaktivieren, eigene Lightbox aktivieren
// ===========================================================================

add_filter( 'woocommerce_single_product_zoom_enabled', '__return_false' );
add_filter( 'wc_zoom_enabled', '__return_false' );

add_filter( 'woocommerce_single_product_image_thumbnail_html', function( string $html, $attachment_id ): string {
    if ( ! $attachment_id || ! is_numeric( $attachment_id ) ) return $html;
    $full_url = wp_get_attachment_image_url( (int) $attachment_id, 'full' );
    $alt      = get_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', true );
    if ( ! $full_url ) return $html;
    $html = preg_replace(
        '/<a([^>]*)href=["\'][^"\']*["\']([^>]*)>/i',
        '<a$1href="' . esc_url( $full_url ) . '" data-lightbox="product-gallery" data-caption="' . esc_attr( $alt ) . '"$2>',
        $html
    );
    return $html;
}, 10, 2 );

// Photoswipe + WC Single Product JS deregistrieren
add_action( 'wp_enqueue_scripts', function(): void {
    if ( ! is_product() ) return;
    wp_dequeue_script( 'photoswipe' );
    wp_dequeue_script( 'photoswipe-ui-default' );
    wp_dequeue_script( 'wc-single-product' );
    wp_dequeue_style( 'photoswipe' );
    wp_dequeue_style( 'photoswipe-default-skin' );
}, 99 );
