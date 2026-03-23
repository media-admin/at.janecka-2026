<?php

class WC_GZDP_VAT_Validation_Rest extends WC_GZDP_VAT_Validation {

	protected $api_url = 'https://ec.europa.eu/taxation_customs/vies/rest-api/';

	public function __construct( $options = array() ) {
		$this->options  = wp_parse_args( $options, $this->options );
		$this->instance = WC_germanized_pro();
	}

	public function check( $country, $nr, $retry = 0 ) {
		$this->errors = new WP_Error();

		try {
			$url      = untrailingslashit( trailingslashit( $this->api_url ) . '/check-vat-number' );
			$response = wp_remote_post(
				esc_url_raw( $url ),
				array(
					'headers' => array( 'Content-Type' => 'application/json' ),
					'timeout' => 100,
					'body'    => wp_json_encode(
						array(
							'countryCode'              => $country,
							'vatNumber'                => $nr,
							'requesterMemberStateCode' => $this->options['requester_vat_id']['country'],
							'requesterNumber'          => $this->options['requester_vat_id']['number'],
						),
						JSON_PRETTY_PRINT
					),
				)
			);

			if ( false !== $response ) {
				if ( is_wp_error( $response ) ) {
					throw new Exception( $response->get_error_message() );
				}

				$code = wp_remote_retrieve_response_code( $response );
				$body = wp_remote_retrieve_body( $response );

				if ( 200 === $code ) {
					$json_body = json_decode( $body, true );

					if ( $json_body ) {
						if ( true === $json_body['valid'] ) {
							$trader_name = $this->parse_string( isset( $json_body['traderName'] ) ? $json_body['traderName'] : '' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							$this->valid = true;
							$this->data  = array(
								'name'       => $this->parse_string( isset( $json_body['name'] ) ? $json_body['name'] : '' ),
								'identifier' => $this->parse_string( isset( $json_body['requestIdentifier'] ) ? $json_body['requestIdentifier'] : '' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								'company'    => empty( $trader_name ) ? $this->parse_string( isset( $json_body['name'] ) ? $json_body['name'] : '' ) : $trader_name, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								'address'    => $this->parse_string( isset( $json_body['address'] ) ? $json_body['address'] : '' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
								'date'       => date_i18n( 'Y-m-d H:i:s' ),
								'raw'        => $json_body,
								'vat_id'     => $country . $nr,
							);
						} else {
							$this->instance->log( sprintf( 'VAT is invalid: %s', $country . $nr ), 'info', 'vat-validation' );

							$this->valid = false;
							$this->data  = array();

							$this->errors->add( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
						}
					}
				} else {
					$json_body = json_decode( $body, true );
					$error     = 'Missing error message';
					$code      = 500;

					if ( $json_body && isset( $json_body['errorWrappers'] ) ) {
						$error = $json_body['errorWrappers']['message'];
						$code  = $json_body['errorWrappers']['error'];
					}

					throw new Exception( $error, $code );
				}
			}
		} catch ( Exception $e ) {
			$this->instance->log( sprintf( 'REST Error (%s) while performing VAT ID validation: %s', $e->getCode(), $e->getMessage() ), 'error', 'vat-validation' );
			$error_msg = strtoupper( $e->getMessage() );

			if ( in_array( $error_msg, $this->get_temp_error_codes(), true ) ) {
				if ( apply_filters( 'woocommerce_gzdp_vat_validation_allow_retry', ( $retry <= 1 ), $retry ) ) {
					++$retry;
					$this->instance->log( sprintf( 'Retry #%s due to error.', $retry ), 'error', 'vat-validation' );

					return $this->check( $country, $nr, $retry );
				}
			}

			$this->valid = false;
			$this->data  = array();

			if ( in_array( $error_msg, $this->get_temp_error_codes(), true ) ) {
				$this->errors->add( 'vat-api-error', __( 'The official EU API for validating VAT IDs is experiencing technical difficulties. Please try again later or contact us.', 'woocommerce-germanized-pro' ) );
			} else {
				$this->errors->add( 'vat-request-error', __( 'There was an error while validating your VAT ID. Please try again in a few minutes.', 'woocommerce-germanized-pro' ) );
			}
		}

		return apply_filters( 'woocommerce_gzdp_vat_validation_rest_result', $this->valid, $country, $nr );
	}
}
