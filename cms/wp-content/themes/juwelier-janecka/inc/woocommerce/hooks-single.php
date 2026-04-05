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

// Standard-Hooks entfernen die wir neu positionieren
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title',       5  );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating',      10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price',       10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt',     20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta',        40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing',     50 );

// Neu registrieren in gewünschter Reihenfolge
add_action( 'woocommerce_single_product_summary', 'janecka_single_brand',                       5  );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title',          10 );
add_action( 'woocommerce_single_product_summary', 'janecka_single_sku_delivery',                15 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price',          20 );
// add_action( 'woocommerce_single_product_summary', 'janecka_single_tax_shipping',                25 );
// add_action( 'woocommerce_single_product_summary', 'janecka_single_stock_notice',                27 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart',    30 );

// WC Standard-Lagerstand entfernen
add_filter( 'woocommerce_get_stock_html', '__return_empty_string' );

// WC-GZD Lieferzeit vor Meta-Tabelle entfernen (wir zeigen sie in der Tabelle)
add_filter( 'woocommerce_gzd_product_delivery_time_html', '__return_empty_string' );

// Versandkosten aus Produkt-Loop entfernen
add_filter( 'woocommerce_gzd_show_shipping_costs_info', '__return_false' );

// ===========================================================================
// 3. MARKE (Brand) über dem Titel
// ===========================================================================

function janecka_single_brand(): void {
	global $product;

	// pa_brand oder product_tag als Marke
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

	// Fallback: product_tag
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

	$sku = $product->get_sku();
	$delivery = get_post_meta( $product->get_id(), '_delivery_time', true );

	// WooCommerce Germanized Lieferzeit
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
// 5. STEUER + VERSANDKOSTEN
// ===========================================================================



// ===========================================================================
// 6. LAGERBESTAND-HINWEIS
// ===========================================================================

// function janecka_single_stock_notice(): void {
// 	global $product;

// 	$stock = $product->get_stock_quantity();

// 	if ( ! $product->managing_stock() || $stock === null ) return;

// 	if ( $stock > 0 && $stock <= 3 ) {
// 		echo '<p class="single-product__stock single-product__stock--low">';
// 		printf(
// 			esc_html__( 'Nur noch %d Stück auf Lager', 'juwelier-janecka' ),
// 			$stock
// 		);
// 		echo '</p>';
// 	}
// }

// ===========================================================================
// 7. WUNSCHLISTE
// ===========================================================================

function janecka_single_wishlist(): void {
	if ( function_exists( 'YITH_WCWL_Frontend' ) ) {
		echo do_shortcode( '[yith_wcwl_add_to_wishlist]' );
	} else {
		// Fallback: manueller Wishlist-Link
		echo '<div class="single-product__wishlist">';
		echo '<a href="' . esc_url( home_url( '/meine-wunschliste/' ) ) . '" class="single-product__wishlist-btn">';
		echo '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>';
		echo esc_html__( 'Auf meinen Wunschzettel', 'juwelier-janecka' );
		echo '</a></div>';
	}
}

// ===========================================================================
// 8. MENGE-LABEL
// ===========================================================================

add_filter( 'woocommerce_quantity_input_args', function( array $args ): array {
	$args['input_value'] = $args['input_value'] ?? 1;
	return $args;
} );

// "Menge" Label vor dem Quantity-Input
add_action( 'woocommerce_before_add_to_cart_quantity', function() {
	echo '<span class="single-product__qty-label">' . esc_html__( 'Menge', 'juwelier-janecka' ) . '</span>';
} );

// ===========================================================================
// 9. TABS → einfache Sektion "Produktbeschreibung"
// ===========================================================================

// Tabs komplett entfernen
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

// Anzeige innerhalb der Summary (rechte Spalte)
add_action( 'woocommerce_single_product_summary', 'janecka_single_description_section', 35 );

function janecka_single_description_section(): void {
	global $product;

	$description = $product->get_description();
	if ( ! $description ) return;
	?>
	<div class="single-product__description">
		<h2><?php esc_html_e( 'Produktbeschreibung', 'juwelier-janecka' ); ?></h2>
		<div class="single-product__description-content">
			<?php echo wp_kses_post( $description ); ?>
		</div>
	</div>
	<?php
}

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
// 13. BRAND IN PRODUKT-LOOP (Related Products etc.)
// ===========================================================================

add_action( 'woocommerce_shop_loop_item_title', 'janecka_loop_brand', 5 );

function janecka_loop_brand(): void {
    global $product;
    if ( ! $product ) return;

    $brand = '';
    foreach ( [ 'pa_brand', 'pa_marke' ] as $tax ) {
        $terms = wc_get_product_terms( $product->get_id(), $tax, [ 'fields' => 'names' ] );
        if ( ! empty( $terms ) ) {
            $brand = $terms[0];
            break;
        }
    }

    if ( ! $brand ) {
        $tags = wc_get_product_terms( $product->get_id(), 'product_tag', [ 'fields' => 'names' ] );
        if ( ! empty( $tags ) ) {
            $brand = $tags[0];
        }
    }

    if ( $brand ) {
        echo '<div class="product-card__brand">' . esc_html( strtoupper( $brand ) ) . '</div>';
    }
}