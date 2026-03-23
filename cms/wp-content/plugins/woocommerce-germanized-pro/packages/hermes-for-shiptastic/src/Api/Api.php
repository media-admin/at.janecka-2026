<?php

namespace Vendidero\Shiptastic\Hermes\Api;

use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\API\REST;
use Vendidero\Shiptastic\Encoding;
use Vendidero\Shiptastic\Hermes\Label\Retoure;
use Vendidero\Shiptastic\Hermes\Label\Simple;
use Vendidero\Shiptastic\Hermes\Package;
use Vendidero\Shiptastic\ImageToPDF;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

/**
 * Hermes API
 *
 * @see https://de-api-int.hermesworld.com/docs/api#Order
 */
class Api extends REST {

	public function get_title() {
		return _x( 'Hermes API', 'hermes', 'woocommerce-germanized-pro' );
	}

	public function get_url() {
		return $this->is_sandbox() ? 'https://de-api-int.hermesworld.com/services/hsi' : 'https://de-api.hermesworld.com/services/hsi';
	}

	public function get_name() {
		return 'hermes';
	}

	protected function get_auth_instance() {
		return new OAuth( $this );
	}

	protected function get_language( $country ) {
		return in_array( $country, array( 'DE', 'AT' ), true ) ? 'DE' : 'EN';
	}

	/**
	 * @param Retoure $label
	 *
	 * @return Response|\WP_Error
	 */
	protected function get_return_label( $label ) {
		$shipment = $label->get_shipment();
		$provider = Package::get_hermes_shipping_provider();

		if ( 'Pickup' === $label->get_product_id() ) {
			$request = array(
				'pickupName'    => array(
					'firstname' => $shipment->get_sender_company() ? '' : $shipment->get_sender_first_name(),
					'lastname'  => $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_sender_last_name(),
				),
				'pickupAddress' => array(
					'street'           => $shipment->get_sender_address_street_number() ? $shipment->get_sender_address_street() : $shipment->get_sender_address_1(),
					'houseNumber'      => $shipment->get_sender_address_street_number(),
					'addressAddition'  => $shipment->get_sender_address_2(),
					'addressAddition2' => $shipment->get_sender_address_street_number() ? $shipment->get_sender_address_street_addition() : '',
					'zipCode'          => $shipment->get_sender_postcode(),
					'town'             => $shipment->get_sender_city(),
					'countryCode'      => $shipment->get_sender_country(),
				),
				'pickupDate'    => $label->get_pickup_date(),
			);

			$request  = $this->clean_request( $request );
			$response = $this->post(
				'returnpickuporders',
				apply_filters( 'woocommerce_shiptastic_hermes_return_pickup_label_api_request', $request, $label ),
				array(
					'Accept-Language' => $this->get_language( $shipment->get_country() ),
				)
			);
		} else {
			$request_services  = array();
			$shipment_order_id = '';

			if ( $order_shipment = $shipment->get_order_shipment() ) {
				foreach ( array_reverse( $order_shipment->get_simple_shipments( true ) ) as $shipment ) {
					if ( $shipment_label = $shipment->get_label() ) {
						if ( is_a( $shipment_label, '\Vendidero\Shiptastic\Hermes\Label\Simple' ) ) {
							$shipment_order_id = $shipment_label->get_hermes_order_id();
						}
						break;
					}
				}
			}

			if ( ! Package::is_self_service_customer() ) {
				$request = array(
					'receiverName'     => array(
						'firstname' => $shipment->get_sender_company() ? '' : $shipment->get_sender_first_name(),
						'lastname'  => $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_sender_last_name(),
					),
					'clientReference'  => $provider->get_formatted_label_reference( $label, 'return', 'clientReference' ),
					'clientReference2' => $provider->get_formatted_label_reference( $label, 'return', 'clientReference2' ),
					'receiverAddress'  => array(
						'street'           => $shipment->get_sender_address_street_number() ? $shipment->get_sender_address_street() : $shipment->get_sender_address_1(),
						'houseNumber'      => $shipment->get_sender_address_street_number(),
						'addressAddition'  => $shipment->get_sender_address_2(),
						'addressAddition2' => $shipment->get_sender_address_street_number() ? $shipment->get_sender_address_street_addition() : '',
						'zipCode'          => $shipment->get_sender_postcode(),
						'town'             => $shipment->get_sender_city(),
						'countryCode'      => $shipment->get_sender_country(),
					),
					'senderAddress'    => array(
						'street'           => $shipment->get_address_street_number() ? $shipment->get_address_street() : $shipment->get_address_1(),
						'houseNumber'      => $shipment->get_address_street_number(),
						'addressAddition'  => $shipment->get_address_2(),
						'addressAddition2' => $shipment->get_address_street_number() ? $shipment->get_address_street_addition() : '',
						'zipCode'          => $shipment->get_postcode(),
						'town'             => $shipment->get_city(),
						'countryCode'      => $shipment->get_country(),
					),
					'senderName'       => array(
						'firstname' => $shipment->get_company() ? '' : $shipment->get_first_name(),
						'lastname'  => $shipment->get_company() ? $shipment->get_company() : $shipment->get_last_name(),
					),
					'parcel'           => array(
						'parcelWeight' => absint( wc_get_weight( $shipment->get_total_weight(), 'g', $shipment->get_weight_unit() ) ),
						'productType'  => 'PARCEL',
					),
				);

				foreach ( $label->get_services() as $service ) {
					$service_name = lcfirst( $service );

					switch ( $service ) {
						default:
							$request_services[ $service_name ] = true;
							break;
					}
				}

				if ( $shipment->has_dimensions() ) {
					$request['parcel']['parcelDepth']  = absint( wc_get_dimension( $shipment->get_length(), 'mm', $shipment->get_dimension_unit() ) );
					$request['parcel']['parcelWidth']  = absint( wc_get_dimension( $shipment->get_width(), 'mm', $shipment->get_dimension_unit() ) );
					$request['parcel']['parcelHeight'] = absint( wc_get_dimension( $shipment->get_height(), 'mm', $shipment->get_dimension_unit() ) );
				}

				if ( ! empty( $request_services ) ) {
					$request['service'] = $request_services;
				}
			} elseif ( empty( $shipment_order_id ) ) {
				return new \WP_Error( 'create_label', _x( 'Please make sure that a valid label to the original shipment exists.', 'hermes', 'woocommerce-germanized-pro' ) );
			}

			if ( ! empty( $shipment_order_id ) ) {
				$request['shipmentOrderID'] = $shipment_order_id;
			}

			$request  = $this->clean_request( $request );
			$response = $this->post(
				'returnorders/labels',
				apply_filters( 'woocommerce_shiptastic_hermes_return_label_api_request', $request, $label ),
				array(
					'Accept-Language' => $this->get_language( $shipment->get_country() ),
					'accept'          => 'QR' === $label->get_product_id() ? 'application/x-qrcode-png+json' : 'application/shippinglabel-pdf+json',
				)
			);
		}

		return $response;
	}

	/**
	 * @param Simple $label
	 *
	 * @return Response|\WP_Error}
	 */
	protected function get_simple_label( $label ) {
		$shipment         = $label->get_shipment();
		$request_services = array();
		$provider         = Package::get_hermes_shipping_provider();

		$request = array(
			'receiverName'     => array(
				'firstname' => $shipment->get_company() ? '' : $shipment->get_first_name(),
				'lastname'  => $shipment->get_company() ? $shipment->get_company() : $shipment->get_last_name(),
			),
			'clientReference'  => $provider->get_formatted_label_reference( $label, 'simple', 'clientReference' ),
			'clientReference2' => $provider->get_formatted_label_reference( $label, 'simple', 'clientReference2' ),
			'receiverAddress'  => array(
				'street'           => $shipment->get_address_street_number() ? $shipment->get_address_street() : $shipment->get_address_1(),
				'houseNumber'      => $shipment->get_address_street_number(),
				'addressAddition'  => $shipment->get_address_2(),
				'addressAddition2' => $shipment->get_address_street_number() ? $shipment->get_address_street_addition() : '',
				'zipCode'          => $shipment->get_postcode(),
				'town'             => $shipment->get_city(),
				'countryCode'      => $shipment->get_country(),
			),
			'parcel'           => array(
				'parcelWeight' => absint( wc_get_weight( $shipment->get_total_weight(), 'g', $shipment->get_weight_unit() ) ),
				'productType'  => 'PARCEL',
			),
		);

		foreach ( $label->get_services() as $service ) {
			$service_name = lcfirst( $service );

			switch ( $service ) {
				case 'identService':
					$request_services[ $service_name ] = array(
						'identVerifyFsk' => (string) $label->get_service_prop( 'identService', 'fsk' ),
					);
					break;
				case 'customerAlertService':
					$request_services[ $service_name ] = array(
						'notificationEmail' => $label->get_service_prop( 'customerAlertService', 'email' ),
						'notificationType'  => 'EMAIL',
					);
					break;
				case 'parcelShopDeliveryService':
					$request_services[ $service_name ] = array(
						'psCustomerFirstName' => $shipment->get_first_name(),
						'psCustomerLastName'  => $shipment->get_last_name(),
						'psSelectionRule'     => 'auto' === $label->get_service_prop( 'parcelShopDeliveryService', 'mode' ) ? 'SELECT_BY_RECEIVER_ADDRESS' : 'SELECT_BY_ID',
					);

					if ( 'auto' !== $label->get_service_prop( 'parcelShopDeliveryService', 'mode' ) ) {
						$request_services[ $service_name ]['psID'] = $label->get_service_prop( 'parcelShopDeliveryService', 'parcel_shop_id' );
					}
					break;
				default:
					$request_services[ $service_name ] = true;
					break;
			}
		}

		if ( $signature = $provider->get_service( 'signatureService' ) ) {
			if ( $signature->supports_country( $shipment->get_country() ) && ! isset( $request_services['signatureService'] ) ) {
				$request_services['signatureService'] = false;
			}
		}

		/**
		 * Need to hand over the email address when booking parcel shop delivery service.
		 */
		if ( array_key_exists( 'parcelShopDeliveryService', $request_services ) && ! array_key_exists( 'customerAlertService', $request_services ) ) {
			$request['receiverContact'] = array(
				'mail' => $shipment->get_email(),
			);
		}

		if ( $shipment->is_shipping_international() ) {
			$request['receiverContact'] = array(
				'phone' => $shipment->get_phone(),
				'mail'  => $shipment->get_email(),
			);
		}

		if ( $shipment->has_dimensions() ) {
			$request['parcel']['parcelDepth']  = absint( wc_get_dimension( $shipment->get_length(), 'mm', $shipment->get_dimension_unit() ) );
			$request['parcel']['parcelWidth']  = absint( wc_get_dimension( $shipment->get_width(), 'mm', $shipment->get_dimension_unit() ) );
			$request['parcel']['parcelHeight'] = absint( wc_get_dimension( $shipment->get_height(), 'mm', $shipment->get_dimension_unit() ) );
		}

		if ( $shipment->is_shipping_international() ) {
			$customs_data = $label->get_customs_data( 512 );
			$items        = array();

			foreach ( $customs_data['items'] as $item ) {
				$single_value_in_cents = round( $item['value'] * 100 );

				/**
				 * Remove any special char except dash and whitespace.
				 */
				$description = remove_accents( $item['description'] );
				$description = preg_replace( '/[^ \w-]/', ' ', $description );
				$description = preg_replace( '/\s+/', ' ', $description );

				$items[] = array(
					'sku'                      => wc_shiptastic_substring( $item['sku'], 0, 50 ),
					'category'                 => wc_shiptastic_substring( $item['category'], 0, 50 ),
					'countryCodeOfManufacture' => wc_shiptastic_substring( $item['origin_code'], 0, 2 ),
					'value'                    => $single_value_in_cents,
					'weight'                   => absint( $item['weight_in_g'] ),
					'quantity'                 => $item['quantity'],
					'description'              => wc_shiptastic_substring( $description, 0, 512 ),
					'hsCode'                   => $item['tariff_number'],
				);
			}

			$total_value_in_cents = round( $customs_data['item_total_value'] * 100 );

			$request['customsAndTaxes'] = array(
				'currency'              => $customs_data['currency'],
				'shipmentCost'          => $total_value_in_cents,
				'items'                 => $items,
				'invoiceReferences'     => array( $customs_data['invoice_number'] ),
				'shipmentOriginAddress' => array(
					'firstname'       => wc_shiptastic_substring( $shipment->get_sender_first_name(), 0, 50 ),
					'lastname'        => wc_shiptastic_substring( $shipment->get_sender_last_name(), 0, 50 ),
					'company'         => wc_shiptastic_substring( $shipment->get_sender_company(), 0, 50 ),
					'street'          => wc_shiptastic_substring( $shipment->get_sender_address_street(), 0, 50 ),
					'houseNumber'     => wc_shiptastic_substring( $shipment->get_sender_address_street_number(), 0, 5 ),
					'zipCode'         => wc_shiptastic_substring( $shipment->get_sender_postcode(), 0, 8 ),
					'town'            => wc_shiptastic_substring( $shipment->get_sender_city(), 0, 30 ),
					'state'           => wc_shiptastic_substring( $shipment->get_sender_state(), 0, 30 ),
					'countryCode'     => wc_shiptastic_substring( $shipment->get_sender_country(), 0, 2 ),
					'addressAddition' => wc_shiptastic_substring( $shipment->get_sender_address_2(), 0, 50 ),
					'phone'           => wc_shiptastic_substring( $shipment->get_sender_phone(), 0, 20 ),
					'mail'            => wc_shiptastic_substring( $shipment->get_sender_email(), 0, 200 ),
				),
			);
		}

		if ( ! empty( $request_services ) ) {
			$request['service'] = $request_services;
		}

		$request = $this->clean_request( $request );

		$response = $this->post(
			'shipmentorders/labels',
			apply_filters( 'woocommerce_shiptastic_hermes_label_api_request', $request, $label ),
			array(
				'Accept-Language' => $this->get_language( $shipment->get_country() ),
				'accept'          => 'application/shippinglabel-pdf+json',
			)
		);

		return $response;
	}

	public function test_connection() {
		$response = $this->get( 'shipmentorders?shipmentOrderID=1234242' );

		if ( $response->is_error() ) {
			if ( 403 === $response->get_code() ) {
				return $response->get_error();
			}

			return true;
		} else {
			return true;
		}
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function get_label( $label ) {
		$is_return = 'return' === $label->get_type();

		if ( $is_return ) {
			$response = $this->get_return_label( $label );
		} else {
			$response = $this->get_simple_label( $label );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $response->is_error() ) {
			$error       = new ShipmentError();
			$parcel_data = $response->get_body();
			$track_id    = isset( $parcel_data['shipmentID'] ) ? wc_clean( $parcel_data['shipmentID'] ) : '';
			$order_id    = '';

			if ( isset( $parcel_data['returnOrderID'] ) ) {
				$order_id = wc_clean( $parcel_data['returnOrderID'] );
			} elseif ( isset( $parcel_data['shipmentOrderID'] ) ) {
				$order_id = wc_clean( $parcel_data['shipmentOrderID'] );
			} elseif ( isset( $parcel_data['pickupOrderID'] ) ) {
				$order_id = wc_clean( $parcel_data['pickupOrderID'] );
			}

			$label->set_number( $track_id );
			$label->set_hermes_order_id( $order_id );

			if ( ! empty( $parcel_data['listOfResultCodes'] ) ) {
				foreach ( $parcel_data['listOfResultCodes'] as $warning ) {
					if ( 'OK' === $warning['code'] ) {
						continue;
					}

					$code    = wp_kses_post( htmlentities( $this->decode( $warning['code'] ) ) );
					$message = wp_kses_post( htmlentities( $this->decode( $warning['message'] ) ) );

					$error->add_soft_error( 'label', $code . ': ' . $message );
				}
			}

			$label_data = isset( $parcel_data['shippinglabel'] ) ? $parcel_data['shippinglabel'] : '';
			$media_type = 'application/pdf';

			if ( isset( $parcel_data['labelImage'] ) ) {
				$label_data = $parcel_data['labelImage'];
			} elseif ( isset( $parcel_data['qrcode'] ) ) {
				$label_data = $parcel_data['qrcode'];
				$media_type = 'image/png';
			}

			if ( ! empty( $label_data ) ) {
				$pdf = base64_decode( $label_data ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				if ( 'image/png' === $media_type ) {
					try {
						$converter = new ImageToPDF();
						$converter->import_image( $pdf );

						$pdf = $converter->Output( 'S' );
					} catch ( \Exception $e ) {
						$error->add( 'upload', sprintf( _x( 'Could not convert PNG QR code to PDF file: %1$s', 'hermes', 'woocommerce-germanized-pro' ), $e->getMessage() ) );
					}
				}

				if ( $path = $label->upload_label_file( $pdf ) ) {
					$label->set_path( $path );
				} else {
					$error->add( 'upload', _x( 'Error while uploading Hermes label.', 'hermes', 'woocommerce-germanized-pro' ) );
				}
			}

			$label->save();

			if ( $error->has_errors() ) {
				return $error;
			}
		}

		return $response->is_error() ? $response->get_error() : true;
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return true|\WP_Error
	 */
	public function cancel_label( $label ) {
		if ( 'Pickup' === $label->get_product_id() ) {
			$shipment = $label->get_shipment();
			$response = $this->delete(
				'pickuporders/' . $label->get_hermes_order_id(),
				array(),
				array(
					'Accept-Language' => $this->get_language( $shipment->get_country() ),
					'accept'          => 'application/json',
				)
			);

			if ( $response->is_error() ) {
				return $response->get_error();
			} else {
				return true;
			}
		}
	}

	/**
	 * @param Response $response
	 *
	 * @return Response
	 */
	protected function parse_error( $response ) {
		$error = new ShipmentError();
		$body  = $response->get_body();
		$code  = $response->get_code();

		if ( isset( $body['error'] ) ) {
			$desc = ! empty( $body['error_description'] ) ? $body['error_description'] : $body['error'];

			$error->add( $code, wp_kses_post( htmlentities( $this->decode( $desc ) ) ) );
		} elseif ( isset( $body['listOfResultCodes'] ) ) {
			foreach ( $body['listOfResultCodes'] as $error_data ) {
				$desc = ! empty( $error_data['message'] ) ? $error_data['message'] : $error_data['code'];
				$error->add( $code, wp_kses_post( htmlentities( $this->decode( $desc ) ) ) );
			}
		} elseif ( isset( $body['message'] ) ) {
			$error->add( $code, wp_kses_post( htmlentities( $this->decode( $body['message'] ) ) ) );
		} else {
			$error->add( $code, _x( 'There was an unknown error calling the Hermes API.', 'hermes', 'woocommerce-germanized-pro' ) );
		}

		if ( $error->has_errors() ) {
			$response->set_error( $error );
		}

		return $response;
	}

	/**
	 * Hermes API has some special encoding rules. See notes on unicode support.
	 *
	 * @see https://de-api.hermesworld.com/docs/applications/order
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected function encode( $str ) {
		if ( class_exists( '\Vendidero\Shiptastic\Encoding' ) ) {
			$str = Encoding::to_utf8( $str, array( 'BASIC_LATIN', 'LATIN_1_SUPPLEMENT', 'LATIN_EXTENDED_A', 'LATIN_EXTENDED_B' ) );
		} else {
			if ( function_exists( 'wc_shiptastic_decode_html' ) ) {
				$str = wc_shiptastic_decode_html( $str );
			}

			$str = str_replace( array( '–', '—' ), '-', $str );
			$str = str_replace( '’', "'", $str );
		}

		return $str;
	}
}
