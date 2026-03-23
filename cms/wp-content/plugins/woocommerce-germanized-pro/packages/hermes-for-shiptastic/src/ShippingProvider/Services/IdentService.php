<?php

namespace Vendidero\Shiptastic\Hermes\ShippingProvider\Services;

use Vendidero\Shiptastic\Labels\ConfigurationSet;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class IdentService extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'        => 'identService',
			'label'     => _x( 'Ident Service', 'hermes', 'woocommerce-germanized-pro' ),
			'countries' => array( 'DE' ),
			'zones'     => array( 'dom' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'fsk' === $suffix ) {
			$default_value = '';
		}

		return $default_value;
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			if ( $this->get_min_age( $shipment ) ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return int|string
	 */
	protected function get_min_age( $shipment ) {
		$value = '';

		if ( $order = $shipment->get_order_shipment() ) {
			$order_min_age = (int) $order->get_min_age();

			if ( in_array( $order_min_age, array( 16, 18 ), true ) ) {
				$value = $order_min_age;
			}
		}

		return $value;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array[]
	 */
	protected function get_additional_label_fields( $shipment ) {
		$label_fields = parent::get_additional_label_fields( $shipment );
		$value        = $this->get_min_age( $shipment );
		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'fsk' ),
					'label'             => _x( 'FSK', 'hermes', 'woocommerce-germanized-pro' ),
					'description'       => '',
					'type'              => 'select',
					'value'             => $value,
					'options'           => array(
						16 => _x( '>= 16', 'hermes', 'woocommerce-germanized-pro' ),
						18 => _x( '>= 18', 'hermes', 'woocommerce-germanized-pro' ),
					),
					'custom_attributes' => array( 'data-show-if-service_identService' => '' ),
				),
			)
		);

		return $label_fields;
	}

	/**
	 * @param ConfigurationSet $configuration_set
	 *
	 * @return array[]
	 */
	protected function get_additional_setting_fields( $configuration_set ) {
		$base_setting_id = $this->get_setting_id( $configuration_set );
		$setting_id      = $this->get_setting_id( $configuration_set, 'fsk' );
		$value           = $configuration_set->get_service_meta( $this->get_id(), 'fsk', '0' );

		return array(
			array(
				'title'             => _x( 'FSK', 'hermes', 'woocommerce-germanized-pro' ),
				'id'                => $setting_id,
				'type'              => 'select',
				'default'           => '0',
				'value'             => $value,
				'options'           => array(
					16 => _x( '>= 16', 'hermes', 'woocommerce-germanized-pro' ),
					18 => _x( '>= 18', 'hermes', 'woocommerce-germanized-pro' ),
				),
				'custom_attributes' => array( "data-show_if_{$base_setting_id}" => '' ),
				'desc_tip'          => _x( 'Choose this option if you want to let Hermes check your customer\'s age.', 'hermes', 'woocommerce-germanized-pro' ),
			),
		);
	}
}
