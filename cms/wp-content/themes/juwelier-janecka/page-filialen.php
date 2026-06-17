<?php
/**
 * Template Name: Filialen-Übersicht
 * Template Description: Übersichtsseite aller Filialen mit redaktionellem Intro-Bereich
 */

get_header(); ?>

<main class="content stores-archive">

    <?php get_template_part('template-parts/components/breadcrumbs'); ?>

    <header class="page-header">
        <h1 class="page-header__title"><?php the_title(); ?></h1>
    </header>

	<?php // ── Redaktioneller Inhalt (Gutenberg-Editor) ────────────────────────── ?>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<?php if ( get_the_content() ) : ?>
		<section class="stores-archive__intro">
			<div class="container">
				<?php the_content(); ?>
			</div>
		</section>
		<?php endif; ?>
	<?php endwhile; endif; ?>


	<?php // ── Filialen-Cards ────────────────────────────────────────────────── ?>
	<?php
	$stores_query = new WP_Query( [
		'post_type'      => 'stores',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	] );
	?>

	<?php if ( $stores_query->have_posts() ) : ?>
	<section class="stores-archive__grid-section">
		<div class="container">
			<ul class="stores-grid">

				<?php
			// MLB-Location Posts einmalig laden für Mapping
			$mlb_locations = get_posts( [
				'post_type'      => 'mlb_location',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			] );

			while ( $stores_query->have_posts() ) : $stores_query->the_post();

				// Zugehörige mlb_location per Titel-Match finden
				$store_title  = get_the_title();
				$mlb_location = null;
				foreach ( $mlb_locations as $loc ) {
					if ( preg_match( '/\d{4}/', $store_title, $m ) && stripos( $loc->post_title, $m[0] ) !== false ) {
						$mlb_location = $loc;
						break;
					}
				}
				$mlb_id  = $mlb_location ? $mlb_location->ID : null;
				$address = $mlb_id ? get_post_meta( $mlb_id, 'mlb_location_address', true ) : '';
				$phone   = $mlb_id ? get_post_meta( $mlb_id, 'mlb_location_phone',   true ) : '';
				$email   = $mlb_id ? get_post_meta( $mlb_id, 'mlb_location_email',   true ) : '';
				$angebot = get_field( 'store-angebot' );
			?>
				<li class="store-card">

					<h2 class="store-card__title"><?php the_title(); ?></h2>

					<a class="store-card__link" href="<?php the_permalink(); ?>">
						<div class="store-card__image-wrap">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'large', [
									'class' => 'store-card__image',
									'alt'   => get_the_title(),
								] ); ?>
							<?php else : ?>
								<div class="store-card__image-placeholder"></div>
							<?php endif; ?>
						</div>
					</a>

					<div class="store-card__body">

						<?php if ( $address ) : ?>
						<p class="store-card__address"><?php echo nl2br( esc_html( $address ) ); ?></p>
						<?php endif; ?>

						<?php if ( $phone ) : ?>
						<p class="store-card__phone">
							<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
								<?php echo esc_html( $phone ); ?>
							</a>
						</p>
						<?php endif; ?>

						<?php if ( $email ) : ?>
						<p class="store-card__email">
							<a href="mailto:<?php echo esc_attr( $email ); ?>">
								<?php echo esc_html( $email ); ?>
							</a>
						</p>
						<?php endif; ?>

						<?php if ( $angebot ) : ?>
						<p class="store-card__offer"><?php echo esc_html( $angebot ); ?></p>
						<?php endif; ?>

					</div>

				</li>
			<?php endwhile; wp_reset_postdata(); ?>

			</ul>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
