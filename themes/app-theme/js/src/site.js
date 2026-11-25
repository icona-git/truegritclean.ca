import './minicollapse.js';
import './minioffcanvas.js';

// B&W portraits: only load on desktop so mobile never fetches the second image.
if (matchMedia('(min-width: 1024px)').matches) {
  document.querySelectorAll('.person-photo--bw[data-desktop-src]').forEach(function (img) {
    img.src = img.dataset.desktopSrc;
  });
}

/**
 * Bootstrap Collapse is now active.
 * Use standard BS5 data attributes in markup — no further setup needed:
 *
 *   <button data-bs-toggle="collapse" data-bs-target="#myElement">Toggle</button>
 *   <div id="myElement" class="collapse">...</div>
 */
