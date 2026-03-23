<?php

namespace Vendidero\StoreaBill\WooCommerce;

use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;
use Vendidero\StoreaBill\Invoice\FeeItem;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class OrderItemFee extends OrderItemTaxable {

	/**
	 * @param FeeItem $document_item
	 */
	public function sync( &$document_item, $args = array() ) {
		do_action( "{$this->get_hook_prefix()}before_sync", $this, $document_item, $args );

		parent::sync( $document_item, $args );

		$props = array();

		if ( 'none' === $this->get_order_item()->get_tax_status() ) {
			$props['is_taxable'] = false;
		}

		$props = apply_filters( "{$this->get_hook_prefix()}sync_props", $props, $this, $args );

		$document_item->set_props( $props );

		do_action( "{$this->get_hook_prefix()}synced", $this, $document_item, $args );
	}
}
