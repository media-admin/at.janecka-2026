<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Document\BulkActionHandler;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

class BulkRefresh extends BulkActionHandler {

	public function get_title() {
		return _x( 'Refresh Invoices', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach ( $current as $invoice_id ) {
				CacheHelper::prevent_caching( 'bulk' );

				if ( $invoice = sab_get_invoice( $invoice_id ) ) {
					if ( $invoice->is_finalized() ) {
						add_filter( 'storeabill_invoice_is_editable', '__return_true', 999 );
						add_filter( 'storeabill_invoice_cancellation_is_editable', '__return_true', 999 );
						$result = $invoice->render();
						remove_all_filters( 'storeabill_invoice_is_editable', 999 );
						remove_all_filters( 'storeabill_invoice_cancellation_is_editable', 999 );

						if ( is_wp_error( $result ) ) {
							foreach ( $result->get_error_messages() as $error ) {
								/* translators: 1: invoice title 2: error message */
								$this->add_notice( sprintf( _x( '%1$s error: %2$s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_title(), $error ), 'error' );
							}
						}
					}
				}
			}
		}
	}

	public function get_success_message() {
		return _x( 'Invoices refreshed successfully', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_limit() {
		return 1;
	}

	public function get_action_name() {
		return 'refresh';
	}
}
