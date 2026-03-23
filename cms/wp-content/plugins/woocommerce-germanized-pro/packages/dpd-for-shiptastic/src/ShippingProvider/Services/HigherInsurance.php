<?php

namespace Vendidero\Shiptastic\DPD\ShippingProvider\Services;

use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class HigherInsurance extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'       => 'higher_insurance',
			'label'    => _x( 'Higher Insurance', 'dpd', 'woocommerce-germanized-pro' ),
			'zones'    => array( 'dom', 'eu', 'int' ),
			'products' => array( 'DPD', 'B2C' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'amount' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array[]
	 */
	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$value        = min( max( $shipment->get_total(), 520.01 ), 15000 );

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'amount' ),
					'class'             => 'wc_input_decimal',
					'data_type'         => 'price',
					'label'             => _x( 'Insurance amount', 'dpd', 'woocommerce-germanized-pro' ),
					'placeholder'       => '',
					'description'       => '',
					'value'             => wc_format_localized_decimal( $value ),
					'type'              => 'text',
					'custom_attributes' => array( 'data-show-if-service_higher_insurance' => '' ),
					'is_required'       => true,
				),
			)
		);

		return $label_fields;
	}
}
