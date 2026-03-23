<?php

namespace Vendidero\StoreaBill\Invoice;

use Exception;
use Vendidero\StoreaBill\Utilities\Numbers;
use Vendidero\StoreaBill\WooCommerce\OrderItemFee;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * SimpleInvoice class
 */
class Simple extends Invoice {

	protected $cancellations = null;

	public function get_item_types_cancelable() {
		return apply_filters(
			$this->get_hook_prefix() . 'item_types_cancelable',
			array(
				'product',
				'fee',
				'shipping',
				'voucher',
			),
			$this
		);
	}

	public function get_invoice_type() {
		return 'simple';
	}

	protected function maybe_set_paid() {
		if ( 'complete' === $this->get_payment_status() ) {
			$this->set_date_paid( time() );
			$this->set_total_paid( $this->get_total() );

			foreach ( $this->get_cancellations() as $cancellation ) {
				$cancellation->set_payment_status( 'complete' );
				$cancellation->set_total_paid( $cancellation->get_total() );

				$cancellation->save();
			}
		} elseif ( 'pending' === $this->get_payment_status() ) {
			$this->set_date_paid( null );
			$this->set_total_paid( 0 );

			foreach ( $this->get_cancellations() as $cancellation ) {
				$cancellation->set_payment_status( 'pending' );
				$cancellation->set_total_paid( 0 );

				$cancellation->save();
			}
		}
	}

	public function get_cancellations() {
		if ( is_null( $this->cancellations ) ) {
			if ( $this->get_id() > 0 ) {
				$this->cancellations = sab_get_invoices(
					array(
						'parent_id' => $this->get_id(),
						'type'      => 'cancellation',
					)
				);
			}

			if ( is_null( $this->cancellations ) ) {
				$this->cancellations = array();
			}
		}

		return $this->cancellations;
	}

	public function is_cancelable() {
		$is_cancelable = true;

		if ( ! $this->has_status( array( 'closed' ) ) ) {
			$is_cancelable = false;
		} else {
			// Check items left for cancellation
			$items_left = $this->get_items_left_to_cancel();

			if ( empty( $items_left ) ) {
				$is_cancelable = false;
			}
		}

		return $is_cancelable;
	}

	public function get_items_left_to_cancel() {
		$items_left = array();

		foreach ( $this->get_items( $this->get_item_types_cancelable() ) as $item ) {
			$items_left[ $item->get_id() ] = array(
				'quantity'      => $item->get_quantity(),
				'line_total'    => $item->get_line_total(),
				'total'         => $item->get_total(),
				'tax'           => $item->get_total_tax(),
				'line_subtotal' => $item->get_line_subtotal(),
				'subtotal'      => $item->get_subtotal(),
				'subtotal_tax'  => $item->get_subtotal_tax(),
				'is_free'       => ( 0.0 === $item->get_total() && 0.0 === $item->get_subtotal() ),
			);
		}

		foreach ( $this->get_cancellations() as $cancellation ) {
			$items = $cancellation->get_items( $this->get_item_types_cancelable() );

			foreach ( $items as $item ) {
				$parent_id = $item->get_parent_id();

				if ( $parent_item = $this->get_item( $parent_id ) ) {
					$cancelled_quantity      = $item->get_quantity();
					$cancelled_total         = $item->get_total();
					$cancelled_subtotal      = $item->get_subtotal();
					$cancelled_line_total    = $item->get_line_total();
					$cancelled_line_subtotal = $item->get_line_subtotal();
					$cancelled_tax           = $item->get_total_tax();
					$cancelled_subtotal_tax  = $item->get_subtotal_tax();

					if ( empty( $cancelled_subtotal ) ) {
						$cancelled_subtotal = $cancelled_total;
					}

					if ( empty( $cancelled_line_subtotal ) ) {
						$cancelled_line_subtotal = $cancelled_line_total;
					}

					if ( empty( $cancelled_subtotal_tax ) ) {
						$cancelled_subtotal_tax = $cancelled_tax;
					}

					if ( isset( $items_left[ $parent_item->get_id() ] ) ) {
						$items_left[ $parent_item->get_id() ]['quantity']      = ( $items_left[ $parent_item->get_id() ]['quantity'] - $cancelled_quantity );
						$items_left[ $parent_item->get_id() ]['total']         = ( $items_left[ $parent_item->get_id() ]['total'] - $cancelled_total );
						$items_left[ $parent_item->get_id() ]['line_total']    = ( $items_left[ $parent_item->get_id() ]['line_total'] - $cancelled_line_total );
						$items_left[ $parent_item->get_id() ]['subtotal']      = ( $items_left[ $parent_item->get_id() ]['subtotal'] - $cancelled_subtotal );
						$items_left[ $parent_item->get_id() ]['line_subtotal'] = ( $items_left[ $parent_item->get_id() ]['line_subtotal'] - $cancelled_line_subtotal );
						$items_left[ $parent_item->get_id() ]['tax']           = ( $items_left[ $parent_item->get_id() ]['tax'] - $cancelled_tax );
						$items_left[ $parent_item->get_id() ]['subtotal_tax']  = ( $items_left[ $parent_item->get_id() ]['subtotal_tax'] - $cancelled_subtotal_tax );

						if ( 0.0 === Numbers::round_to_precision( $items_left[ $parent_item->get_id() ]['total'] ) && 0.0 === Numbers::round_to_precision( $items_left[ $parent_item->get_id() ]['tax'] ) ) {
							unset( $items_left[ $parent_item->get_id() ] );
						}
					}
				}
			}
		}

		$total_to_cancel = 0.0;

		foreach ( $items_left as $item_id => $item ) {
			$org_quantity = $items_left[ $item_id ]['quantity'];

			if ( empty( $items_left[ $item_id ]['quantity'] ) ) {
				$items_left[ $item_id ]['quantity'] = 1;
			}

			$total_rounded        = Numbers::round_to_precision( $items_left[ $item_id ]['total'] );
			$subtotal_rounded     = Numbers::round_to_precision( $items_left[ $item_id ]['subtotal'] );
			$tax_rounded          = Numbers::round_to_precision( $items_left[ $item_id ]['tax'] );
			$subtotal_tax_rounded = Numbers::round_to_precision( $items_left[ $item_id ]['subtotal_tax'] );

			$total_to_cancel += $total_rounded;

			/**
			 * Respect free items and by default allow cancelling them in case the quantity
			 * available to cancel is greater than 0.
			 *
			 * Explicitly check subtotal too as vouchers/discounts may have been added
			 * which might lead to item totals of zero which still need cancellation.
			 */
			if ( 0.0 === $total_rounded && 0.0 === $subtotal_rounded && 0.0 === $tax_rounded && ( ! $item['is_free'] || $org_quantity <= 0 ) ) {
				unset( $items_left[ $item_id ] );
			}
		}

		return $items_left;
	}

	/**
	 * @return float
	 */
	public function get_total_left_to_cancel() {
		$items_left = $this->get_items_left_to_cancel();
		$total_left = 0.0;

		foreach ( $items_left as $item_id => $item ) {
			$total_left += (float) $item['total'];
		}

		return Numbers::round_to_precision( $total_left );
	}

	/**
	 * @param $item_id
	 *
	 * @return int
	 */
	public function get_item_quantity_cancelled( $item_id ) {
		$quantity_cancelled = 0;

		foreach ( $this->get_cancellations() as $cancellation ) {
			$items = $cancellation->get_items( $this->get_item_types_cancelable() );

			foreach ( $items as $item ) {
				$parent_id = $item->get_parent_id();

				if ( $parent_id === (int) $item_id ) {
					$quantity_cancelled += $item->get_quantity();
				}
			}
		}

		return $quantity_cancelled;
	}

	/**
	 * @param $item_id
	 * @param $incl_tax
	 *
	 * @return float
	 */
	public function get_item_total_cancelled( $item_id, $incl_tax = true ) {
		$total_cancelled = 0.0;

		foreach ( $this->get_cancellations() as $cancellation ) {
			$items = $cancellation->get_items( $this->get_item_types_cancelable() );

			foreach ( $items as $item ) {
				$parent_id = $item->get_parent_id();

				if ( $parent_id === (int) $item_id ) {
					$total_cancelled += $incl_tax ? (float) $item->get_total() : (float) $item->get_total_net();
				}
			}
		}

		return $total_cancelled;
	}

	/**
	 * @param $item_id
	 * @param $incl_tax
	 *
	 * @return float
	 */
	public function get_item_subtotal_cancelled( $item_id, $incl_tax = true ) {
		$total_cancelled = 0.0;

		foreach ( $this->get_cancellations() as $cancellation ) {
			$items = $cancellation->get_items( $this->get_item_types_cancelable() );

			foreach ( $items as $item ) {
				$parent_id = $item->get_parent_id();

				if ( $parent_id === (int) $item_id ) {
					$total_cancelled += $incl_tax ? (float) $item->get_subtotal() : (float) $item->get_subtotal_net();
				}
			}
		}

		return $total_cancelled;
	}

	/**
	 * @param $item_id
	 *
	 * @return float
	 */
	public function get_item_tax_total_cancelled( $item_id ) {
		$total_cancelled = 0.0;

		foreach ( $this->get_cancellations() as $cancellation ) {
			$items = $cancellation->get_items( $this->get_item_types_cancelable() );

			foreach ( $items as $item ) {
				$parent_id = $item->get_parent_id();

				if ( $parent_id === (int) $item_id ) {
					$total_cancelled += (float) $item->get_total_tax();
				}
			}
		}

		return $total_cancelled;
	}

	protected function maybe_set_cancelled_status() {
		$items_left = $this->get_items_left_to_cancel();

		if ( empty( $items_left ) ) {
			$this->set_payment_status( 'complete' );
			$this->update_status( 'cancelled' );
		}
	}

	/**
	 * @param Cancellation $cancellation
	 */
	public function add_cancellation( $cancellation ) {
		$this->get_cancellations();

		$this->cancellations[] = $cancellation;
	}

	/**
	 * @param array $items
	 *
	 * @return WP_Error|Cancellation
	 */
	public function cancel( $items = array(), $refund_order_id = 0, $cancellation_props = array() ) {
		$error           = new WP_Error();
		$additional_fees = array();

		if ( $this->data_store->get_document_revision( $this ) !== $this->get_revision() ) {
			$error->add( 'non-cancelable', sprintf( _x( 'This document is in a dirty state and could not be saved - a newer revision exists. Current changes: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_print_r( $this->get_changes(), true ) ) );

			return $error;
		}

		if ( ! $this->is_cancelable() ) {
			if ( ! $this->is_finalized() ) {
				$error->add( 'non-cancelable', _x( 'This invoice is not finalized yet. Update the invoice status before cancelling.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			} else {
				$error->add( 'non-cancelable', _x( 'This invoice has been fully cancelled.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		} else {
			try {
				$items_left      = $this->get_items_left_to_cancel();
				$items_to_cancel = empty( $items ) ? $items_left : array();

				if ( ! empty( $items ) ) {
					$total_to_cancel = 0;

					foreach ( $items as $item_id => $item_data ) {
						if ( isset( $items_left[ $item_id ] ) ) {
							$item_data = wp_parse_args(
								$item_data,
								array(
									'quantity'   => '',
									'total'      => '',
									'line_total' => '',
									'subtotal'   => '',
								)
							);

							if ( empty( $item_data['total'] ) && ! empty( $item_data['quantity'] ) ) {
								if ( $item = $this->get_item( $item_id ) ) {
									$item_data['total']      = $item_data['quantity'] * $item->get_price();
									$item_data['line_total'] = $item_data['quantity'] * ( $this->prices_include_tax() ? $item->get_price() : $item->get_price_net() );
								}
							} elseif ( empty( $item_data['total'] ) ) {
								$item_data['total']      = $items_left[ $item_id ]['total'];
								$item_data['line_total'] = $items_left[ $item_id ]['line_total'];
							}

							/**
							 * Calculate a share to be cancelled to prevent rounding issues with interpolating subtotal amounts.
							 */
							if ( empty( $item_data['subtotal'] ) ) {
								if ( $item = $this->get_item( $item_id ) ) {
									$share_to_cancel            = 0.0 !== $items_left[ $item_id ]['total'] ? abs( $item_data['total'] ) / abs( $item->get_total() ) : 1.0;
									$item_data['subtotal']      = $item->get_subtotal() * $share_to_cancel;
									$item_data['line_subtotal'] = $item->get_line_subtotal() * $share_to_cancel;
								} else {
									$item_data['subtotal']      = $items_left[ $item_id ]['total'];
									$item_data['line_subtotal'] = $items_left[ $item_id ]['line_total'];
								}
							}

							$item_data['quantity'] = empty( $item_data['quantity'] ) ? 1 : $item_data['quantity'];

							if ( $item_data['quantity'] > $items_left[ $item_id ]['quantity'] ) {
								$error->add( 'item-invalid', sprintf( _x( 'The item quantity for %d exceeds quantity left to cancel.', 'storeabill-core', 'woocommerce-germanized-pro' ), $item_id ) );
							}

							if ( abs( $item_data['total'] ) > abs( $items_left[ $item_id ]['total'] ) ) {
								$error->add( 'item-invalid', sprintf( _x( 'The item total for %d exceeds total left to cancel.', 'storeabill-core', 'woocommerce-germanized-pro' ), $item_id ) );
							}

							if ( abs( $item_data['line_total'] ) > abs( $items_left[ $item_id ]['line_total'] ) ) {
								$error->add( 'item-invalid', sprintf( _x( 'The item total for %d exceeds total left to cancel.', 'storeabill-core', 'woocommerce-germanized-pro' ), $item_id ) );
							}

							if ( abs( $item_data['subtotal'] ) > abs( $items_left[ $item_id ]['subtotal'] ) ) {
								$error->add( 'item-invalid', sprintf( _x( 'The item subtotal for %d exceeds subtotal left to cancel.', 'storeabill-core', 'woocommerce-germanized-pro' ), $item_id ) );
							}

							if ( abs( $item_data['line_subtotal'] ) > abs( $items_left[ $item_id ]['line_subtotal'] ) ) {
								$error->add( 'item-invalid', sprintf( _x( 'The item subtotal for %d exceeds subtotal left to cancel.', 'storeabill-core', 'woocommerce-germanized-pro' ), $item_id ) );
							}

							if ( ! sab_wp_error_has_errors( $error ) ) {
								$items_to_cancel[ $item_id ] = $item_data;

								$total_to_cancel += $item_data['total'];
							}
						} elseif ( 'fees' === $item_id ) {
							foreach ( (array) $item_data as $fee ) {
								if ( ! is_a( $fee, '\Vendidero\StoreaBill\Invoice\FeeItem' ) ) {
									continue;
								}

								$total_to_cancel  += $fee->get_total();
								$additional_fees[] = clone $fee;
							}
						}
					}

					$total_to_cancel      = Numbers::round_to_precision( $total_to_cancel );
					$total_left_to_cancel = $this->get_total_left_to_cancel();

					/**
					 * Force a full cancellation in case the amount to be cancelled
					 * equals the amount left to cancel.
					 */
					if ( $total_to_cancel >= $total_left_to_cancel ) {
						$items_to_cancel = $items_left;
					}
				}

				if ( empty( $items_to_cancel ) ) {
					$error->add( 'missing-items', _x( 'There are no items available to cancel.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				}

				if ( sab_wp_error_has_errors( $error ) ) {
					return $error;
				}

				$cancellation = sab_get_invoice( 0, 'cancellation' );

				$cancellation->set_parent_id( $this->get_id() );
				$cancellation->set_reference_id( $this->get_reference_id() );
				$cancellation->set_reference_type( $this->get_reference_type() );
				$cancellation->set_reference_number( $this->get_reference_number() );
				$cancellation->set_refund_order_id( $refund_order_id );
				$cancellation->set_address( $this->get_address() );
				$cancellation->set_shipping_address( $this->get_shipping_address( 'edit' ) );
				$cancellation->set_parent_number( $this->get_number() );
				$cancellation->set_parent_formatted_number( $this->get_formatted_number() );
				$cancellation->set_prices_include_tax( $this->get_prices_include_tax() );
				$cancellation->set_round_tax_at_subtotal( $this->get_round_tax_at_subtotal() );
				$cancellation->set_customer_id( $this->get_customer_id() );
				$cancellation->set_currency( $this->get_currency() );
				$cancellation->set_is_vat_exempt( $this->get_is_vat_exempt() );
				$cancellation->set_is_oss( $this->is_oss() );
				$cancellation->set_vat_id( $this->get_vat_id() );
				$cancellation->set_payment_method_name( $this->get_payment_method_name() );
				$cancellation->set_payment_method_title( $this->get_payment_method_title() );
				$cancellation->set_date_of_service( $this->get_date_of_service() );
				$cancellation->set_date_of_service_end( $this->get_date_of_service_end() );
				$cancellation->set_discount_notice( $this->get_discount_notice() );
				$cancellation->set_stores_vouchers_as_discount( $this->stores_vouchers_as_discount() );

				if ( $cancellation->stores_vouchers_as_discount() ) {
					$cancellation->set_voucher_total( $this->get_voucher_total() );
					$cancellation->set_voucher_tax( $this->get_voucher_tax() );
				}

				if ( $refund = $cancellation->get_refund_order() ) {
					$cancellation->set_refund_order_number( $refund->get_formatted_number() );
					$cancellation->set_reason( $refund->get_reason() );
					$cancellation->set_payment_transaction_id( $refund->get_transaction_id() );

					if ( '' === $cancellation->get_payment_transaction_id() && ( $order = $cancellation->get_order() ) ) {
						$order->get_refund_transaction_id( $refund );
					}
				}

				foreach ( $items_to_cancel as $item_id => $item_data ) {
					if ( $parent_item = $this->get_item( $item_id ) ) {
						$new_item = sab_get_document_item( 0, $parent_item->get_type() );
						$props    = array_diff_key( $parent_item->get_data(), array_flip( array( 'id', 'document_id', 'parent_id', 'taxes', 'quantity', 'total_tax', 'subtotal_tax', 'price', 'price_subtotal', 'line_subtotal', 'line_total' ) ) );

						$cancellation->add_item( $new_item );

						$new_item->set_parent_id( $item_id );
						$new_item->set_props( $props );

						$meta_data = array();

						foreach ( $parent_item->get_meta_data() as $meta ) {
							$meta_data[] = new \WC_Meta_Data(
								array(
									'id'    => 0,
									'key'   => $meta->key,
									'value' => $meta->value,
								)
							);
						}

						/**
						 * Copy meta data
						 */
						$new_item->set_meta_data( $meta_data );

						/**
						 * Calculate based on net total in case invoice prices do not include tax.
						 * This way even vouchers with a total price incl tax but a line total of zero may be cancelled correctly.
						 */
						$item_total    = $this->prices_include_tax() ? $item_data['total'] : $item_data['line_total'];
						$item_subtotal = $this->prices_include_tax() ? $item_data['subtotal'] : $item_data['line_subtotal'];

						/**
						 * Calculate the percentage of the parent item
						 * being cancelled.
						 */
						if ( 0.0 !== (float) $parent_item->get_line_total() ) {
							$total_percentage = (float) $item_total / (float) $parent_item->get_line_total();
						} else {
							$total_percentage = 1.0;
						}

						/**
						 * Calculate the percentage of the parent item
						 * being cancelled.
						 */
						if ( $parent_item->get_line_subtotal() ) {
							$subtotal_percentage = (float) $item_subtotal / (float) $parent_item->get_line_subtotal();
						} else {
							$subtotal_percentage = $total_percentage;
						}

						if ( $subtotal_percentage >= 1 ) {
							$subtotal_percentage = 1.0;
						}

						if ( is_callable( array( $new_item, 'set_quantity' ) ) ) {
							$new_item->set_quantity( $item_data['quantity'] );
						}

						if ( is_callable( array( $new_item, 'set_line_total' ) ) ) {
							$new_item->set_line_total( $item_data['line_total'] );

							if ( is_callable( array( $new_item, 'set_line_subtotal' ) ) ) {
								$new_item->set_line_subtotal( $item_data['line_subtotal'] );
							}
						}

						if ( is_a( $new_item, '\Vendidero\StoreaBill\Invoice\TaxableItem' ) ) {
							$item_total_tax    = 0.0;
							$item_subtotal_tax = 0.0;

							foreach ( $parent_item->get_taxes() as $tax_item ) {
								$new_tax_item = sab_get_document_item( 0, $tax_item->get_type() );
								$props        = array_diff_key( $tax_item->get_data(), array_flip( array( 'id', 'document_id', 'parent_id', 'taxes', 'total_net', 'subtotal_net', 'total_tax', 'subtotal_tax' ) ) );

								$new_tax_item->set_props( $props );

								$tax_total    = $tax_item->get_total_tax() * $total_percentage;
								$subtotal_tax = $tax_item->get_subtotal_tax() * $subtotal_percentage;

								$item_total_tax    += (float) $tax_total;
								$item_subtotal_tax += (float) $subtotal_tax;

								// Need to calculate taxes for adjusted totals.
								$new_tax_item->set_total_tax( $tax_total );
								$new_tax_item->set_subtotal_tax( $subtotal_tax );
								$new_tax_item->set_total_net( $tax_item->get_total_net() * $total_percentage );
								$new_tax_item->set_subtotal_net( $tax_item->get_subtotal_net() * $subtotal_percentage );

								$new_item->add_tax( $new_tax_item );
							}

							$new_item->set_total_tax( $item_total_tax );
							$new_item->set_subtotal_tax( $item_subtotal_tax );
						}
					}
				}

				foreach ( $additional_fees as $fee ) {
					$cancellation->add_item( $fee );
				}

				$meta_data = array();

				foreach ( $this->get_meta_data() as $meta ) {
					$meta_data[] = new \WC_Meta_Data(
						array(
							'id'    => 0,
							'key'   => $meta->key,
							'value' => $meta->value,
						)
					);
				}

				/**
				 * Copy meta data
				 */
				$cancellation->set_meta_data( $meta_data );

				/**
				 * Set additional props
				 */
				$cancellation->set_props( $cancellation_props );

				/**
				 * Calculate totals (without taxes)
				 */
				$cancellation->calculate_totals( false );

				$invoice_paid_total      = $this->get_total_paid();
				$cancellation_total_paid = $cancellation->get_total() > $invoice_paid_total ? $invoice_paid_total : $cancellation->get_total();

				$cancellation->set_payment_status( $cancellation_total_paid < $cancellation->get_total() ? 'partial' : 'complete' );
				$cancellation->set_total_paid( $cancellation_total_paid );

				do_action( "{$this->get_general_hook_prefix()}before_add_cancellation", $cancellation, $this, $items );

				$cancellation->save();

				/**
				 * Finalize the cancellation.
				 */
				$result = $cancellation->finalize();

				if ( true !== $result ) {
					$cancellation->delete( true );

					throw new Exception( _x( 'Error while finalizing and/or rendering the cancellation', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				}

				$this->add_cancellation( $cancellation );
				$this->maybe_set_cancelled_status();

				do_action( "{$this->get_general_hook_prefix()}_cancelled", $this, $cancellation, $items );

				return $cancellation;
			} catch ( Exception $e ) {
				$error->add( 'creating', sprintf( _x( 'There was an error creating the cancellation: %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $e->getMessage() ) );
			}
		}

		return $error;
	}
}
