<?php

namespace Vendidero\StoreaBill\WooCommerce\Admin;

use Vendidero\StoreaBill\Admin\Settings;
use Vendidero\StoreaBill\BusinessInformation;
use Vendidero\StoreaBill\Document\BulkActionHandler;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\WooCommerce\Automation;
use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Admin {

	protected static $bulk_handlers = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		Fields::init();
		Ajax::init();

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ), 15 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 15 );

		if ( ! Package::enable_accounting() ) {
			return;
		}

		add_action( 'admin_notices', array( __CLASS__, 'setting_warning' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_adjust_setting' ), 10 );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 35, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 15 );
		add_action( 'load-woocommerce_page_sab-accounting', array( __CLASS__, 'setup_table' ), 0 );
		add_filter( 'woocommerce_screen_ids', array( __CLASS__, 'register_screen_ids' ), 10 );

		/**
		 * Handle settings saving for sync handlers
		 */
		add_action( 'woocommerce_update_options_germanized-storeabill', array( __CLASS__, 'update_sync_handlers' ), 10 );

		/**
		 * Add download actions to Woo order table. Output our custom buttons instead of using woocommerce_admin_order_actions
		 * as the woocommerce_admin_order_actions does not allow to set _blank link state.
		 */
		add_action( 'woocommerce_admin_order_actions_end', array( __CLASS__, 'order_download_actions' ), 5 );

		add_action(
			'admin_init',
			function () {
				/**
				 * Render bulk progress
				 */
				add_action( 'manage_posts_extra_tablenav', array( __CLASS__, 'render_bulk_actions' ), 150, 1 );
				add_action( 'woocommerce_order_list_table_extra_tablenav', array( __CLASS__, 'order_tablenav' ), 150, 2 );

				add_filter( 'bulk_actions-' . ( 'shop_order' === self::get_order_screen_id() ? 'edit-shop_order' : self::get_order_screen_id() ), array( __CLASS__, 'add_bulk_actions' ) );
				add_filter( 'storeabill_shop_order_bulk_action_handlers', array( __CLASS__, 'register_bulk_actions' ), 10 );
			}
		);

		foreach ( BusinessInformation::get_setting_fields_to_observe() as $setting_key ) {
			add_action( "update_option_{$setting_key}", array( __CLASS__, 'observe_business_information_change' ), 10, 3 );
		}
	}

	public static function observe_business_information_change( $old_value, $value, $option_name ) {
		if ( $old_value !== $value && class_exists( 'WC_Admin_Settings' ) ) {
			\WC_Admin_Settings::add_message( _x( 'Seems like you\'ve changed settings related to your business. Please review your settings under WooCommerce > Settings > Germanized > Invoices & Packing Slips > Business Information and update accordingly where needed.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}
	}

	public static function order_tablenav( $order_type, $which ) {
		self::render_bulk_actions( $which );
	}

	public static function maybe_adjust_setting() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['action'], $_GET['_wpnonce'] ) && 'sab-woo-round-tax-at-subtotal' === $_GET['action'] && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'sab-woo-round-tax-at-subtotal' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$redirect = remove_query_arg( array( '_wpnonce', 'action' ) );
			$incl_tax = 'yes' === get_option( 'woocommerce_prices_include_tax' );

			if ( wc_tax_enabled() ) {
				if ( $incl_tax ) {
					update_option( 'woocommerce_tax_round_at_subtotal', 'yes' );
				} else {
					update_option( 'woocommerce_tax_round_at_subtotal', 'no' );
				}
			}

			wp_safe_redirect( esc_url_raw( $redirect ) );
		}
	}

	public static function setting_warning() {
		$calc_tax            = wc_tax_enabled();
		$incl_tax            = 'yes' === get_option( 'woocommerce_prices_include_tax' );
		$notice              = false;
		$adjust_setting_link = wp_nonce_url( add_query_arg( array( 'action' => 'sab-woo-round-tax-at-subtotal' ) ), 'sab-woo-round-tax-at-subtotal' );

		if ( current_user_can( 'manage_woocommerce' ) && $calc_tax ) {
			if ( $incl_tax && 'yes' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
				$notice = sprintf( _x( 'Your WooCommerce tax settings are inconsistent. Please make sure to <a href="%s">enable rounding tax at subtotal</a> to avoid rounding differences.', 'storeabill-core', 'woocommerce-germanized-pro' ), esc_url( $adjust_setting_link ) );
			} elseif ( ! $incl_tax && 'no' !== get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
				$notice = sprintf( _x( 'Your WooCommerce tax settings are inconsistent. Please make sure to <a href="%s">disable rounding tax at subtotal</a> to avoid rounding differences.', 'storeabill-core', 'woocommerce-germanized-pro' ), esc_url( $adjust_setting_link ) );
			}

			if ( false !== $notice ) {
				?>
				<div class="notice notice-error error">
					<p><?php echo wp_kses_post( $notice ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Use the woocommerce_admin_order_actions_end action instead of the
	 *
	 * @param \WC_Order $order
	 */
	public static function order_download_actions( $order ) {
		$invoices = Helper::get_invoices( $order->get_id() );
		$actions  = array();

		foreach ( $invoices as $invoice ) {
			if ( $invoice->has_file() ) {
				$actions[ "download-invoice-{$invoice->get_id()}" ] = array(
					'url'     => $invoice->get_download_url(),
					'name'    => sprintf( _x( 'Download %s', 'storeabill-core', 'woocommerce-germanized-pro' ), $invoice->get_title() ),
					'action'  => 'download download-' . esc_attr( str_replace( '_', '-', $invoice->get_type() ) ),
					'target'  => '_blank',
					'classes' => 'wc-action-button',
				);
			}
		}

		echo \Vendidero\StoreaBill\Admin\Admin::get_document_actions_html( apply_filters( 'storeabill_woocommerce_order_table_download_actions', $actions, $order ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public static function register_bulk_actions( $handlers ) {
		$handlers = array_merge(
			array(
				'sync_invoices'  => '\Vendidero\StoreaBill\WooCommerce\BulkSync',
				'merge_invoices' => '\Vendidero\StoreaBill\WooCommerce\BulkDownload',
			),
			$handlers
		);

		return $handlers;
	}

	public static function update_sync_handlers() {
		global $current_section;

		if ( $current_section && 'sync_handlers' === $current_section ) {
			if ( $sync_handler = Settings::get_current_sync_handler_name() ) {
				if ( $handler = \Vendidero\StoreaBill\ExternalSync\Helper::get_sync_handler( $sync_handler ) ) {
					$handler->process_admin_settings();

					if ( $handler->get_setting_errors() ) {
						foreach ( $handler->get_setting_errors() as $error ) {
							\WC_Admin_Settings::add_error( $error );
						}
					}
				}
			}
		}
	}

	public static function add_bulk_actions( $actions ) {
		foreach ( \Vendidero\StoreaBill\Admin\Admin::get_bulk_actions_handlers( 'shop_order' ) as $handler ) {
			$actions[ $handler->get_action() ] = $handler->get_title();
		}

		return $actions;
	}

	public static function render_bulk_actions( $which ) {
		$screen = get_current_screen();

		if ( 'top' === $which && $screen && 'shop_order' === $screen->post_type ) {
			$finished    = ( isset( $_GET['bulk_action_handling'] ) && 'finished' === $_GET['bulk_action_handling'] ) ? true : false;
			$bulk_action = ( isset( $_GET['current_bulk_action'] ) ) ? sab_clean( wp_unslash( $_GET['current_bulk_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $finished && ( $handler = \Vendidero\StoreaBill\Admin\Admin::get_bulk_action_handler( $bulk_action, 'shop_order' ) ) && check_admin_referer( $handler->get_done_nonce_action(), 'sab_bulk_action_nonce' ) ) {
				$handler->finish();
			}
			?>
			<div class="sab-bulk-action-wrapper">
				<h4 class="bulk-title"><?php echo esc_html_x( 'Processing bulk actions...', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h4>
				<div class="sab-bulk-notice-wrapper"></div>
				<progress class="sab-bulk-progress sab-progress-bar" max="100" value="0"></progress>
			</div>
			<?php
		}
	}

	public static function register_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, self::get_core_screen_ids() );

		return $screen_ids;
	}

	public static function setup_table() {
		global $wp_list_table, $current_document_type, $current_document_type_object;

		$current_document_type = isset( $_GET['document_type'] ) ? sab_clean( wp_unslash( $_GET['document_type'] ) ) : 'invoice';

		if ( ! $document_type_object = sab_get_document_type( $current_document_type ) ) {
			$current_document_type = 'invoice';
		}

		$current_document_type_object = sab_get_document_type( $current_document_type );

		$wp_list_table = new $current_document_type_object->admin_table_class_name(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$doaction      = $wp_list_table->current_action();

		if ( $doaction ) {
			/**
			 * This nonce is dynamically constructed by WP_List_Table and uses
			 * the normalized plural argument.
			 */
			check_admin_referer( 'bulk-' . sanitize_key( sab_get_document_type_label( $document_type_object, 'plural' ) ) );

			$pagenum     = $wp_list_table->get_pagenum();
			$parent_file = $wp_list_table->get_main_page();
			$sendback    = remove_query_arg( array( 'deleted', 'ids', 'changed', 'bulk_action' ), wp_get_referer() );

			if ( ! $sendback ) {
				$sendback = admin_url( $parent_file );
			}

			$sendback     = add_query_arg( 'paged', $pagenum, $sendback );
			$document_ids = array();

			if ( isset( $_REQUEST['ids'] ) ) {
				$document_ids = array_map( 'absint', explode( ',', wp_unslash( $_REQUEST['ids'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			} elseif ( ! empty( $_REQUEST['document'] ) ) {
				$document_ids = array_map( 'absint', wp_unslash( $_REQUEST['document'] ) );
			}

			if ( ! empty( $document_ids ) ) {
				$sendback = $wp_list_table->handle_bulk_actions( $doaction, $document_ids, $sendback );
			}

			$sendback = remove_query_arg( array( 'action', 'action2', '_status', 'bulk_edit', 'document' ), $sendback );

			wp_safe_redirect( esc_url_raw( $sendback ) );
			exit();
		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
			wp_safe_redirect( esc_url_raw( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
			exit;
		}

		$wp_list_table->set_bulk_notice();
		$wp_list_table->prepare_items();

		add_screen_option( 'per_page' );
	}

	public static function get_order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	protected static function is_order_meta_box_screen( $screen_id ) {
		return in_array( str_replace( 'edit-', '', $screen_id ), self::get_screen_ids( 'order-meta-boxes' ), true );
	}

	public static function get_core_screen_ids() {
		$screen_ids = array(
			'woocommerce_page_sab-accounting',
		);

		return $screen_ids;
	}

	public static function get_screen_ids( $for = '' ) {
		$screen_ids = array();

		foreach ( wc_get_order_types( $for ) as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		$screen_ids[] = self::get_order_screen_id();

		return array_filter( $screen_ids );
	}

	public static function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		wp_register_style( 'storeabill_orders_admin', Package::get_build_url() . '/admin/order-styles.css', array( 'storeabill_admin' ), Package::get_version() );

		// Admin styles for WC pages only.
		if ( in_array( $screen_id, self::get_screen_ids(), true ) ) {
			if ( strpos( $screen_id, 'order' ) !== false ) {
				wp_enqueue_style( 'storeabill_orders_admin' );
			}
		}
	}

	public static function admin_scripts() {
		global $post, $theorder;

		$screen               = get_current_screen();
		$screen_id            = $screen ? $screen->id : '';
		$post_id              = isset( $post->ID ) ? $post->ID : '';
		$order_or_post_object = $post;
		$order_screen_id      = self::get_order_screen_id();

		if ( ( $theorder instanceof \WC_Order ) && self::is_order_meta_box_screen( $screen_id ) ) {
			$order_or_post_object = $theorder;
		}

		wp_register_script( 'storeabill_woo_admin_edit_order', Package::get_build_url() . '/admin/edit-order.js', array( 'woocommerce_admin', 'storeabill_admin_document_modal', 'storeabill_admin_global', 'wc-admin-order-meta-boxes' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		wp_register_script( 'storeabill_woo_admin_bulk_actions', Package::get_build_url() . '/admin/bulk-actions.js', array( 'woocommerce_admin', 'storeabill_admin_global' ), Package::get_version() ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		// Orders.
		if ( self::is_order_meta_box_screen( $screen_id ) ) {
			wp_enqueue_script( 'storeabill_woo_admin_edit_order' );

			$order_order_post_id = $post_id;

			if ( self::is_order_meta_box_screen( $screen_id ) && isset( $order_or_post_object ) && is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'get_post_or_order_id' ) ) ) {
				$order_order_post_id = \Automattic\WooCommerce\Utilities\OrderUtil::get_post_or_order_id( $order_or_post_object );
			}

			wp_localize_script(
				'storeabill_woo_admin_edit_order',
				'storeabill_admin_edit_order_params',
				array(
					'ajax_url'                     => admin_url( 'admin-ajax.php' ),
					'edit_documents_nonce'         => wp_create_nonce( 'sab-edit-woo-documents' ),
					'tax_based_on'                 => esc_attr( get_option( 'woocommerce_tax_based_on' ) ),
					'order_id'                     => $order_order_post_id,
					'i18n_delete_document_notice'  => _x( 'Do you really want to delete the document?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'i18n_cancel_invoice_notice'   => _x( 'Do you really want to cancel the invoice?', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'create_einvoice_load_nonce'   => wp_create_nonce( 'sab-create-einvoice-load' ),
					'create_einvoice_submit_nonce' => wp_create_nonce( 'sab-create-einvoice-submit' ),
				)
			);
		}

		if ( ( 'shop_order' === $order_screen_id && 'edit-shop_order' === $screen_id ) || $order_screen_id === $screen_id ) {
			wp_enqueue_script( 'storeabill_woo_admin_bulk_actions' );

			$bulk_actions = array();

			foreach ( \Vendidero\StoreaBill\Admin\Admin::get_bulk_actions_handlers( 'shop_order' ) as $handler ) {
				$bulk_actions[ sanitize_key( $handler->get_action() ) ] = array(
					'title'               => $handler->get_title(),
					'nonce'               => wp_create_nonce( $handler->get_nonce_action() ),
					'parse_ids_ascending' => $handler->parse_ids_ascending(),
					'id_order_by_column'  => $handler->get_id_order_by_column(),
				);
			}

			wp_localize_script(
				'storeabill_woo_admin_bulk_actions',
				'storeabill_admin_bulk_actions_params',
				array(
					'ajax_url'     => admin_url( 'admin-ajax.php' ),
					'bulk_actions' => $bulk_actions,
					'table_type'   => Helper::is_hpos_enabled() ? 'order' : 'post',
					'object_type'  => 'shop_order',
				)
			);
		}
	}

	public static function add_meta_boxes( $post_type_or_screen_id, $post ) {
		global $pagenow;

		/**
		 * Hide invoice panel while creating new orders.
		 */
		if ( 'post-new.php' === $pagenow || ( isset( $_GET['action'] ) && 'new' === sab_clean( wp_unslash( $_GET['action'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$order_type_screen_ids = array_merge( wc_get_order_types( 'order-meta-boxes' ), array( self::get_order_screen_id() ) );

		foreach ( $order_type_screen_ids as $type ) {
			if ( apply_filters( "storeabill_woo_order_type_{$type}_add_invoice_meta_box", true ) ) {
				add_meta_box( 'storeabill-invoices', _x( 'Invoices', 'storeabill-core', 'woocommerce-germanized-pro' ), array( InvoiceMetaBox::class, 'output' ), $type, 'normal', 'high' );
			}
		}
	}

	public static function add_menu() {
		add_submenu_page( 'woocommerce', _x( 'Accounting', 'storeabill-core', 'woocommerce-germanized-pro' ), _x( 'Accounting', 'storeabill-core', 'woocommerce-germanized-pro' ), 'manage_storeabill', 'sab-accounting', array( 'Vendidero\StoreaBill\Admin\Admin', 'render_accounting_page' ) );
	}
}
