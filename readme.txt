=== WP Auto SRI ===
Contributors: zafir, (your-wp.org-username)
Tags: security, sri, integrity, csp
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generates Subresource Integrity (SRI) hashes for all external scripts and styles that do not provide one.

== Description ==

WP Auto SRI scans all enqueued external assets and adds `integrity="sha384-..."` and `crossorigin="anonymous"` automatically. This improves security and works with strict CSP policies.

* Compatible with all themes and plugins
* No configuration needed
* Caches hashes for performance
* Works with both scripts and styles

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/wp-auto-sri`
2. Activate via the Plugins menu
3. You're done — the plugin works automatically.

== Frequently Asked Questions ==

= Does this slow down my site? =  
No. Hashes are cached with `update_option()` and only computed once.

= Does it support SHA256 or SHA512? =  
Version 1.0 uses SHA384 (recommended for browsers). More algorithms coming soon.

= Does it modify local plugin files? =  
No. It only modifies the `<script>` / `<link>` output HTML via WordPress filters.

== Changelog ==

= 1.0 =
Initial release.

== Upgrade Notice ==

= 1.0 =
First stable version.
