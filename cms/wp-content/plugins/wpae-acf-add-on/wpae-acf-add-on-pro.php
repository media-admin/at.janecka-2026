<?php
/*
Plugin Name: WP All Export - ACF Export Add-On Pro
Plugin URI: http://www.wpallimport.com/
Description: Export ACF data from WordPress. Requires WP All Export Pro.
Version: 2.0.0
Author: Soflyy
*/

namespace soflyy\wpallexport;

use PMAE_Vendor\Soflyy\AddonInstaller\AddonInstaller;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin root dir with forward slashes as directory separator regardless of actual DIRECTORY_SEPARATOR value
 * @var string
 */
define( 'PMAE_PRO_ROOT_DIR', str_replace( '\\', '/', dirname( __FILE__ ) ) );
/**
 * Plugin root url for referencing static content
 * @var string
 */
define( 'PMAE_PRO_ROOT_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );

define( 'PMAE_PRO_VERSION', '2.0.0' );

// Load Composer autoloader.
require __DIR__ . '/vendor/autoload.php';

// Initialize the PMXE Addon Installer SDK for ACF Export Add-On Free.
$wpae_addon_installer = new AddonInstaller([
	'addon_name'           => 'WP All Export - ACF Export Add-On',
	'addon_slug'           => 'wp-all-export-csv-excel-xml-for-acf',
	'addon_author'         => 'Soflyy',
	'minimum_version'      => '1.0.1',
	'pro_plugin_name'      => 'WP All Export - ACF Export Add-On Pro',
	'pro_plugin_file'      => __FILE__,
	'textdomain'           => 'wp_all_export_acf_add_on',
	'version_constant'     => 'PMAE_VERSION',
	'edition_constant'     => 'PMAE_EDITION',
	'expected_edition'     => 'free',
	'free_plugin_file'     => 'wp-all-export-csv-excel-xml-for-acf.php',
	'disable_deactivation' => true,
	'operation_type'       => 'export',
]);
$wpae_addon_installer->install_addon_from_repository();

// Load hook files.
$actions_directory = __DIR__ . '/actions';

if (is_dir($actions_directory)) {

	$files = glob($actions_directory . '/*.php');

	foreach ($files as $file) {
		require_once $file;
		
		if(function_exists('pmaep_'.str_replace('.php', '', basename($file))) && !function_exists(str_replace('.php', '', basename($file)))) {
			add_action( str_replace( '.php', '', basename( $file ) ), 'pmaep_' . str_replace( '.php', '', basename( $file ) ), 10, 99 );
		}
	}
}

$filters_directory = __DIR__ . '/filters';

if (is_dir($filters_directory)) {

	$files = glob($filters_directory . '/*.php');

	foreach ($files as $file) {
		require_once $file;

		if(function_exists('pmaep_'.str_replace('.php', '', basename($file))) && !function_exists(str_replace('.php', '', basename($file)))) {
			add_filter( str_replace( '.php', '', basename( $file ) ), 'pmaep_' . str_replace( '.php', '', basename( $file ) ), 10, 99 );
		}
	}
}

require_once(__DIR__ . '/classes/AdvancedAcfFields.php');

// retrieve our license key from the DB
$wpae_acf_addon_options = get_option( 'PMXE_Plugin_Options' );

// Favor new API URL, but fallback to old if needed.
if ( ! empty( $wpae_acf_addon_options['info_api_url_new'] ) ) {
	$api_url = $wpae_acf_addon_options['info_api_url_new'];
} elseif ( ! empty( $wpae_acf_addon_options['info_api_url'] ) ) {
	$api_url = $wpae_acf_addon_options['info_api_url'];
} else {
	$api_url = null;
}

if ( ! empty( $api_url ) ) {
	require_once('classes/Updater.php');
	$updater = new Updater( $api_url, __FILE__, array(
			'version'   => PMAE_PRO_VERSION,        // current version number
			'license'   => false, // license key (used get_option above to retrieve from DB)
			'item_name' => 'ACF Export Add-On Pro',    // name of this plugin
			'author'    => 'Soflyy'  // author of this plugin
		)
	);
}