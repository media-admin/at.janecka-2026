<?php
namespace Vendidero\Germanized\Pro\Blocks;

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Vendidero\Germanized\Pro\Package;

final class VatId {

	public function __construct() {
		if ( ! Package::is_vat_id_module_enabled() ) {
			return;
		}

		$this->register_integrations();
		$this->register_endpoint_data();
		$this->register_cart_customer_update();
		$this->register_validation_and_storage();

		/**
		 * Use this tweak to prevent Woo from showing a {vat_id} placeholder when
		 * formatting the address within the checkout block.
		 */
		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_before',
			function () {
				$this->maybe_load_customer_data();

				remove_filter( 'woocommerce_localisation_address_formats', array( \WC_GZDP_VAT_Helper::instance(), 'set_vat_field' ), 0 );

				// Make sure that Woo reloads address formats
				WC()->countries->address_formats = array();
			}
		);

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				add_filter( 'woocommerce_localisation_address_formats', array( \WC_GZDP_VAT_Helper::instance(), 'set_vat_field' ), 0 );

				// Make sure that Woo reloads address formats
				WC()->countries->address_formats = array();
			}
		);
	}

	protected function maybe_load_customer_data() {
		$helper = \WC_GZDP_VAT_Helper::instance();

		$vat_ids = array(
			'billing'  => array(
				'vat_id'  => $helper->get_user_vat_id( false, 'billing' ),
				'country' => wc()->customer->get_billing_country(),
			),
			'shipping' => array(
				'vat_id'  => $helper->get_user_vat_id( false, 'shipping' ),
				'country' => wc()->customer->get_shipping_country(),
			),
		);

		/**
		 * Prevent loading vat ids from session that do not match current customer's country data.
		 * Setup session data for the initial cart load. During this initial load, the customer id
		 * for the current logged in customer may be set.
		 */
		foreach ( $vat_ids as $type => $vat_id_data ) {
			if ( ! empty( $vat_id_data['vat_id'] ) ) {
				$vat_id_parts = $helper->get_vat_id_from_string( $vat_id_data['vat_id'] );

				if ( $vat_id_parts['country'] !== $vat_id_data['country'] ) {
					WC()->session->set( "{$type}_vat_id", '' );
				} else {
					WC()->session->set( "{$type}_vat_id", $helper->set_vat_id_format( $vat_id_data['vat_id'] ) );
				}
			}
		}

		$this->maybe_set_cart_vat_exemption();
	}

	public function maybe_set_cart_vat_exemption( $billing_vat_id = null, $shipping_vat_id = null, $address_data = array() ) {
		if ( is_null( $billing_vat_id ) ) {
			$billing_vat_id = $this->get_vat_id_from_session( 'billing' );
		}

		if ( is_null( $shipping_vat_id ) ) {
			$shipping_vat_id = $this->get_vat_id_from_session( 'shipping' );
		}

		$helper    = \WC_GZDP_VAT_Helper::instance();
		$post_data = array_merge(
			$address_data,
			array(
				'billing_vat_id'            => $billing_vat_id,
				'shipping_vat_id'           => $shipping_vat_id,
				'ship_to_different_address' => WC()->cart->needs_shipping() ? true : false,
			)
		);

		$helper->check_vat_exemption(
			$post_data,
			false,
			true
		);
	}

	private function get_vat_id_from_session( $type = 'billing' ) {
		if ( $vat_id = wc()->session->get( "{$type}_vat_id", null ) ) {
			return $vat_id;
		}

		return '';
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new \Vendidero\Germanized\Pro\Blocks\Integrations\Checkout() );
			}
		);
	}

	private function register_cart_customer_update() {
		add_action(
			'woocommerce_store_api_cart_update_customer_from_request',
			function ( $customer, $request ) {
				$this->maybe_set_cart_vat_exemption();
			},
			10,
			2
		);
	}

	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-germanized-pro',
				'data_callback'   => function () {
					return $this->get_cart_data();
				},
				'schema_callback' => function () {
					return $this->get_cart_schema();
				},
			)
		);

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-germanized-pro',
				'schema_callback' => function () {
					return $this->get_checkout_schema();
				},
			)
		);

		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => 'woocommerce-germanized-pro-vat-id',
				'callback'  => function ( $data ) {
					$this->maybe_set_cart_vat_exemption();

					wc()->customer->save();
				},
			)
		);
	}

	private function register_validation_and_storage() {
		/**
		 * This hook fires before order creation after updating the customer
		 * with current request data.
		 */
		add_action(
			'woocommerce_store_api_checkout_update_customer_from_request',
			function ( $customer, $request ) {
				$gzd_data = $this->get_checkout_data_from_request( $request );

				$this->maybe_set_cart_vat_exemption( $gzd_data['billing_vat_id'], $gzd_data['shipping_vat_id'] );
			},
			10,
			2
		);

		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			function ( $order, $request ) {
				$this->validate_checkout_data( $order, $request );
			},
			10,
			2
		);
	}

	private function get_cart_schema() {
		return array(
			'billing_vat_id'  => array(
				'description' => __( 'Billing vat id.', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'shipping_vat_id' => array(
				'description' => __( 'Shipping vat id.', 'woocommerce-germanized-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		);
	}

	private function get_cart_data() {
		return array(
			'billing_vat_id'  => $this->get_vat_id_from_session( 'billing' ),
			'shipping_vat_id' => $this->get_vat_id_from_session( 'shipping' ),
		);
	}

	private function get_checkout_schema() {
		return array(
			'billing_vat_id'  => array(
				'description' => __( 'Billing vat id.', 'woocommerce-germanized-pro' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
			'shipping_vat_id' => array(
				'description' => __( 'Shipping vat id.', 'woocommerce-germanized-pro' ),
				'type'        => array( 'string', 'null' ),
				'context'     => array( 'view', 'edit' ),
			),
		);
	}

	/**
	 * @param string $vat_id
	 * @param array $address_data
	 *
	 * @return array|bool|\WP_Error
	 */
	public function validate( $vat_id, $address_data ) {
		$address_data = wp_parse_args(
			$address_data,
			array(
				'country' => '',
			)
		);

		$helper           = \WC_GZDP_VAT_Helper::instance();
		$vat_id_fragments = $helper->get_vat_id_from_string( $vat_id, $address_data['country'] );

		if ( ! $helper->country_supports_vat_id( $vat_id_fragments['country'] ) ) {
			$result = new \WP_Error( 'vat_id_validation_error', __( 'Your country does not support VAT IDs.', 'woocommerce-germanized-pro' ) );
		} else {
			$result = $helper->validate( $vat_id_fragments['country'], $vat_id_fragments['number'], $address_data );
		}

		return $result;
	}

	/**
	 * @param \WC_Order $order
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	private function validate_checkout_data( $order, $request ) {
		$gzd_data = $this->get_checkout_data_from_request( $request );

		if ( $this->has_checkout_data( 'billing_vat_id', $request ) || $this->has_checkout_data( 'shipping_vat_id', $request ) ) {
			$billing_vat_id     = $gzd_data['billing_vat_id'];
			$shipping_vat_id    = $gzd_data['shipping_vat_id'];
			$helper             = \WC_GZDP_VAT_Helper::instance();
			$has_updated_vat_id = false;

			if ( ! $helper->order_has_differing_shipping_address( $order ) && empty( $billing_vat_id ) ) {
				$billing_vat_id = $shipping_vat_id;
			}

			foreach ( array( 'shipping', 'billing' ) as $address_type ) {
				$vat_id = 'shipping' === $address_type ? $shipping_vat_id : $billing_vat_id;

				$address_data = array(
					'country'  => $order->get_billing_country(),
					'postcode' => $order->get_billing_postcode(),
					'city'     => $order->get_billing_city(),
					'company'  => $order->get_billing_company(),
				);

				if ( 'shipping' === $address_type ) {
					$address_data = array(
						'country'  => $order->get_shipping_country(),
						'postcode' => $order->get_shipping_postcode(),
						'city'     => $order->get_shipping_city(),
						'company'  => $order->get_shipping_company(),
					);
				}

				if ( $helper->vat_field_is_required( false, $address_data ) ) {
					if ( empty( $vat_id ) ) {
						throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( esc_html( "{$address_type}_vat_id_required" ), esc_html( sprintf( __( 'Please provide a valid VAT ID within your %1$s.', 'woocommerce-germanized-pro' ), ( 'shipping' === $address_type ? __( 'shipping address', 'woocommerce-germanized-pro' ) : __( 'billing address', 'woocommerce-germanized-pro' ) ) ) ), 400 );
					}
				}

				if ( ! empty( $vat_id ) ) {
					$vat_id = $helper->set_vat_id_format( $vat_id );
					$result = $this->validate( $vat_id, $address_data );

					if ( 'shipping' === $address_type && $helper->order_has_differing_shipping_address( $order ) && empty( $billing_vat_id ) && apply_filters( 'woocommerce_gzdp_differing_shipping_force_billing_vat_id_existence', true ) ) {
						throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'vat_id_validation_error', esc_html__( 'Please supply a valid VAT ID for your billing address.', 'woocommerce-germanized-pro' ), 400 );
					}

					if ( is_wp_error( $result ) ) {
						throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( esc_html( $result->get_error_code() ), esc_html( $result->get_error_message() ), 400 );
					} else {
						$order->update_meta_data( "_{$address_type}_vat_id", $vat_id );
						$has_updated_vat_id = true;
					}
				}
			}

			if ( $has_updated_vat_id ) {
				/**
				 * Persist customer changes for logged-in customers.
				 */
				if ( $order->get_customer_id() ) {
					$wc_customer = new \WC_Customer( $order->get_customer_id() );

					$wc_customer->update_meta_data( 'billing_vat_id', $order->get_meta( '_billing_vat_id' ) );
					$wc_customer->update_meta_data( 'shipping_vat_id', $order->get_meta( '_shipping_vat_id' ) );
					$wc_customer->save();
				}

				$customer = wc()->customer;
				$customer->update_meta_data( 'billing_vat_id', $order->get_meta( '_billing_vat_id' ) );
				$customer->update_meta_data( 'shipping_vat_id', $order->get_meta( '_shipping_vat_id' ) );

				if ( $helper->order_has_vat_exempt( $order ) ) {
					$customer->set_is_vat_exempt( true );
				} else {
					$customer->set_is_vat_exempt( false );
				}

				$customer->save();
				$order->calculate_totals();

				$helper->save_vat_result_data( $order );
			}
		}
	}

	private function has_checkout_data( $param, $request ) {
		$request_data = isset( $request['extensions']['woocommerce-germanized-pro'] ) ? (array) $request['extensions']['woocommerce-germanized-pro'] : array();

		return isset( $request_data[ $param ] ) && null !== $request_data[ $param ];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	private function get_checkout_data_from_request( $request ) {
		$data = array_filter( isset( $request['extensions']['woocommerce-germanized-pro'] ) ? (array) wc_clean( $request['extensions']['woocommerce-germanized-pro'] ) : array() );

		$data = wp_parse_args(
			$data,
			array(
				'billing_vat_id'  => '',
				'shipping_vat_id' => '',
			)
		);

		$has_shipping_address    = isset( $request['shipping_address'] ) && $request['shipping_address'] ? true : false;
		$data['shipping_vat_id'] = trim( $data['shipping_vat_id'] );
		$data['billing_vat_id']  = trim( $data['billing_vat_id'] );

		if ( ! $has_shipping_address ) {
			$data['shipping_vat_id'] = $data['billing_vat_id'];
		}

		return $data;
	}
}
