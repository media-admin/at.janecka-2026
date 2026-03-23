<?php
/**
 * Custom Taxonomies Registration
 * 
 * @package MediaLab_Project
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Custom Taxonomies
 */
function medialab_project_register_taxonomies() {
    
    // Project Category
    register_taxonomy('project_category', 'project', array(
        'labels' => array(
            'name' => 'Projekt Kategorien',
            'singular_name' => 'Projekt Kategorie',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
    
    // Service Category
    register_taxonomy('service_category', 'service', array(
        'labels' => array(
            'name' => 'Leistungs-Kategorien',
            'singular_name' => 'Leistungs-Kategorie',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
    
    // FAQ Category
    register_taxonomy('faq_category', 'faq', array(
        'labels' => array(
            'name' => 'FAQ Kategorien',
            'singular_name' => 'FAQ Kategorie',
        ),
        'hierarchical' => true,
        'public' => false,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
    
    // Carousel Category
    register_taxonomy('carousel_category', 'carousel', array(
        'labels' => array(
            'name' => 'Karussell Kategorien',
            'singular_name' => 'Karussell Kategorie',
        ),
        'hierarchical' => true,
        'public' => false,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
    
    // Job Category
    register_taxonomy('job_category', 'job', array(
        'labels' => array(
            'name' => 'Job Categories',
            'singular_name' => 'Job Category',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
    
    // Job Type
    register_taxonomy('job_type', 'job', array(
        'labels' => array(
            'name' => 'Job Types',
            'singular_name' => 'Job Type',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
    
    // Job Location
    register_taxonomy('job_location', 'job', array(
        'labels' => array(
            'name' => 'Job Locations',
            'singular_name' => 'Job Location',
        ),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
    ));
}
add_action('init', 'medialab_project_register_taxonomies');


/**
 * Register Services Categories
 */
function agency_core_register_service_categories() {
    $labels = array(
        'name' => __('Leistungs-Kategorien', 'agency-core'),
        'singular_name' => __('Leistungs-Kategorie', 'agency-core'),
        'search_items' => __('Kategorien durchsuchen', 'agency-core'),
        'all_items' => __('Alle Kategorien', 'agency-core'),
        'parent_item' => __('Übergeordnete Kategorie', 'agency-core'),
        'parent_item_colon' => __('Übergeordnete Kategorie:', 'agency-core'),
        'edit_item' => __('Kategorie bearbeiten', 'agency-core'),
        'update_item' => __('Kategorie aktualisieren', 'agency-core'),
        'add_new_item' => __('Neue Kategorie hinzufügen', 'agency-core'),
        'new_item_name' => __('Neuer Kategorie-Name', 'agency-core'),
        'menu_name' => __('Service Kategorien', 'agency-core'),
    );
    
    register_taxonomy('service_category', array('service'), array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'leistungs-kategorie'),
    ));
}
add_action('init', 'agency_core_register_service_categories');


/**
 * Register FAQ Category Taxonomy
 */
function agency_core_register_faq_category_taxonomy() {
    $labels = array(
        'name' => _x('FAQ Kategorien', 'taxonomy general name', 'agency-core'),
        'singular_name' => _x('FAQ Kategorie', 'taxonomy singular name', 'agency-core'),
        'search_items' => __('FAQ Kategorien durchsuchen', 'agency-core'),
        'all_items' => __('Alle FAQ Kategorien', 'agency-core'),
        'parent_item' => __('Übergeordnete FAQ Kategorie', 'agency-core'),
        'parent_item_colon' => __('Übergeordnete FAQ Kategorie:', 'agency-core'),
        'edit_item' => __('FAQ Kategorie bearbeiten', 'agency-core'),
        'update_item' => __('FAQ Kategorie updaten', 'agency-core'),
        'add_new_item' => __('FAQ Kategorie hinzufügen', 'agency-core'),
        'new_item_name' => __('Neuer FAQ Kategorie Name', 'agency-core'),
        'menu_name' => __('FAQ Kategorien', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud' => false,
        'show_in_rest' => true,
    );
    
    register_taxonomy('faq_category', array('faq'), $args);
}
add_action('init', 'agency_core_register_faq_category_taxonomy');


/**
 * Register Carousel Category Taxonomy
 */
function agency_core_register_carousel_category_taxonomy() {
    $labels = array(
        'name' => _x('Karussell Kategorien', 'taxonomy general name', 'agency-core'),
        'singular_name' => _x('Karussell Kategorie', 'taxonomy singular name', 'agency-core'),
        'search_items' => __('Kategorien durchsuchen', 'agency-core'),
        'all_items' => __('Alle Kategorien', 'agency-core'),
        'edit_item' => __('Kategorie bearbeiten', 'agency-core'),
        'update_item' => __('Kategorie updaten', 'agency-core'),
        'add_new_item' => __('Neue Kategorie hinzufügen', 'agency-core'),
        'new_item_name' => __('Neuer Kategorie-Name', 'agency-core'),
        'menu_name' => __('Kategorien', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'show_in_nav_menus' => false,
        'show_tagcloud' => false,
        'show_in_rest' => true,
    );
    
    register_taxonomy('carousel_category', array('carousel'), $args);
}
add_action('init', 'agency_core_register_carousel_category_taxonomy');



/**
 * Register Job Type Taxonomy
 */
function agency_core_register_job_type_taxonomy() {
    $labels = array(
        'name' => __('Job Types', 'agency-core'),
        'singular_name' => __('Job Type', 'agency-core'),
        'search_items' => __('Search Job Types', 'agency-core'),
        'all_items' => __('All Job Types', 'agency-core'),
        'edit_item' => __('Edit Job Type', 'agency-core'),
        'update_item' => __('Update Job Type', 'agency-core'),
        'add_new_item' => __('Add New Job Type', 'agency-core'),
        'new_item_name' => __('New Job Type Name', 'agency-core'),
        'menu_name' => __('Job Types', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'job-type'),
    );
    
    register_taxonomy('job_type', 'job', $args);
}
add_action('init', 'agency_core_register_job_type_taxonomy');


/**
 * Register Job Location Taxonomy
 */
function agency_core_register_job_location_taxonomy() {
    $labels = array(
        'name' => __('Job Locations', 'agency-core'),
        'singular_name' => __('Job Location', 'agency-core'),
        'search_items' => __('Search Job Locations', 'agency-core'),
        'all_items' => __('All Job Locations', 'agency-core'),
        'edit_item' => __('Edit Job Location', 'agency-core'),
        'update_item' => __('Update Job Location', 'agency-core'),
        'add_new_item' => __('Add New Job Location', 'agency-core'),
        'new_item_name' => __('New Job Location Name', 'agency-core'),
        'menu_name' => __('Locations', 'agency-core'),
    );
    
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'job-location'),
    );
    
    register_taxonomy('job_location', 'job', $args);
}
add_action('init', 'agency_core_register_job_location_taxonomy');