<?php

namespace Vendidero\Shiptastic\GLS;

use Vendidero\Shiptastic\GLS\Api\Api;
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
	const VERSION = '1.2.3';

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
			'shiptastic_register_api_instance_gls',
			function () {
				return new Api();
			}
		);

		if ( self::is_enabled() ) {
			self::init_hooks();
		}
	}

	public static function check_version() {
		if ( self::is_standalone() && self::has_dependencies() && ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'woocommerce_shiptastic_gls_version' ) !== self::get_version() ) ) {
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

		load_textdomain( 'gls-for-shiptastic', trailingslashit( WP_LANG_DIR ) . 'gls-for-shiptastic/gls-for-shiptastic-' . $locale . '.mo' );
		load_plugin_textdomain( 'gls-for-shiptastic', false, plugin_basename( self::get_path() ) . '/i18n/languages/' );
	}

	public static function is_standalone() {
		return defined( 'WC_GLS_FOR_STC_IS_STANDALONE_PLUGIN' ) && WC_GLS_FOR_STC_IS_STANDALONE_PLUGIN;
	}

	public static function has_dependencies() {
		return apply_filters( 'woocommerce_shiptastic_gls_enabled', true ) && class_exists( '\Vendidero\Shiptastic\Package' ) && self::base_country_is_supported();
	}

	public static function base_country_is_supported() {
		return in_array( self::get_base_country(), self::get_supported_countries(), true );
	}

	public static function get_supported_countries() {
		return apply_filters( 'woocommerce_shiptastic_gls_supported_countries', array( 'DE', 'AT', 'CH', 'BE', 'LU', 'FR', 'IE', 'ES' ) );
	}

	public static function is_enabled() {
		return ( self::is_gls_enabled() );
	}

	public static function is_gls_enabled() {
		$is_enabled = false;

		if ( method_exists( '\Vendidero\Shiptastic\ShippingProvider\Helper', 'is_shipping_provider_activated' ) ) {
			$is_enabled = Helper::instance()->is_shipping_provider_activated( 'gls' );
		} elseif ( $provider = self::get_gls_shipping_provider() ) {
			$is_enabled = $provider->is_activated();
		}

		return $is_enabled;
	}

	public static function get_api_contact_id( $is_sandbox = false ) {
		if ( $is_sandbox ) {
			$contact_id = defined( 'WC_STC_GLS_API_CONTACT_ID' ) ? WC_STC_GLS_API_CONTACT_ID : '';
		} else {
			$contact_id = self::get_gls_shipping_provider()->get_setting( 'api_contact_id' );
		}

		return $contact_id;
	}

	public static function get_api_url( $is_sandbox = false ) {
		$api_url = '';

		if ( $is_sandbox ) {
			$api_url = defined( 'WC_STC_GLS_API_URL' ) ? WC_STC_GLS_API_URL : '';
		} else {
			$api_url_id = self::get_gls_shipping_provider()->get_setting( 'api_url' );
			$api_urls   = self::get_available_api_urls();

			if ( array_key_exists( $api_url_id, $api_urls ) ) {
				$api_url = $api_urls[ $api_url_id ];
			}
		}

		return apply_filters( 'woocommerce_shiptastic_gls_api_url', $api_url );
	}

	public static function get_available_api_urls() {
		if ( 'AT' === self::get_base_country() ) {
			$urls = array(
				'at01' => 'https://shipit-wbm-at01.gls-group.eu',
			);
		} else {
			$urls = array(
				'de01'  => 'https://shipit-wbm-de01.gls-group.eu',
				'de02'  => 'https://shipit-wbm-de02.gls-group.eu',
				'de03'  => 'https://shipit-wbm-de03.gls-group.eu',
				'de04'  => 'https://shipit-wbm-de04.gls-group.eu',
				'de05'  => 'https://shipit-wbm-de05.gls-group.eu',
				'de06'  => 'https://shipit-wbm-de06.gls-group.eu',
				'de07'  => 'https://shipit-wbm-de07.gls-group.eu',
				'int01' => 'https://shipit-wbm-int01.gls-group.eu',
				'de08'  => 'https://wbm-de08.shipit.gls-group.com',
			);
		}

		return apply_filters( 'woocommerce_shiptastic_gls_available_api_urls', $urls );
	}

	public static function get_return_types() {
		return array(
			'shop_return'     => _x( 'Shop Return', 'gls', 'woocommerce-germanized-pro' ),
			'pick_and_return' => _x( 'Pick & Return', 'gls', 'woocommerce-germanized-pro' ),
		);
	}

	/**
	 * @return false|\Vendidero\Shiptastic\Interfaces\Api|Api
	 */
	public static function get_api() {
		return \Vendidero\Shiptastic\API\Helper::get_api( 'gls', self::is_sandbox_mode() );
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
	public static function get_gls_shipping_provider() {
		$provider = wc_stc_get_shipping_provider( 'gls' );

		if ( ! is_a( $provider, '\Vendidero\Shiptastic\GLS\ShippingProvider\GLS' ) ) {
			return false;
		}

		return $provider;
	}

	public static function add_shipping_provider_class_name( $class_names ) {
		$class_names['gls'] = '\Vendidero\Shiptastic\GLS\ShippingProvider\GLS';

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
		return 'woocommerce-germanized/';
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

	public static function is_sandbox_mode() {
		$is_sandbox = ( defined( 'WC_STC_GLS_DEBUG' ) && WC_STC_GLS_DEBUG );

		return $is_sandbox;
	}

	public static function log( $message, $type = 'info' ) {
		\Vendidero\Shiptastic\Package::log( $message, $type, 'gls' );

		return true;
	}

	public static function get_available_incoterms() {
		return array(
			'10' => _x( 'DDP', 'gls', 'woocommerce-germanized-pro' ),
			'20' => _x( 'DAP', 'gls', 'woocommerce-germanized-pro' ),
			'30' => _x( 'DDP, VAT unpaid', 'gls', 'woocommerce-germanized-pro' ),
			'40' => _x( 'DAP, cleared', 'gls', 'woocommerce-germanized-pro' ),
			'50' => _x( 'DDP, small packages', 'gls', 'woocommerce-germanized-pro' ),
		);
	}

	public static function get_base_country() {
		/**
		 * Filter to adjust the DPD base country.
		 *
		 * @param string $country The country as ISO code.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Shiptastic/DPD
		 */
		return apply_filters( 'woocommerce_shiptastic_gls_base_country', \Vendidero\Shiptastic\Package::get_base_country() );
	}
}
