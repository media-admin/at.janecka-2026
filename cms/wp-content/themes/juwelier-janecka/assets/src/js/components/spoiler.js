/**
 * Spoiler Component
 *
 * Sichtbarkeit wird vollständig via CSS gesteuert (max-height + Fade-Overlay).
 * JS toggelt nur die Klasse `is-open`.
 */

const BREAKPOINT_MD = 768;

export default class Spoiler {
  constructor() {
    this.spoilers = document.querySelectorAll('.spoiler');
    this._resizeTimer = null;
    this.init();
    this._bindResize();
  }

  // ─── Viewport ──────────────────────────────────────────────────────────────

  _isMobile() {
    return window.innerWidth < BREAKPOINT_MD;
  }

  _isActiveOnViewport(spoiler) {
    const showOn = spoiler.dataset.showOn || 'all';
    if (showOn === 'all')     return true;
    if (showOn === 'mobile')  return this._isMobile();
    if (showOn === 'desktop') return !this._isMobile();
    return true;
  }

  // ─── Init ──────────────────────────────────────────────────────────────────

  init() {
    if (this.spoilers.length === 0) return;

    this.spoilers.forEach(spoiler => {
      if (this._isActiveOnViewport(spoiler)) {
        this._activate(spoiler);
      } else {
        this._passivate(spoiler);
      }
    });
  }

  _activate(spoiler) {
    spoiler.classList.remove('spoiler--passive');

    if (!spoiler._spoilerBound) {
      const button = spoiler.querySelector('.spoiler__toggle');
      if (button) {
        button.addEventListener('click', () => this.toggle(spoiler));
      }
      spoiler._spoilerBound = true;
    }
  }

  _passivate(spoiler) {
    // Passive = immer vollständig sichtbar, kein Toggle
    spoiler.classList.remove('is-open');
    spoiler.classList.add('spoiler--passive');
  }

  // ─── Toggle ────────────────────────────────────────────────────────────────

  toggle(spoiler) {
    if (spoiler.classList.contains('spoiler--passive')) return;

    const isOpen = spoiler.classList.contains('is-open');
    const button = spoiler.querySelector('.spoiler__toggle');

    if (isOpen) {
      spoiler.classList.remove('is-open');
      button?.setAttribute('aria-expanded', 'false');
      button?.setAttribute('aria-label', button.getAttribute('data-open-text'));
    } else {
      spoiler.classList.add('is-open');
      button?.setAttribute('aria-expanded', 'true');
      button?.setAttribute('aria-label', button.getAttribute('data-close-text'));

      setTimeout(() => {
        const rect      = spoiler.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
        if (!isVisible) {
          spoiler.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 300);
    }
  }

  // ─── Resize ────────────────────────────────────────────────────────────────

  _bindResize() {
    window.addEventListener('resize', () => {
      clearTimeout(this._resizeTimer);
      this._resizeTimer = setTimeout(() => this._onResize(), 150);
    });
  }

  _onResize() {
    this.spoilers.forEach(spoiler => {
      const showOn = spoiler.dataset.showOn || 'all';
      if (showOn === 'all') return;

      if (this._isActiveOnViewport(spoiler)) {
        this._activate(spoiler);
      } else {
        this._passivate(spoiler);
      }
    });
  }
}