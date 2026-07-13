/* Noti Web Clipper — shared API helpers (imported by background.js and popup.js). */

const NOTI_DEFAULT_BASE = 'http://localhost/noti/public/api';

async function notiSettings() {
  const s = await chrome.storage.sync.get({
    baseUrl: NOTI_DEFAULT_BASE,
    token: null,
    defaultNotebooks: {}, // { bookmark: id, page: id, selection: id }
  });
  s.baseUrl = s.baseUrl.replace(/\/+$/, '');
  return s;
}

async function notiFetch(path, options = {}) {
  const { baseUrl, token } = await notiSettings();
  if (!token) throw new Error('Not connected — open the Noti popup and sign in.');
  options.headers = Object.assign({
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }, options.headers || {});
  if (options.json !== undefined) {
    options.body = JSON.stringify(options.json);
    delete options.json;
  }
  const res = await fetch(baseUrl + path, options);
  const payload = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(payload.error || ('HTTP ' + res.status));
  return payload.data;
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
  }[c]));
}

/** Plain text → sanitizer-friendly paragraphs. */
function textToHtml(text, maxChars = 20000) {
  return String(text).slice(0, maxChars).split(/\n{1,}/)
    .map((line) => line.trim()).filter(Boolean)
    .map((line) => '<p>' + escapeHtml(line) + '</p>').join('');
}

function sourceLinkHtml(url, title) {
  return '<p><a href="' + escapeHtml(url) + '">' + escapeHtml(title || url) + '</a></p>';
}

/** Find a notebook id by (configured id | fallback fixed-notebook name). */
async function resolveNotebook(kind, fallbackName) {
  const { defaultNotebooks } = await notiSettings();
  const notebooks = await notiFetch('/notebooks');
  const configured = defaultNotebooks[kind];
  if (configured && notebooks.some((n) => n.id === configured)) return configured;
  const byName = notebooks.find((n) => n.name === fallbackName);
  return byName ? byName.id : notebooks[0].id;
}

async function createNote(notebookId, title, bodyHtml) {
  return notiFetch('/notes', {
    method: 'POST',
    json: { notebook_id: notebookId, title: String(title).slice(0, 255), body: bodyHtml },
  });
}
