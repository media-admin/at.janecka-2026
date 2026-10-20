<?php
/**
 * Buchungen: automatische Bestätigung (projektspezifisch)
 *
 * Kundenwunsch (Okt 2026): Buchungen müssen im Backend nicht mehr gesondert
 * bestätigt werden. Die Rückmeldung an den Kunden (Eingangsmail) nennt den
 * Termin bereits als bestätigt.
 *
 * Umsetzung ohne Eingriff in media-lab-bookings:
 *  - Hook `mlb_after_save_booking` (läuft in MLB_Ajax::submit_booking NACH dem
 *    Speichern der ACF-Felder und VOR MLB_Mail::send_confirmation()).
 *  - Status wird auf `mlb-confirmed` gesetzt (ACF-Meta + WP-Post-Status, beide
 *    müssen synchron sein, siehe MLB_Notifications::on_acf_save()).
 *  - Es wird bewusst KEINE zweite Status-Mail ("Ihre Buchung wurde bestätigt")
 *    verschickt, da die Eingangsmail den Termin bereits bestätigt und die
 *    Filiale ihre Benachrichtigung über MLB_Mail::send_confirmation() erhält
 *    (Empfänger: ACF-Feld `mlb_location_email` am Standort).
 *  - Der Erinnerungs-Cron wird wie bei einer manuellen Bestätigung geplant.
 *
 * Abschalten: define( 'JANECKA_BOOKING_AUTO_CONFIRM', false ); in wp-config.php
 * oder add_filter( 'janecka_booking_auto_confirm', '__return_false' );
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'mlb_after_save_booking', 'janecka_booking_auto_confirm', 5, 2 );

/**
 * @param int   $booking_id  ID des neu angelegten mlb_booking-Posts
 * @param array $booking_data Daten aus dem Frontend-Formular (unused)
 */
function janecka_booking_auto_confirm( int $booking_id, array $booking_data = [] ): void {

    if ( defined( 'JANECKA_BOOKING_AUTO_CONFIRM' ) && ! JANECKA_BOOKING_AUTO_CONFIRM ) return;
    if ( ! apply_filters( 'janecka_booking_auto_confirm', true, $booking_id, $booking_data ) ) return;
    if ( get_post_type( $booking_id ) !== 'mlb_booking' ) return;

    // ACF-Meta (maßgeblich für Plugin-Logik, Kalender, Slots) …
    if ( function_exists( 'update_field' ) ) {
        update_field( 'mlb_booking_status', 'mlb-confirmed', $booking_id );
    } else {
        update_post_meta( $booking_id, 'mlb_booking_status', 'mlb-confirmed' );
    }

    // … und WP-Post-Status synchron halten (sonst Sync-Warnung im Backend).
    wp_update_post( [ 'ID' => $booking_id, 'post_status' => 'mlb-confirmed' ] );

    // Erinnerungs-Cron planen (wie bei manueller Bestätigung im Backend).
    if ( class_exists( 'MLB_Notifications' ) ) {
        MLB_Notifications::schedule_reminder( $booking_id );
    }
}
