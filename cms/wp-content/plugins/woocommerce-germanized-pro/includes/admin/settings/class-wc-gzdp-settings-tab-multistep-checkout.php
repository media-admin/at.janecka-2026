<?php

use Vendidero\Germanized\Pro\Blocks\MultilevelCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Multistep Checkout settings.
 *
 * @class       WC_GZDP_Settings_Tab_Multistep_Checkout
 * @version     3.0.0
 * @author      Vendidero
 */
class WC_GZDP_Settings_Tab_Multistep_Checkout extends WC_GZD_Settings_Tab_Multistep_Checkout {

	public function get_tab_settings( $current_section = '' ) {
		$supports_block_checkout = function_exists( 'has_block' ) && \Vendidero\Germanized\Pro\Package::load_blocks();
		$helper                  = WC_GZDP_Multistep_Checkout::instance();
		$shortcode_settings      = $helper->get_settings();
		$checkout_page_id        = wc_get_page_id( 'checkout' );
		$has_block_checkout      = $supports_block_checkout && $checkout_page_id && ( has_block( 'woocommerce/checkout', $checkout_page_id ) );

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'checkout_general_options',
			),

			array(
				'title'   => _x( 'Enable', 'multistep', 'woocommerce-germanized-pro' ),
				'desc'    => _x( 'Enable Multistep Checkout.', 'multistep', 'woocommerce-germanized-pro' ),
				'id'      => 'woocommerce_gzdp_checkout_enable',
				'type'    => 'gzd_toggle',
				'default' => 'no',
			),

			$supports_block_checkout ? array(
				'title'    => _x( 'Checkout Type', 'multistep', 'woocommerce-germanized-pro' ),
				'desc'     => _x( 'Choose which checkout type you are using, e.g. Shortcode-based or Block-based.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_gzdp_checkout_type',
				'type'     => 'select',
				'options'  => array(
					'block'     => _x( 'Block-based', 'multistep', 'woocommerce-germanized-pro' ),
					'shortcode' => _x( 'Shortcode', 'multistep', 'woocommerce-germanized-pro' ),
				),
				'default'  => $has_block_checkout ? 'block' : 'shortcode',
			) : array(),

			array(
				'title'             => __( 'Back to Cart', 'woocommerce-germanized-pro' ),
				'desc'              => __( 'Add a cart link to step navigation.', 'woocommerce-germanized-pro' ),
				'id'                => 'woocommerce_gzdp_multilevel_checkout_place_cart_link',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_type' => 'block',
				),
				'default'           => 'yes',
				'type'              => 'gzd_toggle',
			),
		);

		if ( $supports_block_checkout ) {
			foreach ( $shortcode_settings as $k => $setting ) {
				if ( isset( $setting['type'] ) ) {
					$setting = wp_parse_args(
						$setting,
						array(
							'custom_attributes' => array(),
						)
					);

					$setting['custom_attributes']['data-show_if_woocommerce_gzdp_checkout_type'] = 'shortcode';

					$shortcode_settings[ $k ] = $setting;
				}
			}
		}

		$settings = array_merge(
			$settings,
			$shortcode_settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'checkout_general_options',
				),
			)
		);

		return $settings;
	}

	public function supports_disabling() {
		return true;
	}

	protected function get_enable_option_name() {
		return 'woocommerce_gzdp_checkout_enable';
	}

	public function is_enabled() {
		return 'yes' === get_option( $this->get_enable_option_name() );
	}
}
