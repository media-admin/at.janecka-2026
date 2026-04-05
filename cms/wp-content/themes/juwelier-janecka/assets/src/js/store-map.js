/**
 * store-map.js
 * Google Maps Initialisierung für Filial-Seiten (CPT: stores).
 *
 * Ausgelagert aus dem alten Inline-Script im PHP-Template.
 * Wird nur auf is_singular('stores') geladen (siehe functions.php).
 */

( function () {
	'use strict';

	/**
	 * Initialisiert eine Google Map für ein gegebenes .acf-map Element.
	 *
	 * @param {HTMLElement} el
	 * @returns {google.maps.Map}
	 */
	function initMap( el ) {
		const zoom    = parseInt( el.dataset.zoom, 10 ) || 16;
		const markers = el.querySelectorAll( '.marker' );

		const map = new google.maps.Map( el, {
			zoom,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			mapId: el.dataset.mapId || '',
		} );

		map.markers = [];

		markers.forEach( function ( markerEl ) {
			initMarker( markerEl, map );
		} );

		centerMap( map );

		return map;
	}

	/**
	 * Erstellt einen Marker für ein .marker-Element.
	 *
	 * @param {HTMLElement} markerEl
	 * @param {google.maps.Map} map
	 */
	function initMarker( markerEl, map ) {
    const lat    = parseFloat( markerEl.dataset.lat );
    const lng    = parseFloat( markerEl.dataset.lng );
    const latLng = { lat, lng };

    const marker = new google.maps.Marker( {
        position: latLng,
        map,
    } );

    map.markers.push( marker );

    const content = markerEl.innerHTML.trim();
    if ( content ) {
        const infowindow = new google.maps.InfoWindow( { content } );
        marker.addListener( 'click', function () {
            infowindow.open( map, marker );
        } );
    }
}

	/**
	 * Zentriert die Map so, dass alle Marker sichtbar sind.
	 *
	 * @param {google.maps.Map} map
	 */
	function centerMap( map ) {
		const bounds = new google.maps.LatLngBounds();

		map.markers.forEach( function ( marker ) {
			const pos = marker.position;
			bounds.extend( {
				lat: typeof pos.lat === 'function' ? pos.lat() : pos.lat,
				lng: typeof pos.lng === 'function' ? pos.lng() : pos.lng,
			} );
		} );

		if ( map.markers.length === 1 ) {
			map.setCenter( bounds.getCenter() );
		} else {
			map.fitBounds( bounds );
		}
	}

	// Warte bis google.maps geladen ist, dann initialisieren
	function waitForGoogleMaps( callback ) {
		if ( typeof google !== 'undefined' && google.maps && google.maps.Map ) {
			callback();
		} else {
			setTimeout( function () {
				waitForGoogleMaps( callback );
			}, 50 );
		}
	}


	document.addEventListener( 'DOMContentLoaded', function () {
		const mapEls = document.querySelectorAll( '.acf-map' );
		if ( ! mapEls.length ) return;

		waitForGoogleMaps( function () {
			mapEls.forEach( function ( el ) {
				initMap( el );
			} );
		} );
	} );

} )();
