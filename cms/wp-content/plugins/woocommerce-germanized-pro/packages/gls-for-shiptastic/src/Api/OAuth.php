<?php

namespace Vendidero\Shiptastic\GLS\Api;

use Vendidero\Shiptastic\GLS\Package;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class OAuth extends \Vendidero\Shiptastic\API\Auth\OAuth {

	public function get_url() {
		return $this->get_api()->is_sandbox() ? 'https://api-sandbox.gls-group.net/oauth2/v2' : 'https://api.gls-group.net/oauth2/v2';
	}

	protected function get_client_id() {
		if ( $this->get_api()->is_sandbox() ) {
			$username = defined( 'WC_STC_GLS_API_CLIENT_ID' ) ? WC_STC_GLS_API_CLIENT_ID : '';
		} else {
			$username = Package::get_gls_shipping_provider()->get_setting( 'api_client_id' );
		}

		return $username;
	}

	protected function get_client_secret() {
		if ( $this->get_api()->is_sandbox() ) {
			$password = defined( 'WC_STC_GLS_API_CLIENT_SECRET' ) ? WC_STC_GLS_API_CLIENT_SECRET : '';
		} else {
			$password = Package::get_gls_shipping_provider()->get_setting( 'api_client_password' );
		}

		return $password;
	}

	public function auth() {
		$response = $this->get_api()->post(
			$this->get_request_url( 'token' ),
			array(
				'client_id'     => $this->get_client_id(),
				'client_secret' => $this->get_client_secret(),
				'grant_type'    => 'client_credentials',
			),
			array(
				'Content-Type' => 'application/x-www-form-urlencoded',
			)
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

	public function is_connected() {
		return ! empty( $this->get_client_id() ) && ! empty( $this->get_client_secret() );
	}
}
