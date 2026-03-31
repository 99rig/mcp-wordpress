=== MCP Discovery ===
Contributors: 99rig
Tags: mcp, ai, discovery, rest-api, woocommerce
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.5.3
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exposes /.well-known/mcp-server so AI agents can discover your site via mcp://. Implements draft-serra-mcp-discovery-uri-04. WooCommerce-aware.

== Description ==

The **MCP Discovery** plugin exposes a standard `/.well-known/mcp-server` endpoint on your WordPress site, making it discoverable by AI agents using the `mcp://` URI scheme.

Based on the IETF Internet Draft [draft-serra-mcp-discovery-uri-04](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/).

**Features:**

* Works on Apache and Nginx — no server config required
* Automatic manifest generation from WordPress settings
* WooCommerce auto-detection (adds `e-commerce` category automatically)
* Admin settings page under Settings → MCP Discovery
* Security Capability Negotiation — declare trust class, auth method, compliance
* Opt-out crawling support (`"crawl": false`)
* CORS headers for cross-origin access
* Zero configuration required — works out of the box
* Compatible with WordPress 5.0+ and PHP 7.4+

**How it works:**

Once installed, your site exposes:

    GET https://yoursite.com/.well-known/mcp-server

Which returns a manifest like:

    {
      "mcp_version": "2025-06-18",
      "name": "My Shop MCP Server",
      "endpoint": "https://yoursite.com/wp-json/mcp/v1",
      "transport": "http",
      "auth": { "required": false, "methods": ["none"] },
      "capabilities": ["tools", "resources"],
      "trust_class": "public",
      "cache_ttl": 3600,
      "crawl": true
    }

AI agents can then resolve `mcp://yoursite.com` and connect directly to your MCP endpoint.

**Settings:**

| Field | Default | Description |
|-------|---------|-------------|
| Server Name | Blog name | Human-readable server name |
| Description | Blog tagline | Natural language description |
| MCP Endpoint URL | REST API base | URL of the MCP endpoint |
| Categories | auto | Comma-separated (WooCommerce auto-detected) |
| Contact Email | Admin email | Contact for the server |
| Docs URL | — | Documentation URL |
| Allow Crawling | Yes | Opt-out of public indexing |
| Cache TTL | 3600 | Seconds clients should cache the manifest |
| Coverage | — | ISO 3166-1 country code (e.g. IT, EU, WW) |
| Auth Method | None | none / bearer / apikey / oauth2 / mtls |
| Auth Endpoint | — | Required for bearer and OAuth 2.0 |
| Trust Class | — | public / sandbox / enterprise / regulated |
| Jurisdiction | — | EU, EEA, UK or ISO 3166-1 (required for regulated) |
| Compliance Frameworks | — | GDPR, HIPAA, ISO27001, PCI-DSS, SOC2 (comma-separated) |
| Log Retention (days) | 90 | Minimum log retention for regulated servers |

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

= What is trust_class? =

The trust class declares the security posture of your server per draft-serra-mcp-discovery-uri-04 Section 6.10. If left empty, clients treat your server as "public". Use "regulated" for servers subject to GDPR or other compliance requirements.

= What WordPress versions are supported? =

WordPress 5.0 and later. PHP 7.4 and later.

== Changelog ==

= 0.4.1 =
* Added coverage field (ISO 3166-1 country code)
* Fixed stable tag mismatch in readme

= 0.4.0 =
* Aligned with draft-serra-mcp-discovery-uri-04
* auth field now uses structured object {required, methods[]} per Section 6.10.4
* Core auth vocabulary: none, bearer, mtls, apikey, oauth2 + x- extensions
* New trust_class field (public / sandbox / enterprise / regulated) per Section 6.10.2
* New compliance object with jurisdiction and frameworks for regulated servers
* New logging object with retention_days for regulated servers
* New cache_ttl field (default 3600s)
* mcp_version now configurable via WordPress option mcp_protocol_version
* Admin panel reorganized into General, Authentication, Security Posture, Compliance sections

= 0.3.0 =
* Security: endpoint domain validation (draft-03 Section 6.8)
* Security: expires field — manifest expiry date (Section 6.9)
* Admin: Manifest Expiry field in settings page

= 0.2.0 =
* Fixed Nginx compatibility — uses parse_request hook
* PHP 7.4 compatibility
* GPL-2.0 license
* Sensible defaults on activation

= 0.1.0 =
* Initial release

== Upgrade Notice ==

= 0.4.0 =
Major update: auth format changed to structured object. Existing manifests remain valid — the new format is backward compatible with conforming clients.

== Related ==

* [mcpstandard.dev](https://mcpstandard.dev) — specification and reference implementation
* [IETF Draft -04](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/)
* [GitHub](https://github.com/99rig/mcp-wordpress) — source code
* [django-mcp-discovery](https://pypi.org/project/django-mcp-discovery/) — Django package
