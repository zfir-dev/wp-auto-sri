=== WP Auto SRI ===
Contributors: zafir
Tags: security, sri, integrity, csp, headers, performance
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically adds Subresource Integrity (SRI) to ALL external scripts and styles — including ones loaded by wp_enqueue, hard-coded HTML, and scripts injected dynamically by JavaScript.

== Description ==

WP Auto SRI is a lightweight security plugin that automatically generates SHA-384 Subresource Integrity (SRI) hashes for **every external script or stylesheet** on your website.

Unlike most plugins, WP Auto SRI supports:

* Scripts enqueued via `wp_enqueue_script()`
* Styles enqueued via `wp_enqueue_style()`
* Raw `<script>` and `<link>` tags output directly by themes and plugins
* Scripts injected dynamically via JavaScript (e.g. analytics, cookie banners)
* Performance caching — hashes only generated once, then stored
* Zero configuration — works immediately after activation

This helps improve security, increase browser trust, and enforce strong CSP headers.

Ideal for users implementing:

* Content Security Policy (CSP) with `require-sri-for script style`
* Security scanners requiring SRI
* Strict mode WordPress security setups

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/wp-auto-sri`
2. Activate the plugin through the "Plugins" menu in WordPress
3. No configuration needed — SRI is applied automatically

== Frequently Asked Questions ==

= Does this plugin modify local plugin files? =  
No. It only rewrites the final HTML output with proper SRI attributes.

= Does this work with scripts added using JavaScript? =  
Yes. The plugin uses output buffering to apply SRI to all external scripts that appear in the final HTML.

= Does this slow down my website? =  
No. Each hash is cached with `update_option()` and reused on every load.

= Which hashing algorithm is used? =  
SHA-384 (recommended by modern browsers). Support for SHA-256 and SHA-512 is planned.

= Does it break async or defer attributes? =  
No. All existing tag attributes are preserved.

== Screenshots ==

1. Example of SRI added to external script tags

== Changelog ==

= 1.2 =
* Added universal script/link matching (supports async, defer, single quotes, multiline tags, injected scripts)
* Added support for CookieYes, wsimg, ywxi.net, and host-injected scripts
* Improved reliability with multiline and malformed tag detection
* Full compatibility with dynamic JavaScript loaders
* Version bump and cleanup

= 1.1 =
Added output buffering to rewrite ALL external script/style tags and inject SRI.

= 1.0 =
Initial release.

== Upgrade Notice ==

= 1.2 =
This update adds FULL SRI coverage to all dynamic, injected, and non-standard script tags. Update recommended for maximum security.
