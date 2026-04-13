=== Auto SRI ===
Contributors: Zafir Sk Heerah
Tags: security, sri, integrity, csp, performance
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically adds Subresource Integrity (SRI) to external scripts/styles and safely excludes Google reCAPTCHA and Google Fonts.

== Description ==

**Auto SRI** automatically adds Subresource Integrity (SRI) attributes to scripts and styles loaded from external sources.

This improves security, protects against tampering, and enables strict Content Security Policy (CSP) setups.

### Features

* ✔ Adds SRI to all external `<script>` and `<link>` tags  
* ✔ Supports WordPress-enqueued assets and raw HTML tags  
* ✔ Supports async, defer, crossorigin, and multiline script tags  
* ✔ Caches all hashes for performance  
* ✔ Excludes admin panel (wp-admin) to prevent conflicts
* ✔ Automatically skips non-SRI-compatible providers:
    - Google reCAPTCHA  
    - Google Fonts (fonts.googleapis.com / fonts.gstatic.com)  
    - WordPress.com widgets (widgets.wp.com)
    - Dynamic concatenated resources
    - Dynamic script loaders and runtime-inserted scripts  
* ✔ Safe for Elementor, WooCommerce, CookieYes, Jetpack, GoDaddy hosting, etc.

### Why some scripts are excluded

This plugin automatically excludes:

* Google reCAPTCHA (`google.com/recaptcha`)  
* Google Fonts stylesheets (`fonts.googleapis.com`)  
* Google Fonts font files (`fonts.gstatic.com`)  
* WordPress.com widgets (`widgets.wp.com`)
* Dynamic concatenated resources (`/_static/??`)
* Other dynamic inline loaders (CookieYes, wsimg, ywxi, etc.)

Want to whitelist a dynamic provider? Contact us at izafirsk@gmail.com.
* Other dynamic inline loaders (CookieYes, wsimg, ywxi, etc.)

Want to whitelist a dynamic provider? Contact us at izafirsk@gmail.com.

These exclusions prevent:

* CORS failures  
* Integrity mismatch blocking  
* Google reCAPTCHA from breaking  
* Google Fonts from disappearing  
* Layout shifts caused by blocked assets

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

= Does this affect performance? =  
No. SRI hashes are computed once and stored in the WordPress options table.

= Does this break Elementor or CookieYes? =  
No. This plugin is fully compatible and tested against common dynamic script loaders.

= Does this plugin help with CSP? =  
Yes — it allows you to safely enforce:

For excluded domains, you should whitelist them in your CSP.

== Changelog ==

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

= 2.3 =
Version bump to 2.3.
