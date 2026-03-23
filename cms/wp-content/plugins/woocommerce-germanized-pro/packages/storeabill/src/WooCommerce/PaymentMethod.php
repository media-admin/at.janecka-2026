<?php

namespace Vendidero\StoreaBill\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * WooOrder class
 */
class PaymentMethod implements \Vendidero\StoreaBill\Interfaces\PaymentMethod {

	/**
	 * The actual payment gateway object
	 *
	 * @var \WC_Payment_Gateway
	 */
	protected $gateway;

	/**
	 * @param \WC_Payment_Gateway|string $gateway
	 *
	 * @throws \Exception
	 */
	public function __construct( $gateway ) {
		if ( ! is_a( $gateway, 'WC_Payment_Gateway' ) ) {
			if ( WC()->payment_gateways() ) {
				$gateways = WC()->payment_gateways->payment_gateways();

				if ( array_key_exists( $gateway, $gateways ) ) {
					$gateway = $gateways[ $gateway ];
				}
			}
		}

		if ( ! is_a( $gateway, 'WC_Payment_Gateway' ) ) {
			throw new \Exception( esc_html_x( 'Invalid payment gateway.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$this->gateway = $gateway;
	}

	public function get_id() {
		return $this->gateway->id;
	}

	public function get_name() {
		return $this->get_id();
	}

	public function get_description() {
		return $this->gateway->get_description();
	}

	public function get_title() {
		return $this->gateway->get_title();
	}

	public function get_instructions() {
		return isset( $this->gateway->instructions ) ? $this->gateway->instructions : '';
	}

	public function get_payment_method() {
		return $this->gateway;
	}

	public function get_object() {
		return $this->gateway;
	}

	/**
	 * Check if a method is callable by checking the underlying order object.
	 * Necessary because is_callable checks will always return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif ( is_callable( array( $this->get_payment_method(), $method ) ) ) {
			return true;
		}

		return false;
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
		if ( method_exists( $this->gateway, $method ) ) {
			return call_user_func_array( array( $this->gateway, $method ), $args );
		}

		return false;
	}

	public function get_reference_type() {
		return 'woocommerce';
	}

	public function get_meta( $key, $single = true, $context = 'view' ) {
		return $this->gateway->get_option( $key );
	}
}
