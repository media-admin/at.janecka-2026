/**
 * Google Map – Cookie Consent Integration
 * Datei: assets/src/js/components/google-map.js
 *
 * Aufgaben:
 *  1. Beim Seitenload: Prüfe ob Komfort-Consent bereits vorliegt
 *     → Ja: Lade alle Karten sofort
 *     → Nein: Zeige Placeholder, warte auf cookies:changed Event
 *
 *  2. Auf cookies:changed Event hören:
 *     → Wenn comfort=true: Lade Karten nach (ohne Reload)
 *     → Wenn comfort=false: Tausche Karten gegen Placeholder zurück
 *
 *  3. Button-Interaktionen im Placeholder:
 *     → "Karte anzeigen & Cookies akzeptieren": Akzeptiert Komfort-Kategorie direkt
 *     → "Cookie-Einstellungen anpassen": Öffnet das Cookie Modal
 *
 * Dieses Modul ist bewusst eigenständig – kein Import aus cookie-notice.js nötig.
 * Es kommuniziert ausschließlich über window.CookieConsent (Public API) und
 * das cookies:changed DOM-Event.
 */

const GoogleMapConsent = {

    /**
     * Alle .google-map Wrapper auf der aktuellen Seite.
     * @type {NodeListOf<HTMLElement>}
     */
    maps: null,

    // ── Init ──────────────────────────────────────────────────────────────────

    init() {
        this.maps = document.querySelectorAll('.google-map');

        if ( ! this.maps.length ) return;

        // Initialen Consent-Status prüfen und Karten entsprechend rendern
        this._applyConsentState();

        // Auf zukünftige Consent-Änderungen hören (cookies:changed = custom Event
        // aus cookie-notice.js, wird bei jeder Consent-Änderung gefeuert)
        document.addEventListener('cookies:changed', (event) => {
            this._handleConsentChange(event.detail);
        });

        // Placeholder-Buttons delegiert am document-Level binden
        document.addEventListener('click', (event) => {
            // Button: "Karte anzeigen & Cookies akzeptieren"
            if ( event.target.closest('[data-map-accept-comfort]') ) {
                this._acceptComfort();
            }

            // Button: "Cookie-Einstellungen anpassen"
            if ( event.target.closest('[data-map-open-settings]') ) {
                this._openSettings();
            }
        });
    },

    // ── Consent-Status auslesen ───────────────────────────────────────────────

    /**
     * Prüft ob Komfort-Cookies akzeptiert wurden.
     * Nutzt die Public API von CookieConsent (window.CookieConsent.hasConsent).
     *
     * @returns {boolean}
     */
    _hasComfortConsent() {
        if ( window.CookieConsent && typeof window.CookieConsent.hasConsent === 'function' ) {
            return window.CookieConsent.hasConsent('comfort');
        }
        return false;
    },

    // ── Karten laden / entladen ───────────────────────────────────────────────

    /**
     * Wendet den aktuellen Consent-Status auf alle Karten an.
     * Wird beim Init und nach cookies:changed aufgerufen.
     */
    _applyConsentState() {
        const hasConsent = this._hasComfortConsent();

        this.maps.forEach( (mapEl) => {
            if ( hasConsent ) {
                this._loadMap(mapEl);
            } else {
                this._showPlaceholder(mapEl);
            }
        });
    },

    /**
     * Lädt die Karte: Setzt das src-Attribut des iframes, blendet
     * den Placeholder aus und den iframe ein.
     *
     * @param {HTMLElement} mapEl – der .google-map Wrapper
     */
    _loadMap(mapEl) {
        const iframe      = mapEl.querySelector('.google-map__iframe');
        const placeholder = mapEl.querySelector('.google-map__placeholder');

        if ( ! iframe ) return;

        // src nur setzen wenn noch nicht geladen (Performance)
        if ( ! iframe.src || iframe.src === window.location.href ) {
            const dataSrc = iframe.dataset.src || mapEl.dataset.mapSrc;
            if ( dataSrc ) {
                iframe.src = dataSrc;
            }
        }

        // Einblenden
        iframe.removeAttribute('hidden');
        iframe.classList.add('is-loaded');

        // Placeholder ausblenden
        if ( placeholder ) {
            placeholder.classList.add('is-hidden');
            placeholder.setAttribute('aria-hidden', 'true');
        }
    },

    /**
     * Zeigt den Placeholder wieder an und entlädt das iframe.
     * Wird aufgerufen wenn der Besucher Komfort-Cookies widerruft.
     *
     * @param {HTMLElement} mapEl – der .google-map Wrapper
     */
    _showPlaceholder(mapEl) {
        const iframe      = mapEl.querySelector('.google-map__iframe');
        const placeholder = mapEl.querySelector('.google-map__placeholder');

        // iframe entladen (src leeren verhindert weiteres Tracking)
        if ( iframe ) {
            iframe.src = '';
            iframe.setAttribute('hidden', '');
            iframe.classList.remove('is-loaded');
        }

        // Placeholder einblenden
        if ( placeholder ) {
            placeholder.classList.remove('is-hidden');
            placeholder.removeAttribute('aria-hidden');
        }
    },

    // ── Event Handler ─────────────────────────────────────────────────────────

    /**
     * Reagiert auf cookies:changed – aktiviert oder deaktiviert Karten.
     *
     * @param {Object} detail – { necessary, statistics, marketing, comfort }
     */
    _handleConsentChange(detail) {
        if ( ! detail ) return;

        this.maps.forEach( (mapEl) => {
            if ( detail.comfort ) {
                this._loadMap(mapEl);
            } else {
                this._showPlaceholder(mapEl);
            }
        });
    },

    /**
     * "Karte anzeigen & Cookies akzeptieren":
     * Akzeptiert die Komfort-Kategorie direkt über die CookieConsent API.
     * Das cookies:changed Event aus cookie-notice.js übernimmt den Rest.
     */
    _acceptComfort() {
        if ( window.CookieConsent && typeof window.CookieConsent.acceptCategory === 'function' ) {
            // Speichert Komfort-Consent und feuert cookies:changed
            window.CookieConsent.acceptCategory('comfort');
        } else {
            // Fallback: Seite neu laden (sollte nicht vorkommen)
            console.warn('[GoogleMap] CookieConsent.acceptCategory nicht verfügbar.');
        }
    },

    /**
     * Öffnet das Cookie-Settings Modal über die CookieConsent Public API.
     */
    _openSettings() {
        if ( window.CookieConsent && typeof window.CookieConsent.openSettings === 'function' ) {
            window.CookieConsent.openSettings();
        }
    },
};

export default GoogleMapConsent;
