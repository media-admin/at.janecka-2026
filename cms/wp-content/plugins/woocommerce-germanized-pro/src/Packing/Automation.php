<?php

namespace Vendidero\Germanized\Pro\Packing;

defined( 'ABSPATH' ) || exit;

class Automation {

	public static function init() {
	}

	public static function register_percentage_buffer() {
		wc_deprecated_function( __METHOD__, '3.9.0' );
	}

	public static function register_fixed_buffer() {
		wc_deprecated_function( __METHOD__, '3.9.0' );
	}

	public static function use_percentage_buffer() {
		wc_deprecated_function( __METHOD__, '3.9.0' );

		return true;
	}

	public static function is_enabled() {
		wc_deprecated_function( __METHOD__, '3.9.0' );

		return false;
	}

	public static function maybe_disable_auto_create( $disable, $order_id ) {
		wc_deprecated_function( __METHOD__, '3.9.0' );
	}

	public static function log( $message ) {
		wc_deprecated_function( __METHOD__, '3.9.0' );
	}

	public static function pack_order( $order_id, $default_shipment_status = 'processing' ) {
		wc_deprecated_function( __METHOD__, '3.9.0' );

		if ( is_callable( array( '\Vendidero\Shiptastic\Automation', 'create_shipments' ) ) ) {
			return \Vendidero\Shiptastic\Automation::create_shipments( $order_id );
		}

		return array();
	}
}
