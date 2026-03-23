<?php

namespace Vendidero\Shiptastic\DPD\Label;

use Vendidero\Shiptastic\DPD\Package;
use Vendidero\Shiptastic\Labels\Label;

defined( 'ABSPATH' ) || exit;

/**
 * DPD ReturnLabel class.
 */
class Simple extends Label {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'mps_id'         => '',
		'customs_terms'  => '',
		'customs_paper'  => '',
		'pickup_date'    => '',
		'parcel_shop_id' => '',
		'business_unit'  => '',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_page_format( $context = 'view' ) {
		$page_format = $this->get_print_format( $context );

		if ( 'view' === $context && empty( $page_format ) ) {
			$page_format = $this->get_prop( 'page_format', $context );

		}

		return $page_format;
	}

	public function get_customs_terms( $context = 'view' ) {
		return $this->get_prop( 'customs_terms', $context );
	}

	public function get_pickup_date( $context = 'view' ) {
		return $this->get_prop( 'pickup_date', $context );
	}

	public function get_parcel_shop_id( $context = 'view' ) {
		$parcel_shop_id = $this->get_prop( 'parcel_shop_id', $context );

		if ( 'view' === $context && $this->get_service_prop( 'parcel_shop_delivery', 'parcel_shop_id' ) ) {
			$parcel_shop_id = $this->get_service_prop( 'parcel_shop_delivery', 'parcel_shop_id' );
		}

		return $parcel_shop_id;
	}

	public function get_business_unit( $context = 'view' ) {
		return $this->get_prop( 'business_unit', $context );
	}

	public function get_customs_paper( $context = 'view' ) {
		return $this->get_prop( 'customs_paper', $context );
	}

	public function set_page_format( $value ) {
		$this->set_prop( 'print_format', $value );
	}

	public function set_customs_terms( $value ) {
		$this->set_prop( 'customs_terms', $value );
	}

	public function set_business_unit( $value ) {
		$this->set_prop( 'business_unit', $value );
	}

	public function set_customs_paper( $value ) {
		$this->set_prop( 'customs_paper', $value );
	}

	public function set_pickup_date( $date ) {
		$this->set_prop( 'pickup_date', $date );
	}

	public function set_parcel_shop_id( $parcel_shop_id ) {
		$this->set_prop( 'parcel_shop_id', $parcel_shop_id );
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'dpd';
	}

	public function get_mps_id( $context = 'view' ) {
		return $this->get_prop( 'mps_id', $context );
	}

	public function set_mps_id( $mpn ) {
		$this->set_prop( 'mps_id', $mpn );
	}

	/**
	 * @return \WP_Error|true
	 */
	public function fetch() {
		$result = Package::get_api()->get_label( $this );

		return $result;
	}

	public function delete( $force_delete = false ) {
		if ( $api = Package::get_api() ) {
			if ( is_callable( array( $api, 'cancel_label' ) ) ) {
				$api->cancel_label( $this );
			}
		}

		return parent::delete( $force_delete );
	}
}
