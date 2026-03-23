<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class       WC_GZD_WPML_Helper
 * @category    Class
 * @author      vendidero
 */
class WC_GZDP_WPML_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		if ( ! $this->is_activated() ) {
			return;
		}

		add_action( 'init', array( $this, 'init' ), 10 );
	}

	public function init() {
		add_action( 'woocommerce_gzd_reload_locale', array( $this, 'reload_locale' ) );
		add_action( 'storeabill_reload_locale', array( $this, 'reload_locale' ) );
		add_filter( 'woocommerce_gzdp_legal_page_ids', array( $this, 'translate_legal_page_ids' ) );
		add_filter( 'storeabill_wpml_render_language', array( $this, 'switch_legal_page_render_language' ), 10, 2 );
		add_filter( 'woocommerce_gzd_wpml_admin_relevant_string_options', array( $this, 'register_admin_string_options' ), 10, 1 );
		add_filter( 'woocommerce_gzd_wpml_admin_order_items_translatable_actions', array( $this, 'translatable_actions' ) );
		add_filter( 'woocommerce_gzd_wpml_email_ids', array( $this, 'register_emails' ) );

		// Multistep step name refresh after init
		$this->refresh_step_names();
	}

	public function register_emails( $emails ) {
		$emails = array_merge(
			$emails,
			array(
				'WC_GZDP_Email_Customer_Order_Confirmation' => 'customer_order_confirmation',
			)
		);

		return $emails;
	}

	public function translatable_actions( $actions ) {
		return array_merge( $actions, array( 'order_acceptance' ) );
	}

	public function register_admin_string_options( $options ) {
		return array_merge(
			$options,
			array(
				'woocommerce_gzdp_contract_helper_email_order_processing_text',
				'woocommerce_gzdp_legal_page_revocation_pdf',
				'woocommerce_gzdp_legal_page_terms_pdf',
				'woocommerce_gzdp_legal_page_data_security_pdf',
				'woocommerce_gzdp_legal_page_imprint_pdf',
			)
		);
	}

	/**
	 * @param string $lang
	 * @param \Vendidero\StoreaBill\Document\Document $document
	 *
	 * @return string
	 */
	public function switch_legal_page_render_language( $lang, $document ) {
		if ( 'post_document' === $document->get_type() ) {
			if ( $post = $document->get_reference() ) {
				$language_details = apply_filters( 'wpml_post_language_details', null, $post->ID );

				if ( isset( $language_details['language_code'] ) ) {
					$lang = $language_details['language_code'];
				}
			}
		}

		return $lang;
	}

	public function translate_legal_page_ids( $ids ) {
		foreach ( $ids as $page => $id ) {
			$ids[ $page ] = apply_filters( 'translate_object_id', $id, 'page', true );
		}

		return $ids;
	}

	public function refresh_step_names() {
		if ( isset( WC_germanized_pro()->multistep_checkout ) ) {

			$step_names = WC_germanized_pro()->multistep_checkout->get_step_names();
			$steps      = WC_germanized_pro()->multistep_checkout->steps;

			foreach ( $steps as $key => $step ) {
				$step->title = $step_names[ $step->id ];
			}
		}
	}

	public function reload_locale() {
		unload_textdomain( 'woocommerce-germanized-pro', true );

		WC_germanized_pro()->load_plugin_textdomain();
	}

	public function get_gzd_compatibility() {
		$gzd = WC_germanized();

		if ( is_callable( array( $gzd, 'get_compatibility' ) ) ) {
			return $gzd->get_compatibility( 'wpml' );
		}

		return false;
	}

	public function is_activated() {
		return WC_GZDP_Dependencies::instance()->is_wpml_activated();
	}
}

return WC_GZDP_WPML_Helper::instance();
