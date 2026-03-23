<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\StoreaBill\Utilities\Numbers;
use Vendidero\StoreaBill\WooCommerce\Order;

defined( 'ABSPATH' ) || exit;

class Shipment extends \Vendidero\Germanized\Pro\StoreaBill\Shipments\Shipment {

	protected function get_order_item_tax_refunded( $order, $item_id, $item_type = 'line_item' ) {
		$total = 0.0;

		foreach ( $order->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$refunded_item_id = (int) $refunded_item->get_meta( '_refunded_item_id' );

				if ( $refunded_item_id === $item_id ) {
					$total += $refunded_item->get_total_tax();
				}
			}
		}

		return floatval( $total ) * -1;
	}

	public function get_shipping_total() {
		$shipping_total = 0.0;
		$order          = $this->shipment->get_order();

		if ( $order ) {
			$shipping_total = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();

			foreach ( $order->get_items( 'shipping' ) as $item ) {
				if ( is_a( $order, 'WC_Order' ) ) {
					$shipping_total -= (float) $order->get_total_refunded_for_item( $item->get_id(), 'shipping' );
					$shipping_total -= $this->get_order_item_tax_refunded( $order, $item->get_id(), 'shipping' );
				}
			}
		}

		return $shipping_total;
	}

	/**
	 * @param \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice $commercial_invoice
	 * @param array $args
	 */
	public function sync( &$commercial_invoice, $args = array() ) {
		do_action( 'woocommerce_gzdp_before_sync_commercial_invoice', $this->shipment, $args );

		$billing_address = array();
		$order           = $this->shipment->get_order();
		$shipping_total  = $this->get_shipping_total();
		$fee_total       = 0.0;

		if ( $order ) {
			$billing_address = $order->get_address();

			foreach ( $order->get_fees() as $item ) {
				$fee_total += (float) $item->get_total() + (float) $item->get_total_tax();

				if ( is_a( $order, 'WC_Order' ) ) {
					$fee_total -= (float) $order->get_total_refunded_for_item( $item->get_id(), 'fee' );
					$fee_total -= $this->get_order_item_tax_refunded( $order, $item->get_id(), 'fee' );
				}
			}
		}

		$commercial_invoice_args = wp_parse_args(
			$args,
			array(
				'reference_id'     => $this->get_id(),
				'reference_number' => $this->shipment->get_shipment_number(),
				'reference_type'   => 'germanized',
				'order_id'         => $this->shipment->get_order_id(),
				'order_number'     => $this->shipment->get_order_number(),
				'country'          => $this->shipment->get_country(),
				'address'          => 'return' === $this->shipment->get_type() ? $this->shipment->get_address() : $billing_address,
				'shipping_address' => $this->shipment->get_address(),
				'customer_id'      => $order ? $order->get_customer_id() : 0,
				'weight'           => wc_get_weight( $this->shipment->get_total_weight(), 'kg', $this->shipment->get_weight_unit() ),
				'packaging_weight' => wc_get_weight( $this->shipment->get_packaging_weight(), 'kg', $this->shipment->get_weight_unit() ),
				'net_weight'       => wc_get_weight( $this->shipment->get_weight(), 'kg', $this->shipment->get_weight_unit() ),
				'length'           => wc_get_dimension( $this->shipment->get_length(), 'cm', $this->shipment->get_dimension_unit() ),
				'width'            => wc_get_dimension( $this->shipment->get_width(), 'cm', $this->shipment->get_dimension_unit() ),
				'height'           => wc_get_dimension( $this->shipment->get_height(), 'cm', $this->shipment->get_dimension_unit() ),
			)
		);

		if ( 'return' === $this->shipment->get_type() ) {
			$commercial_invoice_args = wp_parse_args(
				$commercial_invoice_args,
				array(
					'sender_address' => $this->shipment->get_sender_address(),
				)
			);
		}

		/**
		 * Do not override properties that may be set manually.
		 */
		if ( 'manual' !== $commercial_invoice->get_created_via() && $commercial_invoice->get_id() <= 0 ) {
			$commercial_invoice_args = wp_parse_args(
				$commercial_invoice_args,
				array(
					'shipping_total' => $shipping_total,
					'fee_total'      => $fee_total,
					'currency'       => $order ? $order->get_currency() : get_woocommerce_currency(),
					'export_type'    => Helper::get_default_export_type( $this->shipment ),
					'export_reason'  => Helper::get_default_export_reason( $this->shipment ),
					'incoterms'      => Helper::get_default_incoterms( $this->shipment ),
				)
			);
		}

		$items_data = isset( $args['items'] ) ? (array) $args['items'] : array();

		$commercial_invoice->set_props( $commercial_invoice_args );

		foreach ( $this->shipment->get_items() as $item ) {
			if ( $shipment_item = $this->get_item( $item ) ) {
				$is_new        = false;
				$document_item = $commercial_invoice->get_item_by_reference_id( $item->get_id() );
				$item_args     = ( isset( $items_data[ $item->get_id() ] ) ? $items_data[ $item->get_id() ] : array() );

				if ( ! $document_item ) {
					$document_item = sab_get_document_item( 0, $shipment_item->get_document_item_type() );
					$is_new        = true;
				}

				$item_data = wp_parse_args(
					$item_args,
					array(
						'created_via' => ( empty( $item_args ) && '' === $document_item->get_name() ) ? 'automation' : $commercial_invoice->get_created_via(),
					)
				);

				$document_item->set_document( $commercial_invoice );
				$shipment_item->sync( $document_item, $item_data );

				if ( $is_new ) {
					$commercial_invoice->add_item( $document_item );
				}
			}
		}

		/**
		 * Remove items that do not exist in parent shipment any longer.
		 */
		foreach ( $commercial_invoice->get_items() as $item ) {
			if ( ! $this->shipment->get_item( $item->get_reference_id() ) ) {
				$commercial_invoice->remove_item( $item->get_id() );
			}
		}

		$commercial_invoice->calculate_totals();
		$commercial_invoice->calculate_weights();

		do_action( 'woocommerce_gzdp_synced_commercial_invoice', $commercial_invoice, $this->shipment, $args );

		do_action( 'woocommerce_gzdp_after_sync_commercial_invoice', $this->shipment, $args );

		$this->document = $commercial_invoice;
	}

	/**
	 * @param \Vendidero\Shiptastic\ShipmentItem $item
	 *
	 * @return ShipmentItem
	 */
	public function get_item( $item ) {
		$classname = '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\ShipmentItem';

		return new $classname( $item );
	}

	public function get_commercial_invoice() {
		if ( is_null( $this->document ) ) {
			$this->document = wc_gzdp_get_commercial_invoice_by_shipment( $this->get_id() );
		}

		return $this->document;
	}
}
