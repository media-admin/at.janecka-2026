<?php

namespace Vendidero\Shiptastic\Hermes\Api;

use Vendidero\Shiptastic\API\Response;
use Vendidero\Shiptastic\API\REST;
use Vendidero\Shiptastic\ShipmentError;

defined( 'ABSPATH' ) || exit;

class ParcelShopFinder extends REST {

	public function get_title() {
		return _x( 'Hermes Parcel Shop Finder API', 'hermes', 'woocommerce-germanized-pro' );
	}

	public function get_url() {
		return 'https://psf.hermesworld.com/psfinder-api/';
	}

	public function get_name() {
		return 'hermes_parcel_shop_finder';
	}

	protected function get_auth_instance() {
		return new AuthByKey( $this );
	}

	/**
	 * @param $id
	 * @param $country
	 *
	 * @return false|\WP_Error
	 */
	public function find_by_id( $id, $country ) {
		$request = array(
			'country'      => $country,
			'parcelShopNo' => $id,
		);

		$response = $this->get( 'parcelshop/', $request );

		if ( $response->is_error() ) {
			return $response->get_error();
		} elseif ( $response->get_body() ) {
			return $response->get_body();
		} else {
			return false;
		}
	}

	/**
	 * @param $address
	 * @param $type
	 * @param $limit
	 *
	 * @return array|\WP_Error
	 */
	public function find( $address, $type = null, $limit = 20 ) {
		$address = wp_parse_args(
			$address,
			array(
				'address' => '',
				'zip'     => '',
				'city'    => '',
				'country' => 'de',
			)
		);

		$limit   = is_numeric( $limit ) ? $limit : 20;
		$request = array(
			'country'            => strtolower( $address['country'] ),
			'adressSearchString' => trim( $address['address'] . ' ' . $address['zip'] . ' ' . $address['city'] ),
			'maxDist'            => 50,
			'maxResult'          => $limit,
		);

		if ( null !== $type ) {
			$request['typeId'] = 'box' === $type ? 1 : 0;
		}

		$response = $this->get( 'findParcelShopByAddressString/', $request );

		if ( $response->is_error() ) {
			return $response->get_error();
		} elseif ( $response->get_body() ) {
			return $response->get_body();
		} else {
			return array();
		}
	}

	public function get( $endpoint = '', $query_args = array(), $header = array() ) {
		$query_args = array_merge( $query_args, $this->get_auth_api()->get_additional_args() );

		return parent::get( $endpoint, $query_args, $header );
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
}
