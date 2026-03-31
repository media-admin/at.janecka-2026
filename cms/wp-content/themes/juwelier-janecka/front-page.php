<?php get_header(); ?>

<?php
// ── Hero Slider ───────────────────────────────────────────────────────────────
$hero_slides = new WP_Query( [
    'post_type'      => 'hero_slide',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
] );
?>

<?php if ( $hero_slides->have_posts() ) : ?>
<section
    class="hero-slider swiper"
    aria-label="<?php esc_attr_e( 'Hero Slider', 'custom-theme' ); ?>"
    data-autoplay="true"
    data-loop="true"
    data-delay="5000"
>
    <div class="swiper-wrapper">

        <?php while ( $hero_slides->have_posts() ) : $hero_slides->the_post();
            $subtitle    = get_field( 'subtitle' );
            $btn_text    = get_field( 'button_text' );
            $btn_url     = get_field( 'button_url' );
            $btn_style   = get_field( 'button_style' ) ?: 'primary';
            $img_desktop = get_field( 'image_desktop' );
            $img_mobile  = get_field( 'image_mobile' );
            $opacity     = get_field( 'overlay_opacity' ) ?? 0;
            $text_color  = get_field( 'text_color' ) ?: 'light';
        ?>

        <div
            class="swiper-slide hero-slide hero-slide--<?php echo esc_attr( $text_color ); ?>"
            style="--overlay-opacity: <?php echo esc_attr( $opacity / 100 ); ?>"
        >
            <?php if ( $img_desktop ) : ?>
            <picture class="hero-slide__bg">
                <?php if ( $img_mobile ) : ?>
                <source media="(max-width: 767px)" srcset="<?php echo esc_url( $img_mobile ); ?>">
                <?php endif; ?>
                <img
                    src="<?php echo esc_url( $img_desktop ); ?>"
                    alt="<?php the_title_attribute(); ?>"
                    loading="eager"
                    fetchpriority="high"
                >
            </picture>
            <?php endif; ?>

            <div class="hero-slide__overlay" aria-hidden="true"></div>

            <div class="hero-slide__content container">
                <?php if ( $subtitle ) : ?>
                    <p class="hero-slide__subtitle"><?php echo esc_html( $subtitle ); ?></p>
                <?php endif; ?>
                <?php if ( $btn_text && $btn_url ) : ?>
                    <a
                        href="<?php echo esc_url( $btn_url ); ?>"
                        class="btn btn--<?php echo esc_attr( $btn_style ); ?> hero-slide__btn"
                    >
                        <?php echo esc_html( $btn_text ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php endwhile; wp_reset_postdata(); ?>

    </div>

    <button class="swiper-button-prev" aria-label="<?php esc_attr_e( 'Vorheriger Slide', 'custom-theme' ); ?>"></button>
    <button class="swiper-button-next" aria-label="<?php esc_attr_e( 'Nächster Slide', 'custom-theme' ); ?>"></button>
    <div class="swiper-pagination"></div>
</section>
<?php endif; ?>

<main id="main-content" class="site-main" tabindex="-1">
    <div class="container">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        <?php endwhile; endif; ?>
    </div>
</main>

<?php get_footer(); ?>
