<?php

namespace Vendidero\Germanized\Pro\StoreaBill\Shipments;

use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Interfaces\Priceable;
use Vendidero\StoreaBill\Interfaces\Summable;
use Vendidero\StoreaBill\WooCommerce\Product;

defined( 'ABSPATH' ) || exit;

class ProductItem extends Item implements Summable, Priceable {

	protected $product = null;

	protected $extra_data = array(
		'sku'              => '',
		'global_unique_id' => '',
		'price'            => 0.0,
		'total'            => 0.0,
	);

	protected $data_store_name = 'shipment_product_item';

	public function get_item_type() {
		return 'product';
	}

	public function get_document_group() {
		return 'shipments';
	}

	public function get_data() {
		$data = parent::get_data();

		$data['price_subtotal'] = $this->get_price_subtotal();
		$data['subtotal']       = $this->get_subtotal();

		return $data;
	}

	public function get_sku( $context = 'view' ) {
		return $this->get_prop( 'sku', $context );
	}

	public function set_sku( $sku ) {
		$this->set_prop( 'sku', $sku );
	}

	public function get_global_unique_id( $context = 'view' ) {
		return $this->get_prop( 'global_unique_id', $context );
	}

	public function set_global_unique_id( $id ) {
		$this->set_prop( 'global_unique_id', $id );
	}

	/**
	 * @param $context
	 *
	 * @return float
	 */
	public function get_price( $context = 'view' ) {
		return (float) $this->get_prop( 'price', $context );
	}

	/**
	 * @param $context
	 *
	 * @return float
	 */
	public function get_price_subtotal( $context = '' ) {
		return (float) $this->get_price( $context );
	}

	public function set_price( $price ) {
		$this->set_prop( 'price', (float) $price );
	}

	/**
	 * @param $context
	 *
	 * @return float
	 */
	public function get_total( $context = 'view' ) {
		return (float) $this->get_prop( 'total', $context );
	}

	public function set_total( $total ) {
		$this->set_prop( 'total', (float) $total );
	}

	public function calculate_totals() {
		$quantity = $this->get_quantity() > 0 ? $this->get_quantity() : 1;

		$this->set_price( $this->get_total() / $quantity );
	}

	/**
	 * @param $context
	 *
	 * @return float
	 */
	public function get_subtotal( $context = '' ) {
		return $this->get_total( $context );
	}

	/**
	 * @return bool|ShipmentItem|null
	 */
	public function get_reference() {
		if ( is_null( $this->reference ) ) {
			$this->reference = false;

			if ( $this->get_reference_id() > 0 && ( $document = $this->get_document() ) ) {
				if ( $shipment = $document->get_reference() ) {
					$this->reference = $shipment->get_item( wc_stc_get_shipment_item( $this->get_reference_id(), $shipment->get_type() ) );
				}
			}
		}

		return $this->reference;
	}

	/**
	 * @return false|Product
	 */
	public function get_product() {
		if ( is_null( $this->product ) ) {
			if ( $reference = $this->get_reference() ) {
				try {
					$this->product = new Product( $reference->get_product() );
				} catch ( \Exception $e ) {
					$this->product = false;
				}
			} else {
				$this->product = false;
			}
		}

		return $this->product;
	}

	public function get_image_url( $size = '', $placeholder = false ) {
		if ( $product = $this->get_product() ) {
			return $product->get_image_url( $size, $placeholder );
		}

		return parent::get_image_url( $size, $placeholder );
	}
}
