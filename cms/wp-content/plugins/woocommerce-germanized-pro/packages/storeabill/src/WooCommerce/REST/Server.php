<?php

namespace Vendidero\StoreaBill\WooCommerce\REST;

use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Server {

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public static function init() {
		add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( __CLASS__, 'register_controllers' ), 10 );
		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'order_schema' ), 10 );
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'prepare_order' ), 10, 3 );
	}

	/**
	 * @param \WP_REST_Response $response
	 * @param $post
	 * @param \WP_REST_Request $request
	 *
	 * @return mixed
	 */
	public static function prepare_order( $response, $post, $request ) {
		$order                           = wc_get_order( $post );
		$response_order_data             = $response->get_data();
		$response_order_data['invoices'] = array();

		if ( $order ) {
			if ( $sab_order = Helper::get_order( $order ) ) {
				$objects = array();

				foreach ( $sab_order->get_documents() as $document ) {
					$document_type = sab_get_document_type( $document->get_type() );

					if ( $controller = \Vendidero\StoreaBill\REST\Server::instance()->get_controller( $document_type->api_endpoint ) ) {
						$data = $controller->prepare_object_for_response( $document, $request );

						if ( $data ) {
							$objects[] = $controller->prepare_response_for_collection( $data );
						}
					}
				}
			}

			$response_order_data['invoices'] = $objects;
		}

		$response->set_data( $response_order_data );

		return $response;
	}

	public static function order_schema( $schema ) {
		$item_schema = array();

		/**
		 * Use the cancellations schema which adds additional properties to the invoice endpoint as
		 * the invoice list may return invoices and cancellations.
		 */
		if ( $controller = \Vendidero\StoreaBill\REST\Server::instance()->get_controller( 'cancellations' ) ) {
			$item_schema = $controller->get_public_item_schema();
		}

		$schema['invoices'] = array(
			'description' => _x( 'List of invoices.', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'type'        => 'array',
			'context'     => array( 'view', 'edit' ),
			'readonly'    => true,
			'items'       => $item_schema,
		);

		return $schema;
	}

	public static function register_controllers( $controller ) {
		$controller['wc/v3']['order-invoices'] = 'Vendidero\StoreaBill\WooCommerce\REST\Orders';

		return $controller;
	}
}
