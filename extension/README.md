# Noti Web Clipper (Chrome extension)

Save webpages, bookmarks, links and selected text from any browser tab
straight into your Noti notebooks, using the Noti JSON API.

## Features

- **Bookmark this page** — saves title + URL as a note (default: *Bookmarks* notebook)
- **Save full page** — extracts the page's readable text and saves it with the source link (default: *Articles*)
- **Save selection** — right-click any selected text on any tab → saved as a quoted note with the source link
- **Save link** — right-click a link → saved to *Links*
- Works from the toolbar **popup** (with notebook picker + optional comment) and from the **right-click context menu** on every tab
- Fixed-notebook auto-tags apply automatically (e.g. `#bookmarks`), courtesy of the Noti API

## Install (unpacked)

1. Open `chrome://extensions`
2. Enable **Developer mode** (top right)
3. Click **Load unpacked** and pick this `extension/` folder

## Connect

1. Click the Noti icon in the toolbar
2. Enter your Noti API URL — e.g. `http://localhost/noti/public/api`
   (or `http://127.0.0.1:8123/api` for the PHP dev server)
3. Sign in with your Noti email + password — the extension stores an API
   token (`chrome.storage.sync`), never your password

## Notes

- The notebook picked in the popup is remembered and also used for
  context-menu quick saves.
- Success/failure is flashed as a ✓ / ! badge on the toolbar icon for
  context-menu saves.
- `host_permissions: <all_urls>` is required to read page content on the
  tab you're saving and to reach your self-hosted Noti API from the
  extension's service worker.
