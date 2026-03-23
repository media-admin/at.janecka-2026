<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WC_GZDP_Theme {

	protected $theme = '';

	protected $title = '';

	public $name = '';

	public function __construct( $template ) {
		$this->theme = wp_get_theme( $template );
		$this->name  = $this->theme->get_template();
		$this->title = $this->theme->Name;

		// Load before WC GZD Hooks
		add_filter( 'woocommerce_germanized_filter_template', array( $this, 'template_filter' ), 0, 4 );
		add_filter( 'woocommerce_gzdp_checkout_template_not_found', array( $this, 'template_filter' ), 0, 3 );

		add_action( 'after_setup_theme', array( $this, 'custom_hooks' ), 0 );
		add_action( 'after_setup_theme', array( $this, 'load_theme_support' ), 10 );
		add_action( 'wp_print_styles', array( $this, 'load_styles' ), 11 );

		// Load after WooCommerce Frontend scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 150 );

		if ( $this->has_custom_shopmarks() ) {
			add_action( 'admin_notices', array( $this, 'shopmark_notice' ), 30 );
		}
	}

	public function shopmark_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( $screen && 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['tab'] ) && 'germanized-shopmarks' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$theme_name = $this->title;

			include_once WC_GERMANIZED_PRO_ABSPATH . 'themes/views/html-admin-notice-theme-custom-shopmarks.php';
		}
	}

	public function has_custom_shopmarks() {
		return false;
	}

	public function remove_shopmark_filters() {
		if ( $this->has_custom_shopmarks() ) {
			foreach ( array_keys( \Vendidero\Germanized\Shopmarks::get_locations() ) as $location ) {
				wc_gzdp_remove_class_filter( "woocommerce_gzd_shopmark_{$location}_filters", get_class( $this ), "{$location}_filters" );
				wc_gzdp_remove_class_filter( "woocommerce_gzd_shopmark_{$location}_defaults", get_class( $this ), "{$location}_defaults" );
			}

			/**
			 * Force re-registering shopmarks to re-load default filters.
			 */
			\Vendidero\Germanized\Shopmarks::register();
		}
	}

	public function load_theme_support() {
		add_theme_support( 'woocommerce-germanized' );
	}

	public function template_filter( $template, $template_name, $template_path ) {
		// Do not override child themes
		if ( strpos( $template, 'child' ) !== false ) {
			return $template;
		}

		$custom_template = WC_germanized_pro()->plugin_path() . '/themes/' . $this->name . '/templates/' . $template_name;

		if ( file_exists( $custom_template ) ) {
			$template = $custom_template;
		}

		return $template;
	}

	public function custom_hooks() {}

	public function load_styles() {
		$gzdp = WC_germanized_pro();
		$css  = WC_germanized_pro()->plugin_path( 'assets/css/wc-gzdp-theme-' . $this->name . '.scss' );

		if ( file_exists( $css ) ) {
			wp_register_style( 'wc-gzdp-theme-' . $this->name, $gzdp->get_assets_build_url( 'static/wc-gzdp-theme-' . $this->name . '.css' ), array(), WC_GERMANIZED_PRO_VERSION );
			wp_enqueue_style( 'wc-gzdp-theme-' . $this->name );
		}
	}

	public function load_scripts() {
		$gzdp   = WC_germanized_pro();
		$assets = WC_GZDP_Assets::instance();
		$js     = $gzdp->plugin_path( 'assets/js/static/wc-gzdp-theme-' . $this->name . '.js' );

		if ( file_exists( $js ) ) {
			$assets->register_script( 'wc-gzdp-theme-' . $this->name, 'static/wc-gzdp-theme-' . $this->name . '.js' );
			wp_enqueue_script( 'wc-gzdp-theme-' . $this->name );
		}
	}
}
