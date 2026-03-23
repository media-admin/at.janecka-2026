<?php

namespace Vendidero\Germanized\Pro\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\Pro\Blocks\Assets;
use Vendidero\Germanized\Pro\Package;

defined( 'ABSPATH' ) || exit;

class MultilevelCheckout implements IntegrationInterface {

	/**
	 * @var Assets
	 */
	private $assets;

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woocommerce-germanized-pro-multilevel-checkout';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzdp-blocks-multilevel-checkout-frontend', $this->assets->get_block_asset_build_path( 'multilevel-checkout-frontend' ) );
		$this->assets->register_style( 'wc-gzdp-blocks-multilevel-checkout-frontend', $this->assets->get_block_asset_build_path( 'multilevel-checkout', 'css' ) );

		foreach ( $this->get_chunks() as $chunk ) {
			$handle = 'wc-gzdp-blocks-' . $chunk . '-chunk';

			$this->assets->register_script( $handle, $this->assets->get_block_asset_build_path( 'multilevel-checkout-blocks' . $chunk ), array(), true );

			wp_add_inline_script(
				'wc-gzdp-blocks-multilevel-checkout-frontend',
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);

			wp_deregister_script( $handle );
		}

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-gzdp-blocks-multilevel-checkout-frontend' );
			}
		);

		$bg_color = ( get_option( 'woocommerce_gzd_display_checkout_table_color' ) ? get_option( 'woocommerce_gzd_display_checkout_table_color' ) : '' );

		if ( ! empty( $bg_color ) ) {
			$custom_css = '.wc-gzdp-multilevel-checkout .wp-block-woocommerce-checkout-order-summary-cart-items-block { padding: 0 !important; } .wc-gzdp-multilevel-checkout .wc-block-components-order-summary, .wc-gzdp-multilevel-checkout .wc-block-components-order-summary.is-large { background-color: ' . esc_attr( $bg_color ) . '; padding: 16px; }';
			wp_add_inline_style( 'wc-gzdp-blocks-multilevel-checkout-frontend', $custom_css );
		}

		$this->assets->register_data( 'addCartLink', 'yes' === get_option( 'woocommerce_gzdp_multilevel_checkout_place_cart_link' ) );
	}

	protected function get_chunks() {
		$build_path = Package::get_path( 'build/multilevel-checkout-blocks' );
		$blocks     = array();

		if ( ! is_dir( $build_path ) ) {
			return array();
		}
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $build_path, \FilesystemIterator::UNIX_PATHS ) ) as $block_name ) {
			/**
			 * Skip additional auto-generated style js files.
			 */
			if ( '-style.js' === substr( $block_name, -9 ) ) {
				continue;
			}

			$blocks[] = str_replace( $build_path, '', $block_name );
		}

		$chunks = preg_filter( '/.js/', '', $blocks );
		return $chunks;
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-gzdp-blocks-multilevel-checkout-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}
}
