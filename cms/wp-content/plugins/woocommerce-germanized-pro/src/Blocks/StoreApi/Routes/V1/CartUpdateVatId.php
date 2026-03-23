<?php
namespace Vendidero\Germanized\Pro\Blocks\StoreApi\Routes\V1;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Routes\V1\AbstractCartRoute;
use Vendidero\Germanized\Pro\Blocks\VatId;
use Vendidero\Germanized\Pro\Package;

/**
 * CartAddItem class.
 */
class CartUpdateVatId extends AbstractCartRoute {
	/**
	 * The route identifier.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'update-vat-id';

	/**
	 * The route's schema.
	 *
	 * @var string
	 */
	const SCHEMA_TYPE = 'vat-id-update';

	/**
	 * Get the path of this REST route.
	 *
	 * @return string
	 */
	public function get_path() {
		return '/cart/update-vat-id';
	}

	/**
	 * Get method arguments for this REST route.
	 *
	 * @return array An array of endpoints.
	 */
	public function get_args() {
		return array(
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'get_response' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'vat_id'       => array(
						'description' => __( 'The vat id to validate.', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'country'      => array(
						'description' => __( 'The country.', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'postcode'     => array(
						'description' => __( 'The postcode.', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'company'      => array(
						'description' => __( 'The company field.', 'woocommerce-germanized-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'address_type' => array(
						'description' => __( 'Whether the id belongs to billing or shipping.', 'woocommerce-germanized-pro' ),
						'type'        => 'enum',
						'options'     => array( 'shipping', 'billing' ),
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
			'schema'      => array( $this->schema, 'get_public_item_schema' ),
			'allow_batch' => array( 'v1' => false ),
		);
	}

	/**
	 * Handle the request and return a valid response for this endpoint.
	 *
	 * @throws RouteException On error.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	protected function get_route_post_response( \WP_REST_Request $request ) {
		$original_vat_id = trim( wc_clean( wp_unslash( $request['vat_id'] ) ) );
		$address_type    = 'billing' === wc_clean( wp_unslash( $request['address_type'] ) ) ? 'billing' : 'shipping';

		if ( ! empty( $original_vat_id ) ) {
			$address_data = array(
				'country'  => strtoupper( wp_unslash( $request['country'] ) ),
				'postcode' => wc_clean( wp_unslash( $request['postcode'] ) ),
				'city'     => wc_clean( wp_unslash( $request['city'] ) ),
				'company'  => wc_clean( wp_unslash( $request['company'] ) ),
			);

			$helper          = \WC_GZDP_VAT_Helper::instance();
			$original_vat_id = $helper->set_vat_id_format( $original_vat_id );
			$result          = Package::container()->get( VatId::class )->validate( $original_vat_id, $address_data );
		} else {
			$result = true;
		}

		if ( is_wp_error( $result ) ) {
			$session = wc()->session;
			$session->set( "{$address_type}_vat_id", '' );

			throw new RouteException( esc_html( $result->get_error_code() ), esc_html( $result->get_error_message() ), 400 );
		} else {
			$session = wc()->session;
			$session->set( "{$address_type}_vat_id", $original_vat_id );

			WC()->customer->update_meta_data( "{$address_type}_vat_id", $original_vat_id );

			$exempt_address_data = array(
				"{$address_type}_country"  => $address_data['country'],
				"{$address_type}_postcode" => $address_data['postcode'],
				"{$address_type}_city"     => $address_data['city'],
				"{$address_type}_company"  => $address_data['company'],
			);

			Package::container()->get( VatId::class )->maybe_set_cart_vat_exemption( null, null, $exempt_address_data );

			$result                 = new \stdClass();
			$result->vat_id         = $original_vat_id;
			$result->has_vat_exempt = WC()->customer->is_vat_exempt();

			$response = rest_ensure_response( $this->schema->get_item_response( $result ) );
			$response->set_status( 201 );
		}

		return $response;
	}
}
