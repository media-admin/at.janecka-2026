<?php

namespace Vendidero\Germanized\Pro\Contract;

defined( 'ABSPATH' ) || exit;

class GatewayPlaceholder extends \WC_Payment_Gateway {

	/**
	 * @var \WC_Payment_Gateway
	 */
	private $original_gateway = null;

	private $original_gateway_id = '';

	/**
	 * @param \WC_Payment_Gateway $original_gateway
	 *
	 * Constructor for the gateway.
	 */
	public function __construct( $original_gateway ) {
		$this->original_gateway    = $original_gateway;
		$this->original_gateway_id = $original_gateway->id;

		/**
		 * Some gateways, e.g. Mollie may use tweaks to use magic methods (__get) to
		 * retrieve properties, e.g. enabled - calling those properties during the woocommerce_payment_gateways may
		 * lead to side effects, especially if the gateway method calls WC_Payment_Gateways again.
		 */
		$object_vars = get_object_vars( $original_gateway );
		$properties  = array(
			'description',
			'method_title',
			'title',
			'method_description',
			'id',
			'settings',
			'enabled',
			'chosen',
			'form_fields',
			'supports',
		);

		foreach ( $properties as $property ) {
			if ( array_key_exists( $property, $object_vars ) ) {
				$this->{$property} = $original_gateway->{$property};
			}
		}

		$this->id = 'placeholder_' . $this->original_gateway_id;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_order_received_url(),
		);
	}

	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_option_key() {
		return $this->plugin_id . $this->original_gateway_id . '_settings';
	}

	public function process_admin_options() {
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		return call_user_func_array( array( $this->original_gateway, $method ), $args );
	}

	/**
	 * __isset legacy.
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->original_gateway->{$key} );
	}

	/**
	 * __get function.
	 * @param string $key
	 * @return string
	 */
	public function __get( $key ) {
		return $this->original_gateway->{$key};
	}

	/**
	 * __set function.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->original_gateway->{$key} = $value;
	}
}
