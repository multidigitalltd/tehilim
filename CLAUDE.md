# Tehilim v2 — Developer Reference

This document provides technical context for developers working on the Tehilim theme.

## Architecture Overview

Tehilim is a **classic WordPress theme** (not block-based) built from a hi-fi design handoff. It consolidates legacy plugin + theme functionality into ~1000 lines of PHP + 900 lines of CSS.

### Design Philosophy

- **Simple**: Direct PHP templates, no custom frameworks
- **Secure**: Input validation, sanitization, escaping throughout
- **Accessible**: WCAG AA+ contrast, keyboard nav, RTL-aware
- **Responsive**: Mobile-first, tested at 4 breakpoints
- **Performant**: No heavy dependencies, minimal JavaScript

## File Structure

```
tehilim-theme/
├── style.css                 # All styles (no separate CSS files)
├── functions.php             # Setup, enqueue, include files
├── header.php                # Global header template
├── footer.php                # Global footer template
├── front-page.php            # Homepage
├── archive-campaign.php      # Campaign listing
├── single-campaign.php       # Campaign detail
├── template-ambassador.php   # Referral link
├── template-dashboard.php    # Private ambassador dashboard
├── page-create.php           # Campaign creation form
├── 404.php                   # 404 error
├── index.php                 # Fallback template
│
├── inc/
│   ├── cpt.php              # Post types, taxonomy, rewrite rules, table creation
│   ├── meta.php             # Meta field registration, helper functions
│   └── rest.php             # REST endpoints, validation, rate-limiting
│
├── assets/
│   ├── css/
│   │   ├── reset.css        # Cross-browser normalize (inlined into style.css)
│   │   └── (theme.css)      # Optional separate file (not used; inline on start)
│   ├── js/
│   │   ├── app.js           # Reader, chapter navigation, stats polling, sharing
│   │   └── form.js          # Campaign creation, ambassador join forms
│   ├── fonts/
│   │   ├── asimon-regular.otf
│   │   ├── asimon-medium.otf
│   │   └── asimon-bold.otf
│   └── images/
│       └── (SVG icons)
│
└── templates/
    └── parts/
        └── campaign-card.php # Reusable campaign card component
```

## Data Model

### Custom Post Types

#### `campaign`
Represents a campaign to say Tehilim for a specific cause.

**Supports**: title, editor, thumbnail, custom-fields
**Meta Fields**:
- `goal_books` (int): Target number of complete books (150 chapters each)
- `organizer_name` (string): Name of campaign creator
- `organizer_email` (string): Contact email

**Taxonomy**: `occasion` (Refua, Iluy, Zivug, Parnasa, Zchut, Event)

**REST**: Available at `/wp-json/wp/v2/campaign`

#### `ambassador`
Represents an ambassador (referrer) for a campaign.

**Supports**: title, custom-fields
**Meta Fields**:
- `campaign_id` (int): Parent campaign ID
- `email` (string): Ambassador email
- `avatar_color` (string): Hex color for avatar display

**REST**: Available at `/wp-json/wp/v2/ambassador`

### Custom Table: `wp_tehilim_recitations`

Atomic recording of each chapter recitation.

```sql
CREATE TABLE {$wpdb->prefix}tehilim_recitations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id BIGINT UNSIGNED NOT NULL,
  ambassador_id BIGINT UNSIGNED,                    -- NULL if public recitation
  chapter_number INT UNSIGNED NOT NULL,              -- 1–150
  reciter_name VARCHAR(255),                         -- optional; display name
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE,
  INDEX (campaign_id),
  INDEX (ambassador_id)
);
```

**Why a custom table?**
- Atomic recitations (not post meta clutter)
- Indexed for fast leaderboard queries
- Timestamps for activity feeds
- No need for full CPT overhead

## REST API Endpoints

All endpoints use `/wp-json/tehilim/v1/` base path.

### `POST /recitations`

Record a chapter recitation.

**Request Body**:
```json
{
  "campaign_id": 123,
  "chapter_number": 47,
  "ambassador_id": 456,     // optional
  "reciter_name": "Sarah",  // optional
  "cf_turnstile_response": "token_..."  // optional (required if Turnstile enabled)
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "chapter_number": 48  // next chapter
}
```

**Errors**:
- 400: Invalid params (chapter out of range, missing campaign)
- 403: Turnstile verification failed
- 404: Campaign not found
- 429: Rate limited (> 10/hour per IP)
- 500: Database error

**Rate Limit**: 10 per hour per IP (unauthenticated); unlimited (authenticated)

### `GET /campaigns/{id}/stats`

Get campaign progress statistics.

**Response** (200 OK):
```json
{
  "books_done": 2,
  "chapters_done": 45,
  "participants": 18,
  "ambassadors": 3
}
```

**Errors**:
- 404: Campaign not found

### `POST /ambassadors/join`

Create a new ambassador for a campaign.

**Request Body**:
```json
{
  "campaign_id": 123,
  "name": "David",
  "email": "david@example.com"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "ambassador_id": 456,
  "personal_url": "https://site.com/c/campaign-slug/ambassador-slug"
}
```

**Errors**:
- 400: Invalid input (missing fields, bad email format, name too short/long)
- 404: Campaign not found
- 429: Rate limited (> 1/hour per IP)
- 500: Creation failed

**Rate Limit**: 1 per hour per IP (unauthenticated); unlimited (authenticated)

## Helper Functions

### In `inc/meta.php`

#### `tehilim_get_campaign_progress( $campaign_id )`
Returns progress stats for a campaign.

```php
$progress = tehilim_get_campaign_progress( $post->ID );
// {
//   'books_done' => 2,
//   'chapters_done' => 45,
//   'total_chapters' => 345,
//   'goal_books' => 5,
//   'progress_percent' => 46
// }
```

#### `tehilim_get_top_ambassadors( $campaign_id, $limit = 3 )`
Returns top ambassadors by chapter count.

```php
$ambassadors = tehilim_get_top_ambassadors( $post->ID );
// [
//   ['id' => 1, 'name' => 'Sarah', 'count' => 45],
//   ['id' => 2, 'name' => 'David', 'count' => 30],
//   ...
// ]
```

#### `tehilim_get_recent_recitations( $campaign_id, $limit = 10 )`
Returns recent recitations with timestamps.

```php
$recitations = tehilim_get_recent_recitations( $post->ID );
// [
//   {
//     'id' => 1,
//     'campaign_id' => 123,
//     'ambassador_id' => null,
//     'chapter_number' => 47,
//     'reciter_name' => 'Sarah',
//     'created_at' => '2026-07-23 12:30:00'
//   },
//   ...
// ]
```

### In `inc/rest.php`

#### `tehilim_check_rate_limit( $endpoint, $limit = 10, $window = 3600 )`
Enforces IP-based rate-limiting for unauthenticated users. Returns boolean.

#### `tehilim_verify_turnstile( $token )`
Verifies a Cloudflare Turnstile token. Returns boolean. Gracefully returns true if Turnstile not configured.

## Security Considerations

### Input Validation

All REST endpoint parameters are validated:
- Type checking (integer, string, etc.)
- Range validation (chapter 1–150, etc.)
- Email validation (`is_email()`)
- String length validation

### Sanitization

- `sanitize_text_field()` for text input
- `sanitize_email()` for emails
- `absint()` for integer IDs

### Escaping

- `esc_html()` for HTML context
- `esc_attr()` for HTML attributes
- `esc_url()` for URLs
- Nonce verification for form submissions

### Rate-Limiting

IP-based transient-backed rate-limiting:
- Key: `tehilim_rl_{endpoint}_{ip_hash}`
- Bypassed for authenticated users
- Configurable limits (see REST endpoints above)

### CAPTCHA

Optional Cloudflare Turnstile integration:
- Server-side verification only (never client-only)
- Environment gated (`TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`)
- Returns 403 on verification failure

### Database Prefix Compatibility

All database operations use `$wpdb->prefix` to support:
- Custom WordPress prefixes
- Multisite installations
- Non-default table prefixes

## Theme Customization

### Design Tokens (in `style.css`)

Edit `:root` variables to change colors, spacing, etc.:

```css
:root {
  --primary: #C05A3A;        /* Campaign accent */
  --gold: #D9A441;           /* Highlights, success */
  --text: #2E2318;           /* Body text */
  --text-muted: #7A6B58;     /* Secondary text */
  --border: #EADCC6;         /* Divider lines */
  --bg-card: #FFFCF6;        /* Card backgrounds */
  --bg-page: #FAF4EA;        /* Page background */
  --radius: 18px;            /* Card radius */
  --radius-sm: 12px;         /* Input radius */
  --shadow-soft: 0 10px 30px rgba(70, 50, 25, 0.07);
}
```

### Typography

- **Body Font**: Change `font-family` in `body` rule (currently: Asimon)
- **Headings**: Use CSS classes `.h1`, `.h2`, etc., or HTML `<h1>`, `<h2>`, etc.
- **Psalms Text**: `.chapter-text` uses Frank Ruhl Libre

### Responsive Breakpoints

Media queries at:
- 768px (tablet)
- 375px (mobile)

Adjust in `style.css` or add new breakpoints as needed.

## JavaScript Modules

### `assets/js/app.js`

Handles:
- **Reader Logic**: Chapter display, next/previous/random navigation
- **"I Said" Button**: AJAX recitation submission with Turnstile integration
- **Stats Polling**: Polls `/campaigns/{id}/stats` every 10 seconds
- **Sharing**: WhatsApp, clipboard, native share API

**Global**: `window.TehilimApp` (for manual initialization if needed)

### `assets/js/form.js`

Handles:
- **Campaign Creation Form**: Validates occasion, dedicatee, goal; submits via REST or direct post
- **Ambassador Signup**: Collects name + email; calls `/ambassadors/join` endpoint; displays personal URL

**Global**: `window.TehilimForms` (for manual initialization if needed)

## Testing

### Manual Testing Checklist

- [ ] **Mobile (375px)**: Test on actual phone or DevTools mobile emulation
- [ ] **Tablet (768px)**: Verify layout stacking, navigation
- [ ] **Desktop (1024px, 1440px)**: Confirm multi-column layouts
- [ ] **RTL**: Open DevTools, toggle `dir="rtl"` on `<html>` (should already be on `<body>`)
- [ ] **Accessibility**: Use WAVE or axe DevTools to check contrast, keyboard nav
- [ ] **Forms**: Submit campaign creation, ambassador join; verify data in database
- [ ] **Reader**: Click "I Said This", verify recitation recorded in table
- [ ] **Stats**: Check real-time update after recitation submission
- [ ] **Rate-limiting**: Rapid-fire submissions; verify 429 after limit
- [ ] **Turnstile** (if enabled): Verify CAPTCHA challenge, token verification

### Automated Testing

**CI/CD** (`/.github/workflows/ci.yml`):
- PHP syntax lint: `php -l` on all `.php` files
- Encoding check: No mojibake (UTF-8 corruption)

To run locally:
```bash
php -l tehilim-theme/**/*.php
```

## Deployment Notes

1. **Database Setup**: Table is auto-created on theme activation via `tehilim_create_recitations_table()`
2. **Permalinks**: Ensure Custom Structure is enabled (e.g., `/%postname%/`)
3. **Rewrite Rules**: Flush after activation: Dashboard → Settings → Permalinks → Save
4. **Environment**: Set `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` in `wp-config.php` if using CAPTCHA
5. **Performance**: Consider caching plugins (WP Super Cache, W3 Total Cache) for high traffic

## Troubleshooting

### Table Not Created
- Verify theme is activated
- Check database permissions
- Manually run: `wp db query < tehilim-recitations.sql` (provide SQL file)
- Check error logs: `/wp-content/debug.log`

### Turnstile Not Working
- Verify `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` in `wp-config.php`
- Test token validation: Enable debug logging in `tehilim_verify_turnstile()`
- Check Cloudflare dashboard for token validation

### Rate-Limiting Errors (429)
- Verify transient storage (use persistent object cache for high traffic)
- Check IP detection (may fail behind proxies; set `REMOTE_ADDR` correctly)
- Adjust limits in `rest.php` if needed

### REST API Errors (400, 404, 500)
- Enable debug logging: `define( 'WP_DEBUG_LOG', true )` in `wp-config.php`
- Check `/wp-content/debug.log` for validation errors
- Verify post types and taxonomies are registered: Dashboard → Campaign / Ambassador

## Future Enhancements

Potential features for future phases:
- [ ] WebSocket integration for live stats (vs. polling)
- [ ] Email notifications on campaign milestones
- [ ] Social sharing analytics
- [ ] Campaign analytics dashboard
- [ ] Multi-language support (i18n)
- [ ] Block editor compatibility (FSE)
- [ ] Mobile app (React Native)

---

**Last Updated**: 2026-07-23  
**Built by**: Multi Digital  
**Repository**: https://github.com/multidigitalltd/tehilim
