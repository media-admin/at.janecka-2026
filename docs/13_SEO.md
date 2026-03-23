# SEO Dokumentation

**Version:** 1.13.0 | **Letzte Aktualisierung:** 2026-03-10
**Plugin:** `media-lab-seo` v1.3.0

---

## Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Installation & Menü](#installation--menü)
3. [SEO Dashboard](#seo-dashboard)
4. [Google Search Console (GSC)](#google-search-console-gsc)
5. [Analytics-Adapter](#analytics-adapter)
6. [Wöchentlicher Report-Mailer](#wöchentlicher-report-mailer)
7. [Schema.org Markup](#schemaorg-markup)
8. [Open Graph Tags](#open-graph-tags)
9. [Twitter Cards](#twitter-cards)
10. [Breadcrumbs](#breadcrumbs)
11. [Weiterleitungen](#weiterleitungen)
12. [Troubleshooting](#troubleshooting)

---

## Übersicht

`media-lab-seo` ist das zentrale SEO-Plugin des Starter Kits. Es deckt ab:

| Modul | Beschreibung | Seit |
|---|---|---|
| Schema.org | Strukturierte Daten (JSON-LD) | v1.0.0 |
| Open Graph | Social Sharing (Facebook, LinkedIn) | v1.0.0 |
| Twitter Cards | Rich Previews auf Twitter/X | v1.0.0 |
| Breadcrumbs | Navigation + Schema.org BreadcrumbList | v1.0.0 |
| Canonical URLs | Duplicate Content Prevention | v1.0.0 |
| Weiterleitungen | 301/302-Manager im Backend | v1.0.0 |
| SEO Dashboard | GSC-KPIs im WordPress-Backend | v1.2.0 |
| GSC API | Google Search Console OAuth2-Anbindung | v1.2.0 |
| Report-Mailer | Wöchentlicher HTML-Report per E-Mail | v1.2.0 |
| GA4 Adapter | Google Analytics 4 Data API | v1.3.0 |
| Matomo Adapter | Matomo Reporting API | v1.3.0 |

---

## Installation & Menü

### Plugin aktivieren

```bash
wp plugin activate media-lab-seo
```

### Menü-Struktur

Nach der Aktivierung erscheint ein eigener Top-Level-Menüpunkt in der WordPress-Sidebar:

```
Media Lab SEO
├── ⚙️ Einstellungen    → Markup-Einstellungen (Schema, OG, Twitter)
└── 📊 Dashboard        → GSC-KPIs, Analytics, Report-Konfiguration
```

> **Hinweis:** In älteren Versionen (< v1.3.0) war der Eintrag unter
> „Einstellungen → SEO Toolkit" zu finden. Seit v1.3.0 ist er ein eigener Menüpunkt.

---

## SEO Dashboard

### Wo zu finden

**WordPress Admin → Media Lab SEO → 📊 Dashboard**

Zusätzlich erscheint ein **Widget auf der WordPress-Übersichtsseite** (`/wp-admin/`) mit den 4 wichtigsten KPIs auf einen Blick.

### Was angezeigt wird

Nach erfolgreicher GSC-Verbindung zeigt das Dashboard:

**KPI-Kacheln** (letzte 28 Tage vs. Vorperiode):
- Klicks
- Impressionen
- Ø CTR
- Ø Position

Jede Kachel zeigt den prozentualen Delta-Wert zur Vorperiode (grün = besser, rot = schlechter).

**Tabellen:**
- Top 10 Keywords (Klicks, Impressionen, CTR, Position)
- Top 10 Seiten (Klicks, Impressionen, CTR, Position)

### Cache

Alle GSC-Daten werden **1 Stunde gecacht** (WordPress Transients). Per „🔄 Cache leeren"-Button kann der Cache manuell geleert werden.

---

## Google Search Console (GSC)

### Voraussetzungen

1. Projekt in der **Google Cloud Console** (https://console.cloud.google.com/)
2. **Search Console API** aktivieren
3. **OAuth2-Zugangsdaten** erstellen (Typ: Webanwendung)
4. Autorisierte Redirect-URI eintragen (wird im Dashboard angezeigt)

### Einrichtung Schritt für Schritt

**1. Google Cloud Console**
```
Neues Projekt → APIs & Dienste → Bibliothek
→ „Google Search Console API" aktivieren

APIs & Dienste → Anmeldedaten → + Anmeldedaten erstellen
→ OAuth-Client-ID → Webanwendung
→ Autorisierte Weiterleitungs-URIs:
   https://deine-domain.at/wp-admin/admin.php?page=medialab-seo-dashboard&gsc_oauth=callback
→ Client-ID und Client-Secret kopieren
```

**2. WordPress Backend**
```
Media Lab SEO → Dashboard → Einstellungen
→ Client ID:     1234567890-xxx.apps.googleusercontent.com
→ Client Secret: ••••••••••
→ Property URL:  https://deine-domain.at/
   (exakt wie in GSC eingetragen, z.B. https://example.at/ oder sc-domain:example.at)
→ Einstellungen speichern
```

**3. Verbinden**
```
Media Lab SEO → Dashboard
→ „Mit Google verbinden →" klicken
→ Google-Konto auswählen + Zugriff erlauben
→ Weiterleitung zurück zum Dashboard
→ Daten werden automatisch geladen
```

### Verbindung trennen

Im Dashboard oben rechts: **„Verbindung trennen"** – löscht alle gespeicherten Tokens und leert den Cache.

### Technische Details

```
Authentifizierung:  OAuth2 Authorization Code Flow
Token-Speicherung:  wp_options (medialab_gsc_refresh_token, medialab_gsc_access_token)
Token-Erneuerung:   Automatisch 5 Minuten vor Ablauf
Cache:              WordPress Transients, TTL: 1 Stunde
GSC-Verzögerung:    ~3 Tage (wird automatisch berücksichtigt)
```

### Verfügbare PHP-Funktionen

```php
// Verbindungsstatus
medialab_gsc_is_configured();  // Client ID + Secret + Property URL gesetzt?
medialab_gsc_is_connected();   // Refresh Token vorhanden?

// Daten abrufen
$data = medialab_gsc_get_dashboard_data();
// Rückgabe: [
//   'current'  => ['clicks', 'impressions', 'ctr', 'position'],
//   'previous' => ['clicks', 'impressions', 'ctr', 'position'],
//   'keywords' => [['keyword', 'clicks', 'impressions', 'ctr', 'position'], ...],
//   'pages'    => [['url', 'clicks', 'impressions', 'ctr', 'position'], ...],
//   'period'   => ['start' => 'Y-m-d', 'end' => 'Y-m-d'],
//   'error'    => null | string,
// ]

medialab_gsc_get_top_keywords(10);   // Top 10 Keywords
medialab_gsc_get_top_pages(10);      // Top 10 Seiten
medialab_gsc_flush_cache();          // Cache leeren
```

---

## Analytics-Adapter

### Konzept

Pluggbare Schnittstelle für Pageview- und Traffic-Daten. Nur ein Adapter ist gleichzeitig aktiv.

**Priorität (Auto-Detection):**
1. Externer Filter `medialab_analytics_adapter`
2. GA4 – wenn Property ID + Service Account JSON konfiguriert
3. Matomo – wenn URL + Site ID + Token konfiguriert
4. Stub – kein Adapter konfiguriert (0-Werte)

---

### Google Analytics 4 (GA4)

#### Voraussetzungen

- **Google Analytics Data API** in der Cloud Console aktivieren
- Service Account erstellen → **JSON-Key** herunterladen
- In GA4: Verwaltung → Kontozugriff → Service-Account-E-Mail als **Betrachter** hinzufügen

> Kein zweiter OAuth-Flow – Authentifizierung via JWT (RS256).

#### Einrichtung

```
Media Lab SEO → Dashboard → Einstellungen → Google Analytics 4

Property ID:           123456789
                       (numerische ID, nicht G-XXXXXXXX)
                       GA4 → Verwaltung → Property-Einstellungen

Service Account JSON:  Inhalt der JSON-Key-Datei einfügen
                       → Einstellungen speichern
```

---

### Matomo

#### Einrichtung

```
Media Lab SEO → Dashboard → Einstellungen → Matomo

Matomo URL:    https://matomo.example.at/
Site ID:       1   (Matomo → Verwaltung → Websites)
API-Token:     ••••  (Matomo → Persönliche Einstellungen → API-Token)
→ Einstellungen speichern
→ „🔌 Verbindung testen" klicken
```

#### Dev-Umgebung (kein SSL)

```php
add_filter( 'medialab_matomo_sslverify', '__return_false' );
```

---

### Eigenen Adapter implementieren

```php
add_filter( 'medialab_analytics_adapter', function() {
    return new class {
        public function is_configured(): bool { return true; }
        public function get_label(): string { return 'Mein Anbieter'; }
        public function get_overview( string $start, string $end ): array {
            return [ 'pageviews' => 0, 'sessions' => 0, 'users' => 0, 'bounce_rate' => 0.0 ];
        }
        public function get_top_sources( int $limit = 5 ): array { return []; }
    };
} );
```

---

## Wöchentlicher Report-Mailer

### Konfiguration

```
Media Lab SEO → Dashboard → Einstellungen → Wöchentlicher SEO-Report

✅ Wöchentlichen Report senden
Empfänger:      kunde@beispiel.at
Absender Name:  Media Lab Agentur
Absender Mail:  seo@agentur.at
Versandtag:     Montag
Uhrzeit:        08:00
→ Einstellungen speichern
```

### Report-Inhalt

HTML-Mail (Inline-CSS, kompatibel mit Gmail, Outlook, Apple Mail):

- KPI-Kacheln mit Δ-Farben (grün = besser, rot = schlechter)
- Top 8 Keywords mit Positions-Badges (≤3 grün · ≤10 gelb · >10 rot)
- Top 8 Seiten mit Klicks und Position
- CTA-Button → WordPress-Dashboard

**Betreff:** `SEO Report: Seitenname · 01.03. – 28.03.2026`

### Test-Mail senden

```
Media Lab SEO → Dashboard → „📧 Test-Report jetzt senden"
```

### WP-CLI

```bash
# Report sofort auslösen
wp cron event run medialab_seo_weekly_report

# Nächsten geplanten Versand anzeigen
wp cron event list | grep medialab
```

---

## Schema.org Markup

| Typ | Seite |
|---|---|
| `Organization` | Startseite |
| `WebSite` | Alle Seiten (inkl. `SearchAction`) |
| `Article` | Einzelne Posts |
| `Product` | WooCommerce-Produkte |
| `BreadcrumbList` | Alle Unterseiten |

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [ { "@type": "Organization", ... }, ... ]
}
</script>
```

**Eigene Typen ergänzen:**
```php
add_filter( 'medialab_seo_schema_types', function( $types, $post ) {
    if ( $post->post_type === 'event' ) {
        $types[] = [ '@type' => 'Event', 'name' => get_the_title( $post ), ... ];
    }
    return $types;
}, 10, 2 );
```

---

## Open Graph Tags

Automatisch auf allen Seiten – Bild: Featured Image → Default Social Image.

```html
<meta property="og:title"       content="Seitentitel">
<meta property="og:description" content="Beschreibung">
<meta property="og:image"       content="https://.../bild.jpg">
<meta property="og:url"         content="https://...">
```

**Empfohlene Bildgröße:** 1200×630px | **Testen:** https://developers.facebook.com/tools/debug/

---

## Twitter Cards

```html
<meta name="twitter:card"  content="summary_large_image">
<meta name="twitter:title" content="Seitentitel">
<meta name="twitter:image" content="https://.../bild.jpg">
```

**Konfiguration:** Media Lab SEO → ⚙️ Einstellungen → Twitter Username

**Testen:** https://cards-dev.twitter.com/validator

---

## Breadcrumbs

```php
// In Templates
if ( function_exists( 'medialab_seo_breadcrumbs' ) ) {
    medialab_seo_breadcrumbs( [
        'separator'     => ' › ',
        'home_title'    => 'Home',
        'wrapper_class' => 'breadcrumbs',
    ] );
}
```

---

## Weiterleitungen

**Media Lab SEO → ⚙️ Einstellungen → Redirects**

- 301 (permanent) und 302 (temporär)
- Wildcard-Pfade unterstützt
- Import/Export als CSV

---

## Troubleshooting

### Dashboard zeigt keine Daten

1. Verbindung prüfen: `Media Lab SEO → Dashboard` – zeigt es „Mit Google verbinden"?
2. Property URL exakt prüfen (mit trailing slash, z.B. `https://example.at/`)
3. GSC-Verzögerung: Neue Websites haben ~3 Tage Verzögerung
4. Cache leeren: „🔄 Cache leeren"-Button

### Report-Mail kommt nicht an

```bash
wp eval "wp_mail('test@example.at', 'Test', 'Test');"
wp cron event list | grep medialab
wp option get medialab_report_last_sent
```

### GA4: „Token-Anfrage fehlgeschlagen"

- JSON-Key vollständig? (inkl. `private_key`)
- Service-Account-E-Mail in GA4 als Betrachter hinzugefügt?
- `Google Analytics Data API` in Cloud Console aktiviert?

### Matomo: „Site nicht gefunden"

- Site ID korrekt (Zahl aus Matomo → Websites)?
- API-Token hat Lesezugriff auf diese Site?

### PHP Deprecated-Warnings (`strpos null`)

Behoben in v1.3.0. Plugin deaktivieren und neu aktivieren, Cache leeren.

### Menüpunkt nicht sichtbar

```bash
wp plugin deactivate media-lab-seo && wp plugin activate media-lab-seo
```

---

## Weiterführende Docs

- [Analytics-Dokumentation](12_ANALYTICS.md)
- [Plugin-Übersicht](03_PLUGINS.md)
- [ACF-Felder](09_ACF-FIELDS.md)
- [Deployment](10_DEPLOYMENT.md)
