# Psalms Unite WordPress Theme

This theme is a WordPress bridge for the Lovable/TanStack design. It loads the
compiled Lovable/Tailwind CSS and renders WordPress/PHP markup that follows the
original visual language.

Version 1.4 also forces the Tehillim Campaign Manager front-end assets to load
before `wp_head`, so plugin shortcodes render with their full design on
Campaigns, Create, Dashboard and single-campaign pages.

## Install

1. From the repository root, run:
   `npm install`
2. Build the WordPress bundle:
   `npm run build:wp`
3. Upload `wordpress/psalms-unite-theme` to:
   `wp-content/themes/psalms-unite-theme`
4. Activate the theme in WordPress.
5. On activation, the theme creates basic pages: Home, About, Campaigns,
   Create Campaign, Dashboard, and Auth.

If you need manual import instead, use WordPress Admin -> Tools -> Import ->
WordPress and upload `demo-content.xml` from this theme folder.

## Plugin Integration

The original project contains TanStack Start server functions and
Supabase-backed workflows. This theme does not replace those workflows with
fake PHP. Instead it exposes shortcode slots that a WordPress plugin can fill.

Default shortcode candidates from `multidigitalltd/tehilim`:

- Campaign archive: `[tehillim_campaigns]`
- Single campaign: `[tehillim_campaign id="123"]`
- Join form: `[tehillim_join_form id="123"]`
- Chapters: `[tehillim_chapters id="123"]`
- Progress: `[tehillim_progress id="123"]`
- Create campaign: `[tehillim_create_campaign_form]`
- My campaigns: `[tehillim_my_campaigns]`
- My activity: `[tehillim_my_activity]`
- Ambassador dashboard: `[tehillim_ambassador_dashboard]`
- Global stats: `[tehillim_global_stats]`

If the plugin uses different shortcode names, map them with the
`psalms_unite_shortcode_map` filter.

## Fonts

Theme font:

WordPress Admin -> Appearance -> Customize -> Psalms Unite Typography.

Plugin component font and design tokens:

WordPress Admin -> Tehillim Campaigns -> Settings -> Design.

For a fully native WordPress version, the campaign/auth/contribution logic
should be moved into a WordPress plugin with REST endpoints and database tables.
