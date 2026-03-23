<?php

namespace Vendidero\Shiptastic\Hermes\Label;

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
		'hermes_order_id' => '',
		'pickup_date'     => '',
	);

	protected function get_hook_prefix() {
		return 'woocommerce_shiptastic_hermes_return_label_get_';
	}

	public function get_type() {
		return 'return';
	}

	public function get_pickup_date( $context = 'view' ) {
		return $this->get_prop( 'pickup_date', $context );
	}

	public function set_pickup_date( $value ) {
		$this->set_prop( 'pickup_date', $value );
	}
}
