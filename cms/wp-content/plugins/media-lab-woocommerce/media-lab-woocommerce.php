<?php
/**
 * Plugin Name: Media Lab WooCommerce
 * Plugin URI:  https://www.media-lab.at
 * Description: WooCommerce integration for Media Lab Agency sites
 * Version:     2.2.0
 * Author:      Media Lab
 * Text Domain: media-lab-woocommerce
 */

if (!defined('ABSPATH')) exit;

// Version orientiert sich am media-lab-starter-kit-Stand (2.2.0), da hier
// synchronisiert wurde. Wishlist (inc/wishlist/) bewusst NICHT übernommen -
// TI WooCommerce Wishlist bleibt vorerst aktiv, siehe Merge-Notizen 08/2026.
define('MEDIA_LAB_WC_VERSION', '2.2.0');
define('MEDIA_LAB_WC_PATH', plugin_dir_path(__FILE__));
define('MEDIA_LAB_WC_URL', plugin_dir_url(__FILE__));

// Includes ohne WooCommerce-Abhängigkeit
require_once MEDIA_LAB_WC_PATH . 'inc/price-suffix.php';
require_once MEDIA_LAB_WC_PATH . 'inc/shop-products-per-page.php';
require_once MEDIA_LAB_WC_PATH . 'inc/shortcodes.php';
require_once MEDIA_LAB_WC_PATH . 'inc/ajax-search-wc.php';
require_once MEDIA_LAB_WC_PATH . 'inc/ajax-load-more-wc.php';

// WooCommerce Theme Support
add_action('after_setup_theme', function() {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
});

// Alle WooCommerce-abhängigen Includes erst nach plugins_loaded
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'WooCommerce' ) ) return;
    require_once MEDIA_LAB_WC_PATH . 'inc/enqueue.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/hpos-product-type-cache-fix.php';
    // ── Inquiry-Engine (gemeinsamer Kern für Cart-Anfrage & Konfigurator-Anfrage) ──
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-cpt.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-i18n.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-settings.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-mail.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-channels.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-inquiry-engine.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/inquiry/class-upload-cleanup.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/catalog-mode.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/configurator/class-configurator.php';
    // ── Medialab WooCommerce Filters ─────────────────────────────────────────
    require_once MEDIA_LAB_WC_PATH . 'inc/filters/filter-config.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/filters/acf-fields.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/filters/ajax-handlers.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/filters/filter-bar.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/filters/setup.php';
    require_once MEDIA_LAB_WC_PATH . 'inc/filters/admin-overview.php';
} );