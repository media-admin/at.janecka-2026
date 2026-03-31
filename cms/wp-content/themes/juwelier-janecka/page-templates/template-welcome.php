<?php
/**
 * Template Name: Welcome Page
 * @package Custom_Theme
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$bg_image     = get_field( 'welcome_bg_image' );
$bg_overlay   = get_field( 'welcome_bg_overlay' ) ?: 0;
$logo         = get_field( 'welcome_logo' );
$content_area = get_field( 'welcome_content' );
$company_name = get_field( 'welcome_company_name' );
$address      = get_field( 'welcome_address' );
$phone        = get_field( 'welcome_phone' );
$email        = get_field( 'welcome_email' );
$social_links = get_field( 'welcome_social_links' );

$bg_style      = $bg_image ? ' style="background-image: url(' . esc_url( $bg_image['url'] ) . ');"' : '';
$overlay_style = ( $bg_image && $bg_overlay > 0 ) ? ' style="opacity: ' . round( $bg_overlay / 100, 2 ) . ';" ' : '';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<?php wp_head(); ?>
<style>
/* Social Icons als CSS background-image data URIs */
/* Keine Font-Abhängigkeit – funktioniert immer */
.welcome-page__social-link {
    background-repeat: no-repeat;
    background-position: center;
    background-size: 20px 20px;
}
.welcome-page__social-link--instagram {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='2' y='2' width='20' height='20' rx='5' ry='5'/%3E%3Cpath d='M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z'/%3E%3Cline x1='17.5' y1='6.5' x2='17.51' y2='6.5'/%3E%3C/svg%3E");
}
.welcome-page__social-link--instagram:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23d40000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='2' y='2' width='20' height='20' rx='5' ry='5'/%3E%3Cpath d='M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z'/%3E%3Cline x1='17.5' y1='6.5' x2='17.51' y2='6.5'/%3E%3C/svg%3E");
}
.welcome-page__social-link--facebook {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--facebook:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23d40000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--linkedin {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z'/%3E%3Crect x='2' y='9' width='4' height='12'/%3E%3Ccircle cx='4' cy='4' r='2'/%3E%3C/svg%3E");
}
.welcome-page__social-link--linkedin:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23d40000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z'/%3E%3Crect x='2' y='9' width='4' height='12'/%3E%3Ccircle cx='4' cy='4' r='2'/%3E%3C/svg%3E");
}
.welcome-page__social-link--xing {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M6.182 4H3.5L7.6 10.94 4 18h2.7l3.6-7.06L6.182 4zm9.4-2H13l-7.2 13.18L10.1 22h2.7l-4.3-6.82L15.582 2z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--xing:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23d40000'%3E%3Cpath d='M6.182 4H3.5L7.6 10.94 4 18h2.7l3.6-7.06L6.182 4zm9.4-2H13l-7.2 13.18L10.1 22h2.7l-4.3-6.82L15.582 2z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--youtube {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z'/%3E%3Cpolygon points='9.75 15.02 15.5 12 9.75 8.98 9.75 15.02'/%3E%3C/svg%3E");
}
.welcome-page__social-link--youtube:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23d40000' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M22.54 6.42a2.78 2.78 0 0 0-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 0 0-1.95 1.96A29 29 0 0 0 1 12a29 29 0 0 0 .46 5.58A2.78 2.78 0 0 0 3.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 0 0 1.95-1.95A29 29 0 0 0 23 12a29 29 0 0 0-.46-5.58z'/%3E%3Cpolygon points='9.75 15.02 15.5 12 9.75 8.98 9.75 15.02'/%3E%3C/svg%3E");
}
.welcome-page__social-link--tiktok {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.34 6.34 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.76a4.85 4.85 0 0 1-1.01-.07z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--tiktok:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23d40000'%3E%3Cpath d='M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.34 6.34 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.78 1.52V6.76a4.85 4.85 0 0 1-1.01-.07z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--x,
.welcome-page__social-link--twitter {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23666'%3E%3Cpath d='M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.91-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z'/%3E%3C/svg%3E");
}
.welcome-page__social-link--x:hover,
.welcome-page__social-link--twitter:hover {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='%23d40000'%3E%3Cpath d='M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.253 5.622 5.91-5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z'/%3E%3C/svg%3E");
}
</style>
</head>
<body class="welcome-page<?php echo $bg_image ? ' welcome-page--has-bg' : ''; ?>">

<div class="welcome-page__bg"<?php echo $bg_style; ?>>
<?php if ( $bg_image && $bg_overlay > 0 ) : ?><div class="welcome-page__bg-overlay"<?php echo $overlay_style; ?>></div><?php endif; ?>
</div>

<main class="welcome-page__main" id="main">
<div class="welcome-page__container">

  <header class="welcome-page__header">
    <?php if ( $logo ) : ?>
      <img class="welcome-page__logo" src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['alt'] ?: get_bloginfo( 'name' ) ); ?>" width="<?php echo esc_attr( $logo['width'] ); ?>" height="<?php echo esc_attr( $logo['height'] ); ?>">
    <?php elseif ( has_custom_logo() ) : the_custom_logo();
    else : ?><p class="welcome-page__site-name"><?php bloginfo( 'name' ); ?></p><?php endif; ?>
  </header>

  <?php if ( $content_area ) : ?>
  <section class="welcome-page__content"><?php echo wp_kses_post( $content_area ); ?></section>
  <?php endif; ?>

  <section class="welcome-page__company" aria-label="Firmendaten">
    <?php if ( $company_name ) : ?><p class="welcome-page__company-name"><?php echo esc_html( $company_name ); ?></p><?php endif; ?>
    <?php if ( $address ) : ?><address class="welcome-page__address"><?php echo nl2br( esc_html( $address ) ); ?></address><?php endif; ?>
    <ul class="welcome-page__contact">
      <?php if ( $phone ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
      <?php if ( $email ) : ?><li><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></li><?php endif; ?>
    </ul>

    <?php if ( $social_links ) : ?>
    <ul class="welcome-page__social" aria-label="Social Media">
    <?php foreach ( $social_links as $item ) :
        if ( empty( $item['url'] ) ) continue;
        $p     = strtolower( trim( $item['platform'] ?? '' ) );
        $label = esc_html( $item['platform'] ?: $item['url'] );
    ?>
      <li>
        <a href="<?php echo esc_url( $item['url'] ); ?>"
           target="_blank" rel="noopener noreferrer"
           aria-label="<?php echo $label; ?>"
           title="<?php echo $label; ?>"
           class="welcome-page__social-link welcome-page__social-link--<?php echo esc_attr( $p ); ?>">
        </a>
      </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </section>

</div>
</main>

<footer class="welcome-page__footer">
<div class="welcome-page__container">
  <?php wp_nav_menu( [ 'theme_location' => 'footer', 'menu_class' => 'welcome-page__footer-nav', 'container' => false, 'depth' => 1, 'fallback_cb' => false ] ); ?>
  <p class="welcome-page__copyright">&copy; <?php echo date( 'Y' ); ?> <?php echo esc_html( $company_name ?: get_bloginfo( 'name' ) ); ?></p>
</div>
</footer>

<!-- Footer-Seiten Modal -->
<div id="wpm" class="welcome-page__modal" role="dialog" aria-modal="true" aria-labelledby="wpm-title" hidden>
  <div class="welcome-page__modal-overlay" id="wpm-overlay"></div>
  <div class="welcome-page__modal-box">
    <div class="welcome-page__modal-header">
      <h2 class="welcome-page__modal-title" id="wpm-title"></h2>
      <button class="welcome-page__modal-close" id="wpm-close" type="button" aria-label="Schliessen">&times;</button>
    </div>
    <div class="welcome-page__modal-content" id="wpm-content">
      <div class="welcome-page__modal-loading">Wird geladen...</div>
    </div>
  </div>
</div>

<?php wp_footer(); ?>

<script>
(function(){
  'use strict';
  // Cookie Banner: sicherstellen dass er initialisiert wird
  // (main.js laeuft als type=module, manchmal nach DOMContentLoaded)
  function initCookies() {
    if (window.CookieConsent) return;
    var scripts = document.querySelectorAll('script[src*="main.js"]');
    if (scripts.length && !window.CookieConsent) {
      // main.js wurde geladen aber CookieConsent noch nicht init
      // Kleiner Delay um module-execution abzuwarten
      setTimeout(function(){
        if (!window.CookieConsent && window.customTheme) {
          // Manuell importieren als Fallback
          import(scripts[0].src).catch(function(){});
        }
      }, 300);
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCookies);
  } else {
    initCookies();
  }

  // Footer Modal
  var modal    = document.getElementById('wpm'),
      overlay  = document.getElementById('wpm-overlay'),
      closeBtn = document.getElementById('wpm-close'),
      titleEl  = document.getElementById('wpm-title'),
      contentEl= document.getElementById('wpm-content');
  if (!modal) return;

  function openModal() { modal.hidden = false; document.body.style.overflow = 'hidden'; closeBtn.focus(); }
  function closeModal() { modal.hidden = true; document.body.style.overflow = ''; contentEl.innerHTML = '<div class="welcome-page__modal-loading">Wird geladen...</div>'; }

  overlay.addEventListener('click', closeModal);
  closeBtn.addEventListener('click', closeModal);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !modal.hidden) closeModal(); });

  document.addEventListener('click', function(e){
    var link = e.target.closest('.welcome-page__footer-nav a');
    if (!link) return;
    var href = link.getAttribute('href') || '';
    if (!href || href === '#') return;
    // Externe Links normal oeffnen
    if (href.indexOf('http') === 0 && href.indexOf(location.hostname) === -1) return;
    e.preventDefault();
    titleEl.textContent = link.textContent.trim();
    openModal();
    // X-WPM-Request Header signalisiert welcome-mode.php: kein Redirect
    // ?wpm_content=1 → PHP liefert nur den reinen Inhalt (kein Layout, keine Scripts)
    var fetchUrl = href + (href.indexOf('?') === -1 ? '?' : '&') + 'wpm_content=1';
    fetch(fetchUrl, { headers: { 'X-WPM-Request': '1' } })
      .then(function(r){ return r.text(); })
      .then(function(html){
        var doc = (new DOMParser()).parseFromString(html, 'text/html');
        // wpm_content=1 liefert <article class="wpm-content"> direkt
        var src = doc.querySelector('.wpm-content')
                 || doc.querySelector('.entry-content')
                 || doc.querySelector('main article')
                 || doc.querySelector('main');
        if (src) {
          // H1 als Modal-Titel verwenden wenn vorhanden
          var h1 = src.querySelector('h1.wpm-content__title');
          if (h1) { titleEl.textContent = h1.textContent.trim(); h1.remove(); }
          contentEl.innerHTML = src.innerHTML;
        } else {
          contentEl.innerHTML = '<p>Inhalt konnte nicht geladen werden.</p>';
        }
      })
      .catch(function(){ contentEl.innerHTML = '<p>Fehler beim Laden der Seite.</p>'; });
  });
}());
</script>
</body>
</html>
