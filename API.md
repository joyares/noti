# Noti — JSON API Reference

Backend spec for the future mobile app. The API is a thin JSON layer over the
same services the web app uses, mounted under `/api/*`.

- **Base URL**: `https://<host>/api` (dev: `http://noti.test/api` or `http://127.0.0.1:8123/api`)
- **Auth**: `Authorization: Bearer <token>` on every endpoint except
  `POST /auth/register` and `POST /auth/login`.
- **Bodies**: send JSON (`Content-Type: application/json`) except file uploads
  (multipart).
- **Envelope**: every response is

```json
{ "data": <payload or null>, "error": <string or null> }
```

- **Pagination**: list endpoints accept `?page=` (default 1) and `?per_page=`
  (default 30, max 100). Paginated payloads echo `page` and `per_page`.
- **Status codes**: `200` OK · `201` created · `401` bad/missing token ·
  `403` wrong password · `404` not found / not yours · `409` conflict ·
  `422` validation error · `423` locked notebook · `500` server error.

---

## Auth

### POST /api/auth/register
Creates the account, seeds the 18 fixed notebooks + their tags, returns a token.

Request:
```json
{ "email": "a@b.com", "display_name": "Ada", "password": "min 8 chars", "device_name": "iPhone 17" }
```
Response `201`:
```json
{ "data": { "token": "<64-hex>", "user": { "id": 1, "email": "a@b.com", "display_name": "Ada" } }, "error": null }
```
The token is shown **once**; store it securely. Errors: `422` invalid fields, `409` email taken.

### POST /api/auth/login
```json
{ "email": "a@b.com", "password": "...", "device_name": "iPhone 17" }
```
Response `200`: same shape as register. `401` on wrong credentials.
Each login issues a new token (one per device); tokens do not expire.

### POST /api/auth/logout
Revokes the presented bearer token. → `{ "data": true }`

### GET /api/me
→ `{ "data": { "id", "email", "display_name", "created_at" } }`

---

## Notebooks

Notebook object:
```json
{ "id": 15, "user_id": 1, "name": "Shopping", "color": "#a3b45f", "icon": "bag",
  "is_fixed": 1, "is_locked": 0, "sort_order": 14, "created_at": "...",
  "note_count": 3, "last_note_at": "2026-07-12 04:48:46" }
```
`icon` is a key; the client renders its own SVG. Fixed notebooks (`is_fixed=1`)
cannot be renamed or deleted — only `color` is editable. The Passwords notebook
has `is_locked=1`.

### GET /api/notebooks
All notebooks with live note counts, fixed first. → `{ "data": [Notebook, …] }`

### POST /api/notebooks
`{ "name": "Recipes", "color": "#5cb3c9" }` → `201` Notebook.
`422` bad name, `409` duplicate name.

### PATCH /api/notebooks/{id}
`{ "color": "#..." }` (any notebook) and/or `{ "name": "..." }` (user notebooks only).
→ updated Notebook. `422` on rename/delete of a fixed notebook.

### DELETE /api/notebooks/{id}
User notebooks only; deletes the notebook **and its notes** permanently. → `{ "data": true }`

### GET /api/notebooks/{id}/notes
Paginated. Optional `?filter=all|pinned|recent|open|done` (`open`/`done` are for
checklist notes, used by the TODO notebook).
→ `{ "data": { "notes": [NoteListItem, …], "page": 1, "per_page": 30 } }`

**Locked notebooks** answer `423` until you unlock (below) and send the token:
`X-Unlock-Token: <token>`.

### POST /api/notebooks/{id}/unlock
Re-verify the **account password** to open a 10-minute unlock window:
```json
{ "password": "account password" }
```
→ `{ "data": { "unlock_token": "<opaque>", "expires_in": 600 } }` · `403` wrong password.
Send the token as `X-Unlock-Token` on any request touching that notebook's notes.

---

## Notes

NoteListItem (list endpoints):
```json
{ "id": 1, "notebook_id": 15, "title": "Grocery run", "body_text": "plain text…",
  "size_bytes": 178, "is_pinned": 0, "is_trashed": 0,
  "created_at": "...", "updated_at": "...",
  "notebook_name": "Shopping", "notebook_color": "#a3b45f",
  "notebook_icon": "bag", "notebook_locked": 0,
  "tags": [ { "id": 15, "name": "shopping" } ] }
```
Full Note (single-note endpoints) adds `body` (sanitized HTML) and
`attachments` (array of Attachment objects).

Allowed HTML in `body`: `h1 h2 p b i u ul ol li input[type=checkbox] code pre a
blockquote br`. Anything else is stripped server-side; `body_text` and
`size_bytes` are recomputed on every write.

### GET /api/notes
All non-trashed notes, pinned first, newest first. Paginated; `?filter=` as above.

### POST /api/notes
```json
{ "notebook_id": 15, "title": "optional", "body": "<p>optional HTML</p>" }
```
→ `201` full Note. Creating in a **fixed** notebook auto-attaches its tag
(e.g. `#shopping`) — the tag is removable afterwards. `423` if the notebook is
locked and no valid `X-Unlock-Token` is sent.

### GET /api/notes/{id}
→ full Note. `423` when its notebook is locked.

### PATCH /api/notes/{id}
Partial update — send any of:
```json
{ "title": "...", "body": "<p>…</p>", "is_pinned": 1, "notebook_id": 7 }
```
→ `{ "data": { "saved_at": "2026-07-12 04:49:26", "size_bytes": 178 } }`
This is the autosave endpoint (client should debounce ~800 ms). Moving a note
into a fixed notebook auto-attaches that notebook's tag.

### DELETE /api/notes/{id}
Soft delete (moves to Trash). → `{ "data": true }`

### POST /api/notes/{id}/restore
Restores from Trash. → `{ "data": true }`

### GET /api/trash
Trashed notes, newest first. Notes older than 30 days in Trash are purged.

### Tags on a note

- `POST /api/notes/{id}/tags` — `{ "name": "Weekend Plans" }`. Names are
  slugged (`weekend-plans`), created if new, attached.
  → `201` `{ "data": { "id": 21, "name": "weekend-plans" } }`
- `DELETE /api/notes/{id}/tags/{tagId}` — detaches. → `{ "data": true }`

### POST /api/notes/{id}/attachments
Multipart with field **`file`**. Max 20 MB; executable/script types rejected.
`kind` is derived from MIME: `image`, `audio`, or `file`.
→ `201` Attachment:
```json
{ "id": 3, "note_id": 1, "kind": "image", "file_path": "storage/uploads/1/ab….jpg",
  "file_name": "photo.jpg", "file_size": 82344, "mime": "image/jpeg", "created_at": "..." }
```
Download an attachment via the web route `GET /files/{id}` (same auth + lock rules).

---

## Tags

### GET /api/tags
All tags with counts of non-trashed notes:
`{ "data": [ { "id": 15, "name": "shopping", "note_count": 3 }, … ] }`

### GET /api/tags/{name}/notes
Paginated notes carrying the tag; each NoteListItem also includes `attachments`.
→ `{ "data": { "tag": {…}, "notes": […], "page": 1, "per_page": 30 } }`

---

## Search

### GET /api/search?q=
MySQL FULLTEXT over `title` + `body_text` (prefix matching per word), plus
`tag:` filters — `q=tag:shopping milk` finds notes tagged `#shopping` matching
"milk". Paginated. `422` when `q` is empty.
→ `{ "data": { "results": [NoteListItem-without-tags, …], "page": 1, "per_page": 30 } }`

---

## Conventions recap for the mobile client

1. Store the bearer token from login/register; send it on every call.
2. Debounce editor writes ~800 ms against `PATCH /api/notes/{id}`; update the
   "Saved" indicator from `saved_at` / `size_bytes` in the response.
3. On `423`, prompt for the account password, call
   `POST /api/notebooks/{id}/unlock`, cache the `unlock_token` for
   `expires_in` seconds, retry with `X-Unlock-Token`.
4. Fixed-notebook auto-tags arrive in the note's `tags` array — no client work.
5. Render notebook icons client-side from the `icon` key
   (`bookmark key link at terminal pencil checkbox bulb info star person plane
   box sun bag shirt document pen book`).
