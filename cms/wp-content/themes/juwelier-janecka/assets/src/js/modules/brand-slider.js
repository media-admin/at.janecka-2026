/**
 * Brand Slider Initialisierung
 *
 * Initialisiert alle .brand-slider-Elemente auf der Seite via Swiper.
 * Wird in der main JS (z.B. main.js) importiert:
 *
 *   import { initBrandSliders } from './modules/brand-slider.js';
 *   initBrandSliders();
 *
 * @module brand-slider
 */

import Swiper from 'swiper';
import { Navigation, Autoplay } from 'swiper/modules';

export function initBrandSliders() {
	const sliders = document.querySelectorAll( '.brand-slider' );

	if ( ! sliders.length ) return;

	sliders.forEach( ( el ) => {
		// Nicht doppelt initialisieren
		if ( el.swiper ) return;

		const wrapper = el.closest( '.brand-slider-wrapper' );
		if ( ! wrapper ) return;

		const autoplayDelay = parseInt( el.dataset.autoplay || '0', 10 );

		new Swiper( el, {
			modules: [ Navigation, ...(autoplayDelay > 0 ? [ Autoplay ] : []) ],
			slidesPerView: 2,
			spaceBetween: 24,
			loop: true,
			loopAdditionalSlides: 3,
			...(autoplayDelay > 0 && {
				autoplay: {
					delay: autoplayDelay,
					disableOnInteraction: false,
					pauseOnMouseEnter: true,
				},
			}),
			navigation: {
				prevEl: wrapper.querySelector( '.brand-slider__prev' ),
				nextEl: wrapper.querySelector( '.brand-slider__next' ),
			},
			breakpoints: {
				480: { slidesPerView: 3, spaceBetween: 24 },
				768: { slidesPerView: 4, spaceBetween: 32 },
				1024: { slidesPerView: 4, spaceBetween: 40 },
				1280: { slidesPerView: 4, spaceBetween: 48 },
			},
		} );
	} );
}
