<?php

namespace Vendidero\Shiptastic\GLS\Api;

use Vendidero\Shiptastic\API\Auth\Basic;
use Vendidero\Shiptastic\GLS\Package;

defined( 'ABSPATH' ) || exit;

class BasicAuth extends Basic {

	protected function get_username() {
		if ( $this->get_api()->is_sandbox() ) {
			$username = defined( 'WC_STC_GLS_API_USERNAME' ) ? WC_STC_GLS_API_USERNAME : '';
		} else {
			$username = Package::get_gls_shipping_provider()->get_api_username();
		}

		return $username;
	}

	protected function get_password() {
		if ( $this->get_api()->is_sandbox() ) {
			$password = defined( 'WC_STC_GLS_API_PASSWORD' ) ? WC_STC_GLS_API_PASSWORD : '';
		} else {
			$password = Package::get_gls_shipping_provider()->get_setting( 'api_password' );
		}

		return $password;
	}
}
