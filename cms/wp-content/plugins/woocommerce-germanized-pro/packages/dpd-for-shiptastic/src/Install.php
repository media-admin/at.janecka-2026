<?php

namespace Vendidero\Shiptastic\DPD;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'woocommerce_shiptastic_dpd_version', null );

		if ( ! is_null( $current_version ) ) {
			self::update( $current_version );
		} elseif ( Package::is_standalone() && ( $dpd = Package::get_dpd_shipping_provider() ) ) {
			$dpd->activate();
		}

		update_option( 'woocommerce_shiptastic_dpd_version', Package::get_version() );
	}

	private static function update( $current_version ) {}
}
