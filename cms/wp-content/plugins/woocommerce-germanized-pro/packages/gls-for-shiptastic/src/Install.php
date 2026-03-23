<?php

namespace Vendidero\Shiptastic\GLS;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'woocommerce_shiptastic_gls_version', null );

		if ( ! is_null( $current_version ) ) {
			self::update( $current_version );
		} elseif ( Package::is_standalone() && ( $gls = Package::get_gls_shipping_provider() ) ) {
			$gls->activate();
		}

		update_option( 'woocommerce_shiptastic_gls_version', Package::get_version() );
	}

	private static function update( $current_version ) {}
}
