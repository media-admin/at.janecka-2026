
// ── Permanenter schwarzer Balken über dem Header ──────────────────────────────
// Gibt ein leeres .site-notifications-banner aus wenn das Plugin keins rendert
add_action( 'wp_body_open', function(): void {
    // Nur ausgeben wenn das Plugin-Banner NICHT aktiv ist
    if ( function_exists( 'media_lab_get_active_notifications' ) ) {
        $banners = media_lab_get_active_notifications( 'banner' );
        if ( ! empty( $banners ) ) return; // Plugin rendert bereits
    }
    echo '<div class="site-notifications-banner site-notifications-banner--empty"></div>';
}, 5 ); // Priority 5 = vor dem Plugin (Priority 10)
