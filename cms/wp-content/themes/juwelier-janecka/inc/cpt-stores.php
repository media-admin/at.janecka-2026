<?php
/**
 * CPT: Stores (Filialen)
 * Registriert den Custom Post Type "stores" sowie die Taxonomie "filialen-zahlungsweisen".
 */

// ─── CPT: stores ──────────────────────────────────────────────────────────────

add_action( 'init', 'janecka_register_cpt_stores' );

function janecka_register_cpt_stores() {

	$labels = [
		'name'                  => __( 'Filialen', 'juwelier-janecka' ),
		'singular_name'         => __( 'Filiale', 'juwelier-janecka' ),
		'menu_name'             => __( 'Filialen', 'juwelier-janecka' ),
		'add_new'               => __( 'Neue Filiale', 'juwelier-janecka' ),
		'add_new_item'          => __( 'Neue Filiale hinzufügen', 'juwelier-janecka' ),
		'edit_item'             => __( 'Filiale bearbeiten', 'juwelier-janecka' ),
		'new_item'              => __( 'Neue Filiale', 'juwelier-janecka' ),
		'view_item'             => __( 'Filiale ansehen', 'juwelier-janecka' ),
		'search_items'          => __( 'Filialen suchen', 'juwelier-janecka' ),
		'not_found'             => __( 'Keine Filialen gefunden', 'juwelier-janecka' ),
		'not_found_in_trash'    => __( 'Keine Filialen im Papierkorb', 'juwelier-janecka' ),
		'all_items'             => __( 'Alle Filialen', 'juwelier-janecka' ),
	];

	$args = [
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'query_var'          => true,
		'rewrite'            => [ 'slug' => 'filialen' ],
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => 25,
		'menu_icon'          => 'dashicons-store',
		'supports'           => [ 'title', 'thumbnail', 'excerpt' ],
	];

	register_post_type( 'stores', $args );
}


// ─── Taxonomie: Zahlungsweisen ─────────────────────────────────────────────────

add_action( 'init', 'janecka_register_taxonomy_zahlungsweisen' );

function janecka_register_taxonomy_zahlungsweisen() {

	$labels = [
		'name'              => __( 'Zahlungsweisen', 'juwelier-janecka' ),
		'singular_name'     => __( 'Zahlungsweise', 'juwelier-janecka' ),
		'search_items'      => __( 'Zahlungsweisen suchen', 'juwelier-janecka' ),
		'all_items'         => __( 'Alle Zahlungsweisen', 'juwelier-janecka' ),
		'edit_item'         => __( 'Zahlungsweise bearbeiten', 'juwelier-janecka' ),
		'update_item'       => __( 'Zahlungsweise aktualisieren', 'juwelier-janecka' ),
		'add_new_item'      => __( 'Neue Zahlungsweise', 'juwelier-janecka' ),
		'new_item_name'     => __( 'Name der neuen Zahlungsweise', 'juwelier-janecka' ),
		'menu_name'         => __( 'Zahlungsweisen', 'juwelier-janecka' ),
	];

	$args = [
		'labels'            => $labels,
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => [ 'slug' => 'zahlungsweisen' ],
	];

	register_taxonomy( 'filialen-zahlungsweisen', [ 'stores' ], $args );
}



add_action( 'acf/include_fields', 'janecka_register_store_mlb_location_field' );

function janecka_register_store_mlb_location_field() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    acf_add_local_field_group( [
        'key'    => 'group_store_booking',
        'title'  => __( 'Terminbuchung', 'juwelier-janecka' ),
        'fields' => [
            [
                'key'           => 'field_store_mlb_location',
                'label'         => __( 'Verknüpfter Buchungs-Standort', 'juwelier-janecka' ),
                'name'          => 'store-mlb-location',
                'type'          => 'post_object',
                'post_type'     => [ 'mlb_location' ],
                'return_format' => 'object',
                'ui'            => 1,
                'instructions'  => __( 'Wähle den passenden Standort aus dem Buchungs-Plugin aus.', 'juwelier-janecka' ),
            ],
        ],
        'location' => [
            [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'stores' ] ],
        ],
        'menu_order' => 10,
    ] );
}