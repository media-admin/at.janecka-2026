<?php
/**
 * Wunschliste (media-lab-woocommerce, inc/wishlist): Janecka-spezifische Anpassungen.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Teilen-Block der Wunschliste: nur Facebook, WhatsApp, E-Mail als schlichte
 * Icons mit Label "Teilen via ...". Wirkt nur auf die Wunschliste, die
 * globalen Share-Einstellungen (ACF "Share-Buttons") bleiben unberührt.
 *
 * icon_only bleibt bewusst false: medialab_share() lässt bei icon_only auch
 * das äußere Label weg. Die Button-Beschriftungen blendet das SCSS aus
 * (_wishlist-share.scss), sie bleiben für Screenreader erhalten.
 */
add_filter( 'mlw_wishlist_share_args', function () {
    return [
        'services'   => 'facebook,whatsapp,email',
        'style'      => 'mono',
        'icon_only'  => false,
        'show_label' => true,
        'label'      => 'Teilen via ...',
    ];
} );
