# mcp-wordpress

**MCP Discovery — WordPress Plugin v0.3.0**

Exposes `/.well-known/mcp-server` on any WordPress site so AI agents
can discover it via `mcp://`. Implements
[draft-serra-mcp-discovery-uri-03](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/).

---

## What it does

Once installed, your site exposes:

```
GET https://yoursite.com/.well-known/mcp-server
```

Which returns:

```json
{
  "mcp_version": "2025-06-18",
  "name": "My Shop MCP Server",
  "endpoint": "https://yoursite.com/wp-json/mcp/v1",
  "transport": "http",
  "auth": { "type": "none" },
  "capabilities": ["tools", "resources"],
  "categories": ["e-commerce"],
  "languages": ["it"],
  "last_updated": "2026-03-25T00:00:00Z",
  "expires": "2026-09-25T00:00:00Z",
  "crawl": true
}
```

AI agents resolve `mcp://yoursite.com` and connect directly.

---

## Installation

1. Download the `mcp-discovery/` folder
2. Upload to `/wp-content/plugins/`
3. Activate in WordPress admin
4. Go to **Settings → MCP Discovery**
5. Verify at `https://yoursite.com/.well-known/mcp-server`

Or wait for approval on wordpress.org and install directly from
**Plugins → Add New → Search "MCP Discovery"**.

---

## Settings

| Field | Default | Description |
|---|---|---|
| Server Name | Site name | Human-readable name |
| Description | Site tagline | Natural language description |
| MCP Endpoint URL | REST API base | URL of your MCP endpoint |
| Authentication | None | none / apikey / oauth2 |
| Categories | auto | Comma-separated (WooCommerce auto-detected) |
| Contact Email | Admin email | Contact for the server |
| Manifest Expiry | 90 days | How long before manifest is stale |
| Allow Crawling | Yes | Opt-out of public indexing |

---

## Security (v0.3.0)

- **Endpoint domain validation** — endpoint MUST be on same domain or
  subdomain. Invalid endpoints fall back to default REST API URL.
- **Expires field** — manifest declares its own expiry date.

---

## WooCommerce

Auto-detected. Adds `e-commerce` to categories automatically.

---

## Changelog

### v0.3.0
- Security: endpoint domain validation (draft-03 Section 6.8)
- Security: `expires` field — manifest expiry date (Section 6.9)
- Admin: Manifest Expiry field in settings page

### v0.2.0
- Fixed Nginx compatibility — uses `parse_request` hook
- PHP 7.4 compatibility
- GPL-2.0 license
- Sensible defaults on activation

### v0.1.0
- Initial release

---

## Related

- [mcpstandard.dev](https://mcpstandard.dev) — specification
- [IETF Draft -03](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/03/)
- [mcp-discovery](https://github.com/99rig/mcp-discovery) — Python client
- [django-mcp-discovery](https://github.com/99rig/django-mcp-discovery) — Django package
- [GitHub Discussion #2462](https://github.com/modelcontextprotocol/modelcontextprotocol/discussions/2462)

---

## Author

Mumble Group — Milan, Italy
support@mumble.group | https://mcpstandard.dev
