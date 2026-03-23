<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;
use Vendidero\StoreaBill\Utilities\Numbers;

defined( 'ABSPATH' ) || exit;

class ShipmentItem extends \Vendidero\Germanized\Pro\StoreaBill\Shipments\ShipmentItem implements SyncableReferenceItem {

	public function get_document_item_type() {
		return 'customs_product';
	}

	public function get_sku() {
		return $this->shipment_item->get_sku() ? $this->shipment_item->get_sku() : $this->shipment_item->get_product_id();
	}

	public function get_hs_code() {
		return $this->shipment_item->get_hs_code();
	}

	public function get_mid_code() {
		return is_callable( array( $this->shipment_item, 'get_mid_code' ) ) ? $this->shipment_item->get_mid_code() : '';
	}

	public function get_manufacture_country() {
		return $this->shipment_item->get_manufacture_country();
	}

	/**
	 * @param ProductItem $object
	 * @param array $args
	 */
	public function sync( &$object, $args = array() ) {
		do_action( 'storeabill_woo_gzd_shipment_item_before_sync', $this, $object, $args );

		$shipment       = $this->shipment_item->get_shipment();
		$dimension_unit = $shipment ? $shipment->get_dimension_unit() : get_option( 'woocommerce_dimension_unit' );
		$weight         = Numbers::round_to_precision( sab_format_decimal( wc_get_weight( $this->shipment_item->get_weight(), 'kg', $shipment ? $shipment->get_weight_unit() : get_option( 'woocommerce_weight_unit' ) ) ), 3 );

		// Enforce min weight
		if ( $weight < 0.001 ) {
			$weight = 0.001;
		}

		$total     = $this->shipment_item->get_total();
		$min_total = ( 0.01 * $this->shipment_item->get_quantity() );

		// Enforce min total
		if ( $total < $min_total ) {
			$total = $min_total;
		}

		$props = wp_parse_args(
			$args,
			array(
				'created_via'      => '',
				'quantity'         => $this->get_quantity(),
				'reference_id'     => $this->get_id(),
				'attributes'       => $this->get_attributes( $object ),
				'sku'              => $this->get_sku(),
				'global_unique_id' => $this->get_global_unique_id(),
				'weight'           => $weight,
				'length'           => wc_get_dimension( $this->shipment_item->get_length(), 'cm', $dimension_unit ),
				'width'            => wc_get_dimension( $this->shipment_item->get_width(), 'cm', $dimension_unit ),
				'height'           => wc_get_dimension( $this->shipment_item->get_height(), 'cm', $dimension_unit ),
			)
		);

		if ( 'manual' !== $props['created_via'] ) {
			$props = wp_parse_args(
				$props,
				array(
					'name'                => $this->get_name(),
					'total'               => $total,
					'hs_code'             => $this->get_hs_code(),
					'mid_code'            => $this->get_mid_code(),
					'manufacture_country' => $this->get_manufacture_country(),
				)
			);
		}

		$props = apply_filters( 'storeabill_woo_gzd_shipment_item_sync_props', $props, $this, $args );

		$object->set_props( $props );
		$object->calculate_totals();

		do_action( 'storeabill_woo_gzd_shipment_item_synced', $this, $object, $args );
	}

	public function get_name() {
		$description = $this->shipment_item->get_name();

		if ( is_callable( array( $this->shipment_item, 'get_customs_description' ) ) ) {
			$description = $this->shipment_item->get_customs_description();
		}

		return $description;
	}
}
