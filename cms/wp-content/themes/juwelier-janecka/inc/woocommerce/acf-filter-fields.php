<?php
/**
 * ACF Feldgruppe: Produktfilter-Konfiguration per Kategorie
 *
 * Registriert Felder direkt auf der product_cat-Taxonomy.
 * Wird in functions.php via require_once eingebunden.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', 'janecka_register_filter_field_group' );

function janecka_register_filter_field_group(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

	// Alle verfügbaren Produktattribute als Choices für Checkbox-Feld ermitteln
	$attribute_choices = janecka_get_attribute_choices();
	$brand_choices     = janecka_get_brand_choices();

	acf_add_local_field_group( [
		'key'      => 'group_janecka_category_filters',
		'title'    => 'Produktfilter-Konfiguration',
		'fields'   => [

			// ── Vererbung ──────────────────────────────────────────────────
			[
				'key'               => 'field_filter_inherit_parent',
				'label'             => 'Filter von Elternkategorie übernehmen',
				'name'              => 'filter_inherit_parent',
				'type'              => 'true_false',
				'default_value'     => 1,
				'ui'                => 1,
				'ui_on_text'        => 'Ja',
				'ui_off_text'       => 'Nein',
				'instructions'      => 'Wenn aktiviert, werden die Filter der übergeordneten Kategorie verwendet (sofern keine eigene Konfiguration gesetzt ist).',
				'wrapper'           => [ 'width' => '100' ],
			],

			// ── Trennlinie ────────────────────────────────────────────────
			[
				'key'     => 'field_filter_divider_1',
				'label'   => 'Eigene Filter-Konfiguration',
				'type'    => 'message',
				'message' => '<p style="margin:0;color:#555;">Die folgenden Einstellungen gelten nur wenn "Filter von Elternkategorie übernehmen" deaktiviert ist — oder wenn dies die oberste Kategorie ist.</p>',
			],

			// ── Preis-Slider ──────────────────────────────────────────────
			[
				'key'           => 'field_filter_show_price',
				'label'         => 'Preis-Filter anzeigen',
				'name'          => 'filter_show_price',
				'type'          => 'true_false',
				'default_value' => 1,
				'ui'            => 1,
				'ui_on_text'    => 'Ja',
				'ui_off_text'   => 'Nein',
				'wrapper'       => [ 'width' => '100' ],
			],

			
			// ── Unterkategorien ──────────────────────────────────────────────
			[
				'key'           => 'field_filter_show_subcategories',
				'label'         => 'Unterkategorie-Filter anzeigen',
				'name'          => 'filter_show_subcategories',
				'type'          => 'true_false',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => 'Ja',
				'ui_off_text'   => 'Nein',
				'instructions'  => 'Zeigt die direkten Unterkategorien als Filter an (nur sinnvoll auf Elternkategorien).',
				'wrapper'       => [ 'width' => '100' ],
			],

			// ── Attribute ─────────────────────────────────────────────────
			[
				'key'           => 'field_filter_attributes',
				'label'         => 'Produkt-Attribute',
				'name'          => 'filter_attributes',
				'type'          => 'checkbox',
				'choices'       => $attribute_choices,
				'layout'        => 'vertical',
				'toggle'        => 1,
				'instructions'  => 'Welche Produktattribute sollen als Filter angezeigt werden?',
				'wrapper'       => [ 'width' => '50' ],
			],

			// ── Marken ────────────────────────────────────────────────────
			[
				'key'           => 'field_filter_brands',
				'label'         => 'Marken-Filter',
				'name'          => 'filter_brands',
				'type'          => 'true_false',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => 'Ja',
				'ui_off_text'   => 'Nein',
				'instructions'  => 'Soll ein Marken-Filter angezeigt werden?',
				'wrapper'       => [ 'width' => '50' ],
			],

			// ── Reihenfolge ───────────────────────────────────────────────
			[
				'key'           => 'field_filter_order',
				'label'         => 'Reihenfolge der Filter',
				'name'          => 'filter_order',
				'type'          => 'textarea',
				'rows'          => 4,
				'instructions'  => 'Optional: Einen Attribut-Slug pro Zeile, in gewünschter Reihenfolge. Nicht aufgeführte aktive Filter werden dahinter angehängt. Beispiel:<br><code>pa_filter-material</code><br><code>pa_filter-stein</code>',
				'wrapper'       => [ 'width' => '100' ],
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
		'menu_order'            => 0,
		'position'              => 'normal',
		'style'                 => 'default',
		'label_placement'       => 'top',
		'instruction_placement' => 'label',
		'active'                => true,
	] );
}

/**
 * Alle verfügbaren WooCommerce-Produktattribute als Choices-Array.
 */
function janecka_get_attribute_choices(): array {
	$choices    = [];
	$attributes = wc_get_attribute_taxonomies();

	foreach ( $attributes as $attr ) {
		$slug             = wc_attribute_taxonomy_name( $attr->attribute_name );
		$choices[ $slug ] = $attr->attribute_label . ' (' . $slug . ')';
	}

	return $choices;
}

/**
 * Marken-Taxonomien als Choices (pa_brand, pa_marke etc.)
 */
function janecka_get_brand_choices(): array {
	return [
		'pa_brand' => 'Marke (pa_brand)',
		'pa_marke' => 'Marke (pa_marke)',
	];
}
