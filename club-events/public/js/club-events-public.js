/* Club Events Manager — Public JS */
(function () {
  'use strict';

  /* ─── Filter bar ──────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-filter-btn');
    if (!btn) return;

    var wrap = btn.closest('[data-ce-component]');
    if (!wrap) return;

    var bar = btn.closest('.ce-filter-bar');
    bar.querySelectorAll('.ce-filter-btn').forEach(function (b) {
      b.classList.remove('active');
    });
    btn.classList.add('active');

    var category = btn.dataset.category;

    if (wrap.dataset.ceComponent === 'timeline') {
      filterTimeline(wrap, category);
    } else if (wrap.dataset.ceComponent === 'overview') {
      filterOverview(wrap, category);
    } else if (wrap.dataset.ceComponent === 'cards') {
      filterCards(wrap, category);
    }
  });

  function filterTimeline(wrap, category) {
    wrap.querySelectorAll('.ce-timeline-item').forEach(function (item) {
      if (!category) {
        item.hidden = false;
        return;
      }
      var cats = (item.dataset.category || '').split(' ');
      item.hidden = !cats.includes(category);
    });

    wrap.querySelectorAll('.ce-month-group').forEach(function (group) {
      var hasVisible = Array.from(group.querySelectorAll('.ce-timeline-item')).some(function (i) { return !i.hidden; });
      group.hidden = !hasVisible;
    });
  }

  function filterOverview(wrap, category) {
    wrap.querySelectorAll('.ce-cal-event').forEach(function (ev) {
      if (!category) { ev.hidden = false; return; }
      var cats = (ev.dataset.category || '').split(' ');
      ev.hidden = !cats.includes(category);
    });
    wrap.querySelectorAll('.ce-list-item').forEach(function (item) {
      if (!category) { item.hidden = false; return; }
      var cats = (item.dataset.category || '').split(' ');
      item.hidden = !cats.includes(category);
    });
    // Toggle has-events class on cal days
    wrap.querySelectorAll('.ce-cal-day').forEach(function (day) {
      var visibleEvents = Array.from(day.querySelectorAll('.ce-cal-event')).filter(function (e) { return !e.hidden; });
      day.classList.toggle('ce-cal-has-events', visibleEvents.length > 0);
    });
  }

  function filterCards(wrap, category) {
    var items = wrap.querySelectorAll('.ce-card-item');
    var hasVisible = false;
    items.forEach(function (item) {
      if (!category) {
        item.hidden = false;
        hasVisible = true;
        return;
      }
      var cats = (item.dataset.category || '').split(' ');
      var visible = cats.includes(category);
      item.hidden = !visible;
      if (visible) hasVisible = true;
    });
    var empty = wrap.querySelector('.ce-empty');
    if (empty) empty.hidden = hasVisible;
  }

  /* ─── Copy to clipboard ───────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-copy-btn');
    if (!btn) return;
    var input = btn.parentElement.querySelector('input[type="text"]');
    if (!input) return;
    input.select();
    try {
      navigator.clipboard.writeText(input.value).then(function () {
        var orig = btn.textContent;
        btn.textContent = '✓ Copied';
        setTimeout(function () { btn.textContent = orig; }, 2000);
      });
    } catch (err) {
      document.execCommand('copy');
    }
  });

  /* ─── Sharing ─────────────────────────────────────────────────────────── */
  var I18N = (window.CE && window.CE.i18n) || {};

  function copyLink(url, onDone) {
    var done = onDone || function () {};
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(done).catch(function () { legacyCopy(url); done(); });
    } else {
      legacyCopy(url);
      done();
    }
  }
  function legacyCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta);
  }

  // Reveal the native share button only where the API exists.
  if (navigator.share) {
    document.querySelectorAll('.ce-share-native').forEach(function (b) { b.hidden = false; });
  }

  // Native share — share card button + tile share icon.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-share-native, .ce-tile-share-btn');
    if (!btn) return;
    e.preventDefault();

    var card = btn.closest('[data-share-url]') || btn;
    var url   = btn.dataset.shareUrl   || card.dataset.shareUrl   || window.location.href;
    var title = btn.dataset.shareTitle || card.dataset.shareTitle || document.title;

    if (navigator.share) {
      navigator.share({ title: title, url: url }).catch(function () {});
    } else {
      // No native share (e.g. tile button on desktop) → copy the link.
      copyLink(url, function () { flashShareLabel(btn); });
    }
  });

  // Copy-link button inside the share card.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-share-copy');
    if (!btn) return;
    e.preventDefault();
    var card = btn.closest('[data-share-url]');
    var url  = (card && card.dataset.shareUrl) || window.location.href;
    var label = btn.querySelector('.ce-share-copy-label');
    copyLink(url, function () {
      if (!label) return;
      var orig = label.textContent;
      label.textContent = I18N.linkCopied || 'Link copied!';
      btn.classList.add('is-copied');
      setTimeout(function () { label.textContent = orig; btn.classList.remove('is-copied'); }, 2000);
    });
  });

  function flashShareLabel(btn) {
    btn.classList.add('is-copied');
    setTimeout(function () { btn.classList.remove('is-copied'); }, 1500);
  }

  /* ─── Animate timeline items on scroll ───────────────────────────────── */
  if ('IntersectionObserver' in window) {
    var style = document.createElement('style');
    style.textContent = '.ce-timeline-item{opacity:0;transform:translateY(16px);transition:opacity .4s ease,transform .4s ease}.ce-timeline-item.is-visible{opacity:1;transform:none}';
    document.head.appendChild(style);

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.ce-timeline-item').forEach(function (item) {
      io.observe(item);
    });
  }

})();
