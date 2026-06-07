# Headless API — integration contract (Path B)

To run a headless front-end (e.g. the Lovable app) on top of this plugin, point
the front-end's data layer at the plugin's REST API instead of Supabase.

Base: `{site}/wp-json/tcm/v1`

## Public (read) endpoints

### `GET /campaigns`
Optional `?per_page=` (default 50, max 100). Returns an array of campaigns:

```json
[
  {
    "id": 12,
    "slug": "lerefuah-...",
    "title": "לרפואת פלוני בן פלונית",
    "dedicated_to": "לרפואת פלוני בן פלונית",
    "purpose": "תיאור קצר",
    "description": "תיאור מלא",
    "goal_books": 5,
    "goal_chapters": 750,
    "image_url": "https://.../featured.jpg",
    "permalink": "https://.../tehillim/campaign-12/",
    "status": "active",
    "stats": { "books": 2, "chapters": 312, "participants": 47 }
  }
]
```

### `GET /campaigns/{id}`
A single campaign in the same shape (404 when not found/unpublished).

### `GET /campaigns/{id}/status`
Lightweight live progress: `{ percent, completed_books, goal_total, round, round_free }`.

## Action endpoints (already present)

- `POST /campaigns/{id}/join` — `{ mode: "single|multi|book", chapter, count, name, email, phone, turnstile }` → `{ ok, assignment_id, token, count, chapters }`.
- `POST /assignments/{id}/done|take-more|release` — `{ token }`.
- `POST /subscribe` — `{ list, name, email, phone, channel, consent }`.
- `POST /campaigns` (create) and `POST /campaigns/{id}/update|bonus` — require a
  logged-in WordPress user (send the `X-WP-Nonce` header from `wp_create_nonce('wp_rest')`).

## Auth

WordPress handles identity. For the headless app:
- **Same-origin / cookie**: log in to WordPress, then send the REST nonce
  (`X-WP-Nonce`) on write calls.
- **Cross-origin**: use an auth plugin (JWT / Application Passwords) and send a
  bearer token; map the WordPress user to the app's "owner" concept.

## Still to add for full parity (next milestones)
- Ambassadors list/leaderboard + apply/approve endpoints.
- Activity feed endpoint (recent takes/completions, no PII).
- Campaign image + a dedicated `dedicated_to` field separate from the title.
