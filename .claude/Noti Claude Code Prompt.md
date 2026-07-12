\# Noti — Development Prompt for Claude Code



> Paste this entire document into Claude Code as the project brief. It contains the full product spec, visual design system, database schema, PHP MVC architecture, and build order for the Noti note-taking application.



\---



\## 1. What you are building



\*\*Noti\*\* — a tag-based note-taking web application.



\- Stack: \*\*PHP 8.2+ (custom MVC, no framework)\*\* + \*\*MySQL 8\*\* + vanilla JS (or Alpine.js) + no CSS framework (hand-written CSS with the tokens below).

\- \*\*Mobile-first responsive\*\*: the same web app must render the dedicated mobile layouts below on small screens (< 768px). A native mobile app will be built later, so \*\*all data access goes through a JSON API layer\*\* (`/api/\*`) that the future app can reuse — the web views consume the same controllers/services.

\- Auth: email + password (bcrypt), session-based for web, token-based (`Authorization: Bearer`) for the API.



\---



\## 2. Core concepts \& rules



1\. \*\*Notebook → notes collection.\*\* A notebook has a name, a color (colored title everywhere), an icon, and many notes.

2\. \*\*18 fixed notebooks\*\* are seeded for every new user and cannot be deleted or renamed (color IS editable):

&#x20;  Bookmarks, Passwords, Links, Socials, Prompts, Designs, TODO, Ideas, Tips, Tricks, Names, Travels, Necessaries, Daily Needs, Shopping, Dress, Articles, Sketch.

3\. Each fixed notebook \*\*doubles as a tag\*\*: saving a note into `Shopping` auto-applies `#shopping` (removable per note). Users can also create their own notebooks and their own tags freely.

4\. \*\*Tags are many-to-many\*\* with notes and are the primary cross-cutting retrieval mechanism.

5\. Notes: title, rich-ish body (headings, bold/italic/underline, bullet list, checklist, inline code, links), attachments (files/photos/audio), pinned flag, soft delete (Trash), byte size shown in the editor.

6\. \*\*Passwords notebook\*\* is special: its notes list is blurred/locked until the user re-enters their password (server-side check, 10-min unlock window in session).



\---



\## 3. Visual design system (match exactly)



Dark, focused, compact. Green accent.



\### Tokens

\- Fonts: `IBM Plex Sans` (UI), `IBM Plex Mono` (counts, dates, tags, kbd). Google Fonts.

\- Background app shell: `#0c0e0d` · panels: `#101312` · list pane: `#121514` · cards: `#151918` · card hover: `#1a1f1d`

\- Text: `#e8ece9` primary · `rgba(255,255,255,.75)` secondary · `.48` snippets · `.35` metadata

\- Accent green: `#57c785` (buttons, active nav, links, selection tint `rgba(87,199,133,.08–.12)`)

\- Borders: `rgba(255,255,255,.07–.09)` · radius: 5–10px · compact paddings (6–14px)

\- Notebook colors (fixed set): Bookmarks `#5cb3c9`, Passwords `#d1857a`, Links `#6ea3d8`, Socials `#a487d6`, Prompts `#57c785`, Designs `#c97fae`, TODO `#c9a05f`, Ideas `#bfae5c`, Tips `#5fbcb0`, Tricks `#8a94dd`, Names `#b57fc9`, Travels `#cf9268`, Necessaries `#8ab873`, Daily Needs `#d0808e`, Shopping `#a3b45f`, Dress `#c98f7f`, Articles `#7fa8c9`, Sketch `#9c8fd0`

\- Each fixed notebook has a matching \*\*stroke SVG line icon\*\* (bookmark, key, chain-link, @, terminal `>\_`, pencil, checkbox, bulb, info, star, person, paper plane, box, sun, shopping bag, shirt, document, pen line). User notebooks get a generic book icon.

\- Tag chips: `#name` in Plex Mono 10px, bg `rgba(255,255,255,.07)`, radius 4px. Active/primary tag chip uses the notebook color as bg with dark text.



\### Desktop layout (≥ 1024px) — 3-pane

1\. \*\*Sidebar 236px\*\* (`#0c0e0d`): logo "N" tile + "Noti", ⌘K hint, search field, green "+ New note" button, nav (Home, All notes, Tags, Trash), `NOTEBOOKS` section listing all notebooks with icon + count, user footer with sync status.

2\. \*\*List pane \~312px\*\* (`#121514`): notebook header (icon, colored name, note count, sort), note rows — bold title + date right-aligned, 2-line snippet, tag chip row. Selected row: green tint bg + 2px green left edge.

3\. \*\*Editor pane\*\*: breadcrumb (notebook / title) + size in KB + green "Saved" pill + Share + `···`; slim toolbar (B I U | H1 H2 | • List, ☐ Todo, `</>`, Link); large title; meta line; body (max-width 760px); footer bar with tag chips + "+ Add tag".



\### Other desktop screens

\- \*\*Notebooks overview\*\* (`/notebooks`): grid of cards (6 cols), each card = colored top border (3px), icon + colored title, count + last-updated. Sections: FIXED NOTEBOOKS, YOUR NOTEBOOKS, dashed "+ New notebook" card.

\- \*\*Tags view\*\* (`/tags`): middle pane = filterable tag list with counts; right pane = header `#tagname` (mono, green) + result cards in a 3-col grid. Each card: notebook dot + name (top-left), icon-only Copy \& Share buttons (top-right), title, 3-line snippet, attachment pills row (dashed border, icon + label like `spec.pdf`, `3 photos`, `voice 0:42`), tag chips row with date at right end.



\### Mobile layout (< 768px) — matches the mobile app design

\- \*\*Home\*\*: Noti header + avatar, search field, `NOTEBOOKS` label + "+ New", 2-column notebook card grid, bottom tab bar (Home, Notebooks, center green + FAB raised, Tags, Settings). Tab bar respects safe-area.

\- \*\*Notebook list screen\*\*: back chevron + notebook icon + colored title + count + `···`, filter chips (All/Open/Done for TODO; generic All/Pinned/Recent elsewhere), note rows as on desktop, floating green + FAB bottom-right.

\- \*\*Editor\*\*: back chevron + notebook chip + Saved pill + `···`, title, meta (edited date + KB), body, tag chips inline, and a formatting toolbar docked at the bottom (above the keyboard when open): B I H1 • ☐ `</>` and a collapse chevron. Touch targets ≥ 44px.



\---



\## 4. MySQL schema



```sql

CREATE TABLE users (

&#x20; id BIGINT UNSIGNED AUTO\_INCREMENT PRIMARY KEY,

&#x20; email VARCHAR(255) NOT NULL UNIQUE,

&#x20; display\_name VARCHAR(100) NOT NULL,

&#x20; password\_hash VARCHAR(255) NOT NULL,

&#x20; created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP

);



CREATE TABLE notebooks (

&#x20; id BIGINT UNSIGNED AUTO\_INCREMENT PRIMARY KEY,

&#x20; user\_id BIGINT UNSIGNED NOT NULL,

&#x20; name VARCHAR(100) NOT NULL,

&#x20; color CHAR(7) NOT NULL DEFAULT '#57c785',

&#x20; icon VARCHAR(40) NOT NULL DEFAULT 'book',   -- icon key, SVG lives in code

&#x20; is\_fixed TINYINT(1) NOT NULL DEFAULT 0,     -- fixed notebooks: no delete/rename

&#x20; is\_locked TINYINT(1) NOT NULL DEFAULT 0,    -- Passwords notebook = 1

&#x20; sort\_order INT NOT NULL DEFAULT 0,

&#x20; created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,

&#x20; FOREIGN KEY (user\_id) REFERENCES users(id) ON DELETE CASCADE,

&#x20; UNIQUE KEY uq\_user\_name (user\_id, name)

);



CREATE TABLE notes (

&#x20; id BIGINT UNSIGNED AUTO\_INCREMENT PRIMARY KEY,

&#x20; user\_id BIGINT UNSIGNED NOT NULL,

&#x20; notebook\_id BIGINT UNSIGNED NOT NULL,

&#x20; title VARCHAR(255) NOT NULL DEFAULT '',

&#x20; body MEDIUMTEXT NOT NULL,                   -- sanitized HTML

&#x20; body\_text MEDIUMTEXT NOT NULL,              -- plain text copy for search

&#x20; size\_bytes INT UNSIGNED NOT NULL DEFAULT 0,

&#x20; is\_pinned TINYINT(1) NOT NULL DEFAULT 0,

&#x20; is\_trashed TINYINT(1) NOT NULL DEFAULT 0,

&#x20; trashed\_at TIMESTAMP NULL,

&#x20; created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,

&#x20; updated\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP ON UPDATE CURRENT\_TIMESTAMP,

&#x20; FOREIGN KEY (user\_id) REFERENCES users(id) ON DELETE CASCADE,

&#x20; FOREIGN KEY (notebook\_id) REFERENCES notebooks(id),

&#x20; FULLTEXT KEY ft\_notes (title, body\_text)

);



CREATE TABLE tags (

&#x20; id BIGINT UNSIGNED AUTO\_INCREMENT PRIMARY KEY,

&#x20; user\_id BIGINT UNSIGNED NOT NULL,

&#x20; name VARCHAR(60) NOT NULL,                  -- lowercase, slug-like

&#x20; FOREIGN KEY (user\_id) REFERENCES users(id) ON DELETE CASCADE,

&#x20; UNIQUE KEY uq\_user\_tag (user\_id, name)

);



CREATE TABLE note\_tags (

&#x20; note\_id BIGINT UNSIGNED NOT NULL,

&#x20; tag\_id BIGINT UNSIGNED NOT NULL,

&#x20; PRIMARY KEY (note\_id, tag\_id),

&#x20; FOREIGN KEY (note\_id) REFERENCES notes(id) ON DELETE CASCADE,

&#x20; FOREIGN KEY (tag\_id) REFERENCES tags(id) ON DELETE CASCADE

);



CREATE TABLE attachments (

&#x20; id BIGINT UNSIGNED AUTO\_INCREMENT PRIMARY KEY,

&#x20; note\_id BIGINT UNSIGNED NOT NULL,

&#x20; kind ENUM('image','file','audio') NOT NULL,

&#x20; file\_path VARCHAR(500) NOT NULL,            -- storage/uploads/...

&#x20; file\_name VARCHAR(255) NOT NULL,

&#x20; file\_size INT UNSIGNED NOT NULL,

&#x20; mime VARCHAR(100) NOT NULL,

&#x20; created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,

&#x20; FOREIGN KEY (note\_id) REFERENCES notes(id) ON DELETE CASCADE

);



CREATE TABLE api\_tokens (

&#x20; id BIGINT UNSIGNED AUTO\_INCREMENT PRIMARY KEY,

&#x20; user\_id BIGINT UNSIGNED NOT NULL,

&#x20; token\_hash CHAR(64) NOT NULL,               -- sha256

&#x20; device\_name VARCHAR(100),

&#x20; last\_used\_at TIMESTAMP NULL,

&#x20; created\_at TIMESTAMP DEFAULT CURRENT\_TIMESTAMP,

&#x20; FOREIGN KEY (user\_id) REFERENCES users(id) ON DELETE CASCADE

);

```



Seeder: on user registration insert the 18 fixed notebooks (correct colors/icons from §3, `is\_fixed=1`, Passwords gets `is\_locked=1`) and their 18 matching tags (`daily-needs` slug for Daily Needs).



\---



\## 5. PHP MVC structure



```

noti/

├─ public/

│  ├─ index.php            # front controller, all requests

│  ├─ assets/css/app.css   # design tokens + components

│  ├─ assets/js/app.js     # editor, tag input, search overlay, mobile nav

│  └─ uploads/ -> ../storage/uploads (or served via controller)

├─ app/

│  ├─ Core/                # Router, Controller, Model(PDO), View, Auth, Request, Response, Csrf

│  ├─ Controllers/         # HomeController, NoteController, NotebookController,

│  │                       # TagController, SearchController, AuthController, TrashController

│  ├─ Controllers/Api/     # ApiNoteController, ApiNotebookController, ApiTagController,

│  │                       # ApiAuthController  (JSON only — used by future mobile app)

│  ├─ Models/              # User, Notebook, Note, Tag, Attachment

│  ├─ Services/            # NoteService (sanitize HTML, compute body\_text + size\_bytes,

│  │                       #   auto-tag on notebook move), SearchService, UploadService,

│  │                       #   LockService (Passwords unlock window)

│  └─ Views/

│     ├─ layouts/app.php   # shell: sidebar (desktop) / tab bar (mobile)

│     ├─ notes/            # index (3-pane), editor partial, row partial

│     ├─ notebooks/        # grid

│     ├─ tags/             # tag browser

│     └─ auth/             # login, register

├─ config/config.php       # DB creds, base URL

├─ storage/uploads/

└─ migrations/\*.sql

```



\### Routes

Web: `GET /` home · `GET /notes` · `GET /notebooks` · `GET /notebooks/{id}` · `POST /notebooks` · `PATCH /notebooks/{id}` (color) · `GET /tags` · `GET /tags/{name}` · `GET/POST/PATCH/DELETE /notes/{id}` · `POST /notes/{id}/tags` · `POST /notebooks/{id}/unlock` (Passwords) · `GET /trash`, `POST /notes/{id}/restore` · auth routes.



API (same services, JSON): `POST /api/auth/login` → token · `GET /api/notebooks` · `GET /api/notebooks/{id}/notes` · CRUD `/api/notes` · `GET /api/tags`, `GET /api/tags/{name}/notes` · `GET /api/search?q=` · `POST /api/notes/{id}/attachments` (multipart). Every API response: `{ "data": ..., "error": null }`. Paginate lists (`?page=\&per\_page=30`).



\### Key behaviors

\- \*\*Autosave\*\*: editor PATCHes `{title, body}` debounced 800ms; server sanitizes HTML (allow: h1,h2,p,b,i,u,ul,ol,li,input\[type=checkbox],code,pre,a,blockquote), recomputes `body\_text` (strip tags) and `size\_bytes = strlen(body)`; returns `{saved\_at, size\_bytes}` → update the "Saved" pill and KB display.

\- \*\*Auto-tag\*\*: moving/creating a note in a fixed notebook attaches that notebook's tag; user can detach it.

\- \*\*Search\*\*: MySQL FULLTEXT over title+body\_text, plus `tag:` prefix filter. ⌘K overlay on desktop; search screen on mobile.

\- \*\*Copy button\*\* on tag-view cards: copies note body as plain text (`navigator.clipboard`). \*\*Share\*\*: copies a share URL (public share can be a later phase — stub the endpoint).

\- \*\*Trash\*\*: soft delete; purge after 30 days (cron or lazy purge on load).

\- \*\*Passwords lock\*\*: `LockService` — list/detail of `is\_locked` notebooks return 423 until `POST unlock` with the account password; unlock stored in session with 10-min expiry (API: short-lived unlock token).



\### Responsive rules

\- One codebase, same views. `app.css` breakpoints: ≥1024px = 3-pane grid (`236px 312px 1fr`); 768–1023px = 2-pane (list+editor, sidebar collapses to icon rail); <768px = single pane with the mobile tab bar, screens navigate as separate pages (`/m`-style stacked nav not needed — normal links + back chevron). FAB and bottom toolbar as in §3. Use `env(safe-area-inset-bottom)`.



\---



\## 6. Build order (do in this sequence)



1\. Core MVC (router, PDO model, view, auth, CSRF) + migrations + seeder.

2\. Auth screens (dark theme from day one).

3\. Notebooks: model, seeding, overview grid page, create modal (name + 18-color swatch picker + icon auto).

4\. Notes: 3-pane desktop view, note rows, editor with autosave + toolbar + tag footer.

5\. Tags: browser page + tag results cards (copy/share, attachment pills).

6\. Search (FULLTEXT + ⌘K overlay).

7\. Attachments (upload, pills, image thumbs).

8\. Trash, pin, Passwords lock.

9\. Responsive/mobile pass — match §3 mobile layouts exactly.

10\. `/api/\*` layer + token auth (thin wrappers over existing services) + `API.md` documenting every endpoint for the future mobile app.



Quality bar: PSR-12, prepared statements everywhere, CSRF on all web POSTs, HTML sanitization server-side, all colors/spacing from §3 (no CSS frameworks, no Inter/Roboto — IBM Plex only).



