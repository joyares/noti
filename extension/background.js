/* Noti Web Clipper — service worker: context menus + quick saves. */

importScripts('api.js');

const MENUS = [
  { id: 'noti-save-selection', title: 'Save selection to Noti', contexts: ['selection'] },
  { id: 'noti-save-bookmark', title: 'Bookmark this page in Noti', contexts: ['page', 'frame'] },
  { id: 'noti-save-page', title: 'Save full page to Noti', contexts: ['page', 'frame'] },
  { id: 'noti-save-link', title: 'Save link to Noti', contexts: ['link'] },
];

chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.removeAll(() => {
    MENUS.forEach((m) => chrome.contextMenus.create(m));
  });
});

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  try {
    switch (info.menuItemId) {
      case 'noti-save-selection': await saveSelection(info, tab); break;
      case 'noti-save-bookmark':  await saveBookmark(tab); break;
      case 'noti-save-page':      await savePage(tab); break;
      case 'noti-save-link':      await saveLink(info, tab); break;
    }
    flashBadge(true, tab?.id);
  } catch (err) {
    console.error('Noti save failed:', err);
    flashBadge(false, tab?.id);
  }
});

/* ── Save actions ─────────────────────────────────────────────────── */

async function saveSelection(info, tab) {
  const notebookId = await resolveNotebook('selection', 'Bookmarks');
  const body =
    '<blockquote>' + escapeHtml(info.selectionText || '') + '</blockquote>' +
    '<p>— from <a href="' + escapeHtml(tab.url) + '">' + escapeHtml(tab.title || tab.url) + '</a></p>';
  await createNote(notebookId, tab.title || 'Selection', body);
}

async function saveBookmark(tab) {
  const notebookId = await resolveNotebook('bookmark', 'Bookmarks');
  const body = sourceLinkHtml(tab.url, tab.title);
  await createNote(notebookId, tab.title || tab.url, body);
}

async function savePage(tab) {
  const notebookId = await resolveNotebook('page', 'Articles');
  const extracted = await extractPage(tab.id);
  const body = sourceLinkHtml(tab.url, tab.title) + textToHtml(extracted.text);
  await createNote(notebookId, extracted.title || tab.title || tab.url, body);
}

async function saveLink(info, tab) {
  const notebookId = await resolveNotebook('bookmark', 'Links');
  const body = sourceLinkHtml(info.linkUrl, info.linkUrl) +
    '<p>Found on ' + escapeHtml(tab.title || tab.url) + '</p>';
  await createNote(notebookId, info.linkUrl, body);
}

/* ── Page text extraction (runs in the tab) ───────────────────────── */

async function extractPage(tabId) {
  const [{ result }] = await chrome.scripting.executeScript({
    target: { tabId },
    func: () => {
      const root = document.querySelector('article') ||
        document.querySelector('main') || document.body;
      return { title: document.title, text: root ? root.innerText : '' };
    },
  });
  return result || { title: '', text: '' };
}

/* ── Feedback: badge flash on the toolbar icon ────────────────────── */

function flashBadge(ok, tabId) {
  const opts = tabId ? { tabId } : {};
  chrome.action.setBadgeBackgroundColor({ ...opts, color: ok ? '#57c785' : '#d1857a' });
  chrome.action.setBadgeText({ ...opts, text: ok ? '✓' : '!' });
  setTimeout(() => chrome.action.setBadgeText({ ...opts, text: '' }), 2500);
}

/* ── Popup asks us to extract the page for "Save full page" ───────── */

chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (msg && msg.type === 'noti-extract-page') {
    extractPage(msg.tabId).then(sendResponse)
      .catch((e) => sendResponse({ title: '', text: '', error: String(e) }));
    return true; // async response
  }
});
