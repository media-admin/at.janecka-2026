/**
 * Footer Accordion – Mobile only
 * Arbeitet mit dem Standard wp_nav_menu Output (menu-item-has-children + sub-menu)
 */
export function initFooterAccordion() {
  const MQ = window.matchMedia('(max-width: 767.98px)');

  function setup() {
    const items = document.querySelectorAll(
      '.footer-nav .footer-nav__list > .menu-item-has-children'
    );

    items.forEach((item) => {
      // Nicht doppelt initialisieren
      if (item.dataset.accordionInit) return;
      item.dataset.accordionInit = 'true';

      const link    = item.querySelector(':scope > a');
      const submenu = item.querySelector(':scope > .sub-menu');
      if (!link || !submenu) return;

      // Toggle-Button erstellen
      const btn = document.createElement('button');
      btn.className  = 'footer-nav__toggle';
      btn.type       = 'button';
      btn.setAttribute('aria-expanded', 'false');
      btn.innerHTML  = link.textContent
        + '<span class="footer-nav__chevron" aria-hidden="true"></span>';

      // Link durch Button ersetzen (nur visuell – Link bleibt im DOM für Desktop)
      link.classList.add('footer-nav__link--hidden');
      item.insertBefore(btn, link);

      // Submenu stylen
      submenu.classList.add('footer-nav__submenu');

      btn.addEventListener('click', () => {
        const isOpen = btn.getAttribute('aria-expanded') === 'true';

        // Alle schließen
        items.forEach((i) => {
          const b = i.querySelector(':scope > .footer-nav__toggle');
          const s = i.querySelector(':scope > .sub-menu');
          if (b) { b.setAttribute('aria-expanded', 'false'); b.classList.remove('is-open'); }
          if (s) s.classList.remove('is-open');
        });

        // Dieses öffnen/schließen
        if (!isOpen) {
          btn.setAttribute('aria-expanded', 'true');
          btn.classList.add('is-open');
          submenu.classList.add('is-open');
        }
      });
    });
  }

  function teardown() {
    // Buttons wieder entfernen, Links wiederherstellen
    document.querySelectorAll('.footer-nav__toggle').forEach((btn) => btn.remove());
    document.querySelectorAll('.footer-nav__link--hidden').forEach((a) => {
      a.classList.remove('footer-nav__link--hidden');
    });
    document.querySelectorAll('.footer-nav__submenu').forEach((s) => {
      s.classList.remove('footer-nav__submenu', 'is-open');
    });
    document.querySelectorAll('[data-accordion-init]').forEach((i) => {
      delete i.dataset.accordionInit;
    });
  }

  function handleMQ(e) {
    if (e.matches) {
      setup();
    } else {
      teardown();
    }
  }

  // Initial + bei Resize
  handleMQ(MQ);
  MQ.addEventListener('change', handleMQ);
}