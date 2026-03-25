<?php
/**
 * Plugin Name: MCP Discovery
 * Plugin URI:  https://mcpstandard.dev
 * Description: Exposes a /.well-known/mcp-server manifest for MCP server discovery. Implements draft-serra-mcp-discovery-uri.
 * Version:     0.1.0
 * Author:      Marco Serra
 * Author URI:  https://mcpstandard.dev
 * License:     MIT
 * Text Domain: mcp-discovery
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

define( 'MCP_DISCOVERY_VERSION', '0.1.0' );
define( 'MCP_DISCOVERY_PATH', plugin_dir_path( __FILE__ ) );
define( 'MCP_DISCOVERY_URL', plugin_dir_url( __FILE__ ) );

require_once MCP_DISCOVERY_PATH . 'includes/class-mcp-manifest.php';
require_once MCP_DISCOVERY_PATH . 'includes/class-mcp-endpoint.php';
require_once MCP_DISCOVERY_PATH . 'includes/class-mcp-admin.php';

function mcp_discovery_init() {
    MCP_Endpoint::instance()->init();
    MCP_Admin::instance()->init();
}
add_action( 'init', 'mcp_discovery_init' );

register_activation_hook( __FILE__, 'mcp_discovery_activate' );
function mcp_discovery_activate() {
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'mcp_discovery_deactivate' );
function mcp_discovery_deactivate() {
    flush_rewrite_rules();
}
