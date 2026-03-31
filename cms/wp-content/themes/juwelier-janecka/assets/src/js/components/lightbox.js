/**
 * Lightbox Component
 */

export default class Lightbox {
  constructor() {
    this.currentIndex = 0;
    this.images = [];
    this.lightbox = null;
    this.isZoomed = false;
    this.init();
  }

  init() {
    this.createLightbox();

    document.addEventListener('click', (e) => {
      const trigger = e.target.closest('[data-lightbox]');
      if (trigger) {
        e.preventDefault();
        this.open(trigger);
      }
    });

    document.addEventListener('keydown', (e) => {
      if (!this.lightbox.classList.contains('is-active')) return;
      if (e.key === 'Escape') this.close();
      if (e.key === 'ArrowLeft') this.prev();
      if (e.key === 'ArrowRight') this.next();
    });
  }

  createLightbox() {
    this.lightbox = document.createElement('div');
    this.lightbox.className = 'lightbox';
    this.lightbox.innerHTML = `
      <button class="lightbox__close" aria-label="Close">&times;</button>
      <button class="lightbox__prev" aria-label="Previous">&lsaquo;</button>
      <button class="lightbox__next" aria-label="Next">&rsaquo;</button>
      <div class="lightbox__content">
        <img class="lightbox__image" src="" alt="">
        <div class="lightbox__caption"></div>
      </div>
      <div class="lightbox__zoom-hint">Doppelklick zum Zoomen</div>
    `;

    document.body.appendChild(this.lightbox);

    const img = this.lightbox.querySelector('.lightbox__image');

    // Doppelklick: Zoom togglen
    img.addEventListener('dblclick', (e) => {
      e.stopPropagation();
      this.toggleZoom(e);
    });

    // Gezoomtes Bild verschieben
    img.addEventListener('mousemove', (e) => {
      if (!this.isZoomed) return;
      const rect = img.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width  - 0.5) * -60;
      const y = ((e.clientY - rect.top)  / rect.height - 0.5) * -60;
      img.style.transform = `scale(2.5) translate(${x}px, ${y}px)`;
    });

    // Touch: Pinch-to-Zoom
    this.initPinchZoom(img);

    this.lightbox.querySelector('.lightbox__close').addEventListener('click', () => this.close());
    this.lightbox.querySelector('.lightbox__prev').addEventListener('click', () => this.prev());
    this.lightbox.querySelector('.lightbox__next').addEventListener('click', () => this.next());

    this.lightbox.addEventListener('click', (e) => {
      if (e.target === this.lightbox) this.close();
      if (this.isZoomed) this.resetZoom();
    });
  }

  toggleZoom(e) {
    const img = this.lightbox.querySelector('.lightbox__image');
    if (this.isZoomed) {
      this.resetZoom();
    } else {
      this.isZoomed = true;
      img.classList.add('is-zoomed');
      this.lightbox.classList.add('is-zoomed');
    }
  }

  resetZoom() {
    const img = this.lightbox.querySelector('.lightbox__image');
    this.isZoomed = false;
    img.style.transform = '';
    img.classList.remove('is-zoomed');
    this.lightbox.classList.remove('is-zoomed');
  }

  initPinchZoom(img) {
    let startDist = 0;
    let startScale = 1;
    let currentScale = 1;

    img.addEventListener('touchstart', (e) => {
      if (e.touches.length === 2) {
        startDist = Math.hypot(
          e.touches[0].clientX - e.touches[1].clientX,
          e.touches[0].clientY - e.touches[1].clientY
        );
        startScale = currentScale;
      }
    }, { passive: true });

    img.addEventListener('touchmove', (e) => {
      if (e.touches.length === 2) {
        e.preventDefault();
        const dist = Math.hypot(
          e.touches[0].clientX - e.touches[1].clientX,
          e.touches[0].clientY - e.touches[1].clientY
        );
        currentScale = Math.min(Math.max(startScale * (dist / startDist), 1), 4);
        img.style.transform = `scale(${currentScale})`;
        this.isZoomed = currentScale > 1;
        img.classList.toggle('is-zoomed', this.isZoomed);
        this.lightbox.classList.toggle('is-zoomed', this.isZoomed);
      }
    }, { passive: false });

    img.addEventListener('touchend', () => {
      if (currentScale <= 1) this.resetZoom();
    });
  }

  open(trigger) {
    const gallery = trigger.dataset.lightbox;
    if (gallery) {
      this.images = Array.from(document.querySelectorAll(`[data-lightbox="${gallery}"]`));
      this.currentIndex = this.images.indexOf(trigger);
    } else {
      this.images = [trigger];
      this.currentIndex = 0;
    }
    this.resetZoom();
    this.showImage();
    this.lightbox.classList.add('is-active');
    document.body.style.overflow = 'hidden';
  }

  close() {
    this.resetZoom();
    this.lightbox.classList.remove('is-active');
    document.body.style.overflow = '';
  }

  showImage() {
    const current = this.images[this.currentIndex];
    const img = this.lightbox.querySelector('.lightbox__image');
    const caption = this.lightbox.querySelector('.lightbox__caption');

    img.src = current.href || current.src;
    img.alt = current.alt || '';
    caption.textContent = current.dataset.caption || '';

    const hasPrev = this.images.length > 1;
    this.lightbox.querySelector('.lightbox__prev').style.display = hasPrev ? 'block' : 'none';
    this.lightbox.querySelector('.lightbox__next').style.display = hasPrev ? 'block' : 'none';
  }

  prev() {
    this.resetZoom();
    this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
    this.showImage();
  }

  next() {
    this.resetZoom();
    this.currentIndex = (this.currentIndex + 1) % this.images.length;
    this.showImage();
  }
}