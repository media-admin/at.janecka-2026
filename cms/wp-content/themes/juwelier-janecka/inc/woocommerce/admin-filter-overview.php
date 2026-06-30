<?php
/**
 * Admin-Übersichtsseite: Produktfilter-Konfiguration
 *
 * Read-only Übersicht welche Kategorie welche Filter verwendet.
 * Erreichbar unter WP Admin → Produkte → Filter-Übersicht
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

// Menü-Eintrag registrieren
add_action( 'admin_menu', 'janecka_register_filter_overview_page' );

function janecka_register_filter_overview_page(): void {
	add_submenu_page(
		'edit.php?post_type=product',
		'Filter-Übersicht',
		'Filter-Übersicht',
		'manage_woocommerce',
		'janecka-filter-overview',
		'janecka_render_filter_overview_page'
	);
}

// Inline-Styles für die Übersichtsseite
add_action( 'admin_head', function() {
	$screen = get_current_screen();
	if ( ! $screen || $screen->id !== 'product_page_janecka-filter-overview' ) return;
	?>
	<style>
		.janecka-filter-overview { max-width: 1200px; }
		.janecka-filter-overview h1 { margin-bottom: 1rem; }
		.janecka-filter-overview .description { color: #666; margin-bottom: 1.5rem; }

		.filter-overview-table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; overflow: hidden; }
		.filter-overview-table th { background: #f6f7f7; padding: 10px 14px; text-align: left; font-weight: 600; border-bottom: 1px solid #e0e0e0; }
		.filter-overview-table td { padding: 10px 14px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
		.filter-overview-table tr:last-child td { border-bottom: none; }
		.filter-overview-table tr:hover td { background: #fafafa; }

		.filter-overview-table .cat-level-0 td:first-child { font-weight: 600; }
		.filter-overview-table .cat-level-1 td:first-child { padding-left: 2rem; }
		.filter-overview-table .cat-level-2 td:first-child { padding-left: 3.5rem; }

		.filter-tag { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin: 2px; }
		.filter-tag--attr { background: #e7f3ff; color: #0066cc; border: 1px solid #b3d4f5; }
		.filter-tag--price { background: #f0faf0; color: #2d7a2d; border: 1px solid #b3ddb3; }
		.filter-tag--brand { background: #fff3e0; color: #c17a00; border: 1px solid #f5d08a; }
		.filter-tag--inherited { background: #f5f5f5; color: #888; border: 1px solid #ddd; font-style: italic; }
		.filter-tag--none { background: #fff0f0; color: #cc0000; border: 1px solid #f5b3b3; }

		.source-badge { font-size: 11px; color: #999; margin-left: 4px; }
		.edit-link { font-size: 12px; margin-left: 8px; color: #2271b1; text-decoration: none; }
		.edit-link:hover { text-decoration: underline; }

		.legend { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; align-items: center; }
		.legend-item { display: flex; align-items: center; gap: 0.4rem; font-size: 13px; color: #555; }
	</style>
	<?php
} );

/**
 * Seite rendern
 */
function janecka_render_filter_overview_page(): void {
	$labels     = janecka_get_attribute_labels();
	$categories = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'orderby'    => 'name',
		'parent'     => 0,
	] );

	?>
	<div class="wrap janecka-filter-overview">
		<h1>Produktfilter-Übersicht</h1>
		<p class="description">
			Read-only Übersicht der Filter-Konfiguration pro Produktkategorie.
			Klicke auf „Bearbeiten" um die Filter einer Kategorie anzupassen.
		</p>

		<div class="legend">
			<strong>Legende:</strong>
			<span class="legend-item"><span class="filter-tag filter-tag--price">Preis</span> Preis-Slider</span>
			<span class="legend-item"><span class="filter-tag filter-tag--attr">Attribut</span> Produkt-Attribut</span>
			<span class="legend-item"><span class="filter-tag filter-tag--brand">Marke</span> Marken-Filter</span>
			<span class="legend-item"><span class="filter-tag filter-tag--inherited">vererbt</span> Von Elternkategorie übernommen</span>
			<span class="legend-item"><span class="filter-tag filter-tag--none">Keine</span> Keine Filter konfiguriert</span>
		</div>

		<table class="filter-overview-table">
			<thead>
				<tr>
					<th>Kategorie</th>
					<th>Aktive Filter</th>
					<th>Quelle</th>
					<th>Aktion</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! is_wp_error( $categories ) ) {
					foreach ( $categories as $cat ) {
						janecka_render_category_row( $cat, $labels, 0 );
					}
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Rekursiv Kategoriezeilen rendern
 */
function janecka_render_category_row( WP_Term $cat, array $labels, int $level ): void {
	$config   = janecka_get_category_filter_config( $cat->term_id );
	$edit_url = get_edit_term_link( $cat->term_id, 'product_cat' ) . '#acf-group_janecka_category_filters';
	$inherited = str_starts_with( $config['source'], 'parent:' );
	$is_default = $config['source'] === 'default';

	echo '<tr class="cat-level-' . $level . '">';

	// Kategorie-Name
	echo '<td>';
	echo esc_html( $cat->name );
	echo ' <span style="color:#999;font-size:11px;">(' . $cat->count . ' Produkte)</span>';
	echo '</td>';

	// Filter-Tags
	echo '<td>';

	if ( $is_default && empty( $config['attributes'] ) ) {
		echo '<span class="filter-tag filter-tag--none">Keine konfiguriert</span>';
	} else {
		// Preis
		if ( $config['show_price'] ) {
			$cls = $inherited ? 'filter-tag--inherited' : 'filter-tag--price';
			echo '<span class="filter-tag ' . $cls . '">Preis</span> ';
		}

		// Attribute
		foreach ( $config['attributes'] as $slug ) {
			$label = $labels[ $slug ] ?? $slug;
			// Marken-Slugs separat kennzeichnen
			$is_brand = in_array( $slug, [ 'pa_brand', 'pa_marke' ], true );
			if ( $is_brand ) {
				$cls = $inherited ? 'filter-tag--inherited' : 'filter-tag--brand';
				echo '<span class="filter-tag ' . $cls . '">' . esc_html( $label ) . '</span> ';
			} else {
				$cls = $inherited ? 'filter-tag--inherited' : 'filter-tag--attr';
				echo '<span class="filter-tag ' . $cls . '">' . esc_html( $label ) . '</span> ';
			}
		}

		if ( empty( $config['attributes'] ) && ! $config['show_price'] ) {
			echo '<span class="filter-tag filter-tag--none">Keine Filter aktiv</span>';
		}
	}

	echo '</td>';

	// Quelle
	echo '<td>';
	if ( $inherited ) {
		$parent_id   = (int) str_replace( 'parent:', '', $config['source'] );
		$parent_term = get_term( $parent_id, 'product_cat' );
		$parent_name = $parent_term && ! is_wp_error( $parent_term ) ? $parent_term->name : 'Elternkategorie';
		echo '<span class="source-badge">↑ ' . esc_html( $parent_name ) . '</span>';
	} elseif ( $is_default ) {
		echo '<span class="source-badge">Standard-Fallback</span>';
	} else {
		echo '<span class="source-badge">Eigene Konfiguration</span>';
	}
	echo '</td>';

	// Aktion
	echo '<td>';
	echo '<a href="' . esc_url( $edit_url ) . '" class="edit-link">Bearbeiten →</a>';
	echo '</td>';

	echo '</tr>';

	// Unterkategorien rekursiv
	$children = get_terms( [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => $cat->term_id,
		'orderby'    => 'name',
	] );

	if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
		foreach ( $children as $child ) {
			janecka_render_category_row( $child, $labels, $level + 1 );
		}
	}
}
