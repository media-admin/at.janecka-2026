<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Invoice
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface Invoice extends Summable {

	public function get_item_types_for_totals();

	public function get_item_types_for_tax_totals();

	public function get_total_tax( $context = '' );

	public function get_subtotal_tax();

	/**
	 * @return PaymentMethod|false
	 */
	public function get_payment_method_instance();

	public function prices_include_tax();

	public function round_tax_at_subtotal();

	public function update_taxes();

	public function update_total();

	public function update_tax_totals();

	public function get_formatted_price( $price, $type = '' );

	public function is_paid();

	public function is_oss();

	public function is_eu();

	public function is_eu_vat();

	public function is_b2b();

	public function is_third_country();

	public function is_reverse_charge();

	public function is_vat_exempt();

	public function is_eu_cross_border_taxable();

	public function is_eu_intra_community_supply();
}
