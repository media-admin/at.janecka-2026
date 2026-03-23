# Troubleshooting Guide

**Version:** 1.13.0  
**Letzte Aktualisierung:** 2026-03-09

Complete troubleshooting guide for common issues in Media Lab Starter Kit.

---

## Table of Contents

1. [Quick Diagnostics](#quick-diagnostics)
2. [Plugin Issues](#plugin-issues)
3. [Theme Issues](#theme-issues)
4. [Build System Issues](#build-system-issues)
5. [AJAX Issues](#ajax-issues)
6. [ACF Issues](#acf-issues)
7. [Performance Issues](#performance-issues)
8. [Error Messages](#error-messages)
9. [Emergency Recovery](#emergency-recovery)

---

## Quick Diagnostics

### Run Full Diagnostic
```bash
cd /path/to/media-lab-starter-kit

echo "=== System Check ==="
php -v
node -v
npm -v

echo "=== WordPress Check ==="
cd cms
wp core version
wp plugin list --status=active
wp theme list --status=active

echo "=== Test Suite ==="
cd ..
./tests/run-tests.sh

echo "=== Build Check ==="
ls -lh cms/wp-content/themes/custom-theme/assets/dist/
```

### Check Error Logs
```bash
# WordPress debug log
tail -50 cms/wp-content/debug.log

# PHP error log
tail -50 /usr/local/var/log/php-error.log

# Valet log (macOS)
tail -50 ~/.valet/Log/nginx-error.log
```

### Enable Debug Mode
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

---

## Plugin Issues

### Critical: Plugin Won't Activate

**Error:** "Plugin could not be activated"

**Solutions:**

1. **Check PHP Version:**
```bash
php -v  # Must be 8.0+

# If wrong version, switch (Valet)
valet use php@8.3
```

2. **Check for Syntax Errors:**
```bash
php -l cms/wp-content/plugins/media-lab-agency-core/media-lab-agency-core.php
```

3. **Check Dependencies:**
```bash
# For Project Plugin
wp plugin is-active media-lab-agency-core
wp plugin is-active advanced-custom-fields-pro
```

4. **Activate with Debug:**
```bash
wp plugin activate media-lab-agency-core --debug
```

### Critical: Agency Core Not Found

**Error:** "Critical: Agency Core plugin not found"

**Cause:** MU-plugin loader still looking for old location

**Solution:**
```bash
# Check MU-plugin loader
cat cms/wp-content/mu-plugins/000-mu-plugin-loader.php | grep agency-core

# Should be commented out or removed
# If not, edit file and remove agency-core loading block
```

### Plugin Dependency Warning

**Error:** "Missing required plugin: Media Lab Agency Core"

**Solution:**
```bash
# Ensure Core Plugin active first
wp plugin activate media-lab-agency-core

# Then activate dependent plugin
wp plugin activate media-lab-project-starter
```

### Plugin Features Not Working

**Problem:** Plugin active but features not working

**Solutions:**

1. **Clear Cache:**
```bash
wp cache flush
wp transient delete --all
```

2. **Verify Hooks Registered:**
```bash
# Check shortcodes
wp eval 'global $shortcode_tags; print_r(array_keys($shortcode_tags));'

# Check AJAX actions
wp eval 'echo has_action("wp_ajax_agency_search") ? "✅" : "❌";'
```

3. **Check for Conflicts:**
```bash
# Deactivate all except Media Lab plugins
wp plugin deactivate --all --exclude=media-lab-agency-core,media-lab-project-starter

# Test if issue persists
# Reactivate one by one to find conflict
```

---

## Theme Issues

### White Screen (WSOD)

**Problem:** Site shows blank white page

**Solutions:**

1. **Check PHP Errors:**
```bash
# Enable error display temporarily
# wp-config.php
define('WP_DEBUG_DISPLAY', true);

# Reload page, check for errors
```

2. **Switch to Default Theme:**
```bash
wp theme activate twentytwentythree

# If site loads, issue is in custom-theme
```

3. **Check functions.php:**
```bash
php -l cms/wp-content/themes/custom-theme/functions.php
```

### Assets Not Loading

**Problem:** CSS/JS files not loading

**Solutions:**

1. **Check Dist Folder:**
```bash
ls -lh cms/wp-content/themes/custom-theme/assets/dist/

# Should have:
# - main-[hash].css
# - main-[hash].js
# - manifest.json
```

2. **Rebuild Assets:**
```bash
npm run build

# Check if files created
ls -lh cms/wp-content/themes/custom-theme/assets/dist/
```

3. **Check Enqueue:**
```php
// functions.php should load:
require_once get_template_directory() . '/inc/enqueue.php';

// Check enqueue.php has correct paths
```

4. **Clear Browser Cache:**
```
Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)
```

### Theme PHP Compatibility Warning

**Problem:** "This theme doesn't work with your PHP version"

**Solution:**
```bash
# Check theme header
head -15 cms/wp-content/themes/custom-theme/style.css

# Should say: Requires PHP: 8.0
# Not: Requires PHP: 8.3

# If wrong, edit style.css:
# Change: Requires PHP: 8.3
# To: Requires PHP: 8.0
```

### Template Not Found

**Problem:** "Template not found" error

**Solution:**
```bash
# Check template exists
ls cms/wp-content/themes/custom-theme/

# WordPress template hierarchy:
# page.php → singular.php → index.php
# Ensure index.php exists as fallback
```

---

## Build System Issues

### npm install Fails

**Problem:** Package installation errors

**Solutions:**

1. **Clear npm Cache:**
```bash
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

2. **Check Node Version:**
```bash
node -v  # Should be 16+

# If wrong, install correct version:
nvm install 18
nvm use 18
```

3. **Try with Legacy Peer Deps:**
```bash
npm install --legacy-peer-deps
```

### npm run dev Fails

**Problem:** Development server won't start

**Solutions:**

1. **Port Already in Use:**
```bash
# Kill process on port 5173
lsof -ti:5173 | xargs kill -9

# Try again
npm run dev
```

2. **Check Vite Config:**
```bash
cat vite.config.js

# Verify paths are correct
# Verify server.origin matches your local URL
```

3. **Run with Debug:**
```bash
DEBUG=vite:* npm run dev
```

### npm run build Fails

**Problem:** Production build errors

**Solutions:**

1. **Check Disk Space:**
```bash
df -h

# Need at least 1GB free
```

2. **Check for Syntax Errors:**
```bash
# Check JavaScript
npm run lint

# Or manually:
node -c cms/wp-content/themes/custom-theme/assets/js/main.js
```

3. **Build with Verbose:**
```bash
npm run build -- --debug
```

### HMR Not Working

**Problem:** Changes not reflecting in browser

**Solutions:**

1. **Check dev Server Running:**
```bash
# Should see: "Local: http://localhost:5173"
```

2. **Check Browser Console:**
```
Open DevTools (F12)
Look for: "[vite] connected"
```

3. **Restart dev Server:**
```bash
# Stop: Ctrl+C
npm run dev
```

---

## AJAX Issues

### AJAX Returns 400 Error

**Problem:** AJAX requests fail with 400 Bad Request

**Solutions:**

1. **Check Action Name:**
```javascript
// Must match PHP action name exactly
formData.append('action', 'agency_search');  // Not 'search' or 'Agency_Search'
```

2. **Check Nonce:**
```php
// Add nonce to JavaScript
wp_localize_script('main-script', 'ajaxData', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('ajax-nonce')
]);

// Verify in AJAX handler
check_ajax_referer('ajax-nonce', 'nonce');
```

3. **Check Data Format:**
```javascript
// Use FormData, not JSON
const formData = new FormData();
formData.append('action', 'agency_search');
formData.append('s', searchTerm);
```

### AJAX Returns 0

**Problem:** AJAX returns just "0"

**Cause:** No matching action handler found

**Solutions:**

1. **Verify Action Registered:**
```bash
wp eval '
$actions = ["agency_search", "agency_load_more", "ajax_filter_posts"];
foreach ($actions as $action) {
    echo $action . ": ";
    echo (has_action("wp_ajax_" . $action) ? "✅" : "❌") . "\n";
}
'
```

2. **Check Plugin Active:**
```bash
wp plugin is-active media-lab-agency-core
```

3. **Check Action Name Match:**
```php
// PHP side
add_action('wp_ajax_my_action', 'my_function');
add_action('wp_ajax_nopriv_my_action', 'my_function');

// JS side
formData.append('action', 'my_action');  // Must match exactly
```

### AJAX Returns 500 Error

**Problem:** Server error during AJAX request

**Solutions:**

1. **Check PHP Error Log:**
```bash
tail -50 cms/wp-content/debug.log
```

2. **Add Error Logging:**
```php
// In AJAX handler
function my_ajax_handler() {
    error_log('AJAX Handler Called');
    error_log('POST Data: ' . print_r($_POST, true));
    
    try {
        // Your code
    } catch (Exception $e) {
        error_log('AJAX Error: ' . $e->getMessage());
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
```

3. **Check Memory Limit:**
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '256M');
```

### AJAX No Response

**Problem:** AJAX request hangs, no response

**Solutions:**

1. **Check Network Tab:**
```
DevTools → Network → XHR
Check if request is sent
Check response status
```

2. **Check for PHP Timeout:**
```php
// Increase timeout temporarily
set_time_limit(60);
```

3. **Check Query:**
```php
// In AJAX handler
$query = new WP_Query($args);
error_log('Found Posts: ' . $query->found_posts);
```

---

## ACF Issues

### ACF Fields Not Showing

**Problem:** Custom fields don't appear in admin

**Solutions:**

1. **Verify ACF PRO Active:**
```bash
wp plugin is-active advanced-custom-fields-pro
```

2. **Check Field Group Settings:**
- Go to: Custom Fields → Field Groups
- Click field group
- Check "Location" rules
- Ensure matches your post type/page

3. **Sync Fields from JSON:**
```bash
# Go to: Custom Fields → Tools
# Click "Sync available"
# Sync all field groups
```

4. **Check JSON Files Exist:**
```bash
ls -la cms/wp-content/plugins/media-lab-project-starter/acf-json/

# Should have 11 .json files
```

### ACF Fields Not Loading (Frontend)

**Problem:** `get_field()` returns empty

**Solutions:**

1. **Check Field Exists:**
```php
// Debug
$fields = get_fields();
print_r($fields);  // Shows all fields

// Or
$value = get_field('field_name');
var_dump($value);  // Shows specific field
```

2. **Check Field Name:**
```php
// Use field key instead of name
$value = get_field('field_5f8a3b2c1d4e5');  // Field key
// Not: get_field('my_field');  // Field name (might not work)
```

3. **Check Post ID:**
```php
// On single post
$value = get_field('field_name');  // Current post

// Specific post
$value = get_field('field_name', $post_id);

// Options page
$value = get_field('field_name', 'option');
```

### ACF JSON Not Saving

**Problem:** Field changes don't save to JSON

**Solutions:**

1. **Check Folder Permissions:**
```bash
chmod 755 cms/wp-content/plugins/media-lab-project-starter/acf-json/
```

2. **Check Save Path:**
```bash
wp eval '
$save_path = apply_filters("acf/settings/save_json", "");
echo "Save Path: " . $save_path . "\n";
echo "Writable: " . (is_writable($save_path) ? "✅" : "❌") . "\n";
'
```

3. **Manual Export:**
```
Custom Fields → Tools → Export Field Groups
Select groups → Export as JSON
Upload to acf-json/ folder
```

---

## Performance Issues

### Slow Page Load

**Problem:** Pages load slowly

**Solutions:**

1. **Check Query Performance:**
```php
// Add to functions.php temporarily
add_action('wp_footer', function() {
    echo '<!-- ' . get_num_queries() . ' queries in ' . timer_stop() . ' seconds -->';
});
```

2. **Optimize Images:**
```bash
# Check image sizes
wp media regenerate --yes

# Use WebP format
# Use lazy loading
```

3. **Enable Caching:**
```php
// Transient caching example
$cache_key = 'my_query_' . md5($args);
$results = get_transient($cache_key);

if (false === $results) {
    $results = new WP_Query($args);
    set_transient($cache_key, $results, HOUR_IN_SECONDS);
}
```

4. **Check Plugins:**
```bash
# Deactivate unused plugins
wp plugin list
wp plugin deactivate unused-plugin
```

### High Memory Usage

**Problem:** "Allowed memory size exhausted"

**Solutions:**

1. **Increase Memory Limit:**
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

2. **Find Memory Hog:**
```php
// Add before suspected code
echo 'Memory Before: ' . memory_get_usage() . "\n";

// Your code here

echo 'Memory After: ' . memory_get_usage() . "\n";
```

3. **Optimize Queries:**
```php
// Don't load all posts
$args['posts_per_page'] = 10;  // Not: -1

// Don't load unnecessary data
$args['fields'] = 'ids';  // Just IDs, not full objects
```

---

## Error Messages

### "Parse error: syntax error..."

**Cause:** PHP syntax error

**Solution:**
```bash
# Find the error
php -l cms/wp-content/themes/custom-theme/functions.php

# Common causes:
# - Missing semicolon
# - Unclosed bracket/parenthesis
# - Missing quote
```

### "Fatal error: Cannot redeclare..."

**Cause:** Function declared twice

**Solution:**
```php
// Wrap in function_exists check
if (!function_exists('my_function')) {
    function my_function() {
        // Function code
    }
}
```

### "Call to undefined function..."

**Cause:** Function doesn't exist or file not loaded

**Solution:**
```bash
# Check if plugin active
wp plugin is-active plugin-name

# Check if file included
# Add to functions.php:
require_once 'path/to/file.php';
```

### "Headers already sent..."

**Cause:** Output before headers

**Solution:**
```php
// Check for whitespace before <?php
// Check for echo/print before headers
// Common culprits:
# - BOM in UTF-8 files
# - Whitespace at end of file
# - Debug output
```

---

## Emergency Recovery

### Site Completely Broken

**Quick Recovery:**

1. **Disable All Plugins (Database):**
```bash
wp plugin deactivate --all
```

2. **Switch to Default Theme:**
```bash
wp theme activate twentytwentythree
```

3. **Check if Site Loads:**
- If yes: Plugin or theme issue
- If no: WordPress core issue

4. **Reactivate One by One:**
```bash
wp plugin activate media-lab-agency-core
# Test site

wp plugin activate media-lab-project-starter
# Test site

# Continue...
```

### Database Restore
```bash
# Export current (broken) database
wp db export backup-$(date +%Y%m%d).sql

# Import previous backup
wp db import backup-previous.sql
```

### File Restore from Git
```bash
# Discard all changes
git checkout -- .

# Restore specific file
git checkout HEAD -- path/to/file.php

# Restore from specific commit
git checkout abc123 -- path/to/file.php
```

### Reset to Clean State
```bash
# WARNING: This resets everything!

# 1. Backup first!
cp -r cms cms-backup-$(date +%Y%m%d)

# 2. Git reset
git reset --hard HEAD

# 3. Rebuild
npm install
npm run build

# 4. WordPress
wp cache flush
wp rewrite flush
```


## Neue Probleme (v1.5.1)

### Live Reload / HMR funktioniert nicht

**Symptom:** `npm run dev` läuft, aber Änderungen an SCSS oder PHP werden im Browser nicht automatisch übernommen.

**Ursache:** WordPress lädt Assets immer aus `dist/` und baut nie eine WebSocket-Verbindung
zu Vite auf — es sei denn, `@vite/client` wird explizit enqueued. Das passiert nur wenn
die `hot`-Datei vorhanden ist.

**Checkliste:**

1. `hot`-Datei vorhanden?
```bash
ls cms/wp-content/themes/custom-theme/assets/hot
```

2. Falls nicht: `scripts/vite-dev.mjs` enthält falschen Theme-Pfad.
```bash
cat scripts/vite-dev.mjs | grep HOT_FILE
# Muss auf das korrekte Theme zeigen, z.B. stadtwirt-theme statt custom-theme
```

3. Port 3000 bereits belegt?
```bash
lsof -ti :3000 | xargs kill -9
npm run dev:stop && npm run dev
```

4. Im Browser DevTools → Network prüfen: Wird `@vite/client` von `localhost:3000` geladen?
   - Ja → WebSocket-Verbindung besteht, HMR aktiv
   - Nein → `hot`-Datei fehlt oder `enqueue.php` nicht aktuell

---

### Hot-Datei bleibt nach Absturz liegen

**Symptom:** Nach einem Terminal-Absturz zeigt das Frontend kein CSS mehr (WordPress
versucht Assets von `localhost:3000` zu laden, der aber nicht läuft).

**Fix:**
```bash
npm run dev:stop
# Oder manuell:
rm cms/wp-content/themes/custom-theme/assets/hot
```

Danach normale Browser-Seite laden → CSS aus `dist/` wird wieder verwendet.

---

### CSS fehlt nach `npm run build`

**Symptom:** Nach dem Build und Browser-Reload ist das gesamte Styling verschwunden.

**Ursache A:** Falscher Theme-Ordner in `vite.config.js`.
```bash
grep "themeDir" vite.config.js
# Muss auf das korrekte Theme zeigen
```

**Ursache B:** `hot`-Datei noch vorhanden → WordPress lädt vom (gestoppten) Dev Server.
```bash
npm run dev:stop
```

**Ursache C:** `assets/dist/css/style.css` existiert nicht.
```bash
ls cms/wp-content/themes/custom-theme/assets/dist/css/
# Falls leer: npm run build erneut ausführen
```

---

### Beim Portieren auf neues Projekt (z.B. stadtwirt-theme)

Folgende Stellen müssen angepasst werden wenn das Theme einen anderen Ordnernamen hat:

| Datei | Zu ändernde Stelle |
|---|---|
| `vite.config.js` | `themeDir` Pfad + `base` URL + `liveReload()` Pfad |
| `scripts/vite-dev.mjs` | `HOT_FILE` Pfad |
| `inc/enqueue.php` | Alle Funktionsnamen + Handles + `window.customTheme`-Name |
| `assets/src/js/**/*.js` | `window.customTheme` → `window.{projektname}Theme` |

**Schnellcheck nach dem Portieren:**
```bash
# Alle alten Theme-Referenzen finden
grep -r "custom-theme" vite.config.js scripts/vite-dev.mjs

# Alle alten window.customTheme Referenzen finden
grep -r "window.customTheme" cms/wp-content/themes/{dein-theme}/assets/src/
```



---

## Getting Help

### Before Asking for Help

1. **Check this guide**
2. **Check error logs**
3. **Try basic diagnostics**
4. **Search existing issues**

### When Asking for Help

**Include:**
- Error message (full text)
- Steps to reproduce
- What you tried
- Environment (PHP version, WordPress version)
- Relevant code snippets
- Error logs

**Format:**
```
Problem: Brief description

Steps to reproduce:
1. Step one
2. Step two

Expected: What should happen
Actual: What actually happens

Environment:
- PHP: 8.3
- WordPress: 6.4
- Theme: custom-theme 1.0
- Plugins: [list active plugins]

Error log:
[paste relevant errors]

Tried:
- Solution 1
- Solution 2
```

---

---

## Neue Probleme (v1.5.0)

### AJAX gibt 403 zurück obwohl Nonce korrekt konfiguriert

**Symptom:** Alle AJAX-Requests scheitern mit HTTP 403, Console zeigt `admin-ajax.php: 403`

**Ursache:** `wp_add_inline_script('before')` wird von WordPress bei `type="module"` Scripts silent ignoriert → `window.customTheme` ist `undefined` → Nonce-Feld wird als Leerstring gesendet → `check_ajax_referer()` schlägt fehl.

**Fix:** In `inc/enqueue.php` – Config direkt via `wp_head` ausgeben:
```php
function customtheme_output_js_config(): void {
    echo '<script id="custom-theme-config">window.customTheme = '
        . wp_json_encode([...])
        . ';</script>';
}
add_action('wp_head', 'customtheme_output_js_config', 1);
```

---

### Swiper-Slider zeigt kein Styling (CSS fehlt)

**Symptom:** Slider funktioniert, aber ohne Pfeile/Pagination-Styling

**Ursache:** Swiper CSS nicht importiert nachdem CDN-Version entfernt wurde.

**Fix:** In `main.js`:
```js
import 'swiper/css/bundle';
```

---

### Maintenance Mode gesetzt, aber Website noch erreichbar

**Symptom:** `MEDIALAB_MAINTENANCE_MODE = true` in wp-config.php gesetzt, trotzdem keine Wartungsseite

**Ursache:** Eingeloggte Administratoren werden immer durchgelassen (by design). Im Inkognito-Fenster testen.

---

## Neue Probleme (v1.2.0+)

### SCSS: "Undefined variable" beim Build

**Problem:** `Error: Undefined variable. $color-primary`

**Ursache:** `@use '../abstracts' as *;` fehlt am Anfang des Partials.

**Lösung:**
```scss
// Am Anfang jedes SCSS-Partials einfügen:
@use '../abstracts' as *;

// Danach sind alle Tokens und Mixins verfügbar
.mein-element {
  color: $color-primary; // ✅
}
```

---

### SCSS: "Can't find stylesheet to import"

**Problem:** `Error: Can't find stylesheet to import. @use 'abstracts/variables'`

**Ursache:** `style.scss` referenziert `abstracts/variables` direkt statt über den Index.

**Lösung:** In `style.scss` nur `@use 'abstracts'` (nicht `abstracts/variables`):
```scss
// ✅ Korrekt
@use 'abstracts' as *;

// ❌ Falsch
@use 'abstracts/variables' as *;
@use 'abstracts/mixins' as *;
```

---

### JS: AJAX-Anfrage liefert 429

**Problem:** AJAX-Request gibt HTTP 429 zurück: `"Too many requests. Please try again later."`

**Ursache:** Rate-Limiting greift – zu viele Anfragen pro Minute von derselben IP.

**Limits:**
- AJAX Search: max. 20 Req / 60 Sekunden
- AJAX Filter + Load More: max. 30 Req / 60 Sekunden

**Für Entwicklung:** Transients kurz löschen:
```bash
cd cms && wp transient delete --all && cd ..
```

**In Production:** Limit bei Bedarf anpassen in `inc/helpers.php`:
```php
medialab_check_rate_limit('ajax_search', 30, 60); // 30 statt 20
```

---

### JS: Dynamic Import schlägt fehl

**Problem:** Komponente lädt nicht, keine Fehlermeldung

**Ursache:** DOM-Selektor in `main.js` stimmt nicht mit HTML-Klasse überein.

**Lösung:** Selektor in `main.js` prüfen:
```javascript
// main.js
if (has('.meine-klasse')) { // ← muss mit HTML übereinstimmen
  const { default: MeineKomponente } = await import('./components/meine-komponente');
}
```

```html
<!-- Template -->
<div class="meine-klasse">...</div>
```

---

### Build: "Could not resolve entry module"

**Problem:** `Could not resolve entry module "assets/src/js/main.js"`

**Ursache:** `vite.config.js` im falschen Verzeichnis oder falsche Pfade.

**Lösung:** Die Build-Config liegt im **Projekt-Root** (neben `package.json`), nicht im Theme-Ordner. Pfade müssen `path.resolve(__dirname, 'cms/wp-content/...')` verwenden.

---

### SMTP: Test-Mail funktioniert nicht

**Problem:** Test-Mail Button im Backend zeigt Fehler

**Checkliste:**
1. `MEDIALAB_SMTP_HOST` in `wp-config.php` gesetzt?
2. Port und Verschlüsselung korrekt? (587/tls oder 465/ssl)
3. SMTP-Konto aktiv? (Firewall blockiert Port 587?)
4. Aktiviert: `define('MEDIALAB_SMTP_ENABLED', true);`

**Debug:**
```php
// In wp-config.php temporär:
define('MEDIALAB_SMTP_DEBUG', true); // Zeigt SMTP-Protokoll im Activity Log
```

---

---

## Neue Probleme (v1.7.0–v1.13.0)

### Navigation: 4. Ebene erscheint nicht

**Symptom:** Flyout-Menü zeigt nur 3 Ebenen, obwohl Menüstruktur tiefer ist.

**Ursache:** `wp_nav_menu()` in `header.php` hatte `depth: 3` gesetzt.

**Fix:** In `header.php` → `depth: 4` (oder `0` für unbegrenzt).

---

### Cookie Consent: Banner erscheint nach Reload erneut

**Symptom:** Banner wird nach Einstellung immer wieder angezeigt.

**Ursache A:** `localStorage` ist im Browser deaktiviert oder blockiert (z.B. privater Modus in Safari).

**Ursache B:** `cc_version` in ACF wurde erhöht → erzwingt erneute Zustimmung (by design).

**Fix für Entwicklung:** In Browser-DevTools → Application → LocalStorage → `ml_cookie_consent` löschen.

---

### Cookie Consent: Code-Snippets werden nicht injiziert

**Symptom:** GA4 / FB Pixel läuft nicht obwohl Nutzer zugestimmt hat.

**Checkliste:**
1. Snippets unter Agency Core → Cookie Consent → Code-Snippets eingetragen?
2. `<script>` Tags korrekt? (kein `<noscript>` in Head-Feld)
3. Browser-Konsole: Fehler beim Parsen des `<script>`-Blocks?
4. Einwilligung tatsächlich für die Kategorie erteilt? → `localStorage.ml_cookie_consent` prüfen.

---

### Hero Image: Fehlt auf einer Seite obwohl gesetzt

**Symptom:** ACF-Feld `hero_image` gesetzt, aber kein Hero sichtbar.

**Ursache A:** `hero_enabled` in den globalen Einstellungen ist deaktiviert.

**Ursache B:** Seite hat kein gültiges `featured_image` und `hero_image` ist leer → `media_lab_get_hero_image()` gibt `null` zurück.

**Debug:**
```php
// In page.php temporär:
$hero = media_lab_get_hero_image();
var_dump($hero);
```

---

### Breadcrumbs: „Fatal error: Cannot redeclare"

**Symptom:** `Fatal error: Cannot redeclare media_lab_breadcrumb_get_term_parents()`

**Ursache:** `breadcrumbs.php` wurde doppelt geladen (z.B. via MU-Plugin und Plugin gleichzeitig).

**Fix:** Alle privaten Hilfsfunktionen in `breadcrumbs.php` sind mit `function_exists()`-Guard geschützt (seit v1.1.1). Falls Fehler weiterhin auftritt: prüfen ob eine alte Version der Datei noch aktiv ist.

---

### ACF: PHP 8.1 Deprecated-Warnings bei `strpos(null)`

**Symptom:** `Deprecated: strpos(): Passing null to parameter #1 ($haystack)` in `wp-includes/functions.php`

**Ursache:** ACF-Felder vom Typ `message` ohne `'label'`-Key liefern intern `null`.

**Fix:** Allen `message`-Feldern `'label' => ''` hinzufügen (seit v1.11.1 behoben).

**Wenn Warnings nach Update noch erscheinen:** PHP OPcache leeren:
```bash
valet restart
# oder:
brew services restart php
```

---

### hCaptcha: Widget erscheint nicht im CF7-Formular

**Symptom:** hCaptcha aktiviert, Site-Key eingetragen, aber kein Widget sichtbar.

**Checkliste:**
1. `hcaptcha_cf7` in ACF auf `true` gesetzt?
2. `hcaptcha_site_key` **und** `hcaptcha_secret_key` ausgefüllt?
3. `hcaptcha-api` Script im HTML-Source? (`Cmd+U` im Browser → nach `js.hcaptcha.com` suchen)
4. Browser-Konsole: CORS- oder Netzwerkfehler?
5. Seite über `https://` erreichbar? (hCaptcha erfordert HTTPS)

**Lokal testen:** hCaptcha bietet Test-Keys für Entwicklung:
- Site Key: `10000000-ffff-ffff-ffff-000000000001`
- Secret Key: `0x0000000000000000000000000000000000000000`

---

### hCaptcha: Formular wird trotz falschem CAPTCHA abgeschickt

**Symptom:** CF7-Formular sendet durch, obwohl CAPTCHA nicht ausgefüllt.

**Ursache:** `wpcf7_validate` Hook greift nur bei aktivem CF7. Prüfen ob CF7 in der aktuellen Version den Hook unterstützt.

**Debug:**
```php
// In hcaptcha.php temporär:
add_filter('wpcf7_validate', function($result, $tags) {
    error_log('hCaptcha Token: ' . ($_POST['h-captcha-response'] ?? 'LEER'));
    // ...
}, 10, 2);
```

---

### Back-to-Top: Button nicht sichtbar obwohl aktiviert

**Symptom:** `btt_enabled = 1` in ACF, aber kein Button im Frontend.

**Ursache A:** Build nicht aktualisiert nach PHP-Änderung → `npm run build` ausführen.

**Ursache B:** `footer.php` der alten Version im Einsatz → `footer.php` ersetzen.

**Ursache C:** ACF nicht aktiv → `function_exists('get_field')` gibt `false` zurück → Fallback: Button wird nicht gerendert.

**Debug:** Im Browser-Devtools HTML inspizieren → Button-Element vorhanden?

---

### Scroll Progress Bar: Linie springt oder bleibt bei 0%

**Symptom:** Progress-Bar zeigt 0% oder bewegt sich nicht.

**Ursache A:** Seite ist nicht `single.php` → Bedingung in `header.php`: `is_single()`.

**Ursache B:** `scroll_progress_enabled` in ACF nicht gesetzt.

**Ursache C:** Page-Höhe entspricht Viewport-Höhe (kein Scroll möglich) → `scrollHeight - clientHeight = 0`.

---

### Build: „Top-level await not available"

**Symptom:** `npm run build` scheitert mit `Top-level await is not available in the configured target environment`

**Ursache:** `await import()` innerhalb von `async`-Funktion wird von esbuild für das eingestellte Browser-Target nicht unterstützt.

**Fix:** Komponente als statischen `import` am Dateianfang von `main.js` eintragen:
```js
// ❌ Verursacht Fehler:
const { default: Foo } = await import('./components/foo');

// ✅ Korrekt (statischer Import):
import Foo from './components/foo';
// Dann bedingt initialisieren:
if (has('.foo')) safeInit('Foo', () => new Foo());
```

---

### Build: „BrowserTracing is not exported"

**Symptom:** `"BrowserTracing" is not exported by "@sentry/browser"`

**Ursache:** Sentry v8 hat die API geändert.

**Fix:** In `utils/sentry.js`:
```js
// ❌ Alt:
new Sentry.BrowserTracing({ ... })

// ✅ Neu:
Sentry.browserTracingIntegration({ ... })
```


## Next Steps

- **Custom Post Types:** [CPT Documentation](08_CUSTOM-POST-TYPES.md)
- **ACF Fields:** [ACF Documentation](09_ACF-FIELDS.md)
- **Development:** [Development Guide](06_DEVELOPMENT.md)

---

**Need more help?** Contact: markus.tritremmel@media-lab.at

**Most issues solved!** 🔧  
**Next:** [Custom Post Types](08_CUSTOM-POST-TYPES.md) →

---

### Maintenance Mode: Besucher landen nicht auf Wartungsseite

**Symptom:** Maintenance aktiviert, aber Website zeigt sich normal.

**Ursache A:** Nutzer ist als Administrator eingeloggt → Admin-Bypass ist aktiv.
**Fix:** Ausloggen oder anderen Browser / Inkognito-Modus verwenden.

**Ursache B:** Page-Caching (z.B. WP Rocket) cached die Seite vor dem Maintenance-Hook.
**Fix:** Cache leeren und Cache für nicht-eingeloggte Nutzer kurzzeitig deaktivieren.

---

### Media Replace: Upload schlägt fehl (413 / max upload size)

**Symptom:** Beim Ersetzen einer Mediendatei erscheint ein 413-Fehler oder die Datei ist zu groß.

**Fix:** PHP-Upload-Limit in `.htaccess` oder `php.ini` erhöhen:

```apache
# .htaccess
php_value upload_max_filesize 64M
php_value post_max_size 64M
```

---

### 404-Seite zeigt keine Quick-Links

**Symptom:** Der Abschnitt „Vielleicht suchen Sie:" erscheint nicht.

**Ursache:** Kein Menü unter dem Location **Primary Menu** zugewiesen.
**Fix:** Design → Menüs → Menü erstellen → Position „Primary Menu" zuweisen.

