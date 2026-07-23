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
