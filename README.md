# mcp-wordpress

**MCP Discovery — WordPress Plugin**

Exposes `/.well-known/mcp-server` on any WordPress site, making it discoverable by AI agents using the `mcp://` URI scheme.

Implements [draft-serra-mcp-discovery-uri](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/).

---

## What it does

Once installed, any WordPress site exposes:

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
  "crawl": true
}
```

AI agents can then resolve `mcp://yoursite.com` and connect directly.

---

## Installation

1. Download the `mcp-discovery/` folder
2. Upload to `/wp-content/plugins/`
3. Activate in WordPress admin
4. Go to **Settings → MCP Discovery**
5. Verify at `https://yoursite.com/.well-known/mcp-server`

---

## WooCommerce integration

The plugin auto-detects WooCommerce and adds `e-commerce` to the categories array automatically.

Full WooCommerce tool integration (search_products, check_stock, get_price) is planned for v0.2.0.

---

## Related

- [mcpstandard.dev](https://mcpstandard.dev) — specification and reference implementation
- [IETF Draft](https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/) — formal specification
- [mcp-discovery](https://github.com/99rig/mcp-discovery) — Python client and validator
- [GitHub Discussion #2462](https://github.com/modelcontextprotocol/modelcontextprotocol/discussions/2462) — community discussion

---

## Author

Marco Serra — Mumble Group — Milan, Italy
marco.serra@mumble.group | https://mcpstandard.dev
