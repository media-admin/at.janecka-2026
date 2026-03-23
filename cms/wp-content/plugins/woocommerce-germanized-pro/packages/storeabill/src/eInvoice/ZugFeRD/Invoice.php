<?php

namespace Vendidero\StoreaBill\eInvoice\ZugFeRD;

use Vendidero\StoreaBill\BusinessInformation;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Utilities\Numbers;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\codelists\ZugferdPaymentMeans;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\ZugferdDocumentBuilder;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\ZugferdDocumentPdfMerger;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\ZugferdDocumentReader;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\ZugferdProfiles;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\ZugferdSettings;
use Vendidero\StoreaBill\Vendor\horstoeko\zugferd\ZugferdXsdValidator;

defined( 'ABSPATH' ) || exit;

class Invoice extends \Vendidero\StoreaBill\eInvoice\Invoice {

	protected $zugferd = null;

	public function get_specification() {
		return 'zugferd';
	}

	public function __construct( $profile, $invoice_type = 'invoice' ) {
		parent::__construct( $profile, $invoice_type );

		$this->zugferd = ZugferdDocumentBuilder::CreateNew( $this->map_profile( $this->get_profile() ) );
	}

	/**
	 * @return ZugferdDocumentBuilder
	 */
	public function get_main_object() {
		return $this->zugferd;
	}

	public function set_routing_id( $routing_id ) {
		$this->zugferd->setDocumentRoutingId( $routing_id );
	}

	protected function get_document_type( $invoice ) {
		$document_type = 'cancellation' === $this->get_invoice_type() ? '384' : '380';

		return apply_filters( "{$this->get_hook_prefix()}document_type", $document_type, $this, $invoice );
	}

	protected function get_amount_negator( $invoice ) {
		$negator = 'cancellation' === $this->get_invoice_type() ? -1 : 1;

		return apply_filters( "{$this->get_hook_prefix()}amount_negator", $negator, $this, $invoice );
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 * @param string[] $additional_fields
	 * @param string $pdf_stream
	 *
	 * @return void
	 */
	public function from_invoice( $invoice, $additional_fields = array(), $pdf_stream = '' ) {
		parent::from_invoice( $invoice, $additional_fields, $pdf_stream );

		$is_xrechnung    = strstr( $this->get_profile(), 'xrechnung' );
		$amount_decimals = apply_filters( "{$this->get_hook_prefix()}amount_decimals", 5, $invoice, $this );
		$negator         = $this->get_amount_negator( $invoice );
		$document_type   = $this->get_document_type( $invoice );
		$seller_id       = $this->get_seller()->get_vat_id() ? null : sanitize_key( $this->get_seller()->get_legal_name() );

		ZugferdSettings::setUnitAmountDecimals( $amount_decimals );
		ZugferdSettings::addSpecialDecimalPlacesMap( '/rsm:CrossIndustryInvoice/rsm:SupplyChainTradeTransaction/ram:IncludedSupplyChainTradeLineItem/ram:SpecifiedLineTradeAgreement/ram:GrossPriceProductTradePrice/ram:AppliedTradeAllowanceCharge/ram:ActualAmount', $amount_decimals );

		$this->zugferd->setDocumentInformation( $invoice->get_formatted_number() ? $invoice->get_formatted_number() : $invoice->get_id(), $document_type, $invoice->get_date_created(), $invoice->get_currency() );

		/**
		 * In case a VAT ID is missing we'll need to provide a custom seller identification id
		 */
		$additional_seller_legal_notice = '';

		if ( BusinessInformation::is_small_business() ) {
			$additional_seller_legal_notice = BusinessInformation::get_small_business_notice();
		}

		if ( BusinessInformation::get_company_legal_free_text() ) {
			if ( ! empty( $additional_seller_legal_notice ) ) {
				$additional_seller_legal_notice .= "\n";
			}

			$additional_seller_legal_notice .= BusinessInformation::get_company_legal_free_text( "\n" );
		}

		$this->zugferd->setDocumentSeller( $this->get_seller()->get_legal_name(), apply_filters( "{$this->get_hook_prefix()}seller_id", $seller_id, $invoice, $this ), ! empty( $additional_seller_legal_notice ) ? $additional_seller_legal_notice : null );

		if ( $this->get_seller()->get_company_id() && $this->get_seller()->get_company_id_scheme() ) {
			$this->zugferd->addDocumentSellerGlobalId( $this->get_seller()->get_company_id(), $this->get_seller()->get_company_id_scheme() );
		}

		if ( $this->get_seller()->get_vat_id() ) {
			$this->zugferd->addDocumentSellerTaxRegistration( 'VA', $this->get_seller()->get_vat_id() );
		}

		if ( $this->get_seller()->get_local_tax_number() ) {
			$this->zugferd->addDocumentSellerTaxRegistration( 'FC', $this->get_seller()->get_local_tax_number() );
		}

		$this->zugferd->setDocumentSellerAddress( $this->get_seller()->get_address_1(), $this->get_seller()->get_address_2(), '', $this->get_seller()->get_postcode(), $this->get_seller()->get_city(), $this->get_seller()->get_country() );
		$this->zugferd->setDocumentSellerContact( $this->get_seller()->get_formatted_full_name() ? $this->get_seller()->get_formatted_full_name() : $this->get_seller()->get_legal_name(), null, $this->get_seller()->get_phone(), '', $this->get_seller()->get_email() );
		$this->zugferd->setDocumentSellerCommunication( 'EM', $this->get_seller()->get_email() );

		$this->zugferd->setDocumentBuyer( $this->get_buyer()->get_legal_name(), $this->get_buyer()->get_customer_id() );
		$this->zugferd->setDocumentBuyerAddress( $this->get_buyer()->get_address_1(), $this->get_buyer()->get_address_2(), '', $this->get_buyer()->get_postcode(), $this->get_buyer()->get_city(), $this->get_buyer()->get_country() );
		$this->zugferd->setDocumentBuyerContact( $this->get_buyer()->get_formatted_full_name(), null, $this->get_buyer()->get_phone(), '', $this->get_buyer()->get_email() );
		$this->zugferd->setDocumentBuyerCommunication( 'EM', $this->get_buyer()->get_email() );

		/**
		 * Prevent FormattedIssueDateTime in BuyerOrderReferencedDocument as XRechnung does not support that.
		 */
		$this->zugferd->setDocumentBuyerOrderReferencedDocument( $invoice->get_order_number(), ( $invoice->get_order() && ! $is_xrechnung ? $invoice->get_order()->get_date_created() : null ) );

		if ( $this->get_buyer()->get_company_id() && $this->get_buyer()->get_company_id_scheme() ) {
			$this->zugferd->addDocumentBuyerGlobalId( $this->get_buyer()->get_company_id(), $this->get_buyer()->get_company_id_scheme() );
		}

		if ( $this->get_buyer()->get_vat_id() ) {
			$this->zugferd->addDocumentBuyerTaxRegistration( 'VA', $this->get_buyer()->get_vat_id() );
		}

		if ( $this->get_buyer()->get_local_tax_number() ) {
			$this->zugferd->addDocumentBuyerTaxRegistration( 'FC', $this->get_buyer()->get_local_tax_number() );
		}

		$this->zugferd->setDocumentShipTo( $this->get_ship_to()->get_legal_name(), $this->get_ship_to()->get_customer_id() );
		$this->zugferd->setDocumentShipToAddress( $this->get_ship_to()->get_address_1(), $this->get_ship_to()->get_address_2(), '', $this->get_ship_to()->get_postcode(), $this->get_ship_to()->get_city(), $this->get_ship_to()->get_country() );

		/**
		 * Prevent validation errors as DefinedTradeContact is not expected in ShipToTradeParty in older profiles.
		 */
		if ( ! in_array( $this->get_profile(), array( 'basic', 'comfort', 'xrechnung', 'xrechnung_2', 'xrechnung_3' ), true ) ) {
			$this->zugferd->setDocumentShipToContact( $this->get_ship_to()->get_formatted_full_name(), null, $this->get_ship_to()->get_phone(), '', $this->get_ship_to()->get_email() );
		}

		if ( $this->get_ship_to()->get_company_id() && $this->get_ship_to()->get_company_id_scheme() ) {
			$this->zugferd->addDocumentShipToGlobalId( $this->get_ship_to()->get_company_id(), $this->get_ship_to()->get_company_id_scheme() );
		}

		if ( $this->get_ship_to()->get_vat_id() ) {
			$this->zugferd->addDocumentShipToTaxRegistration( 'VA', $this->get_ship_to()->get_vat_id() );
		}

		if ( $this->get_ship_to()->get_local_tax_number() ) {
			$this->zugferd->addDocumentShipToTaxRegistration( 'FC', $this->get_ship_to()->get_local_tax_number() );
		}

		if ( 'cancellation' === $this->get_invoice_type() ) {
			$parent_date = null;

			if ( $parent = $invoice->get_parent() ) {
				$parent_date = $parent->get_date_created();
			}

			$this->zugferd->setDocumentInvoiceReferencedDocument( $invoice->get_parent_formatted_number(), '380', $parent_date );
		}

		$this->zugferd->setDocumentBillingPeriod( $invoice->get_date_of_service(), $invoice->get_date_of_service_end(), null );
		$this->zugferd->setDocumentSupplyChainEvent( $invoice->get_date_of_service_end() ? $invoice->get_date_of_service_end() : $invoice->get_date_of_service() );

		if ( in_array( $invoice->get_payment_method_name(), array( 'invoice', 'bacs' ), true ) ) {
			$payee_iban   = BusinessInformation::get_bank_account_iban();
			$payee_bic    = BusinessInformation::get_bank_account_bic();
			$payee_holder = BusinessInformation::get_bank_account_holder();
			$this->zugferd->addDocumentPaymentMean( ZugferdPaymentMeans::UNTDID_4461_58, $invoice->get_payment_method_title(), null, null, null, null, $payee_iban, $payee_holder, null, $payee_bic );

			$payment_means_latin = html_entity_decode( $invoice->get_payment_reference(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$payment_means_latin = remove_accents( $payment_means_latin );
			$payment_means_latin = preg_replace( '/[^ \w-]/', ' ', $payment_means_latin );
			$payment_means_latin = preg_replace( '/\s+/', ' ', $payment_means_latin );

			$this->zugferd->setDocumentGeneralPaymentInformation( null, substr( $payment_means_latin, 0, 140 ) );
		} else {
			$this->zugferd->addDocumentPaymentMean( ZugferdPaymentMeans::UNTDID_4461_1, $invoice->get_payment_method_title() );
		}

		if ( $invoice->is_paid() ) {
			$payment_term = sprintf( _x( 'Invoice paid via %1$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title() );
		} else {
			$date_due = $invoice->get_date_due() ? $invoice->get_date_due() : $invoice->get_date_created();

			$payment_term = sprintf( _x( 'Pay via %1$s until %2$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title(), $date_due->date_i18n( get_option( 'date_format' ) ) );
		}

		$this->zugferd->addDocumentPaymentTerm( $payment_term, ! $invoice->is_paid() && $invoice->get_date_due() ? $invoice->get_date_due() : null );

		$item_count              = 0;
		$line_net_total          = 0.0;
		$zero_tax_line_net_total = 0.0;
		$tax_profile             = $this->get_invoice_tax_profile( $invoice );
		$line_net_totals         = array();

		foreach ( $invoice->get_items( $invoice->get_item_types_for_totals() ) as $item ) {
			if ( $this->skip_item( $item ) ) {
				continue;
			}

			$this->zugferd->addNewPosition( ++$item_count );

			if ( 'product' === $item->get_item_type() ) {
				$gtin = $item->get_global_unique_id();

				$this->zugferd->setDocumentPositionProductDetails( $item->get_name(), '', $item->get_sku(), null, ( ! empty( $gtin ) ? '0160' : null ), ( ! empty( $gtin ) ? $gtin : null ) );

				if ( $item->get_attributes() ) {
					foreach ( $item->get_attributes() as $attribute ) {
						$this->zugferd->addDocumentPositionProductCharacteristic( $attribute->get_formatted_label(), wp_strip_all_tags( $attribute->get_formatted_value() ) );
					}
				}
			} else {
				$this->zugferd->setDocumentPositionProductDetails( $item->get_name() );
			}

			$quantity_code          = $this->get_invoice_item_quantity_code( $item );
			$item_line_subtotal     = sab_add_number_precision( $item->get_subtotal_net(), true, $amount_decimals ) * $negator;
			$item_line_total        = sab_add_number_precision( $item->get_total_net(), true, $amount_decimals ) * $negator;
			$item_subtotal          = $item_line_subtotal / (int) $item->get_quantity();
			$item_total             = $item_line_total / (int) $item->get_quantity();
			$item_subtotal_quantity = $item_subtotal < 0 ? (int) $item->get_quantity() * -1 : (int) $item->get_quantity();
			$item_total_quantity    = $item_total < 0 ? (int) $item->get_quantity() * -1 : (int) $item->get_quantity();

			/**
			 * Edge cases to show a negative quantity for 0,00 in case of cancellations
			 */
			if ( $negator < 0 && 0.0 === $item_subtotal && $item_subtotal_quantity > 0 ) {
				$item_subtotal_quantity *= $negator;
			}

			if ( $negator < 0 && 0.0 === $item_total && $item_total_quantity > 0 ) {
				$item_total_quantity *= $negator;
			}

			$item_total_rounded    = abs( sab_remove_number_precision( $item_total, $amount_decimals ) );
			$item_subtotal_rounded = abs( sab_remove_number_precision( $item_subtotal, $amount_decimals ) );

			if ( sab_remove_number_precision( $item_subtotal, $amount_decimals ) !== sab_remove_number_precision( $item_total, $amount_decimals ) ) {
				$discount_notice = $invoice->get_formatted_discount_notice();
				$discount_amount = $item_subtotal_rounded - $item_total_rounded;

				$this->zugferd->setDocumentPositionGrossPrice( $item_subtotal_rounded, 1, $quantity_code );
				$this->zugferd->addDocumentPositionGrossPriceAllowanceCharge( $discount_amount, false, null, null, $discount_notice );
			}

			$this->zugferd->setDocumentPositionNetPrice( $item_total_rounded, 1, $quantity_code );
			$this->zugferd->setDocumentPositionQuantity( $item_total_quantity, $quantity_code );

			$line_total_rounded = Numbers::round_to_precision( sab_remove_number_precision( $item_line_total, $amount_decimals ), 2 );
			$this->zugferd->setDocumentPositionLineSummation( $line_total_rounded );

			$document_item_tax_profile = $tax_profile;

			if ( $item->get_tax_rates() ) {
				$exempt_data = $this->get_tax_exemption_reason_by_profile( $document_item_tax_profile, $invoice );

				foreach ( $item->get_tax_rates() as $tax_rate ) {
					$tax_rate_pct = in_array( $exempt_data['code'], array( 'VATEX-EU-O' ), true ) ? null : $tax_rate->get_percent();

					$this->zugferd->addDocumentPositionTax( $document_item_tax_profile, 'VAT', $tax_rate_pct, null, $exempt_data['reason'], $exempt_data['code'] );

					if ( ! isset( $line_net_totals[ (string) $tax_rate->get_percent() ] ) ) {
						$line_net_totals[ (string) $tax_rate->get_percent() ] = 0.0;
					}

					$line_net_totals[ (string) $tax_rate->get_percent() ] += $line_total_rounded;
				}
			} else {
				if ( 'S' === $document_item_tax_profile ) {
					$document_item_tax_profile = 'O';
				}

				$exempt_data = $this->get_tax_exemption_reason_by_profile( $document_item_tax_profile, $invoice );
				$tax_rate    = in_array( $exempt_data['code'], array( 'VATEX-EU-O' ), true ) ? null : 0.0;

				$this->zugferd->addDocumentPositionTax( $document_item_tax_profile, 'VAT', $tax_rate, null, $exempt_data['reason'], $exempt_data['code'] );

				$zero_tax_line_net_total += $line_total_rounded;

				if ( ! isset( $line_net_totals['0'] ) ) {
					$line_net_totals['0'] = 0.0;
				}

				$line_net_totals['0'] += $line_total_rounded;
			}

			$line_net_total += $line_total_rounded;

			do_action( "{$this->get_general_hook_prefix()}after_line", $item, $invoice, $additional_fields, $pdf_stream, $this );
		}

		$tax_total_rounded = 0.0;

		/**
		 * Prefer using the actual rounded line net totals instead of
		 * document tax totals to avoid rounding differences.
		 */
		foreach ( $invoice->get_tax_totals() as $tax_total ) {
			$exempt_data  = $this->get_tax_exemption_reason_by_profile( $tax_profile, $invoice );
			$tax_rate_pct = (string) $tax_total->get_tax_rate()->get_percent();
			$net_total    = isset( $line_net_totals[ $tax_rate_pct ] ) ? $line_net_totals[ $tax_rate_pct ] : $tax_total->get_total_net();

			$this->zugferd->addDocumentTax( $tax_profile, 'VAT', $net_total, $tax_total->get_total_tax() * $negator, $tax_total->get_tax_rate()->get_percent(), $exempt_data['reason'], $exempt_data['code'] );

			$tax_total_rounded += $tax_total->get_total_tax() * $negator;
		}

		if ( 0.0 !== $zero_tax_line_net_total ) {
			$zero_tax_profile = $tax_profile;
			$tax_rate_pct     = '0';
			$net_total        = isset( $line_net_totals[ $tax_rate_pct ] ) ? $line_net_totals[ $tax_rate_pct ] : $zero_tax_line_net_total;

			if ( 'S' === $tax_profile ) {
				$zero_tax_profile = 'O';
			}

			$exempt_data = $this->get_tax_exemption_reason_by_profile( $zero_tax_profile, $invoice );

			$this->zugferd->addDocumentTax( $tax_profile, 'VAT', $net_total, 0.0, 0.0, $exempt_data['reason'], $exempt_data['code'] );
		}

		foreach ( $this->get_notes_by_invoice( $invoice ) as $note ) {
			$this->zugferd->addDocumentNote( $note );
		}

		$rounding_amount = 0.0;
		$rounded_total   = Numbers::round_to_precision( $line_net_total + $tax_total_rounded, 2 );
		$invoice_total   = Numbers::round_to_precision( $invoice->get_total() * $negator, 2 );

		if ( $rounded_total !== $invoice_total ) {
			$rounding_amount = Numbers::round_to_precision( $invoice_total - $rounded_total, 2 );
		}

		$due_payable_amount = $invoice_total;
		$prepaid_amount     = 0.0;

		if ( $invoice->is_paid() ) {
			$due_payable_amount = 0.0;
			$prepaid_amount     = $invoice_total;
		}

		/**
		 * Invoice total + rounding total = payable/paid total
		 */
		$this->zugferd->setDocumentSummation( $invoice_total - $rounding_amount, $due_payable_amount, $line_net_total, 0.0, 0.0, $line_net_total, $tax_total_rounded, $rounding_amount, $prepaid_amount );

		do_action( "{$this->get_general_hook_prefix()}after_create", $invoice, $additional_fields, $pdf_stream, $this );
	}

	/**
	 * @param $xml
	 *
	 * @return true|\WP_Error
	 */
	public function validate( $xml = '' ) {
		if ( ! $xml ) {
			$xml = $this->get_xml();
		}

		$document  = ZugferdDocumentReader::readAndGuessFromContent( $xml );
		$validator = new ZugferdXsdValidator( $document );

		$result = $validator->validate();

		if ( $result->validationPased() ) {
			return true;
		} else {
			$error = new \WP_Error();

			foreach ( $result->validationErrors() as $error_msg ) {
				$error->add( 'validation-error', $error_msg );
			}

			return $error;
		}
	}

	public function get_xml() {
		return $this->zugferd->getContent();
	}

	/**
	 * @param $profile
	 *
	 * @return int
	 */
	protected function map_profile( $profile ) {
		$zugferd_profile = ZugferdProfiles::PROFILE_EXTENDED;

		switch ( $profile ) {
			case 'basic':
				$zugferd_profile = ZugferdProfiles::PROFILE_BASIC;
				break;
			case 'comfort':
				$zugferd_profile = ZugferdProfiles::PROFILE_EN16931;
				break;
			case 'extended':
				$zugferd_profile = ZugferdProfiles::PROFILE_EXTENDED;
				break;
			case 'xrechnung':
				$zugferd_profile = ZugferdProfiles::PROFILE_XRECHNUNG;
				break;
			case 'xrechnung_2':
				$zugferd_profile = ZugferdProfiles::PROFILE_XRECHNUNG_2;
				break;
			case 'xrechnung_3':
				$zugferd_profile = ZugferdProfiles::PROFILE_XRECHNUNG_3;
				break;
		}

		return $zugferd_profile;
	}

	public function supports_pdf_attachment() {
		return true;
	}

	public function get_pdf( $pdf, $xml = '' ) {
		if ( ! $xml ) {
			$xml = $this->get_xml();
		}

		$merger = new ZugferdDocumentPdfMerger( $xml, $pdf );
		$stream = $merger->generateDocument()->downloadString( 'attached.pdf' );

		return $stream;
	}

	/**
	 * Check if a method is callable by checking the underlying object.
	 * Necessary because is_callable checks will always return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif ( is_callable( array( $this->zugferd, $method ) ) ) {
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
		if ( is_callable( array( $this->zugferd, $method ) ) ) {
			return call_user_func_array( array( $this->zugferd, $method ), $args );
		}

		return false;
	}
}
