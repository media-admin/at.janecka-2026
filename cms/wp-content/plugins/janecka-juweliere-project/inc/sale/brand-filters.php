<?php
/**
 * Marken-Archive (/marken/<marke>/): dynamische Produktfilter.
 *
 * Ablage: cms/wp-content/plugins/janecka-juweliere-project/inc/sale/brand-filters.php
 * Voraussetzung: sale-filters.php ist eingebunden (stellt janecka_sale_dynamic_attributes() bereit).
 *
 * Wie bei Sale und der Suche richten sich die Filter nach den Produkten der Marke: Es erscheinen
 * nur Attribute, die in den Produkten der Marke vorkommen und tatsächlich etwas eingrenzen.
 * Hat eine Marke im Term-Editor eine eigene Filterliste, bleibt diese unverändert.
 * Die Marke selbst wird nicht als Filter angeboten.
 *
 * Zusätzlich gibt es auf Marken-Seiten die Gruppe "Kategorie" (Kategorien der Marken-Produkte,
 * Zähler innerhalb der Marke). Voraussetzung: Patch in media-lab-woocommerce/inc/filters/filter-bar.php
 * (patch-filter-bar.py). Ohne den Patch bleibt die Einstellung wirkungslos, aber unschädlich.
 *
 * @package JaneckaJuweliereProject
 */

defined( 'ABSPATH' ) || exit;

/** Term-ID, wenn die ACF-Post-ID zu einem Marken-Term gehört ("term_123" oder "product_brand_123"), sonst 0. */
function janecka_brand_term_id_from_acf( $post_id ): int {
	if ( ! is_string( $post_id ) || ! preg_match( '/^(?:term|product_brand)_(\d+)$/', $post_id, $m ) ) {
		return 0;
	}
	$term = get_term( (int) $m[1] );
	return ( $term instanceof WP_Term && 'product_brand' === $term->taxonomy ) ? (int) $term->term_id : 0;
}

// Attribute: dynamisch, solange die Marke keine eigene Liste hat (nur Frontend).
add_filter( 'acf/load_value/name=mlwf_attributes', function ( $value, $post_id ) {
	if ( is_admin() || ! empty( $value ) ) {
		return $value;
	}
	$term_id = janecka_brand_term_id_from_acf( $post_id );
	return $term_id ? janecka_sale_dynamic_attributes( $term_id, 'product_brand' ) : $value;
}, 25, 2 );

// Preisfilter: bei Marken ohne eigene Einstellung an.
add_filter( 'acf/load_value/name=mlwf_show_price', function ( $value, $post_id ) {
	if ( is_admin() || ( null !== $value && '' !== $value ) ) {
		return $value;
	}
	return janecka_brand_term_id_from_acf( $post_id ) ? 1 : $value;
}, 25, 2 );

// Keine Vererbung auf die globale Standardliste, solange die Marke keine eigene Attributliste hat.
add_filter( 'acf/load_value/name=mlwf_inherit_parent', function ( $value, $post_id ) {
	if ( is_admin() ) {
		return $value;
	}
	$term_id = janecka_brand_term_id_from_acf( $post_id );
	if ( ! $term_id || ! empty( get_term_meta( $term_id, 'mlwf_attributes', true ) ) ) {
		return $value;
	}
	return 0;
}, 25, 2 );

// Kategorie-Filter auf Marken-Seiten einschalten (Marken ohne eigene Einstellung).
add_filter( 'acf/load_value/name=mlwf_show_subcategories', function ( $value, $post_id ) {
	if ( is_admin() || ( null !== $value && '' !== $value ) ) {
		return $value;
	}
	return janecka_brand_term_id_from_acf( $post_id ) ? 1 : $value;
}, 25, 2 );

// Sale-Unterkategorien (Damenuhren im Sale usw.) nicht in der Kategorie-Liste einer Marke anbieten;
// "Sale" selbst bleibt als Filter erhalten.
add_filter( 'mlwf_brand_subcategories', function ( $terms ) {
	$sale_children = array_values( janecka_sale_term_ids() );
	return array_values( array_filter( (array) $terms, function ( $term ) use ( $sale_children ) {
		return ! in_array( (int) $term->term_id, $sale_children, true );
	} ) );
} );
