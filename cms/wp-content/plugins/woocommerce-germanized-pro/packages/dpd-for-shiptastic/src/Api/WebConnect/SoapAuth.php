<?php

namespace Vendidero\Shiptastic\DPD\Api\WebConnect;

use Vendidero\Shiptastic\API\Auth\Auth;
use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\DPD\Api\Authentication;
use Vendidero\Shiptastic\DPD\Package;
use Vendidero\Shiptastic\SecretBox;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class SoapAuth extends Auth {

	protected $auth = null;

	public function get_type() {
		return 'dpd_soap_auth';
	}

	protected function get_username() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_API_USERNAME' ) ? WC_STC_DPD_API_USERNAME : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_api_username();
		}
	}

	protected function get_password() {
		if ( $this->get_api()->is_sandbox() ) {
			return defined( 'WC_STC_DPD_API_PASSWORD' ) ? WC_STC_DPD_API_PASSWORD : '';
		} else {
			return Package::get_dpd_shipping_provider()->get_setting( 'api_password' );
		}
	}

	protected function get_auth_transient_name() {
		$username       = sanitize_key( $this->get_username() );
		$transient_name = "wc_shiptastic_dpd_api_auth_{$username}";

		return $transient_name;
	}

	protected function get_auth_transient() {
		return get_transient( $this->get_auth_transient_name() );
	}

	public function auth() {
		$auth_data  = $this->get_auth_transient();
		$this->auth = null;
		$response   = new Response( 200, '' );

		if ( false === $auth_data || ! isset( $auth_data->authToken ) ) {
			try {
				$client = new \SoapClient(
					$this->get_request_url( 'services/LoginService/V2_0/?wsdl' ),
					array(
						'trace' => true,
					)
				);

				$client->__setLocation( $this->get_request_url( 'services/LoginService/V2_0/' ) );

				$auth_response = $client->getAuth(
					array(
						'delisId'         => $this->get_username(),
						'password'        => $this->get_password(),
						'messageLanguage' => Package::get_api_language(),
					)
				);

				if ( isset( $auth_response->return ) ) {
					$valid = strtotime( 'tomorrow 3:00' ) - time();

					/**
					 * Persist the encrypted key
					 */
					$store            = clone $auth_response->return;
					$store->authToken = SecretBox::maybe_encrypt( $store->authToken );

					set_transient( $this->get_auth_transient_name(), $store, $valid );

					$this->auth = new Authentication();
					$this->auth->setAuthToken( $auth_response->return->authToken );
					$this->auth->setDelisId( $auth_response->return->delisId );
					$this->auth->setDepot( $auth_response->return->depot );
					$this->auth->setCustomerUid( $auth_response->return->customerUid );
					$this->auth->setMessageLanguage( Package::get_api_language() );

					$response->set_body( $this->auth );
				} else {
					$response->set_code( 500 );
					$response->set_error( new ShipmentError( 500, _x( 'Error while authenticating with DPD.', 'dpd', 'woocommerce-germanized-pro' ) ) );
				}
			} catch ( \SoapFault $exception ) {
				$response->set_code( 500 );

				if ( is_object( isset( $exception->detail ) ? $exception->detail : null ) && is_object( isset( $exception->detail->authenticationFault ) ? $exception->detail->authenticationFault : null ) ) {
					$response->set_error(
						new ShipmentError(
							$exception->detail->authenticationFault->errorCode,
							$exception->detail->authenticationFault->errorMessage
						)
					);
				} else {
					$response->set_error(
						new ShipmentError(
							'api_error',
							$exception->getMessage()
						)
					);
				}
			}
		} else {
			$this->auth = new Authentication();

			$this->auth->setAuthToken( SecretBox::maybe_decrypt( $auth_data->authToken ) );
			$this->auth->setDelisId( $auth_data->delisId );
			$this->auth->setDepot( $auth_data->depot );
			$this->auth->setCustomerUid( $auth_data->customerUid );
			$this->auth->setMessageLanguage( Package::get_api_language() );

			$response->set_body( $this->auth );
		}

		return $response;
	}

	public function has_auth() {
		return $this->auth ? true : false;
	}

	public function is_connected() {
		return ! empty( $this->get_username() ) && ! empty( $this->get_password() );
	}

	public function revoke() {
		delete_transient( $this->get_auth_transient_name() );
	}

	public function get_url() {
		return $this->get_api()->is_sandbox() ? 'https://public-ws-stage.dpd.com/' : 'https://public-ws.dpd.com/';
	}
}
