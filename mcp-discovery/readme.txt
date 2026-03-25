=== MCP Discovery ===
Contributors: 99rig
Tags: mcp, ai, discovery, rest-api, well-known, woocommerce
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exposes /.well-known/mcp-server so AI agents can discover your site via mcp://. Implements draft-serra-mcp-discovery-uri. WooCommerce-aware.

== Description ==

The **MCP Discovery** plugin exposes a standard `/.well-known/mcp-server` endpoint on your WordPress site, making it discoverable by AI agents using the `mcp://` URI scheme.

Based on the IETF Internet Draft [draft-serra-mcp-discovery-uri](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/).

**Features:**
* Works on Apache and Nginx — no server config required
* Automatic manifest generation from WordPress settings
* WooCommerce auto-detection (adds `e-commerce` category automatically)
* Admin settings page under Settings → MCP Discovery
* Opt-out crawling support (`"crawl": false`)
* CORS headers for cross-origin access
* Zero configuration required — works out of the box
* Compatible with WordPress 5.0+ and PHP 7.4+

**How it works:**

Once installed, your site exposes:

    GET https://yoursite.com/.well-known/mcp-server

Which returns:

    {
      "mcp_version": "2025-06-18",
      "name": "My Shop MCP Server",
      "endpoint": "https://yoursite.com/wp-json/mcp/v1",
      "transport": "http",
      "auth": { "type": "none" },
      "capabilities": ["tools", "resources"],
      "categories": ["e-commerce"],
      "crawl": true
    }

AI agents can then resolve `mcp://yoursite.com` and connect directly to your MCP endpoint.

== Installation ==

1. Upload the `mcp-discovery` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Visit `https://yoursite.com/.well-known/mcp-server` to verify
4. Optionally configure under **Settings → MCP Discovery**

No server configuration required. Works on Apache and Nginx.

== Frequently Asked Questions ==

= Do I need to configure my web server? =

No. The plugin intercepts the request at the WordPress level before any server rewrite rules apply.

= Does it work with WooCommerce? =

Yes. WooCommerce is auto-detected and the `e-commerce` category is added automatically to the manifest.

= How do I opt out of indexing? =

Go to Settings → MCP Discovery and uncheck "Allow Crawling". This sets `"crawl": false` in the manifest.

= What WordPress versions are supported? =

WordPress 5.0 and later. PHP 7.4 and later.

== Changelog ==

= 0.2.0 =
* Rewrote endpoint handler to work on both Apache and Nginx
* Intercept request at parse_request hook (earlier, more reliable)
* Fixed PHP 7.4 compatibility (removed array union type hints)
* Added sensible defaults on activation
* Bumped minimum requirement to WP 5.0 / PHP 7.4

= 0.1.0 =
* Initial release

== Upgrade Notice ==

= 0.2.0 =
Important fix: endpoint now works correctly on Nginx deployments.
