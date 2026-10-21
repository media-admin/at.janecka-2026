# Media Lab Project Starter

Project-specific CPTs, taxonomies, and ACF fields for client websites.

## What's Included

- **Custom Post Types**: Team, Project, Job, Service, Testimonial, FAQ, Hero Slides, Carousel, Maps
- **Taxonomies**: Project Categories, Service Categories, Job Categories, Job Types, Job Locations, etc.
- **ACF Field Groups**: 11 field groups (65 fields total) stored as JSON

## Usage

### For Each New Client Project:

1. Duplicate this plugin folder
2. Rename to `client-name-project` (e.g., `acme-corp-project`)
3. Update plugin header in main PHP file
4. Activate for client site
5. Customize CPTs/ACF as needed

## Customization

Add/remove CPTs in `inc/custom-post-types.php`
Add/remove taxonomies in `inc/taxonomies.php`
ACF fields auto-load from `acf-json/`

## Janecka-spezifische Module

Zusätzlich zu den Starter-Bausteinen enthält dieses Plugin projektspezifische WooCommerce-Erweiterungen:

- **Sale-Bereich** (`inc/sale/`): Sale-Kategorien (Damenuhren, Herrenuhren, Damenschmuck, Herrenschmuck, Sonstiges)
  mit automatischer Zuordnung bei Aktionspreis, dynamisches Menü, Weiterleitungen, dynamische Filter und
  Filter für Marken-Archive
- **Ähnliche Produkte** (`inc/related-cross-sells.php`): auf der Produktseite aus dem Cross-Sell-Feld statt
  Zufallsauswahl nach Kategorie

Regeln, Konfiguration, Deployment-Checkliste und Stolpersteine: [`docs/sale-filter-related.md`](docs/sale-filter-related.md)

**Beim Duplizieren für ein anderes Projekt:** `inc/sale/`, `inc/related-cross-sells.php` und die zugehörigen
`require_once`-Zeilen in `janecka-juweliere-project.php` entfernen; diese Module sind nicht für andere Projekte gedacht.

**Abhängigkeit:** Der Kategorie-Filter auf Marken-Archiven braucht `media-lab-woocommerce` ab Version 2.20.0
(`inc/filters/filter-bar.php`, Starter Kit). Das Plugin liegt nicht in diesem Repo (siehe `.gitignore`)
und muss bei Deployments mitgeliefert werden.

## Dependencies

Requires: Media Lab Agency Core plugin

## Version

1.0.0
