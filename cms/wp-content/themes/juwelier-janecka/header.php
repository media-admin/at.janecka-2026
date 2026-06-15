<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">
    <?php esc_html_e( 'Zum Inhalt springen', 'custom-theme' ); ?>
</a>

<?php
if ( function_exists('get_field') && get_field('scroll_progress_enabled', 'option') && is_single() ) : ?>
<div class="scroll-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="<?php esc_attr_e('Lesefortschritt', 'custom-theme'); ?>"></div>
<?php endif; ?>

<header class="site-header" role="banner">
    <nav class="site-navigation" role="navigation" aria-label="Primary Navigation">

        <!-- Logo Row: Logo zentriert, Icons rechts -->
        <div class="site-navigation__logo-row">

            <!-- Filialen + Telefon (Desktop, links vom Logo) -->
            <?php
            $th_address = function_exists('get_field') ? get_field('top_header_address', 'option') : null;
            $th_phone   = function_exists('get_field') ? get_field('top_header_phone',   'option') : null;
            if ( (!empty($th_address['enable'])) || (!empty($th_phone['enable']) && !empty($th_phone['number'])) ) :
            ?>
            <div class="site-navigation__contact">
                <?php if (!empty($th_address['enable'])) :
                    $filialen_page = get_page_by_path('filialen');
                    $filialen_url  = $filialen_page ? get_permalink($filialen_page) : home_url('/filialen/');
                ?>
                    <a href="<?php echo esc_url($filialen_url); ?>" class="site-navigation__contact-item">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span>Unsere Filialen</span>
                    </a>
                <?php endif; ?>

                <?php if (!empty($th_phone['enable']) && !empty($th_phone['number'])) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $th_phone['number'])); ?>" class="site-navigation__contact-item">
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.53 2 2 0 0 1 3.6 1.37h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        <span><?php echo esc_html(!empty($th_phone['display']) ? $th_phone['display'] : $th_phone['number']); ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Mobile Toggle -->
            <button class="mobile-menu-toggle" aria-label="Toggle Menu" aria-expanded="false">
                <span></span>
            </button>

            <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo" aria-label="<?php bloginfo('name'); ?>">
                <?php
                $logo_desktop       = function_exists('get_field') ? get_field('logo_desktop', 'option')       : null;
                $logo_mobile        = function_exists('get_field') ? get_field('logo_mobile', 'option')        : null;
                $logo_desktop_width = function_exists('get_field') ? get_field('logo_desktop_width', 'option') : 180;
                $logo_mobile_width  = function_exists('get_field') ? get_field('logo_mobile_width', 'option')  : 120;
                if ($logo_desktop) :
                    echo '<img src="' . esc_url($logo_desktop['url']) . '"'
                       . ' alt="' . esc_attr($logo_desktop['alt'] ?: get_bloginfo('name')) . '"'
                       . ' width="' . esc_attr($logo_desktop_width) . '"'
                       . ' class="site-logo__img site-logo__img--desktop"'
                       . ' loading="eager">';
                    if ($logo_mobile) :
                        echo '<img src="' . esc_url($logo_mobile['url']) . '"'
                           . ' alt="' . esc_attr($logo_mobile['alt'] ?: get_bloginfo('name')) . '"'
                           . ' width="' . esc_attr($logo_mobile_width) . '"'
                           . ' class="site-logo__img site-logo__img--mobile"'
                           . ' loading="eager">';
                    endif;
                else :
                    echo '<span class="site-logo__text">' . esc_html(get_bloginfo('name')) . '</span>';
                endif;
                ?>
            </a>

            <!-- Utility Icons -->
            <div class="header-icons">

                <button class="header-icons__btn header-icons__search-btn" 
                        aria-label="Suche" 
                        aria-expanded="false"
                        aria-controls="search-overlay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            

                <span class="header-icons__sep" aria-hidden="true"></span>

                <?php if ( class_exists('WooCommerce') ) : ?>
                <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wc_get_account_endpoint_url('dashboard') ); ?>" class="header-icons__btn" aria-label="Mein Konto">
                <?php else : ?>
                <a href="<?php echo esc_url( wc_get_page_permalink('myaccount') ); ?>" class="header-icons__btn" aria-label="Anmelden">
                <?php endif; ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </a>
                <?php endif; ?>

                <?php if ( class_exists('TINV_Wishlist') ) : ?>
                <a href="<?php echo esc_url( tinv_url_wishlist_default() ); ?>" class="header-icons__btn" aria-label="Wunschliste">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                </a>
                <?php endif; ?>

                <?php
                $wishlist_url = function_exists( 'YITH_WCWL' )
                    ? YITH_WCWL()->get_wishlist_url()
                    : home_url( '/meine-wunschliste/' );
                ?>
                <a href="<?php echo esc_url( $wishlist_url ); ?>" class="header-icons__btn" aria-label="Wunschliste">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </a>

                <?php if ( class_exists('WooCommerce') ) : ?>
                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="header-icons__btn header-icons__cart" aria-label="Warenkorb">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    <?php if ( WC()->cart && WC()->cart->get_cart_contents_count() > 0 ) : ?>
                    <span class="header-icons__badge"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>

            </div><!-- .header-icons -->

        </div><!-- .site-navigation__logo-row -->

        <!-- Desktop Menu -->
        <div class="primary-menu">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => '',
                'fallback_cb'    => false,
                'depth'          => 4,
                'walker'         => new Janecka_Walker_Mega_Menu(),
            ));
            ?>
        </div>

    </nav>

    <!-- Search Overlay -->
    <div class="search-overlay" id="search-overlay" role="dialog" aria-label="Suche" aria-hidden="true">
        <div class="search-overlay__backdrop"></div>
        <div class="search-overlay__inner">
            <button class="search-overlay__close" aria-label="Suche schließen">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <?php echo do_shortcode( '[aws_search_form]' ); ?>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" role="navigation" aria-label="Mobile Navigation">
    <?php
    wp_nav_menu(array(
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => '',
        'fallback_cb'    => false,
        'depth'          => 4,
    ));
    ?>
</div>

<!-- Mobile Overlay -->
<div class="mobile-menu-overlay"></div>