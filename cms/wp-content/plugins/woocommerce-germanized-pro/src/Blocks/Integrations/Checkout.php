<?php

namespace Vendidero\Germanized\Pro\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\Pro\Blocks\Assets;
use Vendidero\Germanized\Pro\Package;

defined( 'ABSPATH' ) || exit;

class Checkout implements IntegrationInterface {

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
		return 'woocommerce-germanized-pro-checkout';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzdp-blocks-checkout', $this->assets->get_block_asset_build_path( 'checkout' ), array( 'wc-gzdp-blocks' ) );
		$this->assets->register_script( 'wc-gzdp-blocks-checkout-frontend', $this->assets->get_block_asset_build_path( 'checkout-frontend' ) );
		$this->assets->register_style( 'wc-gzdp-blocks-checkout-frontend', $this->assets->get_block_asset_build_path( 'checkout', 'css' ) );

		foreach ( $this->get_chunks() as $chunk ) {
			$handle = 'wc-gzdp-blocks-' . $chunk . '-chunk';
			$this->assets->register_script( $handle, $this->assets->get_block_asset_build_path( 'checkout-blocks' . $chunk ), array(), true );

			wp_add_inline_script(
				'wc-gzdp-blocks-checkout-frontend',
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);

			wp_deregister_script( $handle );
		}

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-gzdp-blocks-checkout-frontend' );
			}
		);
	}

	protected function get_chunks() {
		$build_path = Package::get_path( 'build/checkout-blocks' );
		$blocks     = array();

		if ( ! is_dir( $build_path ) ) {
			return array();
		}
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $build_path ) ) as $block_name ) {
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
		return array( 'wc-gzdp-blocks-checkout', 'wc-gzdp-blocks-checkout-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-gzdp-blocks-checkout' );
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
