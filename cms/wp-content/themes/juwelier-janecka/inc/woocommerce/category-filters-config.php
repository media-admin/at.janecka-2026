<?php
/**
 * Kategorie-Filter-Konfiguration
 *
 * Definiert, welche WooCommerce-Attribute-Filter pro Kategorie angezeigt werden.
 * Neuer Kategorien einfach hier ergänzen – kein Template nötig.
 *
 * Attribut-Slugs müssen in WooCommerce unter
 * Produkte → Attribute angelegt sein (z. B. pa_material, pa_edelstein …).
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gibt die Filter-Konfiguration pro Kategorie zurück.
 *
 * @return array<string, array{label: string, attributes: string[], show_price: bool}>
 */
function janecka_get_category_filter_config(): array {
    return [

        // ── Schmuck ────────────────────────────────────────────────────────
        'ringe' => [
            'label'       => 'Ringe',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_ringweite', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'armschmuck' => [
            'label'       => 'Armschmuck',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_laenge', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'halsschmuck' => [
            'label'       => 'Halsschmuck',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_laenge-halskette', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'ohrschmuck' => [
            'label'       => 'Ohrschmuck',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_geschlecht', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'anhaenger' => [
            'label'       => 'Anhänger',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'solitaerschmuck' => [
            'label'       => 'Solitärschmuck',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'charms' => [
            'label'       => 'Charms',
            'attributes'  => [ 'pa_filter-material', 'pa_kollektion' ],
            'show_price'  => true,
        ],

        // ── Uhren ──────────────────────────────────────────────────────────
        'damenuhren' => [
            'label'       => 'Damenuhren',
            'attributes'  => [ 'pa_brand', 'pa_filter-material', 'pa_zifferblatt', 'pa_uhrband' ],
            'show_price'  => true,
        ],
        'herrenuhren' => [
            'label'       => 'Herrenuhren',
            'attributes'  => [ 'pa_brand', 'pa_filter-material', 'pa_zifferblatt', 'pa_uhrband' ],
            'show_price'  => true,
        ],

        // ── Liebe & Hochzeit ───────────────────────────────────────────────
        'eheringe' => [
            'label'       => 'Eheringe',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_ringweite', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'verlobungsringe' => [
            'label'       => 'Verlobungsringe',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_ringweite', 'pa_kollektion' ],
            'show_price'  => true,
        ],
        'morgengabe' => [
            'label'       => 'Morgengabe',
            'attributes'  => [ 'pa_filter-material', 'pa_filter-stein', 'pa_kollektion' ],
            'show_price'  => true,
        ],

        // ── Fallback ───────────────────────────────────────────────────────
        '_default' => [
            'label'       => 'Alle Produkte',
            'attributes'  => [ 'pa_filter-material', 'pa_kollektion' ],
            'show_price'  => true,
        ],
    ];
}

/**
 * Gibt die Filter-Konfiguration für die aktuelle Kategorie zurück.
 */
function janecka_get_current_category_filter_config(): array {
	$config = janecka_get_category_filter_config();

	if ( is_product_category() ) {
		$term = get_queried_object();
		$slug = $term->slug ?? '';

		if ( isset( $config[ $slug ] ) ) {
			return $config[ $slug ];
		}

		// Eltern-Kategorie prüfen
		if ( ! empty( $term->parent ) ) {
			$parent = get_term( $term->parent, 'product_cat' );
			if ( $parent && isset( $config[ $parent->slug ] ) ) {
				return $config[ $parent->slug ];
			}
		}
	}

	return $config['_default'];
}

/**
 * Gibt alle registrierten WooCommerce-Attribute zurück,
 * inkl. Label-Mapping für die Darstellung im Filter.
 *
 * Neue Attribute hier ergänzen.
 */
function janecka_get_attribute_labels(): array {
    return [
        'pa_filter-material' => 'Material',
        'pa_filter-stein'    => 'Edelstein',
        'pa_ringweite'       => 'Ringgröße',
        'pa_laenge'          => 'Länge',
        'pa_laenge-halskette'=> 'Länge',
        'pa_kollektion'      => 'Kollektion',
        'pa_geschlecht'      => 'Für',
        'pa_brand'           => 'Marke',
        'pa_filter-farbe'    => 'Farbe',
        'pa_uhrband'         => 'Armband',
        'pa_zifferblatt'     => 'Zifferblatt',
        'pa_uhrwerk'         => 'Uhrwerk',
        'pa_ringbreite'      => 'Ringbreite',
        'pa_gravur'          => 'Gravur',
        'pa_anlass'          => 'Anlass',
        'pa_extras'          => 'Extras',
    ];
}
