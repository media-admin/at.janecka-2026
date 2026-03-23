<?php

namespace Vendidero\Shiptastic\Hermes\Api;

use Vendidero\Shiptastic\Hermes\Package;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class OAuth extends \Vendidero\Shiptastic\API\Auth\OAuth {

	public function get_url() {
		return $this->get_api()->is_sandbox() ? 'https://authme-int.myhermes.de' : 'https://authme.myhermes.de';
	}

	protected function get_client_id() {
		if ( $this->get_api()->is_sandbox() ) {
			return 'hsi.int.verm.vendidero';
		} else {
			return 'hsi.verm.vendidero';
		}
	}

	protected function get_client_secret() {
		if ( $this->get_api()->is_sandbox() ) {
			return '9IbeNqCTN2A1Wa8YoMBq';
		} else {
			return 'G3tAFNUS-eT4bpD6AMpK';
		}
	}

	protected function get_username() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_HERMES_API_USERNAME' ) ? WC_STC_HERMES_API_USERNAME : '';
		} else {
			return Package::get_hermes_shipping_provider()->get_api_username();
		}
	}

	protected function get_password() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_HERMES_API_PASSWORD' ) ? WC_STC_HERMES_API_PASSWORD : '';
		} else {
			return Package::get_hermes_shipping_provider()->get_setting( 'api_password' );
		}
	}

	public function is_connected() {
		if ( empty( $this->get_username() ) ) {
			return false;
		}

		return true;
	}

	public function auth() {
		$response = $this->get_api()->post(
			$this->get_request_url( 'authorization-facade/oauth2/access_token' ),
			array(
				'client_id'     => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
				'username'      => $this->get_username(),
				'password'      => $this->get_password(),
				'grant_type'    => 'password',
			),
			array( 'Content-Type' => 'application/x-www-form-urlencoded' )
		);

		if ( ! $response->is_error() ) {
			$body = $response->get_body();

			if ( ! empty( $body['access_token'] ) ) {
				$this->update_access_and_refresh_token( $body );
			} else {
				$response->set_error( new ShipmentError( 'auth', 'Error while authenticating with Hermes' ) );
			}
		}

		return $response;
	}
}
