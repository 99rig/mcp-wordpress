=== MCP Discovery ===
Contributors: 99rig
Tags: mcp, ai, discovery, rest-api, well-known
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 0.1.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Exposes /.well-known/mcp-server so AI agents can discover your MCP server. Implements draft-serra-mcp-discovery-uri.

== Description ==

The **MCP Discovery** plugin exposes a standard `/.well-known/mcp-server` endpoint on your WordPress site, making it discoverable by AI agents using the `mcp://` URI scheme.

Based on the IETF Internet Draft [draft-serra-mcp-discovery-uri](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/).

**Features:**
* Automatic manifest generation from WordPress settings
* WooCommerce auto-detection (adds `e-commerce` category)
* Admin settings page under Settings → MCP Discovery
* Opt-out crawling support
* CORS headers for cross-origin access
* Zero configuration required — works out of the box

**Example manifest:**

    {
      "mcp_version": "2025-06-18",
      "name": "My Shop MCP Server",
      "endpoint": "https://myshop.com/wp-json/mcp/v1",
      "transport": "http",
      "auth": { "type": "none" },
      "capabilities": ["tools", "resources"],
      "categories": ["e-commerce"],
      "crawl": true
    }

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Go to **Settings → MCP Discovery** to configure
4. Visit `https://yoursite.com/.well-known/mcp-server` to verify

== Changelog ==

= 0.1.0 =
* Initial release

== Upgrade Notice ==

= 0.1.0 =
Initial release.
