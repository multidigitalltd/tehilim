=== Tehilim Theme ===

A modern WordPress theme for community Psalms (Tehilim) campaign management.

== Description ==

Tehilim is a dedicated WordPress theme for organizing community Psalms recitation campaigns. Features include:

- Campaign creation and management
- Ambassador referral system
- Real-time progress tracking
- Cloudflare Turnstile CAPTCHA integration
- Rate-limiting to prevent abuse
- Full RTL support for Hebrew
- Responsive design (mobile-first)
- WCAG 2.2 AA accessibility compliance

== Installation ==

1. Download the theme ZIP file
2. In WordPress Admin: Appearance > Themes > Upload Theme
3. Select the ZIP file and click "Install Now"
4. Click "Activate" to enable the theme
5. Go to Tehilim Settings to configure

== Requirements ==

- WordPress 6.3+
- PHP 7.4+
- MySQL 5.7+

== Security ==

This theme includes:
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF token verification
- Rate-limiting
- Input sanitization
- Output escaping

== Performance ==

- Transient-based caching (5-minute TTL)
- Optimized database queries
- Cache-Control headers
- LiteSpeed Cache compatible
- Redis/Memcached support

== Support ==

For issues or questions: multidigitalltd@gmail.com
GitHub: https://github.com/multidigitalltd/tehilim

== Changelog ==

= 2.3.0 =
- Campaign image: creation form now offers "upload image" (with drag & drop, preview, JPG/PNG/WEBP up to 3MB) or "no image" mode
- No-image mode shows a rotating panel of curated verses in praise of saying Tehilim in the campaign hero (instead of a static placeholder)
- REST campaign-create endpoint accepts an optional base64 image, strictly validated (mime + size) and set as the featured image
- Campaign creation now requires login (enforced server-side + styled gate on the create page); campaigns are attributed to their creator
- New themed login page (/login/, auto-created on activation) with password login, remember-me, lost-password, and registration links
- Sign in with Google (OAuth 2.0, server-side flow): configure Client ID/Secret in Tehilim Settings or wp-config constants; auto-creates subscriber accounts from verified Google emails
- wp_login_url() now points to the themed page; failed logins return to it with a styled error

= 2.2.0 =
- Full Psalms text (all 150 chapters, 2,527 verses, menukad) now bundled locally in the theme (assets/data/tehilim.json) — no external API dependency, matching the design's text style (nikud without cantillation, Hashem abbreviation, punctuation)
- Smart chapter assignment: the reader offers chapters that were NOT yet said in the current communal book; a book completes only when all 150 chapters are covered
- Random / next navigation picks from the remaining open chapters; chapter picker shows which chapters are still open
- Progress semantics updated accordingly (books_done = fully covered cycles)

= 2.1.0 =
- Pixel-accuracy pass: all six pages audited against the design handoff and corrected (hover states, responsive breakpoints 1024/900/620, footer/CTA/archive structure, dead CSS removed)
- Live Psalms reader: real menukad chapter text (Sefaria API), Hebrew numeral titles, random/next/pick navigation, communal next-chapter suggestion from the server
- Live stats: instant UI update after each recitation + 10s polling
- Campaign creation endpoint (rate-limited, CAPTCHA-ready) with inline Hebrew validation messages
- Fixed multi-book progress counting; fixed /c/{campaign}/{ambassador} referral routing
- Ambassador join flow: inline form, personal link with copy button, organizer email notification

= 2.0.1 =
- CSS/JS cache bust for asset reload
- Performance and styling refinements

= 2.0.0 =
- Initial release
- Full security audit and hardening
- Performance optimization
- Accessibility compliance
