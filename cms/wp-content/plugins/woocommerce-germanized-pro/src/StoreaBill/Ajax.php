<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Helper;
use Vendidero\Germanized\Pro\StoreaBill\PackingSlip\PackingSlips;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * Constructor.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	protected static function add_ajax_events() {
		$ajax_events = array(
			'refresh_packing_slip',
			'create_packing_slip',
			'remove_packing_slip',
			'create_commercial_invoice_load',
			'create_commercial_invoice_submit',
			'remove_commercial_invoice',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.PHP.IniSet.display_errors_Disallowed
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	public static function create_commercial_invoice_submit() {
		check_ajax_referer( 'wc-gzdp-create-commercial-invoice-submit', 'security' );

		if ( ! isset( $_POST['reference_id'] ) ) {
			wp_die();
		}

		if ( ! current_user_can( 'create_commercial_invoices' ) ) {
			wp_die( -1 );
		}

		$shipment_id = absint( $_POST['reference_id'] );

		if ( ! $shipment = wc_stc_get_shipment( $shipment_id ) ) {
			wp_die( -1 );
		}

		$args = array();

		if ( ! empty( $_POST['items'] ) ) {
			$args['items'] = (array) wc_clean( wp_unslash( $_POST['items'] ) );
		}

		$commercial_invoice_props = array(
			'incoterms',
			'export_type',
			'export_reason',
			'insurance_total',
			'shipping_total',
			'fee_total',
			'currency',
			'invoice_type',
		);

		foreach ( $commercial_invoice_props as $prop_name ) {
			if ( ! empty( $_POST[ "commercial_invoice_{$prop_name}" ] ) ) {
				$args[ $prop_name ] = wc_clean( wp_unslash( $_POST[ "commercial_invoice_{$prop_name}" ] ) );
			}
		}

		$result = Helper::sync_commercial_invoice( $shipment, $args );

		if ( true === $result && ( $commercial_invoice = Helper::get_commercial_invoice( $shipment ) ) ) {
			$response = array(
				'success'               => true,
				'commercial_invoice_id' => $commercial_invoice->get_id(),
				'shipment_id'           => $shipment_id,
				'messages'              => array(),
				'fragments'             => array(
					'#shipment-' . $shipment_id . ' .wc-gzdp-shipment-commercial-invoice' => self::refresh_commercial_invoice_html( $shipment, $commercial_invoice ),
					'tr#shipment-' . $shipment_id . ' td.actions .wc-stc-shipment-action-button-generate-commercial-invoice' => self::commercial_invoice_download_button_html( $commercial_invoice ),
				),
			);
		} else {
			$response = array(
				'success'     => false,
				'shipment_id' => $shipment_id,
				'messages'    => is_wp_error( $result ) ? $result->get_error_messages() : array( _x( 'There was an error while creating the commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ) ),
			);
		}

		wp_send_json( $response );
	}

	/**
	 * @param CommercialInvoice $commercial_invoice
	 *
	 * @return string
	 */
	protected static function commercial_invoice_download_button_html( $commercial_invoice ) {
		return '<a class="button wc-stc-shipment-action-button wc-stc-shipment-action-button-download-commercial-invoice download" href="' . esc_url( $commercial_invoice->get_download_url() ) . '" target="_blank" title="' . _x( 'Download commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ) . '">' . _x( 'Download commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ) . '</a>';
	}

	protected static function refresh_commercial_invoice_html( $p_shipment, $p_commercial_invoice = false ) {
		$shipment = $p_shipment;

		if ( $p_commercial_invoice ) {
			$commercial_invoice = $p_commercial_invoice;
		} else {
			$commercial_invoice = false;
		}

		ob_start();
		include_once WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-commercial-invoice.php';
		$html = ob_get_clean();

		return $html;
	}

	public static function create_commercial_invoice_load() {
		check_ajax_referer( 'wc-gzdp-create-commercial-invoice-load', 'security' );

		if ( ! isset( $_POST['reference_id'] ) ) {
			wp_die();
		}

		if ( ! current_user_can( 'create_commercial_invoices' ) ) {
			wp_die( -1 );
		}

		$shipment_id = absint( $_POST['reference_id'] );

		if ( ! $shipment = wc_stc_get_shipment( $shipment_id ) ) {
			wp_die( -1 );
		}

		ob_start();
		include WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-commercial-invoice-backbone-form.php';
		$html = ob_get_clean();

		$response = array(
			'fragments'   => array(
				'.wc-stc-shipment-create-commercial-invoice' => '<div class="wc-stc-shipment-create-commercial-invoice">' . $html . '</div>',
			),
			'shipment_id' => $shipment_id,
			'success'     => true,
		);

		wp_send_json( $response );
	}

	public static function remove_commercial_invoice() {
		check_ajax_referer( 'wc-gzdp-remove-commercial-invoice', 'security' );

		if ( ! isset( $_POST['commercial_invoice'] ) ) {
			wp_die( -1 );
		}

		$commercial_invoice_id = absint( $_POST['commercial_invoice'] );

		if ( ! current_user_can( 'delete_commercial_invoice', $commercial_invoice_id ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the commercial invoice.', 'woocommerce-germanized-pro' ),
			),
		);

		if ( ! $commercial_invoice = wc_gzdp_get_commercial_invoice( $commercial_invoice_id ) ) {
			wp_send_json( $response_error );
		}

		try {
			$shipment    = $commercial_invoice->get_shipment();
			$shipment_id = $shipment->get_id();

			$commercial_invoice->delete( true );

			/**
			 * Explicitly pass needs_refresh to make sure shipment modal is re-initiated.
			 */
			$response = array(
				'success'       => true,
				'needs_refresh' => true,
				'shipment_id'   => $shipment_id,
				'fragments'     => array(
					'#shipment-' . $shipment_id . ' .wc-gzdp-shipment-commercial-invoice' => self::refresh_commercial_invoice_html( $shipment ),
				),
			);

			wp_send_json( $response );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		wp_send_json( $response_error );
	}

	public static function create_packing_slip() {
		check_ajax_referer( 'wc-gzdp-create-packing-slip', 'security' );

		if ( ! isset( $_GET['shipment_id'] ) ) {
			wp_die();
		}

		if ( ! current_user_can( 'create_packing_slips' ) ) {
			wp_die( -1 );
		}

		$shipment_id = absint( $_GET['shipment_id'] );

		if ( ! $shipment = wc_stc_get_shipment( $shipment_id ) ) {
			wp_die( -1 );
		}

		$result = self::maybe_create_packing_slip( $shipment );

		wp_safe_redirect( esc_url_raw( ( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-stc-shipments' ) ) ) );
		exit;
	}

	protected static function maybe_create_packing_slip( $shipment ) {
		$result = new \WP_Error( 'packing-slip-error', __( 'Error while generating packing slip.', 'woocommerce-germanized-pro' ) );

		try {
			$result = PackingSlips::sync_packing_slip( $shipment, true, true );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		return $result;
	}

	public static function refresh_packing_slip() {
		check_ajax_referer( 'wc-gzdp-refresh-packing-slip', 'security' );

		if ( ! current_user_can( 'create_packing_slips' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the packing slip.', 'woocommerce-germanized-pro' ),
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_stc_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		$result = self::maybe_create_packing_slip( $shipment );

		if ( ! is_wp_error( $result ) && ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment ) ) ) {
			$response = array(
				'success'      => true,
				'packing_slip' => $packing_slip->get_id(),
				'fragments'    => array(
					'#shipment-' . $shipment_id . ' .wc-stc-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment, $packing_slip ),
				),
			);

			wp_send_json( $response );
		} elseif ( is_wp_error( $result ) ) {
			wp_send_json(
				array(
					'success'  => false,
					'messages' => $result->get_error_messages(),
				)
			);
		} else {
			wp_send_json( $response_error );
		}
	}

	public static function remove_packing_slip() {
		check_ajax_referer( 'wc-gzdp-remove-packing-slip', 'security' );

		if ( ! isset( $_POST['packing_slip'] ) ) {
			wp_die( -1 );
		}

		$packing_slip_id = absint( $_POST['packing_slip'] );

		if ( ! current_user_can( 'delete_packing_slip', $packing_slip_id ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the packing slip.', 'woocommerce-germanized-pro' ),
			),
		);

		if ( ! $packing_slip = wc_gzdp_get_packing_slip( $packing_slip_id ) ) {
			wp_send_json( $response_error );
		}

		try {
			$shipment    = $packing_slip->get_shipment();
			$shipment_id = $shipment->get_id();

			$packing_slip->delete( true );

			$response = array(
				'success'   => true,
				'fragments' => array(
					'#shipment-' . $shipment_id . ' .wc-stc-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment ),
				),
			);

			wp_send_json( $response );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		}

		wp_send_json( $response_error );
	}

	protected static function refresh_packing_slip_html( $p_shipment, $p_packing_slip = false ) {
		$shipment = $p_shipment;

		if ( $p_packing_slip ) {
			$packing_slip = $p_packing_slip;
		} else {
			$packing_slip = false;
		}

		ob_start();
		include_once WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-packing-slip.php';
		$html = ob_get_clean();

		return $html;
	}
}
