<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RefundOrder
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface RefundOrder extends Reference {

	public function get_formatted_number();

	public function get_reason();

	public function get_transaction_id();
}
