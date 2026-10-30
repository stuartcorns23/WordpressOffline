# Offline Mode

A WordPress plugin that switches your site into **maintenance mode** or **coming soon mode** with a customisable landing page. Logged-in users bypass the landing page and see the site as normal.

## Modes

| Mode | Visitors see | HTTP status |
| --- | --- | --- |
| Off | The live site | – |
| Maintenance | Landing page | `503 Service Unavailable` with a `Retry-After` header, so search engines keep your rankings |
| Coming soon | Landing page | `200 OK` (optionally `noindex`) |

wp-admin, `wp-login.php`, admin-ajax, cron and the REST API keep working in every mode, so you can always log in. An admin bar badge reminds logged-in users when a mode is on.

## Settings

**Settings → Offline Mode**

- **Content:** browser title, heading, message (rich text)
- **Design:** logo and logo width, background image with overlay darkness, background, text and accent colours, font
- **Launch date:** optional countdown, and automatic switch-off of coming soon mode when the date passes
- **Advanced:** Retry-After hours, search engine indexing, a log in link on the landing page, custom CSS

Use **Preview landing page** to see the page while you are logged in.

## Installation

Copy this folder to `wp-content/plugins/offline-mode` (or upload it as a zip from **Plugins → Add New**) and activate **Offline Mode**.

## Developers

- `offline_mode_can_bypass` (bool) — who sees the real site. Defaults to any logged-in user. For example, to only let editors and above through:

  ```php
  add_filter( 'offline_mode_can_bypass', function () {
      return current_user_can( 'edit_others_posts' );
  } );
  ```

- `offline_mode_view_data` (array, mode) — filter the values passed to the landing page template.
- Override the template by copying `templates/landing.php` to `your-theme/offline-mode/landing.php`.

## Troubleshooting: visitors see another plugin's page

A deleted maintenance plugin can leave things behind that still run. The settings page warns about each of these when it finds them:

- `wp-content/maintenance.php` or a `.maintenance` file in the WordPress root
- a must-use plugin in `wp-content/mu-plugins` (these keep running after the main plugin is deleted)
- another active maintenance or coming soon plugin
- a maintenance redirect in `.htaccess`
- a page cache still holding the old page

Offline Mode clears common page caches (WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache, LiteSpeed, SiteGround, WP Engine, Cache Enabler, Breeze, Nginx Helper) whenever you save its settings, and again at the automatic launch time. Host and CDN caches such as Cloudflare need clearing by hand.
