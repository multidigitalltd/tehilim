# Tehilim v2 Build Plan

**Branch**: `claude/tehilim-v2-clean`  
**Source**: Design-reference HTML + README.md handoff  
**Goal**: Production-ready WordPress theme from hi-fi design in 1–2 weeks of focused iteration.

---

## Phase 1: Cleanup & Skeleton (Commit 1)

### 1a. Delete Old Code
```bash
# Remove the old theme and plugin
git rm -r psalms-unite-theme tehillim-campaign-manager
```

**Why**: Fresh start. No legacy cruft in the new architecture.

### 1b. Create Theme Structure
```
tehilim-theme/
├── style.css           # Header + design tokens
├── functions.php       # Enqueue, CPT, taxonomy, REST
├── header.php          # Logo, nav, RTL
├── footer.php          # Footer grid, copyright
├── front-page.php      # Homepage (hero, sections, FAQ, CTA)
├── archive-campaign.php # Campaigns grid + filtering
├── single-campaign.php  # Campaign detail + reader
├── template-ambassador.php # Referral link
├── template-dashboard.php  # Ambassador dashboard
├── page-create.php      # Campaign form
├── 404.php
├── inc/
│   ├── cpt.php         # CPT `campaign`, `ambassador`; taxonomy `occasion`; rewrite
│   ├── meta.php        # Meta fields (ACF or postmeta)
│   └── rest.php        # REST endpoints (/recitations, /join, etc.)
├── assets/
│   ├── fonts/
│   │   └── asimon-{regular,medium,bold}.otf
│   ├── css/
│   │   ├── reset.css
│   │   └── theme.css (optional; inline on start)
│   ├── js/
│   │   ├── app.js      # Chapter reader, "I said", share
│   │   └── form.js     # Campaign creation, ambassador signup
│   └── images/
│       └── [SVG icons]
└── templates/
    ├── parts/
    │   ├── header.html
    │   ├── footer.html
    │   └── campaign-card.html (reusable partial)
    └── [...]
```

**Commit**: "Bootstrap tehilim-theme with skeleton structure"

---

## Phase 2: Design Tokens & Base Layout (Commit 2)

### 2a. `style.css` Header + Root Tokens
From design-reference section 8:

```css
/*
Theme Name: Tehilim
Theme URI: https://github.com/multidigitalltd/tehilim
Author: Multi Digital
Description: Community Psalms campaign platform
Version: 1.0.0
Requires at least: 6.3
Requires PHP: 7.4
Text Domain: tehilim
Domain Path: /languages
*/

:root {
  --primary: #C05A3A;
  --gold: #D9A441;
  --text: #2E2318;
  --text-muted: #7A6B58;
  --border: #EADCC6;
  --bg-card: #FFFCF6;
  --bg-page: #FAF4EA;
  --radius: 18px;
  --radius-sm: 12px;
  --shadow-soft: 0 10px 30px rgba(70, 50, 25, 0.07);
}

html { scroll-behavior: smooth; }
body { margin: 0; background: var(--bg-page); font-family: 'Assistant', sans-serif; direction: rtl; }
```

### 2b. `header.php` & `footer.php` Scaffold
- Header: sticky, RTL nav, hamburger on mobile
- Footer: 4-column grid (logo + desc, product, about, resources), copyright

### 2c. WordPress-Specific Boilerplate
- `wp_head()`, `wp_footer()` in templates
- `wp_enqueue_style()`, `wp_enqueue_script()` for Asimon, Google Fonts
- `get_template_directory_uri()` for asset paths
- `wp_link_pages()`, `get_the_archive_title()`, etc.

**Commit**: "Add design tokens, header/footer, base boilerplate"

---

## Phase 3: CPT, Taxonomy, Rewrite Rules (Commit 3)

### 3a. `inc/cpt.php`
```php
function register_campaign_cpt() {
  register_post_type('campaign', [
    'label' => 'Campaigns',
    'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
    'public' => true,
    'has_archive' => true,
    'rewrite' => ['slug' => 'campaigns', 'with_front' => false],
    'show_in_rest' => true,
  ]);

  register_post_type('ambassador', [
    'label' => 'Ambassadors',
    'supports' => ['title', 'custom-fields'],
    'public' => true,
    'rewrite' => ['slug' => 'ambassadors', 'with_front' => false],
    'show_in_rest' => true,
  ]);
}

function register_occasion_taxonomy() {
  register_taxonomy('occasion', 'campaign', [
    'labels' => ['name' => 'Occasions', ...],
    'public' => true,
    'show_in_rest' => true,
  ]);
}
```

### 3b. Custom Rewrite for `/c/{campaign}/{ambassador}`
```php
add_rewrite_rule(
  '^c/([^/]+)/([^/]+)/?$',
  'index.php?post_type=campaign&name=$matches[1]&ambassador=$matches[2]',
  'top'
);
```

### 3c. Custom Table `wp_tehilim_recitations`
**Important**: Use `$wpdb->prefix` in runtime code; table name built via `$wpdb->prefix . 'tehilim_recitations'`.

```sql
CREATE TABLE {$WPDB_PREFIX}tehilim_recitations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id BIGINT UNSIGNED NOT NULL,
  ambassador_id BIGINT UNSIGNED,
  chapter_number INT UNSIGNED NOT NULL,
  reciter_name VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (campaign_id) REFERENCES {$WPDB_PREFIX}posts(ID) ON DELETE CASCADE,
  INDEX (campaign_id),
  INDEX (ambassador_id)
);
```

**Runtime creation** (in `inc/cpt.php` or activation hook):
```php
$wpdb->query( $wpdb->prepare( 
  "CREATE TABLE IF NOT EXISTS `%i` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id BIGINT UNSIGNED NOT NULL,
    ambassador_id BIGINT UNSIGNED,
    chapter_number INT UNSIGNED NOT NULL,
    reciter_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES `%i`(ID) ON DELETE CASCADE,
    INDEX (campaign_id),
    INDEX (ambassador_id)
  )",
  $wpdb->prefix . 'tehilim_recitations',
  $wpdb->posts
) );
```

**Commit**: "Register CPT, taxonomy, rewrite rules, recitations table"

---

## Phase 4: Homepage & Campaign Archive (Commit 4–5)

### 4a. `front-page.php` — All Sections from Design
1. Hero (a-symmetric 2-col, title + CTA)
2. Stats bar (4 metrics)
3. "Recent campaigns" grid
4. "How it works" 3-col cards
5. "Features" 4×2 grid
6. Testimonials/quotes carousel
7. FAQ accordion (7 items)
8. Dark CTA banner
9. Footer

### 4b. `archive-campaign.php` — Grid + Filters
- Occasion chips (refua, iluy, zivug, parnasa, zchut, event)
- Grid of campaign cards (3 cols on desktop, 1 on mobile)
- Card component: featured image, name, dedication type, goal progress bar, status chip

**Reusable Template**: `parts/campaign-card.php`

**Commit 4**: "Add homepage with all sections and hero animation"  
**Commit 5**: "Add campaign archive grid with occasion filtering"

---

## Phase 5: Campaign Detail & Reader (Commit 6–7)

### 5a. `single-campaign.php`
1. Hero: dedication title, description, featured image
2. Progress card: % ring, 4 stats
3. Reader widget:
   - Current chapter display (Frank Ruhl Libre, punctuated)
   - "I said this" button → AJAX + Turnstile
   - Random/next chapter buttons
4. Ambassadors list (3 top ambassadors by chapters)
5. Recent activity feed (last 10 recitations)
6. Sidebar (sticky on desktop):
   - Share card (WhatsApp + copy link)
   - Ambassador leaderboard

### 5b. `template-ambassador.php` — Referral Link
- Hero: personalized message ("You're invited by {Ambassador}")
- Personal progress ring
- Same reader as campaign, but recitations count to ambassador
- Leaderboard highlights the ambassador

### 5c. `inc/rest.php` — Endpoints & Rate-Limiting
**Security**: All public mutation endpoints require rate-limiting (IP-based; see Phase 7e).

```
POST /wp-json/tehilim/v1/recitations
  → { campaign_id, chapter_number, ambassador_id?, reciter_name?, cf_turnstile_response }
  ← returns { chapter_number (next), success }
  Note: chapter_number is the chapter being marked as read (required for audit trail)

GET /wp-json/tehilim/v1/campaigns/{id}/stats
  → { books_done, chapters_done, participants, ambassadors }

POST /wp-json/tehilim/v1/ambassadors/join
  → { campaign_id, name, email }
  ← returns { ambassador_id, personal_url }
  Note: Rate-limit to 1 per IP per hour to prevent signup spam
```

**Rate-Limiting Implementation**:
- Use transient-based IP counter: `tehilim_rl_{endpoint}_{ip_hash}`
- Mutation endpoints: max 10 requests per hour per IP (recitations), 1 per hour (join)
- Return HTTP 429 with `Retry-After` header when exceeded
- Whitelist authenticated users (rate limit does not apply to logged-in requests)

### 5d. `assets/js/app.js`
- Reader logic: fetch chapter text, display, handle "said" button
- Turnstile integration (if key present in options)
- Live poll to `/stats` every 10s (or WebSocket if built later)
- Share handlers (WhatsApp, copy-to-clipboard)

**Commit 6**: "Add single campaign page with reader and progress"  
**Commit 7**: "Add ambassador referral page and REST endpoints"

---

## Phase 6: Forms & Dashboard (Commit 8–9)

### 6a. `page-create.php` — Campaign Creation
Fields:
- Occasion selector (chips)
- "Name to dedicate" (input)
- "Organizer name" (input)
- Goal slider (1–100 books)
- Submit button → POST to REST or direct to wp_insert_post()

Success: draft campaign created, owner email sent, redirect to personal dashboard

### 6b. `template-dashboard.php` — Ambassador Dashboard
Private view (check `is_user_logged_in()`):
- Personal progress ring
- Stats: chapters recruited, chapters said, leaderboard rank
- Personal referral link + copy button
- "View public page" button
- Pending campaigns (if editing/awaiting approval)

### 6c. Turnstile Integration
If `TURNSTILE_SITE_KEY` env var set:
- Render `<div class="cf-turnstile">` before form submission
- Verify `cf_turnstile_response` on backend via REST/POST
- Show error if token invalid

**Commit 8**: "Add campaign creation form"  
**Commit 9**: "Add ambassador dashboard and Turnstile protection"

---

## Phase 7: Polish & QA (Commit 10)

### 7a. Responsive Breakpoints
- Test at 375px (mobile), 768px (tablet), 1024px (medium), 1440px (desktop)
- Adjust: hero layout, grid cols, nav hamburger, sidebar → static
- Verify all text readable, buttons easily tappable

### 7b. RTL & Contrast
- `dir="rtl"` on all pages
- WAVE audit on each major page
- Ensure 4.5:1 contrast on text vs. background

### 7c. Accessibility
- Keyboard nav (Tab through buttons, forms)
- Focus states (outline on `:focus-visible`)
- Alt text on all images
- ARIA labels on dynamic regions (leaderboard updates, reader)
- `prefers-reduced-motion` respected (disable animations)

### 7d. Performance
- Lazy-load images (`loading="lazy"`)
- Minify CSS (if not using inline)
- Defer JS load where safe
- Test homepage load time (< 3s target)

### 7e. Security
- Sanitize all REST input (`sanitize_text_field`, `absint`, etc.)
- Verify nonces on form submissions
- Escape output (`esc_html`, `esc_attr`, `esc_url`)
- Turnstile verification server-side (never client-only)
- **IP-based rate-limiting** on mutation endpoints:
  - `POST /recitations`: max 10/hour per IP (allows personal use)
  - `POST /ambassadors/join`: max 1/hour per IP (prevents signup spam)
  - Use WordPress transients for state storage
  - Return HTTP 429 with `Retry-After` header
  - Exempt authenticated users

**Commit**: "Polish: responsive, RTL, a11y, security"

---

## Phase 8: Deployment & Testing (Commit 11)

### 8a. CI/QA Gate
- **PHPCS**: WordPress standard + custom ruleset
- **PHPSTAN**: level max (if complexity warrants)
- **PHP -l**: parse all `.php` files
- **Manual**: smoke test on local WP install

### 8b. Documentation
- `README.md` with setup instructions
- `CLAUDE.md` with codebase overview
- Comment on complex REST logic
- Link to design-reference handoff

### 8c. Create Single Squash-Merge PR
- Title: "Tehilim v2: Complete redesign from handoff HTML"
- Description: overview of changes, what's new, migration notes
- Squash into 1 commit on main (if small) or 2–3 logical chunks

---

## Work Cadence & Completion Status

**COMPLETED**:
- ✅ **Phase 1a** (b991136): Delete old theme and plugin
- ✅ **Phase 1b** (2a5095e): Bootstrap tehilim-theme skeleton with 17 files
- ✅ **CI/QA Fix** (d0812d5): Update .github/workflows/ci.yml for new theme, add CSS reset
- ✅ **Phase 2** (099e8c7): Design tokens, header/footer scaffolds, responsive CSS (~257 lines added)
- ✅ **Phase 3 (scaffolded)**: CPT registration (inc/cpt.php), REST endpoints (inc/rest.php), rate-limiting

**READY FOR IMPLEMENTATION**:
- 🔄 **Phase 4–5** (templates exist, need content): Homepage, archive, campaign detail, ambassador referral
- 🔄 **Phase 6**: Campaign creation form, ambassador dashboard
- 🔄 **Phase 7**: Polish, a11y, security audits
- 🔄 **Phase 8**: Final QA and PR review

**Original Week 1 Plan**:
- Mon: Phases 1–2 (cleanup, scaffold, tokens) ✅ COMPLETED
- Tue–Wed: Phases 3–4 (CPT, homepage, archive)
- Thu–Fri: Phases 5–6 (reader, referral, forms)

**Current State**:
- Theme skeleton complete and ready for content
- CI passing (PHP syntax lint)
- Design system fully integrated
- All major scaffolding in place (~1500 lines PHP + 400 lines CSS)

---

## Handoff Spec Checklist

- [ ] Homepage hero (asymmetric, h1 + button + stat row)
- [ ] Stats bar (4 metrics)
- [ ] Campaign archive (grid + occasion filter)
- [ ] Campaign detail (progress, reader, ambassadors, feed)
- [ ] Ambassador referral (personalized, goal progress, reader)
- [ ] Campaign creation form (occasion, name, goal, submit)
- [ ] Ambassador dashboard (personal stats, link, "public" button)
- [ ] Reader widget ("I said", next chapter, random, Turnstile)
- [ ] Design tokens (colors, radius, shadows, fonts)
- [ ] RTL (all templates `dir="rtl"`)
- [ ] Responsive (4 breakpoints tested)
- [ ] A11y (WCAG AA contrast, focus, reduced-motion)
- [ ] Security (nonces, sanitize, escape, Turnstile verify, rate-limiting on mutation endpoints)

---

## Known Decisions

1. **No custom post type for Tehilim text**: Static JSON or Sefaria API call on read (simpler).
2. **Ambassadors as CPT**: Simpler relational model; can join multiple campaigns.
3. **Recitations in custom table**: Atomic, indexed for leaderboard queries; no postmeta overhead.
4. **REST for "I said"**: Enables real-time refresh without page reload.
5. **Turnstile optional**: If `TURNSTILE_SITE_KEY` is unset, skip verification (dev-friendly).
6. **No separate plugin**: All logic in theme `functions.php`, `inc/` for modularity.

---

## File Sizes & Targets

- `index.php`: ~200 lines (many via template parts)
- `functions.php`: ~300 lines (enqueue, CPT, filters)
- `inc/cpt.php`: ~100 lines
- `inc/rest.php`: ~150 lines
- `assets/js/app.js`: ~200 lines
- CSS: inline or ~500 lines
- **Total PHP**: ~1000 lines (vs. old plugin's 5000+)

