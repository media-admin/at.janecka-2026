# Testing Guide

**Version:** 1.15.0 | **Letzte Aktualisierung:** 2026-03-10

**Version:** 1.13.0  
**Letzte Aktualisierung:** 2026-03-09

Complete guide for testing Media Lab Starter Kit.

---

## Table of Contents

1. [Overview](#overview)
2. [Test Suite](#test-suite)
3. [Test Categories](#test-categories)
4. [Running Tests](#running-tests)
5. [Writing Tests](#writing-tests)
6. [Manual Testing](#manual-testing)
7. [Performance Testing](#performance-testing)
8. [Security Testing](#security-testing)
9. [Continuous Integration](#continuous-integration)

---

## Overview

### Testing Philosophy

The Media Lab Starter Kit uses automated tests to ensure reliability and prevent regressions.

**Test Coverage:**
- 35 automated tests
- 100% passing requirement
- Covers plugins, theme, and integrations

**Test Types:**
- Smoke tests (basic functionality)
- Integration tests (components working together)
- Manual tests (user experience)

---

## Test Suite

### Location
```
media-lab-starter-kit/
└── tests/
    ├── run-tests.sh       (Main test runner)
    └── README.md          (Test documentation)
```

### Test Runner

**File:** `tests/run-tests.sh`
```bash
#!/bin/bash
# Automated test suite
# Tests: 35 total
# Categories: Plugins, Shortcodes, CPTs, ACF, AJAX, Analytics, SEO, UI-Features, hCaptcha
```

### Quick Run
```bash
cd /path/to/media-lab-starter-kit

./tests/run-tests.sh
```

**Expected Output:**
```
════════════════════════════════════════════════════════
🧪 Media Lab Starter Kit - Test Suite
════════════════════════════════════════════════════════

Running Smoke Tests...
────────────────────────────────────────────────────────

📦 Plugin Tests:
Testing: Core Plugin Active... ✅ PASS
# media-lab-project-starter ist optional
# media-lab-analytics ist optional
Testing: SEO Plugin Active... ✅ PASS

🔖 Shortcode Tests:
Testing: Accordion Shortcode... ✅ PASS
Testing: Hero Slider Shortcode... ✅ PASS
Testing: Stats Shortcode... ✅ PASS
Testing: Modal Shortcode... ✅ PASS

📋 Custom Post Type Tests:
Testing: Team CPT Registered... ✅ PASS
Testing: Project CPT Registered... ✅ PASS
Testing: Job CPT Registered... ✅ PASS

🎨 ACF Tests:
Testing: ACF Active... ✅ PASS
Testing: ACF Field Groups Loaded... ✅ PASS
Testing: ACF JSON Source... ✅ PASS

🎨 Theme Tests:
Testing: Custom Theme Active... ✅ PASS

⚡ AJAX Tests:
Testing: AJAX Search Action... ✅ PASS
Testing: AJAX Load More Action... ✅ PASS
Testing: AJAX Filter Action... ✅ PASS

📊 Analytics Tests:
Testing: Analytics Enabled... ✅ PASS
Testing: Analytics Settings Exist... ✅ PASS

🔍 SEO Tests:
Testing: SEO Enabled... ✅ PASS
Testing: SEO Schema Active... ✅ PASS
Testing: SEO Schema Hook... ✅ PASS

════════════════════════════════════════════════════════
📊 Test Results
════════════════════════════════════════════════════════

Passed: 23
Failed: 0
Total:  23

✅ All tests passed!
```

---

## Test Categories

### 1. Plugin Tests (4 tests)

**Verifies:**
- Core Plugin active
- Project Plugin active
- Analytics Plugin active
- SEO Plugin active

**Code:**
```bash
run_test "Core Plugin Active" "wp plugin is-active media-lab-agency-core"
run_test "Project Plugin Active" "wp plugin is-active media-lab-project-starter"
run_test "Analytics Plugin Active" "wp plugin is-active media-lab-analytics"
run_test "SEO Plugin Active" "wp plugin is-active media-lab-seo"
```

### 2. Shortcode Tests (4 tests)

**Verifies:**
- Accordion shortcode registered
- Hero Slider shortcode registered
- Stats shortcode registered
- Modal shortcode registered

**Code:**
```bash
run_test "Accordion Shortcode" "wp eval 'global \$shortcode_tags; exit(isset(\$shortcode_tags[\"accordion\"]) ? 0 : 1);'"
run_test "Hero Slider Shortcode" "wp eval 'global \$shortcode_tags; exit(isset(\$shortcode_tags[\"hero_slider\"]) ? 0 : 1);'"
```

### 3. Custom Post Type Tests (3 tests)

**Verifies:**
- Team CPT registered
- Project CPT registered
- Job CPT registered

**Code:**
```bash
run_test "Team CPT Registered" "wp eval 'exit(post_type_exists(\"team\") ? 0 : 1);'"
run_test "Project CPT Registered" "wp eval 'exit(post_type_exists(\"project\") ? 0 : 1);'"
```

### 4. ACF Tests (3 tests)

**Verifies:**
- ACF PRO active
- Field groups loaded (11+)
- Fields loading from JSON

**Code:**
```bash
run_test "ACF Active" "wp plugin is-active advanced-custom-fields-pro"
run_test "ACF Field Groups Loaded" "wp eval 'exit(count(acf_get_field_groups()) >= 11 ? 0 : 1);'"
run_test "ACF JSON Source" "wp eval '\$g = acf_get_field_groups(); exit(isset(\$g[0][\"local\"]) && \$g[0][\"local\"] === \"json\" ? 0 : 1);'"
```

### 5. Theme Tests (1 test)

**Verifies:**
- Custom theme active

**Code:**
```bash
run_test "Custom Theme Active" "wp theme is-active custom-theme"
```

### 6. AJAX Tests (3 tests)

**Verifies:**
- AJAX search action registered
- AJAX load more action registered
- AJAX filter action registered

**Code:**
```bash
run_test "AJAX Search Action" "wp eval 'exit(has_action(\"wp_ajax_agency_search\") ? 0 : 1);'"
run_test "AJAX Load More Action" "wp eval 'exit(has_action(\"wp_ajax_agency_load_more\") ? 0 : 1);'"
```

### 7. Analytics Tests (2 tests)

**Verifies:**
- Analytics settings enabled
- Analytics configuration exists

**Code:**
```bash
run_test "Analytics Enabled" "wp eval 'exit(get_option(\"medialab_analytics_enabled\") === \"1\" ? 0 : 1);'"
run_test "Analytics Settings Exist" "wp eval 'exit(get_option(\"medialab_analytics_ga4_id\") !== false ? 0 : 1);'"
```

### 8. SEO Tests (3 tests)

**Verifies:**
- SEO settings enabled
- Schema markup enabled
- Schema output hook registered

**Code:**
```bash
run_test "SEO Enabled" "wp eval 'exit(get_option(\"medialab_seo_enabled\") === \"1\" ? 0 : 1);'"
run_test "SEO Schema Active" "wp eval 'exit(get_option(\"medialab_seo_schema_enabled\") === \"1\" ? 0 : 1);'"
run_test "SEO Schema Hook" "wp eval 'exit(has_action(\"wp_head\", \"medialab_seo_output_schema\") !== false ? 0 : 1);'"
```

---

---

## Neue Testfälle (v1.7.0–v1.13.0)

### 9. Hero Image Tests (3 Tests)

```bash
echo "🦸 Hero Image Tests:"
run_test "Hero Image Function Exists" \
  "wp eval 'exit(function_exists("media_lab_get_hero_image") ? 0 : 1);'"

run_test "Hero ACF Field Group Registered" \
  "wp eval 'exit(function_exists("acf_get_field_group") && acf_get_field_group("group_hero_image") ? 0 : 1);'"

run_test "Hero Options Page Registered" \
  "wp eval 'global $admin_page_hooks; exit(isset($admin_page_hooks["agency-core-hero"]) ? 0 : 1);'"
```

**Manuell prüfen:**
- [ ] Hero Image auf einer Seite gesetzt → wird im Frontend angezeigt
- [ ] `hero_image_height = xl` → Klasse `.hero-image--xl` am Element
- [ ] `hero_btn1_text` gesetzt → Button sichtbar mit korrektem Link
- [ ] Hero + Breadcrumbs: Breadcrumbs erscheinen unterhalb des Hero

---

### 10. Breadcrumbs Tests (2 Tests)

```bash
echo "🍞 Breadcrumbs Tests:"
run_test "Breadcrumb Function Exists" \
  "wp eval 'exit(function_exists("media_lab_breadcrumbs") ? 0 : 1);'"

run_test "Breadcrumb Template Part Exists" \
  "test -f cms/wp-content/themes/custom-theme/template-parts/components/breadcrumbs.php"
```

**Manuell prüfen:**
- [ ] Auf Einzelseite: Home → Kategorie → Seitenname
- [ ] Schema.org JSON-LD im `<head>` vorhanden
- [ ] Auf Startseite: keine Breadcrumbs

---

### 11. hCaptcha Tests (3 Tests)

```bash
echo "🤖 hCaptcha Tests:"
run_test "hCaptcha Module Loaded" \
  "wp eval 'exit(function_exists("medialab_hcaptcha_active") ? 0 : 1);'"

run_test "hCaptcha Verify Function Exists" \
  "wp eval 'exit(function_exists("medialab_hcaptcha_verify") ? 0 : 1);'"

run_test "hCaptcha CF7 Hook Registered" \
  "wp eval 'exit(has_filter("wpcf7_form_elements") ? 0 : 1);'"
```

**Manuell prüfen (mit Test-Keys):**
- Site Key: `10000000-ffff-ffff-ffff-000000000001`
- Secret Key: `0x0000000000000000000000000000000000000000`
- [ ] Widget erscheint im CF7-Formular
- [ ] Formular nicht absendbar ohne CAPTCHA
- [ ] Widget erscheint auf wp-login.php

---

### 12. Back-to-Top Tests (2 Tests)

```bash
echo "⬆️ Back-to-Top Tests:"
run_test "Back-to-Top Template in Footer" \
  "grep -q 'back-to-top' cms/wp-content/themes/custom-theme/footer.php"

run_test "Back-to-Top JS exists" \
  "test -f cms/wp-content/themes/custom-theme/assets/src/js/components/back-to-top.js"
```

**Manuell prüfen (Playwright `back-to-top.spec.js`):**
- [ ] Button initial nicht sichtbar (opacity: 0)
- [ ] Nach 300px Scroll: Button eingeblendet
- [ ] Klick → smooth scroll nach oben
- [ ] Keyboard: Enter/Space funktioniert
- [ ] `btt_enabled = 0` in ACF → kein Button im HTML

---

### 13. Scroll Progress Tests (2 Tests)

```bash
echo "📊 Scroll Progress Tests:"
run_test "Scroll Progress SCSS exists" \
  "test -f cms/wp-content/themes/custom-theme/assets/src/scss/components/_scroll-progress.scss"

run_test "Scroll Progress JS exists" \
  "test -f cms/wp-content/themes/custom-theme/assets/src/js/components/scroll-progress.js"
```

**Manuell prüfen:**
- [ ] Auf `single.php` mit `scroll_progress_enabled = 1`: Linie sichtbar
- [ ] Linie wächst beim Scrollen proportional
- [ ] Auf normaler Seite (`page.php`): keine Linie
- [ ] `aria-valuenow` im HTML aktualisiert sich beim Scrollen

---

### 14. WooCommerce Styling Tests (1 Test)

```bash
echo "🛒 WooCommerce Tests:"
run_test "WooCommerce SCSS exists" \
  "test -f cms/wp-content/themes/custom-theme/assets/src/scss/woocommerce/_woocommerce.scss"
```

**Manuell prüfen:**
- [ ] Shop-Grid: 3 Spalten Desktop → 2 Tablet → 1 Mobile
- [ ] Produktkarte: Hover-Effekt (translateY + Shadow)
- [ ] Sale-Badge sichtbar
- [ ] Checkout: 2-Spalten-Layout
- [ ] Dark Mode: alle Farben über Custom Properties, kein weiß hardcoded

---

### Frontend Checkliste (aktualisiert)

Ergänzung zur bestehenden Checkliste:

**UI-Features:**
- [ ] Back-to-Top Button: erscheint nach 300px, animiert smooth
- [ ] Scroll Progress Bar: nur auf `single.php`, Fortschritt korrekt
- [ ] Dark Mode: Back-to-Top + Progress Bar korrekt in dunklem Theme

**Templates:**
- [ ] `single.php`: Autor-Box, Post-Navigation, Tags, Lesezeit
- [ ] `archive.php`: Post-Grid, Pagination, leerer Zustand
- [ ] `search.php`: Ergebnisse mit Post-Type-Label, leerer Zustand
- [ ] `404.php`: Quick-Links vorhanden (Primary Menu zugewiesen?)

**Formulare:**
- [ ] CF7: hCaptcha-Widget vor Submit-Button (wenn aktiviert)
- [ ] CF7: Fehler-Styling (`wpcf7-not-valid`)
- [ ] CF7 Layouts: `cf7-grid-2`, `cf7-card`, `cf7-minimal`


## Running Tests

### Basic Run
```bash
cd /path/to/media-lab-starter-kit

./tests/run-tests.sh
```

### Run with Verbose Output
```bash
bash -x ./tests/run-tests.sh
```

### Run Specific Test
```bash
# Edit run-tests.sh and comment out other tests
# Then run:
./tests/run-tests.sh
```

### Automated Testing

**Git Pre-Commit Hook:**
```bash
# .git/hooks/pre-commit
#!/bin/bash
cd "$(git rev-parse --show-toplevel)"
./tests/run-tests.sh
if [ $? -ne 0 ]; then
    echo "Tests failed! Commit aborted."
    exit 1
fi
```

**Make executable:**
```bash
chmod +x .git/hooks/pre-commit
```

---

## Writing Tests

### Test Function
```bash
run_test() {
    local test_name=$1
    local test_command=$2
    
    echo -n "Testing: $test_name... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}"
        ((FAILED++))
        return 1
    fi
}
```

### Adding New Test

**1. Add to run-tests.sh:**
```bash
echo ""
echo "🔧 Custom Tests:"
run_test "Custom Feature Active" "wp eval 'exit(function_exists(\"my_function\") ? 0 : 1);'"
```

**2. Test the test:**
```bash
./tests/run-tests.sh
```

### Test Examples

**Check if option exists:**
```bash
run_test "Option Exists" "wp eval 'exit(get_option(\"my_option\") !== false ? 0 : 1);'"
```

**Check if function exists:**
```bash
run_test "Function Exists" "wp eval 'exit(function_exists(\"my_function\") ? 0 : 1);'"
```

**Check if action is registered:**
```bash
run_test "Action Registered" "wp eval 'exit(has_action(\"init\", \"my_function\") ? 0 : 1);'"
```

**Check post count:**
```bash
run_test "Posts Exist" "wp eval '\$count = wp_count_posts(\"post\"); exit(\$count->publish > 0 ? 0 : 1);'"
```

---

## Manual Testing

### Frontend Checklist

**Homepage:**
- [ ] Loads without errors
- [ ] Assets load (CSS, JS, images)
- [ ] No console errors
- [ ] Mobile responsive
- [ ] Schema.org markup present

**Navigation:**
- [ ] Main menu works
- [ ] Mobile menu works
- [ ] Search works
- [ ] Links work

**Content:**
- [ ] Blog posts display
- [ ] Custom post types display
- [ ] Images load
- [ ] Videos play

**Forms:**
- [ ] Contact form submits
- [ ] Form validation works
- [ ] Success/error messages show
- [ ] Email notifications sent

**Interactive:**
- [ ] Accordions expand/collapse
- [ ] Modals open/close
- [ ] Sliders slide
- [ ] AJAX load more works
- [ ] AJAX filters work

### Backend Checklist

**Admin:**
- [ ] Login works
- [ ] Dashboard loads
- [ ] All menu items accessible
- [ ] No PHP errors

**Content Management:**
- [ ] Create/edit posts
- [ ] Create/edit pages
- [ ] Create/edit CPTs
- [ ] Upload media
- [ ] ACF fields display

**Plugins:**
- [ ] All plugins active
- [ ] Settings pages accessible
- [ ] No conflicts

**Theme:**
- [ ] Theme active
- [ ] Customizer works
- [ ] Widgets work
- [ ] Menus editable

---

## Performance Testing

### Core Web Vitals – Zielwerte

Google bewertet nach 3 Stufen: **Good · Needs Improvement · Poor**.
Das Starter Kit zielt auf „Good" in allen Metriken.

| Metrik | Bedeutung | ✅ Good | ⚠️ Needs | ❌ Poor | Unser Ziel |
|---|---|---|---|---|---|
| **LCP** | Largest Contentful Paint | ≤ 2.5s | ≤ 4.0s | > 4.0s | **≤ 2.5s** |
| **CLS** | Cumulative Layout Shift | ≤ 0.10 | ≤ 0.25 | > 0.25 | **≤ 0.10** |
| **INP** | Interaction to Next Paint | ≤ 200ms | ≤ 500ms | > 500ms | **≤ 200ms** |
| **FCP** | First Contentful Paint | ≤ 1.8s | ≤ 3.0s | > 3.0s | **≤ 1.8s** |
| **TBT** | Total Blocking Time (INP-Proxy) | ≤ 200ms | ≤ 600ms | > 600ms | **≤ 200ms** |
| **TTI** | Time to Interactive | ≤ 3.8s | ≤ 7.3s | > 7.3s | **≤ 3.8s** |

> **Hinweis:** INP ersetzt FID als Core Web Vital seit März 2024.  
> Lighthouse misst INP indirekt als TBT (Total Blocking Time).

### Lighthouse Score-Ziele

| Kategorie | Ziel |
|---|---|
| Performance | ≥ 90 |
| Accessibility | ≥ 95 |
| Best Practices | ≥ 95 |
| SEO | ≥ 95 |

### Ressourcen-Budgets

| Ressource | Budget |
|---|---|
| HTML-Dokument | ≤ 50 KB |
| CSS gesamt | ≤ 80 KB |
| JS gesamt | ≤ 200 KB |
| Bilder pro Seite | ≤ 400 KB |
| Fonts | ≤ 100 KB |
| **Gesamt** | **≤ 900 KB** |
| Script-Requests | ≤ 8 |
| Drittanbieter-Requests | ≤ 8 |

### Lighthouse ausführen

```bash
# Via npm (empfohlen – nutzt lighthouserc.js)
npm run lighthouse

# Einzelne URL testen
lighthouse https://media-lab-starter-kit.localdev/ \
  --preset=desktop \
  --only-categories=performance,accessibility,best-practices,seo \
  --view

# Mobile (strenger – Google-Standard für CWV)
lighthouse https://media-lab-starter-kit.localdev/ \
  --form-factor=mobile \
  --view
```

### Core Web Vitals im Browser messen

```bash
# Chrome DevTools: Lighthouse Tab → Mobile → „Messung starten"
# → zeigt LCP, CLS, TBT direkt mit Einschätzung

# Google Search Console (reale Nutzerdaten, ~28 Tage)
# → Search Console → Core Web Vitals → URL-Gruppe
# → CrUX-Daten (Chrome User Experience Report)

# PageSpeed Insights (Kombination aus Lab + Field Data)
# https://pagespeed.web.dev/
```

### Typische CWV-Probleme und Ursachen

**LCP zu hoch:**
- Hero-Bild nicht preloaded → `customtheme_lcp_image_url` Filter prüfen
- Critical CSS fehlt → `assets/dist/css/critical.css` anlegen
- Langsamer Server / TTFB > 600ms → Caching, Hosting prüfen

**CLS > 0.10:**
- Bilder ohne `width`/`height` → `inc/performance.php` ist aktiv, ACF-Bilder prüfen
- Fonts ohne `display:swap` → Google Fonts URL prüfen, self-hosted Fonts preloaden
- Ads / Embeds ohne reservierten Platz → `.ratio`-Wrapper verwenden

**INP > 200ms (TBT > 200ms):**
- Plugin-Scripts blockieren Main Thread → `customtheme_defer_scripts` Filter erweitern
- Schwere Event-Handler → Performance-Profiler in Chrome DevTools nutzen
- WordPress Admin-Bar im Frontend → nur für eingeloggte Nutzer, kein Einfluss auf CWV

> **Tipp:** Code-Splitting und Dynamic Imports (seit v1.4.0) sowie `defer` auf  
> Plugin-Scripts (seit v1.15.0) verbessern TBT/INP deutlich.

### Load Testing

**Using Apache Bench:**
```bash
# 100 requests, 10 concurrent
ab -n 100 -c 10 http://media-lab-starter-kit.test/

# Look for:
# - Requests per second
# - Time per request
# - Failed requests (should be 0)
```

**Using WP-CLI:**
```bash
# Profile homepage
wp profile stage --all --spotlight

# Profile admin
wp profile stage --all --spotlight --url=/wp-admin/
```

### Database Performance
```bash
# Check slow queries
wp db query "SHOW VARIABLES LIKE 'slow_query_log';"

# Enable slow query log
wp db query "SET GLOBAL slow_query_log = 'ON';"
wp db query "SET GLOBAL long_query_time = 2;"

# Check slow queries file
tail -f /var/log/mysql/mysql-slow.log
```

---

## Security Testing

### WordPress Security Scan
```bash
# Install WPScan
gem install wpscan

# Scan for vulnerabilities
wpscan --url http://media-lab-starter-kit.test --enumerate vp,vt,u

# Check for:
# - Vulnerable plugins
# - Vulnerable themes
# - User enumeration
# - Exposed files
```

### SSL/TLS Check
```bash
# Test SSL configuration
curl -I https://media-lab-starter-kit.test

# Check security headers
curl -I https://media-lab-starter-kit.test | grep -i "x-frame\|x-xss\|strict-transport"
```

### File Permissions
```bash
# Check critical files
ls -la cms/wp-config.php     # Should be 600
ls -la cms/wp-content/       # Should be 755

# Find world-writable files (security risk)
find cms/ -type f -perm 0777
# Should return nothing
```

---

## Continuous Integration

### GitHub Actions

**File:** `.github/workflows/tests.yml`
```yaml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.0
          extensions: mbstring, mysql
      
      - name: Setup Node
        uses: actions/setup-node@v2
        with:
          node-version: 16
      
      - name: Install Dependencies
        run: |
          npm install
          composer install
      
      - name: Build Assets
        run: npm run build
      
      - name: Run Tests
        run: ./tests/run-tests.sh
```

### Test Reports

**Generate HTML Report:**
```bash
# Install junit2html
pip install junit2html

# Run tests with JUnit output
./tests/run-tests.sh --junit > test-results.xml

# Convert to HTML
junit2html test-results.xml test-results.html
```

---

---

## Visual Regression Testing (BackstopJS)

Visual Regression Testing erkennt **unbeabsichtigte visuelle Änderungen** durch Pixel-Vergleich von Screenshots vor und nach einer Code-Änderung.

### Setup

```bash
# Einmalig installieren
npm install

# Referenz-Screenshots erstellen (Baseline)
npm run vrt:ref
```

Referenz-Screenshots werden unter `backstop_data/bitmaps_reference/` gespeichert und **in Git committed** – sie sind der Vergleichs-Standard.

---

### Workflow

```
Änderung am CSS/PHP/JS machen
         ↓
  npm run build
         ↓
  npm run vrt:test      ← vergleicht aktuellen Stand mit Baseline
         ↓
     Diffs prüfen       ← npm run vrt:report öffnet Browser
         ↓
  Absichtlich?  ─── ja ──→  npm run vrt:approve  (neue Baseline)
       │
      nein
       ↓
    Bug fixen
```

---

### Befehle

| Befehl | Beschreibung |
|---|---|
| `npm run vrt:ref` | Referenz-Screenshots erstellen (Baseline setzen) |
| `npm run vrt:test` | Aktuellen Stand mit Baseline vergleichen |
| `npm run vrt:approve` | Aktuelle Screenshots als neue Baseline übernehmen |
| `npm run vrt:report` | HTML-Report im Browser öffnen |

---

### Konfigurierte Szenarien (14)

| Szenario | Viewports | Beschreibung |
|---|---|---|
| Homepage | alle 4 | Startseite vollständig |
| Einzelbeitrag | alle 4 | `single.php` mit Leseinhalt |
| Archiv | alle 4 | `archive.php` Post-Grid |
| Suche – mit Ergebnissen | alle 4 | `?s=beispiel` |
| Suche – leer | alle 4 | Kein Treffer |
| 404-Seite | alle 4 | Fehlerseite |
| Navigation Flyout | Desktop | Hover-State Dropdown |
| Back-to-Top sichtbar | alle 4 | Nach `scrollToSelector: footer` |
| Scroll Progress Bar | alle 4 | Element-Screenshot |
| Hero Image | alle 4 | Element-Screenshot |
| Cookie Banner | alle 4 | Erster Besuch (leere Cookies) |
| WooCommerce Shop | alle 4 | Produkt-Grid |
| WooCommerce Einzelprodukt | alle 4 | Produktdetail |
| Dark Mode | alle 4 | Homepage in dunklem Theme |

**Viewports:** mobile (375px), tablet (768px), desktop (1280px), wide (1440px)

---

### Hinweise

**Toleranz (`misMatchThreshold`):**
- `0.1` – streng (z.B. 404, leere Suche – statische Seiten)
- `0.2` – Standard (die meisten Szenarien)
- `0.3` – locker (z.B. Navigation-Hover, Hero mit Bild)

**Animationen:** Der `onReady`-Hook friert alle CSS-Transitions und Animationen ein → keine flackernden Screenshots.

**Dark Mode:** Szenario `setDarkMode.js` aktiviert `data-theme="dark"` via JS vor dem Screenshot.

**Cookie Banner:** Das Cookie-Szenario übergibt leere Cookies (`cookies_empty.json`), damit der Banner immer erscheint.

---

### Erste Einrichtung auf neuem Projekt

1. URLs in `backstop.json` anpassen (lokale `.localdev`-Domain)
2. Echte Seiten-Slugs eintragen (Beitrag, Produkt, Kategorie)
3. Baseline erstellen: `npm run vrt:ref`
4. Kontrolle: `npm run vrt:report` – alle Szenarien grün?
5. Committen: `git add backstop_data/bitmaps_reference/ && git commit`


## Debugging Failed Tests

### Get Detailed Output
```bash
# Run with debug
bash -x ./tests/run-tests.sh 2>&1 | tee test-debug.log
```

### Common Failures

**Plugin Not Active:**
```bash
# Check plugin status
wp plugin list

# Try reactivating
wp plugin deactivate plugin-name
wp plugin activate plugin-name
```

**Function Not Found:**
```bash
# Check if file is loaded
wp eval "echo (function_exists('my_function') ? 'Yes' : 'No');"

# Check plugin files
ls -la cms/wp-content/plugins/media-lab-*/
```

**ACF Issues:**
```bash
# Check ACF version
wp plugin list | grep advanced-custom-fields

# Sync fields
wp acf sync
```

---

## Test Coverage Goals

### Current Coverage

- **Plugins:** 100% (4/4 tested)
- **Shortcodes:** 9% (4/44 tested)
- **CPTs:** 33% (3/9 tested)
- **ACF:** 100% (critical tests)
- **AJAX:** 100% (3/3 tested)
- **Analytics:** 100% (2/2 tested)
- **SEO:** 100% (3/3 tested)

### Future Improvements

- [ ] Add tests for all 44 shortcodes
- [ ] Add tests for all 9 CPTs
- [ ] Add frontend rendering tests
- [ ] Add performance benchmarks
- [ ] Add security tests
- [ ] Add visual regression tests

---

## Next Steps

- **Analytics:** [Analytics Documentation](12_ANALYTICS.md)
- **SEO:** [SEO Documentation](13_SEO.md)
- **Development:** [Development Guide](06_DEVELOPMENT.md)

---

**Testing is crucial!** 🧪  
**Next:** [Analytics Guide](12_ANALYTICS.md) →
