<?php

namespace Vendidero\Shiptastic\Hermes\ShippingProvider\Services;

use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShippingProvider\Service;

defined( 'ABSPATH' ) || exit;

class ParcelShopDeliveryService extends Service {

	public function __construct( $shipping_provider, $args = array() ) {
		$args = array(
			'id'        => 'parcelShopDeliveryService',
			'label'     => _x( 'Parcel Shop Delivery', 'hermes', 'woocommerce-germanized-pro' ),
			'countries' => array( 'DE' ),
			'zones'     => array( 'dom' ),
		);

		parent::__construct( $shipping_provider, $args );
	}

	public function book_as_default( $shipment ) {
		$book_as_default = parent::book_as_default( $shipment );

		if ( false === $book_as_default ) {
			if ( $shipment->has_pickup_location() ) {
				$book_as_default = true;
			}
		}

		return $book_as_default;
	}

	public function get_default_value( $suffix = '' ) {
		$default_value = parent::get_default_value( $suffix );

		if ( 'mode' === $suffix ) {
			$default_value = 'auto';
		} elseif ( 'parcel_shop_id' === $suffix ) {
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
		$label_fields   = parent::get_additional_label_fields( $shipment );
		$has_auto       = $shipment->has_pickup_location() ? 'manual' : 'auto';
		$parcel_shop_id = $shipment->get_pickup_location_code();

		$label_fields = array_merge(
			$label_fields,
			array(
				array(
					'id'                => $this->get_label_field_id( 'mode' ),
					'label'             => _x( 'Mode', 'hermes', 'woocommerce-germanized-pro' ),
					'description'       => '',
					'value'             => $has_auto,
					'type'              => 'select',
					'options'           => array(
						'auto'   => _x( 'By receiver address', 'hermes', 'woocommerce-germanized-pro' ),
						'manual' => _x( 'By parcel shop ID', 'hermes', 'woocommerce-germanized-pro' ),
					),
					'custom_attributes' => array( 'data-show-if-service_parcelShopDeliveryService' => '' ),
				),
				array(
					'id'                => $this->get_label_field_id( 'parcel_shop_id' ),
					'label'             => _x( 'Parcel Shop ID', 'hermes', 'woocommerce-germanized-pro' ),
					'description'       => '',
					'value'             => $parcel_shop_id,
					'type'              => 'text',
					'custom_attributes' => array(
						'data-show-if-service_parcelShopDeliveryService'      => '',
						'data-show-if-service_parcelShopDeliveryService_mode' => 'manual',
					),
				),
			)
		);

		return $label_fields;
	}
}
