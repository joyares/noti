/* Noti — app.js: editor autosave, toolbar, tags, ⌘K search, menus, mobile nav. */
(function () {
  'use strict';

  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const BASE = (document.querySelector('meta[name="base-url"]')?.content || '/').replace(/\/$/, '');

  const $ = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  function api(path, options = {}) {
    options.headers = Object.assign({
      'X-CSRF-Token': CSRF,
      'X-Requested-With': 'fetch',
      'Accept': 'application/json',
    }, options.headers || {});
    if (options.json !== undefined) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.json);
      delete options.json;
    }
    return fetch(BASE + path, options).then(async (res) => {
      const payload = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(payload.error || ('HTTP ' + res.status));
      return payload.data !== undefined ? payload.data : payload;
    });
  }

  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));

  /* ═══ ⌘K search overlay ═════════════════════════════════════════ */

  const overlay = $('[data-search-overlay]');
  const searchInput = $('[data-search-input]');
  const searchResults = $('[data-search-results]');
  let searchTimer = null;

  function openSearch() {
    if (!overlay) return;
    overlay.hidden = false;
    searchInput.value = '';
    searchResults.innerHTML = '<div class="search-hint">Type to search — use <code>tag:name</code> to filter by tag</div>';
    searchInput.focus();
  }
  function closeSearch() { if (overlay) overlay.hidden = true; }

  $$('[data-open-search]').forEach((el) => el.addEventListener('click', openSearch));
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openSearch(); }
    if (e.key === 'Escape') closeSearch();
  });
  overlay?.addEventListener('click', (e) => { if (e.target === overlay) closeSearch(); });

  searchInput?.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q === '') { searchResults.innerHTML = ''; return; }
    searchTimer = setTimeout(async () => {
      try {
        const rows = await api('/search?q=' + encodeURIComponent(q));
        if (!rows.length) {
          searchResults.innerHTML = '<div class="search-hint">No results</div>';
          return;
        }
        searchResults.innerHTML = rows.map((r) => `
          <a href="${BASE}/notes/${r.id}" class="note-row">
            <div class="note-row-top">
              <span class="note-row-title">${r.title ? escapeHtml(r.title) : '<span class="untitled">Untitled</span>'}</span>
              <span class="note-row-date" style="color:${escapeHtml(r.notebook_color)}">${escapeHtml(r.notebook_name)}</span>
            </div>
            <div class="note-row-snippet">${escapeHtml((r.body_text || '').slice(0, 140))}</div>
          </a>`).join('');
      } catch (err) {
        searchResults.innerHTML = '<div class="search-hint">' + escapeHtml(err.message) + '</div>';
      }
    }, 250);
  });

  /* ═══ Notebook modal ════════════════════════════════════════════ */

  const nbModal = $('[data-nb-modal]');
  $$('[data-open-nb-modal]').forEach((el) => el.addEventListener('click', () => {
    nbModal.hidden = false;
    $('input[name="name"]', nbModal)?.focus();
  }));
  $('[data-close-nb-modal]')?.addEventListener('click', () => { nbModal.hidden = true; });
  nbModal?.addEventListener('click', (e) => { if (e.target === nbModal) nbModal.hidden = true; });

  /* ═══ Tag list filter (tags view) ═══════════════════════════════ */

  $('[data-tag-filter]')?.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    $$('[data-tag-list] .tag-row').forEach((row) => {
      row.style.display = row.dataset.tagName.includes(q) ? '' : 'none';
    });
  });

  /* ═══ Copy / share buttons ══════════════════════════════════════ */

  function flash(el) {
    el.style.color = 'var(--accent)';
    setTimeout(() => { el.style.color = ''; }, 900);
  }

  document.addEventListener('click', (e) => {
    const copyBtn = e.target.closest('[data-copy-note]');
    if (copyBtn) {
      navigator.clipboard.writeText(copyBtn.dataset.bodyText || '').then(() => flash(copyBtn));
      return;
    }
    const shareBtn = e.target.closest('[data-share]');
    if (shareBtn) {
      const editor = shareBtn.closest('[data-editor]');
      const id = shareBtn.dataset.noteId || editor?.dataset.noteId;
      // Public share pages are a later phase — copy the app URL for now.
      navigator.clipboard.writeText(location.origin + BASE + '/notes/' + id).then(() => flash(shareBtn));
    }
  });

  /* ═══ Profile: submit avatar as soon as a file is picked ════════ */

  $('[data-avatar-input]')?.addEventListener('change', function () {
    if (this.files.length) $('[data-avatar-form]').submit();
  });

  /* ═══ Editor ════════════════════════════════════════════════════ */

  const editor = $('[data-editor]');
  if (!editor) return;

  const noteId = editor.dataset.noteId;
  const titleInput = $('[data-editor-title]', editor);
  const bodyEl = $('[data-editor-body]', editor);
  const savedPill = $('[data-saved-pill]', editor);
  const sizeEl = $('[data-note-size]', editor);
  const metaSizeEl = $('[data-meta-size]', editor);
  const crumbTitle = $('[data-crumb-title]', editor);

  let saveTimer = null;
  let pending = false;

  function humanBytes(n) {
    if (n < 1024) return n + ' B';
    if (n < 1048576) return (Math.round(n / 102.4) / 10) + ' KB';
    return (Math.round(n / 104857.6) / 10) + ' MB';
  }

  function markDirty() {
    pending = true;
    savedPill.classList.add('saving');
    savedPill.lastElementChild.textContent = 'Saving…';
    clearTimeout(saveTimer);
    saveTimer = setTimeout(save, 800); // debounce per spec
  }

  async function save() {
    if (!pending) return;
    pending = false;
    try {
      const data = await api('/notes/' + noteId, {
        method: 'PATCH',
        json: { title: titleInput.value, body: bodyEl.innerHTML },
      });
      savedPill.classList.remove('saving');
      savedPill.lastElementChild.textContent = 'Saved';
      const kb = humanBytes(data.size_bytes);
      if (sizeEl) sizeEl.textContent = kb;
      if (metaSizeEl) metaSizeEl.textContent = kb;
      if (crumbTitle) crumbTitle.textContent = titleInput.value || 'Untitled';
    } catch (err) {
      savedPill.lastElementChild.textContent = 'Offline';
      pending = true; // retry on next input
    }
  }

  titleInput.addEventListener('input', markDirty);
  bodyEl.addEventListener('input', markDirty);
  window.addEventListener('beforeunload', () => { if (pending) { clearTimeout(saveTimer); save(); } });

  // Checklist checkboxes: toggle + persist
  bodyEl.addEventListener('click', (e) => {
    if (e.target.matches('input[type="checkbox"]')) {
      if (e.target.checked) e.target.setAttribute('checked', '');
      else e.target.removeAttribute('checked');
      markDirty();
    }
  });

  /* Toolbar */
  const toolbar = $('[data-toolbar]', editor);
  toolbar.addEventListener('mousedown', (e) => e.preventDefault()); // keep selection

  toolbar.addEventListener('click', (e) => {
    const btn = e.target.closest('button');
    if (!btn) return;
    bodyEl.focus();
    if (btn.dataset.cmd) {
      document.execCommand(btn.dataset.cmd, false);
    } else if (btn.dataset.block) {
      const block = document.queryCommandValue('formatBlock').toLowerCase();
      document.execCommand('formatBlock', false, block === btn.dataset.block ? 'p' : btn.dataset.block);
    } else if (btn.hasAttribute('data-todo')) {
      document.execCommand('insertHTML', false, '<p><input type="checkbox"> </p>');
    } else if (btn.hasAttribute('data-code')) {
      const sel = window.getSelection();
      const text = sel && sel.rangeCount ? sel.toString() : '';
      document.execCommand('insertHTML', false, '<code>' + (escapeHtml(text) || '&#8203;') + '</code>');
    } else if (btn.hasAttribute('data-link')) {
      const href = prompt('Link URL (https://…)');
      if (href && /^https?:\/\//i.test(href)) document.execCommand('createLink', false, href);
    } else if (btn.hasAttribute('data-toolbar-collapse')) {
      toolbar.classList.toggle('collapsed');
      return;
    }
    markDirty();
  });

  /* ··· menu */
  const menu = $('[data-menu]', editor);
  $('[data-menu-toggle]', editor)?.addEventListener('click', (e) => {
    e.stopPropagation();
    menu.hidden = !menu.hidden;
  });
  document.addEventListener('click', (e) => {
    if (menu && !menu.hidden && !e.target.closest('.menu-wrap')) menu.hidden = true;
  });

  $('[data-pin-toggle]', editor)?.addEventListener('click', async function () {
    const next = this.dataset.pinned === '1' ? 0 : 1;
    await api('/notes/' + noteId, { method: 'PATCH', json: { is_pinned: next } });
    this.dataset.pinned = String(next);
    this.querySelector('span').textContent = next ? 'Unpin' : 'Pin note';
    menu.hidden = true;
  });

  $('[data-move-notebook]', editor)?.addEventListener('change', async function () {
    await api('/notes/' + noteId, { method: 'PATCH', json: { notebook_id: Number(this.value) } });
    location.href = BASE + '/notes/' + noteId; // reload: breadcrumb, auto-tag, list pane
  });

  $('[data-trash-note]', editor)?.addEventListener('click', async () => {
    if (!confirm('Move this note to Trash?')) return;
    await api('/notes/' + noteId, { method: 'DELETE' });
    location.href = BASE + '/';
  });

  /* Attachments */
  const attachInput = $('[data-attach-input]', editor);
  const attRow = $('[data-attachments]', editor);

  attachInput?.addEventListener('change', async function () {
    if (!this.files.length) return;
    const fd = new FormData();
    fd.append('file', this.files[0]);
    try {
      const att = await api('/notes/' + noteId + '/attachments', { method: 'POST', body: fd });
      attRow.hidden = false;
      const iconName = att.kind === 'image' ? '🖼' : att.kind === 'audio' ? '🎤' : '📎';
      const pill = document.createElement('span');
      pill.className = 'att-pill';
      pill.dataset.attId = att.id;
      pill.innerHTML = iconName + ' <a href="' + BASE + '/files/' + att.id + '" target="_blank" rel="noopener">'
        + escapeHtml(att.file_name) + '</a> <button type="button" class="att-remove" data-att-remove aria-label="Remove">×</button>';
      attRow.appendChild(pill);
    } catch (err) {
      alert(err.message);
    }
    this.value = '';
    menu.hidden = true;
  });

  attRow?.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-att-remove]');
    if (!btn) return;
    const pill = btn.closest('.att-pill');
    if (!confirm('Remove this attachment?')) return;
    await api('/attachments/' + pill.dataset.attId, { method: 'DELETE' });
    pill.remove();
  });

  /* Tag footer */
  const tagWrap = $('[data-note-tags]', editor);
  const addTagInput = $('[data-add-tag]', editor);

  addTagInput?.addEventListener('keydown', async (e) => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const name = addTagInput.value.trim().replace(/^#/, '');
    if (!name) return;
    try {
      const tag = await api('/notes/' + noteId + '/tags', { method: 'POST', json: { name } });
      if (!tagWrap.querySelector('[data-tag-id="' + tag.id + '"]')) {
        const chip = document.createElement('span');
        chip.className = 'tag-chip tag-chip-lg';
        chip.dataset.tagId = tag.id;
        chip.innerHTML = '#' + escapeHtml(tag.name)
          + '<button type="button" class="tag-remove" data-tag-remove aria-label="Remove tag">×</button>';
        tagWrap.appendChild(chip);
      }
      addTagInput.value = '';
    } catch (err) {
      alert(err.message);
    }
  });

  tagWrap?.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-tag-remove]');
    if (!btn) return;
    const chip = btn.closest('[data-tag-id]');
    await api('/notes/' + noteId + '/tags/' + chip.dataset.tagId, { method: 'DELETE' });
    chip.remove();
  });
})();
