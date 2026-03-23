<?php
namespace Vendidero\Germanized\Pro\Blocks\BlockTypes;

/**
 * CheckoutOrderSummaryCouponFormBlock class.
 */
class CheckoutShippingVatId extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-shipping-vat-id';

	protected function enqueue_data( array $attributes = array() ) {
		parent::enqueue_data( $attributes );

		if ( class_exists( 'Vendidero\EUTaxHelper\Helper' ) ) {
			$this->assets->register_data( 'postcodeVatExempts', \Vendidero\EUTaxHelper\Helper::get_vat_postcode_exemptions_by_country() );
		}

		$fields_to_check = array_filter( (array) get_option( 'woocommerce_gzdp_vat_id_additional_field_check', array() ) );
		$this->assets->register_data( 'vatIdFieldsToValidate', $fields_to_check );
	}
}
