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

				<?php while ( $stores_query->have_posts() ) : $stores_query->the_post(); ?>
				<li class="store-card">
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

						<div class="store-card__body">
							<h2 class="store-card__title"><?php the_title(); ?></h2>
							<?php $ort = get_field( 'store-ort' ); ?>
							<?php if ( $ort ) : ?>
							<p class="store-card__location"><?php echo esc_html( $ort ); ?></p>
							<?php endif; ?>
						</div>

					</a>
				</li>
				<?php endwhile; wp_reset_postdata(); ?>

			</ul>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
