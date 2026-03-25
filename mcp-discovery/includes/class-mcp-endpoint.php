<?php
/**
 * Registers the /.well-known/mcp-server endpoint.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Endpoint {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'init', array( $this, 'add_rewrite_rule' ) );
        add_action( 'template_redirect', array( $this, 'handle_request' ) );
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    public function add_rewrite_rule() {
        add_rewrite_rule(
            '^\.well-known/mcp-server$',
            'index.php?mcp_well_known=1',
            'top'
        );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'mcp_well_known';
        return $vars;
    }

    public function handle_request() {
        if ( ! get_query_var( 'mcp_well_known' ) ) {
            return;
        }

        $options = get_option( 'mcp_discovery_options', array() );

        // Check if plugin is enabled
        if ( isset( $options['enabled'] ) && ! $options['enabled'] ) {
            status_header( 404 );
            exit;
        }

        $manifest = MCP_Manifest::build();

        // Set headers per draft-serra-mcp-discovery-uri
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Cache-Control: max-age=3600' );
        header( 'Access-Control-Allow-Origin: *' );
        header( 'X-MCP-Version: 2025-06-18' );

        status_header( 200 );

        echo wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }
}
