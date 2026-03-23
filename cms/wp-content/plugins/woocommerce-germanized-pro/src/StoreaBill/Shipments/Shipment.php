<?php

namespace Vendidero\Germanized\Pro\StoreaBill\Shipments;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Interfaces\SyncableReference;

defined( 'ABSPATH' ) || exit;

abstract class Shipment implements SyncableReference {

	/**
	 * @var bool|\Vendidero\Shiptastic\Shipment|null
	 */
	protected $shipment = null;

	/**
	 * @var null|Document
	 */
	protected $document = null;

	public function __construct( $shipment ) {
		if ( is_numeric( $shipment ) ) {
			$shipment = wc_stc_get_shipment( $shipment );
		}

		if ( ! is_a( $shipment, '\Vendidero\Shiptastic\Shipment' ) ) {
			throw new \Exception( esc_html__( 'Invalid shipment.', 'woocommerce-germanized-pro' ) );
		}

		$this->shipment = $shipment;
	}

	public function get_id() {
		return $this->shipment->get_id();
	}

	public function get_reference_type() {
		return 'germanized';
	}

	/**
	 * @return \Vendidero\Shiptastic\Shipment
	 */
	public function get_shipment() {
		return $this->shipment;
	}

	public function get_object() {
		return $this->get_shipment();
	}

	public function get_shipment_type() {
		return $this->get_shipment()->get_type();
	}

	/**
	 * @param \Vendidero\Germanized\Pro\StoreaBill\PackingSlip $packing_slip
	 * @param array $args
	 */
	abstract public function sync( &$packing_slip, $args = array() );

	/**
	 * @param \Vendidero\Shiptastic\ShipmentItem $item
	 *
	 * @return ShipmentItem
	 */
	abstract public function get_item( $item );

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->get_shipment()->get_meta( $key, $single, $context );
	}

	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif ( is_callable( array( $this->get_shipment(), $method ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		if ( method_exists( $this->get_shipment(), $method ) ) {
			return call_user_func_array( array( $this->get_shipment(), $method ), $args );
		}

		return false;
	}
}
