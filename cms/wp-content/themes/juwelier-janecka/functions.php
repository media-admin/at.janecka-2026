<?php
/**
 * Media Lab Theme - Custom Theme
 * 
 * Presentation layer only. Business logic in plugins.
 * 
 * @package Custom_Theme
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Theme version
define('CUSTOM_THEME_VERSION', '1.4.0');

/**
 * Check Required Plugins
 */
function customtheme_check_required_plugins() {
    $required_plugins = array(
        'media-lab-agency-core' => 'Media Lab Agency Core',
    );
    
    $missing_plugins = array();
    
    foreach ($required_plugins as $plugin_slug => $plugin_name) {
        if (!is_plugin_active($plugin_slug . '/' . $plugin_slug . '.php')) {
            $missing_plugins[] = $plugin_name;
        }
    }
    
    if (!empty($missing_plugins)) {
        add_action('admin_notices', function() use ($missing_plugins) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Custom Theme:</strong> The following plugins are recommended: ';
            echo implode(', ', $missing_plugins);
            echo '</p></div>';
        });
    }
}
add_action('after_setup_theme', 'customtheme_check_required_plugins');

/**
 * Theme Setup
 */
function customtheme_setup() {
    // Theme support
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support( 'align-wide' );
    
    // Navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'custom-theme'),
        'footer' => __('Footer Menu', 'custom-theme'),
        'footer-legal' => __('Footer Legal', 'custom-theme'),
    ));
    
    // Image sizes
    add_image_size('custom-thumbnail', 400, 300, true);
    add_image_size('custom-medium', 800, 600, true);
    add_image_size('custom-large', 1200, 900, true);
}
add_action('after_setup_theme', 'customtheme_setup');

/**
 * Load Theme Components
 */
require_once get_template_directory() . '/inc/enqueue.php';
require_once get_template_directory() . '/inc/performance.php';
require_once get_template_directory() . '/inc/shortcode-overrides.php';
require_once get_template_directory() . '/inc/class-mega-menu-walker.php';
require_once get_template_directory() . '/inc/shortcode-booking-button.php';
require_once get_template_directory() . '/inc/brands/brands-setup.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/category-archive-header.php';
require_once get_template_directory() . '/inc/brands/brands-shortcodes.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/hooks-eheringe.php';
require_once get_stylesheet_directory() . '/inc/blocks/featured-products.php';


// Optional components (only if files exist)
$optional_components = array(
    'walker-nav-menu.php',
    'helpers.php',
    'acf-welcome.php',   // ACF-Felder: Welcome Page
    'welcome-mode.php',  // Weiterleitung: Welcome Mode (auskommentieren zum Deaktivieren)
);

foreach ($optional_components as $component) {
    $file = get_template_directory() . '/inc/' . $component;
    if (file_exists($file)) {
        require_once $file;
    }
}

/**
 * Theme Customizations
 */

// Customize excerpt length
add_filter('excerpt_length', function($length) {
    return 20;
});

// Customize excerpt more
add_filter('excerpt_more', function($more) {
    return '...';
});

/**
 * WooCommerce Support (if WooCommerce is active)
 */
if (class_exists('WooCommerce')) {
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}

// =============================================================================
// Toggle Helper
// =============================================================================
if ( ! function_exists('medialab_toggle') ) {
    /**
     * Gibt ein Toggle-Element aus.
     *
     * @param string      $id      – Eindeutige ID (für aria-labelledby etc.)
     * @param bool|string $state   – true/'on' | false/'off' | 'unavailable'
     * @param string      $label   – Optionaler Label-Text
     * @param array       $args    – Zusätzliche Argumente:
     *                               'size'    => 'sm' | '' | 'lg'
     *                               'class'   => zusätzliche CSS-Klassen
     *                               'stacked' => bool
     */
    function medialab_toggle( string $id, $state = 'off', string $label = '', array $args = [] ) : void {
        // State normalisieren
        if ( $state === true  ) $state = 'on';
        if ( $state === false ) $state = 'off';
        if ( ! in_array( $state, [ 'on', 'off', 'unavailable' ], true ) ) $state = 'off';

        $size    = isset( $args['size'] ) ? sanitize_html_class( $args['size'] ) : '';
        $extra   = isset( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';
        $stacked = ! empty( $args['stacked'] );

        $classes = 'toggle';
        if ( $size )    $classes .= ' toggle--' . $size;
        if ( $stacked ) $classes .= ' toggle--stacked';
        $classes .= $extra;

        $aria_pressed  = $state === 'on' ? 'true' : 'false';
        $aria_disabled = $state === 'unavailable' ? ' aria-disabled="true"' : '';
        $tabindex      = $state === 'unavailable' ? ' tabindex="-1"' : '';
        $role          = $state !== 'unavailable' ? ' role="switch" aria-pressed="' . esc_attr( $aria_pressed ) . '"' : '';
        ?>
        <button
            id="<?php echo esc_attr( $id ); ?>"
            class="<?php echo esc_attr( $classes ); ?>"
            data-toggle="<?php echo esc_attr( $state ); ?>"
            <?php echo $role; // already escaped ?>
            <?php echo $aria_disabled; // already escaped ?>
            <?php echo $tabindex; // already escaped ?>
            type="button"
        >
            <span class="toggle__track" aria-hidden="true">
                <span class="toggle__thumb"></span>
            </span>
            <?php if ( $label ) : ?>
                <span class="toggle__label"><?php echo esc_html( $label ); ?></span>
            <?php endif; ?>
        </button>
        <?php
    }
}

// WooCommerce nur laden wenn aktiv
if ( class_exists( 'WooCommerce' ) ) {


	// Theme-Support, Bild-Größen, Scripts
	require_once get_stylesheet_directory() . '/inc/woocommerce/setup.php';

	// Archiv-/Shop-Hooks (Produkt-Karte, Grid, Layout)
	require_once get_stylesheet_directory() . '/inc/woocommerce/hooks-archive.php';

	// Einzelprodukt-Hooks (Galerie, Summary, Tabs, Schema)
	require_once get_stylesheet_directory() . '/inc/woocommerce/hooks-single.php';
}



// API-Key als Konstante in wp-config.php definieren:
//   define( 'GOOGLE_MAPS_API_KEY', 'AIza...' );
add_action( 'wp_enqueue_scripts', 'janecka_enqueue_google_maps' );

function janecka_enqueue_google_maps() {

	if ( ! is_singular( 'stores' ) ) {
		return;
	}

	$api_key = defined( 'GOOGLE_MAPS_API_KEY' ) ? GOOGLE_MAPS_API_KEY : '';

	if ( empty( $api_key ) ) {
		return;
	}

	wp_enqueue_script(
		'google-maps',
		'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=marker',
		[],
		null,
		true
	);

	wp_enqueue_script(
		'janecka-store-map',
		get_template_directory_uri() . '/assets/dist/js/store-map.js',
		[ 'google-maps' ],
		wp_get_theme()->get( 'Version' ),
		true
	);
}


add_filter( 'script_loader_tag', 'janecka_add_async_to_google_maps', 10, 2 );

function janecka_add_async_to_google_maps( $tag, $handle ) {
    if ( 'google-maps' !== $handle ) {
        return $tag;
    }
    return str_replace( '<script ', '<script async ', $tag );
}


// booking-modal.js auch auf Store-Singular-Seiten laden
add_action( 'wp_enqueue_scripts', 'janecka_enqueue_store_booking_modal' );

function janecka_enqueue_store_booking_modal() {
    if ( ! is_singular( 'stores' ) ) return;
    janecka_enqueue_booking_modal_assets();
}



// ── Produktkategorie-URLs ohne Präfix ─────────────────────────────────────────
// Ziel: /schmuck/, /schmuck/halsschmuck/ statt /kategorien/schmuck/

add_action( 'init', 'janecka_register_category_rewrites' );

function janecka_register_category_rewrites() {
    $categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ] );

    if ( empty( $categories ) || is_wp_error( $categories ) ) return;

    foreach ( $categories as $term ) {
        $ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
        $path = '';
        foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
            $ancestor = get_term( $ancestor_id, 'product_cat' );
            if ( ! $ancestor || is_wp_error( $ancestor ) ) continue;
            $path .= $ancestor->slug . '/';
        }
        $path .= $term->slug;

        add_rewrite_rule(
            '^' . $path . '/?$',
            'index.php?product_cat=' . $term->slug,
            'top' // Kategorie gewinnt vor gleichnamigen WordPress-Seiten
        );
    }
}

// Kategorie-Links korrigieren
add_filter( 'term_link', 'janecka_fix_category_link', 10, 3 );

function janecka_fix_category_link( $link, $term, $taxonomy ) {
    if ( $taxonomy !== 'product_cat' ) return $link;

    $ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
    $path = '';
    foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
        $ancestor = get_term( $ancestor_id, 'product_cat' );
        if ( ! $ancestor || is_wp_error( $ancestor ) ) continue;
        $path .= $ancestor->slug . '/';
    }
    $path .= $term->slug . '/';

    return home_url( '/' . $path );
}



// Breadcrumb auf normalen WordPress-Seiten (page.php) anzeigen
add_action( 'woocommerce_before_main_content', function() {
    if ( is_page() && function_exists( 'woocommerce_breadcrumb' ) ) {
        woocommerce_breadcrumb();
    }
}, 5 );



// ── WooCommerce Login-Seite: Überschriften anpassen ──────────────────────────

add_filter( 'woocommerce_login_form_start', function() {
    // Wird vor dem Login-Formular ausgegeben — Heading wird per CSS überschrieben
} );

// Überschriften-Texte filtern
add_filter( 'gettext', 'janecka_wc_account_strings', 20, 3 );

function janecka_wc_account_strings( $translated, $original, $domain ) {
    if ( $domain !== 'woocommerce' ) return $translated;

    $replacements = [
        'Login'    => 'Anmelden',
        'Register' => 'Neues Kundenkonto',
    ];

    return $replacements[ $original ] ?? $translated;
}

// Präfix ("Kategorie:", "Schlagwort:" etc.) aus Archiv-Titeln entfernen
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

add_action( 'wp_head', function() {
    echo '<style>.tinvwl_add_to_wishlist_button{opacity:1!important;visibility:visible!important}</style>';
}, 999 );


add_action( 'woocommerce_after_shop_loop_item', function() {
    global $wp_filter;
    if ( isset( $wp_filter['woocommerce_after_shop_loop_item'] ) ) {
        foreach ( $wp_filter['woocommerce_after_shop_loop_item']->callbacks as $priority => $callbacks ) {
            foreach ( $callbacks as $key => $callback ) {
                $name = is_array( $callback['function'] )
                    ? ( is_object( $callback['function'][0] )
                        ? get_class( $callback['function'][0] )
                        : $callback['function'][0] ) . '::' . $callback['function'][1]
                    : ( is_string( $callback['function'] ) ? $callback['function'] : 'closure' );
                error_log( 'after_shop_loop_item | priority: ' . $priority . ' | ' . $name );
            }
        }
    }
}, 1 );


// In der Uhren-Kategorie die zweite MwSt.-Klasse und GZD entfernen
add_action( 'wp', function() {
    if ( ! is_product_category( 'uhren' ) ) return;
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_loop_tax_info', 6 );
    remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_gzd_template_loop_shipping_costs_info', 7 );
} );


// Dark Mode deaktivieren — Theme unterstützt nur Light Mode
add_action( 'wp_head', function() {
    echo '<meta name="color-scheme" content="light">' . "\n";
}, 1 );


// ── WooCommerce Pagination – Scroll to Grid ───────────────────────────────────
add_action( 'wp_footer', function() {
    $page = 1;
    if ( preg_match( '#/page/(\d+)/#', $_SERVER['REQUEST_URI'] ?? '', $m ) ) {
        $page = (int) $m[1];
    }
    if ( $page <= 1 ) return;
    ?>
    <script>
    ( function() {
        function scrollToGrid() {
            var grid = document.querySelector( '.wc-products-container' );
            if ( ! grid ) return;
            var header = document.querySelector( '.site-header' );
            var offset = ( header ? header.offsetHeight : 100 ) + 16;
            window.scrollTo( {
                top:      grid.getBoundingClientRect().top + window.scrollY - offset,
                behavior: 'smooth'
            } );
        }
        if ( document.readyState === 'complete' ) {
            setTimeout( scrollToGrid, 300 );
        } else {
            window.addEventListener( 'load', function() {
                setTimeout( scrollToGrid, 300 );
            } );
        }
    } )();
    </script>
    <?php
}, 99 );