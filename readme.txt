=== Auto SRI ===
Contributors: Zafir Sk Heerah
Tags: security, sri, integrity, csp, performance
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically adds Subresource Integrity (SRI) to external scripts/styles and safely excludes Google reCAPTCHA and Google Fonts.

== Description ==

**Auto SRI** automatically adds Subresource Integrity (SRI) attributes to scripts and styles loaded from external sources.

This improves security, protects against tampering, and enables strict Content Security Policy (CSP) setups.

Auto SRI has **no settings**. Activate it and it works, with fixed, safe defaults.

### Features

* ✔ Adds SRI to all external `<script>` and `<link rel="stylesheet">` tags
* ✔ Supports WordPress-enqueued assets and raw HTML tags
* ✔ Supports async, defer, crossorigin, and multiline script tags
* ✔ SHA-384 hashes, computed once and cached for performance
* ✔ Excludes the admin panel (wp-admin), your own site's files, and tags that already have an integrity attribute
* ✔ Automatically skips non-SRI-compatible providers:
    - Google reCAPTCHA
    - Google Fonts (fonts.googleapis.com / fonts.gstatic.com)
    - WordPress.com widgets (widgets.wp.com)
    - Dynamic concatenated resources (`/_static/??`)
* ✔ Safe for Elementor, WooCommerce, CookieYes, Jetpack, GoDaddy hosting, etc.

### Why some scripts are excluded

The providers above serve content that changes on every request, so an integrity hash would make browsers block them. Skipping them prevents CORS failures, integrity mismatch blocking, broken reCAPTCHA, missing Google Fonts and layout shifts.

### Auto SRI Pro (optional)

All settings live in the optional, paid Auto SRI Pro add-on: custom URLs to exclude, hash algorithm choice (SHA-256/384/512), switches for HTML rewriting and the built-in exclusions, scheduled re-checks with email alerts when a CDN file changes, an asset manager and WP-CLI commands. Auto SRI keeps working fully without it.

### For developers

Every default can be changed in code with filters: `auto_sri_enabled`, `auto_sri_rewrite_html`, `auto_sri_hash_algorithm`, `auto_sri_builtin_exclusions`, `auto_sri_excluded_urls`, `auto_sri_is_excluded` and `auto_sri_pre_get_hash`, plus the `auto_sri_hash_computed` action.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/auto-sri`
== Frequently Asked Questions ==

= Does this plugin apply SRI in the WordPress admin panel? =
No. The plugin automatically skips the WordPress admin panel (wp-admin) to prevent any conflicts with admin scripts and ensure smooth backend operation.

= Why are some scripts not receiving SRI? =  
Scripts from Google reCAPTCHA, Google Fonts, WordPress.com widgets, and other dynamic sources cannot support SRI because their content changes on every request.

This plugin intelligently detects those sources and safely skips them.

= Why are some scripts not receiving SRI? =  
Scripts from Google reCAPTCHA, Google Fonts, wsimg, ywxi, and other dynamic sources cannot support SRI because their content changes on every request.

This plugin intelligently detects those sources and safely skips them.

= How do I exclude my own scripts or domains? =
Since version 2.4 Auto SRI has no settings; the Custom URLs setting is part of the optional Auto SRI Pro add-on. Developers can also exclude URLs in code:

`add_filter( 'auto_sri_excluded_urls', function ( $patterns ) { $patterns[] = 'my-dynamic-script.js'; return $patterns; } );`

Each pattern is matched as a substring of the script or stylesheet URL.

= Does this affect performance? =  
No. SRI hashes are computed once and stored in the WordPress options table.

= Does this break Elementor or CookieYes? =  
No. This plugin is fully compatible and tested against common dynamic script loaders.

= Does this plugin help with CSP? =  
Yes — it allows you to safely enforce:

For excluded domains, you should whitelist them in your CSP.

== Changelog ==

= 2.4 =
* Auto SRI no longer has settings. The "Excluded URLs" textarea is removed, and the list saved there is no longer applied by the free plugin. The saved list stays in the database: Auto SRI Pro imports it automatically, and sites that had one see a dismissible notice listing it
* The settings page is now a short info page (Settings → Auto SRI); the plugin row link reads "About"
* Built-in exclusions are now one case-insensitive list
* Added filters so developers and add-ons can change every default: `auto_sri_enabled`, `auto_sri_rewrite_html`, `auto_sri_hash_algorithm`, `auto_sri_builtin_exclusions`, `auto_sri_excluded_urls`, `auto_sri_is_excluded`, `auto_sri_pre_get_hash`, and the `auto_sri_hash_computed` action
* Cached hashes are recomputed if the hash algorithm changes
* Added an Auto SRI Pro card on the info page and an "Upgrade to Pro" link in the plugin list, both hidden once Pro is active

= 2.3 =
* Version bump to 2.3

= 2.2 =
* Updated support section with a cleaner PayPal button and improved copy

= 2.1 =
* Improved settings page UX with clearer instructions
* Added "Settings" link to the plugin action links on the plugins page

= 2.0 =
* Added settings page to allow user-defined URL exclusions
* Refactored exclusion logic for better maintainability (Unit tested)

= 1.9 =
* Added admin panel exclusion - SRI no longer applies in wp-admin
* Added exclusion for WordPress.com widgets (widgets.wp.com)
* Added exclusion for dynamic concatenated resources (/_static/??)
* Fixed integrity mismatch errors for dynamic content
* Improved compatibility with WordPress.com features

= 1.8 =
* Fixed prefixing issues to comply with WordPress standards
* Improved security by preventing direct file access
* Excluded development assets from release package
* Example of SRI added to external script tags in the page source

= 1.7 =
* Code quality improvements
* WordPress coding standards compliance
* Optimized readme for plugin repository

= 1.6 =
* Renamed plugin to comply with WordPress.org trademark policies
* Updated all assets and paths
* Stability improvements

= 1.5 =
* Renamed plugin to comply with WordPress.org trademark policies
* Updated all assets and paths
* Stability improvements

= 1.4 =
* Added new plugin banner + icon assets
* Visual branding improvements
* Updated readme and asset packaging

= 1.3 =
* Added automatic exclusion of Google reCAPTCHA (fixes CORS / blocked script issues)
* Added automatic exclusion of Google Fonts (fixes integrity mismatch issues)
* Improved compatibility with Google APIs and Elementor
* Updated SRI matching and handling logic
* Stable, safe version for production use

== Upgrade Notice ==

= 2.4 =
Settings have moved to Auto SRI Pro. If you saved custom excluded URLs, the free plugin stops applying them after this update and those files get integrity hashes again. Your list is kept, and Auto SRI Pro imports it automatically. Developers can use the auto_sri_excluded_urls filter instead. Built-in exclusions are unchanged.

= 2.3 =
Version bump to 2.3.
