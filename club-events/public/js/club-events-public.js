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

  // Share popover — built once, reused for every share button.
  var pop = null;
  function buildPopover() {
    if (pop) return pop;
    pop = document.createElement('div');
    pop.className = 'ce-share-pop';
    pop.hidden = true;
    pop.innerHTML =
      '<a class="ce-share-pop-item" data-net="whatsapp" target="_blank" rel="noopener">' +
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.9.8.8-2.8-.2-.3A8 8 0 1 1 12 20zm4.4-6c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.5.1-.2.2-.6.8-.7.9-.1.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5l.4-.4c.1-.2.2-.3.2-.5 0-.2 0-.4-.1-.5l-.7-1.7c-.2-.4-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9 0 1.1.8 2.2.9 2.4.1.2 1.6 2.5 4 3.4.5.2 1 .4 1.3.5.6.2 1.1.1 1.5.1.5-.1 1.4-.6 1.6-1.1.2-.5.2-1 .1-1.1-.1-.1-.2-.1-.4-.2z"/></svg><span>WhatsApp</span></a>' +
      '<a class="ce-share-pop-item" data-net="facebook" target="_blank" rel="noopener">' +
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.3v7A10 10 0 0 0 22 12z"/></svg><span>Facebook</span></a>' +
      '<a class="ce-share-pop-item" data-net="email">' +
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span>E-Mail</span></a>' +
      '<button type="button" class="ce-share-pop-item" data-net="copy">' +
        '<svg viewBox="0 0 24 24" width="18" height="18" fill="none"><rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M5 15V5a2 2 0 0 1 2-2h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg><span class="ce-share-copy-label">' + (I18N.copyLink || 'Link kopieren') + '</span></button>';
    document.body.appendChild(pop);
    return pop;
  }

  function closePopover() { if (pop) pop.hidden = true; }

  function openPopover(btn, url, title) {
    var p = buildPopover();
    p.querySelector('[data-net="whatsapp"]').href = 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url);
    p.querySelector('[data-net="facebook"]').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    p.querySelector('[data-net="email"]').href    = 'mailto:?subject=' + encodeURIComponent(title) + '&body=' + encodeURIComponent(title + '\n\n' + url);
    p._url = url;

    p.hidden = false;
    var r = btn.getBoundingClientRect();
    var pw = p.offsetWidth, ph = p.offsetHeight;
    var top = r.bottom + window.scrollY + 8;
    var left = r.left + window.scrollX;
    // Keep within viewport horizontally.
    if (left + pw > window.scrollX + document.documentElement.clientWidth - 8) {
      left = r.right + window.scrollX - pw;
    }
    if (left < window.scrollX + 8) left = window.scrollX + 8;
    // Flip above if no room below.
    if (r.bottom + ph + 8 > document.documentElement.clientHeight) {
      top = r.top + window.scrollY - ph - 8;
    }
    p.style.top = top + 'px';
    p.style.left = left + 'px';
  }

  // Share trigger — native where available, popover otherwise.
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-action-share');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    var url   = btn.dataset.shareUrl   || window.location.href;
    var title = btn.dataset.shareTitle || document.title;

    if (navigator.share) {
      navigator.share({ title: title, url: url }).catch(function () {});
    } else {
      if (pop && !pop.hidden) { closePopover(); return; }
      openPopover(btn, url, title);
    }
  });

  // Popover item clicks (copy handled here; links navigate natively).
  document.addEventListener('click', function (e) {
    var item = e.target.closest('.ce-share-pop-item');
    if (!item) return;
    if (item.dataset.net === 'copy') {
      e.preventDefault();
      var label = item.querySelector('.ce-share-copy-label');
      copyLink(pop._url || window.location.href, function () {
        if (!label) return;
        var orig = label.textContent;
        label.textContent = I18N.linkCopied || 'Link copied!';
        setTimeout(function () { label.textContent = orig; closePopover(); }, 1200);
      });
    } else {
      closePopover();
    }
  });

  // Dismiss popover on outside click / escape / scroll.
  document.addEventListener('click', function (e) {
    if (!pop || pop.hidden) return;
    if (e.target.closest('.ce-share-pop') || e.target.closest('.ce-action-share')) return;
    closePopover();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePopover(); });
  window.addEventListener('scroll', closePopover, true);

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
