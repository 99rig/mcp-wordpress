# mcp-wordpress

**MCP Discovery — WordPress Plugin v0.4.1**

Exposes `/.well-known/mcp-server` on any WordPress site so AI agents
can discover it via `mcp://`. Implements
[draft-serra-mcp-discovery-uri-04](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/).

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
  "auth": {
    "required": false,
    "methods": ["none"]
  },
  "capabilities": ["tools", "resources"],
  "categories": ["e-commerce"],
  "languages": ["it"],
  "coverage": "IT",
  "trust_class": "public",
  "cache_ttl": 3600,
  "last_updated": "2026-03-31T00:00:00Z",
  "crawl": true
}
```

AI agents resolve `mcp://yoursite.com` and connect directly.

## Installation

1. Download `mcp-discovery.zip` from [mcpstandard.dev](https://mcpstandard.dev/mcp-discovery.zip)
2. Go to **Plugins → Add New → Upload Plugin**
3. Activate and go to **Settings → MCP Discovery**
4. Verify at `https://yoursite.com/.well-known/mcp-server`

Or wait for approval on wordpress.org and install directly from
**Plugins → Add New → Search "MCP Discovery"**.

## Settings

### General

| Field | Default | Description |
|---|---|---|
| Server Name | Site name | Human-readable name |
| Description | Site tagline | Natural language description |
| MCP Endpoint URL | REST API base | URL of your MCP endpoint |
| Categories | auto | Comma-separated (WooCommerce auto-detected) |
| Contact Email | Admin email | Contact for the server |
| Docs URL | | Link to MCP documentation |
| Allow Crawling | Yes | Opt-out of public indexing |
| Cache TTL | 3600 | Seconds clients should cache the manifest |
| Coverage | | ISO 3166-1 country code (e.g. IT, EU, WW) |

### Authentication

| Field | Default | Description |
|---|---|---|
| Auth Method | None | none / bearer / apikey / oauth2 / mtls |
| Auth Endpoint URL | | Required for Bearer and OAuth 2.0 |

### Security Posture

Declares the server trust class per draft-04 Section 6.10.

| Field | Default | Description |
|---|---|---|
| Trust Class | (not declared) | public / sandbox / enterprise / regulated |
| Manifest Expiry | 30 days | Required when trust class is "sandbox" |

### Compliance (regulated only)

| Field | Description |
|---|---|
| Jurisdiction | ISO 3166-1 or regional code: EU, EEA, UK, IT, DE, US... |
| Compliance Frameworks | Comma-separated: GDPR, HIPAA, ISO27001, PCI-DSS, SOC2 |
| Log Retention (days) | Minimum log retention declared to clients |

## Trust Class

If `trust_class` is not declared, clients treat the server as `public`.
Absence is never interpreted as elevated privilege.

| Value | Behaviour |
|---|---|
| `public` | No restrictions. Manifest cached freely. |
| `sandbox` | Non-production. Clients warn users. `expires` auto-generated. |
| `enterprise` | Controlled access. Auth required. |
| `regulated` | Compliance regime declared. Auth + compliance + logging required. |

## Security

- **Endpoint domain validation** — endpoint MUST be on same domain or subdomain.
- **Auth core vocabulary** — none, bearer, mtls, apikey, oauth2. Extensions use `x-` prefix.
- **Compliance object** — jurisdiction + frameworks for regulated servers.

## WooCommerce

Auto-detected. Adds `e-commerce` to categories automatically.

## Changelog

### v0.4.1
- Added `coverage` field (ISO 3166-1 country code) to admin panel and manifest

### v0.4.0
- Aligned to draft-serra-mcp-discovery-uri-04
- `auth` now structured object `{required, methods[]}` per Section 6.10.4
- New `trust_class` field (public/sandbox/enterprise/regulated) per Section 6.10.2
- New `compliance` object with jurisdiction and frameworks for regulated servers
- New `logging` object with retention_days for regulated servers
- New `cache_ttl` field (default 3600)
- `mcp_version` now configurable via WordPress option `mcp_protocol_version`
- Admin panel reorganised into sections: General, Authentication, Security Posture, Compliance

### v0.3.0
- Endpoint domain validation (draft-03 Section 6.8)
- `expires` field — manifest expiry date (Section 6.9)

### v0.2.0
- Fixed Nginx compatibility
- PHP 7.4 compatibility
- GPL-2.0 license

### v0.1.0
- Initial release

## Related

- [mcpstandard.dev](https://mcpstandard.dev) — specification and reference implementation
- [IETF Draft -04](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/04/)
- [GitHub Discussion](https://github.com/99rig/mcp-discovery/discussions/1)
- [mcp-discovery](https://github.com/99rig/mcp-discovery) — Python client
- [django-mcp-discovery](https://github.com/99rig/django-mcp-discovery) — Django package
- [django-mcp-server](https://github.com/99rig/django-mcp-server) — Full MCP server for Django

## Author

Mumble Group — Milan, Italy
support@mumble.group | https://mcpstandard.dev
