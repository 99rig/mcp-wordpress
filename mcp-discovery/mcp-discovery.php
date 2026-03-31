<?php
/**
 * Plugin Name: MCP Discovery
 * Plugin URI:  https://github.com/99rig/mcp-wordpress
 * Description: Exposes /.well-known/mcp-server so AI agents can discover your site via mcp://. Implements draft-serra-mcp-discovery-uri. WooCommerce-aware.
 * Version:     0.4.1
 * Author:      Mumble Group
 * Author URI:  https://mumble.group
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: mcp-discovery
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

define( 'MCP_DISCOVERY_VERSION', '0.4.1' );
define( 'MCP_DISCOVERY_PATH', plugin_dir_path( __FILE__ ) );
define( 'MCP_DISCOVERY_URL', plugin_dir_url( __FILE__ ) );

require_once MCP_DISCOVERY_PATH . 'includes/class-mcp-manifest.php';
require_once MCP_DISCOVERY_PATH . 'includes/class-mcp-endpoint.php';
require_once MCP_DISCOVERY_PATH . 'includes/class-mcp-admin.php';

function mcp_discovery_init() {
    MCP_Endpoint::instance()->init();
    MCP_Admin::instance()->init();
}
add_action( 'plugins_loaded', 'mcp_discovery_init' );

register_activation_hook( __FILE__, 'mcp_discovery_activate' );
function mcp_discovery_activate() {
    // Set sensible defaults on first activation
    if ( ! get_option( 'mcp_discovery_options' ) ) {
        update_option( 'mcp_discovery_options', array(
            'enabled' => true,
            'crawl'   => true,
        ) );
    }
}
