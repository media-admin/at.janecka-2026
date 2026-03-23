<?php

namespace Vendidero\Shiptastic\GLS\Api;

use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\API\REST;
use Vendidero\Shiptastic\GLS\Label\Retoure;
use Vendidero\Shiptastic\GLS\Label\Simple;
use Vendidero\Shiptastic\GLS\Package;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

/**
 * GLS ShipIt API
 *
 * @see https://shipit.gls-group.eu/webservices/3_2_9/doxygen/WS-REST-API/index.html
 */
class Api extends REST {

	public function get_title() {
		return _x( 'GLS API', 'gls', 'woocommerce-germanized-pro' );
	}

	protected function get_auth_type() {
		$auth_type = 'password';

		if ( $gls = Package::get_gls_shipping_provider() ) {
			$auth_type = $gls->get_authentication_type();
		}

		return $auth_type;
	}

	public function get_url() {
		if ( 'oauth' === $this->get_auth_type() ) {
			return trailingslashit( ( $this->is_sandbox ? 'https://api-sandbox.gls-group.net/' : 'https://api.gls-group.net/' ) . 'shipit-farm/v1/backend/rs' );
		}

		return trailingslashit( Package::get_api_url( $this->is_sandbox() ) ) . 'backend/rs';
	}

	public function get_name() {
		return 'gls';
	}

	protected function get_auth_instance() {
		return 'oauth' === $this->get_auth_type() ? new OAuth( $this ) : new BasicAuth( $this );
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function cancel_label( $label ) {
		if ( $label->get_gls_track_id() ) {
			$response = $this->post( 'cancel/' . $label->get_gls_track_id() );

			if ( $response->is_error() ) {
				return $response->get_error();
			} else {
				return true;
			}
		}

		return new \WP_Error( 'gls_error', _x( 'There was an error while cancelling the label', 'gls', 'woocommerce-germanized-pro' ) );
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function get_label( $label ) {
		$shipment                      = $label->get_shipment();
		$provider                      = $shipment->get_shipping_provider_instance();
		$label_supports_email_transmit = ( $label->supports_third_party_email_notification() || apply_filters( 'woocommerce_shiptastic_gls_label_force_email_notification', false, $label ) );
		$is_return                     = 'return' === $label->get_type();
		$services                      = $label->get_services();

		if ( in_array( 'FlexDeliveryService', $services, true ) ) {
			$label_supports_email_transmit = true;
		}

		$name_1 = $shipment->get_company() ? $shipment->get_company() : $shipment->get_formatted_full_name();
		$name_2 = $shipment->get_company() ? $shipment->get_formatted_full_name() : $shipment->get_company();

		if ( $is_return ) {
			$name_1 = $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_formatted_sender_full_name();
			$name_2 = $shipment->get_sender_company() ? $shipment->get_formatted_sender_full_name() : $shipment->get_sender_company();
		}

		/**
		 * GLS takes care of switching consignee address in case of returns.
		 */
		$recipient_address = array(
			'Name1'                => $name_1,
			'Name2'                => $name_2,
			'Name3'                => $is_return ? $shipment->get_sender_address_2() : $shipment->get_address_2(),
			'CountryCode'          => $is_return ? $shipment->get_sender_country() : $shipment->get_country(),
			'ZIPCode'              => $is_return ? $shipment->get_sender_postcode() : $shipment->get_postcode(),
			'City'                 => $is_return ? $shipment->get_sender_city() : $shipment->get_city(),
			'Street'               => $is_return ? $shipment->get_sender_address_1() : $shipment->get_address_1(),
			'eMail'                => $label_supports_email_transmit || $is_return ? ( $is_return ? $shipment->get_sender_email() : $shipment->get_email() ) : '',
			'ContactPerson'        => $is_return ? $shipment->get_formatted_sender_full_name() : $shipment->get_formatted_full_name(),
			'FixedLinePhonenumber' => $is_return ? $shipment->get_sender_phone() : $shipment->get_phone(),
		);

		$shipment_unit_services = array();
		$shipment_services      = array();

		foreach ( $label->get_services() as $service ) {
			$service_obj        = $provider->get_service( $service );
			$clean_service_name = str_replace( 'service', '', strtolower( $service ) );
			$inner_service      = array(
				'ServiceName' => 'service_' . $clean_service_name,
			);
			$service_inner_name = 'Service';

			$label_fields = $service_obj->get_label_fields( $shipment );

			foreach ( $label_fields as $label_field ) {
				if ( $label_field['id'] === $service_obj->get_label_field_id() ) {
					continue;
				}

				$service_inner_name = $service;
			}

			if ( 'AddonLiability' === $service ) {
				$inner_service['Amount']   = wc_format_decimal( $label->get_service_prop( 'AddonLiability', 'Amount' ) );
				$inner_service['Currency'] = $label->get_service_prop( 'AddonLiability', 'Currency' );
			} elseif ( 'Deposit' === $service ) {
				$inner_service['PlaceOfDeposit'] = $label->get_service_prop( 'Deposit', 'PlaceOfDeposit' );
			} elseif ( 'Letterbox' === $service ) {
				$service_inner_name              = 'Deposit';
				$inner_service                   = array(
					'ServiceName' => 'service_deposit',
				);
				$inner_service['PlaceOfDeposit'] = _x( 'Letterbox', 'gls', 'woocommerce-germanized-pro' );
			}

			if ( 'unit' === $service_obj->get_level() ) {
				$the_service = array(
					$service => $inner_service,
				);
			} else {
				$the_service = array(
					$service_inner_name => $inner_service,
				);
			}

			if ( 'shipment' === $service_obj->get_level() ) {
				$shipment_services[] = $the_service;
			} else {
				$shipment_unit_services[] = $the_service;
			}
		}

		if ( 'return' === $label->get_type() ) {
			if ( 'shop_return' === $label->get_return_type() ) {
				$shipment_services[] = array(
					'ShopReturn' => array(
						'ServiceName'    => 'service_shopreturn',
						'NumberOfLabels' => 1,
					),
				);
			} elseif ( 'pick_and_return' === $label->get_return_type() ) {
				$shipment_services[] = array(
					'PickAndReturn' => array(
						'ServiceName' => 'service_pickandreturn',
						'PickupDate'  => $label->get_pickup_date(),
					),
				);
			}
		}

		$request = array(
			'Shipment'        => array(
				'ShipmentReference' => array( $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'ShipmentReference' ) ),
				'Product'           => $label->get_product_id(),
				'ShippingDate'      => $label->get_shipping_date(),
				'IncotermCode'      => $label->get_incoterms() ? $label->get_incoterms() : '',
				'Middleware'        => 'vendideroGermanizedviaGLS',
				'Consignee'         => array(
					'Address'  => $recipient_address,
					'Category' => ! empty( $recipient_address['Name2'] ) ? 'BUSINESS' : 'PRIVATE',
				),
				'Shipper'           => array(
					'ContactID' => Package::get_api_contact_id( $this->is_sandbox() ),
				),
				'ShipmentUnit'      => array(
					array(
						'ShipmentUnitReference' => array( apply_filters( 'woocommerce_shiptastic_gls_label_api_reference', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'ShipmentUnitReference' ), $label ) ),
						'Weight'                => $label->get_weight(),
						'Note1'                 => apply_filters( 'woocommerce_shiptastic_gls_label_api_note', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Note1' ), $label ),
						'Note2'                 => apply_filters( 'woocommerce_shiptastic_gls_label_api_note_2', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Note2' ), $label ),
						'Service'               => $shipment_unit_services,
					),
				),
				'Service'           => $shipment_services,
			),
			'PrintingOptions' => array(
				'ReturnLabels' => array(
					'TemplateSet' => 'NONE',
					'LabelFormat' => 'PDF',
				),
			),
		);

		$request  = $this->clean_request( $request );
		$response = $this->post( 'shipments', apply_filters( 'woocommerce_shiptastic_gls_label_api_request', $request, $label ) );

		if ( ! $response->is_error() ) {
			$body = $response->get_body();

			if ( isset( $body['CreatedShipment']['ParcelData'] ) ) {
				$error         = new ShipmentError();
				$parcel_data   = $body['CreatedShipment']['ParcelData'][0];
				$track_id      = wc_clean( $parcel_data['TrackID'] );
				$parcel_number = wc_clean( $parcel_data['ParcelNumber'] );

				$label->set_number( $parcel_number );
				$label->set_gls_track_id( $track_id );

				if ( isset( $body['CreatedShipment']['PrintData'] ) ) {
					$pdf = base64_decode( $body['CreatedShipment']['PrintData'][0]['Data'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

					if ( $path = $label->upload_label_file( $pdf ) ) {
						$label->set_path( $path );
					} else {
						$error->add( 'upload', _x( 'Error while uploading GLS label.', 'gls', 'woocommerce-germanized-pro' ) );
					}
				}

				$label->save();

				if ( $error->has_errors() ) {
					return $error;
				}
			}
		}

		return $response->is_error() ? $response->get_error() : true;
	}

	public function test_connection() {
		$response = $this->post(
			'validate',
			array(
				'Shipment' => array(
					'ShippingDate' => '',
				),
			)
		);

		if ( $response->is_error() && in_array( $response->get_code(), array( 401, 403 ), true ) ) {
			return false;
		}

		return true;
	}

	protected function get_content_type() {
		return 'application/glsVersion1+json';
	}

	protected function get_headers( $headers = array() ) {
		$headers               = parent::get_headers( $headers );
		$headers['Accept']     = 'application/glsVersion1+json, application/json';
		$headers['User-Agent'] = 'Germanized/' . Package::get_version();

		return $headers;
	}

	protected function maybe_encode_body( $body_args, $content_type = '' ) {
		$body_args = parent::maybe_encode_body( $body_args, $content_type );

		if ( 'application/glsVersion1+json' === $content_type ) {
			$body_args = wp_json_encode( $body_args, JSON_PRETTY_PRINT );
		}

		return $body_args;
	}

	/**
	 * @param Response $response
	 *
	 * @return Response
	 */
	protected function parse_error( $response ) {
		$headers = $response->get_headers();
		$code    = $response->get_code();
		$body    = $response->get_body_raw();
		$error   = new ShipmentError();

		if ( isset( $headers['message'] ) ) {
			$error->add( $code, wp_kses_post( htmlentities( $this->decode( $headers['message'] ) ) ) );
		} elseif ( is_array( $headers ) ) {
			$headers = array_values( $headers );

			if ( ! empty( $headers ) ) {
				$headers = $headers[0];
			}

			if ( isset( $headers['message'] ) ) {
				$error->add( $code, wp_kses_post( htmlentities( $this->decode( $headers['message'] ) ) ) );
			}
		}

		if ( ! $error->has_errors() && is_string( $body ) ) {
			if ( 'oauth' === $this->get_auth_type() ) {
				$body = $response->get_body();

				if ( isset( $body['errors'] ) ) {
					foreach ( $body['errors'] as $api_error ) {
						$api_error = wp_parse_args(
							$api_error,
							array(
								'code'    => '',
								'message' => '',
							)
						);

						$error->add( $api_error['code'], wp_kses_post( $api_error['message'] ) );
					}
				}
			} else {
				$body = wp_strip_all_tags( $body );

				if ( 'error' === substr( strtolower( $body ), 0, 5 ) ) {
					$body = substr( $body, 5 );
				}

				$error->add( $code, wp_kses_post( $body ) );
			}
		}

		if ( ! $error->has_errors() ) {
			$error->add( $code, _x( 'There was an unknown error calling the GLS API.', 'gls', 'woocommerce-germanized-pro' ) );
		}

		$response->set_error( $error );

		return $response;
	}
}
