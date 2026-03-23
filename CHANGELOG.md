# Changelog

Alle wesentlichen Änderungen werden in dieser Datei dokumentiert.
Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
Versionierung nach [Semantic Versioning](https://semver.org/lang/de/).

---

## [1.13.1] - 2026-03-09

### Dokumentation & Testing

#### Added
- **`07_TROUBLESHOOTING.md`** – aktualisiert auf v1.13.0:
  - Navigation 4-Ebenen, Cookie Consent (Banner + Code-Snippets)
  - Hero Image, Breadcrumbs, hCaptcha (Widget + Validierung)
  - Back-to-Top, Scroll Progress Bar
  - Build-Fehler: Top-level await, BrowserTracing
  - PHP Deprecated-Warnings (ACF message-Felder)
- **`11_TESTING.md`** – aktualisiert auf v1.13.0:
  - Neue Testfälle: Hero Image (3), Breadcrumbs (2), hCaptcha (3),
    Back-to-Top (2), Scroll Progress (2), WooCommerce (1) → 35 Tests total
  - Manuelle Checklisten für alle Features
  - **BackstopJS Visual Regression Testing** – vollständige Einrichtung:
    14 Szenarien, 4 Viewports, Dark-Mode-Test, Cookie-Banner-Test
- **`backstop.json`** – neue Konfigurationsdatei für Visual Regression Tests
- **`backstop_data/engine_scripts/`** – Playwright-Hooks:
  `onBefore.js` (State-Reset), `onReady.js` (Animationen einfrieren),
  `setDarkMode.js` (Dark-Mode-Aktivierung)

#### Changed
- **`package.json`** – neue npm Scripts:
  `vrt:ref`, `vrt:test`, `vrt:approve`, `vrt:report`
  + `backstopjs` als devDependency (`^6.3.25`)
- **`.gitignore`** – BackstopJS Test-Ausgaben ausgeschlossen
  (nur `bitmaps_reference` wird committed)

---

## [1.17.0] - 2026-03-10

### Versionen
- custom-theme: 1.13.0 (unverändert)
- agency-core: 1.6.0
- media-lab-seo: 1.3.0 (unverändert)

### Added – Gutenberg Custom Blocks (8 Blöcke)

**Architektur:**
- ACF Blocks (PHP-Rendering, kein Build-Step): Hero, Testimonial, Team-Mitglied, Logo-Leiste, Logo-Slider
- Native Blocks (JS/block.json via Vite): CTA-Banner, Accordion/FAQ, Icon+Text
- Alle Blöcke unter Gutenberg-Kategorie `design`

**`inc/blocks.php`** (neu) – zentrale Registrierung:
- `medialab_register_acf_blocks()` – ACF-Blöcke via `acf/init`
- `medialab_register_native_blocks()` – Native Blöcke via `init`
- `medialab_enqueue_block_editor_assets()` – Editor-CSS + blocks.js
- `medialab_enqueue_block_frontend_assets()` – Frontend-CSS, Accordion-JS, Logo-Slider-JS (je nur bei `has_block()`)

**ACF Blocks:**
- `blocks/hero/` – Hintergrundbild, Overlay, Kicker, Titel, Subtitle, 2× CTA-Button, Höhe (full/large/medium), Ausrichtung (left/center/right)
- `blocks/testimonial/` – Zitat, Name, Rolle, Bild, Sterne-Rating (1–5), Stil (card/minimal/centered)
- `blocks/team-member/` – Foto, Name, Rolle, Bio, E-Mail, LinkedIn, Xing, Instagram
- `blocks/logo-grid/` – Repeater: Logo + Link, Spalten (3–6), Graustufen-Toggle
- `blocks/logo-slider/` – Repeater: Logo + Link, Swiper Autoplay/Loop/Speed, Graustufen

**Native Blocks (JS/block.json):**
- `blocks/cta-banner/` – RichText Titel + Text, Button-URL, Button-Stil, Hintergrundfarbe (primary/dark/light/white), Align (full/wide)
- `blocks/accordion/` – RichText Titel, Allow-Multiple Toggle; Frontend: natives `<details>/<summary>` + ARIA
- `blocks/icon-text/` – Icon (Emoji/Dashicon), Farbe, Layout (top/left), RichText Titel + Text

**`assets/src/js/blocks.js`** – Native Block Registrierungen (kein JSX, wp.element.createElement)
**`assets/src/js/block-accordion.js`** – Frontend Accordion: ARIA, Multi-Toggle, schließt andere Items
**`assets/src/js/block-logo-slider.js`** – Swiper-Init aus data-swiper Attribut
**`assets/src/scss/blocks.scss`** – vollständiges Block-CSS (CSS Custom Properties, Responsive)

**`vite.config.js`** – neue Entry Points:
- `plugin-blocks` → `plugins/media-lab-agency-core/assets/dist/js/blocks.js`
- `plugin-block-accordion` → `.../block-accordion.js`
- `plugin-block-logo-slider` → `.../block-logo-slider.js`

---

## [1.16.0] - 2026-03-10

### Versionen
- custom-theme: 1.13.0
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – WooCommerce E-Mail-Templates

**Template-Overrides** (`woocommerce/emails/`):
- `email-header.php` – Custom Header: Logo/Markenname, Primärfarbe, E-Mail-Betreff-Zeile
- `email-footer.php` – Custom Footer: Links (Datenschutz, Impressum, Kontakt), Footer-Text
- `email-styles.php` – Inline-CSS: Typografie, Bestelltabelle, Adress-Boxen, Button, Responsive
- `customer-processing-order.php` – Bestellbestätigung mit Info-Box + „Bestellung ansehen" CTA
- `customer-completed-order.php` – Abgeschlossen-Mail mit optionalem Bewertungs-CTA
- `customer-invoice.php` – Rechnung/Zahlungsaufforderung (zahlen oder ansehen, je nach Status)
- `customer-new-account.php` – Willkommens-Mail mit Zugangsdaten-Box + „Zum Kundenkonto" CTA
- `customer-reset-password.php` – Passwort-Reset mit sicherem Link-Button + Ablauf-Hinweis

**`inc/woocommerce-emails.php`** (neu) – globale Konfiguration:
- `woocommerce_email_content_type` → HTML erzwingen
- `wp_mail_from_name` / `wp_mail_from` → Absender aus WC-Einstellungen
- `woocommerce_email_styles` → Theme-CSS aus `email-styles.php` einbinden
- `customtheme_init_email_colors()` → WC-Farboptionen bei Theme-Aktivierung initialisieren
- `woocommerce_email_before_order_table` → Admin-Bestelllink in Admin-Benachrichtigung
- Betreff-Prefix via Filter `customtheme_email_prefix_subject`

**Konfigurierbare Filter:**
- `customtheme_email_primary_color` – Primärfarbe (Standard: WC-Option)
- `customtheme_email_logo_url` – Logo-URL (Standard: WC-Option)
- `customtheme_email_footer_links` – Footer-Link-Array
- `customtheme_email_show_review_cta` – Bewertungs-CTA in Abgeschlossen-Mail (bool)
- `customtheme_email_prefix_subject` – Site-Name-Prefix im Betreff (bool, Standard: false)
- `customtheme_email_content_type` – Content-Type (Standard: text/html)

---

## [1.15.0] - 2026-03-10

### Versionen
- custom-theme: 1.12.0
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – Core Web Vitals Performance-Optimierungen

**`inc/performance.php`** (neu) – zentrales Performance-Modul:

**LCP (Largest Contentful Paint):**
- `customtheme_preload_lcp_image()` – `<link rel="preload" fetchpriority="high">` für Hero-Bild (ACF-Feld → Featured Image → Filter)
- Responsive Preload: `imagesrcset` + `imagesizes` im Preload-Tag
- `customtheme_fetchpriority_first_image()` – `fetchpriority="high" loading="eager"` auf erstem Content-Bild (für Seiten ohne Hero)
- `customtheme_inline_critical_css()` – inline Critical CSS aus `assets/dist/css/critical.css` (wenn vorhanden)
- `customtheme_nonblocking_css()` – Haupt-CSS auf `media="print"` + onload-Swap (non-blocking, wenn critical.css vorhanden)

**CLS (Cumulative Layout Shift):**
- `customtheme_enforce_image_dimensions()` – erzwingt `width`/`height` auf allen `wp_get_attachment_image()`-Bildern
- `customtheme_add_image_dimensions_to_content()` – ergänzt `width`/`height` in `the_content`-Bildern
- `customtheme_fonts_display_swap()` – fügt `display=swap` zu Google Fonts URLs hinzu
- `customtheme_preload_fonts()` – `<link rel="preload">` für self-hosted Fonts (via Filter `customtheme_preload_fonts`)

**INP (Interaction to Next Paint):**
- `customtheme_defer_scripts()` – `defer` auf CF7, WooCommerce-Frontend und weitere Plugin-Scripts
- Filter: `customtheme_defer_scripts` (erweitern) + `customtheme_exclude_defer_scripts` (ausschließen)
- Heartbeat-Interval im Frontend: 15s → 60s
- `rest_output_link_wp_head` + `wp_oembed_add_host_js` aus wp_head entfernt

**Responsive Images:**
- `customtheme_webp_picture_element()` – `<picture>` + `<source type="image/webp">` (opt-in via Filter `customtheme_enable_picture_webp`)
- `max_srcset_image_width` erweitert: 1600 → 2560px (Retina)
- Lazy Loading für alle Thumbnails via `wp_lazy_loading_enabled`
- LCP-Bild bekommt `loading="eager"` (kein Lazy Loading)

**`inc/enqueue.php`** – Preconnect-Optimierung:
- Google Fonts Preconnect nur noch aktiv wenn tatsächlich eine Google Fonts URL eingebunden ist

**`vite.config.js`** – Brotli/Gzip Precompression:
- `vite-plugin-compression2` eingebunden (graceful fallback wenn nicht installiert)
- Brotli: `.br`-Dateien neben jedem Asset
- Gzip: `.gz`-Dateien als Fallback
- Installation: `npm install -D vite-plugin-compression2`

**`assets/src/scss/utilities/_helpers.scss`** – Aspect Ratio Utilities:
- `.ratio` Container-Klasse mit Varianten: `--16-9`, `--4-3`, `--3-2`, `--1-1`, `--9-16`, `--hero`, `--custom`
- `.aspect-video`, `.aspect-square`, `.aspect-photo` für direkte Verwendung auf `<img>`/`<video>`
- `.object-cover`, `.object-contain`, `.object-fill`, `.object-top/center/bottom`

**`functions.php`:**
- `performance.php` als fester require eingebunden

---

## [1.14.0] - 2026-03-10

### Versionen
- custom-theme: 1.11.0 (unverändert)
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – One-Click-Setup Script

**`bin/setup.sh`** – Bash/WP-CLI Setup (interaktiv + Config-File-Modus):
- Interaktiver Modus: geführte Eingabe aller Parameter
- Config-File-Modus: `--config bin/setup.yml` für automatisierten Einsatz
- `--dry-run`: zeigt alle Änderungen ohne sie auszuführen
- Schritt 1: Backup (DB-Export + wp-config.php)
- Schritt 2: Domain setzen (siteurl + home via WP-CLI)
- Schritt 3: DB-Präfix ändern (RENAME TABLE + usermeta/options Keys)
- Schritt 4: Search/Replace alte → neue Domain (--all-tables)
- Schritt 5: Admin-User anlegen oder Passwort aktualisieren
- Schritt 6: Theme kopieren + umbenennen (style.css, Text Domain in PHP)
- Schritt 7: Plugin-Prefix `medialab_` → Kundenpräfix (PHP + JS + DB)
- Schritt 8: Rewrite Rules flush, WP_DEBUG deaktivieren
- Farbiger Output, Log-File unter `bin/.setup.log`

**`bin/setup.php`** – Browser-Setup (für Hosting ohne SSH):
- Gleiches Feature-Set wie setup.sh
- Absicherung via Token-Parameter (`?token=GEHEIM`)
- Lock-File nach Setup (`bin/.setup-complete`) verhindert doppeltes Ausführen
- Dark-Mode UI mit Collapsible-Sektionen
- Admin-Zugangsdaten werden nach dem Setup angezeigt
- Serialisierungssichere Search/Replace-Implementierung
- Sicherheitshinweis: Datei nach Setup sofort löschen

**`bin/setup.example.yml`** – Config-Template:
- Vollständig kommentiert, alle Parameter dokumentiert
- Als `setup.yml` kopieren + anpassen
- `setup.yml` ist in `.gitignore` (keine Kundendaten im Repo)

**`bin/.gitignore`**:
- `setup.yml`, `.setup.log`, `.backups/`, `.setup-complete` ausgeschlossen

---

## [1.13.0] - 2026-03-09

### custom-theme 1.13.0

#### Added
- **`_scroll-progress.scss`** + **`scroll-progress.js`** – Scroll Progress Bar:
  - 3px Linie am oberen Viewport-Rand, Farbe `$color-primary`
  - Glow-Dot am rechten Ende
  - CSS Custom Property `--scroll-progress` (0%–100%), per `rAF` aktualisiert
  - ARIA `role="progressbar"` + `aria-valuenow`
  - `prefers-reduced-motion`: Glow-Dot ausgeblendet
  - Nur auf `single.php` + nur wenn ACF `scroll_progress_enabled` aktiv
- **`header.php`**: `<div class="scroll-progress">` – ACF-gesteuert + `is_single()`
- **`style.scss`**: `@use 'components/scroll-progress'` registriert

#### Changed
- **`_back-to-top.scss`** – vollständig überarbeitet:
  - SVG-Pfeil (Chevron) statt Text-Zeichen
  - Hover: `translateY(-3px)` + `$color-primary-dark`
  - `focus-visible` Ring für Keyboard-Navigation
  - Cookie-Banner-Abstandsregel (`.cookie-notice-visible`)
- **`back-to-top.js`** – neu geschrieben:
  - Arbeitet mit PHP-gerendtertem Element (kein `createElement` mehr)
  - `rAF`-Throttling statt direktem Scroll-Handler
  - Keyboard-Support (Enter / Space)
  - Focus-Rückgabe nach Scroll auf erstes fokussierbares Element
- **`footer.php`**: `<button class="back-to-top">` – ACF-gesteuert (`btt_enabled`)
- **`main.js`**: BackToTop + ScrollProgress als statische Imports,
  Initialisierung nur wenn DOM-Element vorhanden

#### Fixed
- **`sentry.js`**: `new Sentry.BrowserTracing()` → `Sentry.browserTracingIntegration()`
  (API-Änderung in `@sentry/browser` v8)

### media-lab-agency-core 1.6.0 (ACF)

#### Added
- **`field_btt_enabled`** (true_false, default: 1) – Back-to-Top Button
- **`field_scroll_progress_enabled`** (true_false, default: 0) – Scroll Progress Bar
  → beide in Logo / Globale Einstellungen unter „UI-Features"

---

## [1.17.0] - 2026-03-10

### Versionen
- custom-theme: 1.13.0 (unverändert)
- agency-core: 1.6.0
- media-lab-seo: 1.3.0 (unverändert)

### Added – Gutenberg Custom Blocks (8 Blöcke)

**Architektur:**
- ACF Blocks (PHP-Rendering, kein Build-Step): Hero, Testimonial, Team-Mitglied, Logo-Leiste, Logo-Slider
- Native Blocks (JS/block.json via Vite): CTA-Banner, Accordion/FAQ, Icon+Text
- Alle Blöcke unter Gutenberg-Kategorie `design`

**`inc/blocks.php`** (neu) – zentrale Registrierung:
- `medialab_register_acf_blocks()` – ACF-Blöcke via `acf/init`
- `medialab_register_native_blocks()` – Native Blöcke via `init`
- `medialab_enqueue_block_editor_assets()` – Editor-CSS + blocks.js
- `medialab_enqueue_block_frontend_assets()` – Frontend-CSS, Accordion-JS, Logo-Slider-JS (je nur bei `has_block()`)

**ACF Blocks:**
- `blocks/hero/` – Hintergrundbild, Overlay, Kicker, Titel, Subtitle, 2× CTA-Button, Höhe (full/large/medium), Ausrichtung (left/center/right)
- `blocks/testimonial/` – Zitat, Name, Rolle, Bild, Sterne-Rating (1–5), Stil (card/minimal/centered)
- `blocks/team-member/` – Foto, Name, Rolle, Bio, E-Mail, LinkedIn, Xing, Instagram
- `blocks/logo-grid/` – Repeater: Logo + Link, Spalten (3–6), Graustufen-Toggle
- `blocks/logo-slider/` – Repeater: Logo + Link, Swiper Autoplay/Loop/Speed, Graustufen

**Native Blocks (JS/block.json):**
- `blocks/cta-banner/` – RichText Titel + Text, Button-URL, Button-Stil, Hintergrundfarbe (primary/dark/light/white), Align (full/wide)
- `blocks/accordion/` – RichText Titel, Allow-Multiple Toggle; Frontend: natives `<details>/<summary>` + ARIA
- `blocks/icon-text/` – Icon (Emoji/Dashicon), Farbe, Layout (top/left), RichText Titel + Text

**`assets/src/js/blocks.js`** – Native Block Registrierungen (kein JSX, wp.element.createElement)
**`assets/src/js/block-accordion.js`** – Frontend Accordion: ARIA, Multi-Toggle, schließt andere Items
**`assets/src/js/block-logo-slider.js`** – Swiper-Init aus data-swiper Attribut
**`assets/src/scss/blocks.scss`** – vollständiges Block-CSS (CSS Custom Properties, Responsive)

**`vite.config.js`** – neue Entry Points:
- `plugin-blocks` → `plugins/media-lab-agency-core/assets/dist/js/blocks.js`
- `plugin-block-accordion` → `.../block-accordion.js`
- `plugin-block-logo-slider` → `.../block-logo-slider.js`

---

## [1.16.0] - 2026-03-10

### Versionen
- custom-theme: 1.13.0
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – WooCommerce E-Mail-Templates

**Template-Overrides** (`woocommerce/emails/`):
- `email-header.php` – Custom Header: Logo/Markenname, Primärfarbe, E-Mail-Betreff-Zeile
- `email-footer.php` – Custom Footer: Links (Datenschutz, Impressum, Kontakt), Footer-Text
- `email-styles.php` – Inline-CSS: Typografie, Bestelltabelle, Adress-Boxen, Button, Responsive
- `customer-processing-order.php` – Bestellbestätigung mit Info-Box + „Bestellung ansehen" CTA
- `customer-completed-order.php` – Abgeschlossen-Mail mit optionalem Bewertungs-CTA
- `customer-invoice.php` – Rechnung/Zahlungsaufforderung (zahlen oder ansehen, je nach Status)
- `customer-new-account.php` – Willkommens-Mail mit Zugangsdaten-Box + „Zum Kundenkonto" CTA
- `customer-reset-password.php` – Passwort-Reset mit sicherem Link-Button + Ablauf-Hinweis

**`inc/woocommerce-emails.php`** (neu) – globale Konfiguration:
- `woocommerce_email_content_type` → HTML erzwingen
- `wp_mail_from_name` / `wp_mail_from` → Absender aus WC-Einstellungen
- `woocommerce_email_styles` → Theme-CSS aus `email-styles.php` einbinden
- `customtheme_init_email_colors()` → WC-Farboptionen bei Theme-Aktivierung initialisieren
- `woocommerce_email_before_order_table` → Admin-Bestelllink in Admin-Benachrichtigung
- Betreff-Prefix via Filter `customtheme_email_prefix_subject`

**Konfigurierbare Filter:**
- `customtheme_email_primary_color` – Primärfarbe (Standard: WC-Option)
- `customtheme_email_logo_url` – Logo-URL (Standard: WC-Option)
- `customtheme_email_footer_links` – Footer-Link-Array
- `customtheme_email_show_review_cta` – Bewertungs-CTA in Abgeschlossen-Mail (bool)
- `customtheme_email_prefix_subject` – Site-Name-Prefix im Betreff (bool, Standard: false)
- `customtheme_email_content_type` – Content-Type (Standard: text/html)

---

## [1.15.0] - 2026-03-10

### Versionen
- custom-theme: 1.12.0
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – Core Web Vitals Performance-Optimierungen

**`inc/performance.php`** (neu) – zentrales Performance-Modul:

**LCP (Largest Contentful Paint):**
- `customtheme_preload_lcp_image()` – `<link rel="preload" fetchpriority="high">` für Hero-Bild (ACF-Feld → Featured Image → Filter)
- Responsive Preload: `imagesrcset` + `imagesizes` im Preload-Tag
- `customtheme_fetchpriority_first_image()` – `fetchpriority="high" loading="eager"` auf erstem Content-Bild (für Seiten ohne Hero)
- `customtheme_inline_critical_css()` – inline Critical CSS aus `assets/dist/css/critical.css` (wenn vorhanden)
- `customtheme_nonblocking_css()` – Haupt-CSS auf `media="print"` + onload-Swap (non-blocking, wenn critical.css vorhanden)

**CLS (Cumulative Layout Shift):**
- `customtheme_enforce_image_dimensions()` – erzwingt `width`/`height` auf allen `wp_get_attachment_image()`-Bildern
- `customtheme_add_image_dimensions_to_content()` – ergänzt `width`/`height` in `the_content`-Bildern
- `customtheme_fonts_display_swap()` – fügt `display=swap` zu Google Fonts URLs hinzu
- `customtheme_preload_fonts()` – `<link rel="preload">` für self-hosted Fonts (via Filter `customtheme_preload_fonts`)

**INP (Interaction to Next Paint):**
- `customtheme_defer_scripts()` – `defer` auf CF7, WooCommerce-Frontend und weitere Plugin-Scripts
- Filter: `customtheme_defer_scripts` (erweitern) + `customtheme_exclude_defer_scripts` (ausschließen)
- Heartbeat-Interval im Frontend: 15s → 60s
- `rest_output_link_wp_head` + `wp_oembed_add_host_js` aus wp_head entfernt

**Responsive Images:**
- `customtheme_webp_picture_element()` – `<picture>` + `<source type="image/webp">` (opt-in via Filter `customtheme_enable_picture_webp`)
- `max_srcset_image_width` erweitert: 1600 → 2560px (Retina)
- Lazy Loading für alle Thumbnails via `wp_lazy_loading_enabled`
- LCP-Bild bekommt `loading="eager"` (kein Lazy Loading)

**`inc/enqueue.php`** – Preconnect-Optimierung:
- Google Fonts Preconnect nur noch aktiv wenn tatsächlich eine Google Fonts URL eingebunden ist

**`vite.config.js`** – Brotli/Gzip Precompression:
- `vite-plugin-compression2` eingebunden (graceful fallback wenn nicht installiert)
- Brotli: `.br`-Dateien neben jedem Asset
- Gzip: `.gz`-Dateien als Fallback
- Installation: `npm install -D vite-plugin-compression2`

**`assets/src/scss/utilities/_helpers.scss`** – Aspect Ratio Utilities:
- `.ratio` Container-Klasse mit Varianten: `--16-9`, `--4-3`, `--3-2`, `--1-1`, `--9-16`, `--hero`, `--custom`
- `.aspect-video`, `.aspect-square`, `.aspect-photo` für direkte Verwendung auf `<img>`/`<video>`
- `.object-cover`, `.object-contain`, `.object-fill`, `.object-top/center/bottom`

**`functions.php`:**
- `performance.php` als fester require eingebunden

---

## [1.14.0] - 2026-03-10

### Versionen
- custom-theme: 1.11.0 (unverändert)
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – One-Click-Setup Script

**`bin/setup.sh`** – Bash/WP-CLI Setup (interaktiv + Config-File-Modus):
- Interaktiver Modus: geführte Eingabe aller Parameter
- Config-File-Modus: `--config bin/setup.yml` für automatisierten Einsatz
- `--dry-run`: zeigt alle Änderungen ohne sie auszuführen
- Schritt 1: Backup (DB-Export + wp-config.php)
- Schritt 2: Domain setzen (siteurl + home via WP-CLI)
- Schritt 3: DB-Präfix ändern (RENAME TABLE + usermeta/options Keys)
- Schritt 4: Search/Replace alte → neue Domain (--all-tables)
- Schritt 5: Admin-User anlegen oder Passwort aktualisieren
- Schritt 6: Theme kopieren + umbenennen (style.css, Text Domain in PHP)
- Schritt 7: Plugin-Prefix `medialab_` → Kundenpräfix (PHP + JS + DB)
- Schritt 8: Rewrite Rules flush, WP_DEBUG deaktivieren
- Farbiger Output, Log-File unter `bin/.setup.log`

**`bin/setup.php`** – Browser-Setup (für Hosting ohne SSH):
- Gleiches Feature-Set wie setup.sh
- Absicherung via Token-Parameter (`?token=GEHEIM`)
- Lock-File nach Setup (`bin/.setup-complete`) verhindert doppeltes Ausführen
- Dark-Mode UI mit Collapsible-Sektionen
- Admin-Zugangsdaten werden nach dem Setup angezeigt
- Serialisierungssichere Search/Replace-Implementierung
- Sicherheitshinweis: Datei nach Setup sofort löschen

**`bin/setup.example.yml`** – Config-Template:
- Vollständig kommentiert, alle Parameter dokumentiert
- Als `setup.yml` kopieren + anpassen
- `setup.yml` ist in `.gitignore` (keine Kundendaten im Repo)

**`bin/.gitignore`**:
- `setup.yml`, `.setup.log`, `.backups/`, `.setup-complete` ausgeschlossen

---

## [1.13.0] - 2026-03-10

### media-lab-seo 1.3.0

#### Added
- **`inc/adapter-ga4.php`** – Google Analytics 4 Adapter:
  - Authentifizierung via Service Account JWT (RS256) – kein zweiter OAuth-Flow
  - `get_overview()`: Pageviews, Sessions, Nutzer, Bounce Rate
  - `get_top_sources()`: Top Traffic-Quellen (Channel Groups)
  - Access-Token via Transient gecacht; automatische Erneuerung
  - `flush_cache()` static method

- **`inc/adapter-matomo.php`** – Matomo Adapter:
  - Verbindet beliebige selbst-gehostete Matomo-Instanz
  - `get_overview()`: Pageviews, Besuche, Unique Visitors, Bounce Rate
  - `get_top_sources()`: Referrer-Typen
  - `test_connection()`: Liefert Site-Name + URL zur Verifikation
  - Filter `medialab_matomo_sslverify` für lokale Dev-Umgebungen

- **`inc/analytics-adapter.php`** – Auto-Detection erweitert:
  - Priorität: Externer Filter → GA4 → Matomo → Stub
  - `medialab_analytics_active_provider()` → gibt 'ga4', 'matomo' oder '' zurück

#### Changed
- **`inc/seo-dashboard.php`** – Einstellungen erweitert:
  - GA4-Sektion: Property ID + Service Account JSON (mit "JSON gespeichert"-Badge)
  - Matomo-Sektion: URL + Site ID + Token + Verbindungstest-Button
  - Trennlinie mit Label zwischen GSC/Report und Analytics-Sektionen
  - Aktiver-Adapter-Badge zeigt den konfigurierten Anbieter
  - AJAX `medialab_test_matomo`: testet Matomo-Verbindung on-the-fly
  - Speichern-Handler sichert GA4 + Matomo-Werte und leert Adapter-Cache
- **`assets/js/seo-dashboard.js`** – Matomo-Verbindungstest AJAX
- **`assets/css/seo-dashboard.css`** – `.ml-settings-divider` + `.ml-settings-active-badge`

---

## [1.12.0] - 2026-03-09

### media-lab-agency-core 1.6.0

#### Added
- **`inc/hcaptcha.php`** – hCaptcha Integration (kein Drittanbieter-Plugin nötig):
  - `medialab_hcaptcha_active()` – globaler Aktivitätsstatus
  - `medialab_hcaptcha_widget($id)` – HTML-Widget-Ausgabe
  - `medialab_hcaptcha_verify()` – serverseitige Verifikation via `api.hcaptcha.com/siteverify`
  - **CF7**: Widget vor Submit-Button + `wpcf7_validate` Hook
  - **WP-Login**: Widget + `authenticate` Filter (Prio 30)
  - **WooCommerce Checkout**: Widget + `woocommerce_checkout_process`
  - **WooCommerce Registrierung**: Widget + `woocommerce_process_registration_errors`
  - Script `hcaptcha-api` nur auf relevanten Seiten eingebunden
- **ACF-Felder** in Spam-Schutz-Seite ergänzt (9 neue Felder):
  `hcaptcha_enabled`, `hcaptcha_site_key`, `hcaptcha_secret_key`,
  `hcaptcha_cf7`, `hcaptcha_wp_login`, `hcaptcha_woo_checkout`,
  `hcaptcha_woo_register`, `hcaptcha_theme`, `hcaptcha_size`
  – alle Felder außer dem Schalter via `conditional_logic` versteckt

---

## [1.11.1] - 2026-03-09

### media-lab-agency-core 1.5.5

#### Fixed
- **PHP Deprecated-Warnings (PHP 8.1+)** – ACF `message`-Felder ohne `'label'`-Key:
  ACF verarbeitet alle Felder intern über `wp_json_encode()`. Fehlendes `label`
  liefert `null`, was in PHP 8.1+ `strpos(null, ...)` Deprecated-Warnings auslöst.
  Behoben durch `'label' => ''` in 6 Feldern:
  - `acf-settings.php`: Plugin-Status-Info, SMTP Test-Mail, Obfuscate-Email Info,
    Polylang Voraussetzung, Wartungsmodus Hinweis (5 Felder)
  - `cookie-consent.php`: Code-Snippets Heading (1 Feld)

  Weitere PHP 8.x Patterns geprüft – keine Probleme gefunden:
  - `each()` → alle Treffer sind `foreach()` (kein Deprecated)
  - Implicitly nullable → `?string`/`?int` bereits korrekt
  - `FILTER_SANITIZE_STRING` → nicht verwendet

---

## [1.17.0] - 2026-03-10

### Versionen
- custom-theme: 1.13.0 (unverändert)
- agency-core: 1.6.0
- media-lab-seo: 1.3.0 (unverändert)

### Added – Gutenberg Custom Blocks (8 Blöcke)

**Architektur:**
- ACF Blocks (PHP-Rendering, kein Build-Step): Hero, Testimonial, Team-Mitglied, Logo-Leiste, Logo-Slider
- Native Blocks (JS/block.json via Vite): CTA-Banner, Accordion/FAQ, Icon+Text
- Alle Blöcke unter Gutenberg-Kategorie `design`

**`inc/blocks.php`** (neu) – zentrale Registrierung:
- `medialab_register_acf_blocks()` – ACF-Blöcke via `acf/init`
- `medialab_register_native_blocks()` – Native Blöcke via `init`
- `medialab_enqueue_block_editor_assets()` – Editor-CSS + blocks.js
- `medialab_enqueue_block_frontend_assets()` – Frontend-CSS, Accordion-JS, Logo-Slider-JS (je nur bei `has_block()`)

**ACF Blocks:**
- `blocks/hero/` – Hintergrundbild, Overlay, Kicker, Titel, Subtitle, 2× CTA-Button, Höhe (full/large/medium), Ausrichtung (left/center/right)
- `blocks/testimonial/` – Zitat, Name, Rolle, Bild, Sterne-Rating (1–5), Stil (card/minimal/centered)
- `blocks/team-member/` – Foto, Name, Rolle, Bio, E-Mail, LinkedIn, Xing, Instagram
- `blocks/logo-grid/` – Repeater: Logo + Link, Spalten (3–6), Graustufen-Toggle
- `blocks/logo-slider/` – Repeater: Logo + Link, Swiper Autoplay/Loop/Speed, Graustufen

**Native Blocks (JS/block.json):**
- `blocks/cta-banner/` – RichText Titel + Text, Button-URL, Button-Stil, Hintergrundfarbe (primary/dark/light/white), Align (full/wide)
- `blocks/accordion/` – RichText Titel, Allow-Multiple Toggle; Frontend: natives `<details>/<summary>` + ARIA
- `blocks/icon-text/` – Icon (Emoji/Dashicon), Farbe, Layout (top/left), RichText Titel + Text

**`assets/src/js/blocks.js`** – Native Block Registrierungen (kein JSX, wp.element.createElement)
**`assets/src/js/block-accordion.js`** – Frontend Accordion: ARIA, Multi-Toggle, schließt andere Items
**`assets/src/js/block-logo-slider.js`** – Swiper-Init aus data-swiper Attribut
**`assets/src/scss/blocks.scss`** – vollständiges Block-CSS (CSS Custom Properties, Responsive)

**`vite.config.js`** – neue Entry Points:
- `plugin-blocks` → `plugins/media-lab-agency-core/assets/dist/js/blocks.js`
- `plugin-block-accordion` → `.../block-accordion.js`
- `plugin-block-logo-slider` → `.../block-logo-slider.js`

---

## [1.16.0] - 2026-03-10

### Versionen
- custom-theme: 1.13.0
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – WooCommerce E-Mail-Templates

**Template-Overrides** (`woocommerce/emails/`):
- `email-header.php` – Custom Header: Logo/Markenname, Primärfarbe, E-Mail-Betreff-Zeile
- `email-footer.php` – Custom Footer: Links (Datenschutz, Impressum, Kontakt), Footer-Text
- `email-styles.php` – Inline-CSS: Typografie, Bestelltabelle, Adress-Boxen, Button, Responsive
- `customer-processing-order.php` – Bestellbestätigung mit Info-Box + „Bestellung ansehen" CTA
- `customer-completed-order.php` – Abgeschlossen-Mail mit optionalem Bewertungs-CTA
- `customer-invoice.php` – Rechnung/Zahlungsaufforderung (zahlen oder ansehen, je nach Status)
- `customer-new-account.php` – Willkommens-Mail mit Zugangsdaten-Box + „Zum Kundenkonto" CTA
- `customer-reset-password.php` – Passwort-Reset mit sicherem Link-Button + Ablauf-Hinweis

**`inc/woocommerce-emails.php`** (neu) – globale Konfiguration:
- `woocommerce_email_content_type` → HTML erzwingen
- `wp_mail_from_name` / `wp_mail_from` → Absender aus WC-Einstellungen
- `woocommerce_email_styles` → Theme-CSS aus `email-styles.php` einbinden
- `customtheme_init_email_colors()` → WC-Farboptionen bei Theme-Aktivierung initialisieren
- `woocommerce_email_before_order_table` → Admin-Bestelllink in Admin-Benachrichtigung
- Betreff-Prefix via Filter `customtheme_email_prefix_subject`

**Konfigurierbare Filter:**
- `customtheme_email_primary_color` – Primärfarbe (Standard: WC-Option)
- `customtheme_email_logo_url` – Logo-URL (Standard: WC-Option)
- `customtheme_email_footer_links` – Footer-Link-Array
- `customtheme_email_show_review_cta` – Bewertungs-CTA in Abgeschlossen-Mail (bool)
- `customtheme_email_prefix_subject` – Site-Name-Prefix im Betreff (bool, Standard: false)
- `customtheme_email_content_type` – Content-Type (Standard: text/html)

---

## [1.15.0] - 2026-03-10

### Versionen
- custom-theme: 1.12.0
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – Core Web Vitals Performance-Optimierungen

**`inc/performance.php`** (neu) – zentrales Performance-Modul:

**LCP (Largest Contentful Paint):**
- `customtheme_preload_lcp_image()` – `<link rel="preload" fetchpriority="high">` für Hero-Bild (ACF-Feld → Featured Image → Filter)
- Responsive Preload: `imagesrcset` + `imagesizes` im Preload-Tag
- `customtheme_fetchpriority_first_image()` – `fetchpriority="high" loading="eager"` auf erstem Content-Bild (für Seiten ohne Hero)
- `customtheme_inline_critical_css()` – inline Critical CSS aus `assets/dist/css/critical.css` (wenn vorhanden)
- `customtheme_nonblocking_css()` – Haupt-CSS auf `media="print"` + onload-Swap (non-blocking, wenn critical.css vorhanden)

**CLS (Cumulative Layout Shift):**
- `customtheme_enforce_image_dimensions()` – erzwingt `width`/`height` auf allen `wp_get_attachment_image()`-Bildern
- `customtheme_add_image_dimensions_to_content()` – ergänzt `width`/`height` in `the_content`-Bildern
- `customtheme_fonts_display_swap()` – fügt `display=swap` zu Google Fonts URLs hinzu
- `customtheme_preload_fonts()` – `<link rel="preload">` für self-hosted Fonts (via Filter `customtheme_preload_fonts`)

**INP (Interaction to Next Paint):**
- `customtheme_defer_scripts()` – `defer` auf CF7, WooCommerce-Frontend und weitere Plugin-Scripts
- Filter: `customtheme_defer_scripts` (erweitern) + `customtheme_exclude_defer_scripts` (ausschließen)
- Heartbeat-Interval im Frontend: 15s → 60s
- `rest_output_link_wp_head` + `wp_oembed_add_host_js` aus wp_head entfernt

**Responsive Images:**
- `customtheme_webp_picture_element()` – `<picture>` + `<source type="image/webp">` (opt-in via Filter `customtheme_enable_picture_webp`)
- `max_srcset_image_width` erweitert: 1600 → 2560px (Retina)
- Lazy Loading für alle Thumbnails via `wp_lazy_loading_enabled`
- LCP-Bild bekommt `loading="eager"` (kein Lazy Loading)

**`inc/enqueue.php`** – Preconnect-Optimierung:
- Google Fonts Preconnect nur noch aktiv wenn tatsächlich eine Google Fonts URL eingebunden ist

**`vite.config.js`** – Brotli/Gzip Precompression:
- `vite-plugin-compression2` eingebunden (graceful fallback wenn nicht installiert)
- Brotli: `.br`-Dateien neben jedem Asset
- Gzip: `.gz`-Dateien als Fallback
- Installation: `npm install -D vite-plugin-compression2`

**`assets/src/scss/utilities/_helpers.scss`** – Aspect Ratio Utilities:
- `.ratio` Container-Klasse mit Varianten: `--16-9`, `--4-3`, `--3-2`, `--1-1`, `--9-16`, `--hero`, `--custom`
- `.aspect-video`, `.aspect-square`, `.aspect-photo` für direkte Verwendung auf `<img>`/`<video>`
- `.object-cover`, `.object-contain`, `.object-fill`, `.object-top/center/bottom`

**`functions.php`:**
- `performance.php` als fester require eingebunden

---

## [1.14.0] - 2026-03-10

### Versionen
- custom-theme: 1.11.0 (unverändert)
- agency-core: 1.5.4 (unverändert)
- media-lab-seo: 1.3.0 (unverändert)

### Added – One-Click-Setup Script

**`bin/setup.sh`** – Bash/WP-CLI Setup (interaktiv + Config-File-Modus):
- Interaktiver Modus: geführte Eingabe aller Parameter
- Config-File-Modus: `--config bin/setup.yml` für automatisierten Einsatz
- `--dry-run`: zeigt alle Änderungen ohne sie auszuführen
- Schritt 1: Backup (DB-Export + wp-config.php)
- Schritt 2: Domain setzen (siteurl + home via WP-CLI)
- Schritt 3: DB-Präfix ändern (RENAME TABLE + usermeta/options Keys)
- Schritt 4: Search/Replace alte → neue Domain (--all-tables)
- Schritt 5: Admin-User anlegen oder Passwort aktualisieren
- Schritt 6: Theme kopieren + umbenennen (style.css, Text Domain in PHP)
- Schritt 7: Plugin-Prefix `medialab_` → Kundenpräfix (PHP + JS + DB)
- Schritt 8: Rewrite Rules flush, WP_DEBUG deaktivieren
- Farbiger Output, Log-File unter `bin/.setup.log`

**`bin/setup.php`** – Browser-Setup (für Hosting ohne SSH):
- Gleiches Feature-Set wie setup.sh
- Absicherung via Token-Parameter (`?token=GEHEIM`)
- Lock-File nach Setup (`bin/.setup-complete`) verhindert doppeltes Ausführen
- Dark-Mode UI mit Collapsible-Sektionen
- Admin-Zugangsdaten werden nach dem Setup angezeigt
- Serialisierungssichere Search/Replace-Implementierung
- Sicherheitshinweis: Datei nach Setup sofort löschen

**`bin/setup.example.yml`** – Config-Template:
- Vollständig kommentiert, alle Parameter dokumentiert
- Als `setup.yml` kopieren + anpassen
- `setup.yml` ist in `.gitignore` (keine Kundendaten im Repo)

**`bin/.gitignore`**:
- `setup.yml`, `.setup.log`, `.backups/`, `.setup-complete` ausgeschlossen

---

## [1.13.0] - 2026-03-10

### media-lab-seo 1.3.0

#### Added
- **`inc/adapter-ga4.php`** – Google Analytics 4 Adapter:
  - Authentifizierung via Service Account JWT (RS256) – kein zweiter OAuth-Flow
  - `get_overview()`: Pageviews, Sessions, Nutzer, Bounce Rate
  - `get_top_sources()`: Top Traffic-Quellen (Channel Groups)
  - Access-Token via Transient gecacht; automatische Erneuerung
  - `flush_cache()` static method

- **`inc/adapter-matomo.php`** – Matomo Adapter:
  - Verbindet beliebige selbst-gehostete Matomo-Instanz
  - `get_overview()`: Pageviews, Besuche, Unique Visitors, Bounce Rate
  - `get_top_sources()`: Referrer-Typen
  - `test_connection()`: Liefert Site-Name + URL zur Verifikation
  - Filter `medialab_matomo_sslverify` für lokale Dev-Umgebungen

- **`inc/analytics-adapter.php`** – Auto-Detection erweitert:
  - Priorität: Externer Filter → GA4 → Matomo → Stub
  - `medialab_analytics_active_provider()` → gibt 'ga4', 'matomo' oder '' zurück

#### Changed
- **`inc/seo-dashboard.php`** – Einstellungen erweitert:
  - GA4-Sektion: Property ID + Service Account JSON (mit "JSON gespeichert"-Badge)
  - Matomo-Sektion: URL + Site ID + Token + Verbindungstest-Button
  - Trennlinie mit Label zwischen GSC/Report und Analytics-Sektionen
  - Aktiver-Adapter-Badge zeigt den konfigurierten Anbieter
  - AJAX `medialab_test_matomo`: testet Matomo-Verbindung on-the-fly
  - Speichern-Handler sichert GA4 + Matomo-Werte und leert Adapter-Cache
- **`assets/js/seo-dashboard.js`** – Matomo-Verbindungstest AJAX
- **`assets/css/seo-dashboard.css`** – `.ml-settings-divider` + `.ml-settings-active-badge`

---

## [1.12.0] - 2026-03-10

### media-lab-seo 1.2.0

#### Added
- **`inc/gsc-api.php`** – Google Search Console API-Integration:
  - OAuth2-Flow (Authorization Code + Refresh Token)
  - Tokens in `wp_options` gespeichert; automatische Erneuerung bei Ablauf
  - `medialab_gsc_get_dashboard_data()` – aggregierte Daten (28 Tage + Vorperiode)
  - `medialab_gsc_get_top_keywords()` + `medialab_gsc_get_top_pages()` – Top 10 je
  - Transient-Cache (1h) für alle API-Antworten; manuell löschbar
  - `medialab_gsc_list_properties()` – verifizierte Properties abrufen

- **`inc/analytics-adapter.php`** – Pluggbarer Analytics-Adapter:
  - `MediaLab_Analytics_Stub` als Standard (gibt 0-Werte zurück)
  - Filter `medialab_analytics_adapter` für GA4 / Matomo / Plausible
  - Dokumentiertes Interface für eigene Adapter

- **`inc/seo-dashboard.php`** – SEO Dashboard:
  - Admin-Seite unter `Media Lab SEO → 📊 Dashboard`
  - WordPress Dashboard-Widget (Mini-KPIs 2×2)
  - KPI-Kacheln: Klicks, Impressionen, CTR, Ø Position mit Delta zur Vorperiode
  - Tabellen: Top 10 Keywords + Top 10 Seiten
  - OAuth-Callback-Handler, Disconnect-Action
  - Einstellungsformular: GSC-Credentials + Report-Optionen
  - AJAX: Cache-Flush-Button, Test-Report-Button

- **`inc/seo-report-template.php`** – HTML-Mail-Template:
  - Inline-CSS, kompatibel mit Gmail / Outlook / Apple Mail
  - KPI-Kacheln mit Delta-Farben (grün/rot)
  - Top-Keywords- und Top-Seiten-Tabellen (je 8 Zeilen)
  - Positions-Badges farbcodiert (≤3 grün, ≤10 gelb, >10 rot)
  - CTA-Button zum WordPress-Dashboard

- **`inc/seo-report-mailer.php`** – Wöchentlicher Report-Versand:
  - WP-Cron-Event `medialab_seo_weekly_report` (Intervall: weekly)
  - Konfigurierbarer Versandtag (Mo–So) + Uhrzeit
  - Absender-Name und -E-Mail konfigurierbar
  - Log des letzten Versands in `wp_options`

- **`assets/css/seo-dashboard.css`** – Dashboard-Styling (Admin)
- **`assets/js/seo-dashboard.js`** – AJAX-Aktionen (Cache-Flush, Test-Mail)

---

## [1.11.0] - 2026-03-09

### custom-theme 1.11.0

#### Changed
- **`_woocommerce.scss`** – vollständige Neufassung (1022 → vollständig):
  - **WC-Notices**: `woocommerce-message/error/info` mit `::before`-Icon, alle via Custom Properties
  - **Shop-Grid**: 3-spaltig, 2 ab 1024px, 1 ab 600px; `var(--color-card-bg)` statt `$color-white`
  - **Produktkarte**: Sale-Badge, Bild-Hover-Zoom, Preis mit `$color-primary`, Button via `@include btn-*`
  - **Einzelprodukt**: Gallery-Thumbnails, Preis, Mengen-Input, Add-to-Cart, Produkt-Meta, Tabs, Bewertungen, Verwandte Produkte
  - **Warenkorb**: `shop_table`, Coupon-Zeile, Update-Button, `cart_totals` inkl. Checkout-CTA, leerer Warenkorb
  - **Checkout**: `col2-set` Grid, Bestellübersicht, Bezahlmethoden, `place_order`-Button
  - **Mein Konto**: `MyAccount-navigation`, Bestellliste, Adress-Boxen 2-spaltig
  - **WC-Formularfelder**: `form-row`, `form-row-first/last`, Validation States, Select Custom-Pfeil
  - **Shop-Ordering + Result-Count**, **WC-Pagination**

  Fixes: `$color-white` → `var(--color-card-bg)`, `color: red` → `$color-primary`,
  `$color-woo-danger` → `$color-error`, minimaler `!important`-Einsatz

---

## [1.10.1] - 2026-03-09

### custom-theme 1.10.1

#### Changed
- **`search.php`** – überarbeitet:
  - `get_search_form()` statt `do_shortcode('[ajax_search]')`
  - Breadcrumbs-Integration
  - Post-Type Label via `get_post_type_object()` (dynamisch, kein hardcoded Array)
  - Pagination via `paginate_links()` → nutzt `.archive-pagination` Styles
  - Leer-Zustand mit SVG-Icon, Text, Formular, Home-Link
- **`_search-results.scss`** – vollständige Neufassung:
  - `var(--color-card-bg)` statt `$color-white` → Dark-Mode-Kompatibilität
  - Post-Type Farbstreifen (post/page/product/project/service/job)
  - `.search-empty` Leer-Zustand mit Icon
  - Suchformular-Styles für `get_search_form()` Output
- **`_page-404.scss`** – doppeltes `@use '../abstracts'` entfernt

---

## [1.10.0] - 2026-03-09

### custom-theme 1.10.0

#### Added
- **`single.php`**: Kategorie-Badges, Meta-Zeile (Avatar, Autor, Datum, Lesezeit),
  Featured Image (nur ohne Hero), vollständige Entry-Content-Typografie,
  Tags-Footer, Autor-Box, Prev/Next-Navigation, Kommentare-Integration
- **`archive.php`**: Archiv-Header mit Badge, Beschreibung + Ergebnis-Zähler,
  3-spaltiges Post-Grid, paginate_links(), Leere-Zustand
- **`template-parts/components/post-card.php`**: Wiederverwendbare Post-Card,
  nutzbar im Loop und via `set_query_var('post_card_post', $post)`,
  Variante `horizontal` via `set_query_var('post_card_variant', 'horizontal')`
- **`assets/src/scss/templates/_single.scss`**: Layout, Header, Entry-Content,
  Author-Box, Post-Nav
- **`assets/src/scss/templates/_archive.scss`**: Archive-Header, `.post-grid`,
  `.post-card` (mit CSS Custom Properties), Pagination

#### Changed
- **`_cards.scss`**: `.post-card` in `_archive.scss` verschoben,
  `$color-white` → `var(--color-card-bg)` für Dark-Mode-Kompatibilität,
  Datei auf generische `.card`-Basis reduziert
- **`style.scss`**: `@use templates/single` + `@use templates/archive` registriert

---

## [1.9.1] - 2026-03-09

### custom-theme 1.9.1

#### Changed
- **`_contact-form-7.scss`** – vollständige Neufassung:
  - `%field-base` Placeholder → DRY, alle Felder erben gemeinsame Basis
  - `height: 48px` für alle Inputs und Select → einheitliche Höhe
  - `:hover` State auf Felder (border-color: text-muted)
  - `accent-color: $color-primary` für native Checkboxen / Radio
  - Select-Pfeil: neutrales Grau statt hardcoded `#667eea`
  - `wpcf7-not-valid`: zusätzlich box-shadow Fokusring
  - Response-Output: flex mit `::before` Icon, alle CF7-Klassen abgedeckt (`wpcf7-acceptance-missing`, `wpcf7-aborted`)
  - `submitting`-State am Form verhindert Mehrfach-Submit
  - `@keyframes cf7-spin` (kein globaler Namenskonflikt mehr)
  - Dark Mode: kein eigener `@media`-Block, läuft über CSS Custom Properties
  - Layout: `cf7-grid-2`, `cf7-grid-3`, `cf7-inline`, `cf7-full`
  - Variante `wpcf7-card` mit `$shadow-lg`, Variante `wpcf7-minimal` (Underline)

---

## [1.9.0] - 2026-03-09

### media-lab-agency-core

#### Changed
- **`inc/hero-image.php`** – erweiterte ACF-Felder:
  - Neue Post-Felder: `hero_image_subtitle`, `hero_btn1/2_text/url/style`, `hero_image_align`, `hero_image_height`, `hero_image_vpos`, `hero_image_opacity` (per-post override)
  - Neue globale Felder: `hero_default_height`, `hero_default_align`
  - Conditional Logic: Felder nur sichtbar wenn Hero aktiv / Btn1 gesetzt
  - `media_lab_get_hero_image()`: gibt alle neuen Felder zurück

### custom-theme 1.9.0

#### Changed
- **`template-parts/hero-image.php`** – Untertitel, zwei Buttons mit `btn--light` Modifier, CSS-Klassen für Varianten
- **`_hero-image.scss`** – Höhenvarianten (sm/md/lg/xl via clamp), Textausrichtung, Vertikalposition, `btn--light` für alle drei Button-Stile

### Docs
- `06_DEVELOPMENT.md`: Hero Image Sektion (ACF-Felder Tabelle, CSS-Klassen, Buttons)

---

## [1.8.0] - 2026-03-06

### media-lab-seo

#### Added
- **Breadcrumbs** (`inc/breadcrumbs.php`) – vollständige Eigenimplementierung
  - `medialab_breadcrumbs($args)` – alle Seitentypen: Seiten, Beiträge, CPTs, Taxonomien, Archive, Suche, 404
  - Blog-Seite als Zwischenebene, primäre Kategorie (tiefster Term), Eltern-Seiten
  - Schema.org `BreadcrumbList` JSON-LD (opt-out möglich)
  - Filter `medialab_breadcrumbs_html`
  - Konfiguierbar: separator, show_home, home_label, show_current, container, class, schema

### custom-theme 1.8.0

#### Added
- **`template-parts/components/breadcrumbs.php`** – Template Part für `get_template_part()`
- **`components/_breadcrumbs.scss`** – Varianten: `--light`, `--compact`, `--centered`, `--bar`
  - Mobile: langer Titel mit `text-overflow: ellipsis` abgeschnitten

#### Changed
- **`page.php`**: Breadcrumbs nach Hero-Image eingefügt
- **`footer.php`**: Footer-Nav `depth: 1` → `depth: 4`
- **`style.scss`**: `@use 'components/breadcrumbs'` registriert

### Docs
- `06_DEVELOPMENT.md`: Breadcrumbs-Sektion (Optionen-Tabelle, Varianten, Filter)

---

## [1.7.2] - 2026-03-06

### custom-theme 1.7.2

#### Added
- **Toggle – 3-State Switch** (`components/_toggle.scss`, `components/toggle.js`, `functions.php`)
  - 3 States: `on` (Primary-Farbe), `off` (Border-Farbe), `unavailable` (ausgegraut, nicht klickbar)
  - Größenvarianten: `toggle--sm` / default / `toggle--lg`
  - `toggle--stacked` für vertikales Label
  - ARIA-konform: `role="switch"`, `aria-pressed`, `aria-disabled`, `tabindex`
  - Keyboard-Support: Space / Enter
  - `toggle.change` CustomEvent mit `{ state, previous, element }`
  - Statische Methoden: `Toggle.setState(el, state)`, `Toggle.getState(el)`
  - PHP-Helper `medialab_toggle($id, $state, $label, $args)` in `functions.php`
  - Automatisch in `style.scss` + `main.js` eingebunden

### Docs
- `06_DEVELOPMENT.md`: Toggle-Sektion mit HTML, PHP, JS und Tabelle

---

## [1.7.1] - 2026-03-06

### media-lab-agency-core

#### Changed
- **acf-settings.php:** Einzelne `Einstellungen`-Unterseite ersetzt durch 10 separate Unterseiten:
  Plugin Status · Maintenance Mode / Wartungsmodus · Logo / Globale Einstellungen ·
  Hero Image / Globale Einstellungen · Cookie Consent · E-Mail / SMTP ·
  Spam-Schutz / E-Mail Obfuskierung · Top Header / Kontaktdaten ·
  Multi Language / Mehrsprachigkeit · White Label / Agentur-Branding
- **cookie-consent.php, hero-image.php:** Field Group Locations auf neue Slugs aktualisiert
- **smtp.php:** Script-Enqueue prüft neuen Slug `agency-core-smtp`
- **maintenance.php:** Admin-Bar Link zeigt direkt auf `agency-core-maintenance`

### Docs

- `03_PLUGINS.md`: Navigationspfade auf neue Unterseiten aktualisiert, `acf-settings.php`-Beschreibung erweitert
- `09_ACF-FIELDS.md`: Options Sub-Pages Übersichtstabelle (alle 10 Seiten mit Slug + Field Group), Options Page Pfade aktualisiert, Feldgruppenzähler korrigiert

---

## [1.7.0] - 2026-03-06

### custom-theme 1.7.0

#### Added
- **Fullwidth-Helper** – `utilities/_helpers.scss` + `abstracts/_mixins.scss`
  - `@mixin fullwidth` / `@mixin fullwidth-media` für SCSS-Komponenten
  - `.fullwidth` – bricht aus Container aus (100vw)
  - `.fullwidth--bg` – + Padding + Hintergrundfarbe via CSS Custom Property `--fw-bg`
  - `.fullwidth--media` – für Bilder, Videos, iFrames (overflow hidden, object-fit cover)
  - `.fullwidth__inner` – zentriert Inhalt auf Container-Breite innerhalb fullwidth
  - Weitere Utilities: `.sr-only`, `.hidden`, `.text-center/left/right`, `.text-muted/primary`

#### Changed
- **Navigation 4 Ebenen** – `layout/_navigation.scss` + `components/navigation.js`
  - Desktop Level 4: Flyout rechts, `border-left: $color-primary` als Tiefenindikator
  - Viewport-Kollision: `.opens-left` wird automatisch gesetzt wenn Flyout über Rand ragt
  - Mobile: Level 4 mit tiefster Einrückung + kursiver Schrift
  - Footer-Nav: Flyout nach **oben** auf Desktop, Accordion auf Mobile (< 768px)
  - JS: Toggle-Icon via JS eingefügt (Link bleibt separat klickbar)
  - JS: Erster Tap öffnet Submenu, zweiter Tap navigiert (bei echten Links)
  - JS: Footer-Submenüs als Accordion auf Mobile

---

## [1.6.0] - 2026-03-06

### media-lab-agency-core 1.5.4

#### Added
- **Cookie Consent Manager** – `inc/cookie-consent.php` neu
  - Banner mit „Alle akzeptieren" / „Einstellungen" / „Ablehnen"
  - Settings Modal mit Toggle-Switch pro Kategorie
  - Floating Button 🍪 (bottom-left, immer sichtbar) öffnet Modal jederzeit
  - 4 Kategorien: Notwendig (required), Statistik, Marketing, Komfort
  - Consent als JSON in `localStorage` mit Version + Timestamp
  - HTTP 503-konformes Verhalten: kein retroaktives Tracking
- **Snippet-Verwaltung im Backend** – Head- + Body-Code pro Kategorie via ACF
  - Notwendige Snippets werden immer geladen (kein Consent nötig)
  - Statistik/Marketing/Komfort werden nach Consent injiziert
  - Script-Tags korrekt via `createElement` ausgeführt (nicht innerHTML)
  - Dedup-Schutz via ID verhindert doppeltes Laden
- **ACF Field Group** `group_cookie_consent` – 20 Felder
  - Alle Texte + Kategorienbezeichnungen konfigurierbar
  - Consent-Version-Feld: erhöhen erzwingt erneute Zustimmung

#### Fixed
- `is_admin()` Guard in `output_config()` – PHP-Config nur im Frontend
- Leere ACF `message`-Felder (`label => ''`) übergaben `null` an `wp_json_encode` → Deprecated-Warnings in `functions.php` behoben

### custom-theme 1.6.0

#### Added
- **Globales Button-System** – `components/_buttons.scss`
  - Klassen: `.btn`, `.btn--primary`, `.btn--outline`, `.btn--ghost`, `.btn--secondary`
  - Größen: `.btn--sm`, `.btn--lg`, `.btn--full`
- **Button Mixins** – `abstracts/_mixins.scss`
  - `@mixin btn-base` – Basis-Layout, Transition, Focus-Ring
  - `@mixin btn-primary`, `btn-outline`, `btn-ghost` – Farbvarianten
  - `@mixin btn-sm`, `btn-lg` – Größenvarianten

#### Changed / Refactored
- **7 Komponenten** auf `@include btn-*` umgestellt – keine duplizierten Button-Blöcke mehr:
  `_load-more.scss`, `_google-maps.scss`, `_contact-form-7.scss`, `_modal.scss`, `_pricing-tables.scss`, `_ajax-filters.scss`, `_hero-slider.scss`
- Cookie Consent Banner/Modal nutzen globale `.btn`-Klassen direkt via HTML

#### Fixed
- `cookie-modal` hatte `display: flex` auch im `[hidden]`-Zustand → Backdrop blockierte alle Banner-Button-Klicks
- Doppelte Instanziierung von `CookieConsent` (Modul + `main.js`) behoben
- Doppelter `export default` in `cookie-notice.js` entfernt (Rollup Build-Fehler)

---

## [1.4.0] - 2026-03-04

### custom-theme 1.4.0

#### Performance
- **Code-Splitting** – 27 Komponenten als Dynamic Imports; werden nur geladen wenn das entsprechende DOM-Element auf der Seite vorhanden ist (`has()` Selektor-Check)
- **console.log entfernt** – Terser entfernt alle `console.log/info/debug` automatisch in Production
- **type="module"** – `<script type="module">` statt `defer`; ES-Module sind per Spezifikation immer deferred
- **Preconnect** – Google Fonts + Maps DNS-Prefetch im `<head>` für schnellere externe Ressourcen
- **Emoji-Scripts deaktiviert** – WordPress Emoji-JS/CSS (~16KB) komplett entfernt
- **oEmbed deaktiviert** – REST-Route und Discovery-Links entfernt
- **WP Head bereinigt** – RSD, WLW Manifest, WP Generator, Shortlink entfernt
- **Responsive Images** – `medialab_get_thumbnail()` Hilfsfunktion liefert `srcset`, `sizes`, `loading=lazy`, `decoding=async`; ersetzt `get_the_post_thumbnail_url()` in Team, Projects, Testimonials, Services

#### Changed
- `vite.config.js` – Code-Splitting mit 6 Entry Points, Terser mit `drop_console`, `modern-compiler` SCSS API, Autoprefixer ohne IE 11
- `enqueue.php` – komplett überarbeitet; `type="module"`, Preconnect, Emoji/oEmbed-Deaktivierung, unnötige WP Head Tags entfernt
- `functions.php` – gelöschtes `media-lab-project-starter` aus Required-Plugins entfernt; Theme-Version auf 1.4.0

### media-lab-agency-core 1.5.1

#### Added
- `medialab_get_thumbnail()` – responsive Thumbnail-Hilfsfunktion mit srcset + lazy loading
- `medialab_the_thumbnail()` – Echo-Wrapper für `medialab_get_thumbnail()`

#### Fixed
- `shortcodes.php` – 4 Stellen von `get_the_post_thumbnail_url()` auf `medialab_get_thumbnail()` umgestellt (Team, Projects, Testimonials, Services)

---

## [1.3.0] - 2026-03-04

### media-lab-agency-core 1.5.0

#### Security
- **F-03 Rate-Limiting** – `medialab_check_rate_limit()` in `helpers.php`; alle drei öffentlichen AJAX-Endpunkte geschützt: Filter/Load-More max. 30 Req/60s, Search max. 20 Req/60s pro IP; Transient-basiert, kein externer Service nötig
- **F-05 SMTP Credentials** – `get_options()` liest zuerst `wp-config.php`-Konstanten (`MEDIALAB_SMTP_HOST`, `_PORT`, `_USER`, `_PASS`, `_ENC`, `_FROM`, `_FROM_NAME`, `_ENABLED`); Passwort landet nie mehr in der Datenbank wenn Konstanten gesetzt sind; ACF bleibt als Fallback
- **F-06 Inline-Nonce** – Nonce wird sicher via `wp_localize_script()` übergeben; `<script>`-Tag aus ACF Message Field entfernt; neues `assets/js/smtp-test.js` übernimmt den AJAX-Call
- **F-08 Output-Escaping** – `esc_attr()` für alle `data-post-id`- und Search-Input-Ausgaben; `esc_html()` für alle Datumsausgaben in `shortcodes.php`

#### Added
- `assets/js/smtp-test.js` – separates Admin-Script für SMTP Test-Mail Funktion

### Projekt

#### Fixed
- **F-07 Security HTTP-Header** – `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy` in `cms/.htaccess` ergänzt; Header-Block steht vor `# BEGIN WordPress` um Überschreiben zu verhindern
- **F-09 ABSPATH-Guards** – bereits vollständig vorhanden, kein Fix nötig

---

## [1.2.0] - 2026-03-04

### media-lab-agency-core 1.4.0

#### Security
- **SVG Sanitizer** – `MediaLab_SVG_Sanitizer` ersetzt unsichere Regex-Bereinigung; vollständige Allowlist für Tags und Attribute; entfernt `<script>`, `<foreignObject>`, `<animate>`, externe `<use href>`, alle `on*`-Handler, `javascript:`- und `data:`-URLs via DOMDocument; SVG-Upload auf Administratoren beschränkt
- **IP-Adressen (DSGVO)** – `get_client_ip()` prüft Cloudflare-, nginx-Proxy- und X-Forwarded-For-Header korrekt; akzeptiert nur öffentliche IPs (kein Spoofing via Private Range); WP-Cron anonymisiert IPs automatisch nach 90 Tagen (IPv4: letztes Oktett → 0, IPv6: letzte 5 Gruppen → 0); Cron wird bei Plugin-Deaktivierung sauber entfernt

#### Fixed
- Deaktivierungs-Hook bereinigt nun auch den IP-Anonymisierungs-Cron-Job

### custom-theme 1.2.0

#### Changed
- **Design Tokens** – `$color-gray-100`, `$container-max-width`, `$color-woo-danger` als fehlende Tokens ergänzt; 12 vorkompilierte Farbvarianten (`$color-primary-dark`, `$color-warning-light-bg` etc.) als Ersatz für deprecated `darken()`/`lighten()`-Funktionen
- **Token-Bereinigung** – alle hardcodierten Hex-Werte in 10 SCSS-Files durch Design Tokens ersetzt (`_notification.scss`, `_notifications.scss`, `_testimonials.scss`, `_video-player.scss`, `_cpt-grids.scss`, `_search-results.scss`, `_woocommerce.scss`, `layout/_top-header.scss`)
- **Top Header** – `components/_top-header.scss` (Duplikat) gelöscht; `layout/_top-header.scss` als einzige Quelle; alle Werte auf Tokens umgestellt
- **Sass Module System** – Migration von `@import` auf `@use`/`@forward`; `abstracts/_index.scss` als zentraler Einstiegspunkt; alle 46 SCSS-Partials deklarieren ihre Abhängigkeiten selbst via `@use '../abstracts' as *`; keine Deprecation Warnings mehr

### Projekt

#### Removed
- Backup-Files entfernt: `shortcodes.php.bak*`, `multi-language.php.backup4`, `media-lab-agency-core.php.before-ajax`, `wizard.php.bak`, `acf-fields.php.DISABLED`
- `lighthouse-report.html` aus Repository entfernt
- Inaktives Plugin `media-lab-core` (Vorgänger von `media-lab-agency-core`) gelöscht

---

## [1.1.0] - 2026-03-04

### media-lab-agency-core 1.3.0

#### Added
- **Top Header** – Kontaktleiste über dem Hauptheader (Adresse, Öffnungszeiten, Telefon, E-Mail, Social Media, Styling-Optionen) via ACF Settings
- **Logo** – Desktop- und Mobile-Logo hochladbar mit konfigurierbarer Breite; Fallback auf Seitennamen
- **Dark/Light Mode** – System-Präferenz (`prefers-color-scheme`) wird nun korrekt berücksichtigt; FOUC durch Inline-Script im `<head>` verhindert; `localStorage` wird nur bei expliziter User-Wahl beschrieben
- **Drag & Drop Post Order** – Sortierung aller Posts, Pages und CPTs per Drag & Drop in der Admin-Listenansicht; Reihenfolge in `menu_order` gespeichert
- **Drag & Drop Term Order** – Sortierung aller Taxonomy Terms per Drag & Drop; Reihenfolge in Term Meta gespeichert
- **Duplicate Post / Term** – „Duplizieren"-Link in allen Post- und Term-Listenansichten; kopiert Titel, Inhalt, Meta (inkl. ACF), Taxonomien, Featured Image; Duplikat immer als Entwurf
- **SMTP Mailer** – SMTP-Konfiguration (Host, Port, TLS/SSL, Credentials, Absender) via ACF Settings; Test-Mail-Funktion direkt im Backend
- **E-Mail Obfuskierung** – ROT13-basierter Spam-Schutz für E-Mail-Adressen im Content; automatischer Schutz aller `mailto:`-Links oder manuell via `[obfuscate_email]` Shortcode
- **White Label** – Vollständiges Backend-Branding: Login-Screen (Logo, Hintergrund, Primärfarbe), Admin-Bar, Dashboard-Widget mit Agentur-Kontaktdaten, Footer-Text; Menü-Sichtbarkeit nach Benutzerrolle konfigurierbar

#### Changed
- **ACF Settings** umstrukturiert: Plugin Status als eigene Gruppe oben; Hero Image Felder aus separater Sub-Page in Haupteinstellungen integriert; Mehrsprachigkeit als letzte Gruppe; White Label nach Plugin Status eingefügt
- **Admin-Menü** bereinigt: Doppelter „Agency Core"-Eintrag entfernt; saubere Menüstruktur (Einstellungen → Activity Log)
- **Hook-Reihenfolge** stabilisiert: `acf_add_options_page` via nativen `add_menu_page` + `acf_add_options_sub_page` für zuverlässige Menü-Registrierung unabhängig von ACF-Hook-Timing

#### Fixed
- Doppelter Menüeintrag „Agency Core" nach `remove_submenu_page` mit Priorität 999 entfernt
- Activity Log erscheint nun korrekt nach „Einstellungen" (Hook-Priorität 999)
- Drag-Handle erscheint jetzt inline vor dem Titel-Link ohne Zeilenumbruch

---

### media-lab-seo 1.1.0

#### Added
- **Redirections** – 301/302/307 Redirects mit Wildcard-Support (`/pfad/*`); Hit-Counter pro Redirect; separater Tab im Backend
- **404-Log** – Automatisches Tracking aller 404-Aufrufe mit URL, Referrer, Anzahl und Zeitstempel; direkt aus Log einen Redirect anlegen via Modal; Log leeren
- **SEO Meta Box** – Pro Post/Page/CPT: Meta Title, Meta Description mit Live-Zeichenzähler (grün/gelb/rot), Google Snippet Vorschau (live), Fokus-Keyword, Canonical URL, OG Image (Medien-Picker), Robots (noindex/nofollow)

#### Changed
- **Open Graph** und **Twitter Cards** nutzen jetzt zentrale Helper-Funktionen (`medialab_seo_get_title()`, `medialab_seo_get_description()`, `medialab_seo_get_og_image()`) – per Post überschreibbare Werte werden automatisch verwendet

---

### custom-theme 1.1.0

#### Added
- Logo-Ausgabe in `header.php` mit Desktop/Mobile-Variante via ACF Options
- Top Header Rendering in `header.php` (Adresse, Öffnungszeiten, Telefon, E-Mail, Social Media)
- `_top-header.scss` – Styles für alle Farbvarianten und Mobile-Verhalten
- Inline Theme-Detection Script im `<head>` verhindert FOUC beim Dark/Light Mode

#### Fixed
- Theme Switcher: System-Präferenz wird korrekt respektiert; `localStorage` nur bei expliziter Nutzerwahl

---

### Projekt

#### Changed
- `.gitignore` umfassend aktualisiert: `.env.staging`/`.env.production` hinzugefügt, Backup-Files aller Varianten, inaktive Plugins, Test-Artefakte (`playwright-report/`, `test-results/`), `query-monitor/`

---

## [1.0.0] - 2026-01-27

### Added
- Initial theme setup
- Homepage template mit Hero, Features, CTA
- Card und Button Components
- Mobile-responsive Navigation
- Custom MU-Plugins
- Deployment Scripts
- Figma Design Tokens integriert (Color System, Typography Scale, Spacing System)
- Custom Theme Struktur mit ACF Integration
- Custom Post Types
- SEO Optimierung

## [0.1.0] - 2026-01-20

### Added
- Projektinitialisierung
- Git Repository Setup
- Vite Build System

## [1.4.1] – 2026-03-04

### Security

**media-lab-seo 1.1.1:**
- fix(security): F-04 – Wildcard-Query in `redirects.php` auf `$wpdb->prepare()` + `esc_like()` umgestellt
- fix(security): Open Redirect via Wildcard-Suffix – `$_SERVER['REQUEST_URI']` Suffix wird nun auf Path-Traversal (`../`) und Protocol-Injection (`//`) geprüft und bereinigt
- fix(security): 404-Log – URL und Referrer auf 512 Zeichen begrenzt (DB-Flooding)
- fix(security): `$_SERVER['REQUEST_URI']` via `wp_parse_url()` + `substr()` sanitiert

## [1.5.0] – 2026-03-04

### Added

**Theme – 404.php:**
- Neue 404-Seite mit großer animierter Zahl, Suchformular und Navigationslinks aus dem Hauptmenü
- SCSS-Komponente `pages/_404.scss` mit Dark Mode Support und responsivem Layout

**media-lab-agency-core 1.5.2:**
- Maintenance Mode (`inc/maintenance.php`) – 503-Header, Admin-Bypass, ACF-konfigurierbar
  - Toggle in Agency Core → Einstellungen → Maintenance Mode
  - Konfigurierbar: Überschrift, Nachricht, Datum, Logo, Browser-Titel
  - Eingeloggte Admins sehen die normale Site + orangenen Admin-Bar-Indikator
  - Fallback via `define('MEDIALAB_MAINTENANCE_MODE', true)` in wp-config.php

## [1.5.0] – Release 2026-03-04

### Versionen
- custom-theme: 1.5.0
- agency-core: 1.5.2
- media-lab-seo: 1.1.1

### Zusammenfassung
Bugfixes (AJAX, Swiper, Selektoren), Security F-04, 404-Seite, Maintenance Mode, Footer Navigation.

## [1.5.1] – 2026-03-04

### Added
**media-lab-agency-core 1.5.3:**
- feat: Media Replace (`inc/media-replace.php`) – Mediendateien ersetzen ohne Verlust der Attachment-ID
  - Button in Attachment-Detailseite + Medien-Listenansicht
  - Thumbnails werden automatisch neu generiert
  - Optionaler Dateiname-Erhalt, MIME-Typ-Update, Activity-Log-Integration

### Docs
- 01_README.md: neue Features ergänzt
- 03_PLUGINS.md: Media Replace + Maintenance Mode dokumentiert, Versionen aktualisiert
- 07_TROUBLESHOOTING.md: 3 neue Einträge (Maintenance, Media Replace, 404)
- Alle 13 Docs auf v1.5.0 / 2026-03-04 aktualisiert
