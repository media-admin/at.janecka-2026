<?php
/**
 * Plugin Name: GLS for Shiptastic
 * Plugin URI: https://github.com/vendidero/gls-for-shiptastic
 * Description: GLS Integration for Shiptastic
 * Author: vendidero
 * Author URI: https://vendidero.de
 * Version: 1.2.3
 * Requires PHP: 5.6
 * License: GPLv3
 * Requires Plugins: shiptastic-for-woocommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WC_GLS_FOR_STC_IS_STANDALONE_PLUGIN' ) ) {
	define( 'WC_GLS_FOR_STC_IS_STANDALONE_PLUGIN', true );
}

if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
	return;
}

$autoloader = __DIR__ . '/vendor/autoload_packages.php';

if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	return;
}

register_activation_hook( __FILE__, array( '\Vendidero\Shiptastic\GLS\Package', 'install' ) );
add_action( 'plugins_loaded', array( '\Vendidero\Shiptastic\GLS\Package', 'init' ) );
