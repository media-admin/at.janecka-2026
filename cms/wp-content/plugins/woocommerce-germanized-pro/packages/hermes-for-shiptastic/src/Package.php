<?php

namespace Vendidero\Shiptastic\Hermes;

use Vendidero\Shiptastic\ShippingProvider\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.9';

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		if ( self::has_dependencies() ) {
			// Add shipping provider
			add_filter( 'woocommerce_shiptastic_shipping_provider_class_names', array( __CLASS__, 'add_shipping_provider_class_name' ), 20, 1 );

			if ( ! did_action( 'woocommerce_shiptastic_init' ) ) {
				add_action( 'woocommerce_shiptastic_init', array( __CLASS__, 'on_init' ), 20 );
			} else {
				self::on_init();
			}
		}
	}

	public static function on_init() {
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'check_version' ), 10 );

		self::includes();

		add_filter(
			'shiptastic_register_api_instance_hermes',
			function () {
				return new \Vendidero\Shiptastic\Hermes\Api\Api();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_hermes_parcel_shop_finder',
			function () {
				return new \Vendidero\Shiptastic\Hermes\Api\ParcelShopFinder();
			}
		);

		if ( self::is_enabled() ) {
			self::init_hooks();
		}
	}

	public static function check_version() {
		if ( self::is_standalone() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_shiptastic_hermes_version' ) !== self::get_version() ) ) {
			Install::install();
		}
	}

	public static function load_plugin_textdomain() {
		if ( ! self::is_standalone() ) {
			return;
		}

		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-germanized-pro' );

		load_textdomain( 'hermes-for-shiptastic', trailingslashit( WP_LANG_DIR ) . 'hermes-for-shiptastic/hermes-for-shiptastic-' . $locale . '.mo' );
		load_plugin_textdomain( 'hermes-for-shiptastic', false, plugin_basename( self::get_path() ) . '/i18n/languages/' );
	}

	public static function is_standalone() {
		return defined( 'WC_HERMES_FOR_STC_IS_STANDALONE_PLUGIN' ) && WC_HERMES_FOR_STC_IS_STANDALONE_PLUGIN;
	}

	public static function has_dependencies() {
		return apply_filters( 'woocommerce_shiptastic_hermes_enabled', true ) && version_compare( PHP_VERSION, '7.3.0', '>=' ) && class_exists( '\Vendidero\Shiptastic\Package' ) && self::base_country_is_supported();
	}

	public static function base_country_is_supported() {
		return in_array( \Vendidero\Shiptastic\Package::get_base_country(), self::get_supported_countries(), true );
	}

	public static function get_supported_countries() {
		return array( 'DE' );
	}

	public static function is_enabled() {
		return ( self::is_hermes_enabled() );
	}

	public static function is_hermes_enabled() {
		$is_enabled = false;

		if ( method_exists( '\Vendidero\Shiptastic\ShippingProvider\Helper', 'is_shipping_provider_activated' ) ) {
			$is_enabled = Helper::instance()->is_shipping_provider_activated( 'hermes' );
		} elseif ( $provider = self::get_hermes_shipping_provider() ) {
			$is_enabled = $provider->is_activated();
		}

		return $is_enabled;
	}

	public static function is_sandbox_mode() {
		$is_debug_mode = ( defined( 'WC_STC_HERMES_DEBUG' ) && WC_STC_HERMES_DEBUG );

		return $is_debug_mode;
	}

	public static function is_self_service_customer() {
		return true;
	}

	/**
	 * @return false|\Vendidero\Shiptastic\Interfaces\Api|\Vendidero\Shiptastic\Hermes\Api\Api
	 */
	public static function get_api() {
		return \Vendidero\Shiptastic\API\Helper::get_api( 'hermes', self::is_sandbox_mode() );
	}

	/**
	 * @return false|\Vendidero\Shiptastic\Interfaces\Api|\Vendidero\Shiptastic\Hermes\Api\ParcelShopFinder
	 */
	public static function get_parcel_shop_finder_api() {
		return \Vendidero\Shiptastic\API\Helper::get_api( 'hermes_parcel_shop_finder', self::is_sandbox_mode() );
	}

	private static function includes() {
	}

	public static function init_hooks() {
		// Filter templates
		add_filter( 'shiptastic_default_template_path', array( __CLASS__, 'filter_templates' ), 10, 2 );
	}

	public static function filter_templates( $path, $template_name ) {
		if ( file_exists( self::get_path() . '/templates/' . $template_name ) ) {
			$path = self::get_path() . '/templates/' . $template_name;
		}

		return $path;
	}

	/**
	 * @return false
	 */
	public static function get_hermes_shipping_provider() {
		$provider = wc_stc_get_shipping_provider( 'hermes' );

		if ( ! is_a( $provider, '\Vendidero\Shiptastic\Hermes\ShippingProvider\Hermes' ) ) {
			return false;
		}

		return $provider;
	}

	public static function add_shipping_provider_class_name( $class_names ) {
		$class_names['hermes'] = '\Vendidero\Shiptastic\Hermes\ShippingProvider\Hermes';

		return $class_names;
	}

	public static function install() {
		if ( self::has_dependencies() ) {
			self::init();
			Install::install();
		}
	}

	public static function install_integration() {
		self::install();
	}

	public static function is_integration() {
		return \Vendidero\Shiptastic\Package::is_integration();
	}

	/**
	 * Return the version of the package.
	 *
	 * @return string
	 */
	public static function get_version() {
		return self::VERSION;
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	public static function get_template_path() {
		return \Vendidero\Shiptastic\Package::get_template_path();
	}

	/**
	 * Return the path to the package.
	 *
	 * @return string
	 */
	public static function get_url() {
		return plugins_url( '', __DIR__ );
	}

	public static function get_assets_url() {
		return self::get_url() . '/assets';
	}

	public static function log( $message, $type = 'info' ) {
		\Vendidero\Shiptastic\Package::log( $message, $type, 'hermes' );
	}
}
