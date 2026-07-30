# Tehilim v2 — Community Psalms Campaign Platform

**A modern, purpose-built WordPress theme for organizing and tracking community-driven Psalms (Tehilim) recitation campaigns.**

## Overview

Tehilim is a WordPress theme that enables users to create dedicated campaigns for saying Tehilim (Psalms) together. Each campaign can have a specific dedication, occasion, or goal, and participants can track progress in real-time as chapters are completed.

### Key Features

- **Campaign Management**: Create campaigns with dedication details, occasion tags, and scalable goals
- **Chapter Reader**: Integrated Psalms reader with punctuation support
- **Ambassador System**: Invite friends as ambassadors with personalized referral links
- **Progress Tracking**: Real-time stats on completed books, chapters, and participant counts
- **Community Leaderboard**: Track top contributors and ambassadors
- **Responsive Design**: Fully responsive at 375px (mobile), 768px (tablet), 1024px, and 1440px (desktop)
- **RTL Support**: Full right-to-left language support for Hebrew
- **Security**: Cloudflare Turnstile CAPTCHA protection, IP-based rate-limiting, input validation
- **Accessibility**: WCAG AA+ contrast, keyboard navigation, reduced-motion support

## Requirements

- WordPress 6.3+
- PHP 7.4+
- MySQL 5.7+ (or MariaDB 10.2+)

## Installation

1. Clone or download the repository:
   ```bash
   git clone https://github.com/multidigitalltd/tehilim.git
   ```

2. Place `tehilim-theme/` in your WordPress `wp-content/themes/` directory.

3. Activate the theme in WordPress Admin → Appearance → Themes.

4. (Optional) Configure Turnstile:
   - Add to `wp-config.php`:
     ```php
     define( 'TURNSTILE_SITE_KEY', 'your_site_key_here' );
     define( 'TURNSTILE_SECRET_KEY', 'your_secret_key_here' );
     ```

## Usage

### Creating a Campaign

1. Navigate to **Tehilim** → **Create Campaign** in the menu
2. Select an occasion (Refua, Iluy, Zivug, Parnasa, Zchut, Event)
3. Enter the name to dedicate the campaign to
4. Set your goal (number of books to complete)
5. Submit and share the campaign link

### Joining as Ambassador

1. Click your personal ambassador link on a campaign
2. Enter your name and email
3. Receive your personal referral URL
4. Share your link to track chapters recruited

### Saying a Chapter

1. Open a campaign
2. Use the integrated reader to view the current chapter
3. Click "I Said This" to record your recitation
4. Watch the campaign progress bar update in real-time

## Architecture

### Custom Post Types

- **`campaign`**: Campaign with metadata (dedication, goal_books, occasion taxonomy)
- **`ambassador`**: Ambassador profiles (per campaign, personal stats)

### Custom Taxonomy

- **`occasion`**: Campaign occasion (Refua, Iluy, Zivug, Parnasa, Zchut, Event)

### Custom Database Table

- **`wp_tehilim_recitations`**: Atomic recording of completed chapters with timestamps

### REST API Endpoints

- `POST /wp-json/tehilim/v1/recitations` — Record a chapter recitation
- `GET /wp-json/tehilim/v1/campaigns/{id}/stats` — Get campaign progress stats
- `POST /wp-json/tehilim/v1/ambassadors/join` — Create a new ambassador

### Theme Files

```
tehilim-theme/
├── style.css                    # Main stylesheet (design tokens + components)
├── functions.php                # Theme setup, enqueue, hooks
├── header.php                   # Sticky navigation header
├── footer.php                   # Footer grid with links
├── index.php                    # Default template fallback
├── 404.php                      # 404 error page
├── front-page.php               # Homepage with hero, stats, FAQ
├── archive-campaign.php         # Campaign listing with occasion filters
├── single-campaign.php          # Campaign detail with reader & progress
├── template-ambassador.php      # Personalized referral page
├── template-dashboard.php       # Private ambassador dashboard
├── page-create.php              # Campaign creation form
├── inc/
│   ├── cpt.php                  # Custom post type & taxonomy registration
│   ├── meta.php                 # Meta field registration & helpers
│   └── rest.php                 # REST endpoints with validation & rate-limiting
├── assets/
│   ├── css/
│   │   └── reset.css            # CSS reset / normalization
│   ├── js/
│   │   ├── app.js               # Reader, share, stats polling
│   │   └── form.js              # Campaign creation, ambassador signup
│   ├── fonts/
│   │   └── asimon-{regular,medium,bold}.otf
│   └── images/
└── template-parts/
    ├── campaign-card.php        # Reusable campaign card component
```

## Design System

### Colors

- **Primary**: `#C05A3A` (burnt orange — dedication/action)
- **Gold**: `#D9A441` (accent — highlights/success)
- **Text**: `#2E2318` (dark brown — readability)
- **Muted Text**: `#7A6B58` (secondary text)
- **Border**: `#EADCC6` (light tan)
- **Card Background**: `#FFFCF6` (off-white)
- **Page Background**: `#FAF4EA` (warm beige)

### Typography

- **Body**: Asimon (400/500/700 weights)
- **Psalms Text**: Frank Ruhl Libre (for traditional appearance)

### Spacing & Radius

- **Container Max-Width**: 1200px
- **Border Radius**: 18px (cards), 12px (inputs)
- **Shadow**: `0 10px 30px rgba(70, 50, 25, 0.07)`

## Security

### Input Validation

All user input is:
- Validated server-side (type, length, range checks)
- Sanitized (`sanitize_text_field`, `sanitize_email`, etc.)
- Escaped in output (`esc_html`, `esc_attr`, `esc_url`)

### Rate-Limiting

Unauthenticated users are limited to:
- 10 recitation submissions per hour per IP
- 1 ambassador join per hour per IP

Authenticated users have no rate limits.

### CAPTCHA Protection

When `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` are defined:
- All mutation endpoints require Turnstile verification
- Invalid tokens return HTTP 403
- Server-side verification (never client-only)

### Database Prefix Compatibility

All database operations use `$wpdb->prefix` to support custom WordPress prefixes and multisite installations.

## Accessibility

- **Contrast**: All text meets WCAG AA+ (minimum 4.5:1 for body text)
- **Keyboard Navigation**: All interactive elements accessible via Tab key
- **Focus States**: Visible focus outlines on all focusable elements
- **RTL Support**: Full right-to-left layout support for Hebrew
- **Reduced Motion**: Animations disabled when `prefers-reduced-motion: reduce` is set
- **ARIA Labels**: Dynamic regions properly labeled for screen readers

## Responsive Breakpoints

| Breakpoint | Width | Device |
|-----------|-------|--------|
| Mobile | 375px | Small phone |
| Tablet | 768px | iPad / small tablet |
| Desktop | 1024px | Medium monitor |
| Wide | 1440px | Large monitor |

All breakpoints tested with proper layout adjustments (stacking, hiding, resizing).

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes (`git commit -am 'Add feature'`)
4. Push to the branch (`git push origin feature/your-feature`)
5. Open a Pull Request

## License

This theme is licensed under the GPL v2 or later.

## Support

For support, issues, or feature requests, please visit:
[GitHub Issues](https://github.com/multidigitalltd/tehilim/issues)

---

**Built by Multi Digital** | [multidigital.co.il](https://multidigital.co.il)
