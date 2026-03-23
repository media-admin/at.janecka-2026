<?php

namespace Vendidero\StoreaBill\sevDesk;

use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\ExternalSync\SyncHandler;
use Vendidero\StoreaBill\Interfaces\ExternalSyncable;
use Vendidero\StoreaBill\Invoice\Cancellation;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Item;
use Vendidero\StoreaBill\Invoice\ProductItem;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\References\Product;
use Vendidero\StoreaBill\Utilities\Numbers;
use Vendidero\StoreaBill\WooCommerce\Helper;
use Vendidero\StoreaBill\sevDesk\API\Auth;
use Vendidero\StoreaBill\sevDesk\API\Models;

defined( 'ABSPATH' ) || exit;

class Sync extends SyncHandler {

	/**
	 * @var Auth
	 */
	protected $auth_api = null;

	/**
	 * @var string[]
	 */
	protected $accounts = null;

	/**
	 * @var string[]
	 */
	protected $categories = null;

	protected $default_account = false;

	/**
	 * @var Models
	 */
	protected $api = null;

	public static function get_name() {
		return 'sevdesk';
	}

	public static function get_title() {
		return _x( 'sevdesk', 'sevdesk', 'woocommerce-germanized-pro' );
	}

	public static function get_description() {
		return _x( 'Transfer invoices and customer data to your sevdesk account.', 'sevdesk', 'woocommerce-germanized-pro' );
	}

	public static function get_supported_object_types() {
		return array( 'customer', 'invoice', 'invoice_cancellation' );
	}

	public static function get_api_type() {
		return 'REST';
	}

	public static function get_help_link() {
		return 'https://vendidero.de/dokument/sevdesk-schnittstelle';
	}

	public static function get_icon() {
		return trailingslashit( Package::get_url() ) . 'assets/icon.png';
	}

	public function __construct() {
		$this->auth_api = new Auth( $this );
		$this->api      = new Models( $this, $this->get_hook_prefix() );

		parent::__construct();
	}

	public function is_sandbox() {
		return false;
	}

	public function get_auth_token() {
		return $this->get_setting( 'token' );
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Invoice|\Vendidero\StoreaBill\Interfaces\Customer $object
	 * @param bool $is_manual_request
	 *
	 * @return \WP_Error|boolean
	 */
	public function sync( &$object, $is_manual_request = true ) {
		if ( ! $this->is_syncable( $object ) ) {
			return new \WP_Error( 'sync-error', _x( 'This object cannot be synced', 'sevdesk', 'woocommerce-germanized-pro' ) );
		}

		if ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Customer' ) ) {
			return $this->sync_customer( $object );
		} elseif ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
			if ( 'simple' === $object->get_invoice_type() ) {
				return $this->sync_invoice( $object, $is_manual_request );
			} elseif ( 'cancellation' === $object->get_invoice_type() ) {
				return $this->sync_cancellation( $object, $is_manual_request );
			}
		}

		return new \WP_Error( 'sync-error', _x( 'This object type is not supported by sevdesk', 'sevdesk', 'woocommerce-germanized-pro' ) );
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 */
	public function get_customer_details( $customer ) {
		if ( $customer->has_been_externally_synced( self::get_name() ) ) {
			$sync_data = $customer->get_external_sync_handler_data( self::get_name() );
			$result    = $this->get_api()->get_contact( $sync_data->get_id() );

			if ( ! $this->get_api()->has_failed( $result ) ) {
				$customer        = $result['objects'][0];
				$customer_number = ! empty( $customer['customerNumber'] ) ? $customer['customerNumber'] : $customer['id'];

				return array(
					'id'    => $sync_data->get_id(),
					/* translators: 1: customer first name 2: customer last name 3: customer number */
					'label' => sprintf( esc_html_x( '%1$s %2$s (%3$s)', 'sevdesk', 'woocommerce-germanized-pro' ), $customer['surename'], $customer['familyname'], $customer_number ),
					'url'   => $this->get_customer_link( $sync_data->get_id() ),
				);
			}
		}

		return false;
	}

	public function get_customer_link( $id ) {
		return trailingslashit( Package::get_app_url() ) . 'crm/detail/id/' . $id;
	}

	public function search_customers( $search ) {
		$results  = $this->get_api()->search_contacts( $search );
		$response = array();

		if ( ! $this->get_api()->has_failed( $results ) ) {
			foreach ( $results['objects'] as $customer ) {
				/**
				 * Exclude companies from searching.
				 */
				if ( isset( $customer['name'] ) && ! empty( $customer['name'] ) ) {
					continue;
				}

				$customer_number = ! empty( $customer['customerNumber'] ) ? $customer['customerNumber'] : $customer['id'];
				/* translators: 1: customer first name 2: customer last name 3: customer number */
				$response[ $customer['id'] ] = esc_html( sprintf( _x( '%1$s %2$s (%3$s)', 'sevdesk', 'woocommerce-germanized-pro' ), $customer['surename'], $customer['familyname'], $customer_number ) );
			}
		}

		return $response;
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 */
	protected function sync_customer( $customer ) {
		$data = array(
			'customer'         => $customer,
			'first_name'       => $customer->get_first_name(),
			'last_name'        => $customer->get_last_name(),
			'title'            => $customer->get_formatted_title(),
			'academic_title'   => '',
			'email'            => $customer->get_email(),
			'phone'            => $customer->get_phone(),
			'company'          => $customer->get_company_name(),
			'number'           => $customer->get_id(),
			'vat_id'           => $customer->get_vat_id(),
			'is_vat_exempt'    => $customer->is_vat_exempt(),
			'address'          => array(
				'street'  => $customer->get_billing_address(),
				'zip'     => $customer->get_billing_postcode(),
				'country' => $customer->get_billing_country(),
				'city'    => $customer->get_billing_city(),
			),
			'shipping_address' => array(
				'street'  => $customer->get_shipping_address(),
				'zip'     => $customer->get_shipping_postcode(),
				'country' => $customer->get_shipping_country(),
				'city'    => $customer->get_shipping_city(),
			),
		);

		$contact = new Contact( apply_filters( "{$this->get_hook_prefix()}contact_data", $data, $customer, $this ), $this->get_api(), $customer->get_external_sync_handler_data( self::get_name() ) );
		$result  = $contact->save();

		if ( true === $result ) {
			$customer->update_external_sync_handler( self::get_name(), $contact->get_sync_data() );
		}

		return $result;
	}

	/**
	 * @param Cancellation $invoice
	 * @param bool $is_manual_request
	 *
	 * @return bool|\WP_Error
	 */
	protected function sync_cancellation( &$invoice, $is_manual_request = true ) {
		return $this->sync_invoice( $invoice, $is_manual_request );
	}

	/**
	 * @param Simple|Cancellation $invoice
	 * @param bool $is_manual_request
	 *
	 * @return bool|\WP_Error
	 */
	protected function sync_invoice( &$invoice, $is_manual_request = true ) {
		$customer_id  = false;
		$address_data = $invoice->get_address();
		$title        = isset( $address_data['title'] ) ? $address_data['title'] : '';

		$customer_data = array(
			'customer'       => $invoice->get_customer(),
			'first_name'     => $invoice->get_first_name(),
			'last_name'      => $invoice->get_last_name(),
			'email'          => $invoice->get_email( 'edit' ),
			'phone'          => $invoice->get_phone(),
			'company'        => $invoice->get_company(),
			'title'          => $title,
			'academic_title' => '',
			'vat_id'         => $invoice->get_vat_id(),
			'is_vat_exempt'  => $invoice->is_vat_exempt(),
			'address'        => array(
				'street'  => $invoice->get_address_1(),
				'zip'     => $invoice->get_postcode(),
				'country' => $invoice->get_country(),
				'city'    => $invoice->get_city(),
			),
		);

		if ( $invoice->has_differing_shipping_address() ) {
			$address = $invoice->get_shipping_address();

			$customer_data['shipping_address'] = array(
				'street'  => isset( $address['address_1'] ) ? $address['address_1'] : null,
				'zip'     => isset( $address['postcode'] ) ? $address['postcode'] : null,
				'country' => $invoice->get_shipping_country(),
				'city'    => isset( $address['city'] ) ? $address['city'] : null,
			);
		}

		$sync_data          = $invoice->get_external_sync_handler_data( self::get_name() );
		$customer_sync_data = array();

		if ( $sync_data ) {
			$customer_sync_data = (array) $sync_data->get( 'customer' );
		}

		/**
		 * If the customer exists, prefer the synced customer data.
		 */
		if ( ( $customer = $invoice->get_customer() ) && $customer->has_been_externally_synced( self::get_name() ) ) {
			$existing_customer_sync_data = $customer->get_external_sync_handler_data( self::get_name() );
			$customer_sync_data          = array_replace_recursive( $customer_sync_data, $existing_customer_sync_data->get_data() );
		}

		$contact = new Contact( apply_filters( "{$this->get_hook_prefix()}invoice_customer_data", $customer_data, $invoice, $this ), $this->api, $customer_sync_data );
		$result  = $contact->save();

		if ( ! is_wp_error( $result ) ) {
			$customer_id = $contact->get_id();

			$invoice = sab_get_invoice( $invoice->get_id() ); // Use a fresh instance to prevent version conflicts
			$invoice->update_external_sync_handler( self::get_name(), array( 'customer' => $contact->get_sync_data() ) );

			/**
			 * Update the customer in case exists.
			 */
			if ( $customer = $invoice->get_customer() ) {
				$customer->update_external_sync_handler( self::get_name(), $contact->get_sync_data() );
			}
		} else {
			return $result;
		}

		$filename = false;

		if ( $invoice->has_primary_file( 'sync' ) ) {
			$file_result = $this->get_api()->upload_voucher_file( $invoice->get_primary_path( 'sync' ) );

			if ( ! is_wp_error( $file_result ) ) {
				$filename = $file_result;
			}
		}

		$items         = array();
		$sync_data     = false;
		$exists        = false;
		$remote_status = false;
		$remote_data   = false;

		if ( $this->has_synced( $invoice ) ) {
			$sync_data   = $invoice->get_external_sync_handler_data( static::get_name() );
			$remote_data = $this->get_api()->get_voucher( $sync_data->get_id() );

			/**
			 * In case API does not return a 404 assume an error occurred
			 * which should not trigger a re-submit of the voucher.
			 */
			if ( ! $this->get_api()->is_404( $remote_data ) ) {
				$exists = true;

				if ( ! $this->get_api()->has_failed( $remote_data ) ) {
					$remote_status = (int) $remote_data['objects'][0]['status'];
				}
			}
		}

		/**
		 * Build up items array. Items are being merged by category ID and tax rate.
		 */
		foreach ( $invoice->get_items( $invoice->get_item_types_for_totals() ) as $item ) {
			$category_id = $this->get_category_id( $item, $invoice );
			$item_total  = $item->get_total();

			if ( 0.0 === $item_total && apply_filters( "{$this->get_hook_prefix()}skip_free_items", false, $invoice, $item ) ) {
				continue;
			}

			$item_data = array(
				'net'        => $invoice->prices_include_tax() ? 'false' : 'true',
				/* translators: 1: quantity 2: item name 3: item type */
				'comment'    => sprintf( _x( '%1$sx %2$s (%3$s)', 'sevdesk voucherPos comment', 'woocommerce-germanized-pro' ), $item->get_quantity(), $item->get_name(), $item->get_type() ),
				'objectName' => 'VoucherPos',
				'mapAll'     => 'true',
			);

			if ( $this->has_enhanced_bookkeeping_mode() ) {
				$item_data['accountDatev'] = array(
					'id'         => $category_id,
					'objectName' => 'AccountDatev',
				);
			} else {
				$item_data['accountingType'] = array(
					'id'         => $category_id,
					'objectName' => 'AccountingType',
				);
			}

			if ( $exists && $sync_data->get_id() ) {
				$item_data['voucher'] = array(
					'id'         => $sync_data->get_id(),
					'objectName' => 'Voucher',
				);
			}

			if ( $item->get_total_tax() !== 0.0 ) {
				$taxes = $item->get_taxes();

				foreach ( $taxes as $tax ) {
					$percentage  = $tax->get_tax_rate()->get_percent();
					$total_gross = $tax->get_total_net() + $tax->get_total_tax();
					$total       = $tax->get_total_net();

					// Vouchers
					if ( $total_gross > $item_total ) {
						$total_gross = $item_total;
					}

					$item_tax_data = array_merge(
						$item_data,
						array(
							'sum'      => $total,
							'sumNet'   => $tax->get_total_net(),
							'sumTax'   => $tax->get_total_tax(),
							'sumGross' => $total_gross,
							'taxRate'  => $percentage,
						)
					);

					if ( count( $taxes ) > 1 ) {
						$item_tax_data['comment'] = $item_tax_data['comment'] . ' | ' . $tax->get_tax_rate()->get_formatted_percentage();
					}

					$items[] = apply_filters( "{$this->get_hook_prefix()}voucher_taxable_item", $item_tax_data, $item, $invoice, $tax );
				}
			} else {
				$item_data = array_merge(
					$item_data,
					array(
						'sum'      => $item->get_total(),
						'sumNet'   => $item->get_total(),
						'sumTax'   => 0,
						'sumGross' => $item->get_total(),
						'taxRate'  => 0,
					)
				);

				$items[] = apply_filters( "{$this->get_hook_prefix()}voucher_non_taxable_item", $item_data, $item, $invoice );
			}
		}

		$status   = 100;
		$tax_type = 'default';
		$tax_rule = null;

		if ( $this->has_enhanced_bookkeeping_mode() ) {
			$tax_type = '';
			$tax_rule = 1;

			/**
			 * @see https://api.sevdesk.de/#section/sevdesk-Update-2.0/Tax-Rules
			 */
			if ( Countries::is_small_business() ) {
				$tax_rule = 11; // Steuer nicht erhoben nach §19UStG
			} elseif ( $invoice->is_reverse_charge() ) {
				$tax_rule = 5; // Reverse Charge gem. §13b UStG
			} elseif ( $invoice->is_vat_exempt() ) {
				$tax_rule = 3; // Innergemeinschaftliche Lieferungen
			} elseif ( $invoice->is_third_country() && empty( $invoice->get_total_tax() ) ) {
				$tax_rule = 2; // Ausfuhren
			} elseif ( empty( $invoice->get_total_tax() ) ) {
				$tax_rule = 4; // Steuerfreie Umsätze §4 UStG
			}

			$tax_type = apply_filters( $this->get_hook_prefix() . 'tax_rule', $tax_rule, $invoice );
		} else {
			if ( Countries::is_small_business() ) {
				$tax_type = 'ss';
			} elseif ( ! $this->has_enhanced_bookkeeping_mode() && ! $invoice->is_eu_vat() ) {
				$tax_type = 'noteu';
			} elseif ( $invoice->is_vat_exempt() ) {
				$tax_type = 'eu';
			}

			$tax_type = apply_filters( $this->get_hook_prefix() . 'tax_type', $tax_type, $invoice );
		}

		$voucher = array(
			'voucher'          => array(
				'objectName'      => 'Voucher',
				'mapAll'          => 'true',
				'voucherDate'     => $this->format_api_date( $invoice->get_date_created() ),
				'description'     => $invoice->get_formatted_number(),
				'comment'         => $this->get_invoice_remark( $invoice ),
				'status'          => $status,
				'total'           => $invoice->get_total(),
				'payDate'         => null,
				'deliveryDate'    => $invoice->get_date_of_service() ? $this->format_api_date( $invoice->get_date_of_service() ) : $this->format_api_date( $invoice->get_date_created() ),
				'paymentDeadline' => $invoice->get_date_due() ? $this->format_api_date( $invoice->get_date_due() ) : null,
				'taxType'         => $tax_type,
				'creditDebit'     => 'cancellation' === $invoice->get_invoice_type() ? 'C' : 'D',
				'voucherType'     => 'VOU',
				'vatNumber'       => $invoice->get_vat_id(),
				'supplier'        => $customer_id ? array(
					'id'         => $customer_id,
					'objectName' => 'Contact',
				) : null,
			),
			'voucherPosSave'   => $items,
			'filename'         => $filename ? $filename : null,
			'voucherPosDelete' => null,
		);

		if ( 'EUR' !== $invoice->get_currency() ) {
			$voucher['voucher']['currency']                        = $invoice->get_currency();
			$voucher['voucher']['propertyForeignCurrencyDeadline'] = $voucher['voucher']['deliveryDate'];
		}

        $cost_centre = apply_filters( $this->get_hook_prefix() . 'invoice_cost_centre_id', '', $invoice );

        if ( ! empty( $cost_centre ) ) {
            $voucher['voucher']['costCentre'] = array(
                'id'         => $cost_centre,
                'objectName' => 'CostCentre',
            );
        }

        if ( $this->has_enhanced_bookkeeping_mode() && null !== $tax_rule ) {
			$voucher['voucher']['taxType'] = null;
			$voucher['voucher']['taxRule'] = array(
				'objectName' => 'TaxRule',
				'id'         => $tax_rule,
			);
		}

		if ( $exists ) {
			/**
			 * In case the remote status is draft - allow updating the voucher.
			 */
			if ( 50 === $remote_status ) {
				$voucher['voucher']['id'] = $sync_data->get_id();

				/**
				 * Check for existing items
				 */
				$voucher_items = $this->get_api()->get_voucher_items( $sync_data->get_id() );

				if ( ! $this->get_api()->has_failed( $voucher_items ) ) {
					$voucher['voucherPosDelete'] = array();

					foreach ( $voucher_items['objects'] as $item ) {
						$voucher['voucherPosDelete'][] = array(
							'objectName' => 'VoucherPos',
							'id'         => $item['id'],
						);
					}
				}

				$result = $this->get_api()->update_voucher( $sync_data->get_id(), apply_filters( $this->get_hook_prefix() . 'invoice_voucher_request', $voucher, $invoice, true ) );
			} elseif ( ! $invoice->is_paid() ) {
				$this->get_api()->mark_voucher_as_open( $sync_data->get_id() );
			}
		} else {
			$result = $this->get_api()->create_voucher( apply_filters( $this->get_hook_prefix() . 'invoice_voucher_request', $voucher, $invoice, false ) );

			if ( ! is_wp_error( $result ) ) {
				$invoice    = sab_get_invoice( $invoice->get_id() ); // Use a fresh instance to prevent version conflicts
				$objects    = $result->get( 'objects' );
				$voucher_id = $objects['voucher']['id'];

				$invoice->update_external_sync_handler(
					self::get_name(),
					array(
						'id' => $voucher_id,
					)
				);
			}
		}

		if ( ! is_wp_error( $result ) ) {
			if ( $invoice->is_paid() ) {
				$book_result = $this->book_invoice( $invoice, $is_manual_request );

				if ( is_wp_error( $book_result ) ) {
					return $book_result;
				}
			}
		}

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		return $result;
	}

	/**
	 * @param Invoice $invoice
	 *
	 * @return integer
	 */
	protected function get_default_invoice_item_category_id( $invoice ) {
		/**
		 * Einnahmen / Erlöse
		 */
		$category_id = 26;

		if ( 'cancellation' === $invoice->get_invoice_type() ) {
			/**
			 * Erlösminderung
			 */
			$category_id = 27;
		}

		if ( $this->has_enhanced_bookkeeping_mode() ) {
			/**
			 * sevdesk v2 does not support account 4700 for cancellations
			 */
			$category_id = 3631;
		}

		return $category_id;
	}

	/**
	 * @see
	 *
	 * @param Item $item
	 * @param Invoice $invoice
	 */
	protected function get_category_id( $item, $invoice ) {
		$category_id = $this->get_default_invoice_item_category_id( $invoice );

		if ( 'product' === $item->get_item_type() ) {
			$product_type     = $this->get_product_type( $item );
			$option_name      = 'invoice_' . ( 'simple' !== $invoice->get_invoice_type() ? $invoice->get_invoice_type() . '_' : '' ) . "product_type_{$product_type}_cat_id";
			$type_category_id = $this->get_setting( $option_name );

			if ( ! empty( $type_category_id ) ) {
				$category_id = $type_category_id;
			}
		}

		return apply_filters( $this->get_hook_prefix() . 'item_category_id', $category_id, $item, $invoice );
	}

	/**
	 * @param ProductItem $item
	 */
	protected function get_product_type( $item ) {
		$type = 'default';

		if ( $item->is_service() ) {
			$type = 'service';
		} elseif ( $item->is_virtual() ) {
			$type = 'virtual';
		}

		return $type;
	}

	/**
	 * @param Invoice $invoice
	 */
	public function is_booked( $invoice ) {
		$is_booked = false;

		if ( $invoice->has_been_externally_synced( self::get_name() ) ) {
			$sync_data   = $invoice->get_external_sync_handler_data( self::get_name() );
			$remote_data = $this->get_api()->get_voucher( $sync_data->get_id() );
			$status      = 1000;

			if ( ! $this->get_api()->has_failed( $remote_data ) ) {
				$status = (int) $remote_data['objects'][0]['status'];
			}

			/**
			 * If the voucher has been booked - status is set to 1000 (paid).
			 */
			if ( true === wc_string_to_bool( $sync_data->get( 'is_booked' ) ) && 1000 === $status ) {
				$is_booked = true;
			}
		}

		return $is_booked;
	}

	/**
	 * Use a custom time format which will not cause issues with timezone offsets configured in sevDesk.
	 * Only passing the date to sevDesk leads to sevDesk assuming Y-m-d 00:00:00 which might produce day-offsets
	 * if a different timezone is configured in sevDesk.
	 *
	 * @param \WC_DateTime $date
	 *
	 * @return string
	 */
	protected function format_api_date( $date ) {
		return $date->date_i18n( 'Y-m-d' ) . ' 12:00:00';
	}

	/**
	 * @param Invoice $invoice
	 * @param bool $is_manual_request
	 */
	public function book_invoice( &$invoice, $is_manual_request = true ) {
		/**
		 * Do only book invoice if option is turned on, the invoice was synced before and has not yet been booked.
		 */
		if ( $this->auto_book_vouchers() && $invoice->has_been_externally_synced( self::get_name() ) && $invoice->is_paid() && ! $this->is_booked( $invoice ) ) {
			$sync_data          = $invoice->get_external_sync_handler_data( self::get_name() );
			$amount             = $invoice->get_total();
			$account_id         = $this->get_account_id( $invoice->get_payment_method_name() );
			$purpose            = $invoice->get_order_number();
			$invoice_trans_id   = $invoice->get_payment_transaction_id();
			$transaction_id     = '';
			$transaction_amount = 0.0;
			$is_manual          = $this->is_manual_account( $account_id );
			$accounts           = $this->get_accounts();
			$default_account_id = $this->get_default_account_id();
			$is_cancellation    = 'cancellation' === $invoice->get_invoice_type();
			$amount             = $is_cancellation ? $amount * -1 : $amount;

			/**
			 * No account id was found.
			 * Use case: Booking should only happen for certain gateway(s) and no default account has been chosen.
			 */
			if ( empty( $account_id ) ) {
				return false;
			}

			$start_date_obj = clone $invoice->get_date_created();
			$end_date_obj   = clone ( $invoice->get_date_paid() ? $invoice->get_date_paid() : $invoice->get_date_created() );

			/**
			 * Update transactions as these (new) transactions may not yet be available within sevDesk.
			 */
			if ( ! $is_manual ) {
				$update_transaction_args = array(
					'start_date' => apply_filters( "{$this->get_hook_prefix()}update_transactions_start_date", strtotime( 'today midnight' ), $invoice, $this ),
					'end_date'   => apply_filters( "{$this->get_hook_prefix()}update_transactions_end_date", strtotime( 'today 24:00' ), $invoice, $this ),
				);

				$updated_transactions = $this->get_api()->update_transactions( $account_id, $update_transaction_args );
			}

			/**
			 * Start searching 1 week in the past and make sure we allow searching
			 * for transactions up to 5 hours later as some gateways may book refunds belated.
			*/
			$start_date_obj->modify( '-1 week' );
			$end_date_obj->modify( '+ 5 hours' );

			$start_date = $start_date_obj->getOffsetTimestamp();
			$end_date   = $end_date_obj->getOffsetTimestamp();

			$transaction_args = array(
				'account'    => apply_filters( "{$this->get_hook_prefix()}search_transaction_account_id", $account_id, $invoice, $this ),
				'start_date' => apply_filters( "{$this->get_hook_prefix()}search_transaction_start_date", $start_date, $invoice, $this ),
				'end_date'   => apply_filters( "{$this->get_hook_prefix()}search_transaction_end_date", $end_date, $invoice, $this ),
			);

			if ( ! $is_cancellation ) {
				$transaction_args['amount_from'] = $amount;
			} else {
				$transaction_args['amount_to'] = $amount;
			}

			Package::extended_log( sprintf( 'Searching matching transactions for %s: ' . wc_print_r( $transaction_args, true ), $invoice->get_formatted_number() ) );

			/**
			 * Search the transaction based on amount and date.
			 */
			$transaction_result = $this->get_api()->search_transactions( $transaction_args );

			if ( ! $this->get_api()->has_failed( $transaction_result ) && ! empty( $transaction_result['objects'] ) ) {
				$is_match = false;

				foreach ( $transaction_result['objects'] as $transaction ) {
					if ( 'Bankeinzug' === $transaction['payeePayerName'] ) {
						continue;
					}

					$remote_purposes                  = explode( '/', $transaction['paymtPurpose'] );
					$remote_main_purpose              = ! empty( $remote_purposes[0] ) ? trim( $remote_purposes[0] ) : '';
					$remote_main_purpose_only_numbers = preg_replace( '/[^0-9]/', '', $remote_main_purpose );
					$remote_transaction_id            = '';

					Package::extended_log( sprintf( 'Remote raw transaction purposes: ' . wc_print_r( $remote_purposes, true ) ) );

					// Parse remote transaction id
					foreach ( $remote_purposes as $remote_purpose_piece ) {
						$remote_purpose_piece = trim( strtolower( $remote_purpose_piece ) );
						$purpose_only_numbers = preg_replace( '/[^0-9]/', '', $remote_purpose_piece );

						/**
						 * Fallback to the first piece with numbers in case the first part of the
						 * remote purpose contains a string-only line, e.g.: Rückerstattung / Bestellung 12345 erstatten / Bestellung 12345 / TXID D21223231D
						 */
						if ( empty( $remote_main_purpose_only_numbers ) && ! empty( $purpose_only_numbers ) ) {
							$remote_main_purpose              = $remote_purpose_piece;
							$remote_main_purpose_only_numbers = $purpose_only_numbers;
						}

						if ( strstr( $remote_purpose_piece, 'txid' ) ) {
							$remote_transaction_id = trim( str_replace( 'txid', '', $remote_purpose_piece ) );
						}
					}

					Package::extended_log( sprintf( 'Remote transaction id: %s', $remote_transaction_id ) );
					Package::extended_log( sprintf( 'Remote main purpose: %s', $remote_main_purpose ) );

					// Check if transaction id matches
					if ( ! empty( $invoice_trans_id ) && ! empty( $remote_transaction_id ) && trim( strtolower( $invoice_trans_id ) ) === $remote_transaction_id ) {
						$is_match = true;
					} elseif ( ! empty( $remote_main_purpose ) ) {
						$purpose_numbers = preg_replace( '/[^0-9]/', '', $purpose );

						/**
						 * In case the purpose contains numbers (e.g. order id) make sure to check whether numbers from the original purpose
						 * match remote purpose numbers to prevent that searching for 83718 matches 3718.
						 */
						if ( ! empty( $purpose_numbers ) ) {
							$remote_purpose_numbers = preg_replace( '/[^0-9]/', '', $remote_main_purpose );
							Package::extended_log( sprintf( 'Remote purpose numbers: %s', $remote_purpose_numbers ) );

							// Check if numeric representation (e.g. order number) matches
							if ( (string) $remote_purpose_numbers === (string) $purpose_numbers ) {
								$is_match = true;
							}
						} else {
							// Fallback string existence search
							$is_match = strstr( $remote_main_purpose, $purpose );
						}
					}

					// Stop if we've found the first match
					if ( $is_match ) {
						Package::extended_log( sprintf( 'Transaction is a match: ' . wc_print_r( $transaction, true ) ) );

						$transaction_id     = $transaction['id'];
						$account_id         = $transaction['checkAccount']['id'];
						$transaction_amount = (float) sab_format_decimal( $transaction['amount'] );

						break;
					}
				}
			}

			/**
			 * In case we didn't find the transaction for an account (e.g. does not yet exist). Schedule a retry.
			 */
			if ( ! $is_manual && empty( $transaction_id ) && ! $is_manual_request ) {
				return new \WP_Error( 'transaction_not_found', sprintf( _x( 'Transaction for invoice %1$s could not be found in account %2$s.', 'sevdesk', 'woocommerce-germanized-pro' ), $invoice->get_formatted_number(), $account_id ) );
			}

			/**
			 * Online accounts do not allow booking vouchers without a matching transaction.
			 * Use default account as fallback.
			 */
			if ( ( ! $is_manual && empty( $transaction_id ) ) || ! array_key_exists( $account_id, $accounts ) ) {
				$account_id = apply_filters( "{$this->get_hook_prefix()}book_voucher_failed_default_account_id", $default_account_id, $invoice, $this );
			}

			if ( ! empty( $account_id ) ) {
				$booking_type = 'N'; // Normal booking/partial booking

				if ( Numbers::round_to_precision( $transaction_amount ) !== Numbers::round_to_precision( $invoice->get_total() ) ) {
					$booking_type = 'O'; // Reduced/Higher amount due to other reasons
				}

				$result = $this->get_api()->book_voucher(
					$sync_data->get_id(),
					$amount,
					array(
						'type'        => $booking_type,
						'date'        => $this->format_api_date( $invoice->get_date_paid() ),
						'transaction' => apply_filters( "{$this->get_hook_prefix()}book_voucher_transaction_id", $transaction_id, $invoice, $this ),
						'account'     => apply_filters( "{$this->get_hook_prefix()}book_voucher_account_id", $account_id, $invoice, $this ),
					)
				);

				if ( ! is_wp_error( $result ) ) {
					$invoice = sab_get_invoice( $invoice->get_id() ); // Use a fresh instance to prevent version conflicts
					$invoice->update_external_sync_handler( self::get_name(), array( 'is_booked' => 'yes' ) );
					return true;
				} else {
					return $result;
				}
			}
		}

		return false;
	}

	protected function get_default_account_id() {
		return $this->get_setting( 'vouchers_book_default_account' );
	}

	public function has_enhanced_bookkeeping_mode() {
		return '2.0' === $this->get_bookkeeping_version();
	}

	public function get_bookkeeping_version( $force_refresh = false ) {
		$bookkeeping_version = get_transient( 'storeabill_sevdesk_bookkeeping_version' );

		if ( ! $bookkeeping_version || $force_refresh ) {
			$bookkeeping_version = $this->get_api()->get_bookkeeping_version();

			set_transient( 'storeabill_sevdesk_bookkeeping_version', $bookkeeping_version, DAY_IN_SECONDS );
		}

		return $bookkeeping_version;
	}

	protected function is_manual_account( $account_id ) {
		$accounts = $this->get_accounts( 'manual' );

		return array_key_exists( $account_id, $accounts ) ? true : false;
	}

	protected function get_account_id( $payment_method ) {
		$default_account = $this->get_default_account_id();
		$account_id      = $default_account;

		if ( 'yes' === $this->get_setting( 'vouchers_book_gateway_specific' ) ) {
			$gateway_accounts = (array) $this->get_setting( 'vouchers_book_gateway_accounts' );

			if ( array_key_exists( $payment_method, $gateway_accounts ) && ! empty( $gateway_accounts[ $payment_method ] ) ) {
				$account_id = $gateway_accounts[ $payment_method ];
			}
		}

		return $account_id;
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

	/**
	 * @return Models
	 */
	public function get_api() {
		return $this->api;
	}

	public function enable_auto_sync( $object_type ) {
		return 'yes' === $this->get_setting( "{$object_type}_enable_automation" );
	}

	public function auto_book_vouchers() {
		return 'yes' === $this->get_setting( 'vouchers_book' );
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

				Package::extended_log( sprintf( 'Error (%s) while performing sevdesk request: ' . wc_print_r( $body, true ), $code ) );

				if ( 400 === $code ) {
					$code = 404;
				}

				if ( $r_error = $response->get( 'error' ) ) {
					if ( isset( $r_error['message'] ) ) {
						if ( $this->has_enhanced_bookkeeping_mode() && 500 === $code && 'An error has occurred while booking Voucher' === $r_error['message'] ) {
							$exception_id = isset( $r_error['exceptionUUID'] ) ? esc_html( $r_error['exceptionUUID'] ) : '';

							$error->add( $code, sprintf( _x( 'This business transaction is not supported by sevdesk. Please contact sevdesk support for details on the issue (%s).', 'sevdesk', 'woocommerce-germanized-pro' ), $exception_id ) );
						} else {
							$error->add( $code, sprintf( _x( 'The following error occurred during a sevdesk API call: %s', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $r_error['message'] ) ) );
						}
					}
				}

				if ( ! sab_wp_error_has_errors( $error ) ) {
					if ( $code >= 500 ) {
						$error->add( $code, _x( 'There seems to be an issue while contacting sevdesk. Seems like the API is currently not available.', 'sevdesk', 'woocommerce-germanized-pro' ) );
					} elseif ( 404 === $code ) {
						$error->add( 404, _x( 'The requested resource could not be found.', 'sevdesk', 'woocommerce-germanized-pro' ) );
					} elseif ( isset( $body['message'] ) ) {
						$error->add( $code, sprintf( _x( 'The following error occurred during a sevdesk API call: %s', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $body['message'] ) ) );
					} elseif ( isset( $body['reason'] ) ) {
                        $ray_id = $response->get_header( 'cf-ray' ) ? $response->get_header( 'cf-ray' ) : '';

                        $error->add( $code, sprintf( _x( 'The following error occurred during a sevdesk API call: %s', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $body['reason'] ) . ' Ray ID: ' . esc_html( $ray_id ) ) );
                    } else {
						$error->add( $code, _x( 'Error while connecting to sevdesk. Please make sure that your host allows outgoing connections to sevdesk.', 'sevdesk', 'woocommerce-germanized-pro' ) );
					}
				}
			}
		}

		return sab_wp_error_has_errors( $error ) ? $error : $response;
	}

	protected function get_default_sevdesk_account() {
		if ( false === $this->default_account ) {
			$this->get_accounts();
		}

		return $this->default_account;
	}

	protected function get_accounts( $type = '' ) {
		if ( is_null( $this->accounts ) ) {
			$this->accounts = array(
				'manual' => array(),
				'auto'   => array(),
			);

			foreach ( $this->api->get_accounts() as $account ) {
				/* translators: 1: account name  2: account id */
				$title = sprintf( _x( '%1$s (%2$s)', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $account['name'] ), esc_html( $account['id'] ) );

				/**
				 * Set default account
				 */
				if ( isset( $account['defaultAccount'] ) && '1' === $account['defaultAccount'] ) {
					$this->default_account = $account['id'];
				}

				if ( in_array( $account['type'], array( 'offline', 'register' ), true ) ) {
					$this->accounts['manual'][ $account['id'] ] = $title;
				} else {
					$this->accounts['auto'][ $account['id'] ] = $title;
				}
			}
		}

		$accounts = empty( $type ) ? ( $this->accounts['manual'] + $this->accounts['auto'] ) : $this->accounts[ $type ];

		return $accounts;
	}

	protected function get_categories( $type = '' ) {
		if ( is_null( $this->categories ) ) {
			$this->categories = array();

			foreach ( $this->api->get_categories() as $category ) {
				if ( ! empty( $type ) && $category['type'] !== $type ) {
					continue;
				}

				/* translators: 1: category name 2: category accounting system number or id 3: Internal id */
				$this->categories[ $category['id'] ] = sprintf( _x( '%1$s (%2$s, Internal: %3$s)', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $category['name'] ), esc_html( isset( $category['accountingSystemNumber']['number'] ) ? $category['accountingSystemNumber']['number'] : $category['id'] ), $category['id'] );
			}
		}

		return $this->categories;
	}

	protected function get_account_options( $type = '' ) {
		$accounts = $this->get_accounts( $type );
		$options  = array( '' => _x( 'None', 'sevdesk accounts', 'woocommerce-germanized-pro' ) ) + $accounts;

		return $options;
	}

	protected function get_category_options() {
		$categories = $this->get_categories();
		$options    = array( '' => _x( 'None', 'sevdesk categories', 'woocommerce-germanized-pro' ) ) + $categories;

		return $options;
	}

	public function generate_sab_sevdesk_accounts_html( $key, $data ) {
		ob_start();
		$current_settings = (array) $this->get_setting( 'vouchers_book_gateway_accounts' );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $data['title'] ); ?></span>
			</th>
			<td class="forminp" id="sab-order-status-payment-method">
				<table class="widefat sab-order-status-payment-method-table sab-settings-table fixed striped page" cellspacing="0">
					<input type="text" name="sab_settings_hider" style="display: none" data-show_if_sync_handler_sevdesk_vouchers_book_gateway_specific="" />
					<thead>
					<tr>
						<th><?php echo esc_html_x( 'Payment method', 'sevdesk', 'woocommerce-germanized-pro' ); ?></th>
						<th><?php echo esc_html_x( 'Account', 'sevdesk', 'woocommerce-germanized-pro' ); ?> <?php echo sab_help_tip( _x( 'Choose one or more order statuses. Leave empty to disable automation for the method.', 'sevdesk', 'woocommerce-germanized-pro' ) ); ?></th>
					</tr>
					</thead>
					<tbody class="sab-order-status-payment-methods">
					<?php foreach ( Helper::get_available_payment_methods() as $method_id => $gateway ) : ?>
						<tr>
							<td><?php echo esc_html( $gateway->get_title() ); ?></td>
							<td>
								<select class="sab-enhanced-select" name="<?php echo esc_attr( $this->get_setting_field_key( 'vouchers_book_gateway_accounts' ) ); ?>[<?php echo esc_attr( $method_id ); ?>]" data-allow-clear="true" data-placeholder="<?php echo esc_html_x( 'Use default account', 'sevdesk', 'woocommerce-germanized-pro' ); ?>">
									<?php foreach ( $this->get_account_options() as $id => $title ) : ?>
										<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, array_key_exists( $method_id, $current_settings ) ? $current_settings[ $method_id ] : false ); ?>><?php echo esc_attr( $title ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	public function sanitize_gateway_accounts( $value ) {
		$value = is_null( $value ) ? array() : (array) $value;

		return wc_clean( array_map( 'stripslashes', $value ) );
	}

	public function get_settings( $context = 'view' ) {
		$settings                 = parent::get_settings( $context );
		$account_options          = array();
		$category_options         = array();
		$has_enhanced_bookkeeping = false;

		/**
		 * Prevent loops
		 */
		if ( 'edit' === $context && ! empty( $this->settings ) && $this->is_enabled() ) {
			$account_options          = $this->get_account_options();
			$category_options         = $this->get_category_options();
			$has_enhanced_bookkeeping = $this->has_enhanced_bookkeeping_mode();
		}

		$settings['voucher_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Vouchers', 'sevdesk', 'woocommerce-germanized-pro' ),
			'id'    => 'invoice_options',
		);

		$settings['invoice_enable_automation'] = array(
			'title'       => _x( 'Invoices', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically transfer invoices to sevdesk.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, invoices are transferred to sevdesk automatically as soon as the invoice is finalized.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		foreach ( Product::get_product_types() as $product_type => $title ) {
			$settings[ "invoice_product_type_{$product_type}_cat_id" ] = array(
				'title'             => sprintf( _x( '%s Product', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'type'              => 'select',
				'class'             => 'sab-enhanced-select',
				'desc_tip'          => sprintf( _x( 'Choose an accounting type for products of type %s', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'default'           => '',
				'options'           => $category_options,
				'custom_attributes' => array(
					'data-allow-clear' => true,
					'data-placeholder' => _x( 'Umsätze (Allgemeinkonto) (4200)', 'sevdesk accounts', 'woocommerce-germanized-pro' ),
				),
			);
		}

		$settings['invoice_cancellation_enable_automation'] = array(
			'title'       => _x( 'Cancellations', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically transfer cancellations to sevdesk.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, cancellations are transferred to sevdesk automatically as soon as the cancellation is finalized.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		foreach ( Product::get_product_types() as $product_type => $title ) {
			$settings[ "invoice_cancellation_product_type_{$product_type}_cat_id" ] = array(
				'title'             => sprintf( _x( '%s Products', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'type'              => 'select',
				'class'             => 'sab-enhanced-select',
				'desc_tip'          => sprintf( _x( 'Choose an accounting type for products of type %s', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'default'           => '',
				'options'           => $category_options,
				'custom_attributes' => array(
					'data-allow-clear' => true,
					'data-placeholder' => ! $has_enhanced_bookkeeping ? _x( 'Erlösminderung (4700, Intern: 27)', 'sevdesk accounts', 'woocommerce-germanized-pro' ) : _x( 'Umsätze (Allgemeinkonto) (4200)', 'sevdesk accounts', 'woocommerce-germanized-pro' ),
				),
			);
		}

		$settings['vouchers_book'] = array(
			'title'       => _x( 'Book', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically book vouchers.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, paid invoices will be booked to a specific sevdesk account. In case you are choosing an online account (such as PayPal) a specific transaction must be available to book the voucher. As a fallback the voucher will be booked to the default account instead.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'no',
		);

		$settings['vouchers_book_default_account'] = array(
			'title'             => _x( 'Default account', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'              => 'select',
			'class'             => 'sab-enhanced-select',
			'desc_tip'          => _x( 'Link voucher to a specific transaction if possible.', 'sevdesk', 'woocommerce-germanized-pro' ),
			'default'           => '',
			'options'           => $account_options,
			'custom_attributes' => array(
				'data-allow-clear' => true,
				'data-placeholder' => _x( 'None', 'sevdesk accounts', 'woocommerce-germanized-pro' ),
				'data-show_if_sync_handler_sevdesk_vouchers_book' => '',
			),
		);

		$settings['vouchers_book_gateway_specific'] = array(
			'title'             => _x( 'Gateways', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'              => 'sab_toggle',
			'description'       => _x( 'Choose specific booking account per gateway.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, you may choose an account per gateway to make sure vouchers paid via a specific gateway are booked to a specific account.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'           => 'no',
			'custom_attributes' => array(
				'data-show_if_sync_handler_sevdesk_vouchers_book' => '',
			),
		);

		$settings['vouchers_book_gateway_accounts'] = array(
			'title'             => _x( 'Accounts', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'              => 'sab_sevdesk_accounts',
			'sanitize_callback' => array( $this, 'sanitize_gateway_accounts' ),
		);

		$settings['customer_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Customers', 'sevdesk', 'woocommerce-germanized-pro' ),
			'id'    => 'customer_options',
		);

		$settings['customer_enable_automation'] = array(
			'title'       => _x( 'Automation', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Create and/or update contacts as soon as customer data changes.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, customers are transferred to sevdesk automatically as soon as a customer is created or updated.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		return $settings;
	}
}
