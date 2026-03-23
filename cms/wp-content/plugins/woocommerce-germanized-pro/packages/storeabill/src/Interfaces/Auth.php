<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auth
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
interface Auth {

	public function get_type();

	public function get_description();

	/**
	 * @return boolean
	 */
	public function ping();
}
