# Janecka: Sale-Bereich, dynamische Filter, Ähnliche Produkte

Projektspezifische Erweiterungen im Plugin `janecka-juweliere-project` (Stand Oktober 2026).
Nichts davon gehört ins Starter Kit; die einzige Ausnahme ist der Kategorie-Filter auf Marken-Archiven
(siehe Abschnitt 5), der als allgemeine Erweiterung in `media-lab-woocommerce` liegt.

## Übersicht der Dateien

| Datei | Zweck |
|---|---|
| `inc/sale/sale-sync.php` | Sale-Kategorien anlegen, Produkte bei Aktionspreis automatisch zuordnen, ACF-Felder an der Produktkategorie |
| `inc/sale/sale-frontend.php` | Menü (leere Sale-Punkte ausblenden), Weiterleitungen, Permalink- und Breadcrumb-Korrektur |
| `inc/sale/sale-filters.php` | Dynamische Filter für Sale und Unterkategorien |
| `inc/sale/brand-filters.php` | Dynamische Filter und Kategorie-Filter auf Marken-Archiven |
| `inc/related-cross-sells.php` | "Ähnliche Produkte" aus dem Cross-Sell-Feld |

Einbindung in `janecka-juweliere-project.php` (Reihenfolge einhalten, `sale-sync.php` zuerst):

```php
require_once plugin_dir_path( __FILE__ ) . 'inc/sale/sale-sync.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/sale/sale-frontend.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/sale/sale-filters.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/sale/brand-filters.php';
require_once plugin_dir_path( __FILE__ ) . 'inc/related-cross-sells.php';
```

## 1. Sale-Kategorien und automatische Zuordnung

**Struktur:** Produktkategorie `Sale` (Slug `sale`) mit fünf Kindern: `sale-damenuhren`, `sale-herrenuhren`,
`sale-damenschmuck`, `sale-herrenschmuck`, `sale-sonstiges`. URLs: `/sale/` und `/sale/sale-<gruppe>/`.
Die Kategorien werden vom Werkzeug-Button (unten) angelegt, nicht von Hand.

**Regel:** Ein Produkt mit Aktionspreis bekommt `Sale` plus die passende Unterkategorie. Ohne Aktionspreis
werden nur diese Sale-Terms entfernt; alle anderen Kategorien bleiben unberührt. Sale-Terms werden nie
von Hand gepflegt (der nächste Abgleich überschreibt sie).

**Welche Unterkategorie?** Bereich x Geschlecht, beides konfigurierbar an der Produktkategorie
(Produkte > Kategorien > Block "Sale-Zuordnung"):

| Feld | Werte |
|---|---|
| Sale-Bereich (`sale_bereich`) | Erben, Uhren, Schmuck, Sonstiges |
| Sale-Geschlecht (`sale_geschlecht`) | Erben/aus Attribut, Damen, Herren, Unisex |

- Leer = von der übergeordneten Kategorie erben.
- Fehlt das Geschlecht an allen Kategorien des Produkts, gilt das Attribut `pa_geschlecht` (bei Schmuck befüllt).
- Unisex erscheint in der Damen- und in der Herren-Gruppe. Kinder und alles Unklare kommt nach Sonstiges.
- Standardwerte setzt der Abgleich einmalig per Slug (nur wenn leer): `uhren`, `schmuck`, `herren`, `damen`,
  `unisexuhren`, `damenuhren`, `herrenuhren`. Weichen Slugs ab, die Felder von Hand setzen.
- Variationsprodukte: Das Elternprodukt zählt, sobald eine Variation reduziert ist.

**Wann wird abgeglichen?**

- Produkt speichern (Edit-Screen, Quick-/Bulk-Edit, REST), Variationen speichern
- täglich nach den geplanten Aktionen von WooCommerce (`woocommerce_scheduled_sales`)
- am Ende jedes WP-All-Import-Laufs (`pmxi_after_post_import`)
- manuell: **WooCommerce > Status > Werkzeuge > "Sale-Zuordnung abgleichen"**

Änderungen an den ACF-Feldern der Kategorie wirken erst beim nächsten Abgleich.

## 2. Menü, Weiterleitungen, URLs

- Hauptnavigation: "Sale" und die fünf Gruppen sind Menüpunkte vom Typ *Produktkategorie*, der Hauptpunkt
  hat die CSS-Klasse `mega-menu`. Das Menü liegt in der Datenbank und muss in **jeder Umgebung** einmal
  von Hand angelegt werden.
- Menüpunkte auf Sale-Kategorien ohne veröffentlichte Produkte werden ausgeblendet (Filter an
  `wp_nav_menu_objects`, gilt für Desktop und Mobile). Kindpunkte (z. B. das Mega-Menu-Bild) verschwinden mit.
- Die alten Seiten `/sale/sale-uhren/` und `/sale/sale-schmuck/` leiten per 301 auf `/sale/`. Eine leere
  Sale-Gruppe leitet per 302 auf `/sale/`. Die Seite `/sale/` (ID 436) wird von der Kategorie überlagert
  und kann in den Papierkorb.
- Produkt-Permalinks und der Breadcrumb auf Produktseiten ignorieren Sale-Kategorien
  (Filter `wc_product_post_type_link_product_cat`, `woocommerce_breadcrumb_main_term`). Ohne das würde sich die
  URL eines Produkts beim Start und Ende einer Aktion ändern.

## 3. Dynamische Filter für Sale

Die Filterleiste von `media-lab-woocommerce` liest ihre Attribute pro Kategorie aus ACF (`mlwf_attributes`).
Für Sale und seine Unterkategorien wird dieser Wert zur Laufzeit ersetzt (ACF-Filter `acf/load_value`):
Es erscheinen genau die Filter, die auch die Produktsuche für diese Produktmenge zeigt
(`mlwf_search_facets()`). Zusätzlich:

- Ein Filter mit nur einem Wert bleibt, wenn nicht alle Produkte diesen Wert tragen (im gemischten Sale trennt
  z. B. "Quartz" Uhren von Schmuck). Haben alle denselben Wert, entfällt er.
- Preisfilter immer an; auf `/sale/` eine Kategorie-Auswahl, die nur Gruppen mit Produkten zeigt.
- Nichts wird gespeichert; Sortimentsänderungen wirken sofort.

**Achtung:** `get_term_by()` ruft intern `get_terms()` auf. Jeder Filter an `get_terms_args`, der
`get_term_by()` nutzt, braucht einen Wiederholungsschutz (sonst Endlosschleife, Speicher voll).

## 4. Marken-Archive (`brand-filters.php`)

- Marken ohne eigene Attributliste im Term-Editor bekommen dynamische Filter nach den Produkten der Marke.
  Marken mit eigener Liste behalten diese. Die Marke selbst wird nicht als Filter angeboten.
- Preisfilter an; keine Vererbung auf die globale Standardliste.
- Bei vielen Produkten genügt eine einzige Abfrage für die Veröffentlichungsprüfung.

## 5. Kategorie-Filter auf Marken-Archiven

Auf Marken-Archiven zeigt die Filterleiste die Gruppe "Kategorie" mit allen Haupt- und Unterkategorien der
Marken-Produkte (Zähler innerhalb der Marke). Die Sale-Unterkategorien werden dort ausgeblendet, "Sale" selbst
bleibt (Filter `mlwf_brand_subcategories` in `brand-filters.php`).

**Voraussetzung:** gepatchte `media-lab-woocommerce/inc/filters/filter-bar.php` (Starter Kit, Commit
"Kategorie-Filter auf Marken-Archiven"). Das Plugin liegt nicht im Janecka-Repo (`.gitignore`), die Datei muss
bei Deployments per SFTP mitgeliefert werden. Ohne den Patch bleibt die Gruppe einfach aus; es entsteht kein Fehler.
Ein Plugin-Update aus dem Starter Kit enthält die Änderung.

## 6. Ähnliche Produkte (`related-cross-sells.php`)

Vorher: Zufallsauswahl nach gemeinsamer Kategorie oder Schlagwort (WooCommerce-Standard, 24 h im Transient).
Jetzt:

1. Hat das Produkt **Cross-Sells** (Produktdaten > Verknüpfte Produkte; der Import füllt sie über die Spalte
   "Cross-Sells Produkt" mit SKUs), werden genau diese gezeigt: in der gepflegten Reihenfolge, ohne Zufall, maximal 8.
   Nicht veröffentlichte oder ausgeblendete Produkte entfallen automatisch (Entwürfe erscheinen, sobald sie live sind).
2. Ohne Cross-Sells gilt die WooCommerce-Standardlogik als Fallback.
3. Die Sale-Kategorien zählen im Fallback nicht als Gemeinsamkeit.

Filter: `janecka_related_fallback_to_default` (Standard `true`; `false` = ohne Cross-Sells nichts zeigen),
`janecka_related_cross_sell_limit` (Standard `8`).

**Stolpersteine in WooCommerce** (der Code berücksichtigt sie): `wc_get_related_products()` kürzt per
`array_slice( …, 0, $limit )` und mischt vorher; `-1` schneidet das letzte Produkt ab. Darum wird `posts_per_page`
auf die Zahl der Cross-Sells gesetzt, das Mischen per `woocommerce_product_related_posts_shuffle` abgeschaltet und
`order` auf `asc` gestellt (`wc_products_array_orderby()` kehrt die Liste bei `desc` auch für `none` um).

## Deployment-Checkliste

1. Backup der Datenbank. Dateien hochladen (die fünf Dateien oben, `janecka-juweliere-project.php`, gepatchte `filter-bar.php`).
2. Frontend und `wp-admin` laden (Ausschluss eines fatalen Fehlers).
3. **WooCommerce > Status > Werkzeuge > "Sale-Zuordnung abgleichen"** ausführen und die Meldung prüfen.
4. Kategorien im Editor prüfen (Block "Sale-Zuordnung"), Einstellungen > Permalinks einmal speichern.
5. Menü umbauen (Abschnitt 2), Cache leeren (WP Fastest Cache), WooCommerce-Transienten leeren.
6. Tests im Inkognito-Fenster: `/sale/`, eine Sale-Gruppe, Weiterleitung `/sale/sale-uhren/`, Permalink und Breadcrumb eines
   reduzierten Produkts, Filter am Smartphone, Marken-Archiv, Produktseite mit und ohne Cross-Sells.
7. Automatik testen mit einem Entwurf-Produkt: Aktionspreis setzen, Zuordnung prüfen, Preis entfernen.
8. Nach dem nächsten Uhren-Import prüfen, ob reduzierte Uhren in der richtigen Sale-Gruppe liegen.

## Bekannte Grenzen

- Produkte ohne Kategorie, in "Unkategorisiert" oder in Kategorien ohne Bereichs-Einstellung landen im Sale unter *Sonstiges*.
- Die Zuordnung hängt an den Slugs der Standardkategorien (Abschnitt 1); bei abweichenden Slugs Felder von Hand setzen.
- Ähnliche Produkte: Bis alle Produkte Cross-Sells haben, gilt für den Rest der Fallback.
