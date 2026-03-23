<?php

namespace Vendidero\Shiptastic\DPD\Api\Cloud;

use Vendidero\Shiptastic\API\REST;
use Vendidero\Shiptastic\DPD\Interfaces\LabelApi;
use Vendidero\Shiptastic\DPD\Label\Retoure;
use Vendidero\Shiptastic\DPD\Label\Simple;
use Vendidero\Shiptastic\DPD\Package;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class Api extends REST implements LabelApi {

	public function get_title() {
		return _x( 'DPD Cloud API', 'dpd', 'woocommerce-germanized-pro' );
	}

	public function get_url() {
		return $this->is_sandbox() ? 'https://cloud-stage.dpd.com/api/v1/' : 'https://cloud.dpd.com/api/v1/';
	}

	public function get_name() {
		return 'dpd_cloud';
	}

	protected function get_auth_instance() {
		return new HeaderAuth( $this );
	}

	protected function get_headers( $headers = array() ) {
		$headers['Version']  = '100';
		$headers['language'] = Package::get_api_language();

		$headers = parent::get_headers( $headers );

		return $headers;
	}

	protected function refresh_pickup_details() {
		$response = $this->get( 'ZipCodeRules' );

		if ( ! $response->is_error() ) {
			$response_body = $response->get_body( false );

			if ( isset( $response_body->ZipCodeRules ) ) {
				$pickup_details = array(
					'no_pickup_days' => explode( ',', trim( $response_body->ZipCodeRules->NoPickupDays ) ),
					'express_cutoff' => $response_body->ZipCodeRules->ExpressCutOff,
					'classic_cutoff' => $response_body->ZipCodeRules->ClassicCutOff,
				);

				set_transient( 'dpd_pickup_details', $pickup_details, DAY_IN_SECONDS * 30 );
			}

			return true;
		} else {
			delete_transient( 'dpd_pickup_details' );

			return false;
		}
	}

	public function get_parcel_shop_by_id( $id, $args ) {
		$args['address_1'] = '';
		$parcel_shops      = $this->get_parcel_shops( $args );
		$parcel_shop       = null;
		$id                = strtoupper( (string) $id );

		if ( ! empty( $parcel_shops ) ) {
			foreach ( $parcel_shops as $a_parcel_shop ) {
				/**
				 * Legacy search by shop id - use pudo_id by default
				 */
				if ( is_numeric( $id ) ) {
					$id_to_match = (string) $a_parcel_shop['ParcelShopID'];
				} else {
					$id_to_match = (string) Package::get_pudo_id_from_cloud_parcel_shop( $a_parcel_shop );
				}

				if ( $id_to_match === $id ) {
					$parcel_shop = $a_parcel_shop;
					break;
				}
			}
		}

		return $parcel_shop;
	}

	public function get_parcel_shops( $args, $limit = 20 ) {
		$args = wp_parse_args(
			$args,
			array(
				'postcode'  => '',
				'country'   => \Vendidero\Shiptastic\Package::get_base_country(),
				'city'      => '',
				'address_1' => '',
				'service'   => 'PickupByConsignee',
			)
		);

		$street       = 'null';
		$house_number = 'null';
		$city         = ! empty( $args['city'] ) ? $args['city'] : 'null';
		$postcode     = ! empty( $args['postcode'] ) ? $args['postcode'] : 'null';
		$country      = \Vendidero\Shiptastic\Package::get_country_iso_alpha3( $args['country'] );

		if ( ! empty( $args['address_1'] ) ) {
			$address_parts = wc_stc_split_shipment_street( $args['address_1'] );

			$street       = ! empty( $address_parts['street'] ) ? preg_replace( '/[^A-Za-z0-9 ]/', '', $address_parts['street'] ) : 'null';
			$house_number = ! empty( $address_parts['number'] ) ? $address_parts['number'] : 'null';
		}

		$endpoint = "ParcelShopFinder/{$limit}/{$street}/{$house_number}/{$postcode}/{$city}/{$country}/{$args['service']}/null";
		$endpoint = implode(
			'/',
			array_map(
				function ( $v ) {
					return rawurlencode( $v );
				},
				explode( '/', $endpoint )
			)
		);

		$result = $this->get( $endpoint );

		if ( ! $result->is_error() ) {
			return $result->get_body()['ParcelShopList'];
		} else {
			return array();
		}
	}

	public function get_pickup_details( $force_refresh = false ) {
		if ( $force_refresh ) {
			$this->refresh_pickup_details();
		}

		if ( ! get_transient( 'dpd_pickup_details' ) ) {
			$this->refresh_pickup_details();
		}

		$pickup_details = get_transient( 'dpd_pickup_details' );

		if ( ! $pickup_details || ! isset( $pickup_details['no_pickup_days'] ) ) {
			$pickup_details = array();
		}

		return wp_parse_args(
			$pickup_details,
			array(
				'no_pickup_days' => array(),
				'express_cutoff' => '12:00',
				'classic_cutoff' => '08:00',
			)
		);
	}

	protected function is_working_day( $datetime ) {
		$pickup_details = $this->get_pickup_details();
		$is_working_day = ( in_array( $datetime->format( 'd.m.Y' ), $pickup_details['no_pickup_days'], true ) ) ? false : true;

		if ( $is_working_day ) {
			$is_working_day = $datetime->format( 'N' ) > 5 ? false : true;
		}

		return $is_working_day;
	}

	/**
	 * @param $product_id
	 *
	 * @return \DateTime|false
	 */
	public function get_next_available_pickup_date( $product_id = 'Classic' ) {
		$product_id = strtolower( $product_id );
		$is_express = strstr( $product_id, 'express' ) ? true : false;

		try {
			$tz_obj         = new \DateTimeZone( 'Europe/Berlin' );
			$starting_date  = new \DateTime( 'now', $tz_obj );
			$pickup_details = $this->get_pickup_details();

			// In case current date greater cutoff time -> add one working day
			if ( $starting_date->format( 'Hi' ) > str_replace( ':', '', ( $is_express ? $pickup_details['express_cutoff'] : $pickup_details['classic_cutoff'] ) ) ) {
				$starting_date->add( new \DateInterval( 'P1D' ) );
			}

			while ( ! $this->is_working_day( $starting_date ) ) {
				$starting_date->add( new \DateInterval( 'P1D' ) );
			}

			return apply_filters( 'woocommerce_shiptastic_dpd_next_available_pickup_date', $starting_date, $product_id );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public function get_international_customs_terms() {
		return array();
	}

	public function get_international_customs_paper() {
		return array();
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function get_label( $label ) {
		$shipment  = $label->get_shipment();
		$is_return = 'return' === $label->get_type();
		$provider  = $shipment->get_shipping_provider_instance();

		$error                         = new ShipmentError();
		$label_supports_email_transmit = ( $label->supports_third_party_email_notification() || apply_filters( 'woocommerce_shiptastic_dpd_label_force_email_notification', false, $label ) );
		$house_number                  = $is_return ? $shipment->get_sender_address_street_number() : $shipment->get_address_street_number();
		$country                       = $is_return ? $shipment->get_sender_country() : $shipment->get_country();
		$address_2                     = $is_return ? $shipment->get_sender_address_2() : $shipment->get_address_2();
		$state                         = in_array( $country, array( 'US', 'CA' ), true ) ? ( $is_return ? $shipment->get_sender_state() : $shipment->get_state() ) : '';
		$item_names                    = array();
		$shipment_ref                  = apply_filters( 'woocommerce_shiptastic_dpd_label_api_reference', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Reference1' ), $label );
		$shipment_ref_2                = apply_filters( 'woocommerce_shiptastic_dpd_label_api_reference_2', empty( $address_2 ) ? $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Reference2' ) : $address_2, $label );

		foreach ( $shipment->get_items() as $item ) {
			$item_names[] = $item->get_name();
		}

		$address = array(
			'Gender'     => 'none',
			'Company'    => mb_substr( $is_return ? $shipment->get_sender_company() : $shipment->get_company(), 0, 50 ),
			'Salutation' => '',
			'FirstName'  => mb_substr( $is_return ? $shipment->get_sender_first_name() : $shipment->get_first_name(), 0, 50 ),
			'LastName'   => mb_substr( $is_return ? $shipment->get_sender_last_name() : $shipment->get_last_name(), 0, 50 ),
			'Street'     => mb_substr( $is_return ? $shipment->get_sender_address_street() : $shipment->get_address_street(), 0, 50 ),
			'HouseNo'    => strtolower( mb_substr( empty( $house_number ) ? '0' : $house_number, 0, 8 ) ), // Somehow DPD cannot parse capital house number suffixes, e.g. 53 C
			'Country'    => mb_substr( $country, 0, 2 ),
			'ZipCode'    => $is_return ? $shipment->get_sender_postcode() : $shipment->get_postcode(),
			'City'       => mb_substr( $is_return ? $shipment->get_sender_city() : $shipment->get_city(), 0, 50 ),
			'State'      => mb_substr( $state, 0, 2 ),
			'Phone'      => mb_substr( $is_return ? $shipment->get_sender_phone() : $shipment->get_phone(), 0, 20 ),
			'Mail'       => mb_substr( $is_return ? $shipment->get_sender_email() : $shipment->get_email(), 0, 50 ),
		);

		$parcel_shop_id = '';

		if ( ! $is_return && 'Shop_Delivery' === $label->get_product_id() && $label->get_parcel_shop_id() ) {
			$pickup_code = $label->get_parcel_shop_id();

			if ( ! empty( $pickup_code ) ) {
				$request['ParcelShopID'] = $pickup_code;
				$parcel_shop_id          = $pickup_code;

				$label_supports_email_transmit = true;

				if ( is_callable( array( $shipment, 'get_billing_address' ) ) ) {
					$address = array(
						'Gender'     => 'none',
						'Salutation' => '',
						'FirstName'  => mb_substr( $shipment->get_billing_first_name(), 0, 50 ),
						'LastName'   => mb_substr( $shipment->get_billing_last_name(), 0, 50 ),
						'Street'     => mb_substr( $shipment->get_billing_address_street(), 0, 50 ),
						'HouseNo'    => strtolower( mb_substr( empty( $shipment->get_billing_address_street_number() ) ? '0' : $shipment->get_billing_address_street_number(), 0, 8 ) ), // Somehow DPD cannot parse capital house number suffixes, e.g. 53 C
						'Country'    => mb_substr( $shipment->get_billing_country(), 0, 2 ),
						'ZipCode'    => $shipment->get_billing_postcode(),
						'City'       => mb_substr( $shipment->get_billing_city(), 0, 50 ),
						'State'      => '',
						'Phone'      => mb_substr( $shipment->get_billing_phone(), 0, 20 ),
						'Mail'       => mb_substr( $shipment->get_billing_email(), 0, 50 ),
					);
				}

				$address['Company'] = '';
			}
		}

		/**
		 * Force email, phone transmission for predict, returns and international shipments
		 */
		if ( ! $label_supports_email_transmit && ! $is_return && ! strstr( strtolower( $label->get_product_id() ), 'predict' ) && ! $shipment->is_shipping_inner_eu() && ! $shipment->is_shipping_international() ) {
			unset( $address['Phone'] );
			unset( $address['Mail'] );
		}

		$request = array(
			'OrderAction'   => 'startOrder',
			'OrderSettings' => array(
				'LabelSize'          => $label->get_print_format(),
				'LabelStartPosition' => apply_filters( 'woocommerce_shiptastic_dpd_label_start_position', 'UpperLeft', $label ),
				'ShipDate'           => $label->get_pickup_date() ? $label->get_pickup_date() : date_i18n( 'Y-m-d' ),
			),
			'OrderDataList' => array(
				array(
					'ParcelShopID' => $parcel_shop_id,
					'ShipAddress'  => $address,
					'ParcelData'   => array(
						'ShipService'    => $label->get_product_id(),
						'Weight'         => $label->get_weight(),
						'Content'        => mb_substr( apply_filters( 'woocommerce_shiptastic_dpd_label_api_content_desc', implode( ', ', $item_names ), $label ), 0, 35 ),
						'YourInternalID' => mb_substr( $shipment->get_shipment_number(), 0, 35 ),
						'Reference1'     => $shipment_ref,
						'Reference2'     => $shipment_ref_2,
					),
				),
			),
		);

		$clean_request = $this->clean_request( $request );
		$response      = $this->post( 'setOrder', apply_filters( 'woocommerce_shiptastic_dpd_label_api_request', $clean_request, $label ) );

		if ( ! $response->is_error() ) {
			$response_body  = $response->get_body( false );
			$label_response = $response_body->LabelResponse;
			$pdf            = base64_decode( $label_response->LabelPDF ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

			$label->set_number( $label_response->LabelDataList[0]->ParcelNo );

			if ( ! empty( $label_response->LabelPDF ) ) {
				if ( $path = $label->upload_label_file( $pdf ) ) {
					$label->set_path( $path );
				} else {
					$error->add( 'upload', _x( 'Error while uploading DPD label.', 'dpd', 'woocommerce-germanized-pro' ) );
				}
			}

			$label->save();
		} else {
			$error = $response->get_error();
		}

		return $error->has_errors() ? $error : true;
	}

	protected function parse_error( $response ) {
		parent::parse_error( $response );

		$code  = $response->get_code();
		$body  = $response->get_body( false );
		$error = new ShipmentError();

		if ( isset( $body->ErrorDataList ) && is_array( $body->ErrorDataList ) ) {
			foreach ( $body->ErrorDataList as $api_error ) {
				$error_message = ( ( isset( $api_error->ErrorMsgLong ) && ! empty( $api_error->ErrorMsgLong ) ) ? $api_error->ErrorMsgLong : $api_error->ErrorMsgShort ) . ' (' . $api_error->ErrorCode . ')';
				$error->add( $code, $error_message );
			}
		}

		if ( $error->has_errors() ) {
			$response->set_error( $error );
		}

		return $response;
	}

	protected function parse_response( $response_code, $response_body, $response_headers ) {
		$response      = parent::parse_response( $response_code, $response_body, $response_headers );
		$response_body = $response->get_body( false );

		if ( isset( $response_body->Ack ) && true === $response_body->Ack ) {
			return $response;
		} elseif ( isset( $response_body->ErrorDataList ) && is_array( $response_body->ErrorDataList ) ) {
			$error = new ShipmentError();
			$response->set_code( 400 );

			foreach ( $response_body->ErrorDataList as $api_error ) {
				$error_message = ( ( isset( $api_error->ErrorMsgLong ) && ! empty( $api_error->ErrorMsgLong ) ) ? $api_error->ErrorMsgLong : $api_error->ErrorMsgShort ) . ' (' . $api_error->ErrorCode . ')';
				$error->add( $api_error->ErrorCode, $error_message );

				if ( 'CLOUD_API_NOUSERACCESS' === $api_error->ErrorCode ) {
					$response->set_code( 403 );
				}
			}

			$response->set_error( $error );
		}

		return $response;
	}

	public function test_connection() {
		$is_connected = false;

		if ( $this->get_auth_api()->is_connected() ) {
			$response = $this->get( 'getOrderStatus/123/12207' );

			if ( $response->is_error() && 400 === $response->get_code() ) {
				$is_connected = true;
			}
		}

		return $is_connected;
	}
}
