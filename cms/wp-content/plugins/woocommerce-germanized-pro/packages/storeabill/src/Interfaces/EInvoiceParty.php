<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface EInvoiceParty {

	public function get_first_name();

	public function get_last_name();

	public function get_country();

	public function get_company();

	public function get_postcode();

	public function get_city();

	public function get_address_1();

	public function get_address_2();

	public function get_email();

	public function get_phone();

	public function get_customer_id();

	public function get_vat_id();

	public function get_party_type();

	public function get_local_tax_number();

	public function get_company_id();

	public function get_company_id_scheme();
}
