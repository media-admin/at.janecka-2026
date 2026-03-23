<?php

namespace Vendidero\Shiptastic\DPD;

use Vendidero\Shiptastic\DPD\Interfaces\LabelApi;
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
	const VERSION = '2.1.3';

	protected static $api = null;

	protected static $iso = null;

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		if ( self::has_dependencies() ) {
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
			'shiptastic_register_api_instance_dpd_cloud',
			function () {
				return new \Vendidero\Shiptastic\DPD\Api\Cloud\Api();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_dpd_webconnect',
			function () {
				return new \Vendidero\Shiptastic\DPD\Api\WebConnect\Api();
			}
		);

		add_filter(
			'shiptastic_register_api_instance_dpd_webservice',
			function () {
				return new \Vendidero\Shiptastic\DPD\Api\WEBService\Api();
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

		load_textdomain( 'dpd-for-shiptastic', trailingslashit( WP_LANG_DIR ) . 'dpd-for-shiptastic/dpd-for-shiptastic-' . $locale . '.mo' );
		load_plugin_textdomain( 'dpd-for-shiptastic', false, plugin_basename( self::get_path() ) . '/i18n/languages/' );
	}

	public static function is_standalone() {
		return defined( 'WC_DPD_FOR_STC_IS_STANDALONE_PLUGIN' ) && WC_DPD_FOR_STC_IS_STANDALONE_PLUGIN;
	}

	public static function has_dependencies() {
		return apply_filters( 'woocommerce_shiptastic_dpd_enabled', true ) && version_compare( PHP_VERSION, '7.3.0', '>=' ) && class_exists( '\Vendidero\Shiptastic\Package' ) && self::base_country_is_supported();
	}

	public static function base_country_is_supported() {
		return in_array( self::get_base_country(), self::get_supported_countries(), true );
	}

	public static function get_supported_countries() {
		return array( 'DE', 'AT' );
	}

	public static function is_enabled() {
		return ( self::is_dpd_enabled() );
	}

	public static function is_dpd_enabled() {
		$is_enabled = false;

		if ( method_exists( '\Vendidero\Shiptastic\ShippingProvider\Helper', 'is_shipping_provider_activated' ) ) {
			$is_enabled = Helper::instance()->is_shipping_provider_activated( 'dpd' );
		} elseif ( $provider = self::get_dpd_shipping_provider() ) {
			$is_enabled = $provider->is_activated();
		}

		return $is_enabled;
	}

	public static function get_api_language() {
		return 'de_DE';
	}

	public static function get_current_api_type() {
		$api_type = 'cloud';

		if ( $provider = self::get_dpd_shipping_provider() ) {
			$api_type = $provider->get_api_type();
		}

		return apply_filters( 'woocommerce_shiptastic_dpd_api_type', $api_type );
	}

	/**
	 * @return false|\Vendidero\Shiptastic\Interfaces\Api|LabelApi
	 */
	public static function get_api() {
		if ( 'web_connect' === self::get_current_api_type() ) {
			return \Vendidero\Shiptastic\API\Helper::get_api( 'dpd_webconnect', self::is_sandbox_mode() );
		} elseif ( 'webservice' === self::get_current_api_type() ) {
			return \Vendidero\Shiptastic\API\Helper::get_api( 'dpd_webservice', self::is_sandbox_mode() );
		} else {
			return \Vendidero\Shiptastic\API\Helper::get_api( 'dpd_cloud', self::is_sandbox_mode() );
		}
	}

	private static function includes() {
	}

	public static function init_hooks() {
		// Filter templates
		add_filter( 'shiptastic_default_template_path', array( __CLASS__, 'filter_templates' ), 10, 3 );
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
	public static function get_dpd_shipping_provider() {
		$provider = wc_stc_get_shipping_provider( 'dpd' );

		if ( ! is_a( $provider, '\Vendidero\Shiptastic\DPD\ShippingProvider\DPD' ) ) {
			return false;
		}

		return $provider;
	}

	public static function add_shipping_provider_class_name( $class_names ) {
		$class_names['dpd'] = '\Vendidero\Shiptastic\DPD\ShippingProvider\DPD';

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

	public static function is_sandbox_mode() {
		$is_debug_mode = ( defined( 'WC_STC_DPD_DEBUG' ) && WC_STC_DPD_DEBUG );

		return $is_debug_mode;
	}

	public static function log( $message, $type = 'info' ) {
		\Vendidero\Shiptastic\Package::log( $message, $type, 'dpd' );
	}

	public static function get_base_country() {
		$base_country = \Vendidero\Shiptastic\Package::get_base_country();

		/**
		 * Filter to adjust the DPD base country.
		 *
		 * @param string $country The country as ISO code.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Shiptastic/DPD
		 */
		return apply_filters( 'woocommerce_shiptastic_dpd_base_country', $base_country );
	}

	/**
	 * Returns a weight in 1/10 g
	 *
	 * @param $weight
	 * @param string $base_unit
	 *
	 * @return float
	 */
	public static function to_tenth_gramm( $weight, $base_unit = 'kg' ) {
		$weight = self::to_gramm( $weight, $base_unit );
		$weight = ( (float) $weight ) / 10;

		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $weight, 0 );
	}

	/**
	 * Returns a dimension in mm without decimal points
	 *
	 * @param $dimension
	 * @param string $base_unit
	 *
	 * @return float
	 */
	public static function to_mm( $dimension, $base_unit = 'cm' ) {
		if ( 'mm' !== $base_unit ) {
			$dimension = wc_get_dimension( $dimension, 'mm', $base_unit );
		}

		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $dimension, 0 );
	}

	/**
	 * Converts a weight to gramm
	 *
	 * @param $weight
	 * @param string $base_unit
	 *
	 * @return float
	 */
	public static function to_gramm( $weight, $base_unit = 'kg' ) {
		if ( 'g' !== $base_unit ) {
			$weight = wc_get_weight( $weight, 'g', $base_unit );
		}

		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $weight, 0 );
	}

	/**
	 * Returns a dimension in cm without decimal points
	 *
	 * @param $dimension
	 * @param string $base_unit
	 *
	 * @return float
	 */
	public static function convert_dimension( $dimension, $base_unit = 'cm' ) {
		if ( 'cm' !== $base_unit ) {
			$dimension = wc_get_dimension( $dimension, 'cm', $base_unit );
		}

		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $dimension, 0 );
	}

	public static function get_pudo_id_from_cloud_parcel_shop( $location ) {
		$extra_info = ! empty( $location['ExtraInfo'] ) ? wc_clean( $location['ExtraInfo'] ) : '';
		$pudo_parts = explode( ':', $extra_info );
		$pudo_id    = '';

		if ( count( $pudo_parts ) > 1 ) {
			$pudo_id = preg_replace( '/[\W]/', '', $pudo_parts[1] );
		}

		return $pudo_id;
	}
}
