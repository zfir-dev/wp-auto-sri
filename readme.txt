=== Auto SRI for WordPress ===
Contributors: Zafir Sk Heerah
Tags: security, sri, integrity, csp, headers, performance
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically adds Subresource Integrity (SRI) to external scripts and styles, with safe automatic exclusions for Google reCAPTCHA, Google Fonts, and other dynamic resources.

== Description ==

**WP Auto SRI** automatically adds Subresource Integrity (SRI) attributes to scripts and styles loaded from external sources.

This improves security, protects against tampering, and enables strict Content Security Policy (CSP) setups.

### Features

* ✔ Adds SRI to all external `<script>` and `<link>` tags  
* ✔ Supports WordPress-enqueued assets and raw HTML tags  
* ✔ Supports async, defer, crossorigin, and multiline script tags  
* ✔ Caches all hashes for performance  
* ✔ Automatically skips non-SRI-compatible providers:
    - Google reCAPTCHA  
    - Google Fonts (fonts.googleapis.com / fonts.gstatic.com)  
    - Dynamic script loaders and runtime-inserted scripts  
* ✔ Safe for Elementor, WooCommerce, CookieYes, Jetpack, GoDaddy hosting, etc.

### Why some scripts are excluded

Some providers load **dynamic content** — the content can change depending on browser version, device, region, or session.  
Such scripts cannot safely support SRI.

This plugin automatically excludes:

* Google reCAPTCHA (`google.com/recaptcha`)  
* Google Fonts stylesheets (`fonts.googleapis.com`)  
* Google Fonts font files (`fonts.gstatic.com`)  
* Other dynamic inline loaders (CookieYes, wsimg, ywxi, etc.)

Want to whitelist a dynamic provider? Contact us at izafirsk@gmail.com.

These exclusions prevent:

* CORS failures  
* Integrity mismatch blocking  
* Google reCAPTCHA from breaking  
* Google Fonts from disappearing  
* Layout shifts caused by blocked assets

== Installation ==

1. Upload the plugin to `/wp-content/plugins/auto-sri-for-wordpress`
2. Activate it through **Plugins → Installed Plugins**
3. SRI will be added automatically to all compatible external assets

No configuration required.

== Frequently Asked Questions ==

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

== Screenshots ==

1. Example of SRI added to external script tags in the page source

== Changelog ==

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

= 1.2 =
* Added universal script/link matching (supports async, defer, single quotes, multiline)
* Improved handling for CookieYes, wsimg, ywxi
* Better compatibility with dynamic script loaders

= 1.1 =
Added output buffering to rewrite ALL external script/style tags and inject SRI.

= 1.0 =
Initial release.

== Upgrade Notice ==

== Upgrade Notice ==

= 1.5 =
This update renames the plugin to comply with WordPress.org policies. You must update to continue receiving updates.
