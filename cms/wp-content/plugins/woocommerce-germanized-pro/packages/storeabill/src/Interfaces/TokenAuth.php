<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TokenAuth
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface TokenAuth extends Auth {

	public function get_token_url();

	public function get_token();
}
