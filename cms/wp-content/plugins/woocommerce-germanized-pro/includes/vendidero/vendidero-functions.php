<?php
/**
 * Vendidero Functions
 *
 * Functions to enable automatic updates
 *
 * @author      Vendidero
 * @version     1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vendidero_register_product' ) ) {
	function vendidero_register_product( $file, $product_id ) {
		$plugin             = new stdClass();
		$plugin->file       = $file;
		$plugin->product_id = $product_id;

		return $plugin;
	}
}

if ( ! function_exists( 'vendidero_helper_activated' ) ) {
	function vendidero_helper_activated() {
		return true;
	}
}

if ( ! vendidero_helper_activated() && ! function_exists( 'vendidero_helper_install' ) ) {
	function vendidero_helper_install( $api, $action, $args ) {
		return false;
	}
}

if ( ! vendidero_helper_activated() && ! function_exists( 'vendidero_helper_notice' ) ) {
	function vendidero_helper_notice( $inline = false ) {
		return false;
	}
}
