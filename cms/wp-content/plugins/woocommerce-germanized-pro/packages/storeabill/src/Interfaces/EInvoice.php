<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface EInvoice {

	public function __construct( $profile );

	public function get_profile();

	public function get_specification();

	public function get_invoice_type();

	public function get_available_profiles();

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 * @param string[] $additional_fields
	 * @param string $pdf_stream
	 *
	 * @return void
	 */
	public function from_invoice( $invoice, $additional_fields = array(), $pdf_stream = '' );

	/**
	 * @param $xml
	 *
	 * @return true|\WP_Error
	 */
	public function validate( $xml = '' );

	public function get_xml();

	/**
	 * @return EInvoiceParty
	 */
	public function get_buyer();

	/**
	 * @return EInvoiceParty
	 */
	public function get_seller();

	/**
	 * @return EInvoiceParty
	 */
	public function get_ship_to();

	public function supports_pdf_attachment();

	public function get_pdf( $pdf, $xml = '' );
}
