<?php

class WC_GZDP_VAT_Validation {

	protected $api_url = 'https://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl';

	protected $client = null;

	protected $options = array(
		'debug'            => false,
		'requester_vat_id' => array(
			'country' => '',
			'number'  => '',
		),
	);

	protected $valid = false;

	protected $data = array();

	protected $errors = false;

	protected $instance = null;

	public function __construct( $options = array() ) {
		$this->options  = wp_parse_args( $options, $this->options );
		$this->instance = WC_germanized_pro();

		if ( ! class_exists( 'SoapClient' ) ) {
			$this->valid = false;

			$this->instance->log( 'Missing SOAP Client.', 'error', 'vat-validation' );
		} else {
			try {
				$this->client = new SoapClient( $this->api_url, array( 'trace' => true ) );
			} catch ( Exception $e ) {
				$this->instance->log( sprintf( 'Error %s while setting up the SOAP Client for VAT validation: %s', $e->getCode(), $e->getMessage() ), 'error', 'vat-validation' );

				$this->valid = false;
			}
		}
	}

	public function check( $country, $nr, $retry = 0 ) {
		$rs           = null;
		$this->errors = new WP_Error();

		if ( $this->client ) {
			try {
				$args = array(
					'countryCode' => $country,
					'vatNumber'   => $nr,
				);

				if ( ! empty( $this->options['requester_vat_id']['number'] ) ) {
					$args['requesterCountryCode'] = $this->options['requester_vat_id']['country'];
					$args['requesterVatNumber']   = $this->options['requester_vat_id']['number'];
				} else {
					$this->instance->log( sprintf( 'The requester VAT ID is missing.' ), 'info', 'vat-validation' );
				}

				$rs = $this->client->checkVatApprox( $args );

				if ( $rs->valid ) {
					$this->instance->log( sprintf( 'Successfully validated: %s', $args['countryCode'] . $args['vatNumber'] ), 'info', 'vat-validation' );

					$this->valid = true;
					$this->data  = array(
						'name'       => $this->parse_string( isset( $rs->name ) ? $rs->name : '' ),
						'identifier' => $this->parse_string( isset( $rs->requestIdentifier ) ? $rs->requestIdentifier : '' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						'company'    => $this->parse_string( isset( $rs->traderName ) ? $rs->traderName : '' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						'address'    => $this->parse_string( isset( $rs->traderAddress ) ? $rs->traderAddress : '' ), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						'date'       => date_i18n( 'Y-m-d H:i:s' ),
						'raw'        => (array) $rs,
						'vat_id'     => $country . $nr,
					);
				} else {
					$this->instance->log( sprintf( 'VAT is invalid: %s', $args['countryCode'] . $args['vatNumber'] ), 'info', 'vat-validation' );

					$this->valid = false;
					$this->data  = array();

					$this->errors->add( 'vat-id-invalid', __( 'The VAT ID you\'ve provided is invalid.', 'woocommerce-germanized-pro' ) );
				}
			} catch ( SoapFault $e ) {
				$this->instance->log( sprintf( 'SOAP Error (%s) while performing VAT ID validation: %s', $e->getCode(), $e->getMessage() ), 'error', 'vat-validation' );
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
		}

		return apply_filters( 'woocommerce_gzdp_vat_validation_eu_result', $this->valid, $country, $nr, $rs, $this->options, $this->errors );
	}

	public function get_temp_error_codes() {
		return array( 'GLOBAL_MAX_CONCURRENT_REQ', 'MS_MAX_CONCURRENT_REQ', 'MS_UNAVAILABLE', 'SERVICE_UNAVAILABLE', 'TIMEOUT' );
	}

	/**
	 * @return bool|WP_Error
	 */
	public function get_error_messages() {
		return ( is_wp_error( $this->errors ) && wc_gzd_wp_error_has_errors( $this->errors ) ) ? $this->errors : false;
	}

	public function is_valid() {
		return $this->valid;
	}

	public function get_name() {
		return isset( $this->data['name'] ) ? $this->data['name'] : '';
	}

	public function get_company() {
		return isset( $this->data['company'] ) ? $this->data['company'] : '';
	}

	public function get_identifier() {
		return isset( $this->data['identifier'] ) ? $this->data['identifier'] : '';
	}

	public function get_date() {
		return isset( $this->data['date'] ) ? $this->data['date'] : '';
	}

	public function get_formatted_address() {
		$address = array_filter( array( trim( $this->get_name() ), trim( $this->get_company() ), trim( $this->get_address() ) ) );

		return implode( ' ', $address );
	}

	public function get_raw() {
		return isset( $this->data['raw'] ) ? $this->data['raw'] : '';
	}

	public function get_data() {
		return $this->data;
	}

	public function get_address() {
		return isset( $this->data['address'] ) ? $this->data['address'] : '';
	}

	public function is_debug() {
		return ( true === $this->options['debug'] );
	}

	protected function parse_string( $string ) {
		return ( '---' !== $string ? preg_replace( '/\s+/u', ' ', $string ) : false );
	}
}
