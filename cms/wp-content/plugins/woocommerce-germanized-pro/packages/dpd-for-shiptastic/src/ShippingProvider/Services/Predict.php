<?php

namespace Vendidero\Shiptastic\DPD\ShippingProvider\Services;

use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class Predict extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'       => 'predict',
			'label'    => _x( 'Notification', 'dpd', 'woocommerce-germanized-pro' ),
			'zones'    => array( 'dom', 'eu', 'int' ),
			'products' => array( 'CL', 'DPD' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			$label_supports_email_transmit = false;

			if ( $shipment_order = $shipment->get_order_shipment() ) {
				$label_supports_email_transmit = $shipment_order->supports_third_party_email_transmission();
			}

			if ( $shipment->get_email() && $label_supports_email_transmit ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'email' === $suffix ) {
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
		$email        = $shipment->get_email();

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'email' ),
					'label'             => _x( 'E-Mail', 'dpd', 'woocommerce-germanized-pro' ),
					'description'       => '',
					'value'             => $email,
					'type'              => 'text',
					'custom_attributes' => array(
						'data-show-if-service_predict' => '',
					),
				),
			)
		);

		return $label_fields;
	}
}
