<?php
/**
 * WooCommerce Produktkategorie-Archiv-Header
 *
 * - Registriert ACF-Felder für product_cat-Terme (Banner-Bild + Beschreibung)
 * - Injiziert Banner + Beschreibung auf Kategorie-Archivseiten (kein Template-Override)
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;


// ============================================================
// 1. ACF-Felder für product_cat-Terme
// ============================================================

add_action( 'acf/init', 'janecka_register_category_acf_fields' );
function janecka_register_category_acf_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( [
		'key'    => 'group_category_header_fields',
		'title'  => 'Kategorie-Header',
		'fields' => [
			[
				'key'           => 'field_cat_banner',
				'label'         => 'Banner-Bild',
				'name'          => 'cat_banner',
				'type'          => 'image',
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'library'       => 'all',
				'instructions'  => 'Breites Bannerbild für die Kategorie-Seite (empfohlen: 1400 × 470 px)',
			],
			[
				'key'           => 'field_cat_description',
				'label'         => 'Beschreibung',
				'name'          => 'cat_description',
				'type'          => 'wysiwyg',
				'toolbar'       => 'basic',
				'media_upload'  => 0,
				'instructions'  => 'Optionaler Einleitungstext für die Kategorie-Seite. Wird unterhalb des Banners angezeigt.',
			],
		],
		'location' => [
			[
				[
					'param'    => 'taxonomy',
					'operator' => '==',
					'value'    => 'product_cat',
				],
			],
		],
		'menu_order' => 5,
		'active'     => true,
	] );
}


// ============================================================
// 2. Kategorie-Archiv-Header (Banner + Beschreibung) per Hook
// ============================================================

add_action( 'woocommerce_before_shop_loop', 'janecka_category_archive_header', 5 );
function janecka_category_archive_header(): void {
	if ( ! is_product_category() ) {
		return;
	}

	$term = get_queried_object();
	if ( ! ( $term instanceof WP_Term ) ) {
		return;
	}

	$tid = $term->term_id;

	// ── Banner-Bild ───────────────────────────────────────────
	$banner_url = '';
	$acf_banner = get_field( 'cat_banner', 'product_cat_' . $tid );
	if ( ! empty( $acf_banner['url'] ) ) {
		$banner_url = $acf_banner['url'];
	}

	// ── Beschreibung ──────────────────────────────────────────
	$description = '';

	// 1. ACF-Feld cat_description
	$acf_desc = get_field( 'cat_description', 'product_cat_' . $tid );
	if ( ! empty( $acf_desc ) ) {
		$description = $acf_desc;
	}

	// 2. Native WC term_description() als Fallback
	if ( ! $description ) {
		$description = term_description( $tid, 'product_cat' );
	}

	if ( ! $banner_url && ! $description ) {
		return;
	}
	?>
	<div class="brand-archive-header">

		<?php if ( $banner_url ) : ?>
			<div class="brand-archive-header__banner">
				<img
					src="<?php echo esc_url( $banner_url ); ?>"
					alt="<?php echo esc_attr( $term->name ); ?>"
					class="brand-archive-header__banner-img"
					width="1400"
					height="470"
					loading="eager"
					decoding="async"
				>
			</div>
		<?php endif; ?>

		<?php if ( $description ) : ?>
			<div class="brand-archive-header__description">
				<?php echo wp_kses( wpautop( $description ), wp_kses_allowed_html( 'post' ) ); ?>
			</div>
		<?php endif; ?>

	</div>
	<?php
}
