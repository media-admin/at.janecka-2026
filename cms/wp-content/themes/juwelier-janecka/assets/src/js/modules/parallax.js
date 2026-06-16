/**
 * parallax.js
 * Leichter Scroll-Parallax für Blöcke mit der CSS-Klasse "parallax-clip".
 *
 * Effekt: Der Block selbst scrollt etwas langsamer als der Rest der Seite —
 * ähnlich wie der Store-Finder-Effekt auf nanis.it.
 *
 * Funktioniert mit jedem Block-Typ (Columns, Cover, Gruppe etc.)
 * Auf Mobile (< 768px) deaktiviert.
 */

export function initParallax() {
    if ( window.matchMedia( '(max-width: 767.98px)' ).matches ) return;

    const blocks = document.querySelectorAll( '.parallax-clip' );
    if ( ! blocks.length ) return;

    // Stärke des Effekts: je höher, desto mehr Versatz (in px)
    const STRENGTH = 80;

    function update() {
        const vh = window.innerHeight;

        blocks.forEach( el => {
            const rect     = el.getBoundingClientRect();

            // Nur rendern wenn der Block im oder nahe am Viewport ist
            if ( rect.bottom < -200 || rect.top > vh + 200 ) return;

            // Fortschritt: -1 (Block komplett unten) bis +1 (Block komplett oben)
            // 0 = Block-Mitte befindet sich genau in der Viewport-Mitte
            const blockCenter   = rect.top + rect.height / 2;
            const viewportCenter = vh / 2;
            const progress      = ( blockCenter - viewportCenter ) / ( vh / 2 );

            // Clamp auf -1 bis 1
            const clamped = Math.max( -1, Math.min( 1, progress ) );
            const offset  = clamped * STRENGTH;

            el.style.transform  = `translateY(${ offset }px)`;
            el.style.willChange = 'transform';
        } );
    }

    // Initial ausführen
    update();

    // Scroll-Event: passive für bessere Performance
    window.addEventListener( 'scroll', update, { passive: true } );

    // Bei Resize neu initialisieren (Breakpoint-Check)
    window.addEventListener( 'resize', () => {
        if ( window.matchMedia( '(max-width: 767.98px)' ).matches ) {
            blocks.forEach( el => { el.style.transform = ''; } );
            window.removeEventListener( 'scroll', update );
        }
    } );
}
