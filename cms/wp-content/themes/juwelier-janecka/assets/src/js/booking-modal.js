/**
 * booking-modal.js
 * Universelles Modal für das MLB-Buchungsformular.
 * Wird on-demand geladen wenn der [janecka_booking_button]-Shortcode
 * auf der Seite vorhanden ist.
 *
 * Unterstützt mehrere Modals pro Seite (ein Modal pro Standort).
 *
 * v1.1.0:
 *   - Hash-Handling: #booking in der URL öffnet das Modal automatisch
 *   - Service-Preset: ?service=<Name> wählt die Dienstleistung vor
 *     (funktioniert zusammen mit booking-form.js data-preset Mechanismus)
 */
( function () {
    'use strict';

    function initBookingModals() {
        const triggers = document.querySelectorAll( '.store-booking__trigger[data-modal-target]' );
        if ( ! triggers.length ) return;

        triggers.forEach( function ( trigger ) {
            const modalId = trigger.dataset.modalTarget;
            const modal   = document.getElementById( modalId );
            if ( ! modal ) return;

            const backdrop = modal.querySelector( '.store-booking-modal__backdrop' );
            const closeBtn = modal.querySelector( '.store-booking-modal__close' );

            function openModal() {
                modal.removeAttribute( 'hidden' );
                document.body.classList.add( 'modal-open' );

                // Standort im Dropdown vorauswählen
                const locationId = modal.dataset.locationId;
                if ( locationId ) {
                    const select = modal.querySelector( 'select[name="location"], select[name*="location"]' );
                    if ( select ) {
                        select.value = locationId;
                        select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
                    }
                }

                // Service-Preset via URL-Parameter ?service=<Name>
                // Wird als data-preset auf .mlb-service-select gesetzt —
                // booking-form.js liest es nach populateServices() aus.
                const params      = new URLSearchParams( window.location.search );
                const presetService = params.get( 'service' );
                if ( presetService ) {
                    const serviceSel = modal.querySelector( '.mlb-service-select' );
                    if ( serviceSel ) {
                        serviceSel.dataset.preset = presetService;
                    }
                }

                if ( closeBtn ) closeBtn.focus();
            }

            function closeModal() {
                modal.setAttribute( 'hidden', '' );
                const anyOpen = document.querySelector( '.store-booking-modal:not([hidden])' );
                if ( ! anyOpen ) {
                    document.body.classList.remove( 'modal-open' );
                }
            }

            trigger.addEventListener( 'click', openModal );
            if ( closeBtn )  closeBtn.addEventListener( 'click', closeModal );
            if ( backdrop )  backdrop.addEventListener( 'click', closeModal );

            // Hash-Handling: #booking in der URL öffnet das erste Modal automatisch
            // Nützlich für vorverlinkte Menüpunkte wie:
            // /filialen/janecka-1140/?service=Uhrenservice#booking
            if ( window.location.hash === '#booking' ) {
                // Nur das erste Trigger-Modal öffnen (erster Standort auf der Seite)
                const firstTrigger = document.querySelector( '.store-booking__trigger[data-modal-target]' );
                if ( firstTrigger && firstTrigger === trigger ) {
                    openModal();
                }
            }
        } );

        // Escape schließt das oberste offene Modal
        document.addEventListener( 'keydown', function ( e ) {
            if ( e.key !== 'Escape' ) return;
            const openModal = document.querySelector( '.store-booking-modal:not([hidden])' );
            if ( openModal ) {
                openModal.setAttribute( 'hidden', '' );
                const anyOpen = document.querySelector( '.store-booking-modal:not([hidden])' );
                if ( ! anyOpen ) document.body.classList.remove( 'modal-open' );
            }
        } );
    }

    document.addEventListener( 'DOMContentLoaded', initBookingModals );
} )();
