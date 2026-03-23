<?php
/**
 * Media Lab Starter Kit – Browser-Setup
 *
 * Für Hosting-Umgebungen ohne SSH-Zugang.
 *
 * SICHERHEIT:
 *   1. Datei in den WordPress-Root hochladen (neben wp-config.php)
 *   2. Setup durchführen
 *   3. Datei SOFORT danach löschen!
 *
 * ZUGANG schützen: Entweder .htaccess-Schutz oder Secret-Token (siehe unten).
 */

// ── Zugriffsschutz ────────────────────────────────────────────────────────────
// Token setzen und in der URL mitgeben: setup.php?token=DEIN_TOKEN
define( 'SETUP_TOKEN', '' ); // Leer = kein Schutz (nicht empfohlen!)

if ( SETUP_TOKEN !== '' && ( $_GET['token'] ?? '' ) !== SETUP_TOKEN ) {
    http_response_code( 403 );
    exit( 'Zugriff verweigert. Token fehlt oder ungültig.' );
}

// ── Bereits abgeschlossen? ────────────────────────────────────────────────────
define( 'SETUP_LOCK_FILE', __DIR__ . '/.setup-complete' );
$setup_done = file_exists( SETUP_LOCK_FILE );

// ── WordPress laden ───────────────────────────────────────────────────────────
$wp_load = __DIR__ . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    // Einen Ordner höher versuchen (falls script in /bin/ liegt)
    $wp_load = dirname( __DIR__ ) . '/wp-load.php';
}
$wp_loaded = false;
if ( file_exists( $wp_load ) ) {
    require_once $wp_load;
    $wp_loaded = true;
}

// ── Hilfsfunktionen ───────────────────────────────────────────────────────────

function ml_wpdb(): wpdb|null {
    global $wpdb;
    return $wpdb ?? null;
}

function ml_run_sql( string $sql ): bool {
    $db = ml_wpdb();
    if ( ! $db ) return false;
    $db->query( $sql );
    return empty( $db->last_error );
}

function ml_option( string $key, string $value ): bool {
    if ( ! function_exists( 'update_option' ) ) return false;
    return update_option( $key, $value );
}

function ml_slugify( string $input ): string {
    return preg_replace( '/[^a-z0-9_]/', '_', strtolower( $input ) );
}

function ml_random_password( int $length = 20 ): string {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $pass  = '';
    for ( $i = 0; $i < $length; $i++ ) {
        $pass .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
    }
    return $pass;
}

// ── Setup-Engine ──────────────────────────────────────────────────────────────

class MediaLabSetup {

    private array  $cfg;
    private array  $log    = [];
    private array  $errors = [];
    private bool   $dry_run;

    public function __construct( array $cfg ) {
        $this->cfg     = $cfg;
        $this->dry_run = (bool) ( $cfg['dry_run'] ?? false );
    }

    public function run(): array {
        $this->step_domain();
        $this->step_db_prefix();
        $this->step_search_replace();
        $this->step_admin_user();
        $this->step_theme();
        $this->step_plugin_prefix();
        $this->step_finish();

        return [ 'log' => $this->log, 'errors' => $this->errors ];
    }

    // ── Domain ────────────────────────────────────────────────────────────────

    private function step_domain(): void {
        $url = rtrim( $this->cfg['protocol'] . '://' . $this->cfg['domain_new'], '/' );

        if ( $this->dry_run ) {
            $this->info( "DRY: siteurl → $url" );
            $this->info( "DRY: home → $url" );
            return;
        }

        ml_option( 'siteurl', $url ) ? $this->ok( "siteurl → $url" ) : $this->err( 'siteurl konnte nicht gesetzt werden' );
        ml_option( 'home',    $url ) ? $this->ok( "home → $url" )    : $this->err( 'home konnte nicht gesetzt werden' );
    }

    // ── DB-Präfix ─────────────────────────────────────────────────────────────

    private function step_db_prefix(): void {
        global $wpdb;
        $old = $wpdb->prefix;
        $new = $this->cfg['db_prefix'];

        if ( $old === $new ) {
            $this->info( "DB-Präfix bereits '$new' – übersprungen" );
            return;
        }

        // Tabellen ermitteln
        $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$old}%'" );
        if ( empty( $tables ) ) {
            $this->warn( "Keine Tabellen mit Präfix '$old' gefunden" );
            return;
        }

        if ( $this->dry_run ) {
            $this->info( 'DRY: ' . count( $tables ) . " Tabellen umbenennen: {$old}* → {$new}*" );
            $this->info( "DRY: wp-config.php table_prefix = '$new'" );
            return;
        }

        $renamed = 0;
        foreach ( $tables as $table ) {
            $new_table = $new . substr( $table, strlen( $old ) );
            ml_run_sql( "RENAME TABLE `{$table}` TO `{$new_table}`" ) && $renamed++;
        }
        $this->ok( "$renamed Tabellen umbenannt: {$old}* → {$new}*" );

        // usermeta + options Keys
        ml_run_sql( "UPDATE `{$new}usermeta` SET meta_key = REPLACE(meta_key, '{$old}', '{$new}') WHERE meta_key LIKE '{$old}%'" );
        ml_run_sql( "UPDATE `{$new}options` SET option_name = REPLACE(option_name, '{$old}', '{$new}') WHERE option_name LIKE '{$old}%'" );
        $this->ok( 'usermeta + options Keys aktualisiert' );

        // wp-config.php
        $this->update_wp_config_prefix( $old, $new );
    }

    private function update_wp_config_prefix( string $old, string $new ): void {
        $config = ABSPATH . 'wp-config.php';
        if ( ! is_writable( $config ) ) {
            $this->warn( 'wp-config.php nicht beschreibbar – Präfix manuell ändern!' );
            return;
        }
        $content = file_get_contents( $config );
        $content = preg_replace(
            "/\\\$table_prefix\s*=\s*'{$old}'/",
            "\$table_prefix = '{$new}'",
            $content
        );
        file_put_contents( $config, $content );
        $this->ok( "wp-config.php: table_prefix = '$new'" );
    }

    // ── Search/Replace ────────────────────────────────────────────────────────

    private function step_search_replace(): void {
        $old = trim( $this->cfg['domain_old'] ?? '' );
        if ( empty( $old ) ) {
            $this->info( 'Search/Replace übersprungen (keine alte Domain)' );
            return;
        }

        global $wpdb;
        $new     = $this->cfg['domain_new'];
        $new_url = $this->cfg['protocol'] . '://' . $new;
        $db_prefix = $this->cfg['db_prefix'];

        $pairs = [
            "https://{$old}" => $new_url,
            "http://{$old}"  => $new_url,
            $old             => $new,
        ];

        if ( $this->dry_run ) {
            foreach ( $pairs as $f => $t ) {
                $this->info( "DRY: Search/Replace '$f' → '$t'" );
            }
            return;
        }

        // Serialisierungssichere Ersetzung in allen Tabellen
        $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$db_prefix}%'" );
        $total  = 0;

        foreach ( $tables as $table ) {
            $cols = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A );
            foreach ( $cols as $col ) {
                $col_name = $col['Field'];
                $col_type = strtolower( $col['Type'] );

                // Nur Text-Spalten
                if ( ! preg_match( '/char|text|blob/', $col_type ) ) continue;

                foreach ( $pairs as $from => $to ) {
                    // Direkter str_replace für nicht-serialisierte Werte
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE `{$table}` SET `{$col_name}` = REPLACE(`{$col_name}`, %s, %s) WHERE `{$col_name}` LIKE %s",
                            $from, $to, '%' . $wpdb->esc_like( $from ) . '%'
                        )
                    );
                    $total += $wpdb->rows_affected;
                }
            }
        }

        $this->ok( "Search/Replace: $total Felder aktualisiert" );
        $this->warn( 'Serialisierte Daten (ACF, Widget-Optionen): Falls Probleme auftreten → WP-CLI search-replace mit --precise verwenden.' );
    }

    // ── Admin-User ────────────────────────────────────────────────────────────

    private function step_admin_user(): void {
        if ( empty( $this->cfg['admin_create'] ) ) {
            $this->info( 'Admin-User übersprungen' );
            return;
        }

        $username = $this->cfg['admin_user'];
        $email    = $this->cfg['admin_email'];
        $password = $this->cfg['admin_pass'] ?: ml_random_password();
        $display  = $this->cfg['admin_display'] ?: $username;

        if ( $this->dry_run ) {
            $this->info( "DRY: Admin-User '$username' ($email) anlegen" );
            return;
        }

        if ( ! function_exists( 'wp_create_user' ) ) {
            $this->err( 'WordPress-Funktionen nicht verfügbar' );
            return;
        }

        $existing = get_user_by( 'login', $username );
        if ( $existing ) {
            wp_set_password( $password, $existing->ID );
            $this->ok( "Admin-User '$username' existiert – Passwort aktualisiert" );
        } else {
            $user_id = wp_create_user( $username, $password, $email );
            if ( is_wp_error( $user_id ) ) {
                $this->err( 'User-Erstellung: ' . $user_id->get_error_message() );
                return;
            }
            wp_update_user( [ 'ID' => $user_id, 'role' => 'administrator', 'display_name' => $display ] );
            $this->ok( "Admin-User angelegt: $username" );
        }

        // Passwort in Session speichern für Anzeige
        $this->cfg['_generated_pass'] = $password;
        $this->cfg['_admin_user']     = $username;
        $this->log[] = [ 'type' => 'credentials', 'user' => $username, 'pass' => $password ];
    }

    // ── Theme ─────────────────────────────────────────────────────────────────

    private function step_theme(): void {
        $source      = $this->cfg['theme_source'];
        $target      = $this->cfg['theme_target'];
        $text_domain = $this->cfg['text_domain'];
        $client_name = $this->cfg['client_name'];

        $themes_dir  = WP_CONTENT_DIR . '/themes/';
        $source_dir  = $themes_dir . $source;
        $target_dir  = $themes_dir . $target;

        if ( ! is_dir( $source_dir ) ) {
            $this->err( "Quell-Theme nicht gefunden: $source_dir" );
            return;
        }

        if ( $source !== $target ) {
            if ( $this->dry_run ) {
                $this->info( "DRY: Theme kopieren $source → $target" );
            } elseif ( ! is_dir( $target_dir ) ) {
                $this->copy_dir( $source_dir, $target_dir );
                $this->ok( "Theme kopiert: $source → $target" );
            } else {
                $this->warn( "Ziel-Theme-Ordner existiert bereits: $target" );
            }
        }

        $work_dir = $this->dry_run ? $source_dir : $target_dir;

        // style.css
        $style_css = $work_dir . '/style.css';
        if ( ! $this->dry_run && file_exists( $style_css ) ) {
            $content = file_get_contents( $style_css );

            // Alte Text Domain ermitteln
            preg_match( '/Text Domain:\s*(.+)/', $content, $m );
            $old_domain = trim( $m[1] ?? 'customtheme' );

            $content = preg_replace( '/Theme Name:.+/', "Theme Name: $client_name", $content );
            $content = preg_replace( '/Text Domain:.+/', "Text Domain: $text_domain", $content );
            file_put_contents( $style_css, $content );
            $this->ok( "style.css: Theme Name + Text Domain aktualisiert" );

            // PHP-Dateien
            if ( $old_domain !== $text_domain ) {
                $this->replace_in_dir( $work_dir, "'$old_domain'", "'$text_domain'", '*.php' );
                $this->ok( "Text Domain in PHP-Dateien: $old_domain → $text_domain" );
            }
        } elseif ( $this->dry_run ) {
            $this->info( "DRY: style.css Theme Name → $client_name, Text Domain → $text_domain" );
        }

        // Theme aktivieren
        if ( $this->dry_run ) {
            $this->info( "DRY: Theme aktivieren: $target" );
        } elseif ( function_exists( 'switch_theme' ) ) {
            switch_theme( $target );
            $this->ok( "Theme aktiviert: $target" );
        }
    }

    // ── Plugin-Prefix ─────────────────────────────────────────────────────────

    private function step_plugin_prefix(): void {
        if ( empty( $this->cfg['rename_prefix'] ) ) {
            $this->info( 'Plugin-Prefix übersprungen' );
            return;
        }

        $new     = $this->cfg['new_prefix'];
        $old     = 'medialab_';
        $plugins = [ 'media-lab-agency-core', 'media-lab-seo' ];

        foreach ( $plugins as $plugin ) {
            $dir = WP_CONTENT_DIR . '/plugins/' . $plugin;
            if ( ! is_dir( $dir ) ) continue;

            if ( $this->dry_run ) {
                $count = $this->count_occurrences( $dir, $old );
                $this->info( "DRY: $plugin – $count Vorkommen '$old'" );
                continue;
            }

            $this->replace_in_dir( $dir, $old, $new, '*.php' );
            $this->replace_in_dir( $dir, $old, $new, '*.js' );
            $this->ok( "Plugin $plugin: '$old' → '$new'" );
        }

        // DB
        if ( ! $this->dry_run ) {
            global $wpdb;
            $prefix = $this->cfg['db_prefix'];
            $wpdb->query( "UPDATE `{$prefix}options` SET option_name = REPLACE(option_name, 'medialab_', '{$new}') WHERE option_name LIKE 'medialab_%'" );
            $this->ok( "DB option_names: medialab_ → $new" );
        } else {
            $this->info( "DRY: DB option_names medialab_ → $new" );
        }
    }

    // ── Abschluss ─────────────────────────────────────────────────────────────

    private function step_finish(): void {
        if ( $this->dry_run ) return;

        // Rewrite Rules
        if ( function_exists( 'flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
            $this->ok( 'Rewrite Rules neu generiert' );
        }

        // WP_DEBUG ausschalten
        $config = ABSPATH . 'wp-config.php';
        if ( is_writable( $config ) ) {
            $content = file_get_contents( $config );
            $content = preg_replace(
                "/define\s*\(\s*'WP_DEBUG'\s*,\s*true\s*\)/",
                "define('WP_DEBUG', false)",
                $content
            );
            file_put_contents( $config, $content );
            $this->ok( 'WP_DEBUG deaktiviert' );
        }

        // Lock-File erstellen
        file_put_contents( SETUP_LOCK_FILE, date( 'Y-m-d H:i:s' ) );
        $this->ok( 'Setup abgeschlossen – Lock-File erstellt' );
    }

    // ── Datei-Hilfsfunktionen ─────────────────────────────────────────────────

    private function copy_dir( string $src, string $dst ): void {
        mkdir( $dst, 0755, true );
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $src, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ( $iter as $item ) {
            $dest = $dst . '/' . $iter->getSubPathname();
            $item->isDir() ? mkdir( $dest, 0755, true ) : copy( $item->getPathname(), $dest );
        }
    }

    private function replace_in_dir( string $dir, string $from, string $to, string $pattern ): void {
        $files = new RegexIterator(
            new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ),
            '/' . str_replace( '*', '.*', preg_quote( $pattern, '/' ) ) . '$/'
        );
        foreach ( $files as $file ) {
            $content = file_get_contents( $file->getPathname() );
            if ( str_contains( $content, $from ) ) {
                file_put_contents( $file->getPathname(), str_replace( $from, $to, $content ) );
            }
        }
    }

    private function count_occurrences( string $dir, string $needle ): int {
        $count = 0;
        $files = new RegexIterator(
            new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ) ),
            '/\.(php|js)$/'
        );
        foreach ( $files as $file ) {
            $count += substr_count( file_get_contents( $file->getPathname() ), $needle );
        }
        return $count;
    }

    // ── Log-Hilfsfunktionen ───────────────────────────────────────────────────

    private function ok( string $msg ):   void { $this->log[] = [ 'type' => 'ok',   'msg' => $msg ]; }
    private function info( string $msg ): void { $this->log[] = [ 'type' => 'info', 'msg' => $msg ]; }
    private function warn( string $msg ): void { $this->log[] = [ 'type' => 'warn', 'msg' => $msg ]; }
    private function err( string $msg ):  void {
        $this->log[]    = [ 'type' => 'error', 'msg' => $msg ];
        $this->errors[] = $msg;
    }
}

// ── Form-Verarbeitung ─────────────────────────────────────────────────────────

$result     = null;
$form_error = null;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' && ! $setup_done ) {

    $cfg = [
        'client_name'  => trim( $_POST['client_name']  ?? '' ),
        'project_name' => trim( $_POST['project_name'] ?? '' ),
        'domain_new'   => trim( $_POST['domain_new']   ?? '' ),
        'domain_old'   => trim( $_POST['domain_old']   ?? '' ),
        'protocol'     => in_array( $_POST['protocol'] ?? '', [ 'http', 'https' ] ) ? $_POST['protocol'] : 'https',
        'db_prefix'    => trim( $_POST['db_prefix']    ?? 'wp_' ),
        'admin_create' => ! empty( $_POST['admin_create'] ),
        'admin_user'   => trim( $_POST['admin_user']   ?? '' ),
        'admin_email'  => trim( $_POST['admin_email']  ?? '' ),
        'admin_pass'   => trim( $_POST['admin_pass']   ?? '' ),
        'admin_display'=> trim( $_POST['admin_display']?? '' ),
        'theme_source' => trim( $_POST['theme_source'] ?? 'custom-theme' ),
        'theme_target' => trim( $_POST['theme_target'] ?? '' ),
        'text_domain'  => trim( $_POST['text_domain']  ?? '' ),
        'rename_prefix'=> ! empty( $_POST['rename_prefix'] ),
        'new_prefix'   => trim( $_POST['new_prefix']   ?? '' ),
        'dry_run'      => ! empty( $_POST['dry_run'] ),
    ];

    // Defaults ableiten
    if ( empty( $cfg['theme_target'] ) ) $cfg['theme_target'] = $cfg['project_name'];
    if ( empty( $cfg['text_domain'] ) )  $cfg['text_domain']  = $cfg['theme_target'];
    if ( empty( $cfg['new_prefix'] ) && $cfg['rename_prefix'] ) {
        $cfg['new_prefix'] = ml_slugify( $cfg['project_name'] ) . '_';
    }

    // Validierung
    if ( empty( $cfg['domain_new'] ) ) {
        $form_error = 'Domain ist Pflichtfeld.';
    } elseif ( ! preg_match( '/^[a-zA-Z0-9._-]+$/', $cfg['db_prefix'] ) || ! str_ends_with( $cfg['db_prefix'], '_' ) ) {
        $form_error = 'DB-Präfix ungültig. Nur a-z, 0-9, _ erlaubt. Muss mit _ enden.';
    } elseif ( ! $wp_loaded ) {
        $form_error = 'WordPress konnte nicht geladen werden. Bitte Pfad prüfen.';
    } else {
        $setup  = new MediaLabSetup( $cfg );
        $result = $setup->run();
    }
}

// ── HTML-Output ───────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Media Lab – Projekt-Setup</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: #0f172a;
    color: #e2e8f0;
    min-height: 100vh;
    padding: 2rem;
  }

  .container { max-width: 760px; margin: 0 auto; }

  header {
    text-align: center;
    margin-bottom: 2.5rem;
  }
  header h1 { font-size: 1.75rem; font-weight: 700; color: #f8fafc; margin-bottom: .4rem; }
  header p  { color: #94a3b8; font-size: .9rem; }

  .card {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 1.5rem;
  }

  h2 {
    font-size: 1rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 1.25rem;
    padding-bottom: .75rem;
    border-bottom: 1px solid #334155;
  }

  .field { margin-bottom: 1.1rem; }
  label  { display: block; font-size: .85rem; font-weight: 500; color: #cbd5e1; margin-bottom: .4rem; }

  input[type=text],
  input[type=url],
  input[type=email],
  input[type=password],
  select {
    width: 100%;
    padding: .55rem .85rem;
    background: #0f172a;
    border: 1px solid #475569;
    border-radius: 6px;
    color: #f1f5f9;
    font-size: .9rem;
    transition: border-color .15s;
  }
  input:focus, select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,.15);
  }

  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
  .hint { font-size: .78rem; color: #64748b; margin-top: .3rem; }

  .toggle { display: flex; align-items: center; gap: .6rem; cursor: pointer; }
  .toggle input[type=checkbox] { width: 16px; height: 16px; accent-color: #3b82f6; }
  .toggle span { font-size: .88rem; color: #cbd5e1; }

  .collapsible { display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #334155; }
  .collapsible.open { display: block; }

  button[type=submit] {
    width: 100%;
    padding: .85rem;
    background: #3b82f6;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    margin-top: .5rem;
  }
  button[type=submit]:hover { background: #2563eb; }
  button[type=submit].dry   { background: #f59e0b; }
  button[type=submit].dry:hover { background: #d97706; }

  .alert { padding: 1rem 1.25rem; border-radius: 8px; font-size: .9rem; margin-bottom: 1rem; }
  .alert-error   { background: #450a0a; border: 1px solid #dc2626; color: #fca5a5; }
  .alert-warning { background: #431407; border: 1px solid #f59e0b; color: #fcd34d; }
  .alert-done    { background: #052e16; border: 1px solid #16a34a; color: #86efac; }

  .log { list-style: none; }
  .log li { display: flex; gap: .6rem; padding: .45rem 0; font-size: .87rem; border-bottom: 1px solid #1e293b; }
  .log li:last-child { border: none; }
  .log .icon { width: 20px; flex-shrink: 0; text-align: center; }
  .log .ok    { color: #34d399; }
  .log .info  { color: #60a5fa; }
  .log .warn  { color: #fbbf24; }
  .log .error { color: #f87171; }
  .log .credentials { color: #f0abfc; }

  .creds {
    background: #0f172a;
    border: 1px solid #7c3aed;
    border-radius: 8px;
    padding: 1.25rem;
    margin-top: 1rem;
  }
  .creds h3 { font-size: .9rem; color: #c4b5fd; margin-bottom: .75rem; }
  .creds table { width: 100%; font-size: .88rem; }
  .creds td { padding: .3rem .5rem; }
  .creds td:first-child { color: #94a3b8; width: 110px; }
  .creds td:last-child  { color: #f1f5f9; font-family: monospace; font-weight: 600; }

  .warning-box {
    background: #7c2d12;
    border: 1px solid #ea580c;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1.5rem;
    font-size: .87rem;
    color: #fed7aa;
  }
  .warning-box strong { color: #fb923c; }
</style>
</head>
<body>
<div class="container">

  <header>
    <h1>🚀 Media Lab Starter Kit</h1>
    <p>Projekt-Setup<?php echo $wp_loaded ? ' · WordPress geladen ✓' : ' · <span style="color:#f87171">WordPress nicht gefunden!</span>'; ?></p>
  </header>

  <?php if ( $setup_done ) : ?>

    <div class="card">
      <div class="alert alert-done">
        ✅ Setup wurde bereits abgeschlossen (<?php echo htmlspecialchars( file_get_contents( SETUP_LOCK_FILE ) ); ?>).<br>
        <strong>Bitte diese Datei (setup.php) vom Server löschen!</strong>
      </div>
    </div>

  <?php elseif ( $result ) : ?>

    <div class="card">
      <h2>📋 Setup-Protokoll<?php echo ( $_POST['dry_run'] ?? false ) ? ' (Trockenlauf)' : ''; ?></h2>

      <?php
      $credentials = null;
      foreach ( $result['log'] as $entry ) :
        if ( $entry['type'] === 'credentials' ) { $credentials = $entry; continue; }
      ?>
      <ul class="log">
        <li class="<?php echo $entry['type']; ?>">
          <span class="icon"><?php echo match( $entry['type'] ) {
            'ok'    => '✓',
            'info'  => 'ℹ',
            'warn'  => '⚠',
            'error' => '✗',
            default => '·',
          }; ?></span>
          <span><?php echo htmlspecialchars( $entry['msg'] ); ?></span>
        </li>
      </ul>
      <?php endforeach; ?>

      <?php if ( $credentials ) : ?>
      <div class="creds">
        <h3>🔑 Admin-Zugangsdaten</h3>
        <table>
          <tr><td>URL</td><td><?php echo htmlspecialchars( get_option( 'siteurl' ) . '/wp-admin/' ); ?></td></tr>
          <tr><td>Benutzername</td><td><?php echo htmlspecialchars( $credentials['user'] ); ?></td></tr>
          <tr><td>Passwort</td><td><?php echo htmlspecialchars( $credentials['pass'] ); ?></td></tr>
        </table>
      </div>
      <?php endif; ?>

      <?php if ( ! empty( $result['errors'] ) ) : ?>
      <div class="alert alert-error" style="margin-top:1rem">
        ⚠ <?php echo count( $result['errors'] ); ?> Fehler aufgetreten. Bitte Log prüfen.
      </div>
      <?php else : ?>
      <div class="alert alert-done" style="margin-top:1rem">
        ✅ Setup erfolgreich abgeschlossen.
      </div>
      <?php endif; ?>
    </div>

    <div class="warning-box">
      <strong>⚠ Wichtig:</strong> Diese Datei (setup.php) jetzt sofort vom Server löschen!<br>
      Sie enthält sensible Konfigurationsmöglichkeiten und darf nicht öffentlich zugänglich bleiben.
    </div>

  <?php else : ?>

  <?php if ( $form_error ) : ?>
    <div class="alert alert-error">⚠ <?php echo htmlspecialchars( $form_error ); ?></div>
  <?php endif; ?>

  <form method="post">

    <div class="card">
      <h2>Projekt-Identität</h2>
      <div class="row">
        <div class="field">
          <label>Kundenname *</label>
          <input type="text" name="client_name" placeholder="Muster GmbH" required value="<?php echo htmlspecialchars( $_POST['client_name'] ?? '' ); ?>">
        </div>
        <div class="field">
          <label>Projektname (Slug) *</label>
          <input type="text" name="project_name" placeholder="muster-gmbh" pattern="[a-z0-9-]+" required value="<?php echo htmlspecialchars( $_POST['project_name'] ?? '' ); ?>">
          <p class="hint">Nur a-z, 0-9, Bindestriche</p>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Domain</h2>
      <div class="row">
        <div class="field">
          <label>Neue Domain *</label>
          <input type="text" name="domain_new" placeholder="www.muster-gmbh.at" required value="<?php echo htmlspecialchars( $_POST['domain_new'] ?? '' ); ?>">
          <p class="hint">Ohne https://</p>
        </div>
        <div class="field">
          <label>Protokoll</label>
          <select name="protocol">
            <option value="https" <?php selected( ($_POST['protocol'] ?? 'https'), 'https' ); ?>>https://</option>
            <option value="http"  <?php selected( ($_POST['protocol'] ?? 'https'), 'http'  ); ?>>http://</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Alte Domain ersetzen (Search/Replace)</label>
        <input type="text" name="domain_old" placeholder="old-domain.at (leer = Neuinstallation)" value="<?php echo htmlspecialchars( $_POST['domain_old'] ?? '' ); ?>">
        <p class="hint">Bei Umzug oder Clone – alle URLs in der DB werden ersetzt</p>
      </div>
    </div>

    <div class="card">
      <h2>WordPress</h2>
      <div class="field">
        <label>Datenbank-Präfix *</label>
        <input type="text" name="db_prefix" placeholder="mg_" required pattern="[a-zA-Z_][a-zA-Z0-9_]*_" value="<?php echo htmlspecialchars( $_POST['db_prefix'] ?? 'wp_' ); ?>">
        <p class="hint">Muss mit _ enden. Beispiel: mg_ für Muster GmbH</p>
      </div>
    </div>

    <div class="card">
      <h2>Admin-User</h2>
      <div class="field">
        <label class="toggle">
          <input type="checkbox" name="admin_create" value="1" <?php checked( ! empty( $_POST['admin_create'] ) ); ?> onchange="document.getElementById('admin-fields').classList.toggle('open', this.checked)">
          <span>Neuen Admin-User anlegen</span>
        </label>
      </div>
      <div class="collapsible <?php echo ! empty( $_POST['admin_create'] ) ? 'open' : ''; ?>" id="admin-fields">
        <div class="row">
          <div class="field">
            <label>Benutzername</label>
            <input type="text" name="admin_user" placeholder="admin-muster" value="<?php echo htmlspecialchars( $_POST['admin_user'] ?? '' ); ?>">
          </div>
          <div class="field">
            <label>E-Mail</label>
            <input type="email" name="admin_email" placeholder="admin@muster-gmbh.at" value="<?php echo htmlspecialchars( $_POST['admin_email'] ?? '' ); ?>">
          </div>
        </div>
        <div class="row">
          <div class="field">
            <label>Passwort</label>
            <input type="password" name="admin_pass" placeholder="Leer = zufällig generiert">
          </div>
          <div class="field">
            <label>Anzeigename</label>
            <input type="text" name="admin_display" placeholder="Muster GmbH Admin" value="<?php echo htmlspecialchars( $_POST['admin_display'] ?? '' ); ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Theme</h2>
      <div class="row">
        <div class="field">
          <label>Quell-Theme</label>
          <input type="text" name="theme_source" placeholder="custom-theme" value="<?php echo htmlspecialchars( $_POST['theme_source'] ?? 'custom-theme' ); ?>">
        </div>
        <div class="field">
          <label>Ziel-Theme-Ordner</label>
          <input type="text" name="theme_target" placeholder="Leer = Projektname" value="<?php echo htmlspecialchars( $_POST['theme_target'] ?? '' ); ?>">
        </div>
      </div>
      <div class="field">
        <label>Text Domain</label>
        <input type="text" name="text_domain" placeholder="Leer = Ziel-Theme-Ordner" value="<?php echo htmlspecialchars( $_POST['text_domain'] ?? '' ); ?>">
      </div>
    </div>

    <div class="card">
      <h2>Plugin-Prefix</h2>
      <div class="field">
        <label class="toggle">
          <input type="checkbox" name="rename_prefix" value="1" <?php checked( ! empty( $_POST['rename_prefix'] ) ); ?> onchange="document.getElementById('prefix-fields').classList.toggle('open', this.checked)">
          <span>Plugin-Prefix <code style="color:#94a3b8">medialab_</code> umbenennen</span>
        </label>
      </div>
      <div class="collapsible <?php echo ! empty( $_POST['rename_prefix'] ) ? 'open' : ''; ?>" id="prefix-fields">
        <div class="field">
          <label>Neuer Prefix</label>
          <input type="text" name="new_prefix" placeholder="mustergmbh_ (Leer = aus Projektname)" pattern="[a-z0-9]+_" value="<?php echo htmlspecialchars( $_POST['new_prefix'] ?? '' ); ?>">
          <p class="hint">Nur a-z, 0-9, muss mit _ enden</p>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Optionen</h2>
      <div class="field">
        <label class="toggle">
          <input type="checkbox" name="dry_run" value="1" <?php checked( ! empty( $_POST['dry_run'] ) ); ?>>
          <span>Trockenlauf – zeigt was gemacht würde ohne Änderungen</span>
        </label>
      </div>
    </div>

    <button type="submit" name="dry_run" value="" class="submit-btn">🚀 Setup starten</button>
    <button type="submit" name="dry_run" value="1" class="dry" style="margin-top:.75rem">🔍 Trockenlauf</button>

  </form>

  <div class="warning-box" style="margin-top:1.5rem">
    <strong>⚠ Sicherheitshinweis:</strong> Diese Datei nach dem Setup sofort vom Server löschen.<br>
    Empfehlung: Datei nur temporär hochladen, Setup durchführen, sofort löschen.
  </div>

  <?php endif; ?>

</div>

<?php if ( ! $setup_done && ! $result ) : ?>
<script>
// Checkboxen initialisieren
document.querySelectorAll('input[type=checkbox]').forEach(cb => {
    const target = document.getElementById(cb.dataset.toggle);
    if (target) {
        cb.addEventListener('change', () => target.classList.toggle('open', cb.checked));
    }
});
</script>
<?php endif; ?>

</body>
</html>
