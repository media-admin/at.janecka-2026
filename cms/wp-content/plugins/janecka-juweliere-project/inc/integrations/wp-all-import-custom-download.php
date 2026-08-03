<?php
/**
 * WP All Import - Custom Image Download via wp_remote_get()
 *
 * Ersetzt WP All Imports eingebauten, rohen cURL-Downloader für Bilder.
 * Ursache: Der Quellserver (union-glashuette.com) blockt den User-Agent
 * "WP All Import (version:X)" stillschweigend (cURL error 28/56, 0 bytes
 * empfangen). wp_remote_get() mit dem Standard-WordPress-UA funktioniert
 * zuverlässig (verifiziert: 12x hintereinander erfolgreich, kein
 * Rate-Limiting).
 *
 * Lädt das Bild herunter, speichert es in
 * wp-content/uploads/wpallimport/files/ und gibt nur den Dateinamen
 * zurück. WP All Import erkennt Dateien in diesem Ordner automatisch als
 * lokal vorhanden (keine URL-Validierung nötig, die bei rohen Pfaden
 * oder file:// fehlschlägt).
 *
 * Einbindung im Import-Template (Bild-Feld):
 *   [custom_file_download({Bildfeld}, "png")]
 * statt der reinen URL-Zuordnung.
 *
 * Voraussetzung: Image Options -> Checkbox "Use images currently
 * uploaded in wp-content/uploads/wpallimport/files/" muss aktiviert sein.
 *
 * INTERIMSLÖSUNG: Bei Starter-Kit-Backport nach media-lab-agency-core
 * migrieren. Die Backport-Doku muss darauf hinweisen, dass das
 * Import-Template selbst (Bild-Feld + Checkbox) pro Projekt manuell
 * angepasst werden muss – das ist nicht Teil des Plugin-Codes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'PMXI_VERSION' ) && ! function_exists( 'custom_file_download' ) ) {

	/**
	 * Lädt eine Bild-URL per wp_remote_get() herunter und gibt den
	 * lokalen temporären Dateipfad zurück (von WP All Import erwartetes
	 * Rückgabeformat für custom_file_download()).
	 *
	 * @param string $url Bild-URL.
	 * @param string $ext Ziel-Dateiendung ohne Punkt, z.B. "png".
	 * @return string|false Lokaler Dateipfad oder false bei Fehler.
	 */
	function custom_file_download( $url, $ext = '' ) {
        if ( empty( $url ) ) {
            return false;
        }

        $timeout = (int) apply_filters( 'mlac_wpai_image_timeout_seconds', 30 );

        $response = wp_remote_get(
            $url,
            array(
                'timeout'   => $timeout,
                'sslverify' => true,
            )
        );

        if ( is_wp_error( $response ) ) {
            error_log( 'custom_file_download: Fehler bei ' . $url . ' - ' . $response->get_error_message() );
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            error_log( 'custom_file_download: HTTP ' . $code . ' bei ' . $url );
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            error_log( 'custom_file_download: Leerer Response-Body bei ' . $url );
            return false;
        }

        if ( empty( $ext ) ) {
            $path_info = pathinfo( (string) parse_url( $url, PHP_URL_PATH ) );
            $ext       = isset( $path_info['extension'] ) ? $path_info['extension'] : 'jpg';
        }

        $target_dir = WP_CONTENT_DIR . '/uploads/wpallimport/files/';
        if ( ! file_exists( $target_dir ) ) {
            wp_mkdir_p( $target_dir );
        }

        $filename    = sanitize_file_name( basename( (string) parse_url( $url, PHP_URL_PATH ) ) );
        $target_path = $target_dir . $filename;

        file_put_contents( $target_path, $body );

        return $filename;
    }
}