<?php

namespace Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;

use Vendidero\Germanized\Pro\StoreaBill\AccountingHelper;
use Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice;
use Vendidero\StoreaBill\Admin\Settings;
use Vendidero\StoreaBill\Compatibility\WPML;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Template;
use Vendidero\StoreaBill\Utilities\CacheHelper;

defined( 'ABSPATH' ) || exit;

class Helper {

	protected static $current_locale = null;

	public static function init() {
		add_action( 'storeabill_before_commercial_invoice_object_save', array( __CLASS__, 'maybe_switch_render_lang' ), 10 );
		add_action( 'storeabill_after_commercial_invoice_object_save', array( __CLASS__, 'maybe_restore_render_lang' ), 10 );
		add_action( 'storeabill_before_render_document', array( __CLASS__, 'maybe_switch_render_lang' ), 10 );
		add_action( 'storeabill_after_render_document', array( __CLASS__, 'maybe_restore_render_lang' ), 10 );
		add_action( 'storeabill_editor_before_load_editor_preview_assets', array( __CLASS__, 'maybe_switch_render_lang' ), 10 );

		// Register document types
		add_action( 'storeabill_registered_core_document_types', array( __CLASS__, 'register_document_type' ), 10 );
		add_filter( 'storeabill_document_item_classname', array( __CLASS__, 'register_document_items' ), 10, 3 );
		add_filter( 'storeabill_data_stores', array( __CLASS__, 'register_data_store' ), 10 );
		add_filter( 'storeabill_commercial_invoice_editor_templates', array( __CLASS__, 'register_template' ) );
		add_filter( 'storeabill_commercial_invoice_shortcode_handler_classname', array( __CLASS__, 'register_shortcode_handler' ), 10 );
		add_filter( 'storeabill_available_document_number_placeholders', array( __CLASS__, 'number_placeholder' ), 10, 2 );

		if ( AccountingHelper::is_enabled() ) {
			add_action( 'init', array( __CLASS__, 'setup_automation' ), 50 );

			add_filter( 'storeabill_rest_api_get_rest_namespaces', array( __CLASS__, 'register_rest_controllers' ) );
			add_filter( 'storeabill_default_template_path', array( __CLASS__, 'register_default_template_path' ), 10, 2 );
			add_filter( 'storeabill_commercial_invoice_plain_title', array( __CLASS__, 'adjust_plain_title' ), 10, 2 );

			/**
			 * Sync Commercial Invoices
			 */
			add_action( 'woocommerce_after_shipment_object_save', array( __CLASS__, 'maybe_sync_commercial_invoice' ) );
			add_action( 'woocommerce_shiptastic_shipment_deleted', array( __CLASS__, 'delete_commercial_invoice' ), 10, 2 );
			add_action( 'woocommerce_gzdp_commercial_invoice_auto_sync_callback', array( __CLASS__, 'auto_sync_callback' ), 10 );

			/**
			 * Preview meta data
			 */
			add_filter( 'storeabill_commercial_invoice_item_meta_shortcode_format', array( __CLASS__, 'item_meta_shortcode_format' ), 10, 2 );
			add_filter( 'storeabill_commercial_invoice_preview_product_item_meta_types', array( __CLASS__, 'register_item_preview_meta' ), 10, 3 );

			/**
			 * Sync label customs data
			 */
			add_filter( 'woocommerce_shiptastic_label_customs_data', array( __CLASS__, 'customs_data' ), 10, 4 );
			add_filter( 'woocommerce_shiptastic_shipment_get_incoterms', array( __CLASS__, 'shipment_incoterms' ), 10, 2 );
			add_filter( 'woocommerce_shiptastic_shipment_get_commercial_invoice_path', array( __CLASS__, 'shipment_commercial_invoice' ), 10, 2 );
		}

		if ( is_admin() ) {
			add_filter( 'storeabill_admin_settings_sections', array( __CLASS__, 'register_setting_sections' ) );
			add_filter( 'storeabill_admin_settings', array( __CLASS__, 'register_settings' ), 10, 2 );

			if ( AccountingHelper::is_enabled() ) {
				add_action( 'admin_init', array( __CLASS__, 'download_bulk_export' ), 0 );
				add_action( 'woocommerce_shiptastic_meta_box_shipment_before_label', array( __CLASS__, 'meta_box' ), 20, 1 );

				add_filter( 'woocommerce_shiptastic_shipments_table_actions', array( __CLASS__, 'download_action' ), 10, 2 );
				add_filter( 'woocommerce_shiptastic_shipments_table_bulk_actions', array( __CLASS__, 'bulk_action' ), 10, 1 );
				add_filter( 'woocommerce_shiptastic_table_bulk_action_handlers', array( __CLASS__, 'register_bulk_handler' ) );
				add_filter( 'woocommerce_admin_order_actions', array( __CLASS__, 'order_download_actions' ), 10, 2 );
			}
		}
	}

	protected static function get_auto_statuses() {
		$statuses = array();

		if ( 'yes' === get_option( 'woocommerce_gzdp_commercial_invoice_auto' ) ) {
			$statuses = get_option( 'woocommerce_gzdp_commercial_invoice_auto_statuses' );

			if ( ! is_array( $statuses ) ) {
				$statuses = array( $statuses );
			}

			$statuses = array_filter( $statuses );

			foreach ( $statuses as $key => $status ) {
				$statuses[ $key ] = str_replace( 'gzd-', '', $status );
			}
		}

		return $statuses;
	}

	public static function setup_automation() {
		$statuses = self::get_auto_statuses();

		if ( 'yes' === get_option( 'woocommerce_gzdp_commercial_invoice_auto' ) && ! empty( $statuses ) ) {
			foreach ( $statuses as $status ) {
				add_action( 'woocommerce_shiptastic_shipment_status_' . $status, array( __CLASS__, 'queue_auto_sync' ), 10, 1 );
			}

			add_action( 'woocommerce_shiptastic_shipment_after_save', array( __CLASS__, 'check_automation_new_shipment' ), 10, 2 );
			add_action( 'woocommerce_shiptastic_return_shipment_after_save', array( __CLASS__, 'check_automation_new_shipment' ), 10, 2 );
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 * @param bool $is_new
	 *
	 * @return void
	 */
	public static function check_automation_new_shipment( $shipment, $is_new ) {
		if ( $is_new ) {
			$statuses = self::get_auto_statuses();

			if ( in_array( $shipment->get_status(), $statuses, true ) ) {
				self::queue_auto_sync( $shipment->get_id() );
			}
		}
	}

	/**
	 * @param integer $shipment_id
	 */
	public static function queue_auto_sync( $shipment_id ) {
		$defer = sab_allow_deferring( 'auto' );

		if ( ! $shipment = self::get_shipment( $shipment_id ) ) {
			return;
		}

		if ( ! self::shipment_needs_commercial_invoice( $shipment ) ) {
			return;
		}

		if ( ! apply_filters( 'woocommerce_gzdp_automatically_create_commercial_invoice', true, $shipment_id ) ) {
			return;
		}

		/**
		 * Cancel outstanding events and queue new.
		 */
		self::cancel_deferred_sync( $shipment_id );

		if ( $defer ) {
			$queue = WC()->queue();

			$defer_args = array(
				'shipment_id' => $shipment_id,
			);

			$queue->schedule_single(
				time() + 50,
				'woocommerce_gzdp_commercial_invoice_auto_sync_callback',
				$defer_args,
				'woocommerce-gzdp-commercial-invoice-sync'
			);
		} else {
			self::sync_commercial_invoice( $shipment->get_shipment() );
		}
	}

	/**
	 * @param string $title
	 * @param CommercialInvoice $document
	 *
	 * @return string
	 */
	public static function adjust_plain_title( $title, $document ) {
		// Check if at least the first two chars are uppercase
		$chr        = substr( $title, 0, 2 );
		$is_upper   = strtoupper( $chr ) === $chr;
		$type_title = $document->get_type_title();

		return $is_upper ? strtoupper( $type_title ) : $type_title;
	}

	/**
	 * @param Document|Template $document
	 *
	 * @return void
	 */
	public static function maybe_switch_render_lang( $document ) {
		$document_type = '';

		if ( is_a( $document, 'Vendidero\StoreaBill\Document\Template' ) ) {
			$document_type = $document->get_document_type();
		} elseif ( is_a( $document, 'Vendidero\StoreaBill\Document\Document' ) ) {
			$document_type = $document->get_type();
		}

		if ( 'commercial_invoice' === $document_type ) {
			self::switch_to_english_locale();
		}
	}

	/**
	 * @param Document|Template $document
	 *
	 * @return void
	 */
	public static function maybe_restore_render_lang( $document ) {
		$document_type = '';

		if ( is_a( $document, 'Vendidero\StoreaBill\Document\Template' ) ) {
			$document_type = $document->get_document_type();
		} elseif ( is_a( $document, 'Vendidero\StoreaBill\Document\Document' ) ) {
			$document_type = $document->get_type();
		}

		if ( 'commercial_invoice' === $document_type ) {
			self::restore_locale();
		}
	}

	public static function switch_to_english_locale() {
		if ( apply_filters( 'woocommerce_gzdp_force_commercial_invoice_english', true ) ) {
			/**
			 * Always restore locale (if stored) before setting new locale
			 */
			self::restore_locale();
			self::$current_locale = get_user_locale();

			wc_get_logger()->info( 'Switching locale to english. User locale: ' . self::$current_locale );

			self::switch_locale( 'en_US' );
		}
	}

	protected static function switch_locale( $locale ) {
		if ( WPML::is_active() && WPML::language_exists( 'en' ) ) {
			WPML::switch_language( 'en' );
		} else {
			WPML::switch_to_locale( $locale );
		}
	}

	public static function restore_locale() {
		if ( WPML::is_active() && WPML::language_exists( 'en' ) ) {
			WPML::restore_language();
		} elseif ( function_exists( 'restore_previous_locale' ) ) {
			self::$current_locale = null;
			$original_locale      = restore_previous_locale();

			if ( false !== $original_locale ) {
				self::switch_locale( $original_locale );
			}
		} elseif ( self::$current_locale ) {
			self::switch_locale( self::$current_locale );
			self::$current_locale = null;
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return void
	 */
	public static function meta_box( $the_shipment ) {
		$shipment           = $the_shipment;
		$commercial_invoice = wc_gzdp_get_commercial_invoice_by_shipment( $the_shipment );

		if ( $commercial_invoice || self::shipment_needs_commercial_invoice( $shipment ) ) {
			include WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-commercial-invoice.php';
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment|boolean $shipment
	 *
	 * @return string
	 */
	public static function get_default_invoice_type( $shipment = false ) {
		$default = 'commercial';

		if ( $shipment ) {
			if ( 0.0 === $shipment->get_total() ) {
				$default = 'proforma';
			}
		}

		return apply_filters( 'woocommerce_gzdp_commercial_invoice_default_invoice_type', $default, $shipment );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment|boolean $shipment
	 *
	 * @return string
	 */
	public static function get_default_export_type( $shipment = false ) {
		return apply_filters( 'woocommerce_gzdp_commercial_invoice_default_export_type', get_option( 'woocommerce_gzdp_commercial_invoice_default_export_type', 'permanent' ), $shipment );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment|boolean $shipment
	 *
	 * @return string
	 */
	public static function get_default_export_reason( $shipment = false ) {
		return apply_filters( 'woocommerce_gzdp_commercial_invoice_default_export_reason', get_option( 'woocommerce_gzdp_commercial_invoice_default_export_reason', 'sale' ), $shipment );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment|boolean $shipment
	 *
	 * @return string
	 */
	public static function get_default_incoterms( $shipment = false ) {
		return apply_filters( 'woocommerce_gzdp_commercial_invoice_default_incoterms', get_option( 'woocommerce_gzdp_commercial_invoice_default_incoterms', 'DAP' ), $shipment );
	}

	public static function get_available_invoice_types() {
		return apply_filters(
			'woocommerce_gzdp_commercial_invoice_available_invoice_types',
			array(
				'commercial' => _x( 'Commercial Invoice', 'commercial-invoice-type', 'woocommerce-germanized-pro' ),
				'proforma'   => _x( 'Proforma Invoice', 'commercial-invoice-type', 'woocommerce-germanized-pro' ),
			)
		);
	}

	public static function get_available_incoterms() {
		return apply_filters(
			'woocommerce_gzdp_commercial_invoice_available_incoterm',
			array(
				'DAP' => _x( 'Delivered at Place (DAP)', 'commercial-invoice-incoterm', 'woocommerce-germanized-pro' ),
				'DPU' => _x( 'Delivered at Place Unloaded (DPU)', 'commercial-invoice-incoterm', 'woocommerce-germanized-pro' ),
				'DDP' => _x( 'Delivered Duty Paid (DDP)', 'commercial-invoice-incoterm', 'woocommerce-germanized-pro' ),
				'EXW' => _x( 'Ex Works (EXW)', 'commercial-invoice-incoterm', 'woocommerce-germanized-pro' ),
			)
		);
	}

	public static function get_available_export_types() {
		return apply_filters(
			'woocommerce_gzdp_commercial_invoice_available_export_types',
			array(
				'permanent'     => _x( 'Permanent', 'commercial-invoice-export-type', 'woocommerce-germanized-pro' ),
				'temporary'     => _x( 'Temporary', 'commercial-invoice-export-type', 'woocommerce-germanized-pro' ),
				'repair_return' => _x( 'Repair & Return', 'commercial-invoice-export-type', 'woocommerce-germanized-pro' ),
			)
		);
	}

	public static function get_available_export_reasons() {
		return apply_filters(
			'woocommerce_gzdp_commercial_invoice_available_export_reasons',
			array(
				'sale'             => _x( 'For Sale', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'repair'           => _x( 'For Repair', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'after_repair'     => _x( 'After Repair', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'gift'             => _x( 'Gift', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'sample'           => _x( 'Sample', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'personal_use'     => _x( 'Personal Use', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'no_resale'        => _x( 'Not For Resale', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'replacement'      => _x( 'Replacement', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'intercompany'     => _x( 'Intercompany Transfer', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
				'personal_effects' => _x( 'Personal Effects', 'commercial-invoice-export-reason', 'woocommerce-germanized-pro' ),
			)
		);
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment|Shipment $shipment
	 *
	 * @return boolean
	 */
	public static function shipment_needs_commercial_invoice( $shipment ) {
		$needs_commercial_invoice = false;

		if ( is_a( $shipment, '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Shipment' ) ) {
			$shipment = $shipment->get_shipment();
		}

		if ( $shipment->is_shipping_international() ) {
			$needs_commercial_invoice = true;
		}

		return apply_filters( 'woocommerce_gzdp_shipment_needs_commercial_invoice', $needs_commercial_invoice, $shipment );
	}

	/**
	 * @param string $incoterms
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return string
	 */
	public static function shipment_incoterms( $incoterms, $shipment ) {
		if ( $commercial_invoice = self::get_commercial_invoice( $shipment ) ) {
			$incoterms = $commercial_invoice->get_incoterms();
		}

		return $incoterms;
	}

	/**
	 * @param string $path
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return string
	 */
	public static function shipment_commercial_invoice( $path, $shipment ) {
		if ( $commercial_invoice = self::get_commercial_invoice( $shipment ) ) {
			if ( $the_path = $commercial_invoice->get_path() ) {
				$path = $the_path;
			}
		}

		return $path;
	}

	protected static function encode_customs_str( $str, $max_length = 500 ) {
		return mb_substr( function_exists( 'wc_shiptastic_get_alphanumeric_string' ) ? wc_shiptastic_get_alphanumeric_string( $str ) : $str, 0, $max_length );
	}

	/**
	 * @param array $customs_data
	 * @param \Vendidero\Shiptastic\Labels\Label $label
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 * @param int $max_desc_length
	 *
	 * @return array
	 */
	public static function customs_data( $customs_data, $label, $shipment, $max_desc_length ) {
		if ( $commercial_invoice = self::get_commercial_invoice( $shipment ) ) {
			self::switch_to_english_locale();

			$customs_data = array_merge(
				$customs_data,
				array(
					'invoice_number'                => $commercial_invoice->get_formatted_number(),
					'invoice_date'                  => $commercial_invoice->get_date_created()->date_i18n( 'Y-m-d' ),
					'incoterms'                     => $commercial_invoice->get_incoterms(),
					'currency'                      => $commercial_invoice->get_currency(),
					'additional_fee'                => $commercial_invoice->get_shipping_total() + $commercial_invoice->get_fee_total() + $commercial_invoice->get_insurance_total(),
					'export_type'                   => $commercial_invoice->get_export_type(),
					'export_reason_description'     => self::encode_customs_str( $commercial_invoice->get_formatted_export_reason(), $max_desc_length ),
					'export_reason'                 => $commercial_invoice->get_export_reason(),
					'items'                         => array(),
					'item_total_weight_in_kg'       => $commercial_invoice->get_net_weight(),
					'item_total_weight_in_g'        => (int) ceil( (float) wc_get_weight( $commercial_invoice->get_net_weight(), 'g', 'kg' ) ),
					'item_total_gross_weight_in_kg' => $commercial_invoice->get_weight(),
					'item_total_gross_weight_in_g'  => (int) ceil( (float) wc_get_weight( $commercial_invoice->get_weight(), 'g', 'kg' ) ),
					'item_total_value'              => $commercial_invoice->get_product_total(),
					'total_value'                   => $commercial_invoice->get_total(),
					'is_proforma'                   => $commercial_invoice->is_proforma(),
					'additional_totals'             => array(
						'insurance' => $commercial_invoice->get_insurance_total(),
						'shipping'  => $commercial_invoice->get_shipping_total(),
						'other'     => $commercial_invoice->get_fee_total(),
					),
				)
			);

			$packaging_weight       = $commercial_invoice->get_packaging_weight();
			$added_packaging_weight = false;
			$item_description       = '';

			foreach ( $commercial_invoice->get_items() as $item ) {
				$item_gross_weight       = $item->get_weight() * $item->get_quantity();
				$single_item_description = $item->get_name();
				$item_description       .= ! empty( $item_description ) ? ', ' : '';
				$item_description       .= $single_item_description;

				if ( ! $added_packaging_weight ) {
					$item_gross_weight     += $packaging_weight;
					$added_packaging_weight = true;
				}

				$customs_data['items'][] = array(
					'description'         => self::encode_customs_str( $single_item_description, $max_desc_length ),
					'origin_code'         => $item->get_manufacture_country(),
					'tariff_number'       => preg_replace( '/[^a-z0-9]+/i', '', $item->get_hs_code() ),
					'mid'                 => preg_replace( '/[^a-z0-9]+/i', '', $item->get_mid_code() ),
					'quantity'            => $item->get_quantity(),
					'weight_in_kg'        => $item->get_weight() * $item->get_quantity(),
					'weight_in_g'         => (int) ceil( (float) wc_get_weight( $item->get_weight() * $item->get_quantity(), 'g', 'kg' ) ),
					'single_weight_in_kg' => $item->get_weight(),
					'single_weight_in_g'  => (int) ceil( (float) wc_get_weight( $item->get_weight(), 'g', 'kg' ) ),
					'gross_weight_in_kg'  => $item_gross_weight,
					'gross_weight_in_g'   => (int) ceil( (float) wc_get_weight( $item_gross_weight, 'g', 'kg' ) ),
					'single_value'        => $item->get_price(),
					'value'               => $item->get_total(),
				);
			}

			$item_description                        = self::encode_customs_str( $item_description, $max_desc_length );
			$customs_data['export_type_description'] = $item_description;

			self::restore_locale();
		}

		return $customs_data;
	}

	public static function item_meta_shortcode_format( $format, $meta_type ) {
		if ( in_array( $meta_type, array( 'weight' ), true ) ) {
			$format = 'weight';
		} elseif ( 'manufacture_country' === $meta_type ) {
			$format = 'country';
		}

		return $format;
	}

	/**
	 * @param array $meta
	 * @param \Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\ProductItem|boolean $item
	 * @param CommercialInvoice $preview
	 *
	 * @return array
	 */
	public static function register_item_preview_meta( $meta, $item, $preview ) {
		$meta = array_merge(
			$meta,
			array(
				array(
					'title'   => _x( 'HS Code', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'preview' => $item ? $item->get_hs_code() : '',
					'icon'    => '',
					'type'    => 'hs_code',
				),
				array(
					'title'   => _x( 'MID Code', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'preview' => $item ? $item->get_mid_code() : '',
					'icon'    => '',
					'type'    => 'mid_code',
				),
				array(
					'title'   => _x( 'Manufacture Country', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'preview' => $item ? sab_format_country_name( $item->get_manufacture_country() ) : '',
					'icon'    => '',
					'type'    => 'manufacture_country',
				),
				array(
					'title'   => _x( 'Net weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					'preview' => $item ? $item->get_formatted_weight( $item->get_weight() ) : 0,
					'icon'    => '',
					'type'    => 'weight',
				),
			)
		);

		return $meta;
	}

	public static function register_default_template_path( $default_path, $template_name ) {
		/**
		 * Add default packing slip templates from plugin template path.
		 */
		if ( strpos( $template_name, 'commercial-invoice/' ) !== false ) {
			$default_path = trailingslashit( WC_germanized_pro()->plugin_path() ) . 'templates/';
		}

		return $default_path;
	}

	public static function register_rest_controllers( $controllers ) {
		$controllers['sab/v1']['commercial_invoices'] = '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Controller';

		return $controllers;
	}

	public static function number_placeholder( $placeholder, $document_type ) {
		if ( 'commercial_invoice' === $document_type ) {
			$placeholder['{shipment_number}'] = _x( 'Shipment number (e.g. 1234)', 'commercial-invoice', 'woocommerce-germanized-pro' );
			$placeholder['{order_number}']    = _x( 'Order number (e.g. 1234)', 'commercial-invoice', 'woocommerce-germanized-pro' );
		}

		return $placeholder;
	}

	public static function register_shortcode_handler() {
		return '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Shortcodes';
	}

	public static function register_template( $templates ) {
		$templates['default'] = '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\DefaultTemplate';

		return $templates;
	}

	public static function register_data_store( $stores ) {
		return array_merge(
			$stores,
			array(
				'commercial_invoice' => '\Vendidero\Germanized\Pro\StoreaBill\DataStores\CommercialInvoice',
			)
		);
	}

	public static function cancel_deferred_sync( $shipment_id ) {
		$queue = WC()->queue();

		/**
		 * Cancel outstanding events.
		 */
		$queue->cancel_all( 'woocommerce_gzdp_commercial_invoice_auto_sync_callback', array( $shipment_id ), 'woocommerce-gzdp-commercial-invoice-sync' );
	}

	public static function auto_sync_callback( $shipment_id ) {
		self::cancel_deferred_sync( $shipment_id );

		CacheHelper::prevent_caching( 'automation' );

		if ( $shipment = self::get_shipment( $shipment_id ) ) {
			self::sync_commercial_invoice( $shipment->get_shipment(), array(), true, true );
		}
	}

	public static function delete_commercial_invoice( $shipment_id ) {
		if ( $syncable_shipment = self::get_shipment( $shipment_id ) ) {
			if ( $commercial_invoice = $syncable_shipment->get_commercial_invoice() ) {
				$commercial_invoice->delete( true );
			}
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public static function maybe_sync_commercial_invoice( $shipment ) {
		/**
		 * Woo multilingual seems to have a weird way of
		 * saving/updating order item languages when opening the orders screen.
		 * This might lead to (unnecessary) sync calls within admin.
		 */
		if ( ( WPML::is_active() && is_admin() ) || apply_filters( 'woocommerce_gzdp_disable_auto_commercial_invoice_sync', false ) ) {
			return false;
		}

		if ( $syncable_shipment = self::get_shipment( $shipment ) ) {
			$commercial_invoice = $syncable_shipment->get_commercial_invoice();

			if ( ! $commercial_invoice ) {
				return false;
			} else {
				return self::sync_commercial_invoice( $shipment, array(), true, true );
			}
		}

		return false;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public static function sync_commercial_invoice( $shipment, $args = array(), $render = true, $defer = false ) {
		$result = new \WP_Error( 'commercial-invoice-error', __( 'Error while generating commercial invoice.', 'woocommerce-germanized-pro' ) );

		if ( $syncable_shipment = self::get_shipment( $shipment ) ) {
			$commercial_invoice = $syncable_shipment->get_commercial_invoice();

			if ( ! $commercial_invoice ) {
				$commercial_invoice = wc_gzdp_get_commercial_invoice( 0 );
			}

			if ( ! empty( $args ) ) {
				$commercial_invoice->set_created_via( 'manual' );
			}

			$syncable_shipment->sync( $commercial_invoice, $args );

			if ( $render ) {
				$result = $commercial_invoice->save();

				if ( ! is_wp_error( $result ) ) {
					if ( $defer && sab_allow_deferring( 'render_commercial_invoice' ) ) {
						$result = $commercial_invoice->render_deferred();
					} else {
						$result = $commercial_invoice->render();
					}
				}
			} else {
				$result = $commercial_invoice->save();
			}
		}

		if ( is_wp_error( $result ) || false === $result ) {
			return $result;
		} else {
			return true;
		}
	}

	/**
	 * @param $shipment
	 *
	 * @return false|Shipment
	 */
	public static function get_shipment( $shipment ) {
		try {
			$syncable_shipment = new Shipment( $shipment );
		} catch ( \Exception $e ) {
			$syncable_shipment = false;
		}

		return $syncable_shipment;
	}

	/**
	 * @param $shipment
	 *
	 * @return bool|CommercialInvoice
	 */
	public static function get_commercial_invoice( $shipment ) {
		if ( $syncable_shipment = self::get_shipment( $shipment ) ) {
			return $syncable_shipment->get_commercial_invoice();
		}

		return false;
	}

	public static function register_document_items( $classname, $item_type, $item_id ) {
		if ( 'customs_product' === $item_type ) {
			$classname = '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\ProductItem';
		}

		return $classname;
	}

	public static function register_document_type() {
		sab_register_document_type(
			'commercial_invoice',
			array(
				'group'                     => 'customs',
				'api_endpoint'              => 'commercial_invoices',
				'labels'                    => array(
					'singular' => __( 'Commercial Invoice', 'woocommerce-germanized-pro' ),
					'plural'   => __( 'Commercial Invoices', 'woocommerce-germanized-pro' ),
				),
				'class_name'                => '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice',
				'admin_email_class_name'    => '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\Email',
				'preview_class_name'        => '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\Preview',
				'default_line_item_types'   => array( 'product' ),
				'main_line_item_types'      => array( 'product' ),
				'default_status'            => 'closed',
				'available_line_item_types' => array( 'product' ),
				'supports'                  => array( 'items', 'item_totals', 'totals' ),
				'barcode_code_types'        => array(
					'document?data=order_number'    => __( 'Order number', 'woocommerce-germanized-pro' ),
					'document?data=shipment_number' => __( 'Shipment number', 'woocommerce-germanized-pro' ),
				),
				'total_types'               => array(
					'total'            => array(
						'title' => _x( 'Total', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Total', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'discount'         => array(
						'title' => _x( 'Discount', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Discount', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'product'          => array(
						'title' => _x( 'Product', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Product', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'fee'              => array(
						'title' => _x( 'Fee', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Fee', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'shipping'         => array(
						'title' => _x( 'Shipping', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Shipping', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'insurance'        => array(
						'title' => _x( 'Insurance', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Insurance', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'weight'           => array(
						'title' => _x( 'Weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'net_weight'       => array(
						'title' => _x( 'Net weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Net weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'packaging_weight' => array(
						'title' => _x( 'Packaging weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Packaging weight', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'length'           => array(
						'title' => _x( 'Length', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Length', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'width'            => array(
						'title' => _x( 'Width', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Width', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'height'           => array(
						'title' => _x( 'Height', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Height', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
					'item_count'       => array(
						'title' => _x( 'Item count', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						'desc'  => _x( 'Item count', 'commercial-invoice', 'woocommerce-germanized-pro' ),
					),
				),
				'shortcodes'                => array(
					'document' => array(
						array(
							'shortcode' => 'document?data=order_number',
							'title'     => _x( 'Order number', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
						array(
							'shortcode' => 'document?data=shipment_number',
							'title'     => _x( 'Shipment number', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
						array(
							'shortcode' => 'document?data=tracking_id',
							'title'     => _x( 'Shipment tracking number', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
						array(
							'shortcode' => 'document?data=shipping_provider_title',
							'title'     => _x( 'Shipping Provider', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
						array(
							'shortcode' => 'document?data=formatted_incoterms',
							'title'     => _x( 'Incoterms', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
						array(
							'shortcode' => 'document?data=formatted_export_type',
							'title'     => _x( 'Type of Export', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
						array(
							'shortcode' => 'document?data=formatted_export_reason',
							'title'     => _x( 'Reason for Export', 'commercial-invoice', 'woocommerce-germanized-pro' ),
						),
					),
				),
				'additional_blocks'         => array(
					'storeabill/shipping-address',
					'storeabill/sender-address',
					'storeabill/preferential-origin-declaration',
				),
			)
		);
	}

	public static function register_settings( $settings, $section = '' ) {
		if ( 'commercial_invoices' === $section ) {
			$settings = self::get_commercial_invoices_settings();
		}

		return $settings;
	}

	protected static function get_commercial_invoices_settings() {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'commercial_invoice_settings',
			),

			array(
				'title'   => __( 'Automation', 'woocommerce-germanized-pro' ),
				'desc'    => _x( 'Automatically create commercial invoices to shipments if needed.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'id'      => 'woocommerce_gzdp_commercial_invoice_auto',
				'default' => 'yes',
				'type'    => 'sab_toggle',
			),

			array(
				'title'             => __( 'Shipment status(es)', 'woocommerce-germanized-pro' ),
				'desc'              => '<div class="sab-additional-desc">' . sprintf( _x( 'Select one or more shipment statuses. A commercial invoice is generated as soon as a shipment reaches one of the statuses selected.', 'commercial-invoice', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id'                => 'woocommerce_gzdp_commercial_invoice_auto_statuses',
				'default'           => array( 'processing', 'shipped' ),
				'type'              => 'multiselect',
				'class'             => 'sab-enhanced-select',
				'options'           => function_exists( 'wc_stc_get_shipment_statuses' ) ? wc_stc_get_shipment_statuses() : array(),
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_commercial_invoice_auto' => '',
				),
			),

			array(
				'title'    => _x( 'Incoterms', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'Select default Incoterms to be applied for a new commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'id'       => 'woocommerce_gzdp_commercial_invoice_default_incoterms',
				'default'  => 'DAP',
				'type'     => 'select',
				'class'    => 'sab-enhanced-select',
				'options'  => self::get_available_incoterms(),
			),

			array(
				'title'    => _x( 'Export reason', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'Select a default export reason to be applied for a new commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'id'       => 'woocommerce_gzdp_commercial_invoice_default_export_reason',
				'default'  => 'sale',
				'type'     => 'select',
				'class'    => 'sab-enhanced-select',
				'options'  => self::get_available_export_reasons(),
			),

			array(
				'title'    => _x( 'Export type', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'desc_tip' => _x( 'Select a default export type to be applied for a new commercial invoice.', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'id'       => 'woocommerce_gzdp_commercial_invoice_default_export_type',
				'default'  => 'permanent',
				'type'     => 'select',
				'class'    => 'sab-enhanced-select',
				'options'  => self::get_available_export_types(),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'commercial_invoice_settings',
			),

			array(
				'title' => __( 'Layout', 'woocommerce-germanized-pro' ),
				'desc'  => sprintf( __( 'Manage your %1$s templates by using the visual editor <a href="%2$s" class="button button-secondary">Learn more</a>', 'woocommerce-germanized-pro' ), sab_get_document_type_label( 'commercial_invoice' ), esc_url( AccountingHelper::template_help_link() ) ),
				'type'  => 'title',
				'id'    => 'commercial_invoice_layout_settings',
			),

			array(
				'type'          => 'sab_document_templates',
				'document_type' => 'commercial_invoice',
				'title'         => __( 'Manage template', 'woocommerce-germanized-pro' ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'commercial_invoice_layout_settings',
			),
		);

		$settings = array_merge( $settings, Settings::get_numbering_options( 'commercial_invoice' ) );

		return $settings;
	}

	public static function register_setting_sections( $sections ) {
		if ( ! \Vendidero\Germanized\Pro\Shiptastic\Helper::is_active() ) {
			return $sections;
		}

		$sections['commercial_invoices'] = __( 'Commercial Invoices', 'woocommerce-germanized-pro' );

		return $sections;
	}

	protected static function get_shipments_by_order( $order ) {
		return function_exists( 'wc_stc_get_shipments_by_order' ) ? wc_stc_get_shipments_by_order( $order ) : array();
	}

	/**
	 * @param $actions
	 * @param \WC_Order $order
	 */
	public static function order_download_actions( $actions, $order ) {
		$shipments = self::get_shipments_by_order( $order );

		foreach ( $shipments as $shipment ) {
			if ( $commercial_invoice = wc_gzdp_get_commercial_invoice_by_shipment( $shipment ) ) {
				$actions[ "download-commercial-invoice-{$commercial_invoice->get_id()}" ] = array(
					'url'    => $commercial_invoice->get_download_url(),
					'name'   => sprintf( __( 'Download %s', 'woocommerce-germanized-pro' ), $commercial_invoice->get_title() ),
					'action' => 'download',
				);
			}
		}

		return $actions;
	}

	public static function bulk_action( $actions ) {
		$actions['commercial_invoices'] = _x( 'Generate and download commercial invoices', 'commercial-invoice', 'woocommerce-germanized-pro' );

		return $actions;
	}

	public static function download_action( $actions, $shipment ) {
		if ( $commercial_invoice = wc_gzdp_get_commercial_invoice_by_shipment( $shipment ) ) {
			$actions['download_commercial_invoice'] = array(
				'url'    => $commercial_invoice->get_download_url(),
				'name'   => sprintf( __( 'Download %s', 'woocommerce-germanized-pro' ), $commercial_invoice->get_title() ),
				'action' => 'download-commercial-invoice download',
				'target' => '_blank',
			);
		} elseif ( self::shipment_needs_commercial_invoice( $shipment ) ) {
			$actions['generate_commercial_invoice'] = array(
				'url'               => '#',
				'name'              => _x( 'Generate commercial invoice', 'commercial-invoice', 'woocommerce-germanized-pro' ),
				'action'            => 'generate-commercial-invoice has-shipment-modal generate',
				'custom_attributes' => array(
					'id'                => 'wc-gzdp-create-commercial-invoice-' . $shipment->get_id(),
					'data-id'           => 'wc-gzdp-modal-create-commercial-invoice',
					'data-load-async'   => true,
					'data-reference'    => $shipment->get_id(),
					'data-nonce-params' => 'wc_gzdp_admin_shipments_table_modal_params',
				),
			);

			include WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-commercial-invoice-backbone.php';
		}

		return $actions;
	}

	public static function download_bulk_export() {
		if ( isset( $_GET['action'], $_REQUEST['_wpnonce'] ) && 'wc-gzdp-download-commercial-invoice-export' === $_GET['action'] && wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'wc-gzdp-download-commercial-invoices' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( current_user_can( 'read_commercial_invoice' ) ) {
				$handler = new BulkHandler();

				if ( ( $file = $handler->get_file() ) && file_exists( $file ) ) {
					if ( ! isset( $_GET['force'] ) || 'no' === $_GET['force'] ) {
						$download_method = 'inline';
					} else {
						$download_method = 'force';
					}

					// Trigger download via one of the methods.
					do_action( 'storeabill_download_file_' . $download_method, $file, 'bulk.pdf' );
				}
			}
		}
	}

	public static function register_bulk_handler( $handlers ) {
		$handlers['commercial_invoices'] = '\Vendidero\Germanized\Pro\StoreaBill\CommercialInvoice\BulkHandler';

		return $handlers;
	}
}
