<?php

namespace Vendidero\StoreaBill\WooCommerce;

use Automattic\WooCommerce\Caches\OrderCache;
use Vendidero\StoreaBill\BusinessInformation;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Document\Item;
use Vendidero\StoreaBill\Invoice\Cancellation;
use Vendidero\StoreaBill\Invoice\FeeItem;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\Invoice\TaxItem;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Tax;
use Vendidero\StoreaBill\TaxRate;
use Vendidero\StoreaBill\Utilities\CacheHelper;
use Vendidero\StoreaBill\Utilities\Numbers;
use WC_Order;
use Exception;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class Order implements \Vendidero\StoreaBill\Interfaces\Order {

	/**
	 * The actual order object
	 *
	 * @var WC_Order
	 */
	protected $order;

	protected $documents = array();

	/**
	 * @var Invoice[]
	 */
	protected $documents_to_delete = array();

	protected $unfiltered_items = null;

	/**
	 * @param WC_Order|integer $order
	 *
	 * @throws \Exception
	 */
	public function __construct( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! is_a( $order, 'WC_Order' ) ) {
			throw new Exception( esc_html_x( 'Invalid order.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$this->order = $order;
	}

	public function get_reference_type() {
		return 'woocommerce';
	}

	/**
	 * Returns the Woo WC_Order original object
	 *
	 * @return object|WC_Order
	 */
	public function get_order() {
		return $this->order;
	}

	public function get_object() {
		return $this->get_order();
	}

	public function get_hook_prefix() {
		return 'storeabill_woo_order_';
	}

	public function get_id() {
		return $this->order->get_id();
	}

	public function is_paid() {
		$is_paid = $this->order->is_paid();

		/**
		 * The invoice gateway does only support manual payment confirmation before reaching the completed status.
		 */
		if ( 'invoice' === $this->get_payment_method() && 'completed' !== $this->get_order()->get_status() ) {
			$is_paid = false;
		}

		return apply_filters( "{$this->get_hook_prefix()}is_paid", $is_paid, $this->get_order() );
	}

	public function get_payment_method_name() {
		return $this->get_payment_method();
	}

	public function get_date_paid() {
		return is_callable( array( $this->order, 'get_date_paid' ) ) ? $this->order->get_date_paid( 'edit' ) : null;
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->order->get_meta( $key, $single, $context );
	}

	public function get_payment_method() {
		return $this->order->get_payment_method();
	}

	/**
	 * @return \Vendidero\StoreaBill\Interfaces\PaymentMethod|false
	 */
	public function get_payment_method_instance() {
		$method = $this->get_payment_method();

		if ( ! empty( $method ) ) {
			try {
				return new PaymentMethod( $method );
			} catch ( Exception $e ) {
				return false;
			}
		}

		return false;
	}

	public function get_status() {
		return $this->order->get_status();
	}

	/**
	 * @param RefundOrder $refund
	 *
	 * @return string
	 */
	public function get_refund_transaction_id( $refund ) {
		return apply_filters( "{$this->get_hook_prefix()}refund_transaction_id", '', $this, $refund );
	}

	public function get_transaction_id() {
		return apply_filters( "{$this->get_hook_prefix()}transaction_id", $this->get_order()->get_transaction_id(), $this );
	}

	public function allow_round_split_taxes_at_subtotal() {
		return apply_filters( "{$this->get_hook_prefix()}allow_round_split_taxes_at_subtotal", false, $this );
	}

	public function get_taxable_country() {
		$taxable_country = 'shipping' === $this->get_taxable_address_type() ? $this->order->get_shipping_country() : $this->order->get_billing_country();

		return apply_filters( "{$this->get_hook_prefix()}taxable_country", $taxable_country, $this->get_order() );
	}

	public function get_taxable_address_type() {
		$taxable_type = $this->order->has_shipping_address() ? 'shipping' : 'billing';

		return apply_filters( "{$this->get_hook_prefix()}taxable_address_type", $taxable_type, $this->get_order() );
	}

	public function get_taxable_postcode() {
		$taxable_postcode = 'shipping' === $this->get_taxable_address_type() ? $this->order->get_shipping_postcode() : $this->order->get_billing_postcode();

		return apply_filters( "{$this->get_hook_prefix()}taxable_postcode", $taxable_postcode, $this->get_order() );
	}

	public function get_taxable_company() {
		$taxable_company = 'shipping' === $this->get_taxable_address_type() ? $this->order->get_shipping_company() : $this->order->get_billing_company();

		return apply_filters( "{$this->get_hook_prefix()}taxable_company", $taxable_company, $this->get_order() );
	}

	public function get_price_formatting_args() {
		$order_currency = $this->get_order()->get_currency();
		$base_currency  = sab_get_default_currency();

		$price_formatting_args = array(
			'decimal_separator'  => sab_get_price_decimal_separator(),
			'thousand_separator' => sab_get_price_thousand_separator(),
			'decimals'           => sab_get_price_decimals(),
			'price_format'       => sab_get_price_format(),
		);

		return apply_filters( "{$this->get_hook_prefix()}price_formatting_args", $price_formatting_args, $order_currency, $base_currency, $this->get_order() );
	}

	public function get_currency_exchange_rate() {
		$exchange_rate  = 0.0;
		$order_currency = $this->get_order()->get_currency();
		$base_currency  = sab_get_default_currency();

		return apply_filters( "{$this->get_hook_prefix()}currency_exchange_rate", $exchange_rate, $order_currency, $base_currency, $this->get_order() );
	}

	public function get_vat_id( $type = '' ) {
		$vat_id          = $this->get_order()->get_meta( '_vat_id', true );
		$shipping_vat_id = $this->get_order()->get_meta( '_shipping_vat_id', true );
		$billing_vat_id  = $this->get_order()->get_meta( '_billing_vat_id', true );

		/**
		 * In case of a specific requested vat id type, e.g. shipping
		 * do not fall back to other types.
		 */
		if ( empty( $type ) ) {
			if ( empty( $vat_id ) ) {
				$vat_id = $shipping_vat_id;
			}
			if ( empty( $vat_id ) ) {
				$vat_id = $billing_vat_id;
			}
		} elseif ( empty( $vat_id ) ) {
			$vat_id = 'billing' === $type ? $billing_vat_id : $shipping_vat_id;
		}

		return apply_filters( "{$this->get_hook_prefix()}vat_id", $vat_id, $this->get_order(), $type );
	}

	public function get_email() {
		$billing_email = $this->get_order()->get_billing_email();

		if ( empty( $billing_email ) && $this->get_order()->get_customer_id() > 0 ) {
			$customer = \Vendidero\StoreaBill\References\Customer::get_customer( $this->get_order()->get_customer_id() );

			if ( $customer ) {
				$billing_email = $customer->get_email();
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}email", $billing_email, $this );
	}

	public function get_voucher_total() {
		return (float) apply_filters( "{$this->get_hook_prefix()}voucher_total", 0.0, $this->get_order() );
	}

	public function get_voucher_tax() {
		return (float) apply_filters( "{$this->get_hook_prefix()}voucher_tax", 0.0, $this->get_order() );
	}

	public function get_discount_notice( $include_vouchers = false ) {
		$text         = '';
		$coupon_codes = array();
		$coupons      = $this->get_order()->get_items( 'coupon' );

		if ( $coupons ) {
			foreach ( $coupons as $coupon ) {
				if ( ! $include_vouchers && $this->coupon_is_voucher( $coupon ) ) {
					continue;
				}

				$coupon_codes[] = $coupon->get_code();
			}
		}

		if ( ! empty( $coupon_codes ) ) {
			$text = implode( ', ', $coupon_codes );
		}

		return apply_filters( "{$this->get_hook_prefix()}discount_notice", $text, $this->get_order() );
	}

	public function round_tax_at_subtotal() {
		$round_at_subtotal = 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );

		return apply_filters( "{$this->get_hook_prefix()}round_tax_at_subtotal", $round_at_subtotal, $this->get_order() );
	}

	/**
	 * Check whether the round tax at subtotal settings have changed.
	 * WooCommerce will then dynamically adjust taxes within existing orders too (other than
	 * StoreaBill - invoices won't change, that's why tax diffs might occur while validating orders).
	 *
	 * @return bool
	 */
	public function round_tax_at_subtotal_has_changed() {
		$current_round_tax_at_subtotal = $this->round_tax_at_subtotal();
		$invoices                      = $this->get_finalized_invoices();
		$has_changed                   = false;

		foreach ( $invoices as $invoice ) {
			if ( $invoice->round_tax_at_subtotal() !== $current_round_tax_at_subtotal ) {
				$has_changed = true;
				break;
			}
		}

		return $has_changed;
	}

	public function is_reverse_charge() {
		return $this->is_vat_exempt();
	}

	public function is_vat_exempt() {
		$is_vat_exempt = sab_string_to_bool( $this->get_order()->get_meta( 'is_vat_exempt', true ) );

		if ( ! $is_vat_exempt ) {
			$is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', $is_vat_exempt, $this->get_order() );
		}

		/**
		 * Legacy filter < 2.2.0
		 */
		$is_vat_exempt = apply_filters( "{$this->get_hook_prefix()}is_reverse_charge", $is_vat_exempt, $this->get_order() );

		return apply_filters( "{$this->get_hook_prefix()}is_vat_exempt", $is_vat_exempt, $this->get_order() );
	}

	public function is_b2b() {
		$company = $this->get_taxable_company();

		return apply_filters( "{$this->get_hook_prefix()}is_b2b", ! empty( $company ), $this->get_order() );
	}

	protected function get_tax_display_mode() {
		if ( $this->is_vat_exempt() ) {
			$tax_display_mode = 'excl';
		} else {
			$tax_display_mode = get_option( 'woocommerce_tax_display_cart' );
		}

		return apply_filters( "{$this->get_hook_prefix()}tax_display_mode", $tax_display_mode, $this->get_order() );
	}

	public function is_oss() {
		$is_oss = false;
		$taxes  = $this->get_order()->get_taxes();

		foreach ( $taxes as $tax ) {
			if ( $this->tax_is_oss( $tax ) ) {
				$is_oss = true;
				break;
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}is_oss", $is_oss, $this->get_order() );
	}

	protected function get_tax_item_by_rate_id( $rate_id ) {
		$taxes      = $this->order->get_taxes();
		$percentage = null;

		foreach ( $taxes as $tax ) {
			if ( (int) $tax->get_rate_id() === (int) $rate_id ) {
				return $tax;
			}
		}

		return false;
	}

	/**
	 * @param $rate_id
	 *
	 * @return float
	 */
	public function get_tax_rate_percent( $rate_id ) {
		$percentage = null;
		$tax_item   = $this->get_tax_item_by_rate_id( $rate_id );
		$tax_total  = 0.0;

		if ( $tax_item ) {
			if ( is_callable( array( $tax_item, 'get_rate_percent' ) ) ) {
				$percentage = (float) $tax_item->get_rate_percent();
				Package::extended_log( sprintf( 'Found specific tax percentage for rate %s within order data: %s', $rate_id, $percentage ) );
			}

			$tax_total = Numbers::round_to_precision( (float) $tax_item->get_tax_total() + (float) $tax_item->get_shipping_tax_total() );

			/**
			 * Try to determine the tax rate based on tax label, e.g. DE-MWST. 19 % DE-1
			 */
			if ( is_null( $percentage ) || '' === $percentage || ( 0.0 === $percentage && 0.0 !== $tax_total ) ) {
				$rate_code = $tax_item->get_rate_code();

				preg_match( '/([0-9][0-9]?(?:(\.|\,)[0-9]+)?)\s?%/', $rate_code, $matches );

				if ( count( $matches ) > 1 ) {
					$percentage = (float) sab_format_decimal( $matches[1] );
				}
			}
		}

		/**
		 * WC_Order_Item_Tax::get_rate_percent returns null by default.
		 * Fallback to global tax rates (DB) in case the percentage is not available within order data.
		 */
		if ( is_null( $percentage ) || '' === $percentage || ( 0.0 === $percentage && 0.0 !== $tax_total ) ) {
			$percentage = Tax::get_rate_percent_value( $rate_id );
			Package::extended_log( sprintf( 'Fallback for rate %s to global tax data: %s', $rate_id, $percentage ) );
		}

		if ( ! is_numeric( $percentage ) ) {
			$percentage = 0.0;
		}

		return (float) apply_filters( $this->get_hook_prefix() . 'tax_rate_percentage', $percentage, $rate_id, $this, $this->get_order() );
	}

	/**
	 * Decide whether a tax item is a OSS tax item or not.
	 *
	 * @param \WC_Order_Item_Tax|integer $tax
	 *
	 * @return boolean
	 */
	public function get_tax_country( $tax ) {
		$country = Countries::get_base_country();

		if ( is_numeric( $tax ) ) {
			$tax = $this->get_tax_item_by_rate_id( $tax );
		}

		if ( is_a( $tax, 'WC_Order_Item_Tax' ) ) {
			if ( ( $tax_rate_id = $tax->get_rate_id() ) && ( $tax_rate = \WC_Tax::_get_tax_rate( $tax_rate_id ) ) ) {
				$country = $tax_rate['tax_rate_country'];
			} else {
				$code = explode( '-', $tax->get_rate_code() );

				if ( ! empty( $code ) ) {
					$country = strtoupper( $code[0] );
				}
			}
		}

		return apply_filters( $this->get_hook_prefix() . 'tax_rate_country', $country, $tax, $this, $this->get_order() );
	}

	/**
	 * Decide whether a tax item is a OSS tax item or not.
	 *
	 * @param \WC_Order_Item_Tax|integer $tax
	 *
	 * @return boolean
	 */
	public function tax_is_oss( $tax ) {
		$is_oss   = false;
		$country  = $this->get_taxable_country();
		$postcode = $this->get_taxable_postcode();

		if ( is_numeric( $tax ) ) {
			$tax = $this->get_tax_item_by_rate_id( $tax );
		}

		if ( ! is_a( $tax, 'WC_Order_Item_Tax' ) ) {
			return false;
		}

		if ( Countries::base_country_supports_oss_procedure() ) {
			$tax_total = (float) $tax->get_tax_total();
			$has_tax   = 0.0 !== $tax_total;

			/**
			 * Do enable OSS for non-reverse-charge, non b2b and inner EU (not base country)
			 */
			if ( ! $this->is_vat_exempt() && $has_tax && ( ! \Vendidero\EUTaxHelper\Helper::exclude_b2b_without_vat_id_from_oss() || $this->is_b2b() ) && ( Countries::get_base_country() !== $country && Countries::is_eu_vat_country( $country, $postcode ) ) ) {
				$country = $this->get_tax_country( $tax );

				if ( ! empty( $country ) && Countries::get_base_country() !== $country ) {
					$is_oss = true;
				}
			}
		}

		return apply_filters( $this->get_hook_prefix() . 'tax_is_oss', $is_oss, $tax, $this, $this->get_order() );
	}

	/**
	 * @return boolean
	 */
	protected function has_negative_fee() {
		$has_negative_fee = false;

		foreach ( $this->get_order()->get_items( array( 'fee' ) ) as $item ) {
			if ( $item->get_total() < 0 ) {
				$has_negative_fee = true;
				break;
			}
		}

		return $has_negative_fee;
	}

	/**
	 * @return array
	 */
	protected function get_negative_fee_names() {
		$fee_names = array();

		foreach ( $this->get_order()->get_items( array( 'fee' ) ) as $item ) {
			if ( $item->get_total() < 0 ) {
				$fee_names[] = $item->get_name();
			}
		}

		return $fee_names;
	}

	/**
	 * @param Simple $invoice
	 *
	 * @return Order $order
	 */
	public function sync( &$invoice, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'items'          => array(),
				'validate_total' => true,
			)
		);

		if ( ! $invoice->is_finalized() && 'simple' === $invoice->get_invoice_type() ) {
			do_action( "{$this->get_hook_prefix()}before_sync_invoice", $this );

			/**
			 * Force reloading persisted items from DB to prevent potential caching issues.
			 */
			$invoice->get_items( '', true );

			$billing_address = array_merge(
				$this->get_order()->get_address( 'billing' ),
				array(
					'email' => $this->get_email(),
					'phone' => $this->get_order()->get_billing_phone(),
				)
			);

			// Do only replace vat id in case the vat id does not yet exist or is empty
			if ( ! array_key_exists( 'vat_id', $billing_address ) || empty( $billing_address['vat_id'] ) ) {
				$billing_address['vat_id'] = $this->get_vat_id( 'billing' );
			}

			$shipping_address = $this->get_order()->get_address( 'shipping' );

			if ( $this->get_order()->has_shipping_address() && ( ! array_key_exists( 'vat_id', $shipping_address ) || empty( $shipping_address['vat_id'] ) ) ) {
				$shipping_address['vat_id'] = $this->get_vat_id( 'shipping' );
			}

			$invoice_args = wp_parse_args(
				$args,
				array(
					'reference_id'           => $this->get_id(),
					'reference_number'       => $this->get_formatted_number(),
					'country'                => $this->get_order()->get_billing_country(),
					'address'                => apply_filters( "{$this->get_hook_prefix()}billing_address", $billing_address, $this ),
					'shipping_address'       => apply_filters( "{$this->get_hook_prefix()}shipping_address", $shipping_address, $this ),
					'prices_include_tax'     => $this->get_order()->get_prices_include_tax(),
					'round_tax_at_subtotal'  => $this->round_tax_at_subtotal(),
					'customer_id'            => $this->get_order()->get_customer_id(),
					'payment_method_name'    => $this->get_order()->get_payment_method(),
					'payment_method_title'   => $this->get_order()->get_payment_method_title(),
					'payment_transaction_id' => $this->get_transaction_id(),
					'is_vat_exempt'          => $this->is_vat_exempt(),
					'tax_display_mode'       => $this->get_tax_display_mode(),
					'is_oss'                 => $this->is_oss(),
					'vat_id'                 => $this->get_vat_id(),
					'currency'               => $this->get_order()->get_currency(),
					'base_currency'          => sab_get_default_currency(),
					'exchange_rate'          => $this->get_currency_exchange_rate(),
					'price_formatting_args'  => $this->get_price_formatting_args(),
					'date_of_service'        => $this->get_date_of_service(),
					'voucher_total'          => $this->get_voucher_total(),
					'voucher_tax'            => $this->get_voucher_tax(),
				)
			);

			/**
			 * Legacy voucher support
			 */
			if ( ! empty( $invoice_args['voucher_total'] ) ) {
				$invoice_args['stores_vouchers_as_discount'] = true;
				$invoice_args['discount_notice']             = $this->get_discount_notice( true );
			} else {
				unset( $invoice_args['voucher_total'] );
				unset( $invoice_args['voucher_tax'] );

				$invoice_args['discount_notice'] = $this->get_discount_notice();
			}

			/**
			 * Sync the created via attribute just in case it is explicitly set to prevent overrides
			 * on additional (possibly later) syncs.
			 */
			if ( ! empty( $args['created_via'] ) ) {
				$invoice_args['created_via'] = $args['created_via'];
			}

			unset( $invoice_args['items'] );

			/**
			 * Force the reference type.
			 */
			$invoice_args['reference_type'] = $this->get_reference_type();

			$rates = $this->get_tax_rates();

			Package::extended_log( 'Invoice sync args: ' . sab_print_r( $invoice_args, true ) );

			$invoice->set_props( $invoice_args );

			$available_items = $this->get_billable_items(
				array(
					'invoice_id'              => $invoice->get_id(),
					'exclude_current_invoice' => $invoice->get_id() > 0 ? true : false,
					'incl_tax'                => $invoice->prices_include_tax(),
					'voucher_total'           => $invoice->get_voucher_total(),
				)
			);

			Package::extended_log( 'Invoice ' . $invoice->get_title( false ) . ' sync for order #' . $this->get_id() . ' with ' . sab_print_r( $available_items, true ) );

			foreach ( $available_items as $order_item_id => $item_data ) {
				if ( $order_item = $this->get_order_item( $order_item_id ) ) {
					$is_new        = false;
					$document_item = $invoice->get_item_by_reference_id( $order_item_id );

					if ( ! $document_item ) {
						$document_item = sab_get_document_item( 0, $order_item->get_document_item_type() );
						$is_new        = true;
					}

					$props = array(
						'quantity'      => absint( $item_data['max_quantity'] ),
						'line_total'    => (float) $item_data['max_total'],
						'line_subtotal' => (float) $item_data['max_subtotal'],
					);

					if ( ! empty( $args['items'] ) ) {
						if ( isset( $args['items'][ $order_item_id ] ) ) {
							if ( is_numeric( $args['items'][ $order_item_id ] ) ) {
								$arg_item_data = array(
									'quantity'      => absint( $args['items'][ $order_item_id ] ),
									'line_total'    => '',
									'line_subtotal' => '',
								);
							} else {
								$arg_item_data = wp_parse_args(
									$args['items'][ $order_item_id ],
									array(
										'quantity'      => '',
										'line_total'    => '',
										'line_subtotal' => '',
									)
								);
							}

							/**
							 * Parse item data. Allow manually adjusting item quantity, total and subtotal to be billed.
							 */
							$new_quantity = absint( ! empty( $arg_item_data['quantity'] ) ? $arg_item_data['quantity'] : $props['quantity'] );
							$new_total    = ! empty( $arg_item_data['line_total'] ) ? (float) $arg_item_data['line_total'] : 0.0;
							$new_subtotal = ! empty( $arg_item_data['line_subtotal'] ) ? (float) $arg_item_data['line_subtotal'] : 0.0;

							if ( $new_quantity < $props['quantity'] ) {
								$props['quantity'] = $new_quantity;
							}

							if ( empty( $new_total ) && is_callable( array( $order_item, 'get_total' ) ) ) {
								$line_total             = $this->get_line_total( $order_item->get_order_item(), $invoice->prices_include_tax(), false );
								$props['line_total']    = ( $line_total / (float) $order_item->get_quantity() ) * $props['quantity'];
								$props['line_subtotal'] = $props['line_total'];

								if ( empty( $new_subtotal ) && is_callable( array( $order_item, 'get_subtotal' ) ) ) {
									$line_subtotal          = $this->get_line_subtotal( $order_item->get_order_item(), $invoice->prices_include_tax(), false );
									$props['line_subtotal'] = ( $line_subtotal / (float) $order_item->get_quantity() ) * $props['quantity'];
								}
							} elseif ( $new_total < $props['line_total'] ) {
								$props['line_total'] = $new_total;

								if ( ! empty( $new_subtotal ) && $new_subtotal < $props['line_subtotal'] ) {
									$props['line_subtotal'] = $new_subtotal;
								} else {
									$props['line_subtotal'] = $new_total;
								}
							}
						} else {
							continue;
						}
					}

					$belongs_to_invoice = true;

					if ( $is_new && ! $invoice->get_item_by_reference_id( $order_item_id ) ) {
						$belongs_to_invoice = apply_filters( "{$this->get_hook_prefix()}item_belongs_to_invoice", true, $order_item, $props, $invoice, $this );
					}

					if ( $belongs_to_invoice ) {
						$document_item->set_document( $invoice );

						$sync_data = array_replace(
							$props,
							array(
								'order_tax_rates'    => $rates,
								'prices_include_tax' => $this->order_item_type_includes_tax( $order_item->get_order_item(), $invoice->prices_include_tax() ),
							)
						);

						if ( in_array( $document_item->get_item_type(), $invoice->get_item_types_additional_costs(), true ) ) {
							$sync_data['round_tax_at_subtotal'] = $this->order_item_type_round_tax_at_subtotal( $order_item->get_order_item(), $invoice->round_tax_at_subtotal() );
						}

						$order_item->sync( $document_item, $sync_data );

						do_action( "{$this->get_hook_prefix()}synced_invoice_item", $document_item, $order_item, $this );

						if ( $is_new && ! $invoice->get_item_by_reference_id( $order_item_id ) ) {
							$invoice->add_item( $document_item );
						}
					}
				}
			}

			$ref_item_map = array();

			/**
			 * Remove items that do not exist in parent order any longer. Remove potential duplicates.
			 */
			foreach ( $invoice->get_items() as $item ) {
				if ( ! in_array( $item->get_item_type(), $invoice->get_item_types_for_totals(), true ) ) {
					continue;
				}

				if ( in_array( $item->get_reference_id(), $ref_item_map, true ) ) {
					$invoice->remove_item( $item->get_key() );
				} elseif ( ! array_key_exists( $item->get_reference_id(), $available_items ) ) {
					$invoice->remove_item( $item->get_key() );
				} elseif ( ! $order_item = $this->order->get_item( $item->get_reference_id() ) ) {
					$invoice->remove_item( $item->get_key() );
				} else {
					if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Summable' ) ) {
						/**
						 * Remove the item in case the item total equals zero but the parent order item is not a free item.
						 * Exclude legacy voucher discount items.
						 */
						if ( 0.0 === $item->get_total() && ! $this->is_free_item( $order_item ) && ! isset( $invoice_args['stores_vouchers_as_discount'] ) ) {
							$invoice->remove_item( $item->get_key() );
						}
					}

					$ref_item_map[] = $item->get_reference_id();
				}
			}

			$invoice->calculate_totals();

			if ( $this->is_paid() ) {
				$invoice->set_payment_status( 'complete' );
			}

			do_action( "{$this->get_hook_prefix()}synced_invoice", $invoice, $this );

			if ( $invoice->get_id() <= 0 ) {
				$this->add_document( $invoice );
			}

			do_action( "{$this->get_hook_prefix()}after_sync_invoice", $this );
		}

		return $this;
	}

	/**
	 * @return TaxRate[]
	 */
	protected function get_tax_rates() {
		$taxes = $this->get_order()->get_taxes();
		$rates = array();

		foreach ( $taxes as $tax ) {
			$percentage = $this->get_tax_rate_percent( $tax->get_rate_id() );
			$merge_key  = Tax::get_tax_rate_merge_key(
				array(
					'percent'     => $percentage,
					'is_compound' => $tax->is_compound(),
					'is_oss'      => $this->tax_is_oss( $tax ),
				)
			);

			if ( ! array_key_exists( $merge_key, $rates ) ) {
				$tax_data = array(
					'percent'       => $percentage,
					'is_compound'   => $tax->is_compound(),
					'is_oss'        => $this->tax_is_oss( $tax ),
					'country'       => $this->get_tax_country( $tax ),
					'reference_ids' => array( $tax->get_rate_id() ),
				);

				$rates[ $merge_key ] = new TaxRate( $tax_data );
			} else {
				$ref_ids = $rates[ $merge_key ]->get_reference_ids();

				if ( ! in_array( $tax->get_rate_id(), $ref_ids ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$ref_ids[] = $tax->get_rate_id();

					$rates[ $merge_key ]->set_reference_ids( $ref_ids );
				}
			}
		}

		return $rates;
	}

	/**
	 * @return null|\WC_DateTime
	 */
	public function get_date_of_service() {
		$date_of_service = $this->get_order()->get_date_created();

		if ( $date_completed = $this->get_order()->get_date_completed() ) {
			$date_of_service = $date_completed;
		} elseif ( $date_paid = $this->get_order()->get_date_paid() ) {
			$date_of_service = $date_paid;
		}

		return apply_filters( $this->get_hook_prefix() . 'date_of_service', $date_of_service, $this, $this->get_order() );
	}

	/**
	 * This method cancels all cancelable invoices
	 * and removes unfixed invoices.
	 *
	 * @param string $reason
	 * @param array $cancellation_props
	 */
	public function cancel( $reason = '', $cancellation_props = array() ) {
		foreach ( $this->get_invoices() as $invoice ) {
			if ( ! $invoice->is_finalized() ) {
				$this->delete_document( $invoice->get_id() );
			} else {
				$new_cancellation = $invoice->cancel( array(), 0, $cancellation_props );

				if ( ! is_wp_error( $new_cancellation ) ) {
					$this->add_document( $new_cancellation );

					$note = sprintf( _x( 'Cancelled invoice %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_formatted_number() );

					if ( ! empty( $reason ) ) {
						$note = sprintf( _x( 'Cancelled invoice %1$s (%2$s).', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_formatted_number(), $reason );
					}

					$this->get_order()->add_order_note( $note );
				}
			}
		}

		$this->save();
	}

	/**
	 * Sync invoices with the current order.
	 *
	 * @param bool $add_new Whether to automatically add a new invoice in case billing is necessary or not.
	 *
	 * @return \WP_Error|boolean
	 */
	public function sync_order( $add_new = true, $args = array() ) {
		CacheHelper::prevent_caching( 'sync' );
		$this->remove_cache();

		$result = new \WP_Error();

		Package::extended_log( 'Starting sync for order #' . $this->get_id() );

		/**
		 * Make sure no deferred syncs are left in queue when (manually) syncing.
		 */
		Automation::cancel_deferred_sync( array( 'order_id' => $this->get_id() ) );

		$this->maybe_cancel();

		if ( $this->needs_billing() || $this->get_last_editable_invoice() ) {
			foreach ( $this->get_editable_invoices() as $invoice ) {
				$this->sync( $invoice, $args );
			}

			if ( $add_new ) {
				while ( $this->needs_billing() ) {
					$invoice = sab_get_invoice( 0 );
					$this->sync( $invoice, $args );

					/**
					 * Add an emergency break to prevent infinite loops
					 */
					if ( count( $invoice->get_items() ) === 0 ) {
						break;
					}
				}
			}

			if ( ! $this->needs_billing() && $this->get_last_editable_invoice() ) {
				$this->book_order_divergences( $args );
			}
		}

		$save_result = $this->save( false );

		if ( is_wp_error( $save_result ) ) {
			foreach ( $save_result->get_error_codes() as $code ) {
				if ( ! $result->get_error_message( $code ) ) {
					$result->add( $code, $save_result->get_error_message( $code ) );
				}
			}
		}

		$has_cancelled = $this->maybe_cancel();
		$save_result   = $this->save( false );

		if ( is_wp_error( $save_result ) ) {
			foreach ( $save_result->get_error_codes() as $code ) {
				if ( ! $result->get_error_message( $code ) ) {
					$result->add( $code, $save_result->get_error_message( $code ) );
				}
			}
		}

		if ( $has_cancelled ) {
			if ( empty( $this->get_editable_invoices() ) ) {
				$result->add( 'cancelled-order', _x( 'An invoice could not be created due to changes to or inconsistent order data. Please review the corresponding order.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			} else {
				$result->add( 'cancelled-order', _x( 'The invoices needed a (partial) cancellation due to changes to or inconsistent order data. Please review the corresponding order.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			}
		}

		if ( sab_wp_error_has_errors( $result ) ) {
			return $result;
		} else {
			return true;
		}
	}

	/**
	 * @param Invoice $invoice
	 */
	protected function book_order_divergences( $args = array() ) {
		$args             = wp_parse_args( $args, array( 'validate_total' => true ) );
		$last_invoice     = $this->get_last_editable_invoice();
		$total_billed     = $last_invoice->prices_include_tax() ? $this->get_total_billed() : $this->get_net_total_billed();
		$total_tax_billed = $this->get_total_tax_billed();
		$order_total_tax  = $this->get_order_total_tax_to_bill();
		$order_total      = $this->get_order_total_to_bill();
		$has_discounts    = $last_invoice->get_discount_total() > 0;
		$invoice_taxes    = array();

		/**
		 * If prices do not include tax allow order total to go negative as the order
		 * total may be zero but the tax total may be > zero which results in a negative total.
		 * The same is true for $this->get_net_total_billed().
		 */
		if ( ! $last_invoice->prices_include_tax() ) {
			$order_total -= $order_total_tax;
			$order_total  = Numbers::round_to_precision( $order_total );
		} else {
			$order_total = Numbers::round_to_precision( max( 0, $order_total ) );
		}

		$order_plain_total = Numbers::round_to_precision( $this->get_order_total_to_bill() );
		$total_billed      = Numbers::round_to_precision( $total_billed );
		$total_tax_billed  = Numbers::round_to_precision( $total_tax_billed );

		foreach ( $this->get_editable_invoices() as $editable_invoice ) {
			foreach ( $editable_invoice->get_tax_totals() as $merge_key => $tax_total ) {
				if ( ! isset( $invoice_tax_totals[ $merge_key ] ) ) {
					$invoice_taxes[ $merge_key ] = clone $tax_total;
				} else {
					foreach ( $tax_total->get_taxes() as $tax ) {
						$invoice_taxes[ $merge_key ]->add_tax( $tax );
					}
				}
			}
		}

		/**
		 * In case the order total does not match the invoice total there seems to be some plugin
		 * manipulating order total calculation (e.g. a voucher plugin). In this case we will add a fee (or discount)
		 * to the invoice dynamically.
		 *
		 * Check net total in case the prices exclude tax to make sure no unnecessary fees are added in case of
		 * inconsistencies.
		 *
		 * Do only prove full consistency for full syncs (e.g. without specific items as arguments)
		 */
		if ( $args['validate_total'] && ( ( 0.0 === $order_plain_total && 0.0 === $order_total ) || 0.0 !== $order_total ) && sab_add_number_precision( $total_billed ) !== sab_add_number_precision( $order_total ) ) {
			$total_diff   = $order_total - $total_billed;
			$has_tax_diff = 0.0 !== $order_total_tax && $total_tax_billed !== $order_total_tax;

			if ( sab_add_number_precision( $total_diff ) > 0 ) {
				$fee = new FeeItem();
				$fee->set_name( _x( 'Fee', 'storeabill-core', 'woocommerce-germanized-pro' ) );
				$fee->set_line_total( $total_diff );
				$fee->set_line_subtotal( $total_diff );
				$fee->set_is_taxable( $has_tax_diff ? true : false );
				$fee->set_total_tax( 0 );
				$fee->set_prices_include_tax( $this->order_item_type_includes_tax( 'fee', $last_invoice->prices_include_tax() ) );

				/**
				 * Detected a tax diff. Lets check order total taxes to
				 * determine tax diff per rate.
				 */
				if ( $has_tax_diff ) {
					$tax_rate_diffs = array();

					foreach ( $this->get_order()->get_tax_totals() as $tax_total_obj ) {
						$percentage = $this->get_tax_rate_percent( $tax_total_obj->rate_id );
						$merge_key  = Tax::get_tax_rate_merge_key(
							array(
								'percent'     => $percentage,
								'is_compound' => $tax_total_obj->is_compound,
								'is_oss'      => $this->tax_is_oss( $tax_total_obj->rate_id ),
							)
						);

						$order_tax_rate_total = (float) $tax_total_obj->amount - (float) $this->get_order()->get_total_tax_refunded_by_rate_id( $tax_total_obj->rate_id ) - $this->get_total_tax_billed_by_reference_id( $tax_total_obj->rate_id, true );

						if ( $order_tax_rate_total > 0 ) {
							if ( array_key_exists( $merge_key, $invoice_taxes ) ) {
								$invoice_tax_obj = $invoice_taxes[ $merge_key ];
								$tax_rate_diff   = $order_tax_rate_total - $invoice_tax_obj->get_total_tax( false );

								if ( 0.0 !== Numbers::round_to_precision( $tax_rate_diff ) ) {
									if ( ! array_key_exists( $merge_key, $tax_rate_diffs ) ) {
										$tax_rate_diffs[ $merge_key ] = 0.0;
									}

									$tax_rate_diffs[ $merge_key ] += $tax_rate_diff;
								}
							}
						}
					}

					$total_fee_tax = 0.0;
					$round_taxes   = apply_filters( 'storeabill_round_tax_at_subtotal_split_tax_calculation', $fee->round_tax_at_subtotal(), $fee );

					foreach ( $tax_rate_diffs as $merge_key => $tax_diff ) {
						$item = new TaxItem();
						$item->set_round_tax_at_subtotal( $round_taxes );
						$item->set_tax_rate( $invoice_taxes[ $merge_key ]->get_tax_rate() );
						$item->set_total_tax( $tax_diff );
						$item->set_subtotal_tax( $tax_diff );
						$item->set_tax_type( 'fee' );

						$fee->add_tax( $item );

						$item->set_total_net( $this->order_item_type_includes_tax( 'fee', $last_invoice->prices_include_tax() ) ? ( $total_diff - $item->get_total_tax() ) : $total_diff );
						$item->set_subtotal_net( $this->order_item_type_includes_tax( 'fee', $last_invoice->prices_include_tax() ) ? ( $total_diff - $item->get_subtotal_tax() ) : $total_diff );

						$total_fee_tax += $item->get_total_tax();
					}

					$fee->set_total_tax( $total_fee_tax );
					$fee->set_subtotal_tax( $total_fee_tax );
				}

				$last_invoice->add_item( $fee );
				$last_invoice->calculate_totals( false );

				do_action( "{$this->get_hook_prefix()}added_order_total_diff_as_fee", $fee, $last_invoice, $this );

			} elseif ( sab_add_number_precision( $total_diff ) < 0 ) {
				$args = array(
					'is_voucher' => false,
					'item_types' => array( 'product' ),
				);

				$product_total = $last_invoice->get_product_total();
				$discount      = abs( $total_diff );
				$has_taxes     = $last_invoice->get_total_tax() > 0;

				/**
				 * Make sure to skip the zero order total check here to prevent
				 * treating negative fees with taxes (and order total = 0) as vouchers
				 */
				$has_tax_diff          = $total_tax_billed !== (float) $order_total_tax;
				$discount_notice_parts = array();

				if ( $this->has_negative_fee() ) {
					foreach ( $this->get_negative_fee_names() as $fee_name ) {
						$discount_notice_parts[] = $fee_name;
					}
				}

				/**
				 * Check if a coupon exists which matches the discount amount, e.g. PDF vouchers.
				 */
				$coupons = $this->get_order()->get_items( 'coupon' );

				if ( $coupons ) {
					foreach ( $coupons as $coupon ) {
						if ( $this->coupon_is_voucher( $coupon ) ) {
							$discount_notice_parts[] = $coupon->get_name();
						} else {
							$coupon_discount = Numbers::round_to_precision( (float) $coupon->get_discount() );

							if ( $discount === $coupon_discount ) {
								$discount_notice_parts[] = $coupon->get_name();
								break;
							}
						}
					}
				}

				$discount_notice = implode( ', ', $discount_notice_parts );

				$args['code'] = apply_filters( "{$this->get_hook_prefix()}forced_discount_notice", $discount_notice, $discount, $last_invoice, $this );

				if ( $discount > $product_total ) {
					$args['item_types'] = array( 'product', 'shipping', 'fee' );
				}

				if ( ! $has_tax_diff && $has_taxes ) {
					$args['is_voucher'] = true;
				}

				$last_invoice->apply_discount( $discount, 'fixed', $args );

				do_action( "{$this->get_hook_prefix()}added_order_total_diff_as_discount", $last_invoice, $this, $discount, $args );
			}
		}
	}

	public function needs_finalization() {
		if ( $unfixed = $this->get_last_editable_invoice() ) {
			return true;
		}

		return false;
	}

	public function needs_sync() {
		return ( $this->get_last_editable_invoice() || $this->needs_billing() || $this->needs_cancelling() );
	}

	public function has_draft() {
		return $this->get_last_editable_invoice() ? true : false;
	}

	protected function remove_cache() {
		if ( class_exists( '\Automattic\WooCommerce\Caches\OrderCache' ) ) {
			if ( $order_cache = wc_get_container()->get( OrderCache::class ) ) {
				if ( $this->get_id() > 0 ) {
					$order_cache->remove( $this->get_id() );
					$this->order = wc_get_order( $this->get_id() );
				}
			}
		}

		$this->documents           = array();
		$this->documents_to_delete = array();
		$this->unfiltered_items    = null;
	}

	/**
	 * @return bool|\WP_Error
	 */
	public function finalize( $defer_render = false ) {
		CacheHelper::prevent_caching( 'sync' );
		$this->remove_cache();

		Package::extended_log( 'Finalizing order #' . $this->get_id() );

		/**
		 * Make sure no deferred syncs are left in queue when finalizing.
		 */
		Automation::cancel_deferred_sync( array( 'order_id' => $this->get_id() ) );

		$errors = new \WP_Error();

		foreach ( $this->get_documents() as $document ) {
			if ( ! $document->is_finalized() ) {
				/**
				 * Re-sync document before finalizing.
				 */
				$this->sync( $document );
			}
		}

		if ( ! $this->needs_billing() && $this->get_last_editable_invoice() ) {
			$this->book_order_divergences();
		}

		$this->maybe_cancel();

		foreach ( $this->get_documents() as $document ) {
			if ( ! $document->is_finalized() ) {
				if ( $document->get_id() <= 0 ) {
					$result = $document->save();

					if ( is_wp_error( $result ) ) {
						$errors->add( $result->get_error_code(), $result->get_error_message() );
						continue;
					}
				}

				$result = $document->finalize( $defer_render );

				if ( is_wp_error( $result ) ) {
					$errors->add( $result->get_error_code(), $result->get_error_message() );
				}
			}
		}

		$this->save();

		if ( sab_wp_error_has_errors( $errors ) ) {
			return $errors;
		} else {
			return true;
		}
	}

	public function get_formatted_number() {
		return $this->get_order()->get_order_number();
	}

	public function needs_auto_validation() {
		$status           = Helper::clean_order_status( $this->get_status() );
		$date_of_service  = $this->get_date_of_service();
		$now              = new \DateTime();
		$needs_validation = true;
		$needs_billing    = $this->needs_billing();

		if ( ! $needs_billing && in_array( $status, array( 'completed', 'cancelled', 'refunded', 'failed' ), true ) ) {
			$date_diff = $now->diff( $date_of_service );

			if ( $date_diff->days >= 180 ) {
				$needs_validation = false;
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}needs_auto_validation", $needs_validation, $this );
	}

	/**
	 * Makes sure that the order is not containing a higher
	 * invoice amount as order total. Cancel invoices or removes items from unfixed invoices if necessary.
	 *
	 * @param array $cancellation_props Props passed to the cancellation in case a cancellation is being created.
	 */
	public function validate( $cancellation_props = array() ) {
		$this->maybe_cancel( array(), $cancellation_props );
		$this->save();

		$this->refresh();
	}

	public function get_invoice_payment_status() {
		$total_unpaid = $this->get_invoice_total_unpaid();
		$status       = 'pending';

		if ( $total_unpaid > 0 ) {
			$status = 'pending';
		} else {
			$status = 'complete';

			if ( in_array( $this->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) ) {
				$status = 'cancelled';
			}
		}

		return $status;
	}

	public function refresh() {
		$this->documents           = array();
		$this->documents_to_delete = array();

		$this->load_documents();
	}

	public function get_invoice_total_unpaid() {
		$invoices = $this->get_invoices();
		$total    = 0.0;

		if ( ! empty( $invoices ) ) {
			foreach ( $invoices as $invoice ) {
				$total += $invoice->get_total();
				$total -= $invoice->get_total_paid();
			}

			foreach ( $this->get_cancellations() as $cancellation ) {
				$total -= $cancellation->get_total();
				$total += $cancellation->get_total_paid();
			}

			$total = Numbers::round_to_precision( $total );
		} else {
			$total = 0.0;
		}

		if ( $total < 0 ) {
			$total = 0.0;
		}

		return (float) apply_filters( "{$this->get_hook_prefix()}invoice_unpaid_total", $total, $this );
	}

	public function get_order_item( $item_id ) {
		if ( $order_item = $this->get_order()->get_item( $item_id ) ) {
			return Helper::get_order_item( $order_item, $this );
		}

		return false;
	}

	protected function parse_document_types( $types = '' ) {
		$types = array_filter( array_map( 'sab_maybe_prefix_invoice_type', ( empty( $types ) ? sab_get_document_types( '', 'accounting' ) : (array) $types ) ) );

		return $types;
	}

	protected function parse_document_type( $type = '' ) {
		return sab_maybe_prefix_invoice_type( $type );
	}

	protected function load_documents( $type = '' ) {
		$doc_types = sab_get_document_types( '', 'accounting' );
		$types     = $this->parse_document_types( $type );

		foreach ( $types as $type ) {
			if ( ! in_array( $type, $doc_types, true ) ) {
				continue;
			}

			if ( ! isset( $this->documents[ $type ] ) ) {
				$this->documents[ $type ] = array();
				$this->documents[ $type ] = sab_get_invoices_by_order( $this->get_order()->get_id(), $type, $this->get_reference_type() );
			}

			$this->documents[ $type ] = array_filter( $this->documents[ $type ] );
		}
	}

	/**
	 * @return Simple[]
	 */
	public function get_invoices() {
		return $this->get_documents( 'invoice' );
	}

	/**
	 * @return Simple[]
	 */
	public function get_finalized_invoices() {
		return $this->get_finalized_documents( 'invoice' );
	}

	/**
	 * @return false|Simple
	 */
	public function get_latest_finalized_invoice() {
		$latest   = false;
		$invoices = $this->get_finalized_invoices();

		if ( ! empty( $invoices ) ) {
			$latest = array_values( array_slice( $invoices, -1 ) )[0];
		}

		return $latest;
	}

	/**
	 * @return Simple[]
	 */
	protected function get_editable_invoices() {
		$invoices = array();

		foreach ( $this->get_invoices() as $invoice ) {
			if ( ! $invoice->is_finalized() ) {
				$invoices[] = $invoice;
			}
		}

		return $invoices;
	}

	/**
	 * @return Simple|boolean
	 */
	protected function get_last_editable_invoice() {
		$invoices = $this->get_editable_invoices();

		return ( ! empty( $invoices ) ? $invoices[ count( $invoices ) - 1 ] : false );
	}

	/**
	 * @return Cancellation[]
	 */
	public function get_cancellations() {
		return $this->get_documents( 'invoice_cancellation' );
	}

	/**
	 * @return Cancellation[]
	 */
	public function get_finalized_cancellations() {
		return $this->get_finalized_documents( 'invoice_cancellation' );
	}

	/**
	 * @return Simple[]|Cancellation[]|Invoice[]
	 */
	public function get_finalized_documents( $type = '' ) {
		$documents = array();

		foreach ( $this->get_documents( $type ) as $document ) {
			if ( $document->is_finalized() ) {
				$documents[] = $document;
			}
		}

		return $documents;
	}

	/**
	 * @return Invoice[]|Cancellation[]|Simple[] Invoices
	 */
	public function get_documents( $type = '' ) {
		$type = $this->parse_document_type( $type );

		$this->load_documents( $type );

		$documents = array();

		if ( ! empty( $type ) && array_key_exists( $type, $this->documents ) ) {
			$documents = (array) $this->documents[ $type ];
		} else {
			foreach ( $this->documents as $type => $type_documents ) {
				$documents = array_merge( $type_documents, $documents );
			}
		}

		return array_filter( $documents );
	}

	/**
	 * @param Invoice $invoice
	 */
	public function add_document( $invoice, $replace = false ) {
		$this->load_documents();

		if ( ! array_key_exists( $invoice->get_type(), $this->documents ) ) {
			return false;
		}

		$exists = false;

		if ( $invoice->get_id() > 0 ) {
			foreach ( $this->documents[ $invoice->get_type() ] as $key => $document ) {
				if ( $document->get_id() === $invoice->get_id() ) {
					if ( $replace ) {
						unset( $this->documents[ $invoice->get_type() ][ $key ] );
					}

					$exists = true;
					break;
				}
			}
		}

		if ( ! $exists || $replace ) {
			$this->documents[ $invoice->get_type() ][] = $invoice;
		}

		return true;
	}

	public function delete_document( $invoice_id ) {
		$result = false;
		$this->load_documents();

		foreach ( $this->documents as $invoice_type => $invoices ) {
			foreach ( $invoices as $key => $invoice ) {
				if ( $invoice->get_id() === (int) $invoice_id && ! $invoice->is_finalized() ) {
					$this->documents_to_delete[] = $invoice;

					unset( $this->documents[ $invoice_type ][ $key ] );
					$result = true;
					break 2;
				}
			}
		}

		return $result;
	}

	/**
	 * @param $invoice_id
	 *
	 * @return bool|Invoice
	 */
	public function get_document( $invoice_id ) {
		$invoices = $this->get_documents();

		foreach ( $invoices as $invoice ) {

			if ( $invoice->get_id() === (int) $invoice_id ) {
				return $invoice;
			}
		}

		return false;
	}

	protected function get_order_item_refunded_quantity( $order_item ) {
		$refunded_qty = abs( $this->get_order()->get_qty_refunded_for_item( $order_item->get_id() ) );

		return $refunded_qty;
	}

	protected function get_order_item_tax_refunded( $item_id, $item_type = 'line_item' ) {
		$total = 0.0;

		foreach ( $this->get_order()->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $item_type ) as $refunded_item ) {
				$refunded_item_id = (int) $refunded_item->get_meta( '_refunded_item_id' );

				if ( $refunded_item_id === $item_id ) {
					$total += $refunded_item->get_total_tax();
				}
			}
		}

		return floatval( $total ) * -1;
	}

	/**
	 * Overrides the WC_Order::get_total_refunded_for_item() method as this method enforces rounding to price decimals.
	 *
	 * @param WC_Order_Item $order_item
	 * @param bool $inc_tax
	 *
	 * @see WC_Order::get_total_refunded_for_item()
	 *
	 * @return float
	 */
	protected function get_order_item_refunded_total( $order_item, $inc_tax = true, $round = true ) {
		$refunded_total = 0;

		foreach ( $this->get_order()->get_refunds() as $refund ) {
			foreach ( $refund->get_items( $order_item->get_type() ) as $refunded_item ) {
				if ( absint( $refunded_item->get_meta( '_refunded_item_id' ) ) === $order_item->get_id() ) {
					$refunded_total += (float) $refunded_item->get_total();
				}
			}
		}

		$refunded_total = floatval( $refunded_total ) * -1;
		$refunded_tax   = $this->get_order_item_tax_refunded( $order_item->get_id(), $order_item->get_type() );
		$refunded_total = $inc_tax ? (float) ( $refunded_total + $refunded_tax ) : (float) $refunded_total;

		if ( $round || apply_filters( "{$this->get_hook_prefix()}force_round_order_item_refunded_total", false, $order_item, $this ) ) {
			$refunded_total = Numbers::round( $refunded_total, sab_get_price_decimals() );
		}

		return $refunded_total;
	}

	/**
	 * @param WC_Order_Item $order_item
	 * @param bool $inc_tax
	 *
	 * @return float
	 */
	protected function get_order_item_refunded_tax_total( $order_item ) {
		$refunded_tax = $this->get_order_item_tax_refunded( $order_item->get_id(), $order_item->get_type() );

		return (float) $refunded_tax;
	}

	/**
	 * @param WC_Order_Item $order_item
	 */
	public function get_billable_item_quantity( $order_item, $args = array() ) {
		$quantity_left = 0;
		$args          = wp_parse_args(
			$args,
			array(
				'invoice_id'              => 0,
				'exclude_current_invoice' => false,
			)
		);

		if ( is_numeric( $order_item ) ) {
			$order_item = $this->get_order()->get_item( $order_item );
		}

		if ( $order_item ) {
			$quantity_left = $order_item->get_quantity() - $this->get_order_item_refunded_quantity( $order_item );

			foreach ( $this->get_invoices() as $invoice ) {

				if ( $args['exclude_current_invoice'] && $args['invoice_id'] > 0 && ( $invoice->get_id() === (int) $args['invoice_id'] ) ) {
					continue;
				}

				if ( $item = $invoice->get_item_by_reference_id( $order_item->get_id() ) ) {
					/**
					 * Substract quantity already cancelled to allow
					 * cancelled items to be added again.
					 */
					$item_quantity_cancelled = $invoice->get_item_quantity_cancelled( $item->get_id() );
					$item_quantity           = $item->get_quantity();

					if ( $item_quantity_cancelled > $item_quantity ) {
						$quantity_left -= $item_quantity;
					} else {
						$quantity_left -= ( $item_quantity - $item_quantity_cancelled );
					}
				}
			}
		}

		if ( $quantity_left < 0 ) {
			$quantity_left = 0;
		}

		/**
		 * Filter to adjust the billable item quantity left for a certain order item.
		 *
		 * @param integer       $quantity_left The quantity left for shipment.
		 * @param WC_Order_Item $order_item The order item object.
		 * @param Order      $this The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/Shipments
		 */
		return apply_filters( "{$this->get_hook_prefix()}billable_order_item_quantity", $quantity_left, $order_item, $this );
	}

	/**
	 * Decides whether the order item type includes tax or not.
	 *
	 * @param WC_Order_Item|string $order_item
	 */
	protected function order_item_type_includes_tax( $order_item_type, $default_includes_tax = true ) {
		$includes_tax    = $default_includes_tax;
		$order_item_type = is_callable( array( $order_item_type, 'get_type' ) ) ? $order_item_type->get_type() : $order_item_type;

		return apply_filters( "{$this->get_hook_prefix()}item_type_includes_tax", $includes_tax, $order_item_type, $this );
	}

	/**
	 * Decides whether the order item type rounds taxes at subtotal or not.
	 *
	 * @param WC_Order_Item|string $order_item
	 */
	protected function order_item_type_round_tax_at_subtotal( $order_item_type, $default_round_at_subtotal = true ) {
		$round_at_subtotal = $default_round_at_subtotal;
		$order_item_type   = is_callable( array( $order_item_type, 'get_type' ) ) ? $order_item_type->get_type() : $order_item_type;

		return apply_filters( "{$this->get_hook_prefix()}item_type_round_tax_at_subtotal", $round_at_subtotal, $order_item_type, $this );
	}

	/**
	 * Get line subtotal - this is the cost before discount.
	 *
	 * Patches a Woo bug which leads to fatal errors when using PHP 8 and
	 * legacy item data (empty total strings).
	 *
	 * @param object $item Item to get total from.
	 * @param bool   $inc_tax (default: false).
	 * @param bool   $round (default: true).
	 * @return float
	 *
	 * @see WC_Order::get_line_total()
	 */
	public function get_line_total( $item, $inc_tax = false, $round = true ) {
		$total = 0.0;

		if ( is_callable( array( $item, 'get_total' ) ) && is_callable( array( $item, 'get_total_tax' ) ) ) {
			/**
			 * Legacy order data may contain empty strings as total - prevent PHP 8 errors
			 * from being thrown when calling the Woo core method.
			 *
			 * @see WC_Order::get_line_total()
			 */
			$item_total     = floatval( $item->get_total() );
			$item_total_tax = floatval( $item->get_total_tax() );

			// Check if we need to add line tax to the line total.
			$total = $inc_tax ? $item_total + $item_total_tax : $item_total;

			// Check if we need to round.
			$total = $round ? Numbers::round( $total, sab_get_price_decimals() ) : $total;
		}

		return apply_filters( 'woocommerce_order_amount_line_total', $total, $this->get_order(), $item, $inc_tax, $round );
	}

	/**
	 * Get line subtotal - this is the cost before discount.
	 *
	 * Patches a Woo bug which leads to fatal errors when using PHP 8 and
	 * legacy item data (empty total strings).
	 *
	 * @param object $item Item to get total from.
	 * @param bool   $inc_tax (default: false).
	 * @param bool   $round (default: true).
	 * @return float
	 *
	 * @see WC_Order::get_line_subtotal()
	 */
	public function get_line_subtotal( $item, $inc_tax = false, $round = true ) {
		$subtotal = 0.0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) && is_callable( array( $item, 'get_subtotal_tax' ) ) ) {
			if ( $inc_tax ) {
				$subtotal = floatval( $item->get_subtotal() ) + floatval( $item->get_subtotal_tax() );
			} else {
				$subtotal = floatval( $item->get_subtotal() );
			}

			$subtotal = $round ? Numbers::round( $subtotal, sab_get_price_decimals() ) : $subtotal;
		} elseif ( is_callable( array( $item, 'get_total' ) ) ) {
			$subtotal = $this->get_line_total( $item, $inc_tax, $round );
		}

		return apply_filters( 'woocommerce_order_amount_line_subtotal', $subtotal, $this->get_order(), $item, $inc_tax, $round );
	}

	/**
	 * Calculate item cost - useful for gateways.
	 *
	 * Patches a Woo bug which leads to fatal errors when using PHP 8 and
	 * legacy item data (empty total strings).
	 *
	 * @param object $item Item to get total from.
	 * @param bool   $inc_tax (default: false).
	 * @param bool   $round (default: true).
	 * @return float
	 *
	 * @see WC_Order::get_item_total()
	 */
	public function get_item_total( $item, $inc_tax = false, $round = true ) {
		$total = 0.0;

		if ( is_callable( array( $item, 'get_total' ) ) && $item->get_quantity() ) {
			/**
			 * Legacy order data may contain empty strings as total - prevent PHP 8 errors
			 * from being thrown when calling the Woo core method.
			 *
			 * @see WC_Order::get_line_total()
			 */
			$item_total     = floatval( $item->get_total() );
			$item_total_tax = floatval( $item->get_total_tax() );

			if ( $inc_tax ) {
				$total = ( $item_total + $item_total_tax ) / $item->get_quantity();
			} else {
				$total = $item_total / $item->get_quantity();
			}

			$total = $round ? Numbers::round( $total, sab_get_price_decimals() ) : $total;
		}

		return apply_filters( 'woocommerce_order_amount_item_total', $total, $this->get_order(), $item, $inc_tax, $round );
	}

	/**
	 * Get item subtotal - this is the cost before discount.
	 *
	 * Patches a Woo bug which leads to fatal errors when using PHP 8 and
	 * legacy item data (empty total strings).
	 *
	 * @param object $item Item to get total from.
	 * @param bool   $inc_tax (default: false).
	 * @param bool   $round (default: true).
	 * @return float
	 *
	 * @see WC_Order::get_item_subtotal()
	 */
	public function get_item_subtotal( $item, $inc_tax = false, $round = true ) {
		$subtotal = 0.0;

		if ( is_callable( array( $item, 'get_subtotal' ) ) && $item->get_quantity() ) {
			if ( $inc_tax ) {
				$subtotal = ( floatval( $item->get_subtotal() ) + floatval( $item->get_subtotal_tax() ) ) / $item->get_quantity();
			} else {
				$subtotal = floatval( $item->get_subtotal() ) / $item->get_quantity();
			}

			$subtotal = $round ? Numbers::round( $subtotal, sab_get_price_decimals() ) : $subtotal;
		}

		return apply_filters( 'woocommerce_order_amount_item_subtotal', $subtotal, $this->get_order(), $item, $inc_tax, $round );
	}

	/**
	 * @param WC_Order_Item $order_item
	 *
	 * @return float
	 */
	public function get_billable_item_total( $order_item, $args = array() ) {
		$total_left = 0.0;
		$args       = wp_parse_args(
			$args,
			array(
				'invoice_id'              => 0,
				'exclude_current_invoice' => false,
				'incl_tax'                => $this->get_order()->get_prices_include_tax(),
			)
		);

		if ( is_numeric( $order_item ) ) {
			$order_item = $this->get_order()->get_item( $order_item );
		}

		if ( $order_item ) {
			/**
			 * Dynamically decide whether the item includes tax or not based on order item data.
			 */
			$args['incl_tax'] = $this->order_item_type_includes_tax( $order_item, $args['incl_tax'] );
			$total_left       = $this->get_line_total( $order_item, $args['incl_tax'], false ) - $this->get_order_item_refunded_total( $order_item, $args['incl_tax'], false );

			foreach ( $this->get_invoices() as $invoice ) {

				if ( $args['exclude_current_invoice'] && $args['invoice_id'] > 0 && ( $invoice->get_id() === (int) $args['invoice_id'] ) ) {
					continue;
				}

				if ( $item = $invoice->get_item_by_reference_id( $order_item->get_id() ) ) {
					/**
					 * Subtract quantity already cancelled to allow
					 * cancelled items to be added again.
					 */
					$item_total  = ( $args['incl_tax'] ? $item->get_total() : $item->get_total_net() ) - $invoice->get_item_total_cancelled( $item->get_id(), $args['incl_tax'] );
					$total_left -= $item_total;
				}
			}
		}

		$total_left_rounded = Numbers::round_to_precision( $total_left );

		if ( 0.0 === $total_left_rounded ) {
			$total_left = 0.0;
		}

		/**
		 * Do not bill negative fees (except for high accuracy taxes) - use discount instead.
		 */
		if ( $order_item && 'fee' === $order_item->get_type() && $total_left_rounded <= 0 && $this->bill_negative_fee_as_discount( $order_item ) ) {
			$total_left = 0.0;
		}

		/**
		 * Filter to adjust the billable item total left for a certain order item.
		 *
		 * @param integer       $total_left The total left for billing.
		 * @param WC_Order_Item $order_item The order item object.
		 * @param Order         $this       The invoice order object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return (float) apply_filters( "{$this->get_hook_prefix()}billable_order_item_total", $total_left, $order_item, $this, $args['incl_tax'] );
	}

	/**
	 * @param \WC_Order_Item_Fee $order_item
	 *
	 * @return boolean
	 */
	public function fee_is_voucher( $order_item ) {
		$is_voucher = false;

		if ( is_a( $order_item, 'WC_Order_Item_Fee' ) && 'yes' === $order_item->get_meta( '_is_voucher' ) ) {
			$is_voucher = true;
		}

		return apply_filters( "{$this->get_hook_prefix()}is_voucher", $is_voucher, $order_item );
	}

	/**
	 * @param \WC_Order_Item_Coupon $order_item
	 *
	 * @return boolean
	 */
	protected function coupon_is_voucher( $order_item ) {
		$is_voucher = false;

		if ( is_a( $order_item, 'WC_Order_Item_Coupon' ) && 'yes' === $order_item->get_meta( 'is_voucher' ) ) {
			$is_voucher = true;
		}

		return apply_filters( "{$this->get_hook_prefix()}coupon_is_voucher", $is_voucher, $order_item, $this );
	}

	/**
	 * Older invoice versions may include negative fees as discounts only.
	 *
	 * @return bool
	 */
	protected function may_include_negative_fee_as_discount() {
		$includes_fee_as_discount = false;

		foreach ( $this->get_finalized_invoices() as $invoice ) {
			if ( version_compare( $invoice->get_version(), '1.9.0', '>=' ) ) {
				$includes_fee_as_discount = false;
				break;
			} elseif ( ! $invoice->has_status( 'cancelled' ) && version_compare( $invoice->get_version(), '1.9.0', '<' ) ) {
				$includes_fee_as_discount = true;
				break;
			}
		}

		return $includes_fee_as_discount;
	}

	/**
	 * @param \WC_Order_Item_Fee $order_item
	 */
	protected function bill_negative_fee_as_discount( $order_item ) {
		$bill_as_discount = true;

		/**
		 * Allow fees to be booked as negative fee in case they are regular fees (e.g. include taxes)
		 * or explicitly are marked as voucher. Make sure to book negative fees as discounts vor legacy invoices
		 * to prevent legacy invoices from additional billings.
		 */
		if ( $this->fee_is_voucher( $order_item ) ) {
			$bill_as_discount = false;
		} elseif ( 0.0 !== (float) $order_item->get_total_tax() && ! $this->may_include_negative_fee_as_discount() ) {
			$bill_as_discount = false;
		}

		return apply_filters( "{$this->get_hook_prefix()}bill_negative_fee_as_discount", $bill_as_discount, $order_item, $this );
	}

	/**
	 * @param WC_Order_Item $order_item
	 *
	 * @return float
	 */
	public function get_billable_item_subtotal( $order_item, $args = array() ) {
		$total_left = 0.0;
		$args       = wp_parse_args(
			$args,
			array(
				'invoice_id'              => 0,
				'exclude_current_invoice' => false,
				'incl_tax'                => $this->get_order()->get_prices_include_tax(),
			)
		);

		if ( is_numeric( $order_item ) ) {
			$order_item = $this->get_order()->get_item( $order_item );
		}

		if ( $order_item ) {
			/**
			 * Dynamically decide whether the item includes tax or not based on order item data.
			 */
			$args['incl_tax'] = $this->order_item_type_includes_tax( $order_item, $args['incl_tax'] );

			$line_subtotal = $this->get_line_subtotal( $order_item, $args['incl_tax'], false );

			/**
			 * Fees and/or sipping do not support subtotals - use total instead.
			 */
			if ( 0.0 === $line_subtotal ) {
				$line_subtotal = $this->get_line_total( $order_item, $args['incl_tax'], false );
			}

			$total_left = $line_subtotal - $this->get_order_item_refunded_total( $order_item, $args['incl_tax'], false );

			foreach ( $this->get_invoices() as $invoice ) {

				if ( $args['exclude_current_invoice'] && $args['invoice_id'] > 0 && ( $invoice->get_id() === (int) $args['invoice_id'] ) ) {
					continue;
				}

				if ( $item = $invoice->get_item_by_reference_id( $order_item->get_id() ) ) {
					/**
					 * Substract quantity already cancelled to allow
					 * cancelled items to be added again.
					 */
					$item_total  = ( $args['incl_tax'] ? $item->get_subtotal() : $item->get_subtotal_net() ) - $invoice->get_item_subtotal_cancelled( $item->get_id(), $args['incl_tax'] );
					$total_left -= $item_total;
				}
			}
		}

		$total_left_rounded = Numbers::round_to_precision( $total_left );

		if ( 0.0 === $total_left_rounded ) {
			$total_left = 0.0;
		}

		/**
		 * Do not bill negative fees - use discount instead.
		 */
		if ( $order_item && 'fee' === $order_item->get_type() && $total_left_rounded <= 0 && $this->bill_negative_fee_as_discount( $order_item ) ) {
			$total_left = 0.0;
		}

		/**
		 * Filter to adjust the billable item total left for a certain order item.
		 *
		 * @param integer       $total_left The total left for billing.
		 * @param WC_Order_Item $order_item The order item object.
		 * @param Order         $this       The invoice order object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return (float) apply_filters( "{$this->get_hook_prefix()}billable_order_item_subtotal", $total_left, $order_item, $this, $args['incl_tax'] );
	}

	/**
	 * @param array
	 * @return array
	 */
	public function get_billable_items( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'disable_duplicates'      => false,
				'invoice_id'              => 0,
				'exclude_current_invoice' => false,
				'incl_tax'                => $this->get_order()->get_prices_include_tax(),
				'voucher_total'           => $this->get_voucher_total(),
			)
		);

		$items   = array();
		$invoice = $args['invoice_id'] ? $this->get_document( $args['invoice_id'] ) : false;

		if ( ! $this->has_full_refund() && ! $this->is_cancelled() ) {
			foreach ( $this->get_order_items() as $item ) {
				$quantity_left      = $this->get_billable_item_quantity( $item, $args );
				$total_left         = $this->get_billable_item_total( $item, $args );
				$total_left_inc_tax = $this->get_billable_item_total( $item, array_replace( $args, array( 'incl_tax' => true ) ) );
				$subtotal_left      = $this->get_billable_item_subtotal( $item, $args );

				$total_left_rounded         = Numbers::round_to_precision( $total_left );
				$total_left_inc_tax_rounded = Numbers::round_to_precision( $total_left_inc_tax );
				$subtotal_left_rounded      = Numbers::round_to_precision( $subtotal_left );

				if ( $invoice ) {
					if ( $args['disable_duplicates'] && $invoice->get_item_by_reference_id( $item->get_id() ) ) {
						continue;
					}
				}

				$include_item = false;

				if ( 0.0 === $total_left_inc_tax_rounded && (
					( $this->include_free_items() && $this->is_free_item( $item ) ) ||
					( $this->is_free() && $this->bill_free_orders() && ! $this->is_negative_fee( $item ) ) ||
					( $this->is_voucher_item( $item, $args['voucher_total'] ) && 0.0 !== $this->get_billable_item_subtotal( $item, $args ) )
				) ) {
					if ( $quantity_left > 0 ) {
						$include_item = true;

						if ( $this->is_free() && ! $this->bill_free_orders() && ! $this->is_voucher_item( $item, $args['voucher_total'] ) ) {
							$include_item = false;
						}
					}
				} elseif ( 0.0 !== $total_left_inc_tax_rounded ) {
					$include_item = true;
				}

				if ( $include_item ) {
					$items[ $item->get_id() ] = array(
						'name'         => $item->get_name(),
						'max_quantity' => ( $quantity_left <= 0 ) ? 1 : $quantity_left,
						'max_total'    => $total_left,
						'max_subtotal' => ( $subtotal_left < $total_left ? $total_left : $subtotal_left ),
					);
				}
			}
		}

		return $items;
	}

	/**
	 * @param WC_Order_Item $order_item
	 *
	 * @return boolean
	 */
	public function is_voucher_item( $order_item, $voucher_total = '' ) {
		$voucher_total = '' === $voucher_total ? $this->get_voucher_total() : (float) $voucher_total;

		if ( 'line_item' === $order_item->get_type() && $voucher_total > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * @param WC_Order_Item $order_item
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public function item_needs_billing( $order_item, $args = array() ) {
		$args = wp_parse_args( $args, array() );

		$needs_billing = false;

		if ( 0.0 !== $this->get_billable_item_total( $order_item, $args ) && 0.0 !== $this->get_billable_item_subtotal( $order_item, $args ) ) {
			$needs_billing = true;
		} elseif ( $this->is_voucher_item( $order_item ) && 0.0 !== $this->get_billable_item_subtotal( $order_item, $args ) ) {
			$needs_billing = true;
		}

		/**
		 * Free items. Check quantity instead of total amount.
		 */
		if ( ! $needs_billing && ( $this->get_billable_item_quantity( $order_item ) > 0 && $this->is_free_item( $order_item ) && $this->include_free_items() ) ) {
			$needs_billing = true;

			if ( $this->is_free() && ! $this->bill_free_orders() ) {
				$needs_billing = false;
			}
		}

		/**
		 * Free order. Check quantity instead of total amount.
		 */
		if ( ! $needs_billing && ( $this->get_billable_item_quantity( $order_item ) > 0 && $this->is_free() && $this->bill_free_orders() && ! $this->is_negative_fee( $order_item ) ) ) {
			$needs_billing = true;
		}

		/**
		 * Filter to decide whether an order item needs billing or not.
		 *
		 * @param boolean       $needs_billing Whether the item needs billing or not.
		 * @param WC_Order_Item $item The order item object.
		 * @param array         $args Additional arguments to be considered.
		 * @param Order      $order The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}item_needs_billing", $needs_billing, $order_item, $args, $this );
	}

	public function unfiltered_order_items_callback( $items ) {
		$this->unfiltered_items = $items;

		return $items;
	}

	protected function get_order_item_types_to_sync() {
		$purchase_item_types = array_filter( (array) apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
		$item_types          = array_unique( array_merge( $purchase_item_types, array( 'line_item', 'shipping', 'fee' ) ) );

		/**
		 * Filter to adjust order item types to sync.
		 *
		 * @param string[] $items Array containing order item types.
		 * @param WC_Order $order The order object.
		 * @param Order    $order The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}item_types_to_sync", $item_types, $this->get_order(), $this );
	}

	/**
	 * Returns items that are ready for billing.
	 *
	 * @return WC_Order_Item[] Billable items.
	 */
	protected function get_order_items( $prevent_filters = false ) {
		$items                  = array();
		$this->unfiltered_items = null;

		if ( $prevent_filters ) {
			add_filter( 'woocommerce_order_get_items', array( $this, 'unfiltered_order_items_callback' ), -1000 );
			$this->get_order()->get_items( $this->get_order_item_types_to_sync() );
			remove_filter( 'woocommerce_order_get_items', array( $this, 'unfiltered_order_items_callback' ), -1000 );

			if ( ! is_null( $this->unfiltered_items ) ) {
				$items                  = array_filter( $this->unfiltered_items );
				$this->unfiltered_items = null;
			} else {
				$items = $this->get_order()->get_items( $this->get_order_item_types_to_sync() );
				$items = array_filter( $items );
			}
		} else {
			$items = $this->get_order()->get_items( $this->get_order_item_types_to_sync() );
			$items = array_filter( $items );
		}

		/**
		 * Filter to adjust order items for a specific order.
		 *
		 * @param WC_Order_Item[] $items Array containing order items.
		 * @param WC_Order        $order The order object.
		 * @param Order           $order The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}items", $items, $this->get_order(), $this );
	}

	/**
	 * Returns the newest refund items mapped by its parent order item id.
	 *
	 * @return WC_Order_Item[] Refund items.
	 */
	protected function get_refund_items_map() {
		$refunds       = $this->get_order()->get_refunds();
		$latest_refund = false;
		$items         = array();

		if ( ! empty( $refunds ) ) {
			$latest_refund = reset( $refunds );
		}

		if ( $latest_refund ) {
			$refund_items = $latest_refund->get_items( $this->get_order_item_types_to_sync() );
			$refund_items = array_filter( $refund_items );

			foreach ( $refund_items as $refund_item ) {
				$parent_id = $refund_item->get_meta( '_refunded_item_id', true );

				if ( ! empty( $parent_id ) ) {
					$items[ $parent_id ] = $refund_item;
				} elseif ( $refund_item->get_total() > 0 && 'fee' === $refund_item->get_type() ) {
					/**
					 * Allow additional fees, e.g. return fees
					 */
					if ( ! array_key_exists( 'fees', $items ) ) {
						$items['fees'] = array();
					}

					$items['fees'][] = $refund_item;
				}
			}
		}

		return $items;
	}

	/**
	 * Returns the total number of billable items.
	 *
	 * @return mixed|void
	 */
	public function get_billable_item_count() {
		$count = 0;

		foreach ( $this->get_order_items() as $item ) {
			$count += $this->get_billable_item_quantity( $item );
		}

		/**
		 * Filters the total number of billable items available for a specific order.
		 *
		 * @param integer  $count The total number of items.
		 * @param Order $order The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}billable_item_count", $count, $this );
	}

	public function needs_cancelling() {
		$items_left = $this->get_order_items_to_cancel();

		/**
		 * Filters whether the order needs cancelling or not.
		 *
		 * @param boolean $needs_cancelling Whether cancelling is needed or not.
		 * @param Order $order The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}needs_cancelling", ( ! empty( $items_left ) ), $this );
	}

	/**
	 * @param WC_Order_Item $order_item
	 *
	 * @return bool
	 */
	protected function is_free_item( $order_item ) {
		return ( 0.0 === $this->get_line_total( $order_item, true, true ) );
	}

	/**
	 * @param WC_Order_Item $order_item
	 *
	 * @return bool
	 */
	protected function is_negative_fee( $order_item ) {
		return ( is_a( $order_item, 'WC_Order_Item_Fee' ) && $order_item->get_total() < 0 );
	}

	protected function is_free() {
		return 0.0 === (float) $this->get_order()->get_total() && $this->get_voucher_total() <= 0;
	}

	protected function include_free_items() {
		return apply_filters( "{$this->get_hook_prefix()}include_free_items", true, $this->get_order(), $this );
	}

	protected function bill_free_orders() {
		return apply_filters( "{$this->get_hook_prefix()}bill_free_orders", ( 'yes' === Package::get_setting( 'invoice_woo_order_free' ) ), $this->get_order(), $this );
	}

	/**
	 * Checks whether the order needs billing or not by checking quantity
	 * for every order item.
	 *
	 * @return bool Whether the order needs billing or not.
	 */
	public function needs_billing( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'incl_tax' => $this->get_order()->get_prices_include_tax(),
			)
		);

		$order_items   = $this->get_order_items();
		$needs_billing = false;

		if ( ! $this->has_full_refund() && ! $this->is_cancelled() ) {
			foreach ( $order_items as $order_item ) {
				if ( $this->item_needs_billing( $order_item, $args ) ) {
					$needs_billing = true;
					break;
				}
			}
		}

		/**
		 * Filter to decide whether an order needs billing or not.
		 *
		 * @param boolean  $needs_billing Whether the order needs billing or not.
		 * @param WC_Order $order The order object.
		 * @param Order    $order The invoice order object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_hook_prefix()}needs_billing", $needs_billing, $this->get_order(), $this );
	}

	protected function get_order_items_left( $prevent_filters = false ) {
		$order_item_data = array();

		foreach ( $this->get_order_items( $prevent_filters ) as $order_item ) {
			$item_includes_tax   = $this->order_item_type_includes_tax( $order_item->get_type(), $this->get_order()->get_prices_include_tax() );
			$total_refunded      = $this->get_order_item_refunded_total( $order_item, true, false );
			$total_refunded_excl = $this->get_order_item_refunded_total( $order_item, false, false );
			$quantity            = $order_item->get_quantity() - $this->get_order_item_refunded_quantity( $order_item );
			$total               = $this->get_line_total( $order_item, true, false ) - $total_refunded;
			$tax_total_refunded  = $this->get_order_item_refunded_tax_total( $order_item );
			$line_total_tax      = $this->get_order()->get_line_tax( $order_item ) - $tax_total_refunded;
			$line_total          = $this->get_line_total( $order_item, $item_includes_tax, false ) - ( $item_includes_tax ? $total_refunded : $total_refunded_excl );

			$order_item_data[ $order_item->get_id() ] = array(
				'quantity'     => $quantity,
				'total'        => $total,
				'line_total'   => $line_total,
				'tax'          => $line_total_tax,
				'refunded'     => $total_refunded,
				'refunded_net' => $total_refunded_excl,
				'refunded_tax' => $tax_total_refunded,
			);
		}

		return $order_item_data;
	}

	protected function order_item_has_refund( $order_item ) {
		$total_refunded = $this->get_order_item_refunded_total( $order_item );

		return ( $total_refunded > 0 || $total_refunded < 0 ) ? true : false;
	}

	protected function is_cancelled() {
		return $this->get_order()->has_status( array( 'cancelled', 'refunded' ) );
	}

	protected function has_full_refund() {
		$refund_total = (float) $this->get_order()->get_total_refunded();
		$order_total  = (float) $this->get_order()->get_total();

		if ( $refund_total > 0 && $refund_total >= $order_total ) {
			return true;
		}

		return false;
	}

	protected function get_order_items_to_cancel( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'cancelable_only' => false,
			)
		);

		$order_items_to_cancel       = array();
		$order_items_left            = $this->get_order_items_left();
		$order_items_left_unfiltered = $this->get_order_items_left( true );

		foreach ( $this->get_invoices() as $invoice ) {
			if ( $args['cancelable_only'] && ! $invoice->is_cancelable() ) {
				continue;
			}

			foreach ( $invoice->get_items_left_to_cancel() as $item_id => $item_data ) {
				if ( $item = $invoice->get_item( $item_id ) ) {
					$quantity_available_to_cancel   = $item_data['quantity'];
					$line_total_available_to_cancel = $item_data['line_total'];
					$total_available_to_cancel      = $item_data['total'];
					$available_tax_to_cancel        = $item_data['tax'];

					$order_item_id        = $item->get_reference_id();
					$quantity_to_cancel   = 0;
					$line_total_to_cancel = 0;
					$total_to_cancel      = 0;
					$tax_to_cancel        = 0;

					$price                     = Numbers::round_to_precision( $item->get_price() );
					$price_subtotal            = Numbers::round_to_precision( $item->get_price_subtotal() );
					$order_item_price          = $price;
					$order_item_price_subtotal = $price;
					$order_item_has_refund     = false;

					if ( $order_item = $this->get_order_item( $order_item_id ) ) {
						$order_item_has_refund     = $this->order_item_has_refund( $order_item->get_order_item() );
						$order_item_price          = $this->get_item_total( $order_item->get_order_item(), true, false );
						$order_item_price_subtotal = $this->get_item_subtotal( $order_item->get_order_item(), true, false );
					}

					$order_item_price          = Numbers::round_to_precision( $order_item_price );
					$order_item_price_subtotal = Numbers::round_to_precision( $order_item_price_subtotal );
					$price_has_changed         = false;
					$reason                    = '';
					$reason_details            = '';

					/**
					 * Detect order item price changes (manual adjustments).
					 */
					if ( ! $order_item_has_refund ) {
						if ( $order_item_price < $price && $order_item_price !== $price_subtotal && $order_item_price_subtotal !== $price_subtotal ) {
							$price_has_changed = true;
						}
					}

					if ( $this->is_cancelled() || $this->has_full_refund() ) {
						/**
						 * Seems like the order has been cancelled or refunded.
						 */
						$quantity_to_cancel   = $quantity_available_to_cancel;
						$line_total_to_cancel = $line_total_available_to_cancel;
						$total_to_cancel      = $total_available_to_cancel;
						$tax_to_cancel        = $available_tax_to_cancel;
						$reason               = 'order_cancelled';

						Package::extended_log( 'Order has been cancelled or has a full refund' );
					} elseif ( ! empty( $order_item_id ) && ! array_key_exists( $order_item_id, $order_items_left ) ) {
						/**
						 * Do explicitly check whether the item is missing in the non-filtered items array too to make sure
						 * we are not wrongfully cancelling items that may be temporarily filtered out by some bad extension.
						 */
						if ( ! array_key_exists( $order_item_id, $order_items_left_unfiltered ) ) {
							/**
							 * Seems like the order item does not exist any longer.
							 */
							$quantity_to_cancel   = $quantity_available_to_cancel;
							$line_total_to_cancel = $line_total_available_to_cancel;
							$total_to_cancel      = $total_available_to_cancel;
							$tax_to_cancel        = $available_tax_to_cancel;
							$reason               = 'order_item_removed';

							Package::extended_log( sprintf( 'Order item %s doesnt seem to exist any longer or quantity has changed', $order_item_id ) );
						}
					} elseif ( $price_has_changed || ( ! empty( $order_item_id ) && abs( Numbers::round_to_precision( $order_items_left[ $order_item_id ]['line_total'] ) ) < abs( Numbers::round_to_precision( $line_total_available_to_cancel ) ) ) ) {
						/**
						 * Order item has changed in total, e.g. refunded.
						 */
						$line_total_to_cancel = ( $line_total_available_to_cancel - $order_items_left[ $order_item_id ]['line_total'] );

						if ( $line_total_to_cancel > 0 ) {
							$tax_to_cancel   = max( $available_tax_to_cancel - $order_items_left[ $order_item_id ]['tax'], 0 );
							$total_to_cancel = max( $total_available_to_cancel - $order_items_left[ $order_item_id ]['total'], 0 );
						} else {
							$tax_to_cancel   = min( $available_tax_to_cancel - $order_items_left[ $order_item_id ]['tax'], 0 );
							$total_to_cancel = min( $total_available_to_cancel - $order_items_left[ $order_item_id ]['total'], 0 );
						}

						$quantity_left      = $order_items_left[ $order_item_id ]['quantity'];
						$quantity_to_cancel = ( $quantity_left < $quantity_available_to_cancel ) ? $quantity_available_to_cancel - $quantity_left : 1;

						/**
						 * Seems like a tax-only refund has been added. Let's cancel the whole line.
						 */
						if ( abs( $order_items_left[ $order_item_id ]['refunded_tax'] ) > abs( $order_items_left[ $order_item_id ]['refunded_net'] ) ) {
							$line_total_to_cancel = $line_total_available_to_cancel;
							$total_to_cancel      = $total_available_to_cancel;
							$tax_to_cancel        = $available_tax_to_cancel;
						}

						$reason = 'order_item_changed';

						Package::extended_log( sprintf( 'Order item %s seems to have changed (e.g. refunded, price change). Cancel %s', $order_item_id, $total_to_cancel ) );
					}

					// Prevent rounding issues.
					$line_total_to_cancel_rounded = Numbers::round_to_precision( $line_total_to_cancel );

					if ( $line_total_to_cancel_rounded > 0 || ( $line_total_to_cancel_rounded < 0 && Numbers::round_to_precision( $line_total_available_to_cancel ) < 0 ) ) {
						if ( ! array_key_exists( $order_item_id, $order_items_to_cancel ) ) {
							$order_items_to_cancel[ $order_item_id ] = array(
								'quantity'   => $quantity_to_cancel > 0 ? $quantity_to_cancel : 1,
								'line_total' => $line_total_to_cancel,
								'total'      => $total_to_cancel,
								'tax'        => $tax_to_cancel,
							);
						} else {
							$order_items_to_cancel[ $order_item_id ]['quantity']   += $quantity_to_cancel;
							$order_items_to_cancel[ $order_item_id ]['line_total'] += $line_total_to_cancel;
							$order_items_to_cancel[ $order_item_id ]['total']      += $total_to_cancel;
							$order_items_to_cancel[ $order_item_id ]['tax']        += $tax_to_cancel;
						}

						$order_items_to_cancel[ $order_item_id ]['reason']         = $reason;
						$order_items_to_cancel[ $order_item_id ]['reason_details'] = $reason_details;
					}

					if ( array_key_exists( $order_item_id, $order_items_left ) ) {
						$order_items_left[ $order_item_id ]['quantity']   -= $quantity_available_to_cancel;
						$order_items_left[ $order_item_id ]['line_total'] -= $line_total_available_to_cancel;
						$order_items_left[ $order_item_id ]['total']      -= $total_available_to_cancel;
						$order_items_left[ $order_item_id ]['tax']        -= $available_tax_to_cancel;

						if ( $line_total_available_to_cancel >= 0 && $order_items_left[ $order_item_id ]['line_total'] <= 0 ) {
							unset( $order_items_left[ $order_item_id ] );
						} elseif ( $line_total_available_to_cancel < 0 && $order_items_left[ $order_item_id ]['line_total'] >= 0 ) {
							unset( $order_items_left[ $order_item_id ] );
						}
					}

					if ( array_key_exists( $order_item_id, $order_items_left_unfiltered ) ) {
						$order_items_left_unfiltered[ $order_item_id ]['quantity']   -= $quantity_available_to_cancel;
						$order_items_left_unfiltered[ $order_item_id ]['line_total'] -= $line_total_available_to_cancel;
						$order_items_left_unfiltered[ $order_item_id ]['total']      -= $total_available_to_cancel;
						$order_items_left_unfiltered[ $order_item_id ]['tax']        -= $available_tax_to_cancel;

						if ( $line_total_available_to_cancel >= 0 && $order_items_left_unfiltered[ $order_item_id ]['line_total'] <= 0 ) {
							unset( $order_items_left_unfiltered[ $order_item_id ] );
						} elseif ( $line_total_available_to_cancel < 0 && $order_items_left_unfiltered[ $order_item_id ]['line_total'] >= 0 ) {
							unset( $order_items_left_unfiltered[ $order_item_id ] );
						}
					}
				}
			}
		}

		return $order_items_to_cancel;
	}

	/**
	 * This method makes sure that invoices contained within an order
	 * do not bill more items than available. In case a user creates a
	 * refund or edits the order this method will edit (or delete) unfixed
	 * invoices and/or create cancellations (if necessary).
	 *
	 * In case a data anomaly was found (e.g. totals do not match after refunds)
	 * all unfixed invoices will be deleted and fixed invoices cancelled.
	 *
	 * @param array $args
	 * @param array $cancellation_props Static props passed to the cancellation that may be created.
	 *
	 * @return boolean Whether cancelling was necessary or not.
	 */
	public function maybe_cancel( $args = array(), $cancellation_props = array() ) {
		$refund_items          = $this->get_refund_items_map();
		$order_items_to_cancel = $this->get_order_items_to_cancel( $args );
		$has_cancelled         = false;

		if ( ! apply_filters( "{$this->get_hook_prefix()}allow_auto_cancel", true, $this->get_order(), $this ) ) {
			Package::extended_log( 'Skipped to cancellation for Order #' . $this->get_id() );
			return false;
		}

		foreach ( $this->get_editable_invoices() as $invoice ) {
			$invoice_items = $invoice->get_items();

			if ( 0.0 === (float) $invoice->get_total() && empty( $invoice_items ) ) {
				Package::extended_log( 'Removing empty (zero total, no items) invoice draft ' . $invoice->get_id() );
				$this->delete_document( $invoice->get_id() );
				$has_cancelled = true;
			}
		}

		/**
		 * Check whether we can delete non-finalized invoices.
		 */
		if ( ! empty( $order_items_to_cancel ) ) {
			Package::extended_log( 'Order #' . $this->get_id() . ' items to cancel: ' . sab_print_r( $order_items_to_cancel, true ) );

			foreach ( $this->get_editable_invoices() as $invoice ) {
				$needs_recalculate = false;

				foreach ( $order_items_to_cancel as $order_item_id => $item_to_cancel ) {
					if ( $item = $invoice->get_item_by_reference_id( $order_item_id ) ) {
						/**
						 * Seems like the item type is not cancelable
						 */
						if ( ! in_array( $item->get_item_type(), $invoice->get_item_types_cancelable(), true ) ) {
							continue;
						}

						$item_quantity     = $item->get_quantity();
						$new_item_quantity = $item_quantity - $item_to_cancel['quantity'];

						$item_total          = (float) $item->get_total();
						$new_item_total      = $item_total - $item_to_cancel['total'];
						$item_line_total     = (float) $item->get_line_total();
						$new_item_line_total = $item_line_total - $item_to_cancel['line_total'];
						$needs_recalculate   = true;

						if ( $new_item_line_total <= 0 ) {
							Package::extended_log( 'Auto removing item "' . $item->get_name() . '" from invoice draft ' . $invoice->get_id() );
							$invoice->remove_item( $item->get_id() );

							$has_cancelled = true;
						} else {
							$item->set_line_total( $new_item_line_total );
							$item->set_line_subtotal( $new_item_line_total );
							$item->set_quantity( $new_item_quantity <= 0 ? 1 : $new_item_quantity );
						}

						$order_items_to_cancel[ $order_item_id ]['quantity']   = $item_to_cancel['quantity'] - $item_quantity;
						$order_items_to_cancel[ $order_item_id ]['total']      = $item_to_cancel['total'] - $item_total;
						$order_items_to_cancel[ $order_item_id ]['line_total'] = $item_to_cancel['line_total'] - $item_line_total;

						if ( $order_items_to_cancel[ $order_item_id ]['quantity'] <= 0 ) {
							$order_items_to_cancel[ $order_item_id ]['quantity'] = 1;
						}

						$current_total_rounded = Numbers::round_to_precision( $order_items_to_cancel[ $order_item_id ]['line_total'] );

						if ( $current_total_rounded <= 0 ) {
							unset( $order_items_to_cancel[ $order_item_id ] );
						}
					}
				}

				if ( $needs_recalculate ) {
					$invoice->calculate_totals();

					/**
					 * In case the invoice doesn't hold any items any longer - delete it.
					 */
					if ( empty( $invoice->get_items() ) ) {
						Package::extended_log( 'Auto removing invoice draft ' . $invoice->get_id() );
						$this->delete_document( $invoice->get_id() );
						$has_cancelled = true;
					}
				}
			}
		}

		/**
		 * Do a full cancellation instead of cancelling single items in case round tax at subtotal setting
		 * has changed.
		 */
		if ( ! empty( $order_items_to_cancel ) && $this->round_tax_at_subtotal_has_changed() ) {
			$has_cancelled = true;

			$this->cancel( 'round_tax_setting_changed', $cancellation_props );
			Package::extended_log( 'Auto cancelling order #' . $this->get_id() . ' invoices due to change of Woo round_tax_at_subtotal setting.' );
		} elseif ( ! empty( $order_items_to_cancel ) ) {
			foreach ( $this->get_invoices() as $invoice ) {
				if ( empty( $order_items_to_cancel ) ) {
					break;
				}

				if ( $invoice->is_cancelable() ) {
					$items_to_cancel      = array();
					$items_left_to_cancel = array();
					$is_refund_linkable   = true;
					$refund_id            = 0;

					foreach ( $invoice->get_items_left_to_cancel() as $item_id => $item_data ) {
						if ( $item = $invoice->get_item( $item_id ) ) {
							$items_left_to_cancel[ $item->get_reference_id() ]         = $item_data;
							$items_left_to_cancel[ $item->get_reference_id() ]['item'] = $item;
						}
					}

					foreach ( $order_items_to_cancel as $order_item_id => $item_to_cancel ) {
						if ( isset( $items_left_to_cancel[ $order_item_id ] ) ) {
							$item_data       = $items_left_to_cancel[ $order_item_id ];
							$item            = $item_data['item'];
							$item_quantity   = $item_data['quantity'];
							$item_total      = (float) $item_data['total'];
							$item_line_total = (float) $item_data['line_total'];
							$item_tax        = (float) $item_data['tax'];

							$item_quantity_to_cancel = (int) $item_to_cancel['quantity'] >= $item_quantity ? $item_quantity : $item_to_cancel['quantity'];

							if ( $item_to_cancel['total'] > 0 ) {
								$item_total_to_cancel = (float) min( $item_total, $item_to_cancel['total'] );
							} else {
								$item_total_to_cancel = (float) max( $item_total, $item_to_cancel['total'] );
							}

							if ( $item_to_cancel['line_total'] > 0 ) {
								$item_line_total_to_cancel = (float) min( $item_line_total, $item_to_cancel['line_total'] );
							} else {
								$item_line_total_to_cancel = (float) max( $item_line_total, $item_to_cancel['line_total'] );
							}

							if ( $item_to_cancel['tax'] > 0 ) {
								$item_total_tax_to_cancel = (float) min( $item_tax, $item_to_cancel['tax'] );
							} else {
								$item_total_tax_to_cancel = (float) max( $item_tax, $item_to_cancel['tax'] );
							}

							$items_to_cancel[ $item->get_id() ] = array(
								'name'           => $item->get_name(),
								'quantity'       => $item_quantity_to_cancel,
								'total'          => $item_total_to_cancel,
								'line_total'     => $item_line_total_to_cancel,
								'tax'            => $item_total_tax_to_cancel,
								'reason'         => $item_to_cancel['reason'],
								'reason_details' => $item_to_cancel['reason_details'],
							);

							if ( false !== $is_refund_linkable && array_key_exists( $order_item_id, $refund_items ) ) {
								$current_item_refund_id = $refund_items[ $order_item_id ]->get_order_id();
								$refunded_quantity      = abs( $refund_items[ $order_item_id ]->get_quantity() );

								if ( empty( $refund_id ) && (int) $refunded_quantity === (int) $item_quantity_to_cancel ) {
									$refund_id = $current_item_refund_id;
								} elseif ( (int) $refunded_quantity !== (int) $item_quantity_to_cancel || $refund_id !== $current_item_refund_id ) {
									$is_refund_linkable = false;
									$refund_id          = 0;
								}
							}

							$order_items_to_cancel[ $order_item_id ]['quantity']   = $item_to_cancel['quantity'] - $item_quantity_to_cancel;
							$order_items_to_cancel[ $order_item_id ]['total']      = $item_to_cancel['total'] - $item_total_to_cancel;
							$order_items_to_cancel[ $order_item_id ]['line_total'] = $item_to_cancel['line_total'] - $item_line_total_to_cancel;
							$order_items_to_cancel[ $order_item_id ]['tax']        = $item_to_cancel['tax'] - $item_total_tax_to_cancel;

							if ( $order_items_to_cancel[ $order_item_id ]['quantity'] <= 0 ) {
								$order_items_to_cancel[ $order_item_id ]['quantity'] = 1;
							}

							$current_total_rounded = Numbers::round_to_precision( $order_items_to_cancel[ $order_item_id ]['line_total'] );

							if ( $current_total_rounded <= 0 ) {
								unset( $order_items_to_cancel[ $order_item_id ] );
							}
						}
					}

					if ( ! empty( $items_to_cancel ) ) {
						Package::extended_log( 'Trying to cancel items for invoice ' . $invoice->get_formatted_number() . ': ' . sab_print_r( $items_to_cancel, true ) );

						if ( ! empty( $refund_items['fees'] ) ) {
							$items_to_cancel['fees'] = array();

							foreach ( $refund_items['fees'] as $fee_id => $order_item_fee ) {
								$order_item = new OrderItemFee( $order_item_fee, $this );
								$fee_item   = new FeeItem();
								$sync_data  = array(
									'name'               => $order_item_fee->get_name(),
									'quantity'           => $order_item_fee->get_quantity(),
									'line_total'         => (float) $this->get_line_total( $order_item_fee, $invoice->prices_include_tax(), false ) * -1,
									'order_tax_rates'    => $this->get_tax_rates(),
									'prices_include_tax' => $this->order_item_type_includes_tax( $order_item_fee, $invoice->prices_include_tax() ),
								);

								$sync_data['line_subtotal'] = $sync_data['line_total'];

								if ( in_array( 'fee', $invoice->get_item_types_additional_costs(), true ) ) {
									$sync_data['round_tax_at_subtotal'] = $this->order_item_type_round_tax_at_subtotal( $order_item_fee, $invoice->round_tax_at_subtotal() );
								}

								$order_item->sync( $fee_item, $sync_data );

								$fee_item->calculate_tax_totals();
								$fee_item->calculate_totals();

								$items_to_cancel['fees'][] = $fee_item;

								unset( $refund_items['fees'][ $fee_id ] );
							}
						}

						$cancellation = $invoice->cancel( $items_to_cancel, $refund_id, $cancellation_props );

						if ( ! is_wp_error( $cancellation ) ) {
							$has_cancelled = true;

							$this->add_document( $cancellation );
							Package::extended_log( 'Auto added cancellation ' . $cancellation->get_formatted_number() . ' to order #' . $this->get_id() );

							$item_notes = array();

							foreach ( $items_to_cancel as $item ) {
								if ( ! is_array( $item ) || ! isset( $item['reason'] ) ) {
									continue;
								}

								$reason = empty( $item['reason'] ) ? 'no_reason' : $item['reason'];

								if ( ! empty( $item['reason_details'] ) ) {
									$reason = $reason . ' (' . $item['reason_details'] . ')';
								}

								$item_notes[] = sprintf( _x( '%1$s x Item %2$s &rarr; %3$s (Total: %4$s, Line total: %5$s, Tax: %6$s)', 'storeabill-core', 'woocommerce-germanized-pro' ), $item['quantity'], $item['name'], $reason, Numbers::round_to_precision( $item['total'] ), Numbers::round_to_precision( $item['line_total'] ), Numbers::round_to_precision( $item['tax'] ) );
							}

							$this->get_order()->add_order_note( sprintf( _x( '(Partially) Cancelled invoice %1$s: %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_formatted_number(), implode( ', ', $item_notes ) ) );
						} else {
							Package::extended_log( 'Error while creating cancellation for invoice ' . $invoice->get_formatted_number() . ': ' . implode( ', ', $cancellation->get_error_messages() ) );
						}
					}
				}
			}
		}

		if ( $this->total_billed_greater_order_total() ) {
			$has_cancelled = true;

			/**
			 * Try to delete editable invoices first and see whether the issue persists.
			 */
			foreach ( $this->get_editable_invoices() as $invoice ) {
				$this->delete_document( $invoice->get_id() );
			}

			if ( $this->total_billed_greater_order_total() ) {
				$this->cancel( 'order_total_diff', $cancellation_props );
				Package::extended_log( 'Auto cancelling order #' . $this->get_id() . ' due to total diff (billed: ' . $total_billed . ', order total after refunds: ' . $order_total_after_refunds . ')' );
			}
		}

		return $has_cancelled;
	}

	protected function total_billed_greater_order_total() {
		$total_billed              = $this->get_total_billed();
		$order_total_after_refunds = $this->get_order_total_to_bill();

		return sab_add_number_precision( $total_billed ) > sab_add_number_precision( $order_total_after_refunds );
	}

	/**
	 * @return float
	 */
	protected function get_order_total_to_bill() {
		return ( (float) $this->get_order()->get_total() - (float) $this->get_order()->get_total_refunded() );
	}

	/**
	 * @return float
	 */
	protected function get_order_total_tax_to_bill() {
		return ( (float) $this->get_order()->get_total_tax() - (float) $this->get_order()->get_total_tax_refunded() );
	}

	public function get_edit_url() {
		return $this->get_order()->get_edit_order_url() . '#sab-order-invoices';
	}

	/**
	 * @param $finalized_only
	 *
	 * @return float
	 */
	public function get_total_billed( $finalized_only = false ) {
		$total = 0.0;

		foreach ( $this->get_documents() as $document ) {
			if ( $finalized_only && ! $document->is_finalized() ) {
				continue;
			}

			if ( 'cancellation' === $document->get_invoice_type() ) {
				$total -= $document->get_total();
			} else {
				$total += $document->get_total();
			}
		}

		if ( 0.0 === Numbers::round_to_precision( $total ) ) {
			$total = 0.0;
		}

		return $total;
	}

	/**
	 * Returns the total billed net amount. This amount may be negative for invoices
	 * that do not include taxes and have a total amount of zero but a tax amount greater than zero (e.g. vouchers).
	 *
	 * @param $finalized_only
	 * @param $ex_voucher
	 *
	 * @return float
	 */
	public function get_net_total_billed( $finalized_only = false, $ex_voucher = true ) {
		$total = 0.0;

		foreach ( $this->get_documents() as $document ) {
			if ( $finalized_only && ! $document->is_finalized() ) {
				continue;
			}

			if ( 'cancellation' === $document->get_invoice_type() ) {
				$total -= $ex_voucher ? $document->get_net_total_ex_voucher() : $document->get_total_net();
			} else {
				$total += $ex_voucher ? $document->get_net_total_ex_voucher() : $document->get_total_net();
			}
		}

		if ( 0.0 === Numbers::round_to_precision( $total ) ) {
			$total = 0.0;
		}

		return $total;
	}

	/**
	 * @param $finalized_only
	 *
	 * @return float
	 */
	public function get_total_tax_billed( $finalized_only = false ) {
		$total = 0.0;

		foreach ( $this->get_documents() as $document ) {
			if ( $finalized_only && ! $document->is_finalized() ) {
				continue;
			}

			if ( 'cancellation' === $document->get_invoice_type() ) {
				$total -= $document->get_total_tax();
			} else {
				$total += $document->get_total_tax();
			}
		}

		return $total;
	}

	/**
	 * @param $ref_id
	 * @param $finalized_only
	 *
	 * @return float
	 */
	public function get_total_tax_billed_by_reference_id( $ref_id, $finalized_only = false ) {
		$total = 0.0;

		foreach ( $this->get_documents() as $document ) {
			if ( $finalized_only && ! $document->is_finalized() ) {
				continue;
			}

			$taxes = $document->get_tax_totals();

			foreach ( $taxes as $tax ) {
				if ( in_array( $ref_id, $tax->get_tax_rate()->get_reference_ids() ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					if ( 'cancellation' === $document->get_invoice_type() ) {
						$total -= $tax->get_total_tax( false );
					} else {
						$total += $tax->get_total_tax( false );
					}
				}
			}
		}

		return $total;
	}

	public function save( $sync_editable = true ) {
		$error = new \WP_Error();

		if ( ! empty( $this->documents_to_delete ) ) {
			foreach ( $this->documents_to_delete as $invoice ) {
				$invoice->delete();
			}
		}

		foreach ( $this->documents as $invoice_type => $invoices ) {
			if ( $sync_editable ) {
				foreach ( $invoices as $invoice ) {
					if ( $sync_editable && $invoice->get_id() > 0 ) {
						$this->sync( $invoice );
					}
				}

				if ( 'invoice' === $invoice_type && $this->get_last_editable_invoice() && ! $this->needs_billing() ) {
					$this->book_order_divergences();
				}
			}

			foreach ( $invoices as $key => $invoice ) {
				$id = $invoice->save();

				/**
				 * There seems to be an error while creating the invoice.
				 * Remove the invoice from the list to make sure it is now even presented to the user.
				 */
				if ( empty( $id ) || is_wp_error( $id ) ) {
					unset( $this->documents[ $invoice_type ][ $key ] );

					$error_details = is_wp_error( $id ) ? $id->get_error_message() : $id;

					$error->add( 'invoice-create', sprintf( _x( 'There was an error while saving %1$s: %2$s. Please review and save your order and try again.', 'storeabill-core', 'woocommerce-germanized-pro' ), trim( $invoice->get_title( false ) ), $error_details ) );
				}
			}
		}

		$this->remove_cache();

		return sab_wp_error_has_errors( $error ) ? $error : true;
	}

	/**
	 * Check if a method is callable by checking the underlying order object.
	 * Necessary because is_callable checks will alway return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif ( is_callable( array( $this->get_order(), $method ) ) ) {
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

		if ( method_exists( $this->order, $method ) ) {
			return call_user_func_array( array( $this->order, $method ), $args );
		}

		return false;
	}
}
