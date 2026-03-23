<?php
namespace Vendidero\Germanized\Pro\Blocks;

use Vendidero\Germanized\Pro\Package;

/**
 * BlockTypesController class.
 *
 * @since 5.0.0
 * @internal
 */
final class BlockTypesController {

	/**
	 * @var Assets
	 */
	private $assets = null;

	/**
	 * @param Assets $assets
	 */
	public function __construct( $assets ) {
		$this->assets = $assets;

		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register blocks, hooking up assets and render functions as needed.
	 */
	public function register_blocks() {
		$block_types = $this->get_block_types();

		foreach ( $block_types as $block_type ) {
			$block_type_class = __NAMESPACE__ . '\\BlockTypes\\' . $block_type;

			new $block_type_class( $this->assets );
		}
	}

	/**
	 * Get list of block types.
	 *
	 * @return array
	 */
	protected function get_block_types() {
		$block_types = array(
			'MultilevelCheckout',
			'MultilevelCheckoutFieldsBlock',
			'MultilevelCheckoutSidebar',
			'MultilevelCheckoutSubmit',
			'MultilevelCheckoutBillingAddressBlock',
			'MultilevelCheckoutContactStep',
			'MultilevelCheckoutShippingStep',
			'MultilevelCheckoutPaymentStep',
			'MultilevelCheckoutConfirmationStep',
		);

		if ( Package::is_vat_id_module_enabled() ) {
			$block_types = array_merge(
				$block_types,
				array(
					'CheckoutBillingVatId',
					'CheckoutShippingVatId',
				)
			);
		}

		return $block_types;
	}
}
