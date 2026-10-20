<?php
/**
 * Read-only Prüfung des Buchungs-Setups (ändert nichts an der Datenbank).
 *
 * Aufruf (localdev):
 *   cd cms
 *   wp eval-file wp-content/plugins/janecka-juweliere-project/bin/check-booking-setup.php
 *
 * Prüft:
 *   1. Pro Standort (mlb_location): Filial-E-Mail gültig? Abgleich mit store-email
 *      der zugeordneten Filiale (CPT stores, Titel-Zuordnung wie im Theme).
 *   2. Pro Standort: Anzahl Sperr-Einträge, Max. Kapazität, Tageslimit.
 *   3. Buchungen: Status-Verteilung, Datumsformat in der DB (Ymd vs. Y-m-d)
 *      und Doppelbuchungen (mehr Buchungen pro Slot als Kapazität).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) exit;

$line = static function ( string $s = '' ) { WP_CLI::log( $s ); };

// ── 1 + 2: Standorte ──────────────────────────────────────────────────────────

$locations = get_posts( [ 'post_type' => 'mlb_location', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ] );
$stores    = get_posts( [ 'post_type' => 'stores',       'post_status' => 'publish', 'posts_per_page' => -1 ] );

$line( '=== STANDORTE (' . count( $locations ) . ') ===' );

$capacity_by_location = [];

foreach ( $locations as $loc ) {
    $email    = get_field( 'mlb_location_email', $loc->ID );
    $mail_ok  = $email && is_email( $email );
    $capacity = (int) ( get_field( 'mlb_max_capacity', $loc->ID ) ?: 1 );
    $per_day  = (int) ( get_field( 'mlb_max_per_day',  $loc->ID ) ?: 0 );
    $blocked  = get_field( 'mlb_blocked_periods', $loc->ID );
    $blocked  = is_array( $blocked ) ? count( $blocked ) : 0;
    $capacity_by_location[ $loc->ID ] = $capacity;

    // Zuordnung zur Filiale (Logik analog janecka_single_contact_block)
    $store_email = '';
    $store_title = '';
    foreach ( $stores as $store ) {
        if ( stripos( $loc->post_title, $store->post_title ) !== false ) {
            $store_title = $store->post_title;
            $store_email = (string) get_field( 'store-email', $store->ID );
            break;
        }
    }

    $flags = [];
    if ( ! $mail_ok )                                  $flags[] = 'FILIAL-MAIL FEHLT/UNGÜLTIG';
    if ( $store_title === '' )                         $flags[] = 'keine Filiale zugeordnet';
    if ( $store_email && $mail_ok && strcasecmp( $store_email, $email ) !== 0 ) $flags[] = 'weicht von store-email ab';
    if ( $blocked === 0 )                              $flags[] = 'keine Sperren gepflegt';

    $line( sprintf(
        '#%d %s | mlb_location_email: %s | Filiale: %s (store-email: %s) | Kapazität/Slot: %d | Tageslimit: %s | Sperr-Einträge: %d%s',
        $loc->ID,
        $loc->post_title,
        $email ?: '—',
        $store_title ?: '—',
        $store_email ?: '—',
        $capacity,
        $per_day ?: '–',
        $blocked,
        $flags ? '  <-- ' . implode( '; ', $flags ) : ''
    ) );
}

// ── 3: Buchungen ──────────────────────────────────────────────────────────────

$bookings = get_posts( [ 'post_type' => 'mlb_booking', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ] );

$status_count = [];
$fmt_ymd      = 0;
$fmt_other    = 0;
$groups       = [];
$today        = date( 'Y-m-d' );

foreach ( $bookings as $bid ) {
    $status = get_post_meta( $bid, 'mlb_booking_status', true ) ?: 'mlb-pending';
    $status_count[ $status ] = ( $status_count[ $status ] ?? 0 ) + 1;

    $raw = (string) get_post_meta( $bid, 'mlb_booking_date', true );
    if ( preg_match( '/^\d{8}$/', $raw ) ) $fmt_ymd++; else $fmt_other++;

    if ( $status === 'mlb-cancelled' || $raw === '' ) continue;

    $date = date( 'Y-m-d', strtotime( $raw ) );
    $loc  = (int) get_post_meta( $bid, 'mlb_booking_location', true );
    $time = (string) get_post_meta( $bid, 'mlb_booking_time', true );
    $groups[ $loc . '|' . $date . '|' . $time ][] = $bid;
}

$line();
$line( '=== BUCHUNGEN (' . count( $bookings ) . ') ===' );
foreach ( $status_count as $s => $n ) $line( sprintf( '%-14s %d', $s, $n ) );
$line( sprintf( 'Datumsformat in DB: Ymd (z.B. 20261105) = %d | anderes Format = %d', $fmt_ymd, $fmt_other ) );

$line();
$line( '=== DOPPELBUCHUNGEN (mehr Buchungen als Kapazität pro Slot) ===' );
$found = 0;
foreach ( $groups as $key => $ids ) {
    [ $loc, $date, $time ] = explode( '|', $key );
    $cap = $capacity_by_location[ (int) $loc ] ?? 1;
    if ( count( $ids ) <= $cap ) continue;
    $found++;
    $line( sprintf(
        '%s %s Uhr | %s | %d Buchungen bei Kapazität %d | IDs: %s%s',
        $date, $time, get_the_title( (int) $loc ), count( $ids ), $cap,
        implode( ', ', $ids ),
        $date >= $today ? '  <-- ZUKÜNFTIG' : ''
    ) );
}
if ( ! $found ) $line( 'Keine gefunden.' );
