<?php

namespace Vendidero\StoreaBill\Lexoffice\API;

use Vendidero\StoreaBill\API\REST;
use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\Lexoffice\Customer;
use Vendidero\StoreaBill\Lexoffice\Package;
use Vendidero\StoreaBill\Lexoffice\Sync;

defined( 'ABSPATH' ) || exit;

class Resources extends REST {

	protected $sync_helper = null;

	public function __construct( $helper ) {
		$this->sync_helper = $helper;
	}

	/**
	 * @return Auth $auth
	 */
	protected function get_auth() {
		return $this->get_sync_helper()->get_auth_api();
	}

	/**
	 * @return Sync|null
	 */
	protected function get_sync_helper() {
		return $this->sync_helper;
	}

	protected function get_basic_auth() {
		return 'Bearer ' . $this->get_auth()->get_access_token();
	}

	public function get_url() {
		return Package::get_api_url();
	}

	protected function get_response( $url, $type = 'GET', $body_args = array(), $header = array() ) {
		if ( $this->get_auth()->has_expired() ) {
			$this->get_auth()->refresh();
		}

		$response = parent::get_response( $url, $type, $body_args, $header );

		if ( ! is_wp_error( $response ) && $response->is_error() ) {
			$code = $response->get_code();

			/**
			 * Handle rate limit hits
			 */
			if ( 429 === absint( $code ) ) {
				\Vendidero\StoreaBill\Package::extended_log( sprintf( 'Lexoffice rate limit hit while calling %1$s', $url ) );

				$hits = false === get_transient( 'storeabill_lexoffice_rate_limit_hits' ) ? 0 : absint( get_transient( 'storeabill_lexoffice_rate_limit_hits' ) );

				if ( $hits <= 5 ) {
					++$hits;

					sleep( 1 * $hits );
					set_transient( 'storeabill_lexoffice_rate_limit_hits', $hits, MINUTE_IN_SECONDS );

					return $this->get_response( $url, $type, $body_args, $header );
				}
			}
		}

		return $response;
	}

	public function ping() {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'ping' ) );

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		return false;
	}

	public function get_voucher_link( $id ) {
		return trailingslashit( Package::get_app_url() ) . 'permalink/vouchers/view/' . $id;
	}

	/**
	 * Only returns false in case the voucher cannot be found (remotely deleted)
	 * to prevent duplicates when the API fails.
	 *
	 * @param $id
	 *
	 * @return false|mixed|RESTResponse|\WP_Error
	 */
	public function get_voucher( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'vouchers/' . $id ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	/**
	 * @param \WP_Error|boolean|mixed $api_result
	 *
	 * @return bool
	 */
	public function has_failed( $api_result ) {
		return ( is_wp_error( $api_result ) || false === $api_result ) ? true : false;
	}

	/**
	 * Seems like lexoffice API returns a 403 instead of a 404
	 * in case the object exists but is not linked to the current account.
	 *
	 * @param \WP_Error|boolean|mixed $result
	 *
	 * @return bool
	 */
	public function is_404( $result ) {
		$allowed_codes = array( 404, 403 );

		return ( is_wp_error( $result ) && in_array( (int) $result->get_error_code(), $allowed_codes, true ) );
	}

	public function update_voucher( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'vouchers/' . $id, $data ) );
	}

	public function create_voucher( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'vouchers', $data ) );
	}

	public function create_voucher_transaction_hint( $id, $payment_transaction_id ) {
		return $this->get_sync_helper()->parse_response(
			$this->post(
				'transaction-assignment-hint',
				array(
					'voucherId'         => $id,
					'externalReference' => $payment_transaction_id,
				)
			)
		);
	}

	public function update_voucher_file( $id, $file ) {
		$mime_type = $this->get_file_mime_type( $file );

		try {
			if ( $this->uses_curl() ) {
				/**
				 * Prevent WP from overriding CURLOPT_POSTFIELDS with string data.
				 *
				 * @param $handle
				 */
				$callback = function ( $handle ) use ( $file, $mime_type ) {
					if ( function_exists( 'curl_init' ) && function_exists( 'curl_exec' ) ) {
						$curl_file = new \CURLFile( $file, $mime_type );

						curl_setopt( // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_setopt
							$handle,
							CURLOPT_POSTFIELDS,
							array(
								'file' => $curl_file,
								'type' => 'voucher',
							)
						);
					}
				};

				add_action( 'http_api_curl', $callback, 10, 3 );
				$result = $this->get_sync_helper()->parse_response(
					$this->post(
						'vouchers/' . $id . '/files',
						array(
							'file' => $file,
							'type' => 'voucher',
						),
						array( 'Content-Type' => 'multipart/form-data' )
					)
				);
				remove_action( 'http_api_curl', $callback, 10 );
			} else {
				if ( file_exists( $file ) ) {
					$files = array(
						'file' => array(
							'filename' => basename( $file ),
							'binary'   => file_get_contents( $file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
						),
					);
				} else {
					$files = array();
				}

				$boundary       = uniqid();
				$request_body   = $this->build_multipart_data( $boundary, array( 'type' => 'voucher' ), $files );
				$content_length = strlen( $request_body );

				$callback = function ( &$handle ) use ( $request_body ) {
					$handle .= $request_body;

					return $handle;
				};

				add_action( 'requests-fsockopen.before_send', $callback, 10, 3 );
				$result = $this->get_sync_helper()->parse_response(
					$this->post(
						'vouchers/' . $id . '/files',
						array(),
						array(
							'Content-Type'   => 'multipart/form-data; boundary=' . $boundary,
							'Content-Length' => $content_length,
						)
					)
				);
				remove_action( 'requests-fsockopen.before_send', $callback, 10 );
			}

			return $result;
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return new \WP_Error( 'api-error', _x( 'Error while uploading file to voucher', 'lexoffice', 'woocommerce-germanized-pro' ) );
	}

	public function get_contact( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'contacts/' . $id ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	private function format_contact_str( $str ) {
		return sanitize_key( $str );
	}

	/**
	 * @param Customer $contact
	 *
	 * @return \WP_Error|array
	 */
	public function find_contact( $contact ) {
		$result = $this->search_contacts( $contact->get_email(), 'email' );

		if ( ! is_wp_error( $result ) ) {
			foreach ( $result['content'] as $customer ) {
				if ( isset( $customer['company'] ) ) {
					$company_name            = $customer['company']['name'];
					$has_vat_id              = ! empty( $customer['company']['vatRegistrationId'] );
					$contact_supports_vat_id = $contact->is_vat_exempt();
					$contact_persons         = is_array( $customer['company']['contactPersons'] ) ? $customer['company']['contactPersons'] : array();

					/**
					 * If there exist multiple contacts (companies) with different vat profiles (e.g. with VAT ID and without)
					 * match the actual tax profile too to prevent linking vouchers with invalid contacts.
					 */
					if ( count( $result['content'] ) > 1 && $contact_supports_vat_id !== $has_vat_id ) {
						continue;
					}

					if ( $this->format_contact_str( $contact->get_company() ) === $this->format_contact_str( $company_name ) ) {
						/**
						 * Right now lexoffice does not support syncing contacts with multiple contactPersons via API.
						 *
						 * @see https://developers.lexoffice.io/docs/#contacts-endpoint-retrieve-a-contact
						 */
						if ( count( $contact_persons ) > 1 ) {
							continue;
						}

						foreach ( $contact_persons as $contact_person ) {
							$first_name = $contact_person['firstName'];
							$last_name  = $contact_person['lastName'];

							if (
								strstr( $this->format_contact_str( $first_name ), $this->format_contact_str( $contact->get_first_name() ) ) &&
								strstr( $this->format_contact_str( $last_name ), $this->format_contact_str( $contact->get_last_name() ) )
							) {
								return $customer;
							}
						}
					}
				} else {
					$company_name = '';
					$first_name   = $customer['person']['firstName'];
					$last_name    = $customer['person']['lastName'];

					if (
						strstr( $this->format_contact_str( $first_name ), $this->format_contact_str( $contact->get_first_name() ) ) &&
						strstr( $this->format_contact_str( $last_name ), $this->format_contact_str( $contact->get_last_name() ) ) &&
						$this->format_contact_str( $contact->get_company() ) === $this->format_contact_str( $company_name )
					) {
						return $customer;
					}
				}
			}
		}

		return new \WP_Error( 500, 'Error matching contact' );
	}

	public function search_contacts( $term, $by = '' ) {
		if ( empty( $by ) ) {
			if ( is_numeric( $term ) ) {
				$by = 'number';
			} else {
				$by = 'name';
			}
		}

		$result = $this->get_sync_helper()->parse_response(
			$this->get(
				'contacts',
				array(
					'customer' => true,
					$by        => $term,
				)
			)
		);

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function create_contact( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'contacts', $data ) );
	}

	public function update_contact( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'contacts/' . $id, $data ) );
	}

	public function filter_contacts( $args ) {
		/**
		 * Available filters:
		 * - email
		 * - name (at least 3 chars)
		 * - number (contact customer number)
		 * - customer (true to only find customers)
		 * - vendor (true to only find vendors)
		 */
		$result = $this->get_sync_helper()->parse_response( $this->get( 'contacts', $args ) );

		return $result;
	}
}
