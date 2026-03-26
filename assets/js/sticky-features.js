/**
 * Sticky Features v1.2 — GSAP ScrollTrigger Animation
 *
 * DOM structure (Bricks nestable output):
 *   [data-sf-wrap]
 *     .sf-scroll
 *       .sf-items  (CSS Grid: 2 columns, all items overlap in row 1)
 *         .sf-item  (display:contents — media & text become direct grid children)
 *           .sf-item-media  (grid-col:1, grid-row:1)
 *           .sf-item-text   (grid-col:2, grid-row:1)
 *         .sf-item ...
 *         .sf-progress / .sf-progress-bar
 */
(function () {
  'use strict';

  if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
  gsap.registerPlugin(ScrollTrigger);

  function initStickyFeatures() {
    // Skip in Bricks editor
    if (document.body.classList.contains('iframe') ||
        document.querySelector('.brx-body.iframe')) return;

    var wraps = document.querySelectorAll('[data-sf-wrap]');
    if (!wraps.length) return;

    wraps.forEach(function (w) {
      var container = w.querySelector('.sf-items');
      if (!container) return;

      // Collect items — .sf-item has display:contents so we find them by class
      var items = Array.from(container.querySelectorAll('.sf-item'));
      if (items.length < 1) return;

      // Each item has .sf-item-media and .sf-item-text children
      var medias = items.map(function (item) { return item.querySelector('.sf-item-media'); });
      var texts  = items.map(function (item) { return item.querySelector('.sf-item-text'); });

      var progressBar = w.querySelector('[data-sf-progress]');

      // Config from data attributes
      var duration    = parseFloat(w.getAttribute('data-sf-duration')) || 0.75;
      var ease        = w.getAttribute('data-sf-ease') || 'power4.inOut';
      var scrollAmount = parseFloat(w.getAttribute('data-sf-scroll-amount')) || 0.9;
      var borderRadius = w.getAttribute('data-sf-border-radius') || '0.75em';

      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) duration = 0.01;

      var count = items.length;
      var currentIndex = 0;

      // ── Initial state ──
      medias.forEach(function (m, i) {
        if (!m) return;
        if (i === 0) {
          gsap.set(m, { clipPath: 'inset(0% round ' + borderRadius + ')' });
          var vid = m.querySelector('video');
          if (vid && vid.play) { try { vid.play(); } catch (e) {} }
        } else {
          gsap.set(m, { clipPath: 'inset(50% round ' + borderRadius + ')' });
          var vid2 = m.querySelector('video');
          if (vid2 && vid2.pause) vid2.pause();
        }
      });

      texts.forEach(function (t, i) {
        if (!t) return;
        if (i === 0) {
          gsap.set(t, { autoAlpha: 1 });
          gsap.set(t.children, { autoAlpha: 1, y: 0 });
        } else {
          gsap.set(t, { autoAlpha: 0 });
          gsap.set(t.children, { autoAlpha: 0, y: 30 });
        }
      });

      // ── Transition ──
      function transition(fromIdx, toIdx) {
        if (fromIdx === toIdx) return;

        var fromMedia = medias[fromIdx];
        var toMedia   = medias[toIdx];
        var fromText  = texts[fromIdx];
        var toText    = texts[toIdx];

        // Clip-path on media
        if (fromIdx < toIdx) {
          // Forward: reveal incoming
          if (toMedia) {
            gsap.to(toMedia, {
              clipPath: 'inset(0% round ' + borderRadius + ')',
              duration: duration, ease: ease, overwrite: 'auto'
            });
          }
        } else {
          // Backward: hide outgoing
          if (fromMedia) {
            gsap.to(fromMedia, {
              clipPath: 'inset(50% round ' + borderRadius + ')',
              duration: duration, ease: ease, overwrite: 'auto'
            });
          }
        }

        // Text out
        if (fromText) {
          gsap.to(fromText.children, {
            autoAlpha: 0, y: -30,
            ease: 'power4.out', duration: 0.4, overwrite: 'auto',
            onComplete: function () { gsap.set(fromText, { autoAlpha: 0 }); }
          });
        }

        // Text in
        if (toText) {
          gsap.set(toText, { autoAlpha: 1 });
          gsap.fromTo(toText.children,
            { autoAlpha: 0, y: 30 },
            { autoAlpha: 1, y: 0, ease: 'power4.out', duration: duration, stagger: 0.1, overwrite: 'auto' }
          );
        }

        // Video play/pause
        if (fromMedia) {
          var vidOut = fromMedia.querySelector('video');
          if (vidOut && vidOut.pause) vidOut.pause();
        }
        if (toMedia) {
          var vidIn = toMedia.querySelector('video');
          if (vidIn && vidIn.play) { try { vidIn.play(); } catch (e) {} }
        }
      }

      // ── ScrollTrigger ──
      var steps = Math.max(1, count - 1);

      ScrollTrigger.create({
        trigger: w,
        start: 'center center',
        end: function () { return '+=' + (steps * 100) + '%'; },
        pin: true,
        pinSpacing: 'margin',
        scrub: true,
        invalidateOnRefresh: true,
        onUpdate: function (self) {
          var p = Math.min(self.progress, scrollAmount) / scrollAmount;
          var idx = Math.floor(p * steps + 1e-6);
          idx = Math.max(0, Math.min(steps, idx));

          if (progressBar) {
            gsap.set(progressBar, { scaleX: p });
          }

          if (idx !== currentIndex) {
            transition(currentIndex, idx);
            currentIndex = idx;
          }
        }
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStickyFeatures);
  } else {
    initStickyFeatures();
  }
})();
