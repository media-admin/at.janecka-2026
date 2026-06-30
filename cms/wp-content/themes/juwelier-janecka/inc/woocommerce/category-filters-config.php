<?php
/**
 * Produktfilter-Konfiguration
 *
 * Liest Filter-Einstellungen aus ACF-Feldern auf product_cat-Taxonomy.
 * Unterstützt Vererbung von Elternkategorien.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gibt die Filter-Konfiguration für eine bestimmte Kategorie zurück.
 * Vererbungslogik: Eigene Config → Elternkategorie → Fallback.
 *
 * @param int|null $term_id Term-ID der Produktkategorie (null = aktuelle Seite)
 * @return array{
 *     attributes: string[],
 *     show_price: bool,
 *     show_brands: bool,
 *     show_subcategories: bool,
 *     source: string
 * }
 */
function janecka_get_category_filter_config( ?int $term_id = null ): array {
	if ( $term_id === null ) {
		if ( is_product_category() ) {
			$term_id = get_queried_object_id();
		} else {
			return janecka_get_default_filter_config();
		}
	}

	return janecka_resolve_filter_config( $term_id, [] );
}

/**
 * Alias für Abwärtskompatibilität
 */
function janecka_get_current_category_filter_config(): array {
	return janecka_get_category_filter_config();
}

/**
 * Rekursive Auflösung der Filter-Konfiguration mit Vererbung.
 */
function janecka_resolve_filter_config( int $term_id, array $visited ): array {
	if ( in_array( $term_id, $visited, true ) ) {
		return janecka_get_default_filter_config();
	}
	$visited[] = $term_id;

	$inherit      = (bool) get_field( 'filter_inherit_parent',     'product_cat_' . $term_id );
	$show_price   = get_field( 'filter_show_price',                'product_cat_' . $term_id );
	$attributes   = get_field( 'filter_attributes',                'product_cat_' . $term_id );
	$show_brands  = get_field( 'filter_brands',                    'product_cat_' . $term_id );
	$show_subcats = get_field( 'filter_show_subcategories',        'product_cat_' . $term_id );
	$order_raw    = get_field( 'filter_order',                     'product_cat_' . $term_id );

	$has_own_config = ! empty( $attributes ) || $show_brands;

	// Vererbung: keine eigene Config → Elternkategorie prüfen
	if ( $inherit && ! $has_own_config ) {
		$term = get_term( $term_id, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) && $term->parent > 0 ) {
			$parent_config                       = janecka_resolve_filter_config( $term->parent, $visited );
			$parent_config['source']             = 'parent:' . $term->parent;
			$parent_config['show_subcategories'] = (bool) $show_subcats;
			return $parent_config;
		}
		return janecka_get_default_filter_config();
	}

	// Attribute aufbauen
	$attribute_slugs = is_array( $attributes ) ? $attributes : [];

	// Marken-Filter einbauen
	if ( $show_brands ) {
		foreach ( [ 'pa_brand', 'pa_marke' ] as $brand_slug ) {
			if ( taxonomy_exists( $brand_slug ) && ! in_array( $brand_slug, $attribute_slugs, true ) ) {
				$attribute_slugs[] = $brand_slug;
				break;
			}
		}
	}

	// Reihenfolge anwenden
	if ( $order_raw ) {
		$ordered         = array_filter( array_map( 'trim', explode( "\n", $order_raw ) ) );
		$rest            = array_diff( $attribute_slugs, $ordered );
		$attribute_slugs = array_values( array_merge(
			array_intersect( $ordered, $attribute_slugs ),
			$rest
		) );
	}

	return [
		'attributes'         => $attribute_slugs,
		'show_price'         => $show_price !== false ? (bool) $show_price : true,
		'show_brands'        => (bool) $show_brands,
		'show_subcategories' => (bool) $show_subcats,
		'source'             => 'term:' . $term_id,
	];
}

/**
 * Standard-Konfiguration wenn keine ACF-Einstellungen vorhanden.
 */
function janecka_get_default_filter_config(): array {
	return [
		'attributes'         => [],
		'show_price'         => true,
		'show_brands'        => false,
		'show_subcategories' => false,
		'source'             => 'default',
	];
}

/**
 * Labels für Attribut-Slugs.
 */
function janecka_get_attribute_labels(): array {
	$labels     = [];
	$attributes = wc_get_attribute_taxonomies();

	foreach ( $attributes as $attr ) {
		$slug            = wc_attribute_taxonomy_name( $attr->attribute_name );
		$labels[ $slug ] = $attr->attribute_label;
	}

	return $labels;
}
