/* Noti Web Clipper — popup logic. */

const $ = (id) => document.getElementById(id);

let currentTab = null;

init().catch((e) => showLoginError(e.message));

async function init() {
  const [tab] = await chrome.tabs.query({ active: true, currentWindow: true });
  currentTab = tab;

  const { token, baseUrl } = await notiSettings();
  if (!token) return showLogin(baseUrl);
  await showSave();
}

/* ── Sign-in ──────────────────────────────────────────────────────── */

function showLogin(baseUrl) {
  $('view-login').hidden = false;
  $('view-save').hidden = true;
  $('base-url').value = baseUrl || NOTI_DEFAULT_BASE;
  $('head-note').textContent = 'not connected';
}

function showLoginError(msg) {
  const el = $('login-error');
  el.textContent = msg;
  el.hidden = false;
}

$('btn-login').addEventListener('click', async () => {
  const btn = $('btn-login');
  btn.disabled = true;
  $('login-error').hidden = true;
  try {
    const baseUrl = $('base-url').value.trim().replace(/\/+$/, '');
    const res = await fetch(baseUrl + '/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email: $('email').value.trim(),
        password: $('password').value,
        device_name: 'Chrome extension',
      }),
    });
    const payload = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(payload.error || 'Could not sign in (HTTP ' + res.status + ')');
    await chrome.storage.sync.set({
      baseUrl,
      token: payload.data.token,
      user: payload.data.user,
    });
    $('view-login').hidden = true;
    await showSave();
  } catch (err) {
    showLoginError(err.message);
  } finally {
    btn.disabled = false;
  }
});

/* ── Save view ────────────────────────────────────────────────────── */

async function showSave() {
  $('view-save').hidden = false;
  $('view-login').hidden = true;
  $('head-note').textContent = 'connected';

  $('tab-title').textContent = currentTab?.title || '(no tab)';
  $('tab-url').textContent = currentTab?.url || '';

  const { user, defaultNotebooks } = await chrome.storage.sync.get(['user', 'defaultNotebooks']);
  $('who').textContent = user ? user.email : '';

  const notebooks = await notiFetch('/notebooks');
  const select = $('notebook');
  select.innerHTML = '';
  for (const nb of notebooks) {
    const opt = document.createElement('option');
    opt.value = nb.id;
    opt.textContent = nb.name;
    select.appendChild(opt);
  }
  const preferred = (defaultNotebooks || {}).bookmark
    || (notebooks.find((n) => n.name === 'Bookmarks') || notebooks[0]).id;
  select.value = String(preferred);

  // Remember the chosen notebook for context-menu quick saves too.
  select.addEventListener('change', async () => {
    const id = Number(select.value);
    await chrome.storage.sync.set({
      defaultNotebooks: { bookmark: id, page: id, selection: id },
    });
  });
}

function commentHtml() {
  const c = $('comment').value.trim();
  return c ? '<p><i>' + escapeHtml(c) + '</i></p>' : '';
}

function status(msg, ok = true) {
  const el = $('status');
  el.textContent = msg;
  el.classList.toggle('bad', !ok);
  el.hidden = false;
}

async function doSave(btn, fn) {
  btn.disabled = true;
  $('status').hidden = true;
  try {
    const note = await fn();
    status('Saved “' + (note.title || 'Untitled') + '” ✓');
  } catch (err) {
    status(err.message, false);
  } finally {
    btn.disabled = false;
  }
}

$('btn-bookmark').addEventListener('click', (e) => doSave(e.target, async () => {
  const body = sourceLinkHtml(currentTab.url, currentTab.title) + commentHtml();
  return createNote(Number($('notebook').value), currentTab.title || currentTab.url, body);
}));

$('btn-page').addEventListener('click', (e) => doSave(e.target, async () => {
  const extracted = await chrome.runtime.sendMessage({ type: 'noti-extract-page', tabId: currentTab.id });
  if (extracted && extracted.error) throw new Error('Cannot read this page (' + extracted.error + ')');
  const body = sourceLinkHtml(currentTab.url, currentTab.title)
    + commentHtml() + textToHtml(extracted.text || '');
  return createNote(Number($('notebook').value), extracted.title || currentTab.title, body);
}));

$('btn-selection').addEventListener('click', (e) => doSave(e.target, async () => {
  const [{ result: selection }] = await chrome.scripting.executeScript({
    target: { tabId: currentTab.id },
    func: () => String(window.getSelection()),
  });
  if (!selection || !selection.trim()) throw new Error('Select some text on the page first.');
  const body = '<blockquote>' + escapeHtml(selection) + '</blockquote>'
    + '<p>— from <a href="' + escapeHtml(currentTab.url) + '">'
    + escapeHtml(currentTab.title || currentTab.url) + '</a></p>' + commentHtml();
  return createNote(Number($('notebook').value), currentTab.title || 'Selection', body);
}));

$('btn-logout').addEventListener('click', async () => {
  try { await notiFetch('/auth/logout', { method: 'POST' }); } catch (_) { /* token may already be dead */ }
  await chrome.storage.sync.remove(['token', 'user']);
  const { baseUrl } = await notiSettings();
  showLogin(baseUrl);
});
