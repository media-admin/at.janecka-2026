<?php
/**
 * Custom Walker für das Footer-Accordion (Mobile)
 */
class Janecka_Walker_Footer_Accordion extends Walker_Nav_Menu {

    /**
     * Top-Level-Item: Toggle-Button wenn Kinder vorhanden, sonst direkter Link
     */
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item = $data_object;

        if ( $depth === 0 ) {
            $has_children = in_array( 'menu-item-has-children', $item->classes );

            if ( $has_children ) {
                $output .= '<button class="footer-nav__toggle" type="button" aria-expanded="false">';
                $output .= esc_html( $item->title );
                $output .= '<span class="footer-nav__chevron" aria-hidden="true"></span>';
                $output .= '</button>';
                $output .= '<ul class="footer-nav__submenu">';
            } else {
                $output .= '<a class="footer-nav__link" href="' . esc_url( $item->url ) . '">';
                $output .= esc_html( $item->title );
                $output .= '</a>';
            }

        } elseif ( $depth === 1 ) {
            $output .= '<li>';
            $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
            $output .= '</li>';
        }
    }

    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        if ( $depth === 1 ) {
            // li wird in start_el geschlossen
        }
    }

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        // start_lvl wird nur für depth=1 aufgerufen, die <ul> öffnen wir in start_el
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        if ( $depth === 0 ) {
            $output .= '</ul>'; // Schließt footer-nav__submenu
        }
    }

    public function start_el_wrapper( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {}

    /**
     * Wrapper-Li
     */
    public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
        $id = $element->ID;
        $has_children = ! empty( $children_elements[ $id ] );

        if ( $has_children ) {
            $element->classes[] = 'menu-item-has-children';
        }

        $cb_args = array_merge( [ &$output ], $args );

        $output .= '<li class="footer-nav__item">';
        parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
        $output .= '</li>';
    }
}