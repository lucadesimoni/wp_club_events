/* Club Events Manager — Admin JS */
(function () {
  'use strict';

  var CFG = window.CE_ADMIN || {};
  var ajaxUrl = CFG.ajaxUrl || '';
  var nonce   = CFG.nonce   || '';
  var i18n    = CFG.i18n   || {};

  /* ─── Helpers ─────────────────────────────────────────────────────────── */
  function post(action, data) {
    var body = new FormData();
    body.append('action', action);
    body.append('nonce', nonce);
    for (var k in data) { body.append(k, data[k]); }
    return fetch(ajaxUrl, { method: 'POST', body: body }).then(function (r) { return r.json(); });
  }

  function showSyncResult(el, results) {
    if (!el) return;
    el.hidden = false;
    el.className = 'ce-sync-result';
    if (!Array.isArray(results)) {
      el.textContent = results.message || i18n.sync_done;
      return;
    }
    el.innerHTML = results.map(function (r) {
      if (r.status === 'success') {
        return '<strong style="color:#16a34a">✓ ' + escHtml(r.calendar) + '</strong> — '
          + r.created + ' created, ' + r.updated + ' updated';
      }
      return '<strong style="color:#dc2626">✗ ' + escHtml(r.calendar) + '</strong> — ' + escHtml(r.message);
    }).join('<br>');
    el.classList.add(results.every(function (r) { return r.status === 'success'; }) ? 'is-success' : 'is-error');
  }

  function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ─── Sync Now ────────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#ce-sync-btn');
    if (!btn) return;

    var result = document.getElementById('ce-sync-result');
    btn.disabled = true;
    btn.querySelector && (btn.querySelector('.dashicons') || {}).classList && btn.querySelector('.dashicons').classList.add('spin');
    var origText = btn.textContent;
    btn.textContent = i18n.syncing || 'Syncing…';

    post('ce_sync_now', {}).then(function (res) {
      btn.disabled = false;
      btn.textContent = origText;
      showSyncResult(result, res.success ? res.data : [{ status:'error', calendar:'All', message: i18n.sync_error }]);
    }).catch(function () {
      btn.disabled = false;
      btn.textContent = origText;
      if (result) { result.hidden = false; result.className = 'ce-sync-result is-error'; result.textContent = i18n.sync_error; }
    });
  });

  /* ─── Calendar form ───────────────────────────────────────────────────── */
  var calForm     = document.getElementById('ce-calendar-form');
  var calFormWrap = document.getElementById('ce-calendar-form-wrap');
  var calFormTitle = document.getElementById('ce-cal-form-title');
  var addCalBtn   = document.getElementById('ce-add-calendar-btn');
  var cancelBtn   = document.getElementById('ce-cal-cancel-btn');

  if (addCalBtn) {
    addCalBtn.addEventListener('click', function () {
      resetCalForm();
      calFormWrap.hidden = false;
      calFormTitle.textContent = 'Add Calendar';
      document.getElementById('ce-cal-name').focus();
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      calFormWrap.hidden = true;
    });
  }

  // Edit calendar
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-edit-cal-btn');
    if (!btn) return;
    var d = btn.dataset;
    document.getElementById('ce-cal-id').value = d.id;
    document.getElementById('ce-cal-name').value = d.name;
    document.getElementById('ce-cal-calendar-id').value = d.calendarId;
    document.getElementById('ce-cal-api-key').value = d.apiKey;
    document.getElementById('ce-cal-color').value = d.color;
    document.getElementById('ce-cal-sync-enabled').checked = d.syncEnabled === '1';

    // Restore event type checkboxes
    var saved = (d.eventTypes || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    var cbs = document.querySelectorAll('#ce-cal-event-types input[type="checkbox"]');
    cbs.forEach(function (cb) { cb.checked = saved.indexOf(cb.value) !== -1; });

    calFormWrap.hidden = false;
    calFormTitle.textContent = 'Edit Calendar';
    calFormWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  // Delete calendar
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-delete-cal-btn');
    if (!btn) return;
    if (!confirm(i18n.confirm_delete)) return;
    var id  = btn.dataset.id;
    var row = btn.closest('tr[data-id]');
    post('ce_delete_calendar', { id: id }).then(function (res) {
      if (res.success && row) {
        row.style.opacity = '0';
        row.style.transition = 'opacity .3s';
        setTimeout(function () { row.remove(); }, 300);
      }
    });
  });

  // Save calendar form (uses FormData directly to support checkbox arrays)
  if (calForm) {
    calForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var saveBtn = document.getElementById('ce-cal-save-btn');
      saveBtn.disabled = true;
      saveBtn.textContent = i18n.saving || 'Saving…';

      var body = new FormData(calForm);
      body.append('action', 'ce_save_calendar');
      body.append('nonce', nonce);

      fetch(ajaxUrl, { method: 'POST', body: body }).then(function (r) { return r.json(); }).then(function (res) {
        saveBtn.disabled = false;
        saveBtn.textContent = i18n.saved || 'Saved!';
        setTimeout(function () { window.location.reload(); }, 800);
      }).catch(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Calendar';
      });
    });
  }

  function resetCalForm() {
    if (!calForm) return;
    calForm.reset();
    document.getElementById('ce-cal-id').value = '';
    document.getElementById('ce-cal-color').value = '#3b82f6';
    document.getElementById('ce-cal-sync-enabled').checked = true;
  }

  /* ─── Delete subscriber ───────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-delete-sub-btn');
    if (!btn) return;
    if (!confirm(i18n.confirm_delete)) return;
    var id  = btn.dataset.id;
    var row = btn.closest('tr[data-id]');
    post('ce_delete_subscriber', { id: id }).then(function (res) {
      if (res.success && row) {
        row.style.opacity = '0';
        row.style.transition = 'opacity .3s';
        setTimeout(function () { row.remove(); }, 300);
      }
    });
  });

  /* ─── Copy to clipboard ───────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-copy-btn');
    if (!btn) return;
    var input = btn.parentElement.querySelector('input');
    if (!input) return;
    input.select();
    navigator.clipboard.writeText(input.value).then(function () {
      var orig = btn.textContent;
      btn.textContent = '✓ Copied';
      setTimeout(function () { btn.textContent = orig; }, 1800);
    }).catch(function () { document.execCommand('copy'); });
  });

})();
