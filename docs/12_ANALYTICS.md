# Analytics Dokumentation

**Version:** 1.13.0 | **Letzte Aktualisierung:** 2026-03-10

---

## Übersicht

Analytics-Daten werden seit v1.3.0 direkt im **`media-lab-seo`-Plugin** verwaltet.
Das separate `media-lab-analytics`-Plugin (Tracking: GA4, GTM, Facebook Pixel) bleibt
optional und ist vom SEO-Dashboard unabhängig.

| Was | Wo |
|---|---|
| SEO-KPIs (GSC) | `media-lab-seo` → Dashboard → immer verfügbar |
| Pageviews / Quellen (Adapter) | `media-lab-seo` → Dashboard → optional (GA4 oder Matomo) |
| Tracking-Code-Einbindung (GA4, GTM, Pixel) | `media-lab-analytics` → optional, separates Plugin |

---

## Analytics-Adapter im SEO-Dashboard

Pageview- und Traffic-Daten werden über einen pluggbaren Adapter in das SEO-Dashboard
integriert. Der Kunde entscheidet, welcher Anbieter eingesetzt wird.

**Unterstützte Anbieter:**
- **Google Analytics 4** – via Service Account (kein Cookie-Banner nötig für Backend-Abruf)
- **Matomo** – via Reporting API (DSGVO-konform, selbst gehostet)

**Vollständige Einrichtung:** → [SEO-Dokumentation → Analytics-Adapter](13_SEO.md#analytics-adapter)

---

## Datenschutz-Einordnung

| Anbieter | DSGVO | Anmerkung |
|---|---|---|
| Google Search Console | ✅ Unproblematisch | Aggregierte Suchdaten, keine personenbezogenen Daten |
| Matomo (self-hosted) | ✅ Konform | Kein Drittland-Transfer, keine Cookies nötig |
| Google Analytics 4 | ⚠️ Mit Consent | US-Datentransfer – Consent-Banner erforderlich |
| Google Tag Manager | ⚠️ Je nach Tags | Abhängig von den eingebundenen Tags |
| Facebook Pixel | ⚠️ Mit Consent | US-Datentransfer – Consent-Banner erforderlich |

---

## media-lab-analytics Plugin (optional)

Das separate Plugin ist für **Frontend-Tracking** (Seitenaufrufe, Events, WooCommerce)
zuständig und **nicht** für das Backend-Dashboard.

**Aktivieren:**
```bash
wp plugin activate media-lab-analytics
```

**Konfiguration:** Einstellungen → Analytics
```
✅ Enable Analytics Tracking
Google Analytics 4 ID: G-XXXXXXXXXX
Google Tag Manager ID: GTM-XXXXXXX
Facebook Pixel ID:     XXXXXXXXXXXXXXX
```

### Tracking-Ereignisse

**Automatisch:**
- Seitenaufrufe
- WooCommerce: add_to_cart, begin_checkout, purchase

**Manuell per PHP:**
```php
do_action( 'medialab_track_event', 'button_click', [
    'button_name'     => 'Download PDF',
    'button_location' => 'Hero',
] );
```

**Manuell per JS:**
```javascript
gtag( 'event', 'scroll_depth', { scroll_percent: 75 } );
```

### Admins ausschließen

Admins werden standardmäßig nicht getrackt:
```php
// Deaktivieren für alle Admins (Standard)
// → bereits im Plugin eingebaut (current_user_can('manage_options') Check)
```

### Consent-Integration

```php
// Nur tracken wenn Cookie-Consent gegeben
add_filter( 'medialab_analytics_should_track', function( $track ) {
    // Eigene Consent-Logik
    return isset( $_COOKIE['consent_analytics'] ) && $_COOKIE['consent_analytics'] === '1';
} );
```

---

## Weiterführende Docs

- [SEO Dokumentation](13_SEO.md) – GSC Dashboard, GA4 & Matomo Adapter
- [Plugin-Übersicht](03_PLUGINS.md)
- [Deployment](10_DEPLOYMENT.md)
