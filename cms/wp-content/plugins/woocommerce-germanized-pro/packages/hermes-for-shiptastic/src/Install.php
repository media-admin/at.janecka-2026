<?php

namespace Vendidero\Shiptastic\Hermes;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'woocommerce_shiptastic_hermes_version', null );

		if ( ! is_null( $current_version ) ) {
			self::update( $current_version );
		} elseif ( Package::is_standalone() && ( $hermes = Package::get_hermes_shipping_provider() ) ) {
			$hermes->activate();
		}

		update_option( 'woocommerce_shiptastic_hermes_version', Package::get_version() );
	}

	private static function update( $current_version ) {}
}
