<?php

namespace Vendidero\Shiptastic\Hermes\Label;

use Vendidero\Shiptastic\Hermes\Package;
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
		'hermes_order_id' => '',
	);

	public function get_type() {
		return 'simple';
	}

	public function get_hermes_order_id( $context = 'view' ) {
		return $this->get_prop( 'hermes_order_id', $context );
	}

	public function set_hermes_order_id( $value ) {
		$this->set_prop( 'hermes_order_id', $value );
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'hermes';
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
