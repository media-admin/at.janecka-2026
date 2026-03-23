<?php

namespace Vendidero\Shiptastic\DPD\Api\WebConnect;

use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\DPD\Interfaces\LabelApi;
use Vendidero\Shiptastic\DPD\Label\Retoure;
use Vendidero\Shiptastic\DPD\Label\Simple;
use Vendidero\Shiptastic\DPD\Package;
use League\ISO3166\ISO3166;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class Api extends \Vendidero\Shiptastic\API\Api implements LabelApi {

	private $client = null;

	protected $is_sandbox = false;

	protected $auth_response = null;

	public function __construct() {}

	protected function get_auth_instance() {
		return new SoapAuth( $this );
	}

	public function get_name() {
		return 'dpd_webconnect';
	}

	public function get_title() {
		return _x( 'DPD WebConnect', 'dpd', 'woocommerce-germanized-pro' );
	}

	public function get_url() {
		return $this->is_sandbox() ? 'https://public-ws-stage.dpd.com/' : 'https://public-ws.dpd.com/';
	}

	public function get_parcel_shops( $args, $limit = 20 ) {
		$auth_response = $this->auth();
		$args          = wp_parse_args(
			$args,
			array(
				'postcode'  => '',
				'country'   => \Vendidero\Shiptastic\Package::get_base_country(),
				'city'      => '',
				'address_1' => '',
			)
		);

		if ( ! $auth_response->is_error() ) {
			$request = array(
				'country'  => $args['country'],
				'zipCode'  => $args['postcode'],
				'street'   => $args['address_1'],
				'limit'    => $limit,
				'services' => array(
					'service' => array(
						'code'      => 100,
						'available' => true,
					),
				),
			);

			$clean_request = $this->clean_request( $request );
			$result        = $this->get_client( 'findParcelShops', $clean_request, 'ParcelShopFinderService/V5_0' );

			if ( ! $result->is_error() ) {
				$result = json_decode( wp_json_encode( $result->get_body_raw()->parcelShop ), true );

				/**
				 * When only one parcel shop is requested, the API seems to return a single item instead of a list.
				 */
				if ( isset( $result['parcelShopId'] ) ) {
					$result = array( $result );
				}

				return $result;
			}
		}

		return array();
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
					$id_to_match = (string) $a_parcel_shop['parcelShopId'];
				} else {
					$id_to_match = (string) $a_parcel_shop['pudoId'];
				}

				if ( $id_to_match === (string) $id ) {
					$parcel_shop = $a_parcel_shop;
					break;
				}
			}
		}

		return $parcel_shop;
	}

	public function get_last_request() {
		if ( ! is_null( $this->client ) ) {
			return $this->client->__getLastRequest();
		}

		return false;
	}

	protected function get_numeric_iso_code( $iso_alpha_2 ) {
		$iso         = new ISO3166();
		$iso_numeric = '';

		try {
			$data        = $iso->alpha2( $iso_alpha_2 );
			$iso_numeric = $data['numeric'];
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $iso_numeric;
	}

	/**
	 * @return Response
	 */
	protected function auth() {
		if ( ! $this->get_auth_api()->has_auth() ) {
			$this->auth_response = $this->get_auth_api()->auth();
		}

		return $this->auth_response;
	}

	public function get_client( $endpoint, $request = array(), $service_endpoint = 'ShipmentService/V4_4' ) {
		$auth_response = $this->auth();
		$response      = new Response( 500, '' );

		if ( ! $auth_response->is_error() ) {
			try {
				$this->client = new \SoapClient(
					$this->get_request_url( "services/{$service_endpoint}/?wsdl" ),
					array(
						'trace' => true,
					)
				);

				$this->client->__setSoapHeaders(
					new \SoapHeader(
						'http://dpd.com/common/service/types/Authentication/2.0',
						'authentication',
						$auth_response->get_body_raw()
					)
				);

				$this->client->__setLocation( $this->get_request_url( "services/{$service_endpoint}/" ) );
				$result = $this->client->{$endpoint}( $request );

				if ( $result ) {
					$response->set_code( 200 );
					$response->set_body( $result );
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
			$response->set_error( $this->auth_response->get_error() );
		}

		return $response;
	}

	/**
	 * @param Simple|Retoure $label
	 *
	 * @return \WP_Error|true
	 */
	public function get_label( $label ) {
		$auth_response = $this->auth();

		if ( $auth_response->is_error() ) {
			return $auth_response->get_error();
		}

		$shipment       = $label->get_shipment();
		$provider       = $shipment->get_shipping_provider_instance();
		$shipment_ref   = apply_filters( 'woocommerce_shiptastic_dpd_label_api_reference', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Reference1' ), $label );
		$shipment_ref_2 = apply_filters( 'woocommerce_shiptastic_dpd_label_api_reference_2', $provider->get_formatted_label_reference( $label, $shipment->get_type(), 'Reference2' ), $label );
		$weight         = Package::to_tenth_gramm( $label->get_weight() );
		$length         = Package::convert_dimension( $label->get_length() );
		$width          = Package::convert_dimension( $label->get_width() );
		$height         = Package::convert_dimension( $label->get_height() );
		$volume         = $length * $width * $height;
		$parcel_volume  = str_pad( $length, 3, '0', STR_PAD_LEFT ) . str_pad( $width, 3, '0', STR_PAD_LEFT ) . str_pad( $height, 3, '0', STR_PAD_LEFT );

		$provider = $shipment->get_shipping_provider_instance();
		$error    = new ShipmentError();

		$product_and_service_data = array(
			'orderType'                => 'consignment',
			'customerReferenceNumber1' => $shipment_ref,
			'customerReferenceNumber2' => $shipment_ref_2,
		);

		if ( $label->has_service( 'predict' ) ) {
			$product_and_service_data['predict'] = array(
				'channel'  => 1,
				'value'    => $label->get_service_prop( 'predict', 'email' ) ? $label->get_service_prop( 'predict', 'email' ) : $shipment->get_email(),
				'language' => $shipment->get_country(),
			);
		}

		if ( 'E12' === $label->get_product_id() && $label->has_service( 'saturday_delivery' ) ) {
			$product_and_service_data['saturdayDelivery'] = true;
		}

		$sender = array(
			'name1'          => mb_substr( $shipment->get_sender_company() ? $shipment->get_sender_company() : $shipment->get_formatted_sender_full_name(), 0, 35 ),
			'name2'          => mb_substr( trim( $shipment->get_sender_company() ? $shipment->get_formatted_sender_full_name() . ' ' . $shipment->get_sender_address_2() : $shipment->get_sender_address_2() ), 0, 35 ),
			'street'         => mb_substr( $shipment->get_sender_address_street(), 0, 50 ),
			'houseNo'        => mb_substr( $shipment->get_sender_address_street_number(), 0, 8 ),
			'country'        => mb_substr( $shipment->get_sender_country(), 0, 2 ),
			'zipCode'        => mb_substr( $shipment->get_sender_postcode(), 0, 9 ),
			'city'           => mb_substr( $shipment->get_sender_city(), 0, 50 ),
			'contact'        => mb_substr( $shipment->get_formatted_sender_full_name(), 0, 35 ),
			'phone'          => mb_substr( $shipment->get_sender_phone(), 0, 30 ),
			'email'          => mb_substr( $shipment->get_sender_email(), 0, 100 ),
			'customerNumber' => mb_substr( '', 0, 17 ),
		);

		$recipient = array(
			'name1'   => mb_substr( $shipment->get_company() ? $shipment->get_company() : $shipment->get_formatted_full_name(), 0, 35 ),
			'name2'   => mb_substr( $shipment->get_address_2(), 0, 35 ),
			'street'  => mb_substr( $shipment->get_address_street(), 0, 50 ),
			'houseNo' => mb_substr( $shipment->get_address_street_number(), 0, 8 ),
			'country' => mb_substr( $shipment->get_country(), 0, 2 ),
			'zipCode' => mb_substr( $shipment->get_postcode(), 0, 9 ),
			'city'    => mb_substr( $shipment->get_city(), 0, 50 ),
			'contact' => mb_substr( apply_filters( 'woocommerce_shiptastic_dpd_label_recipient_contact', $shipment->get_company() ? $shipment->get_formatted_full_name() : '', $shipment, $label ), 0, 35 ),
			'phone'   => mb_substr( $shipment->get_phone(), 0, 30 ),
			'email'   => mb_substr( $shipment->get_email(), 0, 100 ),
		);

		if ( $label->has_service( 'parcel_shop_delivery' ) && $label->get_parcel_shop_id() ) {
			$product_and_service_data['parcelShopDelivery'] = array(
				'parcelShopId'           => is_numeric( $label->get_parcel_shop_id() ) ? strtoupper( $label->get_parcel_shop_id() ) : '',
				'parcelShopPudoId'       => ! is_numeric( $label->get_parcel_shop_id() ) ? strtoupper( $label->get_parcel_shop_id() ) : '',
				'parcelShopNotification' => array(
					'channel'  => 1,
					'value'    => $shipment->get_email(),
					'language' => $shipment->get_country(),
				),
			);

			if ( is_callable( array( $shipment, 'get_billing_address' ) ) ) {
				$recipient = array(
					'name1'   => mb_substr( $shipment->get_billing_company() ? $shipment->get_billing_company() : $shipment->get_formatted_billing_full_name(), 0, 35 ),
					'name2'   => mb_substr( $shipment->get_billing_address_2(), 0, 35 ),
					'street'  => mb_substr( $shipment->get_billing_address_street(), 0, 50 ),
					'houseNo' => mb_substr( $shipment->get_billing_address_street_number(), 0, 8 ),
					'country' => mb_substr( $shipment->get_billing_country(), 0, 2 ),
					'zipCode' => mb_substr( $shipment->get_billing_postcode(), 0, 9 ),
					'city'    => mb_substr( $shipment->get_billing_city(), 0, 50 ),
					'contact' => mb_substr( apply_filters( 'woocommerce_shiptastic_dpd_label_billing_recipient_contact', $shipment->get_billing_company() ? $shipment->get_formatted_billing_full_name() : '', $shipment, $label ), 0, 35 ),
					'phone'   => mb_substr( $shipment->get_billing_phone(), 0, 30 ),
					'email'   => mb_substr( $shipment->get_billing_email(), 0, 100 ),
				);
			}
		}

		/**
		 * DPD expects the sender to be the recipient of the return, e.g. the shop owner.
		 */
		if ( 'return' === $label->get_type() ) {
			$real_recipient = $recipient;
			$recipient      = $sender;
			$sender         = $real_recipient;
		}

		/**
		 * Routing network for FR
		 */
		if ( 'FR' === $shipment->get_country() && $label->get_business_unit() && 'auto' !== $label->get_business_unit() ) {
			$recipient['businessUnit'] = $label->get_business_unit();
		}

		if ( ! $label->has_service( 'predict' ) && ! $shipment->is_shipping_international() ) {
			if ( 'return' === $label->get_type() ) {
				unset( $sender['phone'] );
				unset( $sender['email'] );
			} else {
				unset( $recipient['phone'] );
				unset( $recipient['email'] );
			}
		}

		$customs_data = $label->get_customs_data();

		/**
		 * Additional international guarantee
		 */
		if ( $label->has_service( 'international_guarantee' ) ) {
			$product_and_service_data['guarantee'] = true;
		}

		if ( $shipment->is_shipping_international() || ( $shipment->is_shipping_inner_eu() && 'IE2' === $label->get_product_id() ) ) {
			$invoice_lines = array();
			$item_count    = 0;

			foreach ( $customs_data['items'] as $key => $item ) {
				++$item_count;

				$invoice_lines[] = array(
					'customsInvoicePosition' => $item_count,
					'quantityItems'          => $item['quantity'],
					'customsContent'         => $item['description'],
					'customsTarif'           => $item['tariff_number'],
					'customsAmountLine'      => round( $item['value'] * 100 ),
					'customsOrigin'          => $this->get_numeric_iso_code( $item['origin_code'] ),
					'customsNetWeight'       => Package::to_tenth_gramm( $item['weight_in_kg'] ),
					'customsGrossWeight'     => Package::to_tenth_gramm( $item['gross_weight_in_kg'] ),
				);
			}

			/**
			 * Make sure phone, email is available for international shipments (e.g. customs)
			 */
			$recipient['email']   = $shipment->get_email();
			$recipient['phone']   = $shipment->get_phone();
			$recipient['contact'] = empty( $recipient['contact'] ) ? $shipment->get_formatted_full_name() : $recipient['contact'];

			$product_and_service_data['international'] = array(
				// True in case is document (letter) and not cardboard
				'parcelType'                          => apply_filters( 'woocommerce_shiptastic_dpd_label_api_shipment_is_document', false, $label ),
				'customsAmount'                       => round( $customs_data['item_total_value'] * 100 ),
				'customsCurrency'                     => $customs_data['currency'],
				'customsTerms'                        => $label->get_customs_terms(),
				'customsPaper'                        => apply_filters( 'woocommerce_shiptastic_dpd_customs_paper', implode( '', $label->get_customs_paper() ), $label ),
				'customsOrigin'                       => Package::get_dpd_shipping_provider()->get_shipper_country(),
				'numberOfArticle'                     => count( $customs_data['items'] ),
				'additionalInvoiceLines'              => $invoice_lines,
				'commercialInvoiceConsignorVatNumber' => apply_filters( 'woocommerce_shiptastic_dpd_label_api_consignor_vat_id', $customs_data['sender_customs_ref_number'], $label ),
				'commercialInvoiceConsignor'          => $sender,
				'commercialInvoiceConsigneeVatNumber' => apply_filters( 'woocommerce_shiptastic_dpd_label_api_consignee_vat_id', $customs_data['receiver_customs_ref_number'], $label ),
				'commercialInvoiceConsignee'          => $recipient,
			);

			if ( 'GB' === $shipment->get_country() && $shipment->get_total() <= 135 ) {
				/**
				 * Special rules apply for total amounts <= 135 GBP. Senders need to register for a UK VAT ID
				 * Customs terms need to be set to eDAP.
				 *
				 * @see https://www.dpd.com/de/de/support/international/brexit/
				*/
				$product_and_service_data['international']['countryRegistrationNumber'] = isset( $customs_data['sender_customs_uk_vat_id'] ) ? $customs_data['sender_customs_uk_vat_id'] : '';
				$product_and_service_data['international']['customsTerms']              = '07';

				if ( empty( $product_and_service_data['international']['countryRegistrationNumber'] ) ) {
					$error->add( 'missing_field', _x( 'Please provide your UK VAT ID in the shipment address settings.', 'dpd', 'woocommerce-germanized-pro' ) );
				}
			}

			$recipient_error = $this->is_valid_address( $recipient, 'recipient', array( 'email' ) );
			$sender_error    = $this->is_valid_address( $sender, 'sender', array( 'email', 'phone' ) );
		} else {
			$recipient_error = $this->is_valid_address( $recipient );
			$sender_error    = $this->is_valid_address( $sender, 'sender' );
		}

		if ( is_wp_error( $recipient_error ) ) {
			$error->merge_from( $recipient_error );
		}

		if ( is_wp_error( $sender_error ) ) {
			$error->merge_from( $sender_error );
		}

		if ( $error->has_errors() ) {
			return $error;
		}

		$content_description = $customs_data['export_type_description'];

		if ( $label->has_service( 'higher_insurance' ) && $label->get_service_prop( 'higher_insurance', 'description' ) ) {
			$content_description = $label->get_service_prop( 'higher_insurance', 'description' );
		}

		$parcel_data = array(
			'weight'                  => $weight,
			'content'                 => mb_strcut( apply_filters( 'woocommerce_shiptastic_dpd_label_api_parcel_content', $content_description, $label ), 0, 35 ),
			'printInfo1OnParcelLabel' => '' === apply_filters( 'woocommerce_shiptastic_dpd_label_api_parcel_info', '', $label ) ? false : true,
			'info1'                   => apply_filters( 'woocommerce_shiptastic_dpd_label_api_parcel_info', '', $label ),
		);

		if ( $volume > 0 ) {
			$parcel_data['volume'] = $parcel_volume;
		}

		if ( $label->has_service( 'higher_insurance' ) ) {
			$amount = $label->get_service_prop( 'higher_insurance', 'amount' ) ? wc_format_decimal( $label->get_service_prop( 'higher_insurance', 'amount' ) ) : '';

			if ( empty( $amount ) ) {
				$amount = $shipment->get_total();
			}

			$parcel_data['higherInsurance'] = array(
				'amount'   => round( (float) $amount * 100 ),
				'currency' => $shipment->get_order() ? $shipment->get_order()->get_currency() : get_woocommerce_currency(),
			);
		}

		if ( 'return' === $label->get_type() ) {
			$parcel_data['returns'] = true;
		}

		$request = array(
			'printOptions' => array(
				'printOption' => array(
					'outputFormat' => 'PDF',
					'paperFormat'  => $label->get_page_format(),
				),
			),
			'order'        => array(
				'generalShipmentData'   => array(
					'sendingDepot'                => $auth_response->get_body_raw()->getDepot(),
					'product'                     => $label->get_product_id(),
					'mpsWeight'                   => $weight,
					'mpsCustomerReferenceNumber1' => $shipment_ref,
					'mpsCustomerReferenceNumber2' => $shipment_ref_2,
					'identificationNumber'        => $shipment->get_shipment_number(),
					'sender'                      => $sender,
					'recipient'                   => $recipient,
				),
				'parcels'               => $parcel_data,
				'productAndServiceData' => $product_and_service_data,
			),
		);

		if ( $volume > 0 ) {
			$request['order']['generalShipmentData']['mpsVolume'] = $volume;
		}

		$clean_request = $this->clean_request( $request );
		$response      = $this->get_client( 'storeOrders', apply_filters( 'woocommerce_shiptastic_dpd_label_api_request', $clean_request, $label ) );

		if ( ! $response->is_error() ) {
			$body = $response->get_body_raw();

			if ( isset( $body->orderResult, $body->orderResult->shipmentResponses ) ) {
				$order_result      = $body->orderResult;
				$shipment_response = $order_result->shipmentResponses;

				/**
				 * Error handling
				 */
				if ( isset( $shipment_response->faults ) ) {
					if ( is_array( $shipment_response->faults ) ) {
						foreach ( $shipment_response->faults as $fault ) {
							$error->add( $fault->faultCode, $fault->message );
						}
					} else {
						$error->add( $shipment_response->faults->faultCode, $shipment_response->faults->message );
					}
				}

				if ( ! $error->has_errors() && isset( $shipment_response->mpsId, $shipment_response->parcelInformation ) ) {
					$parcel_information = $shipment_response->parcelInformation;
					$label_number       = $parcel_information->parcelLabelNumber;
					$mps_id             = $shipment_response->mpsId;
					$pdf                = $order_result->output->content;

					$label->set_number( $label_number );
					$label->set_mps_id( $mps_id );

					if ( $path = $label->upload_label_file( $pdf ) ) {
						$label->set_path( $path );
					} else {
						$error->add( 'upload', _x( 'Error while uploading DPD label.', 'dpd', 'woocommerce-germanized-pro' ) );
					}

					$label->save();
				}
			}
		} else {
			$error = $response->get_error();
		}

		if ( $error->has_errors() ) {
			Package::log( sprintf( '%s error during SOAP call to %s:', $response->get_code(), 'storeOrders' ) );
			Package::log( wc_print_r( $error->get_error_messages(), true ) );
			Package::log( 'Body:' );
			Package::log( wc_print_r( $clean_request, true ) );
		}

		return $error->has_errors() ? $error : true;
	}

	protected function is_valid_address( $address, $address_type = 'recipient', $additional_mandatory = array() ) {
		$errors = new ShipmentError();
		$fields = array(
			'name1'   => _x( 'First Name', 'dpd', 'woocommerce-germanized-pro' ),
			'name2'   => _x( 'Last Name', 'dpd', 'woocommerce-germanized-pro' ),
			'street'  => _x( 'Street', 'dpd', 'woocommerce-germanized-pro' ),
			'houseNo' => _x( 'House number', 'dpd', 'woocommerce-germanized-pro' ),
			'country' => _x( 'Country', 'dpd', 'woocommerce-germanized-pro' ),
			'zipCode' => _x( 'Postcode', 'dpd', 'woocommerce-germanized-pro' ),
			'city'    => _x( 'City', 'dpd', 'woocommerce-germanized-pro' ),
			'email'   => _x( 'Email', 'dpd', 'woocommerce-germanized-pro' ),
			'phone'   => _x( 'Phone', 'dpd', 'woocommerce-germanized-pro' ),
		);

		$mandatory = array(
			'name1',
			'street',
			'houseNo',
			'country',
			'zipCode',
			'city',
		);

		$address_labels = array(
			'recipient' => _x( 'Recipient Address', 'dpd', 'woocommerce-germanized-pro' ),
			'sender'    => _x( 'Sender Address', 'dpd', 'woocommerce-germanized-pro' ),
		);

		$address_label = array_key_exists( $address_type, $address_labels ) ? $address_labels[ $address_type ] : '';
		$mandatory     = array_unique( array_merge( $mandatory, $additional_mandatory ) );

		foreach ( $mandatory as $mandatory_field_name ) {
			if ( ! array_key_exists( $mandatory_field_name, $address ) || '' === $address[ $mandatory_field_name ] ) {
				$errors->add( $address_type . '_' . $mandatory_field_name, sprintf( _x( '%1$s: %2$s is missing or empty.', 'dpd', 'woocommerce-germanized-pro' ), $address_label, array_key_exists( $mandatory_field_name, $fields ) ? $fields[ $mandatory_field_name ] : $mandatory_field_name ) );
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		} else {
			return true;
		}
	}

	public function get_international_customs_terms() {
		return array(
			'01' => _x( 'DAP, cleared', 'dpd', 'woocommerce-germanized-pro' ),
			'02' => _x( 'DDP (incl. duties, excl. taxes)', 'dpd', 'woocommerce-germanized-pro' ),
			'03' => _x( 'DDP (incl. duties and taxes)', 'dpd', 'woocommerce-germanized-pro' ),
			'05' => _x( 'Ex works (EXW)', 'dpd', 'woocommerce-germanized-pro' ),
			'06' => _x( 'DAP', 'dpd', 'woocommerce-germanized-pro' ),
			'07' => _x( 'DAP (duty and taxes pre-paid by receiver)', 'dpd', 'woocommerce-germanized-pro' ),
		);
	}

	public function get_international_customs_paper() {
		return array(
			'A' => _x( 'Commercial invoice', 'dpd', 'woocommerce-germanized-pro' ),
			'B' => _x( 'Proforma invoice', 'dpd', 'woocommerce-germanized-pro' ),
			'G' => _x( 'Delivery note', 'dpd', 'woocommerce-germanized-pro' ),
			'H' => _x( 'Third party billing', 'dpd', 'woocommerce-germanized-pro' ),
			'C' => _x( 'Export declaration', 'dpd', 'woocommerce-germanized-pro' ),
			'D' => _x( 'EUR1', 'dpd', 'woocommerce-germanized-pro' ),
			'E' => _x( 'EUR2', 'dpd', 'woocommerce-germanized-pro' ),
			'F' => _x( 'ATR', 'dpd', 'woocommerce-germanized-pro' ),
			'I' => _x( 'T1 document', 'dpd', 'woocommerce-germanized-pro' ),
		);
	}

	public function test_connection() {
		$is_connected = false;

		if ( $this->get_auth_api()->is_connected() ) {
			$response = $this->auth();

			if ( ! $response->is_error() ) {
				$is_connected = true;
			}
		}

		return $is_connected;
	}
}
