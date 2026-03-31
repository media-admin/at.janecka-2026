<?php
/**
 * Welcome Mode – Weiterleitung
 *
 * Leitet alle Besucher auf die Welcome Page weiter, solange der
 * Welcome Mode aktiv ist. Ausgenommen:
 *   - Eingeloggte Admins / Editoren
 *   - Die Welcome Page selbst
 *   - Footer-Seiten (Impressum, Datenschutz) via Konfiguration
 *   - WordPress-interne Routen (wp-login, wp-admin, REST API, cron)
 *
 * ── Aktivierung ───────────────────────────────────────────────────────────
 * In wp-config.php oder functions.php folgende Konstante setzen:
 *
 *   define( 'WELCOME_MODE', true );
 *
 * Oder direkt hier WELCOME_MODE_ACTIVE auf true setzen.
 *
 * ── Welcome Page ID ───────────────────────────────────────────────────────
 * WELCOME_PAGE_ID auf die ID der erstellten Seite setzen.
 * Alternativ: Slug-basiert über WELCOME_PAGE_SLUG.
 *
 * @package Custom_Theme
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// KONFIGURATION – hier anpassen
// =============================================================================

// Welcome Mode aktiv? Auch via wp-config.php: define('WELCOME_MODE', true);
if ( ! defined( 'WELCOME_MODE' ) ) {
    define( 'WELCOME_MODE', true );
}

// ID der Welcome Page (nach Erstellung im Backend hier eintragen)
if ( ! defined( 'WELCOME_PAGE_ID' ) ) {
    define( 'WELCOME_PAGE_ID', 0 ); // 0 = noch nicht gesetzt, via Slug ermitteln
}

// Slug der Welcome Page (Fallback wenn ID noch nicht bekannt)
if ( ! defined( 'WELCOME_PAGE_SLUG' ) ) {
    define( 'WELCOME_PAGE_SLUG', 'welcome' );
}

// Slugs der Footer-Seiten die trotzdem erreichbar sein sollen
// (Impressum, Datenschutz, etc.)
if ( ! defined( 'WELCOME_ALLOWED_SLUGS' ) ) {
    define( 'WELCOME_ALLOWED_SLUGS', serialize( [
        'impressum',
        'datenschutz',
        'datenschutzerklaerung',
        'privacy-policy',
        'legal-notice',
    ] ) );
}

// =============================================================================
// REDIRECT-LOGIK
// =============================================================================

add_action( 'template_redirect', 'customtheme_welcome_mode_redirect', 1 );

function customtheme_welcome_mode_redirect(): void {

    // Welcome Mode inaktiv → nichts tun
    if ( ! WELCOME_MODE ) return;

    // WordPress-interne Routen durchlassen
    if ( is_admin() )              return;
    if ( wp_doing_ajax() )         return;
    if ( wp_doing_cron() )         return;
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) return;
    if ( isset( $_SERVER['SCRIPT_NAME'] ) && strpos( $_SERVER['SCRIPT_NAME'], 'wp-login.php' ) !== false ) return;

    // Eingeloggte Nutzer mit Bearbeitungsrechten durchlassen (Admins, Editoren)
    if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) return;

    // Welcome Page selbst: kein Loop
    $welcome_page = customtheme_get_welcome_page();
    if ( $welcome_page && is_page( $welcome_page->ID ) ) return;

    // Erlaubte Seiten (Impressum, Datenschutz, etc.) durchlassen
    $allowed_slugs = unserialize( WELCOME_ALLOWED_SLUGS );
    if ( is_page() ) {
        global $post;
        if ( $post && in_array( $post->post_name, $allowed_slugs, true ) ) return;
    }

    // Feed, Sitemap, Robots.txt durchlassen
    if ( is_feed() )    return;
    if ( is_robots() )  return;
    if ( function_exists( 'is_sitemap' ) && is_sitemap() ) return;

    // Fetch-Requests vom Welcome Page Modal durchlassen (X-WPM-Request Header)
    if ( isset( $_SERVER['HTTP_X_WPM_REQUEST'] ) && $_SERVER['HTTP_X_WPM_REQUEST'] === '1' ) return;

    // ── Weiterleitung ───────────────────────────────────────────────────────
    if ( $welcome_page ) {
        $redirect_url = get_permalink( $welcome_page->ID );
    } else {
        $redirect_url = home_url( '/' );
    }

    // Nur weiterleiten wenn wir nicht bereits dort sind
    $current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    if ( rtrim( $current_url, '/' ) === rtrim( $redirect_url, '/' ) ) return;

    wp_redirect( $redirect_url, 302 );
    exit;
}

/**
 * Ermittelt die Welcome Page – zuerst via ID-Konstante, dann via Slug.
 */
function customtheme_get_welcome_page(): ?WP_Post {
    // Via ID (bevorzugt)
    if ( WELCOME_PAGE_ID > 0 ) {
        $page = get_post( WELCOME_PAGE_ID );
        if ( $page && $page->post_status === 'publish' ) return $page;
    }

    // Via Slug (Fallback)
    $page = get_page_by_path( WELCOME_PAGE_SLUG );
    if ( $page && $page->post_status === 'publish' ) return $page;

    return null;
}

// =============================================================================
// ADMIN-HINWEIS wenn Welcome Mode aktiv
// =============================================================================

add_action( 'admin_notices', 'customtheme_welcome_mode_admin_notice' );

function customtheme_welcome_mode_admin_notice(): void {
    if ( ! WELCOME_MODE ) return;

    $welcome_page = customtheme_get_welcome_page();
    $edit_link    = $welcome_page
        ? '<a href="' . esc_url( get_edit_post_link( $welcome_page->ID ) ) . '">Welcome Page bearbeiten</a>'
        : 'Bitte zuerst eine Seite mit dem Template "Welcome Page" erstellen.';

    echo '<div class="notice notice-warning">';
    echo '<p><strong>⚠ Welcome Mode ist aktiv.</strong> Alle Besucher werden auf die Welcome Page weitergeleitet. ';
    echo $edit_link;
    echo ' | <a href="' . esc_url( add_query_arg( 'preview_as_visitor', '1', home_url() ) ) . '">Als Besucher ansehen</a>';
    echo '</p></div>';
}
