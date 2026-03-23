<?php
/**
 * Bilder-Sitemap Provider
 *
 * Registriert die Bilder-Sitemap im WordPress Core Sitemap-System.
 * Erscheint als eigener Eintrag im Sitemap-Index (/wp-sitemap.xml).
 *
 * @package MediaLab_SEO
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) return;

class MediaLab_Image_Sitemap_Provider extends WP_Sitemaps_Provider {

    public function __construct() {
        $this->name        = 'medialab-images';
        $this->object_type = 'post';
    }

    /**
     * Gibt die Sitemap-Einträge für eine Seite zurück.
     * Jeder Eintrag: [ 'loc' => URL ]
     *
     * Da WordPress Core keine image:-Tags nativ unterstützt,
     * leiten wir auf unsere eigene Sitemap-URL weiter.
     */
    public function get_url_list( int $page_num, string $object_subtype = '' ): array {
        // Wir geben nur die URL zur eigenen Bilder-Sitemap zurück
        return [ [
            'loc' => home_url( '/wp-sitemap-images.xml' ),
        ] ];
    }

    /**
     * Anzahl der Seiten für den Sitemap-Index.
     */
    public function get_max_num_pages( string $object_subtype = '' ): int {
        return 1;
    }
}
