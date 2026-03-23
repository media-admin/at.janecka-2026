<?php
/**
 * Custom Post Types
 * 
 * Register all custom post types for the agency core functionality.
 * These CPTs persist across theme changes.
 * 
 * @package Agency_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Team CPT
 */
function agency_core_register_team_cpt() {
    $labels = array(
        'name' => __('Team', 'agency-core'),
        'singular_name' => __('Team Mitglied', 'agency-core'),
        'menu_name' => __('Team', 'agency-core'),
        'add_new' => __('Neu hinzufügen', 'agency-core'),
        'add_new_item' => __('Neues Team Mitglied', 'agency-core'),
        'edit_item' => __('Team Mitglied bearbeiten', 'agency-core'),
        'new_item' => __('New Team Member', 'agency-core'),
        'view_item' => __('View Team Member', 'agency-core'),
        'search_items' => __('Search Team', 'agency-core'),
        'not_found' => __('No team members found', 'agency-core'),
        'not_found_in_trash' => __('No team members found in trash', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
        'menu_icon' => 'dashicons-groups',
        'menu_position' => 20,
        'rewrite' => array('slug' => 'team'),
        'capability_type' => 'post',
    );
    
    register_post_type('team', $args);
}
add_action('init', 'agency_core_register_team_cpt');


/**
 * Register Projects CPT
 */
function agency_core_register_projects_cpt() {
    $labels = array(
        'name' => __('Projekte', 'agency-core'),
        'singular_name' => __('Projekt', 'agency-core'),
        'menu_name' => __('Projekte', 'agency-core'),
        'add_new' => __('Neu hinzufügen', 'agency-core'),
        'add_new_item' => __('Neues Projekt', 'agency-core'),
        'edit_item' => __('Projekt bearbeiten', 'agency-core'),
        'new_item' => __('New Project', 'agency-core'),
        'view_item' => __('Projekt ansehen', 'agency-core'),
        'search_items' => __('Projekte suchen', 'agency-core'),
        'not_found' => __('Keine Projekte gefunden', 'agency-core'),
        'not_found_in_trash' => __('Keine Projekte im Papierkorb gefunden', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
        'menu_icon' => 'dashicons-portfolio',
        'menu_position' => 21,
        'rewrite' => array('slug' => 'projekte'),
        'capability_type' => 'post',
        'taxonomies' => array('project_category'),
    );
    
    register_post_type('project', $args);
}
add_action('init', 'agency_core_register_projects_cpt');


/**
 * Register Project Categories
 */
function agency_core_register_project_categories() {
    $labels = array(
        'name' => __('Projekt Kategorien', 'agency-core'),
        'singular_name' => __('Projekt Kategorie', 'agency-core'),
        'search_items' => __('Kategorien durchsuchen', 'agency-core'),
        'all_items' => __('Alle Kategorien', 'agency-core'),
        'parent_item' => __('Übergeordnete Kategorie', 'agency-core'),
        'parent_item_colon' => __('Übergeordnete Kategorie:', 'agency-core'),
        'edit_item' => __('Kategorie bearbeiten', 'agency-core'),
        'update_item' => __('Kategorie aktualisieren', 'agency-core'),
        'add_new_item' => __('Neue Kategorie hinzufügen', 'agency-core'),
        'new_item_name' => __('Neuer Kategorie-Name', 'agency-core'),
        'menu_name' => __('Projekt Kategorien', 'agency-core'),
    );
    
    register_taxonomy('project_category', array('project'), array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'projekt-kategorie'),
    ));
}
add_action('init', 'agency_core_register_project_categories');


/**
 * Register Testimonials CPT
 */
function agency_core_register_testimonials_cpt() {
    $labels = array(
        'name' => __('Testimonials', 'agency-core'),
        'singular_name' => __('Testimonial', 'agency-core'),
        'menu_name' => __('Testimonials', 'agency-core'),
        'add_new' => __('Neu hinzufügen', 'agency-core'),
        'add_new_item' => __('Neues Testimonial', 'agency-core'),
        'edit_item' => __('Testimonial bearbeiten', 'agency-core'),
        'new_item' => __('Neues Testimonial', 'agency-core'),
        'view_item' => __('Testimonial', 'agency-core'),
        'search_items' => __('Testimonials durchsuchen', 'agency-core'),
        'not_found' => __('Keine Testimonials gefunden', 'agency-core'),
        'not_found_in_trash' => __('Keine Testimonials im Papierkorb gefunden', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'menu_icon' => 'dashicons-testimonial',
        'menu_position' => 22,
        'rewrite' => array('slug' => 'testimonials'),
        'capability_type' => 'post',
    );
    
    register_post_type('testimonial', $args);
}
add_action('init', 'agency_core_register_testimonials_cpt');


/**
 * Register Services CPT
 */
function agency_core_register_services_cpt() {
    $labels = array(
        'name' => __('Leistungen', 'agency-core'),
        'singular_name' => __('Leistung', 'agency-core'),
        'menu_name' => __('Leistungen', 'agency-core'),
        'add_new' => __('Neu hinzufügen', 'agency-core'),
        'add_new_item' => __('Neue Leistung', 'agency-core'),
        'edit_item' => __('Leistung bearbeiten', 'agency-core'),
        'new_item' => __('Neue Leistung', 'agency-core'),
        'view_item' => __('Leistung anzeigen', 'agency-core'),
        'search_items' => __('Leistungen durchsuchen', 'agency-core'),
        'not_found' => __('Keine Leistungen gefunden', 'agency-core'),
        'not_found_in_trash' => __('Keine Leistungen im Papierkorb gefunden', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'page-attributes'),
        'menu_icon' => 'dashicons-admin-tools',
        'menu_position' => 23,
        'rewrite' => array('slug' => 'leistungen'),
        'capability_type' => 'post',
    );
    
    register_post_type('service', $args);
}
add_action('init', 'agency_core_register_services_cpt');


/**
 * Register FAQ CPT
 */
function agency_core_register_faq_cpt() {
    $labels = array(
        'name' => __('FAQ', 'agency-core'),
        'singular_name' => __('Frage', 'agency-core'),
        'menu_name' => __('Fragen', 'agency-core'),
        'add_new' => __('Neu hinzufügen', 'agency-core'),
        'add_new_item' => __('Neue Frage', 'agency-core'),
        'edit_item' => __('Frage bearbeiten', 'agency-core'),
        'new_item' => __('Neue Frage', 'agency-core'),
        'view_item' => __('Frage anzeigen', 'agency-core'),
        'search_items' => __('Fragen durchsuchen', 'agency-core'),
        'not_found' => __('Keine Fragen gefunden', 'agency-core'),
        'not_found_in_trash' => __('Keine Fragen im Papierkorb gefunden', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'description' => __('Frequently Asked Questions', 'agency-core'),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 24,
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => false,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'page-attributes'),
        'menu_icon' => 'dashicons-editor-help',
        'rewrite' => array('slug' => 'faq'),
    );
    
    register_post_type('faq', $args);
}
add_action('init', 'agency_core_register_faq_cpt');





/**
 * Register Google Maps Post Type
 */
function agency_core_register_maps_cpt() {
    $labels = array(
        'name' => _x('Maps', 'Post Type General Name', 'agency-core'),
        'singular_name' => _x('Map', 'Post Type Singular Name', 'agency-core'),
        'menu_name' => __('Google Maps', 'agency-core'),
        'name_admin_bar' => __('Map', 'agency-core'),
        'all_items' => __('Alle Maps', 'agency-core'),
        'add_new_item' => __('Neue Map hinzufügen', 'agency-core'),
        'add_new' => __('Neue hinzufügen', 'agency-core'),
        'new_item' => __('Neue Map', 'agency-core'),
        'edit_item' => __('Map bearbeiten', 'agency-core'),
        'update_item' => __('Map updaten', 'agency-core'),
        'view_item' => __('Map anzeigen', 'agency-core'),
        'search_items' => __('Map suchen', 'agency-core'),
        'not_found' => __('Nichts gefunden', 'agency-core'),
        'not_found_in_trash' => __('Nichts im Papierkorb gefunden', 'agency-core'),
    );
    
    $args = array(
        'label' => __('Google Map', 'agency-core'),
        'description' => __('Google Maps Locations', 'agency-core'),
        'labels' => $labels,
        'supports' => array('title'),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 25,
        'menu_icon' => 'dashicons-location-alt',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => false,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
        'show_in_rest' => true,
    );
    
    register_post_type('gmap', $args);
}
add_action('init', 'agency_core_register_maps_cpt');


/**
 * Register Hero Slide Post Type
 */
function agency_core_register_hero_slide_cpt() {
    $labels = array(
        'name' => _x('Hero Slides', 'Post Type General Name', 'agency-core'),
        'singular_name' => _x('Hero Slide', 'Post Type Singular Name', 'agency-core'),
        'menu_name' => __('Hero Slides', 'agency-core'),
        'name_admin_bar' => __('Hero Slide', 'agency-core'),
        'archives' => __('Hero Slide Archive', 'agency-core'),
        'attributes' => __('Hero Slide Attribute', 'agency-core'),
        'parent_item_colon' => __('Übergeordnete Hero Slide:', 'agency-core'),
        'all_items' => __('Alle Hero Slides', 'agency-core'),
        'add_new_item' => __('Neue Hero Slide hinzufügen', 'agency-core'),
        'add_new' => __('Neue Hero Slide', 'agency-core'),
        'new_item' => __('Neue Hero Slide', 'agency-core'),
        'edit_item' => __('Hero Slide bearbeiten', 'agency-core'),
        'update_item' => __('Hero Slide updaten', 'agency-core'),
        'view_item' => __('View Hero Slide anzeigen', 'agency-core'),
        'view_items' => __('Hero Slides anzeigen', 'agency-core'),
        'search_items' => __('Hero Slide durchsuchen', 'agency-core'),
        'not_found' => __('Nichts gefunden', 'agency-core'),
        'not_found_in_trash' => __('Nichts im Papierkorb gefunden', 'agency-core'),
        'featured_image' => __('Featured Image', 'agency-core'),
        'set_featured_image' => __('Featured Image festlegen', 'agency-core'),
        'remove_featured_image' => __('Featured Image entfernen', 'agency-core'),
        'use_featured_image' => __('Als Featured Image verwenden', 'agency-core'),
        'insert_into_item' => __('Zur Hero Slide einfügen', 'agency-core'),
        'uploaded_to_this_item' => __('Zu dieser Hero Slide hochladen', 'agency-core'),
        'items_list' => __('Hero Slides Liste', 'agency-core'),
        'items_list_navigation' => __('Hero Slides Listen-Navigation', 'agency-core'),
        'filter_items_list' => __('Hero Slides Liste filtern', 'agency-core'),
    );
    
    $args = array(
        'label' => __('Hero Slide', 'agency-core'),
        'description' => __('Hero Slider Slides', 'agency-core'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail'),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 26,
        'menu_icon' => 'dashicons-slides',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => false,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
        'show_in_rest' => true,
    );
    
    register_post_type('hero_slide', $args);
}
add_action('init', 'agency_core_register_hero_slide_cpt');


/**
 * Register Carousel Post Type
 */
function agency_core_register_carousel_cpt() {
    $labels = array(
        'name' => _x('Karussell Elemente', 'Post Type General Name', 'agency-core'),
        'singular_name' => _x('Karussell Element', 'Post Type Singular Name', 'agency-core'),
        'menu_name' => __('Karussells', 'agency-core'),
        'name_admin_bar' => __('Karussell Element', 'agency-core'),
        'archives' => __('Karussell Archiv', 'agency-core'),
        'attributes' => __('Karussell Attribute', 'agency-core'),
        'all_items' => __('Alle Elemente', 'agency-core'),
        'add_new_item' => __('Neues Element hinzufügen', 'agency-core'),
        'add_new' => __('Neues hinzufügen', 'agency-core'),
        'new_item' => __('Neues Element', 'agency-core'),
        'edit_item' => __('Element bearbeiten', 'agency-core'),
        'update_item' => __('Element updaten', 'agency-core'),
        'view_item' => __('Element anzeigen', 'agency-core'),
        'view_items' => __('Elemente anzeigen', 'agency-core'),
        'search_items' => __('Element suchen', 'agency-core'),
        'not_found' => __('Nichts gefunden', 'agency-core'),
        'not_found_in_trash' => __('Nichts im Papierkorb gefunden', 'agency-core'),
    );
    
    $args = array(
        'label' => __('Karussell Element', 'agency-core'),
        'description' => __('Karussell Elemente', 'agency-core'),
        'labels' => $labels,
        'supports' => array('title', 'editor', 'thumbnail', 'page-attributes'),
        'hierarchical' => false,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 27,
        'menu_icon' => 'dashicons-images-alt2',
        'show_in_admin_bar' => true,
        'show_in_nav_menus' => false,
        'can_export' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'publicly_queryable' => false,
        'capability_type' => 'post',
        'show_in_rest' => true,
    );
    
    register_post_type('carousel', $args);
}
add_action('init', 'agency_core_register_carousel_cpt');


/**
 * Register Jobs Post Type
 */
function agency_core_register_jobs_cpt() {
    $labels = array(
        'name' => __('Jobs', 'agency-core'),
        'singular_name' => __('Job', 'agency-core'),
        'menu_name' => __('Jobs', 'agency-core'),
        'add_new' => __('Add New', 'agency-core'),
        'add_new_item' => __('Add New Job', 'agency-core'),
        'edit_item' => __('Edit Job', 'agency-core'),
        'new_item' => __('New Job', 'agency-core'),
        'view_item' => __('View Job', 'agency-core'),
        'search_items' => __('Search Jobs', 'agency-core'),
        'not_found' => __('No jobs found', 'agency-core'),
        'not_found_in_trash' => __('No jobs found in trash', 'agency-core'),
        'all_items' => __('All Jobs', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-businessperson',
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'revisions'),
        'rewrite' => array('slug' => 'jobs'),
        'show_in_menu' => true,
        'menu_position' => 28,
        'taxonomies' => array('job_category', 'job_type', 'job_location'),
    );
    
    register_post_type('job', $args);
}
add_action('init', 'agency_core_register_jobs_cpt');




/**
 * Register Store Post Type
 */
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_6119c7a444568',
	'title' => 'Filialen',
	'fields' => array(
		array(
			'key' => 'field_6153213ab5911',
			'label' => 'Aktuelle Meldung',
			'name' => 'store-aktuelle-meldung',
			'aria-label' => '',
			'type' => 'wysiwyg',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_6119c8a1a957e',
			'label' => 'Kurzbeschreibung',
			'name' => 'store-kurzbeschreibung',
			'aria-label' => '',
			'type' => 'wysiwyg',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_6119c7aea9575',
			'label' => 'Straße',
			'name' => 'store-strasse',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119c7c8a9576',
			'label' => 'PLZ',
			'name' => 'store-plz',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119c7f1a9577',
			'label' => 'Ort',
			'name' => 'store-ort',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119c7f9a9578',
			'label' => 'Telefon',
			'name' => 'store-telefon',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119c809a9579',
			'label' => 'EMail',
			'name' => 'store-email',
			'aria-label' => '',
			'type' => 'email',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_6119c831a957b',
			'label' => 'Öffnungszeiten',
			'name' => 'store-oeffnungszeiten',
			'aria-label' => '',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'collapsed' => '',
			'min' => 0,
			'max' => 0,
			'layout' => 'table',
			'button_label' => 'Eintrag hinzufügen',
			'sub_fields' => array(
				array(
					'key' => 'field_612d5300c6dab',
					'label' => 'Montag geöffnet',
					'name' => 'monday_opened',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d536cc6dad',
					'label' => 'Montag geschlossen',
					'name' => 'monday_closed',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d53a9c6db0',
					'label' => 'Dienstag geöffnet',
					'name' => 'thuesday_opened',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d538fc6daf',
					'label' => 'Dienstag geschlossen',
					'name' => 'thuesday_closed',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d53c971ad4',
					'label' => 'Mittwoch geöffnet',
					'name' => 'wednesday_opened',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d53da71ad5',
					'label' => 'Mittwoch geschlossen',
					'name' => 'wednesday_closed',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d53f271ad6',
					'label' => 'Donnerstag geöffnet',
					'name' => 'thursday_opened',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d541671ad7',
					'label' => 'Donnerstag geschlossen',
					'name' => 'thuesday_closed',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d543571ad8',
					'label' => 'Freitag geöffnet',
					'name' => 'friday_opened',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d54718fece',
					'label' => 'Freitag geschlossen',
					'name' => 'friday_closed',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d549aeefbe',
					'label' => 'Samstag geöffnet',
					'name' => 'saturday_opened',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
				array(
					'key' => 'field_612d5486eefbd',
					'label' => 'Samstag geschlossen',
					'name' => 'saturday_closed',
					'aria-label' => '',
					'type' => 'time_picker',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'display_format' => 'H:i',
					'return_format' => 'H:i',
					'parent_repeater' => 'field_6119c831a957b',
				),
			),
			'rows_per_page' => 20,
		),
		array(
			'key' => 'field_6176c374935fe',
			'label' => 'Filialen-Marken-Schlagwort',
			'name' => 'store-brand-tag',
			'aria-label' => '',
			'type' => 'taxonomy',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'taxonomy' => 'product_tag',
			'field_type' => 'checkbox',
			'add_term' => 1,
			'save_terms' => 0,
			'load_terms' => 0,
			'return_format' => 'object',
			'multiple' => 0,
			'allow_null' => 0,
			'bidirectional_target' => array(
			),
		),
		array(
			'key' => 'field_6119c890a957d',
			'label' => 'Außenfoto',
			'name' => 'store-aussenfoto',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_6119c8bfa957f',
			'label' => 'Bildergalerie',
			'name' => 'store-bildergalerie',
			'aria-label' => '',
			'type' => 'gallery',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'insert' => 'append',
			'library' => 'all',
			'min' => '',
			'max' => '',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_6119c817a957a',
			'label' => 'Google Map',
			'name' => 'store-map',
			'aria-label' => '',
			'type' => 'google_map',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'center_lat' => '',
			'center_lng' => '',
			'zoom' => '',
			'height' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'stores',
			),
		),
	),
	'menu_order' => 3,
	'position' => 'acf_after_title',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'discussion',
		1 => 'comments',
		2 => 'format',
		3 => 'page_attributes',
		4 => 'featured_image',
		5 => 'categories',
		6 => 'tags',
		7 => 'send-trackbacks',
	),
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
	'display_title' => '',
) );
} );


/**
 * Register Payment Method Post Type
 */
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_615421596b0d1',
	'title' => 'Filialen-Zahlungsweisen-Erweiterung',
	'fields' => array(
		array(
			'key' => 'field_615421596e8b0',
			'label' => 'Zahlungsweisen-Logo',
			'name' => 'zahlungsweisen-logo',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'taxonomy',
				'operator' => '==',
				'value' => 'filialen-zahlungsweisen',
			),
		),
	),
	'menu_order' => 2,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'discussion',
		1 => 'comments',
		2 => 'format',
		3 => 'page_attributes',
		4 => 'featured_image',
		5 => 'categories',
		6 => 'tags',
		7 => 'send-trackbacks',
	),
	'active' => true,
	'description' => '',
	'show_in_rest' => false,
	'display_title' => '',
) );
} );


/**
 * Register Eheringe Post Type
 */
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_6119bc04e6960',
	'title' => 'Eheringe',
	'fields' => array(
		array(
			'key' => 'field_6119bc94a9db7',
			'label' => 'Damenring Artikelnummer',
			'name' => 'damenring_artikelnummer',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119bcbda9db9',
			'label' => 'Damenring Diamant',
			'name' => 'damenring_diamant',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_61b0cdfea5d6c',
			'label' => 'Damenring Anzahl Steine',
			'name' => 'damenring_anzahl_steine',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119bcdea9dbb',
			'label' => 'Damenring Basispreis',
			'name' => 'damenring_basispreis',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119bc0ea9db2',
			'label' => 'Herrenring Artikelnummer',
			'name' => 'herrenring_artikelnummer',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119bccba9dba',
			'label' => 'Herrenring Basispreis',
			'name' => 'herrenring_basispreis',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_6119bcada9db8',
			'label' => 'Herrenring Diamant',
			'name' => 'herrenring_diamant',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'product',
			),
		),
	),
	'menu_order' => 4,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
	'display_title' => '',
) );
} );


/**
 * Register Brand Details Post Type
 */
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_617bda9205790',
	'title' => 'Marken-Details',
	'fields' => array(
		array(
			'key' => 'field_617be14d6dd6b',
			'label' => 'Marken-Hauptlogo',
			'name' => 'marken-hauptlogo',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_617bda921334b',
			'label' => 'Marken-Banner',
			'name' => 'marken-banner',
			'aria-label' => '',
			'type' => 'gallery',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'insert' => 'append',
			'library' => 'all',
			'min' => 0,
			'max' => 0,
			'min_width' => 0,
			'min_height' => 0,
			'min_size' => 0,
			'max_width' => 0,
			'max_height' => 0,
			'max_size' => 0,
			'mime_types' => '',
		),
		array(
			'key' => 'field_618d24e03643f',
			'label' => 'Marken-Beschreibung',
			'name' => 'marken-beschreibung',
			'aria-label' => '',
			'type' => 'wysiwyg',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_617be0ec9cd4c',
			'label' => 'Marken-Kategorie',
			'name' => 'marken-kategorie',
			'aria-label' => '',
			'type' => 'checkbox',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'Uhren' => 'Uhren',
				'Schmuck' => 'Schmuck',
				'Verlobung' => 'Verlobung',
				'Hochzeit' => 'Hochzeit',
			),
			'allow_custom' => 0,
			'default_value' => array(
			),
			'layout' => 'vertical',
			'toggle' => 0,
			'return_format' => 'value',
			'save_custom' => 0,
			'custom_choice_button_text' => 'Eine neue Auswahlmöglichkeit hinzufügen',
		),
		array(
			'key' => 'field_617bda921335d',
			'label' => 'Hersteller-Logo 1c Dunkel',
			'name' => 'hersteller-logo_1c_dunkel',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_617bda9213362',
			'label' => 'Hersteller-Logo 1c Hell',
			'name' => 'hersteller-logo_4c_dunkel_Kopie',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_617bda921336b',
			'label' => 'Hersteller-Logo 4c Dunkel',
			'name' => 'hersteller-logo_4c_dunkel',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_617bda9213373',
			'label' => 'Hersteller-Logo 4c Hell',
			'name' => 'hersteller-logo_4c_hell',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'taxonomy',
				'operator' => '==',
				'value' => 'pa_marke',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'acf_after_title',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'the_content',
		1 => 'excerpt',
		2 => 'discussion',
		3 => 'comments',
		4 => 'format',
		5 => 'page_attributes',
		6 => 'featured_image',
		7 => 'tags',
		8 => 'send-trackbacks',
	),
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
	'display_title' => '',
) );
} );




/**
 * Register Brand Extension Post Type
 */
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_61198e07222ac',
	'title' => 'Marken-Erweiterung',
	'fields' => array(
		array(
			'key' => 'field_6132988986114',
			'label' => 'Marken-Banner',
			'name' => 'marken-banner',
			'aria-label' => '',
			'type' => 'gallery',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'insert' => 'append',
			'library' => 'all',
			'min' => 0,
			'max' => 0,
			'min_width' => 0,
			'min_height' => 0,
			'min_size' => 0,
			'max_width' => 0,
			'max_height' => 0,
			'max_size' => 0,
			'mime_types' => '',
		),
		array(
			'key' => 'field_613020e14ba9a',
			'label' => 'Hersteller-Logo 1c Dunkel',
			'name' => 'hersteller-logo_1c_dunkel',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_613020ce4315c',
			'label' => 'Hersteller-Logo 1c Hell',
			'name' => 'hersteller-logo_4c_dunkel_Kopie',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_613020bffe414',
			'label' => 'Hersteller-Logo 4c Dunkel',
			'name' => 'hersteller-logo_4c_dunkel',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_61198e100d133',
			'label' => 'Hersteller-Logo 4c Hell',
			'name' => 'hersteller-logo_4c_hell',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'marken',
			),
		),
	),
	'menu_order' => 5,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => array(
		0 => 'discussion',
		1 => 'comments',
		2 => 'format',
		3 => 'page_attributes',
		4 => 'featured_image',
		5 => 'categories',
		6 => 'tags',
		7 => 'send-trackbacks',
	),
	'active' => true,
	'description' => '',
	'show_in_rest' => false,
	'display_title' => '',
) );
} );


/**
 * Register Brand Tag Details Post Type
 */
add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_61a8d36079a55',
	'title' => 'Marken-Tag Details',
	'fields' => array(
		array(
			'key' => 'field_61a8d39dfc354',
			'label' => 'Marken-Hauptlogo',
			'name' => 'brand-logo-main',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_61a8d3b4fc355',
			'label' => 'Marken-Banner',
			'name' => 'brand-banner',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'full',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_61a8d3d7fc356',
			'label' => 'Marken-Beschreibung',
			'name' => 'brand-description',
			'aria-label' => '',
			'type' => 'wysiwyg',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_61a8d3effc357',
			'label' => 'Marken-Kategorie',
			'name' => 'brand-category',
			'aria-label' => '',
			'type' => 'checkbox',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'Uhren' => 'Uhren',
				'Schmuck' => 'Schmuck',
				'Liebe & Hochzeit' => 'Liebe & Hochzeit',
			),
			'allow_custom' => 0,
			'default_value' => array(
			),
			'layout' => 'horizontal',
			'toggle' => 0,
			'return_format' => 'value',
			'save_custom' => 0,
			'custom_choice_button_text' => 'Eine neue Auswahlmöglichkeit hinzufügen',
		),
		array(
			'key' => 'field_61d899454f34c',
			'label' => 'Marke aktiv',
			'name' => 'brand-is-active',
			'aria-label' => '',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'message' => '',
			'default_value' => 0,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
		array(
			'key' => 'field_61a8d414fc358',
			'label' => 'Marken-Logo dunkel (4c)',
			'name' => 'brand-logo-dark-4c',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_61a8d426fc359',
			'label' => 'Marken-Logo hell (4c)',
			'name' => 'brand-logo-light-4c',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_61a8d436fc35a',
			'label' => 'Marken-Logo dunkel (1c)',
			'name' => 'brand-logo-dark-1c',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_61a8d447fc35b',
			'label' => 'Marken-Logo hell (1c)',
			'name' => 'brand-logo-light-1c',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'taxonomy',
				'operator' => '==',
				'value' => 'product_tag',
			),
		),
	),
	'menu_order' => 1,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 1,
	'display_title' => '',
) );
} );
