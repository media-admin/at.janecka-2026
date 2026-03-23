<?php

namespace Vendidero\StoreaBill\Lexoffice;

use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\ExternalSync\SyncHandler;
use Vendidero\StoreaBill\Interfaces\ExternalSyncable;
use Vendidero\StoreaBill\Invoice\Cancellation;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\Tax;
use Vendidero\StoreaBill\TaxRate;
use Vendidero\StoreaBill\Lexoffice\API\Auth;
use Vendidero\StoreaBill\Lexoffice\API\Resources;
use Vendidero\StoreaBill\Utilities\Numbers;

defined( 'ABSPATH' ) || exit;

class Sync extends SyncHandler {

	/**
	 * @var Auth|null
	 */
	protected $auth_api = null;

	/**
	 * @var Resources|null
	 */
	protected $api = null;

	/**
	 * @var bool|Customer
	 */
	protected $invoice_customer = false;

	public static function get_name() {
		return 'lexoffice';
	}

	public static function get_title() {
		return _x( 'lexware', 'lexoffice', 'woocommerce-germanized-pro' );
	}

	public static function get_description() {
		return _x( 'Transfer invoices and customer data to your lexware account.', 'lexoffice', 'woocommerce-germanized-pro' );
	}

	public static function get_help_link() {
		return 'https://vendidero.de/dokument/lexoffice-schnittstelle';
	}

	public static function get_supported_object_types() {
		return array( 'customer', 'invoice', 'invoice_cancellation' );
	}

	public static function get_api_type() {
		return 'REST';
	}

	public static function get_icon() {
		return trailingslashit( Package::get_url() ) . 'assets/icon.png';
	}

	public function __construct() {
		$this->auth_api = new Auth( $this );
		$this->api      = new Resources( $this );

		parent::__construct();
	}

	public function is_sandbox() {
		return false;
	}

	/**
	 * @param Invoice|\Vendidero\StoreaBill\Interfaces\Customer $object
	 * @param bool $is_manual_request
	 *
	 * @return \WP_Error|boolean
	 */
	public function sync( &$object, $is_manual_request = true ) {
		if ( ! $this->is_syncable( $object ) ) {
			return new \WP_Error( 'sync-error', _x( 'This object cannot be synced', 'lexoffice', 'woocommerce-germanized-pro' ) );
		}

		if ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Customer' ) ) {
			return $this->sync_customer( $object );
		} elseif ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
			if ( 'simple' === $object->get_invoice_type() ) {
				return $this->sync_invoice( $object );
			} elseif ( 'cancellation' === $object->get_invoice_type() ) {
				return $this->sync_cancellation( $object );
			}
		}

		return new \WP_Error( 'sync-error', _x( 'This object type is not supported by lexware', 'lexoffice', 'woocommerce-germanized-pro' ) );
	}

	public function is_syncable( $object ) {
		$is_syncable = parent::is_syncable( $object );

		/**
		 * Lexoffice does not support free orders
		 */
		if ( $is_syncable && is_a( $object, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
			if ( 0.0 === (float) $object->get_total() || $object->get_voucher_total() > 0 ) {
				$is_syncable = false;
			}
		}

		return $is_syncable;
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 */
	public function get_customer_details( $customer ) {
		if ( $customer->has_been_externally_synced( self::get_name() ) ) {
			$sync_data = $customer->get_external_sync_handler_data( self::get_name() );
			$result    = $this->get_api()->get_contact( $sync_data->get_id() );

			if ( ! $this->get_api()->has_failed( $result ) ) {
				$customer        = $result;
				$customer_number = $customer['roles']['customer']['number'];

				if ( isset( $customer['company'] ) ) {
					/* translators: 1: customer company 2: customer number */
					$label = sprintf( esc_html_x( '%1$s (%2$s)', 'lexoffice', 'woocommerce-germanized-pro' ), $customer['company']['name'], $customer_number );
				} else {
					/* translators: 1: first name 2: last name 3: customer number */
					$label = sprintf( esc_html_x( '%1$s %2$s (%3$s)', 'lexoffice', 'woocommerce-germanized-pro' ), $customer['person']['firstName'], $customer['person']['lastName'], $customer_number );
				}

				return array(
					'id'    => $sync_data->get_id(),
					'label' => $label,
					'url'   => $this->get_customer_link( $sync_data->get_id() ),
				);
			}
		}

		return false;
	}

	public function get_customer_link( $id ) {
		return trailingslashit( Package::get_app_url() ) . 'customer/#/' . $id;
	}

	public function search_customers( $search ) {
		$result   = $this->get_api()->search_contacts( $search );
		$response = array();

		if ( ! $this->get_api()->has_failed( $result ) ) {
			foreach ( $result['content'] as $customer ) {
				$customer_number = $customer['roles']['customer']['number'];

				if ( isset( $customer['company'] ) ) {
					/* translators: 1: customer company 2: customer number */
					$label = sprintf( esc_html_x( '%1$s (%2$s)', 'lexoffice', 'woocommerce-germanized-pro' ), $customer['company']['name'], $customer_number );
				} else {
					/* translators: 1: first name 2: last name 3: customer number */
					$label = sprintf( esc_html_x( '%1$s %2$s (%3$s)', 'lexoffice', 'woocommerce-germanized-pro' ), $customer['person']['firstName'], $customer['person']['lastName'], $customer_number );
				}

				$response[ $customer['id'] ] = $label;
			}
		}

		return $response;
	}

	/**
	 * Whether to use a collective contact instead of individually
	 * creating and/or updating contacts while creating/updating vouchers
	 *
	 * @param bool|Invoice $invoice
	 *
	 * @return bool
	 */
	protected function use_collective_customer( $invoice = false ) {
		$use_collective_customer = 'yes' === $this->get_setting( 'voucher_link_contacts' ) ? false : true;

		/**
		 * Maybe force individual customers for some invoices
		 */
		if ( $invoice && ( $this->invoice_supports_eu_taxation( $invoice ) || $this->is_vat_exempt( $invoice ) || $this->is_third_country( $invoice ) ) ) {
			$use_collective_customer = false;
		}

		return $use_collective_customer;
	}

	/**
	 * @return bool
	 */
	protected function force_creating_customers() {
		return 'yes' === $this->get_setting( 'voucher_force_link_contacts' ) ? true : false;
	}

	protected function is_northern_ireland( $country, $postcode ) {
		$postcode = strtoupper( $postcode );

		return 'GB' === $country && substr( $postcode, 0, 2 ) === 'BT';
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 */
	protected function sync_customer( $customer ) {
		if ( ! is_a( $customer, 'Vendidero\StoreaBill\Lexoffice\Customer' ) ) {
			$customer = new Customer( $customer );
		}

		$shipping_country  = $customer->has_shipping_address() ? $customer->get_shipping_country() : $customer->get_billing_country();
		$shipping_postcode = $customer->has_shipping_address() ? $customer->get_shipping_postcode() : $customer->get_billing_postcode();

		$request = array(
			'roles'          => array(
				'customer' => array( 'number' => '' ),
			),
			'addresses'      => array(
				'billing'  => array(
					array(
						'supplement'  => $customer->get_billing_address_2(),
						'street'      => $customer->get_billing_address(),
						'zip'         => $customer->get_billing_postcode(),
						'city'        => $customer->get_billing_city(),
						'countryCode' => $this->is_northern_ireland( $customer->get_billing_country(), $customer->get_billing_postcode() ) ? 'IX' : $customer->get_billing_country(),
					),
				),
				'shipping' => array(
					array(
						'supplement'  => $customer->has_shipping_address() ? $customer->get_shipping_address_2() : $customer->get_billing_address_2(),
						'street'      => $customer->has_shipping_address() ? $customer->get_shipping_address() : $customer->get_billing_address(),
						'zip'         => $shipping_postcode,
						'city'        => $customer->has_shipping_address() ? $customer->get_shipping_city() : $customer->get_billing_city(),
						'countryCode' => $this->is_northern_ireland( $shipping_country, $shipping_postcode ) ? 'IX' : $shipping_country,
					),
				),
			),
			'emailAddresses' => array(),
			'note'           => _x( 'WooCommerce customer', 'lexoffice', 'woocommerce-germanized-pro' ),
		);

		if ( ! $customer->is_business() ) {
			$request['person'] = array(
				'firstName'  => $customer->get_first_name(),
				'lastName'   => $customer->get_last_name(),
				'salutation' => $customer->get_formatted_title(),
			);

			$request['emailAddresses']['other'] = array( $customer->get_email() );

			if ( $customer->get_phone() ) {
				$request['phoneNumbers'] = array(
					'other' => array( $customer->get_phone() ),
				);
			}
		} else {
			$request['company'] = array(
				'name'                 => $customer->get_company_name(),
				'allowTaxFreeInvoices' => $customer->is_vat_exempt(),
				'contactPersons'       => array(),
			);

			/**
			 * Do not transfer swiss VAT Ids as lexoffice does not support them
			 */
			if ( ! empty( $customer->get_vat_id() ) && 'CHE' !== substr( $customer->get_vat_id(), 0, 3 ) ) {
				$request['company']['vatRegistrationId'] = $customer->get_vat_id();
			}

			$contact = array(
				'firstName'    => $customer->get_first_name(),
				'lastName'     => $customer->get_last_name(),
				'emailAddress' => $customer->get_email(),
				'phoneNumber'  => $customer->get_phone(),
				'primary'      => true,
				'salutation'   => $customer->get_formatted_title(),
			);

			$request['emailAddresses']['business'] = array( $customer->get_email() );
			$request['company']['contactPersons']  = array( $contact );

			if ( $customer->get_phone() ) {
				$request['phoneNumbers'] = array(
					'business' => array( $customer->get_phone() ),
				);
			}
		}

		if ( ! $this->has_synced( $customer ) && apply_filters( "{$this->get_hook_prefix()}contact_enable_account_matching", true ) ) {
			$existing = $this->get_api()->find_contact( $customer );

			if ( ! $this->get_api()->has_failed( $existing ) ) {
				$customer->update_external_sync_handler(
					static::get_name(),
					array(
						'id'      => $existing['id'],
						'version' => $existing['version'],
					)
				);
			}
		}

		if ( $this->has_synced( $customer ) ) {
			$data        = $customer->get_external_sync_handler_data( static::get_name() );
			$remote_data = $this->get_api()->get_contact( $data->get_id() );

			if ( $this->get_api()->is_404( $remote_data ) ) {
				$result = $this->get_api()->create_contact( $request );
			} elseif ( $this->get_api()->has_failed( $remote_data ) ) {
				$result = $remote_data;
			} else {
				$request['version'] = absint( $remote_data['version'] );

				/**
				 * Do not override allowTaxFreeInvoices in case remote data has been manually
				 * set to allow tax free invoices, e.g. for third countries.
				 */
				if ( isset( $remote_data['company'] ) && isset( $remote_data['company']['allowTaxFreeInvoices'] ) ) {
					if ( $remote_data['company']['allowTaxFreeInvoices'] ) {
						$request['company']['allowTaxFreeInvoices'] = $remote_data['company']['allowTaxFreeInvoices'];
					}
				}

				/**
				 * Do not transmit vat id in case the customer does not apply for vat exempt as lexware does not support
				 * vat ids on billing/shipping level.
				 */
				if ( ! $customer->is_vat_exempt() ) {
					unset( $request['company']['vatRegistrationId'] );
				}

				$result = $this->get_api()->update_contact( $data->get_id(), $request );

				/**
				 * Lexware does not allow removing the vat id for an existing contact with linked vouchers that include reverse charges.
				 * Solution: Create a new contact instead.
				 */
				if ( is_wp_error( $result ) && 406 === $result->get_error_code() ) {
					$has_vat_id              = ! empty( $remote_data['company']['vatRegistrationId'] );
					$contact_supports_vat_id = $customer->is_vat_exempt();

					if ( $contact_supports_vat_id !== $has_vat_id ) {
						unset( $request['version'] );
						$request['company']['allowTaxFreeInvoices'] = $customer->is_vat_exempt();

						$result = $this->get_api()->create_contact( $request );
					}
				}
			}
		} else {
			if ( ! $customer->is_vat_exempt() ) {
				unset( $request['company']['vatRegistrationId'] );
			}

			$result = $this->get_api()->create_contact( $request );
		}

		if ( ! is_wp_error( $result ) ) {
			$customer->update_external_sync_handler(
				static::get_name(),
				array(
					'id'      => $result->get( 'id' ),
					'version' => $result->get( 'version' ),
				)
			);

			return true;
		} else {
			return $result;
		}
	}

	protected function sync_cancellation( &$invoice ) {
		return $this->sync_invoice( $invoice );
	}

	/**
	 * @param Invoice $invoice
	 */
	protected function is_vat_exempt( $invoice ) {
		return $invoice->is_vat_exempt();
	}

	/**
	 * @param Invoice $invoice
	 */
	protected function is_third_country( $invoice ) {
		return Countries::is_third_country( $invoice->get_country(), $invoice->get_postcode() );
	}

	/**
	 * @param Invoice $invoice
	 *
	 * @return boolean
	 */
	protected function voucher_needs_contact( $invoice ) {
		return ( ! $this->is_small_business() && ( $this->is_vat_exempt( $invoice ) || $this->is_third_country( $invoice ) ) );
	}

	/**
	 * @param Invoice $invoice
	 */
	protected function force_company_contact_existence( $invoice ) {
		return ( ! $this->is_small_business() && ( $this->is_vat_exempt( $invoice ) || $this->is_third_country( $invoice ) ) );
	}

	/**
	 * @param Simple|Cancellation $invoice
	 *
	 * @return bool|\WP_Error
	 */
	protected function sync_invoice( &$invoice ) {
		$total_tax = sab_format_decimal( $invoice->get_total_tax(), 2 );
		$result    = false;

		$voucher_number = $invoice->get_formatted_number();

		if ( 'order_number' === $this->get_setting( 'invoice_voucher_number_type' ) && ! empty( $invoice->get_order_number() ) ) {
			$voucher_number = $invoice->get_order_number();
		}

		$shipping_date = $invoice->get_date_created()->date_i18n( 'Y-m-d' );

		if ( $invoice->get_date_of_service_end() ) {
			$shipping_date = $invoice->get_date_of_service_end()->date_i18n( 'Y-m-d' );
		} elseif ( $invoice->get_date_of_service() ) {
			$shipping_date = $invoice->get_date_of_service()->date_i18n( 'Y-m-d' );
		}

		$request = array(
			'type'                 => 'cancellation' === $invoice->get_invoice_type() ? 'salescreditnote' : 'salesinvoice',
			'voucherNumber'        => apply_filters( "{$this->get_hook_prefix()}voucher_number", $voucher_number, $invoice ),
			'voucherDate'          => $invoice->get_date_created()->date_i18n( 'Y-m-d' ),
			'shippingDate'         => apply_filters( "{$this->get_hook_prefix()}voucher_shipping_date", $shipping_date, $invoice ),
			'dueDate'              => $invoice->get_date_due() ? $invoice->get_date_due()->date_i18n( 'Y-m-d' ) : $invoice->get_date_created()->date_i18n( 'Y-m-d' ),
			'totalGrossAmount'     => sab_format_decimal( $invoice->get_total(), 2 ),
			'totalTaxAmount'       => $total_tax,
			'taxType'              => $invoice->prices_include_tax() ? 'gross' : 'net',
			'useCollectiveContact' => $this->use_collective_customer( $invoice ),
			'voucherItems'         => array(),
			'remark'               => '',
		);

		$remark_data            = $this->get_invoice_remark_data( $invoice );
		$this->invoice_customer = false;

		if ( ! $this->use_collective_customer( $invoice ) ) {
			$request['contactId'] = '';
			$force_valid_customer = $this->invoice_supports_eu_taxation( $invoice ) || $this->is_vat_exempt( $invoice ) || $this->is_third_country( $invoice );
			$invoice_customer     = false;

			/**
			 * (Re)sync the customer
			 */
			if ( $customer = $invoice->get_customer() ) {
				$invoice_customer = new \Vendidero\StoreaBill\Lexoffice\Customer( $customer );
				/**
				 * Prefer current invoice data when syncing customer
				 */
				$invoice_customer->populate_by_invoice( $invoice );

				$customer_result = $this->sync_customer( $invoice_customer );

				if ( ! is_wp_error( $customer_result ) && ( $customer_sync_data = $invoice_customer->get_external_sync_handler_data( self::get_name() ) ) ) {
					$request['contactId'] = $customer_sync_data->get_id();
				} elseif ( is_wp_error( $customer_result ) ) {
					if ( $force_valid_customer ) {
						return $customer_result;
					} else {
						foreach ( $customer_result->get_error_messages() as $message ) {
							Package::log( sprintf( 'The following error occurred while syncing customer data for %s - need to use collective customer instead: %s', $invoice->get_title(), $message ), 'error' );
						}
					}
				}
			} elseif ( $this->voucher_needs_contact( $invoice ) || $this->force_creating_customers() || $force_valid_customer ) {
				$invoice_customer = new \Vendidero\StoreaBill\Lexoffice\Customer( $invoice );
				$customer_result  = $this->sync_customer( $invoice_customer );

				if ( ! is_wp_error( $customer_result ) && ( $customer_sync_data = $invoice_customer->get_external_sync_handler_data( self::get_name() ) ) ) {
					$request['contactId'] = $customer_sync_data->get_id();
				} elseif ( is_wp_error( $customer_result ) ) {
					if ( $force_valid_customer ) {
						return $customer_result;
					} else {
						foreach ( $customer_result->get_error_messages() as $message ) {
							Package::log( sprintf( 'The following error occurred while syncing customer data for %s - need to use collective customer instead: %s', $invoice->get_title(), $message ), 'error' );
						}
					}
				}
			}

			/**
			 * In case syncing the customer fails for some reason (e.g. guest) - use collective contact as fallback
			 */
			if ( empty( $request['contactId'] ) ) {
				$request['useCollectiveContact'] = true;
				unset( $request['contactId'] );
			} elseif ( $invoice_customer ) {
				$this->invoice_customer = $invoice_customer;
			}
		}

		$items             = array();
		$main_category_ids = array();

		/**
		 * Build a map of main category ids to be used as reference
		 * while detecting category ids for items later on.
		 */
		foreach ( $invoice->get_items( 'product' ) as $item ) {
			$main_category_ids[] = $this->get_category_id( $item, $invoice );
		}

		$main_category_ids = array_unique( $main_category_ids );

		/**
		 * Build up items array. Items are being merged by category ID and tax rate.
		 */
		foreach ( $invoice->get_items( $invoice->get_item_types_for_totals() ) as $item ) {
			$category_id = $this->get_category_id( $item, $invoice, $main_category_ids );
			$total_tax   = Numbers::round_to_precision( $item->get_total_tax(), 2 );
			$item_total  = Numbers::round_to_precision( $item->get_total(), 2 );

			/**
			 * Lexoffice cannot handle free items
			 */
			if ( 0.0 === $item_total ) {
				continue;
			}

			if ( 0.0 !== $total_tax ) {
				foreach ( $item->get_taxes() as $tax ) {
					$percentage       = strval( $tax->get_tax_rate()->get_percent() );
					$voucher_item_key = $category_id . '_' . $percentage;
					$total            = $invoice->prices_include_tax() ? $tax->get_total_net() + $tax->get_total_tax() : $tax->get_total_net();

					if ( ! $invoice->round_tax_at_subtotal() ) {
						$total = Numbers::round_to_precision( $total, 2 );
					}

					$total      = sab_add_number_precision( $total, false );
					$tax_amount = sab_add_number_precision( $tax->get_total_tax(), false );

					/**
					 * Skip tax items with zero (tax) total.
					 */
					if ( 0.0 === $total || 0.0 === $tax_amount ) {
						continue;
					}

					if ( array_key_exists( $voucher_item_key, $items ) ) {
						$items[ $voucher_item_key ]['amount']    += $total;
						$items[ $voucher_item_key ]['taxAmount'] += $tax_amount;
					} else {
						$items[ $voucher_item_key ] = array(
							'amount'         => $total,
							'taxAmount'      => $tax_amount,
							'taxRatePercent' => strval( $tax->get_tax_rate()->get_percent() ),
							'categoryId'     => $category_id,
						);
					}
				}
			} else {
				$voucher_item_key = $category_id . '_0';

				if ( array_key_exists( $voucher_item_key, $items ) ) {
					$items[ $voucher_item_key ]['amount'] += sab_add_number_precision( $item->get_total(), false );
				} else {
					$items[ $voucher_item_key ] = array(
						'amount'         => sab_add_number_precision( $item->get_total(), false ),
						'taxAmount'      => 0,
						'taxRatePercent' => strval( 0.0 ),
						'categoryId'     => $category_id,
					);
				}
			}
		}

		$column_total_tax = 0.0;
		$total_tax_diff   = 0.0;
		$gross_total      = 0.0;

		/**
		 * Format totals.
		 */
		foreach ( $items as $voucher_item_key => $item ) {
			$items[ $voucher_item_key ]['amount']    = Numbers::round_to_precision( sab_remove_number_precision( $item['amount'] ), 2 );
			$items[ $voucher_item_key ]['taxAmount'] = Numbers::round_to_precision( sab_remove_number_precision( $item['taxAmount'] ), 2 );

			/**
			 * Recalculate taxes per column to make sure valid items are constructed.
			 * Lexoffice uses column-based tax calculation. Woo uses row-based tax calculation.
			 * That will inevitably lead to rounding differences.
			 *
			 * https://developers.lexoffice.io/partner/cookbooks/bookkeeping/#berechnung-der-steuerbetrage-spaltenmethode
			 */
			if ( ! empty( $item['taxRatePercent'] ) ) {
				$rates        = array( new TaxRate( array( 'percent' => $item['taxRatePercent'] ) ) );
				$column_taxes = Tax::calc_tax( $items[ $voucher_item_key ]['amount'], $rates, $invoice->prices_include_tax() );
				$column_tax   = Numbers::round_to_precision( array_sum( $column_taxes ) );
				$tax_diff     = Numbers::round_to_precision( $items[ $voucher_item_key ]['taxAmount'] - $column_tax );

				$total_tax_diff   += $tax_diff;
				$column_total_tax += $column_tax;

				$items[ $voucher_item_key ]['taxAmount'] = Numbers::round_to_precision( $column_tax, 2 );
			}

			$gross_total += ( $invoice->prices_include_tax() ? $items[ $voucher_item_key ]['amount'] : ( $items[ $voucher_item_key ]['amount'] + $items[ $voucher_item_key ]['taxAmount'] ) );

			// Convert floats to strings
			$items[ $voucher_item_key ]['amount']    = sab_format_decimal( $items[ $voucher_item_key ]['amount'], 2 );
			$items[ $voucher_item_key ]['taxAmount'] = sab_format_decimal( $items[ $voucher_item_key ]['taxAmount'], 2 );
		}

		$total_tax_diff = Numbers::round_to_precision( $total_tax_diff, 2 );

		/**
		 * Add remark containing tax rounding difference.
		 */
		if ( ! empty( $total_tax_diff ) ) {
			if ( ! $invoice->prices_include_tax() ) {
				if ( $total_tax_diff < 0 ) {
					/**
					 * In case the total tax diff is negative there is no other option
					 * than increasing the total gross amount instead.
					 */
					$request['totalGrossAmount'] = sab_format_decimal( $request['totalGrossAmount'] + abs( $total_tax_diff ), 2 );
				} elseif ( $request['totalGrossAmount'] > $gross_total ) {
					/**
					 * In case the calculated gross total (from the items containing adjusted column-wise tax totals)
					 * is smaller than the actual gross total there is no other way but to adjust the gross amount and tax amount to be transmitted to lexoffice.
					 */
					$request['totalTaxAmount']   = sab_format_decimal( $request['totalTaxAmount'] - abs( $total_tax_diff ), 2 );
					$request['totalGrossAmount'] = sab_format_decimal( $gross_total, 2 );
				} elseif ( ! $invoice->is_oss() ) {
					$items[] = array(
						'amount'         => sab_format_decimal( $total_tax_diff, 2 ),
						'taxAmount'      => sab_format_decimal( 0.0, 2 ),
						'taxRatePercent' => strval( 0.0 ),
						'categoryId'     => 'aba9020f-d0a6-47ca-ace6-03d6ed492351',
					);
				} else {
					$request['totalTaxAmount']   = sab_format_decimal( $request['totalTaxAmount'] + $total_tax_diff, 2 );
					$request['totalGrossAmount'] = sab_format_decimal( $gross_total, 2 );
				}
			}

			$remark_data[] = sprintf( _x( 'Tax round difference: %s', 'lexoffice', 'woocommerce-germanized-pro' ), $total_tax_diff );
		} elseif ( Numbers::round_to_precision( $gross_total, 2 ) !== Numbers::round_to_precision( $request['totalGrossAmount'], 2 ) ) {
			$gross_total_diff = Numbers::round_to_precision( Numbers::round_to_precision( $gross_total, 2 ) - Numbers::round_to_precision( $request['totalGrossAmount'], 2 ), 2 );

			/**
			 * There is no other option available than reducing the actual gross amount of the voucher.
			 */
			$request['totalGrossAmount'] = sab_format_decimal( $gross_total, 2 );

			$remark_data[] = sprintf( _x( 'Gross total rounding difference: %s', 'lexoffice', 'woocommerce-germanized-pro' ), $gross_total_diff );
		}

		/**
		 * Override tax with column-based taxes.
		 */
		$request['totalTaxAmount'] = sab_format_decimal( $column_total_tax, 2 );

		/**
		 * Flatten the array
		 */
		$request['voucherItems'] = array_values( $items );

		/**
		 * Format remark
		 */
		$request['remark'] = apply_filters( "{$this->get_hook_prefix()}voucher_remark", $this->get_invoice_remark( $remark_data ), $invoice );

		$request = apply_filters( "{$this->get_hook_prefix()}voucher", $request, $invoice );

		if ( $this->has_synced( $invoice ) ) {
			$data        = $invoice->get_external_sync_handler_data( static::get_name() );
			$remote_data = $this->get_api()->get_voucher( $data->get_id() );

			if ( $this->get_api()->is_404( $remote_data ) ) {
				$result = $this->parse_response( $this->get_api()->create_voucher( $request ) );
			} elseif ( $this->get_api()->has_failed( $remote_data ) ) {
				$result = $remote_data;
			} else {
				$request['version'] = absint( $remote_data['version'] );

				/**
				 * Transmit existing file to make sure the voucher PDF
				 * file is not freed/removed from the voucher.
				 */
				$request['files'] = $remote_data['files'];

				$result = $this->parse_response( $this->get_api()->update_voucher( $data->get_id(), $request ) );
			}
		} else {
			$result = $this->parse_response( $this->get_api()->create_voucher( $request ) );
		}

		if ( ! is_wp_error( $result ) ) {
			$id      = $result->get( 'id' );
			$version = $result->get( 'version' );

			/**
			 * Lazily upload file
			 */
			if ( $invoice->has_primary_file( 'sync' ) && $id && ( ! isset( $request['files'] ) || empty( $request['files'] ) ) ) {
				$file_result = $this->get_api()->update_voucher_file( $id, $invoice->get_primary_path( 'sync' ) );

				/**
				 * Update local version.
				 */
				if ( ! is_wp_error( $file_result ) ) {
					$remote_data = $this->get_api()->get_voucher( $id );

					if ( ! $this->get_api()->has_failed( $remote_data ) ) {
						$version = absint( $remote_data['version'] );
					}
				} else {
					\Vendidero\StoreaBill\Package::extended_log( sprintf( 'Error while updating voucher file %1$s to %2$s', $invoice->get_primary_path( 'sync' ), $invoice->get_title() ) );
				}
			}

			/**
			 * Create transaction hint
			 */
			if ( $invoice->is_paid() && $invoice->get_payment_transaction_id() ) {
				$hint_result = $this->get_api()->create_voucher_transaction_hint( $id, $invoice->get_payment_transaction_id() );
			}

			$invoice = sab_get_invoice( $invoice->get_id() ); // Use a fresh instance to prevent version conflicts

			$invoice->update_external_sync_handler(
				static::get_name(),
				array(
					'id'      => $id,
					'version' => $version,
				)
			);

			return true;
		} else {
			\Vendidero\StoreaBill\Package::extended_log( sprintf( 'Original request that lead to lexware error: ' . sab_print_r( $request, true ) ) );

			return $result;
		}
	}

	protected function is_small_business() {
		return Countries::is_small_business();
	}

	/**
	 * @param Invoice $invoice
	 *
	 * @return bool
	 */
	protected function invoice_supports_eu_taxation( $invoice ) {
		$invoice_date = $invoice->get_date_of_service_end() ? $invoice->get_date_of_service_end()->date_i18n( 'Y-m-d' ) : $invoice->get_date_of_service()->date_i18n( 'Y-m-d' );

		if ( $invoice->is_eu_cross_border_taxable() && $invoice_date > new \WC_DateTime( '@' . strtotime( '2021-07-01 00:00:00' ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @see https://developers.lexoffice.io/partner/docs/#vouchers-endpoint-list-of-categoryids
	 *
	 * @param $item
	 * @param Invoice $invoice
	 * @param array $main_category_ids
	 */
	protected function get_category_id( $item, $invoice, $main_category_ids = array() ) {
		$categories = array(
			'products'                => '8f8664a8-fd86-11e1-a21f-0800200c9a66',
			'services'                => '8f8664a0-fd86-11e1-a21f-0800200c9a66',
			'revenues'                => '8f8664a1-fd86-11e1-a21f-0800200c9a66',
			'reverse_charge'          => '9075a4e3-66de-4795-a016-3889feca0d20',
			'reverse_charge_external' => '380a20cb-d04c-426e-b49c-84c22adfa362',
			'third_party'             => '93d24c20-ea84-424e-a731-5e1b78d1e6a9',
			'third_party_services'    => 'ef5b1a6e-f690-4004-9a19-91276348894f',
			'small_business'          => '7a1efa0e-6283-4cbf-9583-8e88d3ba5960',
			'eu_revenues'             => '7c112b66-0565-479c-bc18-5845e080880a',
			'eu_digital'              => 'd73b880f-c24a-41ea-a862-18d90e1c3d82',
			'eu_revenues_oss'         => '4ebd965a-7126-416c-9d8c-a5c9366ee473',
			'eu_digital_oss'          => '7ecea006-844c-4c98-a02d-aa3142640dd5',
			'solar_products'          => 'e188b015-58e0-4182-8419-96ba15cbb166',
		);

		$default_category_name = 'revenues';
		$category_name         = $default_category_name;

		if ( $this->is_small_business() ) {
			$category_name = 'small_business';
		} else {
			$is_service = false;
			$is_digital = false;

			if ( is_a( $item, '\Vendidero\StoreaBill\Invoice\ProductItem' ) ) {
				if ( $item->is_service() ) {
					$category_name = 'services';
					$is_service    = true;
				}

				if ( $item->is_virtual() ) {
					$is_digital = true;

					/**
					 * Non-virtual bundle compatibility + fallback check whether the
					 * product is downloadable but not virtual.
					 */
					if ( $product = $item->get_product() ) {
						$wc_product = $product->get_object();

						if ( is_a( $wc_product, 'WC_Product_Bundle' ) && is_callable( array( $wc_product, 'is_virtual_bundle' ) ) && ! $wc_product->is_virtual_bundle() ) {
							$is_digital = false;
						} elseif ( $wc_product->is_downloadable() && ! $wc_product->is_virtual() ) {
							$is_digital = false;
						}
					}
				}

				if ( $item->is_photovoltaic_system() && 'DE' === $invoice->get_taxable_country() && 0.0 === $item->get_total_tax() ) {
					$category_name = 'solar_products';
				}
			} elseif ( in_array( $categories['solar_products'], $main_category_ids, true ) && 0.0 === $item->get_total_tax() ) {
				/**
				 * Book additional items, e.g. shipping costs as solar_products too.
				 */
				$category_name = 'solar_products';
			}

			if ( $this->invoice_supports_eu_taxation( $invoice ) ) {
				if ( $invoice->is_oss() ) {
					$category_name = 'eu_revenues_oss';

					if ( $is_digital ) {
						$category_name = 'eu_digital_oss';
					}
				} else {
					$category_name = 'eu_revenues';

					if ( $is_digital ) {
						$category_name = 'eu_digital';
					}
				}

				/**
				 * Lexoffice does not allow mixing categories for distance sales, e.g.
				 * in case a digital product is sold and an additional service (fee) is booked as
				 * a revenue that won't work. Tweak: Book fees as digital too in case a digital product is included.
				 */
				if ( 'fee' === $item->get_item_type() && ! empty( $main_category_ids ) ) {
					$digital_category    = $invoice->is_oss() ? 'eu_digital_oss' : 'eu_digital';
					$digital_category_id = $categories[ $digital_category ];

					if ( in_array( $digital_category_id, $main_category_ids, true ) ) {
						$category_name = $digital_category;
					}
				}
			}

			/**
			 * These categories need a valid company customer to
			 * be linked to the voucher.
			 *
			 * @see https://developers.lexoffice.io/partner/cookbooks/bookkeeping/#kategorien-haufige-sonderfalle
			 */
			if ( $this->is_vat_exempt( $invoice ) ) {
				$category_name = 'reverse_charge';
			}

			if ( $this->is_third_country( $invoice ) ) {
				$category_name = 'third_party';
				$is_business   = $this->invoice_customer ? $this->invoice_customer->is_business() : false;

				/**
				 * The third_party_services may only be available for business customers.
				 * As a fallback use the normal services category which expects that the service
				 * is to be taxed at the base location for B2C transactions (e.g. Germany).
				 */
				if ( $is_service ) {
					if ( $is_business ) {
						$category_name = 'third_party_services';
					} else {
						$category_name = 'services';
					}
				}

				/**
				 * Book additional items as services too in case it's a service
				 */
				if ( in_array( $categories['third_party_services'], $main_category_ids, true ) ) {
					$category_name = 'third_party_services';
				}
			}
		}

		$category = $categories[ $category_name ];
		$category = apply_filters( "{$this->get_hook_prefix()}voucher_item_category_id", $category, $item, $invoice, $this );

		if ( ! in_array( $category, $categories, true ) ) {
			$category = $categories[ $default_category_name ];
		}

		return $category;
	}

	/**
	 * @param ExternalSyncable $object
	 */
	public function has_synced( $object ) {
		return $object->has_been_externally_synced( static::get_name() );
	}

	/**
	 * @param ExternalSyncable $object
	 *
	 * @return \WC_DateTime|null
	 */
	public function get_date_last_synced( $object ) {
		if ( $object->has_been_externally_synced( static::get_name() ) ) {
			if ( $data = $object->get_external_sync_handler_data( static::get_name() ) ) {
				return $data->get_last_updated();
			}
		}

		return null;
	}

	public function get_auth_api() {
		return $this->auth_api;
	}

	public function get_api() {
		return $this->api;
	}

	public function enable_auto_sync( $object_type ) {
		return 'yes' === $this->get_setting( "{$object_type}_enable_automation" );
	}

	/**
	 * @param \WP_Error|RESTResponse $response
	 *
	 * @return \WP_Error|RESTResponse
	 */
	public function parse_response( $response ) {
		$error = new \WP_Error();

		if ( ! is_wp_error( $response ) ) {
			if ( $response->is_error() ) {
				$code = $response->get_code();
				$body = $response->get_body();

				\Vendidero\StoreaBill\Package::extended_log( sprintf( 'Error (%s) while performing lexware request: ' . wc_print_r( $body, true ), $code ) );

				if ( $r_error = $response->get( 'error' ) ) {
					if ( 'invalid_grant' === $r_error ) {
						$error->add( $response->get_code(), _x( 'The authorization code is unknown', 'lexoffice', 'woocommerce-germanized-pro' ) );
					} elseif ( $response->get( 'message' ) ) {
						$error->add( $response->get_code(), sprintf( _x( 'The following error occurred during a lexware API call: %s', 'lexoffice', 'woocommerce-germanized-pro' ), esc_html( $response->get( 'message' ) ) ) );
					}
				} elseif ( $response->get( 'IssueList' ) ) {
					$list = (array) $response->get( 'IssueList' );

					foreach ( $list as $l ) {
						if ( in_array( $l['type'], array( 'validation_failure', 'bad_request_error' ), true ) ) {
							/* translators: 1: error type 2: error description 3: error source */
							$error->add( $response->get_code(), sprintf( _x( 'This voucher cannot be modified due to it\'s payment status or state (%1$s: %2$s: %3$s).', 'lexoffice', 'woocommerce-germanized-pro' ), $l['type'], $l['i18nKey'], $l['source'] ) );
						} else {
							/* translators: 1: error type 2: error description 3: error source */
							$error->add( $response->get_code(), sprintf( _x( 'Lexware %1$s: %2$s: %3$s', 'lexoffice', 'woocommerce-germanized-pro' ), $l['type'], $l['i18nKey'], $l['source'] ) );
						}
					}
				}

				if ( ! sab_wp_error_has_errors( $error ) ) {
					if ( $response->get_code() >= 500 ) {
						$error->add( $response->get_code(), _x( 'There seems to be an issue while contacting lexware. Seems like the API is currently not available.', 'lexoffice', 'woocommerce-germanized-pro' ) );
					} elseif ( 409 === $response->get_code() ) {
						$error->add( 409, _x( 'A version conflict has been detected. Please try again.', 'lexoffice', 'woocommerce-germanized-pro' ) );
					} elseif ( 404 === $response->get_code() ) {
						$error->add( 404, _x( 'The requested resource could not be found.', 'lexoffice', 'woocommerce-germanized-pro' ) );
					} elseif ( in_array( (int) $response->get_code(), array( 400, 401 ), true ) ) {
						$error->add( $response->get_code(), sprintf( _x( 'Seems like your connection to lexware was lost. Please <a href="%s">connect</a> to lexware.', 'lexoffice', 'woocommerce-germanized-pro' ), esc_url( $this->get_admin_url() ) ) );
					} elseif ( in_array( (int) $response->get_code(), array( 403 ), true ) ) {
						$error->add( $response->get_code(), sprintf( _x( 'Scope is missing. Please <a href="%s">refresh</a> your connection to lexware.', 'lexoffice', 'woocommerce-germanized-pro' ), esc_url( $this->get_admin_url() ) ) );
					} else {
						$error->add( $response->get_code(), _x( 'Error while connecting to lexware. Please make sure that your host allows outgoing connections to lexware.', 'lexoffice', 'woocommerce-germanized-pro' ) );
					}
				}
			}
		}

		return sab_wp_error_has_errors( $error ) ? $error : $response;
	}

	public function get_settings( $context = 'view' ) {
		$settings = parent::get_settings( $context );

		$settings['invoice_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Invoices', 'lexoffice', 'woocommerce-germanized-pro' ),
			'id'    => 'invoice_options',
		);

		$settings['invoice_enable_automation'] = array(
			'title'       => _x( 'Automation', 'lexoffice', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically transfer invoices to lexware.', 'lexoffice', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, invoices are transferred to lexoffice automatically as soon as the invoice is finalized.', 'lexoffice', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		$settings['invoice_voucher_number_type'] = array(
			'title'       => _x( 'Number type', 'lexoffice', 'woocommerce-germanized-pro' ),
			'type'        => 'select',
			'description' => '<div class="sab-additional-desc">' . _x( 'Choose whether to submit the invoice number or order number as voucher number. By default lexware does only allow syncing payments by the voucher number. In case your customers use the order number as payment indicator you should consider using the order number here too.', 'lexoffice', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'document_number',
			'options'     => array(
				'document_number' => _x( 'Invoice number', 'lexoffice', 'woocommerce-germanized-pro' ),
				'order_number'    => _x( 'Order number', 'lexoffice', 'woocommerce-germanized-pro' ),
			),
		);

		$settings['invoice_cancellation_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Cancellations', 'lexoffice', 'woocommerce-germanized-pro' ),
			'id'    => 'cancellation_options',
		);

		$settings['invoice_cancellation_enable_automation'] = array(
			'title'       => _x( 'Automation', 'lexoffice', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically transfer cancellations to lexware.', 'lexoffice', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, cancellations are transferred to lexoffice automatically as soon as the cancellation is finalized.', 'lexoffice', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		$settings['customer_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Customers', 'lexoffice', 'woocommerce-germanized-pro' ),
			'id'    => 'customer_options',
		);

		$settings['customer_enable_automation'] = array(
			'title'       => _x( 'Automation', 'lexoffice', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Create and/or update contacts as soon as customer data changes.', 'lexoffice', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, customers are transferred to lexoffice automatically as soon as a customer is created or updated.', 'lexoffice', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		$settings['voucher_link_contacts'] = array(
			'title'             => _x( 'Link Contacts', 'lexoffice', 'woocommerce-germanized-pro' ),
			'type'              => 'sab_toggle',
			'description'       => _x( 'Link vouchers (invoices, cancellations) with individually created contacts.', 'lexoffice', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'Your vouchers are linked to individually created contacts. By disabling this option your vouchers will be linked to a collective contact instead.', 'lexoffice', 'woocommerce-germanized-pro' ) . '</div>',
			'default'           => 'yes',
			'custom_attributes' => array(
				'data-show_if_sync_handler_lexoffice_customer_enable_sync' => '',
			),
		);

		$settings['voucher_force_link_contacts'] = array(
			'title'             => _x( 'Guest Checkouts', 'lexoffice', 'woocommerce-germanized-pro' ),
			'type'              => 'sab_toggle',
			'description'       => _x( 'Force creating individual lexware customers for guest checkouts too.', 'lexoffice', 'woocommerce-germanized-pro' ),
			'default'           => 'no',
			'custom_attributes' => array(
				'data-show_if_sync_handler_lexoffice_voucher_link_contacts' => '',
			),
		);

		return $settings;
	}
}
