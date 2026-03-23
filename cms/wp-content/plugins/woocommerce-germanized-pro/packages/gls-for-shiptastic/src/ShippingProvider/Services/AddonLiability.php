<?php

namespace Vendidero\Shiptastic\GLS\ShippingProvider\Services;

use Vendidero\Shiptastic\Shipment;

defined( 'ABSPATH' ) || exit;

class AddonLiability extends BaseService {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'       => 'AddonLiability',
			'label'    => _x( 'Addon Liability', 'gls', 'woocommerce-germanized-pro' ),
			'products' => array( 'PARCEL' ),
			'level'    => 'unit',
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'Amount' === $suffix ) {
			$default_value = '';
		} elseif ( 'Currency' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$amount       = $shipment->get_total() + round( $shipment->get_additional_total(), wc_get_price_decimals() );
		$currency     = $shipment->get_order() ? $shipment->get_order()->get_currency() : get_woocommerce_currency();

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'Amount' ),
					'class'             => 'wc_input_decimal',
					'data_type'         => 'price',
					'label'             => _x( 'Amount', 'gls', 'woocommerce-germanized-pro' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => wc_format_localized_decimal( $amount ),
					'type'              => 'text',
					'custom_attributes' => array( 'data-show-if-service_AddonLiability' => '' ),
					'is_required'       => true,
				),
				array(
					'id'                => $this->get_label_field_id( 'Currency' ),
					'label'             => _x( 'Currency', 'gls', 'woocommerce-germanized-pro' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => $currency,
					'type'              => 'text',
					'custom_attributes' => array( 'data-show-if-service_AddonLiability' => '' ),
					'is_required'       => true,
				),
			)
		);

		return $label_fields;
	}
}
