<footer class="site-footer">

    <!-- Footer Nav: Full Width, grauer Bereich -->
    <div class="site-footer__nav-area">
        <div class="site-footer__nav-inner">
            <?php
            if ( has_nav_menu('footer') ) :
                wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'menu_class'     => 'footer-nav__list',
                    'container'      => 'nav',
                    'container_class'=> 'footer-nav',
                    'depth'          => 2,
                    'fallback_cb'    => false,
                ));
            endif;
            ?>
        </div>
    </div>

    <!-- Footer Middle: Legal links + Social -->
    <div class="site-footer__middle">
        <div class="site-footer__middle-inner">

            <?php
            if ( has_nav_menu('footer-legal') ) :
                wp_nav_menu([
                    'theme_location'       => 'footer-legal',
                    'menu_class'           => 'footer-legal__list',
                    'container'            => 'nav',
                    'container_class'      => 'footer-legal',
                    'container_aria_label' => __('Rechtliche Links', 'custom-theme'),
                    'depth'                => 1,
                    'fallback_cb'          => false,
                ]);
            endif;
            ?>

            <!-- Social Media -->
            <?php
            $social = function_exists('get_field') ? get_field('top_header_social', 'option') : null;
            $social_links = array(
                'instagram' => array('label' => 'Instagram', 'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>'),
                'facebook'  => array('label' => 'Facebook',  'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>'),
                'youtube'   => array('label' => 'YouTube',   'icon' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.54C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.96A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z"/><polygon points="9.75,15.02 15.5,12 9.75,8.98 9.75,15.02"/></svg>'),
            );
            $has_social = false;
            if ($social) :
                foreach ($social_links as $key => $data) :
                    if (!empty($social[$key])) { $has_social = true; break; }
                endforeach;
            endif;
            if ($has_social) : ?>
            <div class="footer-social">
                <?php foreach ($social_links as $key => $data) :
                    if (!empty($social[$key])) : ?>
                <a href="<?php echo esc_url($social[$key]); ?>"
                   class="footer-social__link"
                   target="_blank" rel="noopener noreferrer"
                   aria-label="<?php echo esc_attr($data['label']); ?>">
                    <?php echo $data['icon']; ?>
                </a>
                <?php endif; endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Footer Bottom: Copyright + Agency -->
    <div class="site-footer__bottom">
        <div class="site-footer__bottom-inner">
            <p class="site-footer__copyright">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>.
                <?php esc_html_e('Alle Rechte vorbehalten.', 'custom-theme'); ?>
            </p>
            <p class="site-footer__credit">
                <?php esc_html_e('Konzept und Programmierung:', 'custom-theme'); ?>
                <a href="https://www.media-lab.at" target="_blank" rel="noopener noreferrer">Media Lab Tritremmel GmbH</a>
            </p>
        </div>
    </div>

</footer>

</div><!-- #page -->

<?php
$btt_enabled = ! function_exists('get_field') || get_field('btt_enabled', 'option') !== false;
if ( $btt_enabled ) : ?>
<button class="back-to-top" aria-label="<?php esc_attr_e('Zurück nach oben', 'custom-theme'); ?>" type="button">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><polyline points="18 15 12 9 6 15"></polyline></svg>
</button>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>