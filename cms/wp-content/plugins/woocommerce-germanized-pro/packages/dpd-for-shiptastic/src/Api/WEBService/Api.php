<?php

namespace Vendidero\Shiptastic\DPD\Api\WEBService;

use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\API\REST;
use Vendidero\Shiptastic\DPD\Interfaces\LabelApi;
use Vendidero\Shiptastic\DPD\Label\Retoure;
use Vendidero\Shiptastic\DPD\Label\Simple;
use Vendidero\Shiptastic\DPD\Package;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class Api extends REST implements LabelApi {

	public function get_title() {
		return _x( 'DPD WEB.Service API', 'dpd', 'woocommerce-germanized-pro' );
	}

	public function get_url() {
		return 'https://ws.paketomat.at/restapi106/service.php';
	}

	public function get_name() {
		return 'dpd_webservice';
	}

	protected function get_auth_instance() {
		return new BodyAuth( $this );
	}

	public function get_parcel_shop_by_id( $id, $args ) {
		$args['address_1'] = '';
		$parcel_shops      = $this->get_parcel_shops( $args );
		$parcel_shop       = null;
		$id                = strtoupper( (string) $id );

		if ( ! empty( $parcel_shops ) ) {
			foreach ( $parcel_shops as $a_parcel_shop ) {
				if ( is_numeric( $id ) ) {
					$id_to_match = preg_replace( '/[^0-9]/', '', (string) $a_parcel_shop['id'] );
				} else {
					$id_to_match = (string) $a_parcel_shop['id'];
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
				'type'      => '0', // 0 = Locker + Shops, 1 = Shops only, 2 = Locker only
			)
		);

		$request = array(
			'service'  => 'PaketomatRest',
			'function' => 'shopfinder',
			'data'     => array(
				'username' => 'shopfinder',
				'password' => '6bc79aeef2870c8d17ebae4a0374b6fe',
				'strasse'  => $args['address_1'],
				'plz'      => $args['postcode'],
				'land'     => $args['country'],
				'ort'      => $args['city'],
				'typ'      => $args['type'],
			),
		);

		$clean_request = $this->clean_request( $request );
		$response      = $this->post( '', apply_filters( 'woocommerce_shiptastic_dpd_webservice_parcel_shops_api_request', $clean_request, $args ) );
		$parcel_shops  = array();

		if ( ! $response->is_error() ) {
			$parcel_shops = $response->get_body()['result'];
		}

		return $parcel_shops;
	}

	public function get_international_customs_terms() {
		return array();
	}

	public function get_international_customs_paper() {
		return array();
	}

	protected function is_working_day( $datetime ) {
		$is_working_day = $datetime->format( 'N' ) > 5 ? false : true;

		return $is_working_day;
	}

	/**
	 * @param string $product_id
	 * @return \DateTime|false
	 */
	public function get_next_available_pickup_date( $product_id = '' ) {
		try {
			$tz_obj        = new \DateTimeZone( 'Europe/Berlin' );
			$starting_date = new \DateTime( 'now', $tz_obj );

			// In case current date greater cutoff time -> add one working day
			if ( $starting_date->format( 'Hi' ) > '1700' ) {
				$starting_date->add( new \DateInterval( 'P1D' ) );
			}

			while ( ! $this->is_working_day( $starting_date ) ) {
				$starting_date->add( new \DateInterval( 'P1D' ) );
			}

			return apply_filters( 'woocommerce_shiptastic_dpd_next_available_pickup_date', $starting_date );
		} catch ( \Exception $e ) {
			return false;
		}
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
		$shipment_ref                  = apply_filters( 'woocommerce_shiptastic_dpd_label_api_reference', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Reference1' ), $label );
		$shipment_ref_2                = apply_filters( 'woocommerce_shiptastic_dpd_label_api_reference_2', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Reference2' ), $label );
		$weight                        = Package::to_gramm( $label->get_weight() );
		$length                        = Package::to_mm( $label->get_length() );
		$width                         = Package::to_mm( $label->get_width() );
		$height                        = Package::to_mm( $label->get_height() );
		$parcel_volume                 = str_pad( $length, 4, '0', STR_PAD_LEFT ) . str_pad( $width, 4, '0', STR_PAD_LEFT ) . str_pad( $height, 4, '0', STR_PAD_LEFT );
		$product_id_parts              = explode( '_', $label->get_product_id() );
		$parcel_type                   = $product_id_parts[0];
		$product_1                     = count( $product_id_parts ) > 1 ? $product_id_parts[1] : '';

		if ( in_array( $parcel_type, array( 'B2B', 'B2C', '2S' ), true ) ) {
			if ( $weight <= 3000 ) {
				$product_1 = 'KP';
			} else {
				$product_1 = 'NP';
			}

			if ( in_array( $parcel_type, array( 'B2B', 'B2C' ), true ) && $is_return ) {
				$product_1 = 'RETURN';
			}
		}

		$main_name = $is_return ? ( $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_formatted_sender_full_name() ) : ( $shipment->get_company() ? $shipment->get_company() : $shipment->get_formatted_full_name() );

		$address = array(
			'name'      => mb_substr( $main_name, 0, 50 ),
			'anschrift' => mb_substr( $is_return ? $shipment->get_sender_address_1() : $shipment->get_address_1(), 0, 50 ),
			'zusatz'    => mb_substr( $is_return ? $shipment->get_sender_address_2() : $shipment->get_address_2(), 0, 50 ),
			'land'      => mb_substr( $is_return ? $shipment->get_sender_country() : $shipment->get_country(), 0, 2 ),
			'plz'       => $is_return ? $shipment->get_sender_postcode() : $shipment->get_postcode(),
			'ort'       => mb_substr( $is_return ? $shipment->get_sender_city() : $shipment->get_city(), 0, 50 ),
			'tel'       => mb_substr( $is_return ? $shipment->get_sender_phone() : $shipment->get_phone(), 0, 30 ),
			'mail'      => mb_substr( $is_return ? $shipment->get_sender_email() : $shipment->get_email(), 0, 50 ),
			'bezugsp'   => mb_substr( $is_return ? $shipment->get_formatted_sender_full_name() : $shipment->get_formatted_full_name(), 0, 50 ),
		);

		$shipper_address = array(
			'absender_name'      => mb_substr( $is_return ? $shipment->get_formatted_full_name() : $shipment->get_formatted_sender_full_name(), 0, 50 ),
			'absender_adresse '  => mb_substr( $is_return ? $shipment->get_address_1() : $shipment->get_sender_address_1(), 0, 50 ),
			'absender_adresse2 ' => mb_substr( $is_return ? $shipment->get_address_2() : $shipment->get_sender_address_2(), 0, 50 ),
			'absender_land'      => mb_substr( $is_return ? $shipment->get_country() : $shipment->get_sender_country(), 0, 2 ),
			'absender_plz'       => $is_return ? $shipment->get_postcode() : $shipment->get_sender_postcode(),
			'absender_ort'       => mb_substr( $is_return ? $shipment->get_city() : $shipment->get_sender_city(), 0, 50 ),
			'absender_tel'       => mb_substr( $is_return ? $shipment->get_phone() : $shipment->get_sender_phone(), 0, 30 ),
			'absender_mail'      => mb_substr( $is_return ? $shipment->get_email() : $shipment->get_sender_email(), 0, 50 ),
			'absender_mail_name' => mb_substr( $is_return ? $shipment->get_formatted_full_name() : ( $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_formatted_sender_full_name() ), 0, 30 ),
		);

		if ( ! $is_return && '2S' === $label->get_product_id() && $label->get_parcel_shop_id() ) {
			$pickup_code = $label->get_parcel_shop_id();

			if ( ! empty( $pickup_code ) ) {
				$address['kdnr']               = $pickup_code;
				$label_supports_email_transmit = true;
			}
		}

		/**
		 * Force email, phone transmission for PrimeTime, returns and international shipments
		 */
		if ( ! $label_supports_email_transmit && ! $is_return && 'PT' !== $parcel_type && ! $shipment->is_shipping_inner_eu() && ! $shipment->is_shipping_international() ) {
			unset( $address['tel'] );
			unset( $address['mail'] );
		}

		$request = array(
			'service'  => 'PaketomatRest',
			'function' => 'getLabel',
			'data'     => array(
				'pakanz'     => 1,
				'empfaenger' => $address,
				'paket'      => array(
					'liefernr' => $shipment_ref . '~' . $shipment_ref_2,
					'pakettyp' => $parcel_type,
					'gewicht'  => $weight,
					'volumen'  => $parcel_volume,
				),
				'vdat'       => $label->get_pickup_date() ? date_i18n( 'Ymd', $label->get_pickup_date() ) : date_i18n( 'Ymd' ),
				'absender'   => $shipper_address,
				'format'     => 'PDF',
				'dfu'        => '0',
				'produkt1'   => $product_1,
			),
		);

		if ( 'DPD' === $label->get_product_id() && $label->has_service( 'predict' ) ) {
			$request['data']['produkt6'] = array(
				'pred' => $label->get_service_prop( 'predict', 'email' ) ? $label->get_service_prop( 'predict', 'email' ) : $shipment->get_email(),
			);
		}

		if ( in_array( $label->get_product_id(), array( 'DPD', 'B2C' ), true ) && $label->has_service( 'higher_insurance' ) ) {
			$amount = ( (float) wc_format_decimal( $label->get_service_prop( 'higher_insurance', 'amount' ), 2 ) ) * 100;

			$request['data']['produkt2'] = array(
				'hv' => $amount,
			);
		}

		$clean_request = $this->clean_request( $request );
		$response      = $this->post( '', apply_filters( 'woocommerce_shiptastic_dpd_webservice_label_api_request', $clean_request, $label ) );

		if ( ! $response->is_error() ) {
			$result = $this->get_main_body( $response->get_body() );

			if ( ! empty( $result['paknr'] ) ) {
				$label->set_number( $result['paknr'] );
				$label->save();

				if ( ! empty( $result['label'] ) ) {
					$result = $label->download_label_file( $result['label'] );

					if ( ! $result ) {
						$error->add_soft_error( 'upload', _x( 'Error while uploading DPD label.', 'dpd', 'woocommerce-germanized-pro' ) );
					}
				}

				$label->save();
			}
		} else {
			$error = $response->get_error();
		}

		return $error->has_errors() ? $error : true;
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function cancel_label( $label ) {
		$request = array(
			'service'  => 'PaketomatRest',
			'function' => 'cancelByTracknr',
			'data'     => array(
				'paknr' => $label->get_number(),
			),
		);

		$clean_request = $this->clean_request( $request );
		$response      = $this->post( '', apply_filters( 'woocommerce_shiptastic_dpd_webservice_cancel_label_api_request', $clean_request, $label ) );

		if ( $response->is_error() ) {
			return $response->get_error();
		} else {
			return true;
		}
	}

	/**
	 * @param string $endpoint
	 * @param array  $query_args
	 *
	 * @return Response
	 */
	public function post( $endpoint = '', $body_args = array(), $header = array() ) {
		$body_args = array_replace_recursive(
			array(
				'data' => $this->get_auth_api()->get_body(),
			),
			$body_args
		);

		return parent::post( $endpoint, $body_args, $header );
	}

	protected function parse_error( $response ) {
		$result = $this->get_main_body( $response->get_body() );

		if ( ! empty( $result['err_code'] ) || ! empty( $result['msg'] ) ) {
			$error     = new ShipmentError();
			$error_msg = wc_clean( ! empty( $result['err_code'] ) ? $result['err_code'] : $result['msg'] );
			$error->add( 'error', $error_msg );
			$response->set_error( $error );
		}

		return $response;
	}

	protected function get_main_body( $body ) {
		if ( is_array( $body['result'] ) ) {
			$has_string_keys = count( array_filter( array_keys( $body['result'] ), 'is_string' ) ) > 0;

			if ( $has_string_keys ) {
				$main_body = $body['result'];
			} else {
				$main_body = $body['result'][0];
			}
		} else {
			$main_body = $body['result'];
		}

		return $main_body;
	}

	protected function parse_response( $response_code, $response_body, $response_headers ) {
		$response = parent::parse_response( $response_code, $response_body, $response_headers );
		$result   = $this->get_main_body( $response->get_body() );

		if ( ! empty( $result['err_code'] ) || ( ! empty( $result['state'] ) && 'error' === $result['state'] ) ) {
			$response->set_code( 400 );
		}

		return $response;
	}

	public function test_connection() {
		$is_connected = false;

		if ( $this->get_auth_api()->is_connected() ) {
			$request = array(
				'service'  => 'PaketomatRest',
				'function' => 'cancelByTracknr',
				'data'     => array(
					'paknr' => '123',
				),
			);

			$clean_request = $this->clean_request( $request );
			$response      = $this->post( '', $clean_request );

			if ( $response->is_error() && 400 === $response->get_code() ) {
				$body = $this->get_main_body( $response->get_body() );

				if ( strstr( $body['err_code'], 'FR04' ) ) {
					$is_connected = true;
				}
			}
		}

		return $is_connected;
	}
}
