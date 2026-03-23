<?php

namespace Vendidero\StoreaBill\eInvoice;

use Vendidero\StoreaBill\BusinessInformation;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Interfaces\EInvoice;
use Vendidero\StoreaBill\Invoice\Item;

defined( 'ABSPATH' ) || exit;

abstract class Invoice implements EInvoice {

	protected $profile = '';

	protected $buyer = null;

	protected $seller = null;

	protected $ship_to = null;

	protected $invoice_type = '';

	public function __construct( $profile, $invoice_type = 'invoice' ) {
		if ( ! in_array( $profile, $this->get_available_profiles(), true ) ) {
			throw new \Exception( esc_html( sprintf( '%s is not a valid profile', $profile ) ), 500 );
		}

		$this->profile      = $profile;
		$this->invoice_type = $invoice_type;
		$this->buyer        = new Buyer();
		$this->seller       = new Seller();
		$this->ship_to      = new ShipTo();
	}

	abstract public function get_main_object();

	public function get_profile() {
		return $this->profile;
	}

	public function get_general_hook_prefix() {
		return "storeabill_einvoice_{$this->get_specification()}_{$this->get_profile()}_";
	}

	public function get_hook_prefix() {
		return "{$this->get_general_hook_prefix()}get_";
	}

	public function supports_pdf_attachment() {
		return false;
	}

	public function get_pdf( $pdf, $xml = '' ) {
		return null;
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @return string
	 */
	protected function get_invoice_tax_profile( $invoice ) {
		$tax_profile = 'S';

		if ( BusinessInformation::is_small_business() ) {
			$tax_profile = 'E';
		} elseif ( $invoice->is_eu_intra_community_supply() ) {
			$tax_profile = 'K';
		} elseif ( $invoice->is_reverse_charge() ) {
			$tax_profile = 'AE';
		} elseif ( $invoice->is_vat_exempt() ) {
			$tax_profile = 'O';
		} elseif ( $invoice->is_third_country() && 0.0 === $invoice->get_total_tax() ) {
			$tax_profile = 'G';
		} elseif ( 0.0 === $invoice->get_total_tax() ) {
			$tax_profile = 'O';
		}

		return apply_filters( "{$this->get_general_hook_prefix()}tax_profile", $tax_profile, $invoice );
	}

	/**
	 * @param $profile
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @see https://docs.peppol.eu/poacc/billing/3.0/codelist/vatex/
	 *
	 * @return null[]|string[]
	 */
	protected function get_tax_exemption_reason_by_profile( $profile, $invoice ) {
		$exempt_reason      = null;
		$exempt_reason_code = null;

		if ( 'O' === $profile ) {
			$exempt_reason      = 'Not subject to VAT';
			$exempt_reason_code = 'VATEX-EU-O';
		} elseif ( 'K' === $profile ) {
			$exempt_reason      = 'Intra-Community supply';
			$exempt_reason_code = 'VATEX-EU-IC';
		} elseif ( 'AE' === $profile ) {
			$exempt_reason      = 'Reverse charge';
			$exempt_reason_code = 'VATEX-EU-AE';
		} elseif ( 'G' === $profile ) {
			$exempt_reason      = 'Export outside the EU';
			$exempt_reason_code = 'VATEX-EU-G';
		} elseif ( 'E' === $profile ) {
			$exempt_reason = BusinessInformation::get_small_business_notice();
		}

		return apply_filters(
			"{$this->get_general_hook_prefix()}tax_exempt_reason",
			array(
				'reason' => $exempt_reason,
				'code'   => $exempt_reason_code,
			),
			$profile,
			$invoice
		);
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Item $item
	 *
	 * @return boolean
	 */
	protected function skip_item( $item ) {
		$skip = false;

		if ( ! is_a( $item, 'Vendidero\StoreaBill\Invoice\TaxableItem' ) ) {
			$skip = true;
		}

		/**
		 * Skip free shipping/fees missing tax rate data to prevent validation errors.
		 */
		if ( 0.0 === $item->get_total() && in_array( $item->get_item_type(), array( 'fee', 'shipping' ), true ) && ! $item->get_tax_rates() && $item->get_document()->get_total_tax() > 0 ) {
			$skip = true;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}skip_item", $skip, $item, $this );
	}

	/**
	 * @param Item $item
	 *
	 * @see https://service.unece.org/trade/uncefact/vocabulary/rec20/
	 *
	 * @return string
	 */
	protected function get_invoice_item_quantity_code( $item ) {
		$item_code = 'H87'; // piece

		return $item_code;
	}

	public function get_available_profiles() {
		return Helper::get_profiles_by_specification( $this->get_specification() );
	}

	public function set_additional_fields( $fields ) {
		$available_fields = array();

		if ( $profile_data = Helper::get_profile( $this->get_specification(), $this->get_profile() ) ) {
			$available_fields = $profile_data['fields'];
		}

		foreach ( $available_fields as $field_name => $field ) {
			$field = wp_parse_args(
				$field,
				array(
					'mappings' => array(),
					'id'       => '',
				)
			);

			if ( isset( $fields[ $field['id'] ] ) ) {
				$field_value = $fields[ $field['id'] ];

				if ( ! empty( $field['mappings'] ) && ! empty( $field_value ) ) {
					foreach ( $field['mappings'] as $mapping ) {
						$mapping_exp = explode( ':', $mapping );
						$object      = $mapping_exp[0];
						$key         = $mapping_exp[1];

						if ( ! empty( $object ) && ! empty( $key ) ) {
							switch ( $object ) {
								case 'seller':
									$this->get_seller()->set_address( array( $key => $field_value ) );
									break;
								case 'buyer':
									$this->get_buyer()->set_address( array( $key => $field_value ) );
									$this->get_ship_to()->set_address( array( $key => $field_value ) );
									break;
								case 'ship_to':
									$this->get_ship_to()->set_address( array( $key => $field_value ) );
									break;
								case 'invoice':
									$setter            = 'set' === substr( $key, 0, 3 ) ? $key : "set_{$key}";
									$setter_camel_case = 'set' === substr( $key, 0, 3 ) ? $key : "set{$key}";

									if ( $this->is_callable( $setter ) ) {
										$this->{ $setter }( $field_value );
									} elseif ( $this->is_callable( $setter_camel_case ) ) {
										$this->{ $setter_camel_case }( $field_value );
									}

									break;
							}
						}
					}
				}
			}
		}
	}

	public function get_invoice_type() {
		return $this->invoice_type;
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 * @param array $additional_fields
	 * @param string $pdf_stream
	 *
	 * @return void
	 */
	public function from_invoice( $invoice, $additional_fields = array(), $pdf_stream = '' ) {
		$this->invoice_type = $invoice->get_invoice_type();
		$buyer_address      = array_merge(
			$invoice->get_address(),
			array(
				'customer_id' => $invoice->get_customer_id() ? $invoice->get_customer_id() : '',
			)
		);

		$this->buyer->set_address( $buyer_address );

		$ship_to_address = array_merge(
			$invoice->has_differing_shipping_address() ? $invoice->get_shipping_address() : $invoice->get_address(),
			array(
				'customer_id' => $invoice->get_customer_id() ? $invoice->get_customer_id() : '',
			)
		);

		$this->ship_to->set_address( $ship_to_address );

		$this->seller->set_address(
			array(
				'first_name'                  => BusinessInformation::get_contact_first_name(),
				'last_name'                   => BusinessInformation::get_contact_last_name(),
				'email'                       => BusinessInformation::get_contact_email(),
				'phone'                       => BusinessInformation::get_contact_phone(),
				'company'                     => BusinessInformation::get_base_company_name(),
				'address_1'                   => BusinessInformation::get_base_address(),
				'address_2'                   => BusinessInformation::get_base_address_2(),
				'postcode'                    => BusinessInformation::get_base_postcode(),
				'city'                        => BusinessInformation::get_base_city(),
				'country'                     => BusinessInformation::get_base_country(),
				'state'                       => BusinessInformation::get_base_state(),
				'vat_id'                      => BusinessInformation::get_vat_id(),
				'local_tax_number'            => BusinessInformation::get_local_tax_number(),
				'company_registration_number' => BusinessInformation::get_company_registration_number(),
			)
		);

		$this->set_additional_fields( $additional_fields );

		do_action( "{$this->get_general_hook_prefix()}after_set_additional_fields", $invoice, $additional_fields, $pdf_stream, $this );
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @return string[]
	 */
	protected function get_notes_by_invoice( $invoice ) {
		$notes = array();

		if ( $invoice->get_order_number() ) {
			$notes[] = sprintf( _x( 'Order %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_order_number() );
		}

		if ( 'cancellation' === $invoice->get_invoice_type() ) {
			$notes[] = sprintf( _x( 'Cancellation to %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_parent_formatted_number() );

			if ( $invoice->has_refund_order() ) {
				$notes[] = sprintf( _x( 'Refund %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_refund_order_number() );
			}
		}

		if ( $invoice->get_payment_method_title() ) {
			if ( $invoice->is_paid() ) {
				$notes[] = sprintf( _x( 'Paid via %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title() );
			} else {
				$notes[] = sprintf( _x( 'Awaiting payment via %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title() );
			}
		}

		if ( $invoice->get_payment_transaction_id() ) {
			$notes[] = sprintf( _x( 'Payment transaction id %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_transaction_id() );
		}

		if ( $invoice->is_reverse_charge() || $invoice->is_vat_exempt() ) {
			$reverse_charge_notice         = _x( 'Tax free intracommunity despatch/delivery.', 'storeabill-core', 'woocommerce-germanized-pro' );
			$virtual_reverse_charge_notice = _x( 'Tax liability of the recipient of the services.', 'storeabill-core', 'woocommerce-germanized-pro' );

			/**
			 * Use custom strings from template.
			 */
			if ( $template = $invoice->get_template() ) {
				if ( $reverse_charge = $template->get_block( 'storeabill/reverse-charge-notice' ) ) {
					$reverse_charge = wp_parse_args(
						$reverse_charge,
						array(
							'innerHTML' => '',
							'attrs'     => array(),
						)
					);

					$reverse_charge['attrs'] = wp_parse_args(
						$reverse_charge['attrs'],
						array(
							'virtualNotice' => _x( 'Tax liability of the recipient of the services.', 'storeabill-core', 'woocommerce-germanized-pro' ),
						)
					);

					$reverse_charge_notice         = wp_strip_all_tags( $reverse_charge['innerHTML'] );
					$virtual_reverse_charge_notice = wp_strip_all_tags( $reverse_charge['attrs']['virtualNotice'] );
				}
			}

			if ( $invoice->is_reverse_charge() ) {
				$reverse_charge_notice = $virtual_reverse_charge_notice;
			}

			$reverse_charge_notice = apply_filters( 'storeabill_invoice_reverse_charge_notice', $reverse_charge_notice, $invoice );

			if ( ! empty( $reverse_charge_notice ) ) {
				$notes[] = $reverse_charge_notice;
			}
		}

		if ( $invoice->is_third_country() || ( false === apply_filters( 'storeabill_use_third_country_notice_shipping_country', true, $invoice ) && Countries::is_third_country( $invoice->get_country(), $invoice->get_postcode() ) ) ) {
			$third_country_notice = _x( 'Tax-exempt export delivery.', 'storeabill-core', 'woocommerce-germanized-pro' );

			/**
			 * Use custom strings from template.
			 */
			if ( $template = $invoice->get_template() ) {
				if ( $third_country = $template->get_block( 'storeabill/third-country-notice' ) ) {
					$third_country = wp_parse_args(
						$third_country,
						array(
							'innerHTML' => '',
						)
					);

					$third_country_notice = wp_strip_all_tags( $third_country['innerHTML'] );
				}
			}

			$third_country_notice = apply_filters( 'storeabill_document_third_country_notice', $third_country_notice, $invoice );

			/**
			 * In case the document/invoice is a reverse of charge do not show the third country tax notice.
			 */
			if ( $invoice->is_reverse_charge() && apply_filters( 'storeabill_hide_third_country_notice_reverse_charge', true ) ) {
				$third_country_notice = '';
			}

			/**
			 * Hide the notice for invoices that contain taxes.
			 */
			if ( $invoice->get_total_tax() > 0 && apply_filters( 'storeabill_hide_third_country_notice_taxes_exist', true ) ) {
				$third_country_notice = '';
			}

			if ( ! empty( $third_country_notice ) ) {
				$notes[] = $third_country_notice;
			}
		}

		return $notes;
	}

	/**
	 * @return Buyer
	 */
	public function get_buyer() {
		return $this->buyer;
	}

	/**
	 * @return Seller
	 */
	public function get_seller() {
		return $this->seller;
	}

	public function get_ship_to() {
		return $this->ship_to;
	}

	public function is_callable( $method ) {
		if ( is_callable( array( $this, $method ) ) ) {
			return true;
		}

		return false;
	}
}
