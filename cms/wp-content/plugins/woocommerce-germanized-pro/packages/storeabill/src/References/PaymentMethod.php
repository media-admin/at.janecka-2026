<?php

namespace Vendidero\StoreaBill\References;

defined( 'ABSPATH' ) || exit;

class PaymentMethod {

	/**
	 * Gets available order references.
	 *
	 * @return mixed|void
	 */
	public static function get_references() {
		$references = apply_filters(
			'storeabill_payment_method_reference_types',
			array(
				'woocommerce' => '\Vendidero\StoreaBill\WooCommerce\PaymentMethod',
			)
		);

		return $references;
	}

	/**
	 * @param $payment_method
	 * @param string $ref_type
	 *
	 * @return bool|\Vendidero\StoreaBill\Interfaces\PaymentMethod
	 */
	public static function get_payment_method( $payment_method, $ref_type = 'woocommerce' ) {
		$references        = self::get_references();
		$default_reference = '\Vendidero\StoreaBill\WooCommerce\PaymentMethod';

		if ( array_key_exists( $ref_type, $references ) ) {
			$reference = $references[ $ref_type ];
		} else {
			$reference = $default_reference;
		}

		$obj = false;

		try {
			$obj = new $reference( $payment_method );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		if ( ! $obj || ! is_a( $obj, '\Vendidero\StoreaBill\Interfaces\PaymentMethod' ) ) {
			try {
				$obj = new $default_reference( $payment_method );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		return $obj;
	}
}
