<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_GZD_Admin_Note' ) ) {
	include_once WC_GERMANIZED_ABSPATH . 'includes/admin/notes/class-wc-gzd-admin-note.php';
}

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZDP_Admin_Note_Refreshed_Theme_Shopmarks extends WC_GZD_Admin_Note {

	public function is_disabled() {
		$has_refreshed = 'yes' === get_option( 'woocommerce_gzdp_refreshed_theme_shopmarks', 'no' );

		if ( $has_refreshed && current_user_can( 'manage_woocommerce' ) ) {
			return parent::is_disabled();
		}

		return true;
	}

	public function enable_notices() {
		return true;
	}

	public function get_name() {
		return 'refreshed_theme_shopmarks';
	}

	public function get_title() {
		return __( 'Price labeling refreshed', 'woocommerce-germanized-pro' );
	}

	public function get_content() {
		return __( 'We\'ve automatically refreshed your price labeling settings as the last theme you\'ve used had special-purpose price labels available. Please review your settings.', 'woocommerce-germanized-pro' );
	}

	public function dismiss( $and_note = true ) {
		parent::dismiss( $and_note );

		delete_option( 'woocommerce_gzdp_refreshed_theme_shopmarks' );
	}

	public function get_actions() {
		$actions = array(
			array(
				'url'        => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks' ),
				'title'      => __( 'Review settings', 'woocommerce-germanized-pro' ),
				'target'     => '_self',
				'is_primary' => true,
			),
		);

		return $actions;
	}
}
