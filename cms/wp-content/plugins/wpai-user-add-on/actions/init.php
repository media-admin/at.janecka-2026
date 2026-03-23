<?php

function pmui_init(){

	// Register a post type for Users

	$labels = array(
	    'name' => __('Users', 'wp_all_import_user_add_on'),
	    'singular_name' => __('User', 'wp_all_import_user_add_on'),
  	);

	$args = array(
	    'labels' => $labels,
	    'public' => false,
	    'publicly_queryable' => true,
	    'show_ui' => true, 
	    'show_in_menu' => false, 
	    'query_var' => true,	    
	    'rewrite' => array( 'slug' => 'import_users' ),
	    'capability_type' => 'post',
	    'has_archive' => false, 
	    'hierarchical' => false,
	    'menu_position' => null,
	    'supports' => array( 'title', 'editor', 'custom-fields' ),
	    'taxonomies' => array()
	); 
	
	register_post_type('import_users', $args);

	// Register a post type for WooCommerce Customers if supported by WPAI
	
	if ( class_exists('WooCommerce') && defined('PMXI_VERSION') && defined('PMXI_EDITION') && ( ( PMXI_EDITION == 'paid' && version_compare(PMXI_VERSION,'4.5.6') >= 0 ) || ( PMXI_EDITION == 'free' && version_compare(PMXI_VERSION,'3.5.0') >= 0 ) ) ) {
		$labels = array(
			'name' => __('WooCommerce Customers', 'wp_all_import_user_add_on'),
			'singular_name' => __('WooCommerce Customer', 'wp_all_import_user_add_on'),
		  );
	
		$args = array(
			'labels' => $labels,
			'public' => false,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => false, 
			'query_var' => true,	    
			'rewrite' => array( 'slug' => 'shop_customer' ),
			'capability_type' => 'post',
			'has_archive' => false, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'custom-fields' ),
			'taxonomies' => array()
		); 
		
		register_post_type('shop_customer', $args);
	}

	//flush_rewrite_rules();
		
}

?>