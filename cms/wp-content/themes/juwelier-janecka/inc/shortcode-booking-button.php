<?php
/**
 * Shortcode: [janecka_booking_button]
 *
 * Attribute:
 *   location  – ID oder Slug des mlb_location-Eintrags (Pflicht)
 *   label     – Buttontext (optional, Default: "Termin vereinbaren")
 *   class     – Zusätzliche CSS-Klassen am Button (optional)
 *
 * Beispiel im Gutenberg-Shortcode-Block:
 *   [janecka_booking_button location="42" label="Jetzt Termin buchen"]
 *   [janecka_booking_button location="janecka-1060"]
 */

add_shortcode( 'janecka_booking_button', 'janecka_booking_button_shortcode' );

function janecka_booking_button_shortcode( $atts ) {

    $atts = shortcode_atts( [
        'location' => '',
        'label'    => __( 'Termin vereinbaren', 'juwelier-janecka' ),
        'class'    => '',
    ], $atts, 'janecka_booking_button' );

    // Kein location-Attribut → Standard aus Theme-Optionen
    if ( empty( $atts['location'] ) ) {
        $default = get_field( 'default_mlb_location', 'option' );
        if ( ! $default ) {
            return '<!-- janecka_booking_button: kein location-Attribut und kein Standard gesetzt -->';
        }
        $atts['location'] = $default;
    }

    // ID oder Slug auflösen
    $location_id = janecka_resolve_mlb_location_id( $atts['location'] );

    if ( ! $location_id ) {
        return '<!-- janecka_booking_button: location "' . esc_html( $atts['location'] ) . '" nicht gefunden -->';
    }

    // Modal-JS on-demand einbinden
    janecka_enqueue_booking_modal_assets();

    // Eindeutige Modal-ID pro Standort (mehrere Shortcodes auf einer Seite möglich)
    $modal_id = 'booking-modal-' . $location_id;

    $extra_class = $atts['class'] ? ' ' . esc_attr( $atts['class'] ) : '';

    ob_start(); ?>

    <div class="janecka-booking-trigger-wrap">
        <button
            class="btn btn--primary store-booking__trigger<?php echo $extra_class; ?>"
            data-modal-target="<?php echo esc_attr( $modal_id ); ?>"
            aria-haspopup="dialog"
        >
            <?php echo esc_html( $atts['label'] ); ?>
        </button>
    </div>

    <div
        class="store-booking-modal"
        id="<?php echo esc_attr( $modal_id ); ?>"
        role="dialog"
        aria-modal="true"
        aria-label="<?php echo esc_attr( $atts['label'] ); ?>"
        data-location-id="<?php echo esc_attr( $location_id ); ?>"
        hidden
    >
        <div class="store-booking-modal__backdrop"></div>
        <div class="store-booking-modal__dialog">
            <button
                class="store-booking-modal__close"
                aria-label="<?php esc_attr_e( 'Schließen', 'juwelier-janecka' ); ?>"
            >
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2"
                     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="store-booking-modal__content">
                <?php echo do_shortcode( '[mlb_booking_form]' );?>
            </div>
        </div>
    </div>

    <?php return ob_get_clean();
}


/**
 * Löst einen mlb_location-Slug oder eine ID in eine Post-ID auf.
 */
function janecka_resolve_mlb_location_id( $location ) {

    // Numerisch → direkt als ID verwenden (kurze Prüfung ob der Post existiert)
    if ( is_numeric( $location ) ) {
        return get_post_status( (int) $location ) ? (int) $location : null;
    }

    // Slug → per WP_Query auflösen
    $query = new WP_Query( [
        'post_type'      => 'mlb_location',
        'name'           => sanitize_title( $location ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ] );

    return ! empty( $query->posts ) ? $query->posts[0] : null;
}


/**
 * Enqueued booking-modal.js einmalig pro Seitenaufruf.
 */
function janecka_enqueue_booking_modal_assets() {
    static $enqueued = false;
    if ( $enqueued ) return;
    $enqueued = true;

    wp_enqueue_script(
        'janecka-booking-modal',
        get_template_directory_uri() . '/assets/dist/js/booking-modal.js',
        [],
        wp_get_theme()->get( 'Version' ),
        true // footer
    );
}


// ── Options-Seite + Feld registrieren ────────────────────────────────────────

add_action( 'init', 'janecka_register_booking_options_page' );

function janecka_register_booking_options_page() {
    if ( ! function_exists( 'acf_add_options_page' ) ) return;

    acf_add_options_page( [
        'page_title'  => __( 'Buchungs-Einstellungen', 'juwelier-janecka' ),
        'menu_title'  => __( 'Buchungs-Einstellungen', 'juwelier-janecka' ),
        'menu_slug'   => 'janecka-booking-settings',
        'parent_slug' => 'options-general.php',
        'capability'  => 'manage_options',
    ] );
}

add_action( 'acf/include_fields', 'janecka_register_booking_options_field' );

function janecka_register_booking_options_field() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

    acf_add_local_field_group( [
        'key'    => 'group_booking_options',
        'title'  => __( 'Buchungs-Einstellungen', 'juwelier-janecka' ),
        'fields' => [
            [
                'key'           => 'field_default_mlb_location',
                'label'         => __( 'Standard-Buchungsstandort', 'juwelier-janecka' ),
                'name'          => 'default_mlb_location',
                'type'          => 'post_object',
                'post_type'     => [ 'mlb_location' ],
                'return_format' => 'id',
                'ui'            => 1,
                'allow_null'    => 1,
                'instructions'  => __( 'Wird verwendet wenn der Shortcode kein location-Attribut hat.', 'juwelier-janecka' ),
            ],
        ],
        'location' => [
            [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'janecka-booking-settings' ] ],
        ],
    ] );
}