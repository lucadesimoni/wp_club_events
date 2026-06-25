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

  function fadeRemove(el) {
    el.style.opacity = '0';
    el.style.transition = 'opacity .3s';
    setTimeout(function () { el.remove(); }, 300);
  }

  /* ─── Sync Now ────────────────────────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('#ce-sync-btn');
    if (!btn) return;

    var result = document.getElementById('ce-sync-result');
    btn.disabled = true;
    var origText = btn.textContent.trim();
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

  /* ═══════════════════════════════════════════════════════════════════════ *
   *  Event Types CRUD
   * ═══════════════════════════════════════════════════════════════════════ */
  var typeForm     = document.getElementById('ce-type-form');
  var typeFormWrap = document.getElementById('ce-type-form-wrap');
  var typeFormTitle = document.getElementById('ce-type-form-title');
  var addTypeBtn   = document.getElementById('ce-add-type-btn');
  var typeCancelBtn = document.getElementById('ce-type-cancel-btn');

  if (addTypeBtn) {
    addTypeBtn.addEventListener('click', function () {
      resetTypeForm();
      typeFormWrap.hidden = false;
      typeFormTitle.textContent = i18n.add_type || 'Add Event Type';
      document.getElementById('ce-type-name').focus();
    });
  }

  if (typeCancelBtn) {
    typeCancelBtn.addEventListener('click', function () {
      typeFormWrap.hidden = true;
    });
  }

  // Edit event type
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-type-edit');
    if (!btn) return;
    var chip = btn.closest('.ce-type-chip');
    if (!chip) return;
    document.getElementById('ce-type-term-id').value = chip.dataset.termId;
    document.getElementById('ce-type-name').value = chip.dataset.name;
    document.getElementById('ce-type-color').value = chip.dataset.color;
    typeFormWrap.hidden = false;
    typeFormTitle.textContent = i18n.edit_type || 'Edit Event Type';
    typeFormWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  // Delete event type
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.ce-type-delete');
    if (!btn) return;
    if (!confirm(i18n.confirm_delete)) return;
    var chip = btn.closest('.ce-type-chip');
    if (!chip) return;
    var termId = chip.dataset.termId;
    post('ce_delete_event_type', { term_id: termId }).then(function (res) {
      if (res.success) {
        fadeRemove(chip);
        // Remove from calendar form checkboxes
        var cb = document.querySelector('#ce-cal-event-types input[value="' + chip.dataset.slug + '"]');
        if (cb) fadeRemove(cb.closest('.ce-checkbox-chip'));
      }
    });
  });

  // Save event type
  if (typeForm) {
    typeForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var saveBtn = document.getElementById('ce-type-save-btn');
      saveBtn.disabled = true;
      saveBtn.textContent = i18n.saving || 'Saving…';

      var data = {
        term_id: document.getElementById('ce-type-term-id').value,
        name:    document.getElementById('ce-type-name').value,
        color:   document.getElementById('ce-type-color').value
      };

      post('ce_save_event_type', data).then(function (res) {
        saveBtn.disabled = false;
        saveBtn.textContent = i18n.saved || 'Saved!';

        if (res.success) {
          var d = res.data;
          var chips = document.getElementById('ce-type-chips');
          var empty = document.getElementById('ce-types-empty');

          if (d.action === 'created') {
            chips.hidden = false;
            if (empty) empty.hidden = true;
            chips.insertAdjacentHTML('beforeend', buildTypeChipHtml(d));
            addCalFormCheckbox(d);
          } else {
            var existing = chips.querySelector('[data-term-id="' + d.term_id + '"]');
            if (existing) {
              existing.dataset.name = d.name;
              existing.dataset.slug = d.slug;
              existing.dataset.color = d.color;
              existing.querySelector('.ce-type-dot').style.background = d.color;
              existing.querySelector('.ce-type-name').textContent = d.name;
            }
            updateCalFormCheckbox(d);
          }

          typeFormWrap.hidden = true;
          resetTypeForm();
        }

        setTimeout(function () { saveBtn.textContent = i18n.save_type || 'Save Type'; }, 1200);
      }).catch(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = i18n.save_type || 'Save Type';
      });
    });
  }

  function resetTypeForm() {
    if (!typeForm) return;
    typeForm.reset();
    document.getElementById('ce-type-term-id').value = '';
    document.getElementById('ce-type-color').value = '#3b82f6';
  }

  function buildTypeChipHtml(d) {
    return '<div class="ce-type-chip" data-term-id="' + d.term_id + '" data-name="' + escHtml(d.name) + '" data-slug="' + escHtml(d.slug) + '" data-color="' + escHtml(d.color) + '">'
      + '<span class="ce-type-dot" style="background:' + escHtml(d.color) + '"></span>'
      + '<span class="ce-type-name">' + escHtml(d.name) + '</span>'
      + '<span class="ce-type-count">0</span>'
      + '<button type="button" class="ce-type-edit" title="Edit"><span class="dashicons dashicons-edit"></span></button>'
      + '<button type="button" class="ce-type-delete" title="Delete"><span class="dashicons dashicons-no-alt"></span></button>'
      + '</div>';
  }

  function addCalFormCheckbox(d) {
    var group = document.getElementById('ce-cal-event-types');
    if (!group) return;
    var hint = document.getElementById('ce-no-types-hint');
    if (hint) hint.remove();
    group.insertAdjacentHTML('beforeend',
      '<label class="ce-checkbox-label ce-checkbox-chip" style="--chip-color:' + escHtml(d.color) + '">'
      + '<input type="checkbox" name="event_types[]" value="' + escHtml(d.slug) + '">'
      + '<span class="ce-type-dot" style="background:' + escHtml(d.color) + '"></span>'
      + escHtml(d.name) + '</label>'
    );
  }

  function updateCalFormCheckbox(d) {
    var group = document.getElementById('ce-cal-event-types');
    if (!group) return;
    var labels = group.querySelectorAll('.ce-checkbox-chip');
    labels.forEach(function (lbl) {
      var cb = lbl.querySelector('input[type="checkbox"]');
      if (cb && (cb.value === d.slug || cb.value === d.term_id.toString())) {
        cb.value = d.slug;
        var dot = lbl.querySelector('.ce-type-dot');
        if (dot) dot.style.background = d.color;
        lbl.style.setProperty('--chip-color', d.color);
        lbl.childNodes[lbl.childNodes.length - 1].textContent = d.name;
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════════════════ *
   *  Calendar form
   * ═══════════════════════════════════════════════════════════════════════ */
  var calForm     = document.getElementById('ce-calendar-form');
  var calFormWrap = document.getElementById('ce-calendar-form-wrap');
  var calFormTitle = document.getElementById('ce-cal-form-title');
  var addCalBtn   = document.getElementById('ce-add-calendar-btn');
  var cancelBtn   = document.getElementById('ce-cal-cancel-btn');

  if (addCalBtn) {
    addCalBtn.addEventListener('click', function () {
      resetCalForm();
      calFormWrap.hidden = false;
      calFormTitle.textContent = i18n.add_calendar || 'Add Calendar';
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

    var saved = (d.eventTypes || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
    var cbs = document.querySelectorAll('#ce-cal-event-types input[type="checkbox"]');
    cbs.forEach(function (cb) { cb.checked = saved.indexOf(cb.value) !== -1; });

    calFormWrap.hidden = false;
    calFormTitle.textContent = i18n.edit_calendar || 'Edit Calendar';
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
      if (res.success && row) fadeRemove(row);
    });
  });

  // Save calendar form
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
      if (res.success && row) fadeRemove(row);
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
