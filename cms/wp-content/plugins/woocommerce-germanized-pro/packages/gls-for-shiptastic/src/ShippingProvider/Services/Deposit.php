<?php

namespace Vendidero\Shiptastic\GLS\ShippingProvider\Services;

use Vendidero\Shiptastic\Shipment;

defined( 'ABSPATH' ) || exit;

class Deposit extends BaseService {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'       => 'Deposit',
			'label'    => _x( 'DepositService', 'gls', 'woocommerce-germanized-pro' ),
			'products' => array( 'PARCEL' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'PlaceOfDeposit' === $suffix ) {
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

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'PlaceOfDeposit' ),
					'label'             => _x( 'Location', 'gls', 'woocommerce-germanized-pro' ),
					'placeholder'       => '',
					'description'       => '',
					'type'              => 'text',
					'custom_attributes' => array( 'data-show-if-service_Deposit' => '' ),
					'is_required'       => true,
				),
			)
		);

		return $label_fields;
	}
}
