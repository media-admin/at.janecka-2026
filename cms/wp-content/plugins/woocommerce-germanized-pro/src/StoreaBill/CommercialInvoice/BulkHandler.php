<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\Shiptastic\Admin\BulkActionHandler;

defined( 'ABSPATH' ) || exit;

class BulkHandler extends BulkActionHandler {

	protected $path = '';

	public function get_action() {
		return 'commercial_invoices';
	}

	public function get_limit() {
		return 5;
	}

	public function get_title() {
		return _x( 'Generating commercial invoices...', 'commercial-invoice', 'woocommerce-germanized-pro' );
	}

	public function get_file() {
		$file = get_user_meta( get_current_user_id(), $this->get_file_option_name(), true );

		if ( $file && ! empty( $file ) ) {
			return sab_get_absolute_file_path( $file );
		}

		return '';
	}

	protected function update_file( $path ) {
		update_user_meta( get_current_user_id(), $this->get_file_option_name(), $path );
	}

	protected function get_file_option_name() {
		return '_sab_commercial_invoice_bulk_merge_path';
	}

	protected function get_files_option_name() {
		$action = sanitize_key( $this->get_action() );

		return "woocommerce_gzd_shipments_{$action}_bulk_files";
	}

	protected function get_files() {
		$files = get_user_meta( get_current_user_id(), $this->get_files_option_name(), true );

		if ( empty( $files ) || ! is_array( $files ) ) {
			$files = array();
		}

		return $files;
	}

	protected function add_file( $path ) {
		$files   = $this->get_files();
		$files[] = $path;

		update_user_meta( get_current_user_id(), $this->get_files_option_name(), $files );
	}

	public function reset( $is_new = false ) {
		parent::reset( $is_new );

		if ( $is_new ) {
			delete_user_meta( get_current_user_id(), $this->get_file_option_name() );
			delete_user_meta( get_current_user_id(), $this->get_files_option_name() );
		}
	}

	public function get_filename() {
		if ( $file = $this->get_file() ) {
			return basename( $file );
		}

		return '';
	}

	protected function get_download_button() {
		$download_button = '';

		if ( ( $path = $this->get_file() ) && file_exists( $path ) ) {
			$download_url = add_query_arg(
				array(
					'action' => 'wc-gzdp-download-commercial-invoice-export',
					'force'  => 'no',
				),
				wp_nonce_url( admin_url(), 'wc-gzdp-download-commercial-invoices' )
			);

			$download_button = '<a class="button button-primary bulk-download-button" style="margin-left: 1em;" href="' . esc_url( $download_url ) . '" target="_blank">' . _x( 'Download commercial invoices', 'commercial-invoice', 'woocommerce-germanized-pro' ) . '</a>';
		}

		return $download_button;
	}

	public function get_success_message() {
		$download_button = $this->get_download_button();

		return sprintf( _x( 'Successfully generated commercial invoices. %s', 'commercial-invoice', 'woocommerce-germanized-pro' ), $download_button );
	}

	public function admin_after_error() {
		$download_button = $this->get_download_button();

		if ( ! empty( $download_button ) ) {
			echo '<div class="notice"><p>' . sprintf( esc_html_x( 'Commercial invoices partially generated. %s', 'commercial-invoice', 'woocommerce-germanized-pro' ), wp_kses_post( $download_button ) ) . '</p></div>';
		}
	}

	public function handle() {
		$current = $this->get_current_ids();

		if ( ! empty( $current ) ) {
			foreach ( $current as $shipment_id ) {
				$commercial_invoice = wc_gzdp_get_commercial_invoice_by_shipment( $shipment_id );

				if ( ! $commercial_invoice ) {
					if ( $shipment = wc_stc_get_shipment( $shipment_id ) ) {
						if ( ! Helper::shipment_needs_commercial_invoice( $shipment ) ) {
							continue;
						}

						try {
							$result = Helper::sync_commercial_invoice( $shipment );

							if ( ! is_wp_error( $result ) ) {
								$commercial_invoice = Helper::get_commercial_invoice( $shipment );
							} else {
								foreach ( $result->get_error_messages() as $message ) {
									$this->add_notice( sprintf( _x( 'An error occurred while creating commercial invoice for %1$s: %2$s.', 'commercial-invoice', 'woocommerce-germanized-pro' ), '<a href="' . esc_url( $shipment->get_edit_shipment_url() ) . '" target="_blank">' . sprintf( __( 'shipment #%d', 'woocommerce-germanized-pro' ), $shipment_id ) . '</a>', $message ), 'error' );
								}
							}
						} catch ( \Exception $e ) {
							$this->add_notice( sprintf( _x( 'Error while creating commercial invoice for %s.', 'commercial-invoice', 'woocommerce-germanized-pro' ), '<a href="' . esc_url( $shipment->get_edit_shipment_url() ) . '" target="_blank">' . sprintf( __( 'shipment #%d', 'woocommerce-germanized-pro' ), $shipment_id ) . '</a>' ), 'error' );
						}
					}
				}

				// Merge to bulk print/download
				if ( $commercial_invoice ) {
					$this->add_file( $commercial_invoice->get_path() );
				}
			}
		}

		if ( $this->is_last_step() ) {
			try {
				$merger   = sab_get_pdf_merger();
				$filename = apply_filters( 'woocommerce_gzdp_commercial_invoice_bulk_filename', 'commercial-invoice-export.pdf', $this );

				foreach ( $this->get_files() as $file ) {
					if ( ! file_exists( $file ) ) {
						continue;
					}

					$merger->add( $file );
				}

				$new_file_stream = $merger->stream();

				if ( $new_file_path = sab_upload_document( $filename, $new_file_stream, true, true ) ) {
					$this->update_file( $new_file_path );
				}
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		$this->update_notices();
	}
}
