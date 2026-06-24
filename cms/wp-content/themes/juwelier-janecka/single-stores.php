<?php
/**
 * Template für einzelne Filialen (CPT: stores)
 */

get_header(); ?>

<main class="content store-single">

	<?php get_template_part( 'template-parts/components/breadcrumbs' ); ?>

	<?php while ( have_posts() ) : the_post(); ?>

	<?php // ── Hero-Bild (full-width, kein Overlay) ────────────────────────── ?>
	<?php if ( has_post_thumbnail() ) : ?>
	<div class="store-hero">
		<?php the_post_thumbnail( 'full', [
			'class' => 'store-hero__image',
			'alt'   => get_the_title(),
		] ); ?>
	</div>
	<?php endif; ?>

	<?php // ── Seitentitel (zentriert, unter Hero) ────────────────────────── ?>
	<header class="page-header">
		<h1 class="page-header__title"><?php the_title(); ?></h1>
	</header>


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


	<?php // ── Öffnungszeiten + Kontakt (links) + Karte (rechts) ───────────── ?>
	<section class="store-contact">
		<div class="container">
			<div class="store-contact__grid">

				<?php // ── Linke Spalte ──────────────────────────────────────── ?>
				<div class="store-contact__left">

					<?php // Öffnungszeiten ?>
					<?php if ( have_rows( 'store-oeffnungszeiten' ) ) : ?>
					<div class="store-hours">
						<h2 class="store-hours__heading"><?php _e( 'Unsere Öffnungszeiten', 'juwelier-janecka' ); ?></h2>

						<?php while ( have_rows( 'store-oeffnungszeiten' ) ) : the_row();
							$days = [
								__( 'Montag',     'juwelier-janecka' ) => [ get_sub_field( 'monday_opened' ),    get_sub_field( 'monday_closed' ) ],
								__( 'Dienstag',   'juwelier-janecka' ) => [ get_sub_field( 'thuesday_opened' ),  get_sub_field( 'thuesday_closed' ) ],
								__( 'Mittwoch',   'juwelier-janecka' ) => [ get_sub_field( 'wednesday_opened' ), get_sub_field( 'wednesday_closed' ) ],
								__( 'Donnerstag', 'juwelier-janecka' ) => [ get_sub_field( 'thursday_opened' ),  get_sub_field( 'thuesday_closed' ) ],
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

					<?php // Termin-Button via Shortcode (Standard-Standort aus Theme-Optionen) ?>
					<div class="store-contact__cta">
						<?php echo do_shortcode( '[janecka_booking_button]' ); ?>
					</div>

					<?php // Kontakt ?>
					<div class="store-contact__info">
						<h2 class="store-contact__heading"><?php _e( 'Kontakt', 'juwelier-janecka' ); ?></h2>

						<address class="store-contact__address">
							<?php the_field( 'store-strasse', get_the_ID() ); ?><br>
							<?php the_field( 'store-plz', get_the_ID() ); ?> <?php the_field( 'store-ort', get_the_ID() ); ?><br>
							<?php _e( 'Österreich / Austria', 'juwelier-janecka' ); ?>
						</address>

						<ul class="store-contact__details">
							<?php if ( get_field( 'store-telefon', get_the_ID() ) ) : ?>
							<li>
								<?php _e( 'Telefon:', 'juwelier-janecka' ); ?>
								<a class="store-contact__link" href="tel:<?php the_field( 'store-telefon', get_the_ID() ); ?>">
									<?php the_field( 'store-telefon', get_the_ID() ); ?>
								</a>
							</li>
							<?php endif; ?>
							<?php if ( get_field( 'store-email', get_the_ID() ) ) : ?>
							<li>
								<?php _e( 'E-Mail:', 'juwelier-janecka' ); ?>
								<a class="store-contact__link" href="mailto:<?php the_field( 'store-email', get_the_ID() ); ?>">
									<?php the_field( 'store-email', get_the_ID() ); ?>
								</a>
							</li>
							<?php endif; ?>
						</ul>
					</div>

				</div><!-- .store-contact__left -->

				<?php // ── Rechte Spalte: Google Map (via gmap CPT, Cookie Consent) ── ?>
				<?php
				$map_post = get_field( 'store-map' );
				if ( $map_post instanceof WP_Post ) :
					$embed_src    = get_field( 'embed_src',    $map_post->ID );
					$marker_title = get_field( 'marker_title', $map_post->ID ) ?: get_the_title();
					$map_height   = get_field( 'map_height',   $map_post->ID ) ?: 450;
				?>
				<?php if ( $embed_src ) : ?>
				<div class="store-contact__map">
					<div class="google-map" style="--map-height: <?php echo intval( $map_height ); ?>px;">

						<?php // iframe: src leer lassen — JS setzt data-src erst nach Consent ?>
						<iframe
							class="google-map__iframe"
							data-src="<?php echo esc_url( $embed_src ); ?>"
							width="100%"
							height="<?php echo intval( $map_height ); ?>"
							title="<?php echo esc_attr( $marker_title ); ?>"
							style="border:0;"
							allowfullscreen
							loading="lazy"
							referrerpolicy="no-referrer-when-downgrade"
							hidden
						></iframe>

						<?php // Placeholder: wird angezeigt solange kein Consent vorliegt ?>
						<div class="google-map__placeholder">
							<div class="google-map__placeholder-inner">
								<div class="google-map__placeholder-icon">
									<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
										<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
										<circle cx="12" cy="9" r="2.5"/>
									</svg>
								</div>
								<p class="google-map__placeholder-title"><?php echo esc_html( $marker_title ); ?></p>
								<p class="google-map__placeholder-text">
									<?php _e( 'Um die Karte anzuzeigen, stimmen Sie bitte der Nutzung von Google Maps zu.', 'juwelier-janecka' ); ?>
								</p>
								<button class="google-map__placeholder-btn btn btn--primary" data-map-accept-comfort>
									<?php _e( 'Karte anzeigen & Cookies akzeptieren', 'juwelier-janecka' ); ?>
								</button>
								<button class="google-map__placeholder-settings-link" data-map-open-settings>
									<?php _e( 'Cookie-Einstellungen anpassen', 'juwelier-janecka' ); ?>
								</button>
							</div>
						</div>

					</div>
				</div>
				<?php endif; ?>
				<?php endif; ?>

			</div><!-- .store-contact__grid -->
		</div>
	</section>


	<?php // ── Zahlungsmöglichkeiten ─────────────────────────────────────────── ?>
	<?php $payment_terms = get_the_terms( get_the_ID(), 'filialen-zahlungsweisen' ); ?>
	<?php if ( ! empty( $payment_terms ) && ! is_wp_error( $payment_terms ) ) : ?>
	<section class="store-payment">
		<div class="container">
			<h2 class="store-payment__heading"><?php _e( 'Zahlungsmöglichkeiten in dieser Filiale', 'juwelier-janecka' ); ?></h2>
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
					>
					<?php endif; ?>
					<span class="store-payment__name"><?php echo esc_html( $term->name ); ?></span>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Marken (filialspezifisch via ACF) ───────────────────────────── ?>
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
			<div class="brand-grid">
				<?php foreach ( $brand_names as $brand ) :
					$logo = get_field( 'brand-logo-main', $brand );
					$link = get_term_link( $brand );
					if ( is_wp_error( $link ) ) continue;
				?>
					<a class="brand-grid__item" href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $brand->name ); ?>">
						<?php if ( $logo ) : ?>
							<img class="brand-grid__logo" src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['alt'] ?: $brand->name ); ?>" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="brand-grid__name"><?php echo esc_html( $brand->name ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>


	<?php // ── Bildergalerie ─────────────────────────────────────────────────────── ?>
	<?php $gallery_images = get_field( 'store-bildergalerie', get_the_ID() ); ?>
	<?php if ( $gallery_images ) : ?>
	<section class="store-gallery">
		<div class="container">
			<h2 class="store-gallery__heading"><?php _e( 'Unsere Verkaufsräume', 'juwelier-janecka' ); ?></h2>
			<ul class="store-gallery__grid">
				<?php foreach ( $gallery_images as $image ) : ?>
				<li class="store-gallery__item">
					<a data-lightbox="store-gallery" href="<?php echo esc_url( $image['url'] ); ?>" data-caption="<?php echo esc_attr( $image['caption'] ?? '' ); ?>">
						<img class="store-gallery__image" src="<?php echo esc_url( $image['sizes']['large'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ); ?>">
					</a>
					<?php if ( ! empty( $image['caption'] ) ) : ?>
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
		<div class="container">
			<?php echo do_shortcode( '[block id="82973"]' ); ?>
		</div>
	</section>


	<?php // ── Booking Modal (wird durch [janecka_booking_button] gerendert) ── ?>
	<?php
	// Das Modal wird vom Shortcode [janecka_booking_button] direkt ausgegeben.
	// Hier zusätzlich das store-spezifische Modal für den fall dass store-mlb-location
	// gesetzt ist und der Button den richtigen Standort vorauswählen soll.
	$mlb_location = get_field( 'store-mlb-location' );
	?>

	<?php endwhile; ?>

</main>

<?php get_footer(); ?>