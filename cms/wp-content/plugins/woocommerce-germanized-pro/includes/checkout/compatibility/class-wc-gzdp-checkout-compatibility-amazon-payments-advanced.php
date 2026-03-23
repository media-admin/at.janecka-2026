<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_GZDP_Checkout_Compatibility_Amazon_Payments_Advanced {

	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'remove_ajax_hook' ), 20 );

		// Before amazon app widget script
		add_action( 'wp_enqueue_scripts', array( $this, 'set_sca_scripts' ), 9 );
		add_action( 'wc_amazon_pa_scripts_enqueued', array( $this, 'localize_sca_script' ), 10, 2 );

		add_action( 'woocommerce_gzdp_checkout_scripts', array( $this, 'set_scripts' ), 10, 2 );

		add_action( 'woocommerce_checkout_billing', array( $this, 'billing_fields_placeholder' ), 500 );
		add_action( 'woocommerce_checkout_shipping', array( $this, 'shipping_fields_placeholder' ), 500 );

		add_action( 'admin_init', array( $this, 'maybe_disable_notice' ) );
	}

	/**
	 * Disable the legacy notice added by Amazon Pay regarding cancellations in Germanized
	 */
	public function maybe_disable_notice() {
		if ( ! get_option( 'amazon_pay_dismiss_germanized_notice' ) ) {
			update_option( 'amazon_pay_dismiss_germanized_notice', 'no' );
		}
	}

	public function billing_fields_placeholder() {
		echo '<div class="wc-gzdp-amazon-billing-fields-wrapper"></div>';
	}

	public function shipping_fields_placeholder() {
		echo '<div class="wc-gzdp-amazon-shipping-fields-wrapper"></div>';
		echo '<div class="wc-gzdp-amazon-additional-fields-wrapper"></div>';
	}

	public function localize_sca_script( $type, $params ) {
		wp_localize_script(
			'wc-gzdp-amazon-multistep-sca-helper',
			'wc_gzdp_multistep_amazon_sca_helper',
			array(
				'is_sca' => ( isset( $params['is_sca'] ) && $params['is_sca'] ) ? true : false,
			)
		);
	}

	public function set_scripts( $multistep, $assets ) {
		$assets = WC_GZDP_Assets::instance();

		// Multistep Checkout
		$assets->register_script( 'wc-gzdp-amazon-multistep-helper', 'static/checkout-multistep-amazon-helper.js', array( 'wc-gzdp-checkout-multistep' ) );

		wp_localize_script(
			'wc-gzdp-amazon-multistep-helper',
			'wc_gzdp_multistep_amazon_helper_params',
			array(
				'managed_by' => _x( 'Managed by Amazon', 'multistep', 'woocommerce-germanized-pro' ),
			)
		);

		wp_enqueue_script( 'wc-gzdp-amazon-multistep-helper' );
	}

	public function set_sca_scripts() {
		$assets = WC_GZDP_Assets::instance();

		// Multistep Checkout
		$assets->register_script( 'wc-gzdp-amazon-multistep-sca-helper', 'static/checkout-multistep-amazon-sca-helper.js', array( 'jquery' ) );

		if ( is_checkout() ) {
			wp_enqueue_script( 'wc-gzdp-amazon-multistep-sca-helper' );
		}
	}

	public function remove_ajax_hook() {
		// Remove payment validation filter of step 2
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'remove_step_address_validation_filter' ), 8 );
	}

	public function remove_step_address_validation_filter() {
		if ( isset( $_POST['payment_method'] ) && 'amazon_payments_advanced' === wc_clean( wp_unslash( $_POST['payment_method'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			remove_all_filters( 'woocommerce_cart_needs_payment' );
		}
	}
}
