<?php
/**
 * Template Name: Marken-Übersicht
 *
 * Zeigt alle Marken an, die Produkte in der Hauptkategorie der übergeordneten
 * Seite haben. Einfach anwenden: WordPress-Seite "Marken" als Kind von z.B.
 * "Uhren" anlegen und dieses Template zuweisen.
 *
 * Kategorie-Mapping: Falls der Seitenslug nicht exakt mit dem WooCommerce
 * product_cat-Slug übereinstimmt, das Array $category_map unten anpassen.
 *
 * @package JuwelierJanecka
 */

defined( 'ABSPATH' ) || exit;

get_header();

// --------------------------------------------------------
// Kategorie-Slug aus der Elternseite ermitteln
// --------------------------------------------------------

$parent_id   = wp_get_post_parent_id( get_the_ID() );
$parent_slug = $parent_id ? get_post_field( 'post_name', $parent_id ) : '';

/**
 * Mapping: Seiten-Slug → WooCommerce product_cat-Slug
 * Anpassen wenn die Slugs voneinander abweichen.
 */
$category_map = [
	'uhren'          => 'uhren',
	'schmuck'        => 'schmuck',
	'hochzeit-liebe' => 'hochzeit-liebe',
];

$cat_slug = $category_map[ $parent_slug ] ?? $parent_slug;
$brands   = ! empty( $cat_slug ) ? janecka_get_brands_by_category( $cat_slug ) : [];

?>
<main id="main" class="site-main page-marken">
	<div class="container">

		<header class="page-header">
			<h1 class="page-header__title"><?php the_title(); ?></h1>
		</header>

		<?php if ( ! empty( $brands ) ) : ?>
			<div class="brand-grid">
				<?php foreach ( $brands as $brand ) :
					$logo_url  = janecka_get_brand_logo_url( $brand, 'large' );
					$brand_url = get_term_link( $brand, 'product_brand' );

					if ( is_wp_error( $brand_url ) ) {
						continue;
					}
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
							<span class="brand-grid__name">
								<?php echo esc_html( $brand->name ); ?>
							</span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>

		<?php else : ?>
			<p class="brand-grid__empty">
				<?php esc_html_e( 'Keine Marken gefunden.', 'juwelier-janecka' ); ?>
			</p>
		<?php endif; ?>

	</div>
</main>

<?php get_footer(); ?>
