<?php

namespace Vendidero\Shiptastic\DPD\Api\Cloud;

use Vendidero\Shiptastic\API\Auth\RESTAuth;
use Vendidero\Shiptastic\DPD\Package;

defined( 'ABSPATH' ) || exit;

class HeaderAuth extends RESTAuth {

	public function get_type() {
		return 'dpd_cloud_header_auth';
	}

	public function auth() {
		return true;
	}

	public function has_auth() {
		return true;
	}

	protected function get_partner_name() {
		if ( $this->get_api()->is_sandbox() ) {
			if ( defined( 'WC_STC_DPD_CLOUD_API_PARTNER_NAME' ) ) {
				return WC_STC_DPD_CLOUD_API_PARTNER_NAME;
			} else {
				return 'DPD Sandbox';
			}
		} else {
			return 'Vendidero';
		}
	}

	protected function get_partner_token() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_CLOUD_API_PARTNER_TOKEN' ) ? WC_STC_DPD_CLOUD_API_PARTNER_TOKEN : '';
		} else {
			return 'C412B4B6B4C746230786';
		}
	}

	protected function get_username() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_CLOUD_API_USERNAME' ) ? WC_STC_DPD_CLOUD_API_USERNAME : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_setting( 'cloud_api_username' );
		}
	}

	protected function get_password() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_CLOUD_API_PASSWORD' ) ? WC_STC_DPD_CLOUD_API_PASSWORD : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_setting( 'cloud_api_password' );
		}
	}

	public function is_connected() {
		return ! empty( $this->get_username() ) && ! empty( $this->get_password() );
	}

	public function get_headers() {
		return array(
			'PartnerCredentials-Name'     => $this->get_partner_name(),
			'PartnerCredentials-Token'    => $this->get_partner_token(),
			'UserCredentials-cloudUserID' => $this->get_username(),
			'UserCredentials-Token'       => $this->get_password(),
		);
	}

	public function revoke() {}

	public function get_url() {
		return '';
	}
}
