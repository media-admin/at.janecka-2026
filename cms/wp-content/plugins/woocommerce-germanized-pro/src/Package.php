<?php

namespace Vendidero\Germanized\Pro;

use Vendidero\Germanized\Pro\Registry\Container;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Package {

	/**
	 * @var \WooCommerce_Germanized_Pro
	 */
	private static $gzd_instance = null;

	/**
	 * @param \WooCommerce_Germanized_Pro $gzd_instance
	 *
	 * @return void
	 */
	public static function init( $gzd_instance ) {
		self::$gzd_instance = $gzd_instance;

		self::container()->get( Bootstrap::class );
	}

	public static function get_version() {
		return self::$gzd_instance->version;
	}

	/**
	 * Loads the dependency injection container for woocommerce blocks.
	 *
	 * @param boolean $reset Used to reset the container to a fresh instance.
	 *                       Note: this means all dependencies will be
	 *                       reconstructed.
	 */
	public static function container( $reset = false ) {
		static $container;
		if (
			! $container instanceof Container
			|| $reset
		) {
			$container = new Container();

			// register Bootstrap.
			$container->register(
				Bootstrap::class,
				function ( $container ) {
					return new Bootstrap(
						$container
					);
				}
			);
		}
		return $container;
	}

	public static function get_path( $rel_path = '' ) {
		return self::$gzd_instance->plugin_path( $rel_path );
	}

	public static function get_url( $rel_url = '' ) {
		return self::$gzd_instance->plugin_url( $rel_url );
	}

	public static function get_language_path() {
		return self::$gzd_instance->language_path();
	}

	public static function is_vat_id_module_enabled() {
		return 'yes' === get_option( 'woocommerce_gzdp_enable_vat_check' );
	}

	public static function load_blocks() {
		return version_compare( \WC_GZDP_Dependencies::instance()->get_plugin_version( 'woocommerce' ), '8.2.0', '>=' );
	}

	public static function load_html_dom( $html ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return false;
		}

		$lib_xml_state            = \libxml_use_internal_errors( true );
		$dom                      = new \DOMDocument( '1.0', 'utf-8' );
		$dom->preserveWhiteSpace  = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom->formatOutput        = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom->strictErrorChecking = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		$html              = str_replace( '&nbsp;', '@nbsp;', $html );
		$has_document_type = \stripos( $html, '<!DOCTYPE' ) !== false;

		if ( $has_document_type ) {
			$html = \preg_replace( '/<!DOCTYPE\\s++html(?=[\\s>])/i', '<!DOCTYPE html', $html, 1 );
		} else {
			$html = '<!DOCTYPE html>' . $html;
		}

		// Load without HTML wrappers
		@$dom->loadHTML( '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $html ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase, WordPress.PHP.NoSilencedErrors.Discouraged

		// Explicitly force utf-8 encoding
		$dom->encoding = 'UTF-8';

		\libxml_clear_errors();
		\libxml_use_internal_errors( $lib_xml_state );

		$html_element = $dom->getElementsByTagName( 'html' )->item( 0 );

		if ( ! $html_element instanceof \DOMElement ) {
			return false;
		}

		return $dom;
	}

	/**
	 * @param \DOMDocument|\DOMNode $dom
	 *
	 * @return string|\WP_Error
	 */
	public static function get_dom_html_content( $dom, $options = LIBXML_NOEMPTYTAG ) {
		if ( is_a( $dom, 'DOMDocument' ) ) {
			$html = $dom->saveXML( $dom->documentElement, $options ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( false === $html ) {
				return new \WP_Error( 500, 'Error while saving HTML via DOMDocument' );
			}

			$html = \preg_replace( '%</?+body(?:\\s[^>]*+)?+>%', '', $html );
		} elseif ( is_a( $dom, 'DOMNode' ) ) {
			$html = $dom->ownerDocument->saveXML( $dom, $options ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( false === $html ) {
				return new \WP_Error( 500, 'Error while saving HTML via DOMDocument' );
			}
		} else {
			$html = '';
		}

		$unrecognized_tagname_matcher = '(?:command|embed|keygen|source|track|wbr)';

		$html = \preg_replace( '%</' . $unrecognized_tagname_matcher . '>%', '', $html );
		$html = str_replace( '@nbsp;', '&nbsp;', $html );

		return $html;
	}
}
