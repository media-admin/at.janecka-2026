<?php

namespace Vendidero\StoreaBill\WooCommerce;

use Vendidero\StoreaBill\Invoice\TaxableItem;
use Vendidero\StoreaBill\Interfaces\SyncableReferenceItem;
use Vendidero\StoreaBill\TaxRate;
use Vendidero\StoreaBill\Utilities\Numbers;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrderItemTaxable class
 */
class OrderItemTaxable extends OrderItem {

	/**
	 * @var TaxRate[]
	 */
	protected $item_tax_rates = array();

	/**
	 * @param TaxableItem $document_item
	 */
	public function sync( &$document_item, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'prices_include_tax' => false,
				'order_tax_rates'    => array(),
				'line_total'         => '',
				'line_subtotal'      => '',
			)
		);

		parent::sync( $document_item, $args );

		$this->sync_tax_rates( $document_item, $args['order_tax_rates'] );
	}

	/**
	 * @param TaxableItem $document_item
	 * @param TaxRate[]   $order_tax_rates
	 */
	protected function sync_tax_rates( &$document_item, $order_tax_rates ) {
		$rates      = array();
		$item_taxes = $this->get_order_item()->get_taxes();

		/**
		 * Order item taxes might include the tax rate key but still are empty (and thus considered as not used).
		 */
		$item_tax_rates = array_filter(
			$order_tax_rates,
			function ( $tax_rate ) use ( $item_taxes, $document_item ) {
				foreach ( $tax_rate->get_reference_ids() as $ref_id ) {
					$tax_total          = array_key_exists( $ref_id, $item_taxes['total'] ) ? $item_taxes['total'][ $ref_id ] : '';
					$tax_subtotal       = array_key_exists( 'subtotal', $item_taxes ) && array_key_exists( $ref_id, $item_taxes['subtotal'] ) ? $item_taxes['subtotal'][ $ref_id ] : $tax_total;
					$refunded_tax_total = (float) $this->get_order()->get_order()->get_tax_refunded_for_item( $this->get_order_item()->get_id(), $ref_id, $this->get_order_item()->get_type() );

					$tax_total        = '' !== $tax_total ? Numbers::round_to_precision( (float) $tax_total - $refunded_tax_total ) : $tax_total;
					$tax_subtotal     = '' !== $tax_subtotal ? Numbers::round_to_precision( (float) $tax_subtotal - $refunded_tax_total ) : $tax_subtotal;
					$include_tax_rate = '' !== $tax_total || '' !== $tax_subtotal;

					/**
					 * Special case for items which do not include taxes (e.g. free shipping) but may include
					 * taxes during refunds, e.g. refunding a negative amount to add shipping costs on refunding items.
					 */
					if ( $refunded_tax_total < 0.0 ) {
						return true;
					}

					/**
					 * Exclude tax rate in case the tax total amount for the item has been fully refunded, e.g.
					 * a (subsequent) tax refund for a certain order.
					 */
					if ( 0.0 !== $refunded_tax_total ) {
						if ( 0.0 === $tax_total && 0.0 === $tax_subtotal ) {
							$include_tax_rate = false;
						}
					}

					/**
					 * Do only include tax rates for the item which do actually exist.
					 * This may by default explicitly include zero tax rates as these tax rates are needed, e.g. for calculating tax shares.
					 */
					if ( $include_tax_rate ) {
						return true;
					}
				}

				return false;
			}
		);

		if ( is_a( $document_item, '\Vendidero\StoreaBill\Interfaces\SplitTaxable' ) ) {
			// If a shipping order item contains more than one tax rate enable split tax calculation.
			if ( count( $item_tax_rates ) > 1 ) {
				$document_item->set_enable_split_tax( true );
			}
		}

		foreach ( $item_tax_rates as $tax_rate ) {
			if ( ! $document_item->contains_tax_rate( $tax_rate ) ) {
				$document_item->add_tax_rate( $tax_rate );
			}

			$rates[] = $tax_rate->get_merge_key();
		}

		// Remove unused tax rates
		foreach ( $document_item->get_tax_rates() as $tax_rate ) {
			if ( ! in_array( $tax_rate->get_merge_key(), $rates, true ) ) {
				$document_item->remove_tax_rate( $tax_rate->get_merge_key() );
			}
		}
	}
}
