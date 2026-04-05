/**
 * booking-modal.js
 * Universelles Modal für das MLB-Buchungsformular.
 * Wird on-demand geladen wenn der [janecka_booking_button]-Shortcode
 * auf der Seite vorhanden ist.
 *
 * Unterstützt mehrere Modals pro Seite (ein Modal pro Standort).
 */

( function () {
    'use strict';

    function initBookingModals() {

        // Alle Trigger-Buttons auf der Seite
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
                        // Change-Event feuern damit das Plugin reagiert (Slots laden etc.)
                        select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
                    }
                }

                if ( closeBtn ) closeBtn.focus();
            }

            function closeModal() {
                modal.setAttribute( 'hidden', '' );
                // Nur body-Klasse entfernen wenn kein anderes Modal offen ist
                const anyOpen = document.querySelector( '.store-booking-modal:not([hidden])' );
                if ( ! anyOpen ) {
                    document.body.classList.remove( 'modal-open' );
                }
            }

            trigger.addEventListener( 'click', openModal );

            if ( closeBtn )  closeBtn.addEventListener( 'click', closeModal );
            if ( backdrop )  backdrop.addEventListener( 'click', closeModal );
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