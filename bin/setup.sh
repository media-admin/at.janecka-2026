#!/usr/bin/env bash
# =============================================================================
# Media Lab Starter Kit – One-Click-Setup
# =============================================================================
#
# Einrichtung eines neuen Kundenprojekts aus dem Starter Kit.
#
# USAGE:
#   bash bin/setup.sh                         # Interaktiver Modus
#   bash bin/setup.sh --config bin/setup.yml  # Config-File Modus
#   bash bin/setup.sh --dry-run               # Trockenlauf (keine Änderungen)
#   bash bin/setup.sh --help                  # Hilfe
#
# VORAUSSETZUNGEN:
#   - WP-CLI (wp) im PATH
#   - PHP >= 8.0
#   - MySQL/MariaDB erreichbar
#   - WordPress bereits installiert (cms/ vorhanden)
# =============================================================================

set -euo pipefail

# ── Farben ────────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

# ── Globals ───────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE=""
DRY_RUN=false
VERBOSE=false
LOG_FILE="$PROJECT_ROOT/bin/.setup.log"
BACKUP_DIR="$PROJECT_ROOT/bin/.backups"

# Config-Werte (werden interaktiv oder per YML befüllt)
CFG_PROJECT_NAME=""
CFG_CLIENT_NAME=""
CFG_DOMAIN_NEW=""
CFG_DOMAIN_OLD=""
CFG_PROTOCOL="https"
CFG_WP_PATH="cms"
CFG_DB_PREFIX=""
CFG_LOCALE="de_AT"
CFG_ADMIN_CREATE=true
CFG_ADMIN_USER=""
CFG_ADMIN_EMAIL=""
CFG_ADMIN_PASS=""
CFG_ADMIN_DISPLAY=""
CFG_THEME_SOURCE="custom-theme"
CFG_THEME_TARGET=""
CFG_TEXT_DOMAIN=""
CFG_RENAME_PREFIX=false
CFG_NEW_PREFIX=""
CFG_BACKUP=true

# ── Hilfsfunktionen ───────────────────────────────────────────────────────────

log()     { echo -e "${RESET}$*" | tee -a "$LOG_FILE"; }
info()    { echo -e "${BLUE}ℹ  $*${RESET}" | tee -a "$LOG_FILE"; }
success() { echo -e "${GREEN}✓  $*${RESET}" | tee -a "$LOG_FILE"; }
warn()    { echo -e "${YELLOW}⚠  $*${RESET}" | tee -a "$LOG_FILE"; }
error()   { echo -e "${RED}✗  $*${RESET}" | tee -a "$LOG_FILE"; }
step()    { echo -e "\n${BOLD}${CYAN}━━ $* ${RESET}" | tee -a "$LOG_FILE"; }
dry()     { echo -e "${YELLOW}[DRY] $*${RESET}" | tee -a "$LOG_FILE"; }

ask() {
    local prompt="$1" default="${2:-}" var_name="$3"
    local display_default=""
    [[ -n "$default" ]] && display_default=" ${CYAN}[${default}]${RESET}"
    echo -ne "${BOLD}${prompt}${display_default}: ${RESET}"
    read -r input
    [[ -z "$input" && -n "$default" ]] && input="$default"
    printf -v "$var_name" '%s' "$input"
}

ask_yn() {
    local prompt="$1" default="${2:-y}" var_name="$3"
    local display="(j/n)"
    [[ "$default" == "y" ]] && display="${BOLD}(J${RESET}/n)" || display="(j/${BOLD}N${RESET})"
    echo -ne "${BOLD}${prompt} ${display}: ${RESET}"
    read -r input
    input="${input:-$default}"
    if [[ "$input" =~ ^[jJyY]$ ]]; then
        printf -v "$var_name" '%s' "true"
    else
        printf -v "$var_name" '%s' "false"
    fi
}

wp_cmd() {
    local wp_path="$PROJECT_ROOT/$CFG_WP_PATH"
    wp --path="$wp_path" --allow-root "$@"
}

# ── YML-Parser (ohne yq-Abhängigkeit) ────────────────────────────────────────

yml_get() {
    local file="$1" key="$2"
    grep -E "^\s*${key}:" "$file" 2>/dev/null | sed 's/.*: *//; s/"//g; s/#.*//' | tr -d ' '
}

yml_get_nested() {
    local file="$1" section="$2" key="$3"
    # Extrahiert key aus einem benannten YAML-Block
    awk "/^${section}:/{found=1} found && /^\s+${key}:/{print; exit}" "$file" \
        | sed 's/.*: *//; s/"//g; s/#.*//' | tr -d ' '
}

load_config() {
    local file="$1"
    [[ ! -f "$file" ]] && { error "Config-File nicht gefunden: $file"; exit 1; }

    info "Lade Konfiguration aus: $file"

    CFG_PROJECT_NAME=$(yml_get_nested "$file" "project" "name")
    CFG_CLIENT_NAME=$(yml_get_nested  "$file" "project" "client_name")
    CFG_DOMAIN_NEW=$(yml_get_nested   "$file" "domain"  "new")
    CFG_DOMAIN_OLD=$(yml_get_nested   "$file" "domain"  "old")
    CFG_PROTOCOL=$(yml_get_nested     "$file" "domain"  "protocol")
    CFG_WP_PATH=$(yml_get_nested      "$file" "wordpress" "path")
    CFG_DB_PREFIX=$(yml_get_nested    "$file" "wordpress" "db_prefix")
    CFG_LOCALE=$(yml_get_nested       "$file" "wordpress" "locale")

    local admin_create
    admin_create=$(yml_get_nested "$file" "admin" "create")
    [[ "$admin_create" == "true" ]] && CFG_ADMIN_CREATE=true || CFG_ADMIN_CREATE=false

    CFG_ADMIN_USER=$(yml_get_nested    "$file" "admin" "username")
    CFG_ADMIN_EMAIL=$(yml_get_nested   "$file" "admin" "email")
    CFG_ADMIN_PASS=$(yml_get_nested    "$file" "admin" "password")
    CFG_ADMIN_DISPLAY=$(yml_get_nested "$file" "admin" "display_name")

    CFG_THEME_SOURCE=$(yml_get_nested "$file" "theme" "source")
    CFG_THEME_TARGET=$(yml_get_nested "$file" "theme" "target")
    CFG_TEXT_DOMAIN=$(yml_get_nested  "$file" "theme" "text_domain")

    local rename_prefix
    rename_prefix=$(yml_get_nested "$file" "plugins" "rename_prefix")
    [[ "$rename_prefix" == "true" ]] && CFG_RENAME_PREFIX=true || CFG_RENAME_PREFIX=false
    CFG_NEW_PREFIX=$(yml_get_nested "$file" "plugins" "new_prefix")

    local backup dry_run verbose
    backup=$(yml_get_nested "$file" "options" "backup")
    dry_run=$(yml_get_nested "$file" "options" "dry_run")
    verbose=$(yml_get_nested "$file" "options" "verbose")
    [[ "$backup"   == "false" ]] && CFG_BACKUP=false
    [[ "$dry_run"  == "true"  ]] && DRY_RUN=true
    [[ "$verbose"  == "true"  ]] && VERBOSE=true
}

# ── Interaktiver Modus ────────────────────────────────────────────────────────

interactive_mode() {
    echo -e "\n${BOLD}${CYAN}╔═══════════════════════════════════════════════╗${RESET}"
    echo -e "${BOLD}${CYAN}║   Media Lab Starter Kit – Projekt-Setup       ║${RESET}"
    echo -e "${BOLD}${CYAN}╚═══════════════════════════════════════════════╝${RESET}\n"

    echo -e "${YELLOW}Beantworte die folgenden Fragen. Leere Eingabe = Standardwert in [].${RESET}\n"

    step "Projekt-Identität"
    ask "Projektname (slug, z.B. 'muster-gmbh')" "" CFG_PROJECT_NAME
    ask "Kundenname (Anzeigename)" "" CFG_CLIENT_NAME

    step "Domain"
    ask "Neue Domain (ohne https://)" "" CFG_DOMAIN_NEW
    ask "Protokoll" "https" CFG_PROTOCOL
    ask "Alte Domain ersetzen (leer = Neuinstallation)" "" CFG_DOMAIN_OLD

    step "WordPress"
    ask "WP-Pfad (relativ zum Projekt-Root)" "cms" CFG_WP_PATH
    ask "Datenbank-Präfix (z.B. 'mg_')" "wp_" CFG_DB_PREFIX
    ask "Sprache" "de_AT" CFG_LOCALE

    step "Admin-User"
    ask_yn "Neuen Admin-User anlegen?" "y" CFG_ADMIN_CREATE
    if [[ "$CFG_ADMIN_CREATE" == "true" ]]; then
        ask "Admin-Benutzername" "admin" CFG_ADMIN_USER
        ask "Admin-E-Mail" "" CFG_ADMIN_EMAIL
        ask "Admin-Passwort (leer = zufällig)" "" CFG_ADMIN_PASS
        ask "Anzeigename" "$CFG_CLIENT_NAME Admin" CFG_ADMIN_DISPLAY
    fi

    step "Theme"
    ask "Quell-Theme-Ordner" "custom-theme" CFG_THEME_SOURCE
    ask "Ziel-Theme-Ordner (leer = Projektname)" "" CFG_THEME_TARGET
    ask "Text Domain (leer = Ziel-Ordner)" "" CFG_TEXT_DOMAIN

    step "Plugin-Prefix"
    ask_yn "Plugin-Prefix 'medialab_' umbenennen?" "n" CFG_RENAME_PREFIX
    if [[ "$CFG_RENAME_PREFIX" == "true" ]]; then
        ask "Neuer Prefix (z.B. 'mustergmbh_')" "" CFG_NEW_PREFIX
    fi

    step "Optionen"
    ask_yn "Backup vor Änderungen erstellen?" "y" CFG_BACKUP
}

# ── Defaults ableiten ─────────────────────────────────────────────────────────

derive_defaults() {
    # Theme-Target aus Projektname
    [[ -z "$CFG_THEME_TARGET" ]] && CFG_THEME_TARGET="$CFG_PROJECT_NAME"

    # Text Domain aus Theme-Target
    [[ -z "$CFG_TEXT_DOMAIN" ]] && CFG_TEXT_DOMAIN="$CFG_THEME_TARGET"

    # Plugin-Prefix aus Projektname (Bindestriche entfernen)
    if [[ "$CFG_RENAME_PREFIX" == "true" && -z "$CFG_NEW_PREFIX" ]]; then
        CFG_NEW_PREFIX="${CFG_PROJECT_NAME//-/}_"
    fi

    # Passwort generieren
    [[ -z "$CFG_ADMIN_PASS" ]] && CFG_ADMIN_PASS=$(openssl rand -base64 16 | tr -d '=/+' | head -c 20)
}

# ── Validierung ───────────────────────────────────────────────────────────────

validate() {
    step "Validierung"

    local errors=0

    [[ -z "$CFG_PROJECT_NAME" ]] && { error "Projektname fehlt"; ((errors++)); }
    [[ ! "$CFG_PROJECT_NAME" =~ ^[a-z0-9-]+$ ]] && {
        error "Projektname darf nur a-z, 0-9 und Bindestriche enthalten: $CFG_PROJECT_NAME"
        ((errors++))
    }

    [[ -z "$CFG_DOMAIN_NEW" ]] && { error "Domain fehlt"; ((errors++)); }

    [[ -z "$CFG_DB_PREFIX" ]] && { error "DB-Präfix fehlt"; ((errors++)); }
    [[ ! "$CFG_DB_PREFIX" =~ ^[a-zA-Z_][a-zA-Z0-9_]*_$ ]] && {
        error "DB-Präfix muss mit Underscore enden und nur a-z/0-9/_ enthalten: $CFG_DB_PREFIX"
        ((errors++))
    }

    local wp_path="$PROJECT_ROOT/$CFG_WP_PATH"
    [[ ! -d "$wp_path" ]] && { error "WP-Pfad nicht gefunden: $wp_path"; ((errors++)); }

    # WP-CLI prüfen
    if ! command -v wp &>/dev/null; then
        error "WP-CLI nicht gefunden. Installation: https://wp-cli.org/#installing"
        ((errors++))
    fi

    # Theme-Source prüfen
    local theme_source="$wp_path/wp-content/themes/$CFG_THEME_SOURCE"
    [[ ! -d "$theme_source" ]] && {
        error "Quell-Theme nicht gefunden: $theme_source"
        ((errors++))
    }

    [[ $errors -gt 0 ]] && {
        error "$errors Validierungsfehler – Setup abgebrochen."
        exit 1
    }

    success "Validierung erfolgreich"
}

# ── Zusammenfassung ───────────────────────────────────────────────────────────

show_summary() {
    echo -e "\n${BOLD}${CYAN}━━ Zusammenfassung ${RESET}"
    echo -e "  ${BOLD}Projekt:${RESET}        $CFG_PROJECT_NAME ($CFG_CLIENT_NAME)"
    echo -e "  ${BOLD}Domain:${RESET}         ${CFG_PROTOCOL}://${CFG_DOMAIN_NEW}"
    [[ -n "$CFG_DOMAIN_OLD" ]] && \
    echo -e "  ${BOLD}Search/Replace:${RESET} $CFG_DOMAIN_OLD → $CFG_DOMAIN_NEW"
    echo -e "  ${BOLD}WP-Pfad:${RESET}        $CFG_WP_PATH"
    echo -e "  ${BOLD}DB-Präfix:${RESET}      $CFG_DB_PREFIX"
    echo -e "  ${BOLD}Theme:${RESET}          $CFG_THEME_SOURCE → $CFG_THEME_TARGET"
    echo -e "  ${BOLD}Text Domain:${RESET}    $CFG_TEXT_DOMAIN"
    [[ "$CFG_ADMIN_CREATE" == "true" ]] && \
    echo -e "  ${BOLD}Admin-User:${RESET}     $CFG_ADMIN_USER ($CFG_ADMIN_EMAIL)"
    [[ "$CFG_RENAME_PREFIX" == "true" ]] && \
    echo -e "  ${BOLD}Plugin-Prefix:${RESET}  medialab_ → $CFG_NEW_PREFIX"
    [[ "$DRY_RUN" == "true" ]] && \
    echo -e "\n  ${YELLOW}${BOLD}⚠ TROCKENLAUF – keine Änderungen werden gespeichert${RESET}"
    echo ""
}

confirm() {
    if [[ "$DRY_RUN" == "false" ]]; then
        ask_yn "Setup jetzt ausführen?" "j" _confirm
        [[ "$_confirm" == "false" ]] && { warn "Abgebrochen."; exit 0; }
    fi
}

# ── STEP 1: Backup ────────────────────────────────────────────────────────────

step_backup() {
    [[ "$CFG_BACKUP" == "false" ]] && { info "Backup übersprungen (deaktiviert)"; return; }

    step "Backup erstellen"
    mkdir -p "$BACKUP_DIR"
    local ts; ts=$(date +%Y%m%d_%H%M%S)
    local backup_path="$BACKUP_DIR/${CFG_PROJECT_NAME}_${ts}"
    mkdir -p "$backup_path"

    local wp_path="$PROJECT_ROOT/$CFG_WP_PATH"

    if [[ "$DRY_RUN" == "true" ]]; then
        dry "wp db export $backup_path/db.sql"
        dry "cp $wp_path/wp-config.php $backup_path/"
    else
        info "Datenbank-Export..."
        wp_cmd db export "$backup_path/db.sql" && success "DB gesichert: $backup_path/db.sql"

        cp "$wp_path/wp-config.php" "$backup_path/wp-config.php" 2>/dev/null \
            && success "wp-config.php gesichert"
    fi

    success "Backup erstellt: $backup_path"
}

# ── STEP 2: Domain setzen ─────────────────────────────────────────────────────

step_domain() {
    step "Domain konfigurieren"

    local new_url="${CFG_PROTOCOL}://${CFG_DOMAIN_NEW}"

    if [[ "$DRY_RUN" == "true" ]]; then
        dry "wp option update siteurl '$new_url'"
        dry "wp option update home '$new_url'"
    else
        wp_cmd option update siteurl "$new_url" && success "siteurl → $new_url"
        wp_cmd option update home    "$new_url" && success "home → $new_url"
    fi
}

# ── STEP 3: DB-Präfix ändern ──────────────────────────────────────────────────

step_db_prefix() {
    step "Datenbank-Präfix ändern"

    local wp_path="$PROJECT_ROOT/$CFG_WP_PATH"
    local wp_config="$wp_path/wp-config.php"

    # Alten Präfix ermitteln
    local old_prefix
    old_prefix=$(grep "table_prefix" "$wp_config" | sed "s/.*'\(.*\)'.*/\1/")

    if [[ "$old_prefix" == "$CFG_DB_PREFIX" ]]; then
        info "DB-Präfix bereits korrekt: $CFG_DB_PREFIX – übersprungen"
        return
    fi

    info "DB-Präfix: $old_prefix → $CFG_DB_PREFIX"

    if [[ "$DRY_RUN" == "true" ]]; then
        dry "Tabellen umbenennen: ${old_prefix}* → ${CFG_DB_PREFIX}*"
        dry "wp-config.php: table_prefix = '$CFG_DB_PREFIX'"
        dry "usermeta: meta_key ${old_prefix}capabilities → ${CFG_DB_PREFIX}capabilities"
        dry "options: option_name ${old_prefix}user_roles → ${CFG_DB_PREFIX}user_roles"
        return
    fi

    # Alle Tabellen umbenennen
    local tables
    tables=$(wp_cmd db query "SHOW TABLES LIKE '${old_prefix}%'" --skip-column-names 2>/dev/null)

    if [[ -z "$tables" ]]; then
        warn "Keine Tabellen mit Präfix '${old_prefix}' gefunden – übersprungen"
        return
    fi

    local renamed=0
    while IFS= read -r table; do
        local new_table="${CFG_DB_PREFIX}${table#$old_prefix}"
        wp_cmd db query "RENAME TABLE \`${table}\` TO \`${new_table}\`;" 2>/dev/null \
            && ((renamed++))
        [[ "$VERBOSE" == "true" ]] && info "  $table → $new_table"
    done <<< "$tables"
    success "$renamed Tabellen umbenannt"

    # usermeta-Keys aktualisieren
    wp_cmd db query "UPDATE \`${CFG_DB_PREFIX}usermeta\` SET meta_key = REPLACE(meta_key, '${old_prefix}', '${CFG_DB_PREFIX}') WHERE meta_key LIKE '${old_prefix}%';" 2>/dev/null
    success "usermeta-Keys aktualisiert"

    # options: user_roles-Key
    wp_cmd db query "UPDATE \`${CFG_DB_PREFIX}options\` SET option_name = REPLACE(option_name, '${old_prefix}', '${CFG_DB_PREFIX}') WHERE option_name LIKE '${old_prefix}%';" 2>/dev/null
    success "options-Keys aktualisiert"

    # wp-config.php aktualisieren
    sed -i "s/\\\$table_prefix\s*=\s*'${old_prefix}'/\$table_prefix = '${CFG_DB_PREFIX}'/" "$wp_config"
    success "wp-config.php: table_prefix = '$CFG_DB_PREFIX'"
}

# ── STEP 4: Search-Replace (alte → neue Domain) ───────────────────────────────

step_search_replace() {
    [[ -z "$CFG_DOMAIN_OLD" ]] && { info "Search/Replace übersprungen (keine alte Domain angegeben)"; return; }

    step "Search/Replace: $CFG_DOMAIN_OLD → $CFG_DOMAIN_NEW"

    local old_url_http="http://${CFG_DOMAIN_OLD}"
    local old_url_https="https://${CFG_DOMAIN_OLD}"
    local new_url="${CFG_PROTOCOL}://${CFG_DOMAIN_NEW}"

    if [[ "$DRY_RUN" == "true" ]]; then
        dry "wp search-replace '$old_url_https' '$new_url' --all-tables"
        dry "wp search-replace '$old_url_http' '$new_url' --all-tables"
        return
    fi

    wp_cmd search-replace "$old_url_https" "$new_url" --all-tables --report-changed-only \
        && success "https-URLs ersetzt"
    wp_cmd search-replace "$old_url_http"  "$new_url" --all-tables --report-changed-only \
        && success "http-URLs ersetzt"

    # Cache leeren
    wp_cmd cache flush 2>/dev/null && success "Cache geleert"
}

# ── STEP 5: Admin-User anlegen ────────────────────────────────────────────────

step_admin_user() {
    [[ "$CFG_ADMIN_CREATE" == "false" ]] && { info "Admin-User übersprungen"; return; }

    step "Admin-User anlegen"

    if [[ "$DRY_RUN" == "true" ]]; then
        dry "wp user create '$CFG_ADMIN_USER' '$CFG_ADMIN_EMAIL' --role=administrator"
        return
    fi

    # Prüfen ob User bereits existiert
    if wp_cmd user get "$CFG_ADMIN_USER" &>/dev/null; then
        warn "User '$CFG_ADMIN_USER' existiert bereits – Passwort wird aktualisiert"
        wp_cmd user update "$CFG_ADMIN_USER" --user_pass="$CFG_ADMIN_PASS" \
            && success "Passwort aktualisiert"
    else
        wp_cmd user create "$CFG_ADMIN_USER" "$CFG_ADMIN_EMAIL" \
            --role=administrator \
            --user_pass="$CFG_ADMIN_PASS" \
            --display_name="$CFG_ADMIN_DISPLAY" \
            && success "Admin-User angelegt: $CFG_ADMIN_USER"
    fi

    # Passwort in Log schreiben (nur wenn generiert)
    echo -e "\n  ${BOLD}Admin-Zugangsdaten:${RESET}"
    echo -e "  URL:       ${CFG_PROTOCOL}://${CFG_DOMAIN_NEW}/wp-admin/"
    echo -e "  User:      ${CFG_ADMIN_USER}"
    echo -e "  Passwort:  ${BOLD}${CFG_ADMIN_PASS}${RESET}"
    echo -e "  ${YELLOW}→ Passwort sofort ändern!${RESET}\n"
}

# ── STEP 6: Theme umbenennen ─────────────────────────────────────────────────

step_theme() {
    step "Theme umbenennen: $CFG_THEME_SOURCE → $CFG_THEME_TARGET"

    local wp_path="$PROJECT_ROOT/$CFG_WP_PATH"
    local themes_dir="$wp_path/wp-content/themes"
    local source_dir="$themes_dir/$CFG_THEME_SOURCE"
    local target_dir="$themes_dir/$CFG_THEME_TARGET"

    if [[ "$CFG_THEME_SOURCE" == "$CFG_THEME_TARGET" ]]; then
        info "Theme-Name unverändert – Text Domain wird geprüft"
    else
        if [[ "$DRY_RUN" == "true" ]]; then
            dry "cp -r $source_dir $target_dir"
        else
            if [[ -d "$target_dir" ]]; then
                warn "Ziel-Theme-Ordner existiert bereits: $target_dir"
            else
                cp -r "$source_dir" "$target_dir"
                success "Theme kopiert: $CFG_THEME_SOURCE → $CFG_THEME_TARGET"
            fi
        fi
    fi

    local work_dir="$target_dir"
    [[ "$DRY_RUN" == "true" ]] && work_dir="$source_dir"

    # style.css aktualisieren
    if [[ "$DRY_RUN" == "true" ]]; then
        dry "style.css: Theme Name → $CFG_CLIENT_NAME"
        dry "style.css: Text Domain → $CFG_TEXT_DOMAIN"
    else
        local style_css="$work_dir/style.css"
        if [[ -f "$style_css" ]]; then
            sed -i "s/Theme Name:.*/Theme Name: $CFG_CLIENT_NAME/" "$style_css"
            sed -i "s/Text Domain:.*/Text Domain: $CFG_TEXT_DOMAIN/" "$style_css"
            success "style.css aktualisiert"
        fi
    fi

    # Text Domain in PHP-Dateien ersetzen
    local old_domain
    old_domain=$(grep "Text Domain:" "$source_dir/style.css" 2>/dev/null \
        | sed 's/Text Domain: *//' | tr -d ' \r')

    if [[ -n "$old_domain" && "$old_domain" != "$CFG_TEXT_DOMAIN" ]]; then
        if [[ "$DRY_RUN" == "true" ]]; then
            dry "Text Domain in PHP: '$old_domain' → '$CFG_TEXT_DOMAIN'"
        else
            find "$work_dir" -name "*.php" -exec \
                sed -i "s/'${old_domain}'/'${CFG_TEXT_DOMAIN}'/g" {} +
            find "$work_dir" -name "*.php" -exec \
                sed -i "s/\"${old_domain}\"/\"${CFG_TEXT_DOMAIN}\"/g" {} +
            success "Text Domain in PHP-Dateien ersetzt: $old_domain → $CFG_TEXT_DOMAIN"
        fi
    fi

    # Theme aktivieren
    if [[ "$DRY_RUN" == "true" ]]; then
        dry "wp theme activate '$CFG_THEME_TARGET'"
    else
        wp_cmd theme activate "$CFG_THEME_TARGET" \
            && success "Theme aktiviert: $CFG_THEME_TARGET"
    fi
}

# ── STEP 7: Plugin-Prefix umbenennen ─────────────────────────────────────────

step_plugin_prefix() {
    [[ "$CFG_RENAME_PREFIX" == "false" ]] && { info "Plugin-Prefix übersprungen"; return; }

    step "Plugin-Prefix: medialab_ → $CFG_NEW_PREFIX"

    local wp_path="$PROJECT_ROOT/$CFG_WP_PATH"
    local plugins_dir="$wp_path/wp-content/plugins"
    local old_prefix="medialab_"

    # Ziel-Plugins: nur Media Lab Plugins
    local ml_plugins=("media-lab-agency-core" "media-lab-seo")

    for plugin in "${ml_plugins[@]}"; do
        local plugin_dir="$plugins_dir/$plugin"
        [[ ! -d "$plugin_dir" ]] && continue

        if [[ "$DRY_RUN" == "true" ]]; then
            local count
            count=$(grep -r "$old_prefix" "$plugin_dir" --include="*.php" -l | wc -l)
            dry "Plugin $plugin: $count Dateien mit '$old_prefix'"
        else
            info "Plugin: $plugin"
            # PHP-Dateien: Funktions-Prefixes
            find "$plugin_dir" -name "*.php" -exec \
                sed -i "s/${old_prefix}/${CFG_NEW_PREFIX}/g" {} +
            # JS-Dateien: wp_localize_script etc.
            find "$plugin_dir" -name "*.js" -exec \
                sed -i "s/${old_prefix}/${CFG_NEW_PREFIX}/g" {} +
            success "  $plugin: Prefix ersetzt"
        fi
    done

    # Datenbank: option_names
    if [[ "$DRY_RUN" == "true" ]]; then
        dry "DB: UPDATE options SET option_name = REPLACE(option_name, 'medialab_', '$CFG_NEW_PREFIX')"
    else
        wp_cmd db query \
            "UPDATE \`${CFG_DB_PREFIX}options\` SET option_name = REPLACE(option_name, 'medialab_', '${CFG_NEW_PREFIX}') WHERE option_name LIKE 'medialab_%';" \
            2>/dev/null && success "DB option_names aktualisiert"
    fi
}

# ── STEP 8: Abschluss ─────────────────────────────────────────────────────────

step_finish() {
    step "Abschluss"

    if [[ "$DRY_RUN" == "false" ]]; then
        # Rewrite Rules neu aufbauen
        wp_cmd rewrite flush 2>/dev/null && success "Rewrite Rules neu generiert"

        # Cache leeren
        wp_cmd cache flush 2>/dev/null

        # wp-config: Debug ausschalten (Sicherheit)
        local wp_config="$PROJECT_ROOT/$CFG_WP_PATH/wp-config.php"
        if grep -q "WP_DEBUG.*true" "$wp_config" 2>/dev/null; then
            sed -i "s/define.*WP_DEBUG.*true.*/define('WP_DEBUG', false);/" "$wp_config"
            success "WP_DEBUG deaktiviert"
        fi
    fi

    echo -e "\n${BOLD}${GREEN}╔═══════════════════════════════════════════════╗${RESET}"
    echo -e "${BOLD}${GREEN}║   ✅  Setup abgeschlossen!                    ║${RESET}"
    echo -e "${BOLD}${GREEN}╚═══════════════════════════════════════════════╝${RESET}"
    echo -e "\n  ${BOLD}Frontend:${RESET}  ${CFG_PROTOCOL}://${CFG_DOMAIN_NEW}/"
    echo -e "  ${BOLD}Backend:${RESET}   ${CFG_PROTOCOL}://${CFG_DOMAIN_NEW}/wp-admin/"
    echo -e "  ${BOLD}Log:${RESET}       $LOG_FILE\n"

    [[ "$DRY_RUN" == "true" ]] && \
        warn "TROCKENLAUF – keine Änderungen wurden gespeichert."
}

# ── Argument-Parsing ──────────────────────────────────────────────────────────

parse_args() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --config|-c)   CONFIG_FILE="$2"; shift 2 ;;
            --dry-run|-d)  DRY_RUN=true; shift ;;
            --verbose|-v)  VERBOSE=true; shift ;;
            --help|-h)
                echo -e "Usage: bash bin/setup.sh [OPTIONS]"
                echo -e "  --config FILE   Config-File (YAML)"
                echo -e "  --dry-run       Trockenlauf (keine Änderungen)"
                echo -e "  --verbose       Verbose Output"
                exit 0 ;;
            *) warn "Unbekannter Parameter: $1"; shift ;;
        esac
    done
}

# ── Main ──────────────────────────────────────────────────────────────────────

main() {
    mkdir -p "$(dirname "$LOG_FILE")"
    echo "=== Setup gestartet: $(date) ===" >> "$LOG_FILE"

    parse_args "$@"

    if [[ -n "$CONFIG_FILE" ]]; then
        load_config "$CONFIG_FILE"
    else
        interactive_mode
    fi

    derive_defaults
    validate
    show_summary
    confirm

    step_backup
    step_domain
    step_db_prefix
    step_search_replace
    step_admin_user
    step_theme
    step_plugin_prefix
    step_finish
}

main "$@"
