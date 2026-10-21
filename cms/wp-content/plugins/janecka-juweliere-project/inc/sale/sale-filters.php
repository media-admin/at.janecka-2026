<?php
/**
 * Sale-Bereich: dynamische Produktfilter.
 *
 * Ablage: cms/wp-content/plugins/janecka-juweliere-project/inc/sale/sale-filters.php
 * Voraussetzung: sale-sync.php und sale-frontend.php sind eingebunden;
 *                Plugin media-lab-woocommerce (mlwf_*-Funktionen) ist aktiv.
 *
 * Die Filterleiste der Kategorie-Archive liest ihre Attribute aus ACF (mlwf_attributes).
 * Für Sale und die Sale-Unterkategorien wird dieser Wert zur Laufzeit ersetzt: Es erscheinen
 * genau die Filter, die auch die Suche für diese Produktmenge anzeigen würde
 * (mlwf_search_facets(): nur Werte, die in mindestens einem Produkt vorkommen).
 * Zusätzlich:
 *   - Preisfilter immer an,
 *   - auf /sale/ eine Unterkategorie-Auswahl (Damenuhren, Herrenschmuck, ...), die nur
 *     Gruppen mit Produkten zeigt.
 * Nichts davon wird gespeichert; Änderungen am Sortiment wirken sofort.
 *
 * Die Funktion janecka_sale_dynamic_attributes() wird auch von brand-filters.php
 * (Marken-Archive) verwendet.
 *
 * @package JaneckaJuweliereProject
 */

defined( 'ABSPATH' ) || exit;

/** Term-ID aus einer ACF-Post-ID ("term_123" oder "product_cat_123"). */
function janecka_sale_term_id_from_acf( $post_id ): int {
	if ( is_string( $post_id ) && preg_match( '/^(?:term|product_cat)_(\d+)$/', $post_id, $m ) ) {
		return (int) $m[1];
	}
	return 0;
}

function janecka_sale_is_sale_term( int $term_id ): bool {
	return $term_id > 0 && in_array( $term_id, janecka_sale_all_term_ids(), true );
}

/**
 * Attribut-/Marken-Taxonomien, die für die Produkte einer Kategorie bzw. Marke als Filter sinnvoll sind.
 *
 * @param int    $term_id  Term-ID der Kategorie bzw. Marke
 * @param string $taxonomy product_cat (Standard) oder product_brand
 */
function janecka_sale_dynamic_attributes( int $term_id, string $taxonomy = 'product_cat' ): array {
	static $cache = array();

	$key = $taxonomy . ':' . $term_id;
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}
	$cache[ $key ] = array();

	if ( ! function_exists( 'mlwf_get_context_product_ids' ) || ! function_exists( 'mlwf_search_facets' ) ) {
		return $cache[ $key ];
	}

	$ids = array_filter(
		mlwf_get_context_product_ids( $term_id, $taxonomy ),
		static function ( $id ) {
			return 'publish' === get_post_status( $id );
		}
	);
	if ( empty( $ids ) ) {
		return $cache[ $key ];
	}

	// Die Suche blendet Filter mit nur einem Wert aus. Bei gemischtem Sortiment (z. B. Uhren und
	// Schmuck im Sale) trennt auch ein einzelner Wert, z. B. "Quartz", Uhren von Schmuck. Daher
	// Mindestzahl auf 1 senken und selbst entscheiden: ein Ein-Wert-Filter bleibt, wenn nicht
	// alle Produkte den Wert tragen.
	$min_terms = static function () {
		return 1;
	};
	add_filter( 'mlwf_search_facet_min_terms', $min_terms );
	$facets = mlwf_search_facets( $ids );
	remove_filter( 'mlwf_search_facet_min_terms', $min_terms );

	unset( $facets['product_cat'] ); // Kategorien laufen über die Unterkategorie-Auswahl
	if ( 'product_brand' === $taxonomy ) {
		unset( $facets['product_brand'] ); // Auf einer Marken-Seite steht die Marke bereits fest
	}

	$total = count( $ids );
	foreach ( $facets as $facet_taxonomy => $facet ) {
		if ( count( $facet['terms'] ) < 2 && (int) $facet['terms'][0]['count'] >= $total ) {
			unset( $facets[ $facet_taxonomy ] ); // alle Produkte haben denselben Wert: filtert nichts
		}
	}

	$cache[ $key ] = array_keys( $facets );

	return $cache[ $key ];
}

// Attribute: dynamisch statt aus dem Term-Editor (nur Frontend).
add_filter( 'acf/load_value/name=mlwf_attributes', function ( $value, $post_id ) {
	if ( is_admin() ) {
		return $value;
	}
	$term_id = janecka_sale_term_id_from_acf( $post_id );
	return janecka_sale_is_sale_term( $term_id ) ? janecka_sale_dynamic_attributes( $term_id ) : $value;
}, 20, 2 );

// Preisfilter: bei Sale immer an.
add_filter( 'acf/load_value/name=mlwf_show_price', function ( $value, $post_id ) {
	if ( is_admin() ) {
		return $value;
	}
	return janecka_sale_is_sale_term( janecka_sale_term_id_from_acf( $post_id ) ) ? 1 : $value;
}, 20, 2 );

// Unterkategorie-Auswahl: nur auf der Elternkategorie "Sale".
add_filter( 'acf/load_value/name=mlwf_show_subcategories', function ( $value, $post_id ) {
	if ( is_admin() ) {
		return $value;
	}
	$term_id = janecka_sale_term_id_from_acf( $post_id );
	if ( ! janecka_sale_is_sale_term( $term_id ) ) {
		return $value;
	}
	return $term_id === janecka_sale_parent_id() ? 1 : 0;
}, 20, 2 );

// Die Unterkategorie-Auswahl lädt mit hide_empty=false: Sale-Gruppen ohne Produkte ausblenden.
// Achtung: get_term_by() ruft intern get_terms() auf. Ohne Wiederholungsschutz wäre das eine Endlosschleife.
add_filter( 'get_terms_args', function ( $args, $taxonomies ) {
	static $busy = false;

	if ( $busy || is_admin() || empty( $args['parent'] ) || ! in_array( 'product_cat', (array) $taxonomies, true ) ) {
		return $args;
	}

	$busy   = true;
	$parent = janecka_sale_parent_id();
	$busy   = false;

	if ( $parent && (int) $args['parent'] === $parent ) {
		$args['hide_empty'] = true;
	}
	return $args;
}, 10, 2 );
