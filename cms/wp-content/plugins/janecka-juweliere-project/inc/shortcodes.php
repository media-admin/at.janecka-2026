<?php
/**
 * Shortcodes
 *
 * @package JaneckaJuweliereProject
 */

defined( 'ABSPATH' ) || exit;


// ===========================================================================
// Kategorie-Archiv Shortcode
// [janecka_category_archive category="liebe-hochzeit"]
// ===========================================================================
add_shortcode( 'janecka_category_archive', 'janecka_shortcode_category_archive' );
function janecka_shortcode_category_archive( array $atts ): string {
	$atts = shortcode_atts( [
		'category' => '',
		'limit'    => -1,
		'orderby'  => 'menu_order',
		'order'    => 'ASC',
	], $atts, 'janecka_category_archive' );

	if ( empty( $atts['category'] ) ) return '';

	$category_slug = sanitize_text_field( $atts['category'] );

	ob_start();

	echo '<div class="janecka-category-archive">';

	// ── Breadcrumb ────────────────────────────────────────────────────────────
	if ( function_exists( 'woocommerce_breadcrumb' ) ) {
		echo '<div class="category-archive-intro__breadcrumb">';
		woocommerce_breadcrumb();
		echo '</div>';
	}

	// ── Filter-Bar ────────────────────────────────────────────────────────────
	if ( function_exists( 'mlwf_render_filter_bar_for_category' ) ) {
		mlwf_render_filter_bar_for_category( $category_slug );
	}

	// ── Produkt-Grid ──────────────────────────────────────────────────────────
	echo do_shortcode( sprintf(
		'[products category="%s" limit="%d" orderby="%s" order="%s"]',
		esc_attr( $category_slug ),
		intval( $atts['limit'] ),
		esc_attr( $atts['orderby'] ),
		esc_attr( $atts['order'] )
	) );

	echo '</div><!-- .janecka-category-archive -->';

	return ob_get_clean();
}
