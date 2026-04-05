<?php
/**
 * Template für einzelne Filialen (CPT: stores)
 *
 * Kein Inline-CSS, kein Inline-JS — alles ausgelagert in _stores.scss
 * und via wp_enqueue_scripts in functions.php geladen.
 */

get_header(); ?>

<main class="content store-single">

	<?php while ( have_posts() ) : the_post(); ?>

	<?php // ── Hero ─────────────────────────────────────────────────────────── ?>
	<?php if ( has_post_thumbnail() ) : ?>
	<section class="store-hero">
		<?php the_post_thumbnail( 'full', [
			'class' => 'store-hero__image',
			'alt'   => get_the_title(),
		] ); ?>
		<div class="store-hero__overlay">
			<h1 class="store-hero__title"><?php the_title(); ?></h1>
		</div>
	</section>
	<?php else : ?>
	<section class="store-hero store-hero--no-image">
		<div class="container">
			<h1 class="store-hero__title store-hero__title--plain"><?php the_title(); ?></h1>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Aktuelle Meldung ──────────────────────────────────────────────── ?>
	<?php if ( get_field( 'store-aktuelle-meldung' ) ) : ?>
	<section class="store-notification">
		<div class="container">
			<h2 class="store-notification__header"><?php _e( 'Aktueller Hinweis', 'juwelier-janecka' ); ?></h2>
			<p class="store-notification__content"><?php the_field( 'store-aktuelle-meldung' ); ?></p>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Kurzbeschreibung ──────────────────────────────────────────────── ?>
	<?php if ( get_field( 'store-kurzbeschreibung' ) ) : ?>
	<section class="store-description">
		<div class="container">
			<?php echo wp_kses_post( get_field( 'store-kurzbeschreibung' ) ); ?>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Kontakt + Karte ───────────────────────────────────────────────── ?>
	<section class="store-contact">
		<div class="container">
			<div class="store-contact__grid">

				<?php // Linke Spalte: Kontakt & Öffnungszeiten ?>
				<div class="store-contact__info">

					<?php // ── Öffnungszeiten ZUERST ────────────────────────── ?>
					<?php if ( have_rows( 'store-oeffnungszeiten' ) ) : ?>
					<div class="store-hours">
						<h2 class="store-hours__heading"><?php _e( 'Unsere Öffnungszeiten', 'juwelier-janecka' ); ?></h2>

						<?php while ( have_rows( 'store-oeffnungszeiten' ) ) : the_row();
							$days = [
								__( 'Montag',     'juwelier-janecka' ) => [ get_sub_field( 'monday_opened' ),    get_sub_field( 'monday_closed' ) ],
								__( 'Dienstag',   'juwelier-janecka' ) => [ get_sub_field( 'thuesday_opened' ),  get_sub_field( 'thuesday_closed' ) ],
								__( 'Mittwoch',   'juwelier-janecka' ) => [ get_sub_field( 'wednesday_opened' ), get_sub_field( 'wednesday_closed' ) ],
								__( 'Donnerstag', 'juwelier-janecka' ) => [ get_sub_field( 'thursday_opened' ),  get_sub_field( 'thursday_closed' ) ],
								__( 'Freitag',    'juwelier-janecka' ) => [ get_sub_field( 'friday_opened' ),    get_sub_field( 'friday_closed' ) ],
								__( 'Samstag',    'juwelier-janecka' ) => [ get_sub_field( 'saturday_opened' ),  get_sub_field( 'saturday_closed' ) ],
							];
						?>
						<table class="store-hours__table">
							<tbody>
								<?php foreach ( $days as $day => [ $open, $close ] ) : ?>
								<tr class="store-hours__row">
									<td class="store-hours__day"><?php echo esc_html( $day ); ?></td>
									<td class="store-hours__time">
										<?php if ( $open && $close ) : ?>
											<strong><?php echo esc_html( $open ); ?> – <?php echo esc_html( $close ); ?> Uhr</strong>
										<?php else : ?>
											<strong><?php _e( 'Geschlossen', 'juwelier-janecka' ); ?></strong>
										<?php endif; ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						<?php endwhile; ?>
					</div>
					<?php endif; ?>

					<?php // ── Terminwunsch-Button ─────────────────────────────── ?>
					<?php $mlb_location = get_field( 'store-mlb-location' ); ?>
					<?php if ( $mlb_location ) : ?>
					<div class="store-contact__cta">
						<button
							class="btn btn--primary store-booking__trigger"
							data-modal-target="booking-modal-<?php echo esc_attr( $mlb_location->ID ); ?>"
							aria-haspopup="dialog"
						>
							<?php _e( 'Termin vereinbaren', 'juwelier-janecka' ); ?>
						</button>
					</div>
					<?php endif; ?>

					<?php // ── Kontakt DARUNTER ────────────────────────────────── ?>
					<div class="store-contact__details-block">
						<h2 class="store-contact__heading"><?php _e( 'Kontakt', 'juwelier-janecka' ); ?></h2>

						<address class="store-contact__address">
							<?php the_field( 'store-strasse', get_the_ID() ); ?><br>
							<?php the_field( 'store-plz', get_the_ID() ); ?> <?php the_field( 'store-ort', get_the_ID() ); ?><br>
							<?php _e( 'Österreich / Austria', 'juwelier-janecka' ); ?>
						</address>

						<ul class="store-contact__details">
							<?php if ( get_field( 'store-telefon', get_the_ID() ) ) : ?>
							<li>
								<span class="store-contact__label"><?php _e( 'Telefon:', 'juwelier-janecka' ); ?></span>
								<a class="store-contact__link" href="tel:<?php the_field( 'store-telefon', get_the_ID() ); ?>">
									<?php the_field( 'store-telefon', get_the_ID() ); ?>
								</a>
							</li>
							<?php endif; ?>
							<?php if ( get_field( 'store-email', get_the_ID() ) ) : ?>
							<li>
								<span class="store-contact__label"><?php _e( 'E-Mail:', 'juwelier-janecka' ); ?></span>
								<a class="store-contact__link" href="mailto:<?php the_field( 'store-email', get_the_ID() ); ?>">
									<?php the_field( 'store-email', get_the_ID() ); ?>
								</a>
							</li>
							<?php endif; ?>
						</ul>
					</div>

				</div>

				<?php // Rechte Spalte: Google Map ?>
				<?php $location = get_field( 'store-map' ); ?>
				<?php if ( $location ) : ?>
				<div class="store-contact__map">
					<div
						class="acf-map"
						data-zoom="14"
						data-lat="<?php echo esc_attr( $location['lat'] ); ?>"
						data-lng="<?php echo esc_attr( $location['lng'] ); ?>"
					>
						<div
							class="marker"
							data-lat="<?php echo esc_attr( $location['lat'] ); ?>"
							data-lng="<?php echo esc_attr( $location['lng'] ); ?>"
							data-map-id="<?php echo esc_attr( defined('GOOGLE_MAPS_MAP_ID') ? GOOGLE_MAPS_MAP_ID : '' ); ?>"
						></div>
					</div>
				</div>
				<?php endif; ?>

			</div>
		</div>
	</section>


	<?php // ── Zahlungsmöglichkeiten ─────────────────────────────────────────── ?>
	<?php $payment_terms = get_the_terms( get_the_ID(), 'filialen-zahlungsweisen' ); ?>
	<?php if ( ! empty( $payment_terms ) && ! is_wp_error( $payment_terms ) ) : ?>
	<section class="store-payment">
		<div class="container">
			<h3 class="store-payment__heading"><?php _e( 'Zahlungsmöglichkeiten in dieser Filiale', 'juwelier-janecka' ); ?></h3>
			<ul class="store-payment__list">
				<?php foreach ( $payment_terms as $term ) :
					$image_id  = get_field( 'zahlungsweisen-logo', $term, false );
					$image_src = $image_id ? wp_get_attachment_image_src( $image_id, 'thumbnail' ) : false;
				?>
				<li class="store-payment__item <?php echo esc_attr( $term->slug ); ?>">
					<?php if ( $image_src ) : ?>
					<img
						class="store-payment__icon"
						src="<?php echo esc_url( $image_src[0] ); ?>"
						alt="<?php echo esc_attr( $term->name ); ?>"
						width="<?php echo esc_attr( $image_src[1] ); ?>"
						height="<?php echo esc_attr( $image_src[2] ); ?>"
					>
					<?php endif; ?>
					<span class="store-payment__name"><?php echo esc_html( $term->name ); ?></span>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Marken ────────────────────────────────────────────────────────── ?>
	<?php $brand_names = get_field( 'store-brand-tag' ); ?>
	<?php if ( $brand_names ) : ?>
	<section class="store-brands">
		<div class="container">

			<div class="store-brands__intro">
				<h2 class="store-brands__heading">
					<?php _e( 'An diesem Standort umfasst unser Sortiment folgende Marken', 'juwelier-janecka' ); ?>
				</h2>
				<p><strong><?php _e( 'Gerne reservieren wir unverbindlich Ihren Lieblingsartikel in Ihrer Wunschfiliale.', 'juwelier-janecka' ); ?></strong></p>
				<p>
					<?php _e( 'Falls Sie Ihren Lieblingsartikel in unserem Online-Shop nicht finden, organisieren wir Ihnen diesen unverbindlich. Kontaktieren Sie uns diesbezüglich gerne per', 'juwelier-janecka' ); ?>
					<a href="mailto:info@janecka.at"><?php _e( 'Mail', 'juwelier-janecka' ); ?></a>
					<?php _e( 'oder', 'juwelier-janecka' ); ?>
					<a href="tel:+4319113728"><?php _e( 'telefonisch', 'juwelier-janecka' ); ?></a>.
				</p>
			</div>

			<ul class="store-brands__grid">
				<?php foreach ( $brand_names as $brand ) :
					setup_postdata( $brand );
					$logo = get_field( 'brand-logo-main', $brand );
					$link = get_term_link( $brand->slug, $brand->taxonomy );
				?>
				<li class="store-brands__item">
					<a class="store-brands__link" href="<?php echo esc_url( $link ); ?>">
						<?php if ( $logo ) : ?>
							<img
								class="store-brands__logo"
								src="<?php echo esc_url( $logo['url'] ); ?>"
								alt="<?php echo esc_attr( $logo['alt'] ?: $brand->name ); ?>"
							>
						<?php else : ?>
							<span class="store-brands__name"><?php echo esc_html( $brand->name ); ?></span>
						<?php endif; ?>
					</a>
				</li>
				<?php endforeach; ?>
				<?php wp_reset_postdata(); ?>
			</ul>

		</div>
	</section>
	<?php endif; ?>


	<?php // ── Bildergalerie ─────────────────────────────────────────────────── ?>
	<?php $gallery_images = get_field( 'store-bildergalerie' ); ?>
	<?php if ( $gallery_images ) : ?>
	<section class="store-gallery">
		<div class="container">
			<h3 class="store-gallery__heading"><?php _e( 'Unsere Verkaufsräume', 'juwelier-janecka' ); ?></h3>
			<ul class="store-gallery__grid">
				<?php foreach ( $gallery_images as $image ) : ?>
				<li class="store-gallery__item">
					<img
						class="store-gallery__image"
						src="<?php echo esc_url( $image['sizes']['large'] ); ?>"
						alt="<?php echo esc_attr( $image['alt'] ); ?>"
					>
					<?php if ( $image['caption'] ) : ?>
					<p class="store-gallery__caption"><?php echo esc_html( $image['caption'] ); ?></p>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Schmuck-Service ───────────────────────────────────────────────── ?>
	<section class="store-service">
		<?php echo do_shortcode( '[content_schmuckservice]' ); ?>
	</section>

	<?php endwhile; ?>

</main>

<?php // ── Booking Modal ─────────────────────────────────────── ?>
<?php $mlb_location = get_field( 'store-mlb-location' ); ?>
<?php if ( $mlb_location ) : ?>
<div
    class="store-booking-modal"
    id="booking-modal-<?php echo esc_attr( $mlb_location->ID ); ?>"
    role="dialog"
    aria-modal="true"
    aria-label="<?php esc_attr_e( 'Termin vereinbaren', 'juwelier-janecka' ); ?>"
    data-location-id="<?php echo esc_attr( $mlb_location->ID ); ?>"
    hidden
>
    <div class="store-booking-modal__backdrop"></div>
    <div class="store-booking-modal__dialog">
        <button class="store-booking-modal__close" aria-label="<?php _e( 'Schließen', 'juwelier-janecka' ); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
        <div class="store-booking-modal__content">
			<?php echo do_shortcode( '[mlb_booking_form]' ); // kein location-Attribut ?>
		</div>
    </div>
</div>
<?php endif; ?>

<?php get_footer(); ?>
