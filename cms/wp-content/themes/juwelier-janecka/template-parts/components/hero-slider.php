<?php
/**
 * Hero Slider Component
 *
 * @package JuwelierJanecka
 */

$slides = $args['slides'] ?? [];

if ( empty( $slides ) ) {
    return;
}
?>

<section
    class="hero-slider swiper"
    aria-label="Hero Slider"
    data-autoplay="true"
    data-loop="true"
    data-delay="5000"
>
    <div class="swiper-wrapper">

        <?php foreach ( $slides as $index => $slide ) :
            $overlay = floatval( $slide['overlay_opacity'] ?? 0 );
        ?>
        <div
            class="swiper-slide hero-slide hero-slide--light"
            style="--overlay-opacity: <?php echo esc_attr( $overlay ); ?>"
        >
            <?php if ( ! empty( $slide['image'] ) ) : ?>
                <picture class="hero-slide__bg">
                    <img
                        src="<?php echo esc_url( $slide['image'] ); ?>"
                        alt="<?php echo esc_attr( $slide['title'] ?? '' ); ?>"
                        <?php echo $index === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'; ?>
                    >
                </picture>
            <?php endif; ?>

            <div class="hero-slide__overlay" aria-hidden="true"></div>

            <div class="hero-slide__content">

                <?php if ( ! empty( $slide['title'] ) ) : ?>
                    <h2 class="hero-slide__title">
                        <?php echo esc_html( $slide['title'] ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( ! empty( $slide['subtitle'] ) ) : ?>
                    <p class="hero-slide__subtitle">
                        <?php echo esc_html( $slide['subtitle'] ); ?>
                    </p>
                <?php endif; ?>

                <?php if ( ! empty( $slide['button_text'] ) && ! empty( $slide['button_link'] ) ) : ?>
                    <a
                        href="<?php echo esc_url( $slide['button_link'] ); ?>"
                        class="hero-slide__btn"
                    >
                        <?php echo esc_html( $slide['button_text'] ); ?>
                    </a>
                <?php endif; ?>

            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <!-- Navigation: visuell versteckt, für Accessibility behalten -->
    <button class="swiper-button-prev" aria-label="Vorheriger Slide"></button>
    <button class="swiper-button-next" aria-label="Nächster Slide"></button>

    <!-- Pagination: rechts oben -->
    <div class="swiper-pagination"></div>

</section>