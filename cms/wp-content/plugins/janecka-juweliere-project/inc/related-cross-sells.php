<?php
/**
 * Produktseite: "Ähnliche Produkte" aus dem Cross-Sell-Feld.
 *
 * Ablage: cms/wp-content/plugins/janecka-juweliere-project/inc/related-cross-sells.php
 * Einbindung: require_once in janecka-juweliere-project.php (nach den Sale-Dateien).
 *
 * Bisher zeigt WooCommerce hier eine Zufallsauswahl aus Produkten mit gemeinsamer Kategorie oder
 * gemeinsamem Schlagwort. Neu:
 *   1. Hat das Produkt Cross-Sells (Produktdaten > Verknüpfte Produkte, im Uhren-Import die Spalte
 *      "Cross-Sells Produkt"), werden genau diese gezeigt: gepflegte Reihenfolge, kein Zufall,
 *      keine Kürzung auf 4 (maximal 8). Nicht veröffentlichte/ausgeblendete Produkte entfallen.
 *   2. Ohne Cross-Sells bleibt die WooCommerce-Standardlogik (abschaltbar, siehe unten).
 *   3. Die Sale-Kategorien zählen bei der Standardlogik nicht als Gemeinsamkeit, sonst wären alle
 *      reduzierten Produkte untereinander "ähnlich".
 *
 * Filter:
 *   janecka_related_fallback_to_default (bool, Standard true)  false = ohne Cross-Sells nichts zeigen
 *   janecka_related_cross_sell_limit    (int, Standard 8)      maximale Anzahl Cross-Sells
 *
 * @package JaneckaJuweliereProject
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sichtbare Cross-Sell-Produkt-IDs in gepflegter Reihenfolge.
 *
 * @param WC_Product|false $product
 * @param int[]            $exclude IDs, die nicht erscheinen sollen (z. B. Upsells)
 * @return int[]
 */
function janecka_related_cross_sell_ids( $product, array $exclude = array() ): array {
	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$ids = array_map( 'absint', (array) $product->get_cross_sell_ids() );
	$ids = array_values( array_diff( array_unique( $ids ), array_map( 'absint', $exclude ), array( $product->get_id() ), array( 0 ) ) );
	if ( empty( $ids ) ) {
		return array();
	}

	$visible = array();
	foreach ( $ids as $id ) {
		get_the_terms( $id, 'product_type' ); // HPOS-Termcache vor wc_get_product() wärmen (siehe hpos-product-type-cache-fix.php)
		$candidate = wc_get_product( $id );
		if ( $candidate && $candidate->is_visible() ) {
			$visible[] = $id;
		}
	}

	return array_slice( $visible, 0, max( 1, (int) apply_filters( 'janecka_related_cross_sell_limit', 8 ) ) );
}

/**
 * Merkt sich, ob die aktuelle Auswahl aus Cross-Sells stammt. WooCommerce mischt das Ergebnis von
 * wc_get_related_products() danach; der Shuffle-Filter unten schaltet das in diesem Fall ab.
 */
function janecka_related_curated( $set = null ): bool {
	static $curated = false;
	if ( null !== $set ) {
		$curated = (bool) $set;
	}
	return $curated;
}

// Vor der Ausgabe: bei vorhandenen Cross-Sells genau so viele Plätze wie Cross-Sells und keine
// Zufallsreihenfolge. WooCommerce kürzt das Ergebnis per array_slice() auf "posts_per_page";
// mit -1 würde das letzte Produkt abgeschnitten.
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$product = wc_get_product( get_the_ID() );
	$ids     = $product ? janecka_related_cross_sell_ids( $product, $product->get_upsell_ids() ) : array();
	if ( $ids ) {
		$args['posts_per_page'] = count( $ids );
		$args['orderby']        = 'none'; // gepflegte Reihenfolge beibehalten
		$args['order']          = 'asc';  // wc_products_array_orderby() kehrt die Liste bei "desc" auch bei "none" um
	}
	return $args;
}, 20 );

// Auswahl der Produkte: Cross-Sells vor der Standardlogik.
add_filter( 'woocommerce_related_products', function ( $related, $product_id, $args = array() ) {
	$product = wc_get_product( $product_id );
	$exclude = isset( $args['excluded_ids'] ) ? (array) $args['excluded_ids'] : array();

	$ids = janecka_related_cross_sell_ids( $product, $exclude );
	janecka_related_curated( (bool) $ids );
	if ( $ids ) {
		return $ids;
	}

	return apply_filters( 'janecka_related_fallback_to_default', true ) ? $related : array();
}, 20, 3 );

// WooCommerce mischt das Ergebnis danach (shuffle): bei gepflegten Cross-Sells nicht.
add_filter( 'woocommerce_product_related_posts_shuffle', function ( $shuffle ) {
	return janecka_related_curated() ? false : $shuffle;
}, 20 );

// Standardlogik: Sale-Kategorien nicht als Gemeinsamkeit werten.
add_filter( 'woocommerce_get_related_product_cat_terms', function ( $term_ids ) {
	if ( ! function_exists( 'janecka_sale_all_term_ids' ) ) {
		return $term_ids;
	}
	return array_values( array_diff( array_map( 'intval', (array) $term_ids ), janecka_sale_all_term_ids() ) );
}, 20 );
