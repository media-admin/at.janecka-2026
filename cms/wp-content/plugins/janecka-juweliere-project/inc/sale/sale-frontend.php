<?php
/**
 * Sale-Bereich: Frontend (Menü dynamisch ein-/ausblenden, Weiterleitungen).
 *
 * Ablage: cms/wp-content/plugins/janecka-juweliere-project/inc/sale/sale-frontend.php
 * Voraussetzung: sale-sync.php ist bereits eingebunden (nutzt dessen Helfer).
 *
 * - Menüpunkte, die auf "Sale" oder eine Sale-Unterkategorie zeigen (Typ Produktkategorie),
 *   werden nur ausgegeben, wenn die Kategorie mindestens ein veröffentlichtes Produkt hat.
 *   Das gilt für Desktop- und Mobile-Menü, weil der Filter vor dem Walker greift.
 *   Kindpunkte eines ausgeblendeten Punktes (z. B. das Mega-Menu-Bild) verschwinden mit.
 * - Alte Sale-Seiten /sale/sale-uhren/ und /sale/sale-schmuck/ leiten per 301 auf /sale/.
 * - Leere Sale-Unterkategorien leiten (302) auf /sale/ statt eine leere Seite zu zeigen.
 *
 * @package JaneckaJuweliereProject
 */

defined( 'ABSPATH' ) || exit;

/** Term-IDs von Sale und allen Sale-Unterkategorien. */
function janecka_sale_all_term_ids(): array {
	$ids = array_values( janecka_sale_term_ids() );
	$parent = janecka_sale_parent_id();
	if ( $parent ) {
		$ids[] = $parent;
	}
	return array_map( 'intval', $ids );
}

// Menü: leere Sale-Punkte ausblenden.
add_filter( 'wp_nav_menu_objects', function ( $items ) {
	$sale_ids = janecka_sale_all_term_ids();
	if ( empty( $sale_ids ) || ! is_array( $items ) ) {
		return $items;
	}

	$remove = array();
	foreach ( $items as $item ) {
		if ( 'taxonomy' !== $item->type || 'product_cat' !== $item->object ) {
			continue;
		}
		if ( ! in_array( (int) $item->object_id, $sale_ids, true ) ) {
			continue;
		}
		$term = get_term( (int) $item->object_id, 'product_cat' );
		if ( ! $term || is_wp_error( $term ) || (int) $term->count < 1 ) {
			$remove[ (int) $item->ID ] = true;
		}
	}

	if ( empty( $remove ) ) {
		return $items;
	}

	// Alle Nachfahren entfernter Punkte ebenfalls entfernen (Bild-Item, tiefere Ebenen).
	do {
		$changed = false;
		foreach ( $items as $item ) {
			$item_id   = (int) $item->ID;
			$parent_id = (int) $item->menu_item_parent;
			if ( ! isset( $remove[ $item_id ] ) && isset( $remove[ $parent_id ] ) ) {
				$remove[ $item_id ] = true;
				$changed            = true;
			}
		}
	} while ( $changed );

	return array_values( array_filter( $items, function ( $item ) use ( $remove ) {
		return ! isset( $remove[ (int) $item->ID ] );
	} ) );
}, 20 );

// Weiterleitungen.
add_action( 'template_redirect', function () {
	$parent_id = janecka_sale_parent_id();
	if ( ! $parent_id ) {
		return;
	}
	$parent_link = get_term_link( $parent_id, 'product_cat' );
	if ( is_wp_error( $parent_link ) ) {
		return;
	}

	// Alte Sale-Seiten.
	if ( is_page( array( 'sale/sale-uhren', 'sale/sale-schmuck' ) ) ) {
		wp_safe_redirect( $parent_link, 301 );
		exit;
	}

	// Leere Sale-Unterkategorie.
	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term && (int) $term->parent === $parent_id && (int) $term->count < 1 ) {
			wp_safe_redirect( $parent_link, 302 );
			exit;
		}
	}
} );

// Produkt-Permalink: Sale-Kategorien nie als Pfadbestandteil verwenden,
// damit sich die URL eines Produkts bei Beginn/Ende einer Aktion nicht ändert.
add_filter( 'wc_product_post_type_link_product_cat', function ( $category, $terms ) {
	$sale_ids = janecka_sale_all_term_ids();
	if ( ! $category instanceof WP_Term || ! in_array( (int) $category->term_id, $sale_ids, true ) ) {
		return $category;
	}
	foreach ( (array) $terms as $term ) {
		if ( $term instanceof WP_Term && ! in_array( (int) $term->term_id, $sale_ids, true ) ) {
			return $term;
		}
	}
	return $category;
}, 20, 2 );

// Produktseite: Breadcrumb darf nie über eine Sale-Kategorie laufen.
add_filter( 'woocommerce_breadcrumb_main_term', function ( $main_term, $terms ) {
	$sale_ids = janecka_sale_all_term_ids();
	if ( ! $main_term instanceof WP_Term || ! in_array( (int) $main_term->term_id, $sale_ids, true ) ) {
		return $main_term;
	}
	foreach ( (array) $terms as $term ) {
		if ( $term instanceof WP_Term && ! in_array( (int) $term->term_id, $sale_ids, true ) ) {
			return $term;
		}
	}
	return $main_term;
}, 20, 2 );