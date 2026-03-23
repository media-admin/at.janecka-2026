<?php

namespace Vendidero\Germanized\Pro\Shiptastic;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function init() {
		add_filter(
			'woocommerce_shiptastic_is_provider_integration_active',
			function ( $is_active, $provider_name ) {
				$builtin_provider = array(
					'dpd',
					'gls',
					'hermes',
				);

				if ( in_array( $provider_name, $builtin_provider, true ) ) {
					$is_active = true;
				}

				return $is_active;
			},
			10,
			2
		);

		add_filter( 'woocommerce_shiptastic_is_pro', '__return_true' );
	}

	public static function is_active() {
		return is_callable( array( 'Vendidero\Germanized\PluginsHelper', 'is_shiptastic_plugin_active' ) ) ? \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() : false;
	}
}
