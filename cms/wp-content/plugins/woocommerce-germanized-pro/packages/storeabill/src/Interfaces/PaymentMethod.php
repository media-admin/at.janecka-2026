<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PaymentMethod Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface PaymentMethod extends Reference {

	public function get_name();

	public function get_description();

	public function get_title();

	public function get_instructions();
}
