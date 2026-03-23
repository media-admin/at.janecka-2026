<?php

namespace Vendidero\Shiptastic\GLS\Label;

use Vendidero\Shiptastic\GLS\Package;
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
		'gls_track_id'  => '',
		'shipping_date' => '',
		'incoterms'     => '',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_gls_track_id( $context = 'view' ) {
		return $this->get_prop( 'gls_track_id', $context );
	}

	public function set_gls_track_id( $value ) {
		$this->set_prop( 'gls_track_id', $value );
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'gls';
	}

	public function get_shipping_date( $context = 'view' ) {
		return $this->get_prop( 'shipping_date', $context );
	}

	public function set_shipping_date( $date ) {
		$this->set_prop( 'shipping_date', $date );
	}

	public function get_incoterms( $context = 'view' ) {
		return $this->get_prop( 'incoterms', $context );
	}

	public function set_incoterms( $value ) {
		$this->set_prop( 'incoterms', $value );
	}

	/**
	 * @return \WP_Error|true
	 */
	public function fetch() {
		$result = Package::get_api()->get_label( $this );

		return $result;
	}

	public function delete( $force_delete = false ) {
		Package::get_api()->cancel_label( $this );

		return parent::delete( $force_delete );
	}
}
