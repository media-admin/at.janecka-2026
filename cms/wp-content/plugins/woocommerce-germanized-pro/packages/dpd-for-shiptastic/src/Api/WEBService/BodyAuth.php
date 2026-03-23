<?php

namespace Vendidero\Shiptastic\DPD\Api\WEBService;

use Vendidero\Shiptastic\API\Auth\RESTAuth;
use Vendidero\Shiptastic\DPD\Package;

defined( 'ABSPATH' ) || exit;

class BodyAuth extends RESTAuth {

	public function get_type() {
		return 'dpd_webservice_body_auth';
	}

	public function auth() {
		return true;
	}

	public function has_auth() {
		return true;
	}

	protected function get_mandant() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_WEBSERVICE_API_MANDANT' ) ? WC_STC_DPD_WEBSERVICE_API_MANDANT : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_setting( 'webservice_api_client' );
		}
	}

	protected function get_username() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_WEBSERVICE_API_USERNAME' ) ? WC_STC_DPD_WEBSERVICE_API_USERNAME : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_setting( 'webservice_api_username' );
		}
	}

	protected function get_password() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_WEBSERVICE_API_PASSWORD' ) ? WC_STC_DPD_WEBSERVICE_API_PASSWORD : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_setting( 'webservice_api_password' );
		}
	}

	public function is_connected() {
		return ! empty( $this->get_username() ) && ! empty( $this->get_password() );
	}

	public function get_body() {
		return array(
			'username' => $this->get_username(),
			'password' => md5( $this->get_password() ),
			'mandant'  => $this->get_mandant(),
		);
	}

	public function revoke() {}

	public function get_url() {
		return '';
	}

	public function get_headers() {
		return array();
	}
}
