<?php
/**
 * Plugin Name: Mumble MCP Discovery
 * Plugin URI:  https://github.com/99rig/mcp-wordpress
 * Description: Exposes /.well-known/mcp-server so AI agents can discover your site via mcp://. Implements draft-serra-mcp-discovery-uri. WooCommerce-aware.
 * Version:     0.5.3
 * Author:      Mumble Group
 * Author URI:  https://mumble.group
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: mumble-mcp-discovery
 *
 * @package MumbleMCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

define( 'MMCD_VERSION', '0.5.3' );
define( 'MMCD_PATH', plugin_dir_path( __FILE__ ) );
define( 'MMCD_URL', plugin_dir_url( __FILE__ ) );

require_once MMCD_PATH . 'includes/class-mcp-manifest.php';
require_once MMCD_PATH . 'includes/class-mcp-endpoint.php';
require_once MMCD_PATH . 'includes/class-mcp-validator.php';
require_once MMCD_PATH . 'includes/class-mcp-admin.php';

function mmcd_init() {
    MMCD_Endpoint::instance()->init();
    MMCD_Admin::instance()->init();
}
add_action( 'plugins_loaded', 'mmcd_init' );

register_activation_hook( __FILE__, 'mmcd_activate' );
function mmcd_activate() {
    // Set sensible defaults on first activation
    if ( ! get_option( 'mmcd_options' ) ) {
        update_option( 'mmcd_options', array(
            'enabled' => true,
            'crawl'   => true,
        ) );
    }
}
