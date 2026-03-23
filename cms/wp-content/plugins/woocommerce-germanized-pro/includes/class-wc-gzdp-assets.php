<?php

class WC_GZDP_Assets {

	protected static $_instance = null;

	protected $localized_scripts = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'add_admin_assets' ), 15 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_scripts' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_frontend_styles' ), 21 );

		add_action( 'wp_print_scripts', array( $this, 'localize_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( $this, 'localize_scripts' ), 5 );
	}

	public function add_frontend_styles() {
		do_action( 'woocommerce_gzdp_frontend_styles', $this );
	}

	public function localize_scripts() {
		if ( wp_script_is( 'wc-gzdp-checkout' ) && ! in_array( 'wc-gzdp-checkout', $this->localized_scripts, true ) ) {
			$this->localized_scripts[] = 'wc-gzdp-checkout';

			wp_localize_script(
				'wc-gzdp-checkout',
				'wc_gzdp_checkout_params',
				apply_filters(
					'wc_gzdp_checkout_params',
					array(
						'ajax_url'                      => admin_url( 'admin-ajax.php' ),
						'vat_exempt_postcodes'          => Vendidero\StoreaBill\Countries::get_eu_vat_postcode_exemptions(),
						'great_britain_supports_vat_id' => wc_bool_to_string( WC_GZDP_VAT_Helper::instance()->country_supports_vat_id( 'GB' ) ),
						'supports_shipping_vat_id'      => apply_filters( 'woocommerce_gzdp_checkout_supports_shipping_vat_id', true ),
					)
				)
			);
		}
	}

	public function register_script( $handle, $path, $dep = array(), $ver = '', $in_footer = array( 'strategy' => 'defer' ) ) {
		global $wp_version;

		$gzdp = WC_germanized_pro();

		if ( version_compare( $wp_version, '6.3', '<' ) ) {
			$in_footer = true;
		}

		$ver = empty( $ver ) ? WC_GERMANIZED_PRO_VERSION : $ver;

		wp_register_script(
			$handle,
			$gzdp->get_assets_build_url( $path ),
			$dep,
			$ver,
			$in_footer
		);
	}

	public function add_frontend_scripts() {
		// Checkout general
		$this->register_script( 'wc-gzdp-checkout', 'static/checkout.js', array( 'wc-checkout' ) );

		if ( is_checkout() && 'yes' === get_option( 'woocommerce_gzdp_enable_vat_check' ) ) {
			wp_enqueue_script( 'wc-gzdp-checkout' );
		}

		do_action( 'woocommerce_gzdp_frontend_scripts', $this );
	}

	/**
	 * Helper function to determine whether the current screen is an order edit screen.
	 *
	 * @param string $screen_id Screen ID.
	 *
	 * @return bool Whether the current screen is an order edit screen.
	 */
	protected function is_order_meta_box_screen( $screen_id ) {
		return in_array( str_replace( 'edit-', '', $screen_id ), $this->get_order_screen_ids(), true );
	}

	public function get_order_screen_id() {
		return function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	}

	protected function get_order_screen_ids() {
		$screen_ids = array();

		foreach ( wc_get_order_types() as $type ) {
			$screen_ids[] = $type;
			$screen_ids[] = 'edit-' . $type;
		}

		$screen_ids[] = $this->get_order_screen_id();

		return array_filter( $screen_ids );
	}

	public function add_admin_assets() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$gzdp      = WC_germanized_pro();

		wp_register_style( 'wc-gzdp-admin', $gzdp->get_assets_build_url( 'static/wc-gzdp-admin.css' ), array(), WC_GERMANIZED_PRO_VERSION );
		wp_enqueue_style( 'wc-gzdp-admin' );

		wp_register_style( 'wc-gzdp-admin-setup-wizard', $gzdp->get_assets_build_url( 'static/wc-gzdp-admin-setup-wizard.css' ), array( 'wp-admin', 'dashicons', 'install' ), WC_GERMANIZED_PRO_VERSION );

		wp_register_script( 'wc-gzdp-admin-order', $gzdp->get_assets_build_url( 'static/admin-order.js' ), array( 'jquery' ), WC_GERMANIZED_PRO_VERSION, true );
		wp_register_script( 'wc-gzdp-admin-settings', $gzdp->get_assets_build_url( 'static/admin-settings.js' ), array( 'jquery' ), WC_GERMANIZED_PRO_VERSION, true );
		wp_register_script( 'wc-gzdp-admin-products', $gzdp->get_assets_build_url( 'static/admin-products.js' ), array( 'jquery' ), WC_GERMANIZED_PRO_VERSION, true );
		wp_register_script( 'wc-gzdp-admin-meta-boxes-order', $gzdp->get_assets_build_url( 'static/admin-meta-boxes-order.js' ), array( 'wc-admin-meta-boxes' ), WC_GERMANIZED_PRO_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter

		if ( \Vendidero\Germanized\Pro\Shiptastic\Helper::is_active() ) {
			wp_register_script( 'wc-gzdp-admin-shipment-documents', $gzdp->get_assets_build_url( 'static/admin-shipment-documents.js' ), array( 'wc-shiptastic-admin-shipment-modal' ), WC_GERMANIZED_PRO_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NotInFooter
		}

		if ( $this->is_order_meta_box_screen( $screen_id ) ) {
			wp_enqueue_script( 'wc-gzdp-admin-order' );
			wp_enqueue_script( 'wc-gzdp-admin-meta-boxes-order' );

			if ( \Vendidero\Germanized\Pro\Shiptastic\Helper::is_active() ) {
				wp_enqueue_script( 'wc-gzdp-admin-shipment-documents' );
			}

			wp_localize_script(
				'wc-gzdp-admin-shipment-documents',
				'wc_gzdp_admin_shipment_documents_params',
				array(
					'ajax_url'                             => admin_url( 'admin-ajax.php' ),
					'remove_packing_slip_nonce'            => wp_create_nonce( 'wc-gzdp-remove-packing-slip' ),
					'refresh_packing_slip_nonce'           => wp_create_nonce( 'wc-gzdp-refresh-packing-slip' ),
					'remove_commercial_invoice_nonce'      => wp_create_nonce( 'wc-gzdp-remove-commercial-invoice' ),
					'create_commercial_invoice_load_nonce' => wp_create_nonce( 'wc-gzdp-create-commercial-invoice-load' ),
					'create_commercial_invoice_submit_nonce' => wp_create_nonce( 'wc-gzdp-create-commercial-invoice-submit' ),
					'i18n_remove_commercial_invoice_notice' => __( 'Do you really want to delete the commercial invoice?', 'woocommerce-germanized-pro' ),
					'i18n_remove_packing_slip_notice'      => __( 'Do you really want to delete the packing slip?', 'woocommerce-germanized-pro' ),
					'i18n_create_packing_slip_enabled'     => __( 'Create new packing slip', 'woocommerce-germanized-pro' ),
					'i18n_create_packing_slip_disabled'    => __( 'Please save the shipment before creating a new packing slip', 'woocommerce-germanized-pro' ),
				)
			);
		} elseif ( 'woocommerce_page_wc-settings' === $screen_id ) {
			wp_enqueue_media();
			wp_localize_script(
				'wc-gzdp-admin-settings',
				'wc_gzdp_attachment_field',
				array(
					'title'    => _x( 'Choose Attachment', 'admin-field', 'woocommerce-germanized-pro' ),
					'insert'   => _x( 'Set attachment', 'admin-field', 'woocommerce-germanized-pro' ),
					'unset'    => _x( 'Unset attachment', 'admin-field', 'woocommerce-germanized-pro' ),
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);

			wp_localize_script(
				'wc-gzdp-admin-settings',
				'wc_gzdp',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);

			wp_enqueue_script( 'wc-gzdp-admin-settings' );
		}

		// Add modal nonce params to the main script
		if ( 'woocommerce_page_wc-gzd-shipments' === $screen_id || 'woocommerce_page_wc-gzd-return-shipments' === $screen_id ) {
			wp_localize_script(
				'wc-gzd-admin-shipments-table',
				'wc_gzdp_admin_shipments_table_modal_params',
				array(
					'create_commercial_invoice_load_nonce' => wp_create_nonce( 'wc-gzdp-create-commercial-invoice-load' ),
					'create_commercial_invoice_submit_nonce' => wp_create_nonce( 'wc-gzdp-create-commercial-invoice-submit' ),
				)
			);
		}

		if ( in_array( $screen_id, array( 'product' ), true ) ) {
			wp_enqueue_script( 'wc-gzdp-admin-products' );

			$nutrient_ref_values = array_flip( \Vendidero\Germanized\Pro\Food\Helper::get_nutrient_reference_values() );
			$nutrient_ref_values[ __( '100 g each', 'woocommerce-germanized-pro' ) ]  = '100g';
			$nutrient_ref_values[ __( '100 ml each', 'woocommerce-germanized-pro' ) ] = '100g';

			$nutrient_ref_regexes = array();

			foreach ( $nutrient_ref_values as $nutrient_ref_value => $ref_value ) {
				$nutrient_ref_regexes[ str_replace( array( ' g', ' ml' ), array( '(\s)?g', '(\s)?ml' ), $nutrient_ref_value ) ] = $ref_value;
			}

			$units = WC_germanized()->units->get_units();

			wp_localize_script(
				'wc-gzdp-admin-products',
				'wc_gzdp_admin_products_params',
				array(
					'i18n_nutrient_reference_values'       => $nutrient_ref_regexes,
					'i18n_nutrient_reference_values_regex' => '(' . implode( '|', array_keys( $nutrient_ref_regexes ) ) . ')',
					'i18n_nutrient_units_regex'            => '(' . implode( '|', $units ) . ')',
					'decimal_separator'                    => wc_get_price_decimal_separator(),
				)
			);
		}

		do_action( 'woocommerce_gzdp_admin_assets' );
	}
}

return WC_GZDP_Assets::instance();
