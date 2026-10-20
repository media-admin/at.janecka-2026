<?php
/**
 * Sale-Bereich: automatische Zuordnung reduzierter Produkte zu Sale-Kategorien.
 *
 * Ablage: cms/wp-content/plugins/janecka-juweliere-project/inc/sale/sale-sync.php
 * Einbindung: require_once in der Hauptdatei des Plugins (siehe unten im Chat).
 *
 * Funktionsweise
 * - Elternkategorie "Sale" (Slug sale) mit fünf Kindern (Slug sale-<gruppe>).
 * - Ein Produkt mit Aktionspreis bekommt "Sale" plus die passende(n) Unterkategorie(n).
 *   Ohne Aktionspreis werden nur diese Sale-Terms entfernt, alle anderen Kategorien bleiben.
 * - Zuordnung je Produkt: Bereich (uhren/schmuck) x Geschlecht (damen/herren/unisex).
 *   Beide Werte kommen aus ACF-Feldern an der Produktkategorie (mit Vererbung vom Parent).
 *   Fehlt das Geschlecht an allen Kategorien, gilt das Attribut pa_geschlecht.
 *   Unisex -> beide Gruppen. Unklar oder Kinder -> Sonstiges.
 * - Trigger: Produkt speichern (inkl. Quick-/Bulk-Edit), Variationen speichern,
 *   geplante Aktionen (WC-Cron), Ende eines WP-All-Import-Laufs, manueller Button
 *   unter WooCommerce > Status > Werkzeuge.
 *
 * @package JaneckaJuweliereProject
 */

defined( 'ABSPATH' ) || exit;

const JANECKA_SALE_PARENT_SLUG  = 'sale';
const JANECKA_SALE_CHILD_PREFIX = 'sale-';


// ============================================================
// Gruppen und Term-Helfer
// ============================================================

function janecka_sale_groups(): array {
	return array(
		'damenuhren'    => 'Damenuhren',
		'herrenuhren'   => 'Herrenuhren',
		'damenschmuck'  => 'Damenschmuck',
		'herrenschmuck' => 'Herrenschmuck',
		'sonstiges'     => 'Sonstiges',
	);
}

function janecka_sale_parent_id(): int {
	$term = get_term_by( 'slug', JANECKA_SALE_PARENT_SLUG, 'product_cat' );
	return $term ? (int) $term->term_id : 0;
}

/** Gruppen-Key => Term-ID der vorhandenen Sale-Unterkategorien. */
function janecka_sale_term_ids(): array {
	$out = array();
	foreach ( array_keys( janecka_sale_groups() ) as $key ) {
		$term = get_term_by( 'slug', JANECKA_SALE_CHILD_PREFIX . $key, 'product_cat' );
		if ( $term ) {
			$out[ $key ] = (int) $term->term_id;
		}
	}
	return $out;
}

/** Legt Sale und die fünf Unterkategorien an, falls sie fehlen (idempotent). */
function janecka_sale_ensure_terms(): void {
	$parent_id = janecka_sale_parent_id();
	if ( ! $parent_id ) {
		$result = wp_insert_term( 'Sale', 'product_cat', array( 'slug' => JANECKA_SALE_PARENT_SLUG ) );
		if ( is_wp_error( $result ) ) {
			return;
		}
		$parent_id = (int) $result['term_id'];
	}

	foreach ( janecka_sale_groups() as $key => $label ) {
		if ( get_term_by( 'slug', JANECKA_SALE_CHILD_PREFIX . $key, 'product_cat' ) ) {
			continue;
		}
		wp_insert_term(
			$label,
			'product_cat',
			array(
				'slug'   => JANECKA_SALE_CHILD_PREFIX . $key,
				'parent' => $parent_id,
			)
		);
	}
}


// ============================================================
// ACF-Felder an der Produktkategorie
// ============================================================

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
		'key'      => 'group_janecka_sale_mapping',
		'title'    => 'Sale-Zuordnung',
		'fields'   => array(
			array(
				'key'           => 'field_janecka_sale_bereich',
				'label'         => 'Sale-Bereich',
				'name'          => 'sale_bereich',
				'type'          => 'select',
				'instructions'  => 'In welchen Sale-Bereich gehören reduzierte Produkte dieser Kategorie? Leer = von der übergeordneten Kategorie erben.',
				'choices'       => array(
					''          => 'Erben',
					'uhren'     => 'Uhren',
					'schmuck'   => 'Schmuck',
					'sonstiges' => 'Sonstiges',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_janecka_sale_geschlecht',
				'label'         => 'Sale-Geschlecht',
				'name'          => 'sale_geschlecht',
				'type'          => 'select',
				'instructions'  => 'Leer = erben bzw. aus dem Produkt-Attribut "Geschlecht" nehmen. Unisex = erscheint in der Damen- und der Herren-Gruppe.',
				'choices'       => array(
					''       => 'Erben / aus Attribut',
					'damen'  => 'Damen',
					'herren' => 'Herren',
					'unisex' => 'Unisex',
				),
				'default_value' => '',
				'allow_null'    => 0,
				'return_format' => 'value',
			),
		),
		'location' => array(
			array(
				array(
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => 'product_cat',
				),
			),
		),
	) );
} );

/** Wert einer Einstellung für eine Kategorie, mit Vererbung von den Eltern. */
function janecka_sale_term_setting( int $term_id, string $key ): string {
	$ids = array_merge( array( $term_id ), get_ancestors( $term_id, 'product_cat', 'taxonomy' ) );
	foreach ( $ids as $id ) {
		$value = (string) get_term_meta( $id, $key, true );
		if ( '' !== $value ) {
			return $value;
		}
	}
	return '';
}

/** Setzt Standardwerte für die bestehenden Kategorien (per Slug, nur wenn noch leer). */
function janecka_sale_seed_defaults(): void {
	$defaults = array(
		'uhren'       => array( 'uhren', '' ),
		'schmuck'     => array( 'schmuck', '' ),
		'herren'      => array( 'uhren', 'herren' ),
		'damen'       => array( 'uhren', 'damen' ),
		'unisexuhren' => array( 'uhren', 'unisex' ),
		'damenuhren'  => array( '', 'damen' ),
		'herrenuhren' => array( '', 'herren' ),
	);

	foreach ( $defaults as $slug => $values ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term ) {
			continue;
		}
		list( $bereich, $geschlecht ) = $values;

		if ( '' !== $bereich && '' === (string) get_term_meta( $term->term_id, 'sale_bereich', true ) ) {
			update_term_meta( $term->term_id, 'sale_bereich', $bereich );
			update_term_meta( $term->term_id, '_sale_bereich', 'field_janecka_sale_bereich' );
		}
		if ( '' !== $geschlecht && '' === (string) get_term_meta( $term->term_id, 'sale_geschlecht', true ) ) {
			update_term_meta( $term->term_id, 'sale_geschlecht', $geschlecht );
			update_term_meta( $term->term_id, '_sale_geschlecht', 'field_janecka_sale_geschlecht' );
		}
	}
}


// ============================================================
// Zuordnungslogik
// ============================================================

/** Ermittelt die Sale-Gruppen (Keys) für ein Produkt. */
function janecka_sale_target_keys( WC_Product $product ): array {
	$id        = $product->get_id();
	$bereiche  = array();
	$geschlechter = array();

	foreach ( wc_get_product_term_ids( $id, 'product_cat' ) as $term_id ) {
		$bereich    = janecka_sale_term_setting( (int) $term_id, 'sale_bereich' );
		$geschlecht = janecka_sale_term_setting( (int) $term_id, 'sale_geschlecht' );
		if ( '' !== $bereich ) {
			$bereiche[ $bereich ] = true;
		}
		if ( '' !== $geschlecht ) {
			$geschlechter[ $geschlecht ] = true;
		}
	}

	// Fallback: Attribut "Geschlecht" (nur bei Schmuck befüllt).
	if ( empty( $geschlechter ) && taxonomy_exists( 'pa_geschlecht' ) ) {
		$slugs = wp_get_post_terms( $id, 'pa_geschlecht', array( 'fields' => 'slugs' ) );
		if ( ! is_wp_error( $slugs ) ) {
			foreach ( $slugs as $slug ) {
				$geschlechter[ $slug ] = true;
			}
		}
	}

	$map = array(
		'uhren'   => array( 'damen' => 'damenuhren',   'herren' => 'herrenuhren' ),
		'schmuck' => array( 'damen' => 'damenschmuck', 'herren' => 'herrenschmuck' ),
	);

	$keys = array();
	foreach ( $map as $bereich => $groups ) {
		if ( empty( $bereiche[ $bereich ] ) ) {
			continue;
		}
		foreach ( array_keys( $geschlechter ) as $geschlecht ) {
			if ( 'unisex' === $geschlecht ) {
				$keys = array_merge( $keys, array_values( $groups ) );
			} elseif ( isset( $groups[ $geschlecht ] ) ) {
				$keys[] = $groups[ $geschlecht ];
			}
		}
	}

	return $keys ? array_values( array_unique( $keys ) ) : array( 'sonstiges' );
}

/** Gleicht ein einzelnes Produkt ab (Variationen werden über das Elternprodukt behandelt). */
function janecka_sale_sync_product( $product_or_id ): void {
	$product = wc_get_product( $product_or_id );
	if ( ! $product ) {
		return;
	}
	if ( $product->is_type( 'variation' ) ) {
		$product = wc_get_product( $product->get_parent_id() );
		if ( ! $product ) {
			return;
		}
	}

	$parent_id = janecka_sale_parent_id();
	$managed   = janecka_sale_term_ids();
	if ( ! $parent_id || empty( $managed ) ) {
		return;
	}

	$id      = $product->get_id();
	$current = array_map( 'intval', wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'ids' ) ) );
	$keep    = array_diff( $current, $managed, array( $parent_id ) );

	$should = array();
	if ( $product->is_on_sale( 'edit' ) ) {
		$should[] = $parent_id;
		foreach ( janecka_sale_target_keys( $product ) as $key ) {
			if ( isset( $managed[ $key ] ) ) {
				$should[] = $managed[ $key ];
			}
		}
	}

	$new = array_values( array_unique( array_merge( $keep, $should ) ) );
	sort( $new );
	sort( $current );

	if ( $new !== $current ) {
		wp_set_object_terms( $id, $new, 'product_cat' );
	}
}

/** Vollständiger Abgleich: Kategorien anlegen, Standardwerte setzen, alle relevanten Produkte synchronisieren. */
function janecka_sale_run_full_sync(): string {
	janecka_sale_ensure_terms();
	janecka_sale_seed_defaults();

	delete_transient( 'wc_products_onsale' );

	$managed = array_values( janecka_sale_term_ids() );
	if ( janecka_sale_parent_id() ) {
		$managed[] = janecka_sale_parent_id();
	}

	$in_sale = array();
	if ( $managed ) {
		$in_sale = get_posts( array(
			'post_type'   => 'product',
			'post_status' => 'any',
			'fields'      => 'ids',
			'nopaging'    => true,
			'tax_query'   => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $managed,
				),
			),
		) );
	}

	$ids = array_unique( array_merge( wc_get_product_ids_on_sale(), $in_sale ) );
	foreach ( $ids as $id ) {
		get_the_terms( $id, 'product_type' ); // HPOS-Termcache vor wc_get_product() wärmen
		janecka_sale_sync_product( $id );
	}

	$parent = get_term( janecka_sale_parent_id(), 'product_cat' );
	$count  = ( $parent && ! is_wp_error( $parent ) ) ? (int) $parent->count : 0;

	return sprintf( 'Sale-Abgleich abgeschlossen: %d Produkte geprüft, %d aktuell in "Sale".', count( $ids ), $count );
}


// ============================================================
// Trigger
// ============================================================

// Produkt gespeichert (Edit-Screen, Quick-/Bulk-Edit, REST, Import via CRUD).
add_action( 'woocommerce_after_product_object_save', function ( $product ) {
	if ( $product instanceof WC_Product && ! $product->is_type( 'variation' ) ) {
		janecka_sale_sync_product( $product );
	}
}, 20 );

// Variationen per AJAX im Backend gespeichert.
add_action( 'woocommerce_ajax_save_product_variations', function ( $product_id ) {
	janecka_sale_sync_product( $product_id );
}, 20 );

// Geplante Aktionen: nachdem WooCommerce sie täglich aktiviert/beendet hat.
add_action( 'woocommerce_scheduled_sales', 'janecka_sale_run_full_sync', 20 );

// WP All Import: nach Ende eines Importlaufs.
add_action( 'pmxi_after_post_import', function () {
	janecka_sale_run_full_sync();
}, 20 );

// Manueller Button: WooCommerce > Status > Werkzeuge.
add_filter( 'woocommerce_debug_tools', function ( $tools ) {
	$tools['janecka_sale_resync'] = array(
		'name'     => 'Sale-Zuordnung abgleichen',
		'button'   => 'Jetzt abgleichen',
		'desc'     => 'Legt die Sale-Kategorien an (falls nötig), setzt Standardwerte und ordnet alle reduzierten Produkte neu zu.',
		'callback' => 'janecka_sale_run_full_sync',
	);
	return $tools;
} );