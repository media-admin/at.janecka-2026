<?php

namespace Vendidero\StoreaBill\eInvoice\CIUS;

use Vendidero\StoreaBill\BusinessInformation;
use Vendidero\StoreaBill\Invoice\Item;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Utilities\Numbers;
use Vendidero\StoreaBill\Vendor\Einvoicing\Attachment;
use Vendidero\StoreaBill\Vendor\Einvoicing\Attribute;
use Vendidero\StoreaBill\Vendor\Einvoicing\Delivery;
use Vendidero\StoreaBill\Vendor\Einvoicing\Identifier;
use Vendidero\StoreaBill\Vendor\Einvoicing\InvoiceLine;
use Vendidero\StoreaBill\Vendor\Einvoicing\InvoiceReference;
use Vendidero\StoreaBill\Vendor\Einvoicing\Party;
use Vendidero\StoreaBill\Vendor\Einvoicing\Payments\Payment;
use Vendidero\StoreaBill\Vendor\Einvoicing\Payments\Transfer;
use Vendidero\StoreaBill\Vendor\Einvoicing\Presets\Peppol;
use Vendidero\StoreaBill\Vendor\Einvoicing\Readers\UblReader;
use Vendidero\StoreaBill\Vendor\Einvoicing\Writers\UblWriter;
use function ElementorDeps\DI\value;

defined( 'ABSPATH' ) || exit;

class Invoice extends \Vendidero\StoreaBill\eInvoice\Invoice {

	protected $cius = null;

	public function get_specification() {
		return 'cius';
	}

	public function __construct( $profile, $invoice_type = 'invoice' ) {
		parent::__construct( $profile, $invoice_type );

		$this->cius = new \Vendidero\StoreaBill\Vendor\Einvoicing\Invoice( $this->map_profile( $this->get_profile() ) );
	}

	/**
	 * @return \Vendidero\StoreaBill\Vendor\Einvoicing\Invoice
	 */
	public function get_main_object() {
		return $this->cius;
	}

	/**
	 * @param \Vendidero\StoreaBill\eInvoice\Party $party
	 *
	 * @return Party
	 */
	protected function map_address( $party ) {
		$invoice_party = new Party();
		$address_lines = array( $party->get_address_1() );

		if ( ! empty( $party->get_address_2() ) ) {
			$address_lines[] = $party->get_address_2();
		}

		$invoice_party->setName( $party->get_legal_name() );
		$invoice_party->setAddress( $address_lines );
		$invoice_party->setTradingName( $party->get_legal_name() );
		$invoice_party->setCity( $party->get_city() );
		$invoice_party->setPostalCode( $party->get_postcode() );
		$invoice_party->setCountry( $party->get_country() );

		if ( $party->get_email() ) {
			$invoice_party->setContactEmail( $party->get_email() );
		}

		if ( $party->get_phone() ) {
			$invoice_party->setContactPhone( $party->get_phone() );
		}

		$invoice_party->setContactName( $party->get_formatted_full_name() ? $party->get_formatted_full_name() : $party->get_legal_name() );

		if ( $party->get_company_id() && $party->get_company_id_scheme() ) {
			$invoice_party->setCompanyId( new Identifier( $party->get_company_id(), $party->get_company_id_scheme() ) );
		}

		if ( $party->get_electronic_address() ) {
			$invoice_party->setElectronicAddress( new Identifier( $party->get_electronic_address_id(), $party->get_electronic_address_id_scheme() ) );
		}

		if ( $party->get_vat_id() ) {
			$invoice_party->setVatNumber( $party->get_vat_id() );
		}

		if ( $party->get_local_tax_number() ) {
			$invoice_party->setTaxRegistrationId( new Identifier( $party->get_local_tax_number(), 'FC' ) );
		}

		return $invoice_party;
	}

	public function set_order_reference( $order_reference ) {
		$this->cius->setPurchaseOrderReference( $order_reference );
	}

	public function set_buyer_reference( $buyer_reference ) {
		$this->cius->setBuyerReference( $buyer_reference );
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

		$document_type = $this->get_document_type( $invoice );
		$negator       = $this->get_amount_negator( $invoice );

		$this->cius->setType( $document_type );
		$this->cius->setNumber( $invoice->get_formatted_number() ? $invoice->get_formatted_number() : $invoice->get_id() );
		$this->cius->setIssueDate( $invoice->get_date_created() );
		$this->cius->setCurrency( $invoice->get_currency() );
		$this->cius->setDueDate( $invoice->get_date_due() );

		if ( 'cancellation' === $this->get_invoice_type() ) {
			$parent_date = null;

			if ( $parent = $invoice->get_parent() ) {
				$parent_date = $parent->get_date_created();
			}

			$reference = new InvoiceReference( $invoice->get_parent_formatted_number(), $parent_date );
			$this->cius->addPrecedingInvoiceReference( $reference );
		}

		if ( ! $this->cius->getPurchaseOrderReference() ) {
			$this->cius->setPurchaseOrderReference( 'NA' );
		}

		$this->cius->setSalesOrderReference( $invoice->get_order_number() );

		$seller = $this->map_address( $this->get_seller() );
		$this->cius->setSeller( $seller );

		$buyer = $this->map_address( $this->get_buyer() );
		$this->cius->setBuyer( $buyer );

		$address_lines = array( $this->get_ship_to()->get_address_1() );

		if ( ! empty( $this->get_ship_to()->get_address_2() ) ) {
			$address_lines[] = $this->get_ship_to()->get_address_2();
		}

		$delivery = new Delivery();
		$delivery->setName( $this->get_ship_to()->get_legal_name() );
		$delivery->setAddress( $address_lines );
		$delivery->setCity( $this->get_ship_to()->get_city() );
		$delivery->setPostalCode( $this->get_ship_to()->get_postcode() );
		$delivery->setCountry( $this->get_ship_to()->get_country() );
		$this->cius->setDelivery( $delivery );

		$this->cius->setPeriodStartDate( $invoice->get_date_of_service() );

		if ( $invoice->get_date_of_service_end() ) {
			$this->cius->setPeriodEndDate( $invoice->get_date_of_service_end() );
		}

		$payment = new Payment();

		if ( 'bacs' === $invoice->get_payment_method_name() ) {
			$payment->setId( $invoice->get_formatted_number() );
			$payment->setMeansCode( 58 );

			$transfer = new Transfer();
			$transfer->setAccountId( Package::get_setting( 'bank_account_iban' ) );
			$transfer->setAccountName( Package::get_setting( 'bank_account_holder' ) );
			$payment->addTransfer( $transfer );
		} else {
			$payment->setId( $invoice->get_formatted_number() );
			$payment->setMeansCode( 1 );
		}

		if ( $invoice->is_paid() ) {
			$payment_term = sprintf( _x( 'Invoice paid via %1$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title() );
		} else {
			$date_due = $invoice->get_date_due() ? $invoice->get_date_due() : $invoice->get_date_created();

			$payment_term = sprintf( _x( 'Pay via %1$s until %2$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_payment_method_title(), $date_due->date_i18n( get_option( 'date_format' ) ) );
		}

		$payment->setMeansText( $payment_term );
		$this->cius->addPayment( $payment );

		$item_count     = 0;
		$line_net_total = 0.0;
		$tax_profile    = $this->get_invoice_tax_profile( $invoice );

		foreach ( $invoice->get_items( $invoice->get_item_types_for_totals() ) as $item ) {
			if ( $this->skip_item( $item ) ) {
				continue;
			}

			$line = new InvoiceLine();
			$line->setName( $item->get_name() );

			if ( 'product' === $item->get_item_type() ) {
				$line->setId( $item->get_sku() ? $item->get_sku() : $item->get_id() );

				if ( $item->get_attributes() ) {
					foreach ( $item->get_attributes() as $attribute ) {
						$line->addAttribute( new Attribute( $attribute->get_formatted_label(), wp_strip_all_tags( $attribute->get_formatted_value() ) ) );
					}
				}
			}

			$quantity_code          = $this->get_invoice_item_quantity_code( $item );
			$item_line_subtotal     = sab_add_number_precision( $item->get_subtotal_net(), true, 5 ) * $negator;
			$item_line_total        = sab_add_number_precision( $item->get_total_net(), true, 5 ) * $negator;
			$item_subtotal          = $item_line_subtotal / (int) $item->get_quantity();
			$item_total             = $item_line_total / (int) $item->get_quantity();
			$item_subtotal_quantity = $item_subtotal < 0 ? $item->get_quantity() * -1 : $item->get_quantity();
			$item_total_quantity    = $item_total < 0 ? $item->get_quantity() * -1 : $item->get_quantity();

			/**
			 * Edge cases to show a negative quantity for 0,00 in case of cancellations
			 */
			if ( $negator < 0 && 0.0 === $item_subtotal && $item_subtotal_quantity > 0 ) {
				$item_subtotal_quantity *= $negator;
			}

			if ( $negator < 0 && 0.0 === $item_total && $item_total_quantity > 0 ) {
				$item_total_quantity *= $negator;
			}

			$line->setPrice( abs( sab_remove_number_precision( $item_line_total, 5 ) ), abs( $item_total_quantity ) );
			$line->setQuantity( $item_total_quantity );
			$line->setUnit( $quantity_code );

			$document_item_tax_profile = $tax_profile;

			if ( $item->get_tax_rates() ) {
				$exempt_data = $this->get_tax_exemption_reason_by_profile( $document_item_tax_profile, $invoice );

				foreach ( $item->get_tax_rates() as $tax_rate ) {
					$line->setVatRate( $tax_rate->get_percent() );
					$line->setVatCategory( $document_item_tax_profile );

					if ( ! empty( $exempt_data['code'] ) ) {
						$line->setVatExemptionReason( $exempt_data['reason'] );
						$line->setVatExemptionReasonCode( $exempt_data['code'] );
					}
				}
			} else {
				if ( 'S' === $document_item_tax_profile ) {
					$document_item_tax_profile = 'O';
				}

				$exempt_data = $this->get_tax_exemption_reason_by_profile( $document_item_tax_profile, $invoice );

				$line->setVatRate( 0.0 );
				$line->setVatCategory( $document_item_tax_profile );

				if ( ! empty( $exempt_data['code'] ) ) {
					$line->setVatExemptionReason( $exempt_data['reason'] );
					$line->setVatExemptionReasonCode( $exempt_data['code'] );
				}
			}

			do_action( "{$this->get_general_hook_prefix()}after_line", $item, $line, $invoice, $additional_fields, $pdf_stream, $this );

			$this->cius->addLine( $line );
		}

		$note_summary = '';

		foreach ( $this->get_notes_by_invoice( $invoice ) as $note ) {
			$note_summary .= $note . "\n";
		}

		$this->cius->addNote( $note_summary );

		if ( ! empty( $pdf_stream ) ) {
			$attachment = new Attachment();
			$attachment->setFilename( $invoice->get_filename() );
			$attachment->setMimeCode( 'attachment/pdf' );
			$attachment->setContents( base64_encode( $pdf_stream ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$attachment->setId( new Identifier( 'invoice-pdf' ) );

			$this->cius->addAttachment( $attachment );
		}

		$decimals       = apply_filters( "{$this->get_hook_prefix()}decimals", 2, $invoice, $this );
		$price_decimals = apply_filters( "{$this->get_hook_prefix()}line_price_decimals", 4, $invoice, $this );

		$this->cius->setRoundingMatrix(
			array(
				'line/price' => $price_decimals,
				''           => $decimals,
			)
		);

		$totals        = $this->cius->getTotals();
		$invoice_total = $invoice->get_total() * $negator;

		if ( $totals->payableAmount !== $invoice_total ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$diff = Numbers::round( $invoice_total - $totals->payableAmount, $decimals ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$this->cius->setRoundingAmount( $diff );
		}

		do_action( "{$this->get_general_hook_prefix()}after_create", $invoice, $additional_fields, $pdf_stream, $this );
	}

	/**
	 * @param $xml
	 *
	 * @return true|\WP_Error
	 */
	public function validate( $xml = '' ) {
		if ( $xml ) {
			$reader = new UblReader();
			$reader->registerPreset( $this->map_profile( $this->get_profile() ) );

			try {
				$this->cius = $reader->import( $xml );
			} catch ( \Exception $e ) {
				return new \WP_Error( 'validation-error', $e->getMessage() );
			}
		}

		try {
			$this->cius->validate();

			return true;
		} catch ( \Exception $e ) {
			return new \WP_Error( 'validation-error', $e->getMessage() );
		}
	}

	public function get_xml() {
		$writer = new UblWriter();

		return $writer->export( $this->cius );
	}

	/**
	 * @param $profile
	 *
	 * @return int
	 */
	protected function map_profile( $profile ) {
		$cius_profile = Peppol::class;

		switch ( $profile ) {
			case 'peppol':
				$cius_profile = Peppol::class;
				break;
		}

		return $cius_profile;
	}

	public function supports_pdf_attachment() {
		return false;
	}

	public function get_pdf( $pdf, $xml = '' ) {
		return false;
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
		} elseif ( is_callable( array( $this->cius, $method ) ) ) {
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
		if ( is_callable( array( $this->cius, $method ) ) ) {
			return call_user_func_array( array( $this->cius, $method ), $args );
		}

		return false;
	}
}
