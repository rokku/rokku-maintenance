=== Rokku Maintenance Mode ===
Contributors: rokku
Donate link: 
Tags: maintenance mode, coming soon, maintenance page, site offline, maintenance
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple maintenance mode plugin for WordPress. Customize your maintenance page with a logo, headline, and WYSIWYG message.

== Description ==

Rokku Maintenance Mode lets administrators easily enable a maintenance mode for their WordPress site. 
When enabled, non-logged-in visitors will see a custom maintenance page with:

- A custom logo
- A headline message
- A WYSIWYG text message

The WordPress admin bar turns red while maintenance mode is active, and an admin notice is displayed.

Perfect for site updates or temporary downtime, without the complexity.

== Installation ==

1. Upload the `rokku-maintenance-mode` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Settings > Maintenance Mode** to configure.

== Frequently Asked Questions ==

= Will this affect logged-in administrators? =

No. Administrators (users with `manage_options` capability) will see the normal site.

= Can I upload a logo? =

Yes, you can upload a logo using the WordPress Media Library.

= Will search engines be affected? =

Yes. The plugin returns a 503 Service Unavailable status code while active, which tells search engines the downtime is temporary.

== Screenshots ==

1. Maintenance mode settings page
2. Example maintenance page
3. Admin bar turns red when active

== Changelog ==

= 1.4 =
* Improved code organization and standards compliance:
  * Updated all function names, options, and constants to use unique prefix
  * Removed deprecated textdomain loading for WordPress 5.0+
  * Properly enqueued all styles using WordPress functions
  * Improved maintenance page template with proper wp_head/wp_footer
  * Added version numbers to all assets for better caching
  * Improved code organization and maintainability

= 1.3 =
* Added comprehensive security improvements:
  * Added nonce verification for maintenance mode toggle
  * Implemented rate limiting for status changes
  * Added security headers (CSP, X-Frame-Options, etc.)
  * Enhanced maintenance page template security
  * Added REST API endpoints with proper auth checks
  * Improved error handling and validation
  * Added responsive design improvements
* Removed debug logging in production
* Improved maintenance page styling and accessibility

= 1.2 =
* Fixed maintenance mode detection and display
* Added proper template redirect priority
* Improved settings handling and caching
* Added REST API support for settings
* Added debugging capabilities

= 1.1 =
* Added media uploader support for logo upload
* Replaced checkbox with styled on/off toggle
* Cleaned code for WordPress.org submission

== Upgrade Notice ==

= 1.3 =
Important security update: Adds comprehensive security improvements and enhanced maintenance page features.

= 1.2 =
Fixed maintenance mode detection and improved settings handling.

== License ==

This plugin is licensed under the GPLv2 or later.
