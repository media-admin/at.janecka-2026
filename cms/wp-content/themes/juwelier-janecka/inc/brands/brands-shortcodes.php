<?php
/**
 * Shortcodes für WooCommerce Brands
 *
 * [janecka_alle_marken]
 *   Zeigt alle aktiven Marken (mit mind. 1 Produkt) als Logo-Grid.
 *
 * [janecka_alle_marken ansicht="slider"]
 *   Wie oben, aber als Swiper-Slider.
 *
 * [janecka_marken kategorie="uhren"]
 *   Zeigt alle Marken einer bestimmten WooCommerce-Produktkategorie als Logo-Grid.
 *
 * [janecka_marken kategorie="uhren" ansicht="slider"]
 *   Wie oben, aber als Swiper-Slider.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;


// ============================================================
// Shortcode: Alle aktiven Marken
// ============================================================

add_shortcode( 'janecka_alle_marken', 'janecka_shortcode_alle_marken' );
function janecka_shortcode_alle_marken( array|string $atts ): string {
	$atts = shortcode_atts(
		[
			'ansicht'  => 'grid',
			'autoplay' => '0',
		],
		$atts,
		'janecka_alle_marken'
	);

	$brands = get_terms( [
		'taxonomy'   => 'product_brand',
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
		'meta_query' => [
			[
				'key'     => 'brand-is-active',
				'value'   => '1',
				'compare' => '=',
			],
		],
	] );

	if ( is_wp_error( $brands ) || empty( $brands ) ) {
		return '';
	}

	return 'slider' === $atts['ansicht']
		? janecka_render_brand_slider( $brands, (int) $atts['autoplay'] )
		: janecka_render_brand_grid( $brands );
}


// ============================================================
// Shortcode: Marken nach Kategorie
// ============================================================

add_shortcode( 'janecka_marken', 'janecka_shortcode_marken_by_kategorie' );
function janecka_shortcode_marken_by_kategorie( array|string $atts ): string {
	$atts = shortcode_atts(
		[
			'kategorie' => '',
			'ansicht'   => 'grid',
			'autoplay'  => '0',
		],
		$atts,
		'janecka_marken'
	);

	if ( empty( $atts['kategorie'] ) ) {
		return '';
	}

	$brands = janecka_get_brands_by_category( sanitize_key( $atts['kategorie'] ) );

	if ( empty( $brands ) ) {
		return '';
	}

	return 'slider' === $atts['ansicht']
		? janecka_render_brand_slider( $brands, (int) $atts['autoplay'] )
		: janecka_render_brand_grid( $brands );
}


// ============================================================
// Render: Grid
// ============================================================

function janecka_render_brand_grid( array $brands ): string {
	ob_start();
	?>
	<div class="brand-grid">
		<?php foreach ( $brands as $brand ) :
			$logo_url  = janecka_get_brand_logo_url( $brand, 'large' );
			$brand_url = get_term_link( $brand, 'product_brand' );
			if ( is_wp_error( $brand_url ) ) continue;
		?>
			<a
				href="<?php echo esc_url( $brand_url ); ?>"
				class="brand-grid__item"
				title="<?php echo esc_attr( $brand->name ); ?>"
			>
				<?php if ( $logo_url ) : ?>
					<img
						src="<?php echo esc_url( $logo_url ); ?>"
						alt="<?php echo esc_attr( $brand->name ); ?>"
						class="brand-grid__logo"
						loading="lazy"
						decoding="async"
					>
				<?php else : ?>
					<span class="brand-grid__name"><?php echo esc_html( $brand->name ); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}


// ============================================================
// Render: Slider (Swiper)
// ============================================================

function janecka_render_brand_slider( array $brands, int $autoplay = 0 ): string {
	// Eindeutige ID, falls mehrere Slider auf einer Seite
	static $slider_count = 0;
	$slider_count++;
	$slider_id = 'brand-slider-' . $slider_count;


	ob_start();
	?>
	<div class="brand-slider-wrapper">
		<button class="brand-slider__prev" aria-label="<?php esc_attr_e( 'Vorherige Marken', 'juwelier-janecka' ); ?>"></button>

		<div class="brand-slider swiper" id="<?php echo esc_attr( $slider_id ); ?>" data-autoplay="<?php echo esc_attr( $autoplay ); ?>">
			<div class="swiper-wrapper">
				<?php foreach ( $brands as $brand ) :
					$logo_url  = janecka_get_brand_logo_url( $brand, 'large' );
					$brand_url = get_term_link( $brand, 'product_brand' );
					if ( is_wp_error( $brand_url ) ) continue;
				?>
					<div class="swiper-slide brand-slider__slide">
						<a
							href="<?php echo esc_url( $brand_url ); ?>"
							class="brand-slider__item"
							title="<?php echo esc_attr( $brand->name ); ?>"
						>
							<?php if ( $logo_url ) : ?>
								<img
									src="<?php echo esc_url( $logo_url ); ?>"
									alt="<?php echo esc_attr( $brand->name ); ?>"
									class="brand-slider__logo"
									loading="lazy"
									decoding="async"
								>
							<?php else : ?>
								<span class="brand-slider__name"><?php echo esc_html( $brand->name ); ?></span>
							<?php endif; ?>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>

		<button class="brand-slider__next" aria-label="<?php esc_attr_e( 'Nächste Marken', 'juwelier-janecka' ); ?>"></button>
	</div>

	<?php
	return ob_get_clean();
}

