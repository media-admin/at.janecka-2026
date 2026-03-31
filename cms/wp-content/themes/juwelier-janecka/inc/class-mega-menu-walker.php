<?php
/**
 * Janecka Mega Menu Walker
 *
 * Aktivierung:
 *   1. WP Admin → Darstellung → Menü → Bildschirmoptionen → "CSS-Klassen" aktivieren
 *   2. Dem gewünschten Top-Level-Menüpunkt die Klasse "mega-menu" vergeben
 *
 * Bild pro Menüpunkt:
 *   Einen "Individuelle URL"-Eintrag im Untermenü anlegen.
 *   Als Navigations-Label folgenden HTML-Code eingeben:
 *   <img class="mega-menu__img" src="URL-ZUM-BILD.jpg" alt="Beschreibung">
 *   Der Walker erkennt diesen Eintrag, extrahiert das Bild und zeigt
 *   es als rechte Spalte an — der Eintrag erscheint nicht als Link.
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

    /** Ob das aktuelle Top-Level-Item ein Mega Menu ist. */
    private bool $in_mega = false;

    /** Gespeichertes Bild-HTML für das aktuelle Mega Menu. */
    private string $image_html = '';

    /** Ob das aktuelle Element übersprungen werden soll (Bild-Item). */
    private bool $skip_item = false;


    // ── Submenu öffnen ─────────────────────────────────────────────────────────

    public function start_lvl( &$output, $depth = 0, $args = null ) {

        if ( $depth === 0 && $this->in_mega ) {
            $output .= "\n"
                . '<div class="mega-menu__panel">'
                . '<div class="mega-menu__inner">'
                . '<ul class="mega-menu__links">'
                . "\n";
            return;
        }

        $indent  = str_repeat( "\t", $depth );
        $output .= "\n{$indent}<ul class=\"sub-menu\">\n";
    }


    // ── Submenu schließen ──────────────────────────────────────────────────────

    public function end_lvl( &$output, $depth = 0, $args = null ) {

        if ( $depth === 0 && $this->in_mega ) {
            $output .= "</ul>\n"; // .mega-menu__links schließen

            // Bild ausgeben falls vorhanden
            if ( $this->image_html ) {
                $output .= '<div class="mega-menu__image">'
                    . $this->image_html
                    . '</div>' . "\n";
            }

            $output .= '</div></div>' . "\n"; // .mega-menu__inner + .mega-menu__panel
            return;
        }

        $indent  = str_repeat( "\t", $depth );
        $output .= "{$indent}</ul>\n";
    }


    // ── Listenelement öffnen ───────────────────────────────────────────────────

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {

        // Top-Level: Flags zurücksetzen
        if ( $depth === 0 ) {
            $this->in_mega    = in_array( 'mega-menu', (array) $item->classes, true );
            $this->image_html = '';
        }

        // Bild-Item erkennen: Navigations-Label enthält <img class="mega-menu__img">
        if ( $this->in_mega && $depth >= 1 ) {
            $title = $item->title ?? '';
            if ( $this->is_image_item( $title ) ) {
                $this->image_html = $title; // img-Tag speichern
                $this->skip_item  = true;
                return; // kein Output, kein <li>
            }
        }

        $this->skip_item = false;
        parent::start_el( $output, $item, $depth, $args, $id );
    }


    // ── Listenelement schließen ────────────────────────────────────────────────

    public function end_el( &$output, $item, $depth = 0, $args = null ) {

        if ( $this->skip_item ) {
            $this->skip_item = false;
            return; // kein </li>
        }

        parent::end_el( $output, $item, $depth, $args );
    }


    // ── Hilfsmethoden ──────────────────────────────────────────────────────────

    /**
     * Prüft ob ein Navigations-Label ein Bild-Item ist.
     * Erkennt: <img class="mega-menu__img" ...>
     */
    private function is_image_item( string $title ): bool {
        return str_contains( $title, 'mega-menu__img' )
            || ( str_contains( $title, '<img' ) && str_contains( $title, 'mega-menu' ) );
    }
}

endif;
