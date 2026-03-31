<?php
/**
 * Janecka Mega Menu Walker
 *
 * Aktivierung:
 *   1. WP Admin → Darstellung → Menü → Bildschirmoptionen → "CSS-Klassen" aktivieren
 *   2. Dem gewünschten Top-Level-Menüpunkt die Klasse "mega-menu" vergeben
 *
 * Optionales Bild:
 *   - ACF-Feld "mega_menu_image" (Typ: Image, Return Format: Array oder URL)
 *     auf dem Top-Level-Menüpunkt anlegen (Location: Menu Item)
 *   - Das Bild erscheint dann als rechte Spalte im Mega Menu Panel
 *
 * Einbindung in functions.php:
 *   require_once get_template_directory() . '/inc/class-mega-menu-walker.php';
 *
 * Verwendung in header.php (wp_nav_menu):
 *   'walker' => new Janecka_Walker_Mega_Menu(),
 */

if ( ! class_exists( 'Janecka_Walker_Mega_Menu' ) ) :

class Janecka_Walker_Mega_Menu extends Walker_Nav_Menu {

    // ── State ──────────────────────────────────────────────────────────────────

    /** Ob das aktuell verarbeitete Top-Level-Item ein Mega Menu ist. */
    private bool $in_mega = false;

    /** Referenz auf das aktuelle Top-Level-Item (für ACF-Bild-Lookup). */
    private ?object $mega_item = null;


    // ── Submenu öffnen ─────────────────────────────────────────────────────────

    public function start_lvl( &$output, $depth = 0, $args = null ) {

        // Depth 0 + Mega Menu → Panel-Wrapper statt <ul class="sub-menu">
        if ( $depth === 0 && $this->in_mega ) {
            $output .= "\n"
                . '<div class="mega-menu__panel">'
                . '<div class="mega-menu__inner">'
                . '<ul class="mega-menu__links">'
                . "\n";
            return;
        }

        // Standard-Dropdown
        $indent  = str_repeat( "\t", $depth );
        $output .= "\n{$indent}<ul class=\"sub-menu\">\n";
    }


    // ── Submenu schließen ──────────────────────────────────────────────────────

    public function end_lvl( &$output, $depth = 0, $args = null ) {

        if ( $depth === 0 && $this->in_mega ) {
            $output .= "</ul>\n"; // .mega-menu__links

            // Optionales ACF-Bild als rechte Spalte
            $image = $this->get_mega_image();
            if ( $image ) {
                $output .= '<div class="mega-menu__image">';
                $output .= '<img'
                    . ' src="'   . esc_url( $image['url'] )  . '"'
                    . ' alt="'   . esc_attr( $image['alt'] ) . '"'
                    . ' loading="lazy"'
                    . '>';
                $output .= '</div>';
            }

            $output .= '</div></div>' . "\n"; // .mega-menu__inner + .mega-menu__panel
            return;
        }

        $indent  = str_repeat( "\t", $depth );
        $output .= "{$indent}</ul>\n";
    }


    // ── Listenelement öffnen ───────────────────────────────────────────────────

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {

        // Top-Level: Mega-Menu-Flag + Item-Referenz für Bild-Lookup aktualisieren
        if ( $depth === 0 ) {
            $this->in_mega   = in_array( 'mega-menu', (array) $item->classes, true );
            $this->mega_item = $item;
        }

        // Standard Walker für alle Elemente (HTML-Markup unverändert)
        parent::start_el( $output, $item, $depth, $args, $id );
    }


    // ── Hilfsmethoden ──────────────────────────────────────────────────────────

    /**
     * ACF-Bild vom gespeicherten Top-Level-Item holen.
     *
     * Erwartet ein ACF-Feld "mega_menu_image" (Location: Menu Item)
     * mit Return Format "array" oder "url".
     *
     * @return array{url: string, alt: string}|false
     */
    private function get_mega_image(): array|false {

        if ( ! $this->mega_item || ! function_exists( 'get_field' ) ) {
            return false;
        }

        $img = get_field( 'mega_menu_image', 'menu_item_' . $this->mega_item->ID );

        if ( empty( $img ) ) {
            return false;
        }

        // Return Format: Array
        if ( is_array( $img ) && ! empty( $img['url'] ) ) {
            return [
                'url' => $img['url'],
                'alt' => $img['alt'] ?? '',
            ];
        }

        // Return Format: URL (String)
        if ( is_string( $img ) && ! empty( $img ) ) {
            return [
                'url' => $img,
                'alt' => '',
            ];
        }

        return false;
    }
}

endif;
