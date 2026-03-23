<?php

namespace Vendidero\StoreaBill\eInvoice;

use PHP_CodeSniffer\Util\Help;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Automation {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ), 50 );
	}

	public static function setup() {
		if ( self::auto_create_einvoices() ) {
			add_action( 'storeabill_invoice_finalized', array( __CLASS__, 'maybe_create' ) );
			add_action( 'storeabill_invoice_cancellation_finalized', array( __CLASS__, 'maybe_create' ) );
		}
	}

	/**
	 * @param \Vendidero\StoreaBill\Invoice\Invoice $invoice
	 *
	 * @return void
	 */
	public static function maybe_create( $invoice ) {
		foreach ( Helper::get_available_specifications_by_invoice( $invoice ) as $specification_name ) {
			$specification = apply_filters(
				"storeabill_einvoice_auto_create_specification_{$specification_name}",
				array(
					'profile_name'    => self::get_auto_create_profile( $specification_name ),
					'additional_meta' => array(
						'merge_pdf' => self::merge_pdf_on_auto_create( $specification_name ),
					),
				),
				$invoice
			);

			if ( $specification['profile_name'] ) {
				Package::extended_log( sprintf( 'Creating %1$s (%2$s) eInvoice for %3$s', $specification['profile_name'], $specification_name, $invoice->get_title() ) );

				if ( ! $invoice->has_einvoice( $specification_name, $specification['profile_name'] ) ) {
					$result = $invoice->create_einvoice( $specification_name, $specification['profile_name'], $specification['additional_meta'] );

					if ( is_wp_error( $result ) ) {
						Package::log( $result->get_error_message(), 'error', 'eInvoice' );
					}
				}
			}
		}
	}

	public static function merge_pdf_on_auto_create( $specification_name ) {
		$merge_pdf = 'yes' === Package::get_setting( "invoice_auto_create_einvoices_{$specification_name}_merge" );

		if ( $specification = Helper::get_specification( $specification_name ) ) {
			if ( ! $specification['supports_pdf'] ) {
				$merge_pdf = false;
			}
		}

		return $merge_pdf;
	}

	public static function auto_create_einvoices() {
		return 'yes' === Package::get_setting( 'invoice_auto_create_einvoices' );
	}

	public static function get_auto_create_profile( $specification_name ) {
		$profile = '';

		if ( self::auto_create_einvoices() ) {
			$profile = Package::get_setting( "invoice_auto_create_einvoices_{$specification_name}_profile" );
		}

		return apply_filters( 'storeabill_einvoice_auto_create_profile', $profile, $specification_name );
	}
}
