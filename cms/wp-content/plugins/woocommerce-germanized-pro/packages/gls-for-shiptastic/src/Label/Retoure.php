<?php

namespace Vendidero\Shiptastic\GLS\Label;

use Vendidero\Shiptastic\GLS\Package;
use Vendidero\Shiptastic\Interfaces\ShipmentReturnLabel;

defined( 'ABSPATH' ) || exit;

/**
 * DPD ReturnLabel class.
 */
class Retoure extends Simple implements ShipmentReturnLabel {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'gls_track_id'  => '',
		'shipping_date' => '',
		'return_type'   => '',
		'pickup_date'   => '',
	);

	protected function get_hook_prefix() {
		return 'woocommerce_gzd_gls_return_label_get_';
	}

	public function get_type() {
		return 'return';
	}

	public function get_return_type( $context = 'view' ) {
		return $this->get_prop( 'return_type', $context );
	}

	public function set_return_type( $value ) {
		$this->set_prop( 'return_type', $value );
	}

	public function get_pickup_date( $context = 'view' ) {
		return $this->get_prop( 'pickup_date', $context );
	}

	public function set_pickup_date( $value ) {
		$this->set_prop( 'pickup_date', $value );
	}
}
