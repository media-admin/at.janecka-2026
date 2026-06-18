<?php
/**
 * WooCommerce Setup
 *
 * Theme-Support, Bild-Größen, Scripts/Styles.
 * KEIN Template-Override – alle Anpassungen via Hooks.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// 1. Theme Support
// ---------------------------------------------------------------------------

add_action( 'after_setup_theme', 'janecka_woocommerce_support' );

function janecka_woocommerce_support(): void {
	add_theme_support( 'woocommerce', [
		'thumbnail_image_width'         => 600,
		'intermediate_image_sizes'      => [ 'woocommerce_thumbnail', 'woocommerce_single' ],
		'single_image_width'            => 900,
		'product_grid'                  => [
			'default_rows'    => 4,
			'min_rows'        => 2,
			'max_rows'        => 8,
			'default_columns' => 3,
			'min_columns'     => 2,
			'max_columns'     => 4,
		],
	] );

	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}

// ---------------------------------------------------------------------------
// 2. WooCommerce Standard-CSS deaktivieren (wir nutzen eigenes SCSS)
// ---------------------------------------------------------------------------

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

// ---------------------------------------------------------------------------
// 3. Eigene Scripts & Styles einbinden
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'janecka_woocommerce_assets' );

function janecka_woocommerce_assets(): void {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}

	$is_shortcode_page = is_singular() && has_shortcode( get_post()->post_content ?? '', 'janecka_category_archive' );
	if ( ! ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() || $is_shortcode_page ) ) {
		return;
	}

	// noUISlider CSS — aus node_modules kopiert (via Vite)
	wp_enqueue_style(
		'nouislider',
		get_template_directory_uri() . '/assets/dist/css/nouislider.css',
		[],
		'15.7.1'
	);
	wp_enqueue_script(
		'janecka-wc-filters',
		// ... Pfad via Manifest ...
		[], // ← kein nouislider mehr als Dependency
		null,
		true
	);

	// WooCommerce Filter-Script via Vite-Manifest (wie main.js in enqueue.php)
	$dist_uri = get_template_directory_uri() . '/assets/dist';
	$dist     = get_template_directory()     . '/assets/dist';

	$entry = function_exists( 'customtheme_vite_manifest_entry' )
		? customtheme_vite_manifest_entry( 'src/js/woocommerce-filters.js' )
		: null;

	if ( $entry ) {
		wp_enqueue_script(
			'janecka-wc-filters',
			$dist_uri . '/' . $entry['file'],
			[ ],
			null,
			true
		);
	} elseif ( file_exists( $dist . '/js/woocommerce-filters.js' ) ) {
		wp_enqueue_script(
			'janecka-wc-filters',
			$dist_uri . '/js/woocommerce-filters.js',
			[ ],
			null,
			true
		);
	}

	// AJAX-URL & Nonce für JS
	wp_localize_script( 'janecka-wc-filters', 'janeckaWC', [
		'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'mlwf_filter_nonce' ),
		'currency' => get_woocommerce_currency_symbol(),
		'i18n'     => [
			'loading'     => __( 'Produkte werden geladen …', 'juwelier-janecka' ),
			'noProducts'  => __( 'Keine Produkte gefunden.', 'juwelier-janecka' ),
			'filterReset' => __( 'Filter zurücksetzen', 'juwelier-janecka' ),
		],
	] );
}

// ---------------------------------------------------------------------------
// 4. Bild-Größen
// ---------------------------------------------------------------------------

add_action( 'after_setup_theme', 'janecka_woocommerce_image_sizes' );

function janecka_woocommerce_image_sizes(): void {
	// Produkt-Karte (3-spaltig): 600 × 750 px (4:5 Hochformat)
	add_image_size( 'janecka-product-card', 600, 750, true );
	// Produkt-Detail Hauptbild
	add_image_size( 'janecka-product-single', 900, 900, false );
}