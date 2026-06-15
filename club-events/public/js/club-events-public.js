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
