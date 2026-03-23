<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

use Vendidero\StoreaBill\Document\Attribute;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;

defined( 'ABSPATH' ) || exit;

class ShipmentItem extends \Vendidero\Germanized\Pro\StoreaBill\Shipments\ShipmentItem implements SyncableReferenceItem {

	/**
	 * @param \Vendidero\Germanized\Pro\StoreaBill\Shipments\ProductItem $object
	 * @param array $args
	 */
	public function sync( &$object, $args = array() ) {
		do_action( 'storeabill_woo_gzd_shipment_item_before_sync', $this, $object, $args );

		$props = wp_parse_args(
			$args,
			array(
				'quantity'         => $this->get_quantity(),
				'reference_id'     => $this->get_id(),
				'name'             => $this->get_name(),
				'attributes'       => $this->get_attributes( $object ),
				'sku'              => $this->get_sku(),
				'global_unique_id' => $this->get_global_unique_id(),
				'price'            => $this->get_price(),
				'total'            => $this->get_total(),
			)
		);

		$props = apply_filters( 'storeabill_woo_gzd_shipment_item_sync_props', $props, $this, $args );

		$object->set_props( $props );

		do_action( 'storeabill_woo_gzd_shipment_item_synced', $this, $object, $args );
	}
}
