<?php
/**
 * Bilder-Sitemap – WordPress Core Sitemap Erweiterung
 *
 * Erweitert die native WordPress Sitemap (/wp-sitemap.xml) um Bildeinträge.
 * Für jeden konfigurierten Post Type werden Hauptbild + Galerie-Bilder
 * als <image:image>-Elemente in den jeweiligen URL-Eintrag eingefügt.
 *
 * Aktivierung: Agency Core → SEO-Einstellungen → Bilder-Sitemap
 *
 * Unterstützte Post Types: alle registrierten (WooCommerce-Produkte, Portfolio, Blog, etc.)
 *
 * Google Dokumentation:
 * https://developers.google.com/search/docs/crawling-indexing/sitemaps/image-sitemaps
 *
 * @package MediaLab_SEO
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// =============================================================================
// Bootstrap – früh laden damit Sitemap-Hooks greifen
// =============================================================================

add_action( 'init', 'medialab_image_sitemap_init', 5 );

function medialab_image_sitemap_init(): void {
    // Nur wenn Feature aktiviert
    if ( ! medialab_image_sitemap_enabled() ) return;

    // Namespace für image: Sitemap-Tag registrieren
    add_filter( 'wp_sitemaps_index_entry',        'medialab_sitemap_add_image_namespace', 10, 3 );
    add_filter( 'wp_sitemaps_posts_entry',         'medialab_sitemap_posts_add_images',   10, 3 );
    add_filter( 'wp_sitemaps_posts_query_args',    'medialab_sitemap_filter_post_types',  10, 2 );
}

// =============================================================================
// Einstellungen lesen
// =============================================================================

/**
 * Ist die Bilder-Sitemap aktiviert?
 */
function medialab_image_sitemap_enabled(): bool {
    return (bool) get_option( 'medialab_image_sitemap_enabled', false );
}

/**
 * Welche Post Types sollen Bilder enthalten?
 * Gibt Array von Post-Type-Slugs zurück.
 *
 * @return string[]
 */
function medialab_image_sitemap_post_types(): array {
    $saved = get_option( 'medialab_image_sitemap_post_types', [] );

    if ( empty( $saved ) || ! is_array( $saved ) ) {
        // Default: Produkte (WooCommerce) wenn vorhanden, sonst Posts + Pages
        $defaults = [];
        if ( post_type_exists( 'product' ) ) $defaults[] = 'product';
        if ( post_type_exists( 'post' ) )    $defaults[] = 'post';
        return $defaults;
    }

    return array_filter( $saved, 'post_type_exists' );
}

// =============================================================================
// Bilder zu Sitemap-Einträgen hinzufügen
// =============================================================================

/**
 * Fügt Bildeinträge zu jedem Post in der Sitemap hinzu.
 *
 * Der Filter `wp_sitemaps_posts_entry` wird für jeden einzelnen URL-Eintrag
 * aufgerufen. Wir fügen ein `images`-Array hinzu, das später via
 * `wp_sitemaps_posts_entry` in den XML-Output einfließt.
 *
 * Leider unterstützt WordPress Core nativ keine <image:image>-Tags.
 * Wir müssen deshalb den XML-Output via `wp_sitemap_index` + eigene Ausgabe
 * oder den WP_Sitemaps_Renderer-Filter abfangen.
 *
 * Lösung: eigener Rewrite-Endpoint der die Standard-Sitemap um Bilder ergänzt.
 */
add_action( 'init', 'medialab_register_image_sitemap_endpoint' );

function medialab_register_image_sitemap_endpoint(): void {
    if ( ! medialab_image_sitemap_enabled() ) return;

    // Eigene Sitemap-URL: /wp-sitemap-images.xml
    add_rewrite_rule(
        '^wp-sitemap-images(-([0-9]+))?\.xml$',
        'index.php?medialab_image_sitemap=1&medialab_sitemap_page=$matches[2]',
        'top'
    );
    add_rewrite_tag( '%medialab_image_sitemap%', '([0-9]+)' );
    add_rewrite_tag( '%medialab_sitemap_page%',  '([0-9]+)' );

    add_action( 'template_redirect', 'medialab_render_image_sitemap' );
}

/**
 * Rendert die Bilder-Sitemap als XML.
 */
function medialab_render_image_sitemap(): void {
    if ( ! get_query_var( 'medialab_image_sitemap' ) ) return;

    $page       = max( 1, (int) get_query_var( 'medialab_sitemap_page', 1 ) );
    $post_types = medialab_image_sitemap_post_types();
    $per_page   = (int) apply_filters( 'medialab_image_sitemap_per_page', 200 );

    if ( empty( $post_types ) ) {
        status_header( 404 );
        exit;
    }

    $posts = medialab_image_sitemap_get_posts( $post_types, $page, $per_page );

    if ( empty( $posts ) && $page > 1 ) {
        status_header( 404 );
        exit;
    }

    // XML ausgeben
    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex, follow', true );

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
    echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    foreach ( $posts as $post ) {
        $images = medialab_image_sitemap_get_images( $post->ID );
        if ( empty( $images ) ) continue;

        echo "\t<url>\n";
        echo "\t\t<loc>" . esc_url( get_permalink( $post->ID ) ) . "</loc>\n";

        foreach ( $images as $img ) {
            echo "\t\t<image:image>\n";
            echo "\t\t\t<image:loc>" . esc_url( $img['url'] ) . "</image:loc>\n";
            if ( ! empty( $img['title'] ) ) {
                echo "\t\t\t<image:title>" . esc_xml( $img['title'] ) . "</image:title>\n";
            }
            if ( ! empty( $img['caption'] ) ) {
                echo "\t\t\t<image:caption>" . esc_xml( $img['caption'] ) . "</image:caption>\n";
            }
            echo "\t\t</image:image>\n";
        }

        echo "\t</url>\n";
    }

    echo '</urlset>';
    exit;
}

// =============================================================================
// Sitemap-Index erweitern
// =============================================================================

add_filter( 'wp_sitemaps_index_entry', 'medialab_add_image_sitemap_to_index', 10, 3 );

/**
 * Fügt die Bilder-Sitemap zum Sitemap-Index hinzu.
 * Wird als letzter Eintrag in /wp-sitemap.xml erscheinen.
 */
function medialab_add_image_sitemap_to_index( array $entry, string $object_type, string $object_subtype ): array {
    // Nur beim letzten normalen Eintrag anhängen – wir nutzen stattdessen
    // den Filter `wp_sitemaps_sitemaps` der direkt den Index beeinflusst.
    return $entry;
}

add_filter( 'wp_sitemaps_sitemaps', 'medialab_register_image_sitemap_provider' );

/**
 * Registriert den Bilder-Sitemap-Provider im WordPress Sitemap-System.
 */
function medialab_register_image_sitemap_provider( array $sitemaps ): array {
    if ( ! medialab_image_sitemap_enabled() ) return $sitemaps;
    if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) return $sitemaps;

    require_once __DIR__ . '/image-sitemap-provider.php';
    $sitemaps['medialab-images'] = new MediaLab_Image_Sitemap_Provider();
    return $sitemaps;
}

// =============================================================================
// Hilfsfunktionen
// =============================================================================

/**
 * Lädt Posts für die Bilder-Sitemap (paginiert).
 *
 * @param  string[] $post_types
 * @param  int      $page
 * @param  int      $per_page
 * @return WP_Post[]
 */
function medialab_image_sitemap_get_posts( array $post_types, int $page, int $per_page ): array {
    return get_posts( [
        'post_type'        => $post_types,
        'post_status'      => 'publish',
        'posts_per_page'   => $per_page,
        'paged'            => $page,
        'orderby'          => 'modified',
        'order'            => 'DESC',
        'suppress_filters' => false,
        'has_password'     => false,
        // Nur Posts mit Hauptbild oder Galerie
        'meta_query'       => [
            'relation' => 'OR',
            [
                'key'     => '_thumbnail_id',
                'compare' => 'EXISTS',
            ],
            [
                'key'     => '_product_image_gallery', // WooCommerce
                'compare' => 'EXISTS',
            ],
        ],
    ] );
}

/**
 * Sammelt alle Bilder eines Posts (Hauptbild + Galerie).
 *
 * @param  int $post_id
 * @return array[] Array von [ 'url', 'title', 'caption' ]
 */
function medialab_image_sitemap_get_images( int $post_id ): array {
    $images     = [];
    $seen_ids   = [];

    // ── 1. Hauptbild (Featured Image) ────────────────────────────────────────
    $thumbnail_id = get_post_thumbnail_id( $post_id );
    if ( $thumbnail_id ) {
        $img = medialab_image_sitemap_build_entry( $thumbnail_id );
        if ( $img ) {
            $images[]            = $img;
            $seen_ids[]          = $thumbnail_id;
        }
    }

    // ── 2. WooCommerce Produkt-Galerie ────────────────────────────────────────
    $gallery_meta = get_post_meta( $post_id, '_product_image_gallery', true );
    if ( $gallery_meta ) {
        $gallery_ids = array_filter( array_map( 'intval', explode( ',', $gallery_meta ) ) );
        foreach ( $gallery_ids as $img_id ) {
            if ( in_array( $img_id, $seen_ids, true ) ) continue;
            $img = medialab_image_sitemap_build_entry( $img_id );
            if ( $img ) {
                $images[]   = $img;
                $seen_ids[] = $img_id;
            }
        }
    }

    // ── 3. WordPress Standard-Galerie (Gutenberg / Classic Editor) ─────────────
    $post = get_post( $post_id );
    if ( $post && has_blocks( $post->post_content ) ) {
        // Gutenberg: wp:gallery Blöcke
        $blocks = parse_blocks( $post->post_content );
        medialab_image_sitemap_parse_blocks( $blocks, $images, $seen_ids );
    }

    // ── 4. Classic Editor: [gallery] Shortcode ────────────────────────────────
    if ( $post && has_shortcode( $post->post_content, 'gallery' ) ) {
        preg_match_all( '/\[gallery[^\]]*ids=["\']?([\d,]+)["\']?/i', $post->post_content, $matches );
        foreach ( $matches[1] ?? [] as $ids_string ) {
            foreach ( array_map( 'intval', explode( ',', $ids_string ) ) as $img_id ) {
                if ( in_array( $img_id, $seen_ids, true ) ) continue;
                $img = medialab_image_sitemap_build_entry( $img_id );
                if ( $img ) {
                    $images[]   = $img;
                    $seen_ids[] = $img_id;
                }
            }
        }
    }

    // ── 5. ACF Image / Gallery Felder (falls vorhanden) ─────────────────────
    if ( function_exists( 'get_field_objects' ) ) {
        $fields = get_field_objects( $post_id );
        if ( is_array( $fields ) ) {
            foreach ( $fields as $field ) {
                if ( $field['type'] === 'image' && ! empty( $field['value']['ID'] ) ) {
                    $img_id = (int) $field['value']['ID'];
                    if ( ! in_array( $img_id, $seen_ids, true ) ) {
                        $img = medialab_image_sitemap_build_entry( $img_id );
                        if ( $img ) {
                            $images[]   = $img;
                            $seen_ids[] = $img_id;
                        }
                    }
                }
                if ( $field['type'] === 'gallery' && is_array( $field['value'] ) ) {
                    foreach ( $field['value'] as $acf_img ) {
                        $img_id = is_array( $acf_img ) ? (int) ( $acf_img['ID'] ?? 0 ) : (int) $acf_img;
                        if ( ! $img_id || in_array( $img_id, $seen_ids, true ) ) continue;
                        $img = medialab_image_sitemap_build_entry( $img_id );
                        if ( $img ) {
                            $images[]   = $img;
                            $seen_ids[] = $img_id;
                        }
                    }
                }
            }
        }
    }

    return apply_filters( 'medialab_image_sitemap_images', $images, $post_id );
}

/**
 * Baut einen Bild-Eintrag aus einer Attachment-ID.
 *
 * @return array|null  [ 'url', 'title', 'caption' ] oder null wenn Bild nicht gefunden
 */
function medialab_image_sitemap_build_entry( int $attachment_id ): ?array {
    $url = wp_get_attachment_image_url( $attachment_id, 'full' );
    if ( ! $url ) return null;

    // URL muss zur eigenen Domain gehören (kein CDN-Problem)
    $home = home_url();
    if ( ! str_starts_with( $url, $home ) && ! str_starts_with( $url, '//' ) ) {
        // Erlauben wenn auf derselben Domain (relative Protokoll-URLs)
        $parsed_url  = parse_url( $url );
        $parsed_home = parse_url( $home );
        if ( ( $parsed_url['host'] ?? '' ) !== ( $parsed_home['host'] ?? '' ) ) {
            return null;
        }
    }

    $attachment = get_post( $attachment_id );

    return [
        'url'     => $url,
        'title'   => $attachment ? trim( $attachment->post_title ) : '',
        'caption' => $attachment ? trim( $attachment->post_excerpt ) : '',
    ];
}

/**
 * Durchsucht Gutenberg-Blöcke rekursiv nach Bildern.
 */
function medialab_image_sitemap_parse_blocks( array $blocks, array &$images, array &$seen_ids ): void {
    foreach ( $blocks as $block ) {
        // wp:image
        if ( $block['blockName'] === 'core/image' && ! empty( $block['attrs']['id'] ) ) {
            $img_id = (int) $block['attrs']['id'];
            if ( ! in_array( $img_id, $seen_ids, true ) ) {
                $img = medialab_image_sitemap_build_entry( $img_id );
                if ( $img ) {
                    $images[]   = $img;
                    $seen_ids[] = $img_id;
                }
            }
        }

        // wp:gallery → innerBlocks
        if ( $block['blockName'] === 'core/gallery' && ! empty( $block['innerBlocks'] ) ) {
            medialab_image_sitemap_parse_blocks( $block['innerBlocks'], $images, $seen_ids );
        }

        // Media & Text Block
        if ( $block['blockName'] === 'core/media-text' && ! empty( $block['attrs']['mediaId'] ) ) {
            $img_id = (int) $block['attrs']['mediaId'];
            if ( ! in_array( $img_id, $seen_ids, true ) ) {
                $img = medialab_image_sitemap_build_entry( $img_id );
                if ( $img ) {
                    $images[]   = $img;
                    $seen_ids[] = $img_id;
                }
            }
        }
    }
}

/**
 * XML-safe escaping für Sitemap-Strings.
 */
if ( ! function_exists( 'esc_xml' ) ) {
    function esc_xml( string $text ): string {
        return htmlspecialchars( $text, ENT_XML1 | ENT_QUOTES, 'UTF-8' );
    }
}

// Einstellungen werden über das SEO-Dashboard (seo-dashboard.php) gespeichert.

// =============================================================================
// Admin-Hinweis: Sitemap-URL anzeigen
// =============================================================================

add_action( 'admin_notices', 'medialab_image_sitemap_admin_notice' );

function medialab_image_sitemap_admin_notice(): void {
    $screen = get_current_screen();
    if ( ! $screen || ! str_contains( $screen->id ?? '', 'medialab-seo' ) ) return;
    if ( ! medialab_image_sitemap_enabled() ) return;

    $sitemap_url = home_url( '/wp-sitemap-images.xml' );
    ?>
    <div class="notice notice-info" style="margin-top:8px">
        <p>
            <strong>🖼 Bilder-Sitemap aktiv:</strong>
            <a href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank">
                <?php echo esc_html( $sitemap_url ); ?>
            </a>
            &nbsp;—&nbsp;
            <a href="https://search.google.com/search-console/sitemaps" target="_blank" rel="noopener">
                In Google Search Console eintragen →
            </a>
        </p>
    </div>
    <?php
}
