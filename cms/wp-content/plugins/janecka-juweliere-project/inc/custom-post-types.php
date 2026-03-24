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
 * Register Stores Post Type
 */
function agency_core_register_stores_cpt() {
    $labels = array(
        'name' => _x('Filialen', 'Post Type General Name', 'agency-core'),
        'singular_name' => __('Filiale', 'agency-core'),
        'menu_name' => __('Filialen', 'agency-core'),
        'add_new' => __('Neue Filiale hinzufügen', 'agency-core'),
        'add_new_item' => __('Neue Filiale hinzufügen', 'agency-core'),
        'edit_item' => __('Filiale bearbeiten', 'agency-core'),
        'new_item' => __('Neue Filiale', 'agency-core'),
        'view_item' => __('Filiale anzeigen', 'agency-core'),
        'search_items' => __('Filialen durchsuchen', 'agency-core'),
        'not_found' => __('Keine Filialen gefunden', 'agency-core'),
        'not_found_in_trash' => __('Keine Filialen im Papierkorb gefunden', 'agency-core'),
        'all_items' => __('Alle Filialen', 'agency-core'),
    );

    $args = array(
        'label' => __('Filialen', 'agency-core'),
        'labels' => $labels,
        'show_in_rest' => true,
		'public' => true,
		'show_ui' => true,
		'taxonomies' => array( 'filialen-zahlungsweisen' ),
		'supports' => array('title', 'thumbnail', 'editor', 'author', 'custom-fields', ),
		'has_archive' => true,
		'exclude_from_search' => false,
		'rewrite' => array('slug' => 'unsere-filialen', 'with_front' => true, 'pages' => true, 'feeds' => true,),
		'menu_position' => 25,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'publicly_queryable'  => true,
		'menu_icon' => 'dashicons-store'
    );

    register_post_type( 'stores',  $args);
}
add_action('init', 'agency_core_register_stores_cpt');
