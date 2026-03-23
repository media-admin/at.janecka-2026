<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZDP_Theme_OceanWP extends WC_GZDP_Theme {

	public function __construct( $template ) {
		parent::__construct( $template );

		add_filter( 'woocommerce_gzd_shopmark_single_product_filters', array( $this, 'single_product_filters' ), 10, 2 );
		add_filter( 'woocommerce_gzd_shopmark_product_loop_filters', array( $this, 'product_loop_filters' ), 10, 2 );

		add_filter( 'woocommerce_gzd_shopmark_product_loop_defaults', array( $this, 'product_loop_defaults' ), 10 );
		add_filter( 'woocommerce_gzd_shopmark_single_product_defaults', array( $this, 'single_product_defaults' ), 10 );

		add_action( 'init', array( $this, 'remove_gzd_compatibility' ), 50 );
	}

	public function load_scripts() {
		parent::load_scripts();

		if ( get_theme_mod( 'ocean_woo_quick_view', true ) ) {
			wp_enqueue_script( 'wc-gzd-add-to-cart-variation' );
		}
	}

	public function has_custom_shopmarks() {
		return true;
	}

	public function remove_gzd_compatibility() {
		wc_gzdp_remove_class_action( 'ocean_after_archive_product_inner', 'OceanWP_WooCommerce_Config', 'woocommerce_germanized' );
		wc_gzdp_remove_class_action( 'ocean_after_single_product_price', 'OceanWP_WooCommerce_Config', 'woocommerce_germanized_single', 12 );
		wc_gzdp_remove_class_action( 'ocean_after_single_product_excerpt', 'OceanWP_WooCommerce_Config', 'woocommerce_germanized_single_meta', 12 );

		/**
		 * Remove + execute shopmarks again to prevent removals by OceanWP
		 */
		foreach ( wc_gzd_get_product_loop_shopmarks() as $shopmark ) {
			$shopmark->remove();
			$shopmark->execute();
		}

		foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$shopmark->remove();
			$shopmark->execute();
		}

		foreach ( wc_gzd_get_single_product_grouped_shopmarks() as $shopmark ) {
			$shopmark->remove();
			$shopmark->execute();
		}
	}

	public function product_loop_defaults( $defaults ) {
		$count = 10;

		foreach ( $defaults as $type => $type_data ) {
			$defaults[ $type ]['default_filter']   = 'ocean_after_archive_product_inner';
			$defaults[ $type ]['default_priority'] = $count++;
		}

		return $defaults;
	}

	public function single_product_defaults( $defaults ) {
		$count = 10;

		foreach ( $defaults as $type => $type_data ) {
			$defaults[ $type ]['default_filter']   = 'ocean_after_single_product_price';
			$defaults[ $type ]['default_priority'] = $count++;
		}

		return $defaults;
	}

	public function product_loop_filters( $filters, $load_translation = true ) {
		$filters['ocean_after_archive_product_inner'] = array(
			'title'            => $load_translation ? __( 'OceanWP Inner Product', 'woocommerce-germanized-pro' ) : 'OceanWP Inner Product',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['ocean_after_archive_product_title'] = array(
			'title'            => $load_translation ? __( 'OceanWP Product Title', 'woocommerce-germanized-pro' ) : 'OceanWP Product Title',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		return $filters;
	}

	public function single_product_filters( $filters, $load_translation = true ) {
		$filters['ocean_after_single_product_title'] = array(
			'title'            => $load_translation ? __( 'OceanWP Title', 'woocommerce-germanized-pro' ) : 'OceanWP Title',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['ocean_after_single_product_price'] = array(
			'title'            => $load_translation ? __( 'OceanWP Price', 'woocommerce-germanized-pro' ) : 'OceanWP Price',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		$filters['ocean_after_single_product_meta'] = array(
			'title'            => $load_translation ? __( 'OceanWP Meta', 'woocommerce-germanized-pro' ) : 'OceanWP Meta',
			'number_of_params' => 1,
			'is_action'        => true,
		);

		return $filters;
	}
}
