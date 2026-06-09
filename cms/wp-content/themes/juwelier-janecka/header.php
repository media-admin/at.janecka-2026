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

<?php
if (function_exists('get_field') && get_field('top_header_enable', 'option')) :
    $th_address = get_field('top_header_address', 'option');
    $th_hours   = get_field('top_header_hours',   'option');
    $th_phone   = get_field('top_header_phone',   'option');
    $th_email   = get_field('top_header_email',   'option');
    $th_social  = get_field('top_header_social',  'option');
    $th_style   = get_field('top_header_style',   'option');
    $bg_class     = 'top-header--' . esc_attr($th_style['background'] ?? 'primary');
    $mobile_class = 'top-header--mobile-' . esc_attr($th_style['mobile'] ?? 'toggle');
?>
<div class="top-header <?php echo $bg_class . ' ' . $mobile_class; ?>" role="complementary" aria-label="Kontaktinformationen">
    <div class="top-header__inner container">
        <div class="top-header__contact">
            
            <?php if (!empty($th_hours['enable']) && !empty($th_hours['text'])) : ?>
                <span class="top-header__item top-header__hours">
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <?php echo esc_html($th_hours['text']); ?>
                </span>
            <?php endif; ?>
            <?php if (!empty($th_address['enable'])) : ?>
                <?php
                    $filialen_page = get_page_by_path('filialen');
                    $filialen_url  = $filialen_page
                        ? get_permalink($filialen_page)
                        : home_url('/filialen/');
                    ?>
                    <span class="top-header__item top-header__address">
                        <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <a href="<?php echo esc_url($filialen_url); ?>">Unsere Filialen</a>
                    </span>
                <?php endif; ?>

            <?php if (!empty($th_phone['enable']) && !empty($th_phone['number'])) : ?>
                <span class="top-header__item top-header__phone">
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.53 2 2 0 0 1 3.6 1.37h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $th_phone['number'])); ?>"><?php echo esc_html(!empty($th_phone['display']) ? $th_phone['display'] : $th_phone['number']); ?></a>
                </span>
            <?php endif; ?>
            <?php if (!empty($th_email['enable']) && !empty($th_email['address'])) : ?>
                <span class="top-header__item top-header__email">
                    <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <a href="mailto:<?php echo esc_attr($th_email['address']); ?>"><?php echo esc_html($th_email['address']); ?></a>
                </span>
            <?php endif; ?>
        </div>
        <?php if (!empty($th_social['enable'])) : ?>
        <div class="top-header__social">
            <?php
            $social_links = array(
                'facebook'  => array('label' => 'Facebook',    'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>'),
                'instagram' => array('label' => 'Instagram',   'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>'),
                'linkedin'  => array('label' => 'LinkedIn',    'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/></svg>'),
                'twitter'   => array('label' => 'X / Twitter', 'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'),
                'youtube'   => array('label' => 'YouTube',     'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75,15.02 15.5,12 9.75,8.98 9.75,15.02" fill="#fff"/></svg>'),
                'xing'      => array('label' => 'Xing',        'icon' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.239.234.45.234h3.461c.518 0 .766-.348.945-.667l3.734-6.609-2.378-4.155c-.172-.315-.434-.659-.962-.659H3.648v.016z"/></svg>'),
            );
            foreach ($social_links as $key => $data) :
                if (!empty($th_social[$key])) : ?>
            <a href="<?php echo esc_url($th_social[$key]); ?>" class="top-header__social-link top-header__social-<?php echo esc_attr($key); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($data['label']); ?>"><?php echo $data['icon']; ?></a>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<header class="site-header" role="banner">
    <nav class="site-navigation" role="navigation" aria-label="Primary Navigation">

        <!-- Logo Row: Logo zentriert, Icons rechts -->
        <div class="site-navigation__logo-row">

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