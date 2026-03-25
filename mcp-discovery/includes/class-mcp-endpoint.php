<?php
/**
 * Registers the /.well-known/mcp-server endpoint.
 *
 * Works on both Apache and Nginx deployments by intercepting
 * the request at the earliest possible WordPress hook.
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
        /*
         * Intercept at 'parse_request' — fires before template_redirect
         * and before WordPress tries to match rewrite rules.
         * This works on both Apache and Nginx regardless of server-level
         * .well-known handling, because we check $_SERVER['REQUEST_URI']
         * directly.
         */
        add_action( 'parse_request', array( $this, 'handle_request' ), 1 );
    }

    public function handle_request() {
        $uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

        // Normalize: strip query string and trailing slash
        $path = strtok( $uri, '?' );
        $path = rtrim( $path, '/' );

        if ( $path !== '/.well-known/mcp-server' ) {
            return;
        }

        $options = get_option( 'mcp_discovery_options', array() );

        // Return 404 if plugin is explicitly disabled
        if ( array_key_exists( 'enabled', $options ) && ! $options['enabled'] ) {
            status_header( 404 );
            exit;
        }

        $manifest = MCP_Manifest::build();

        // Headers per draft-serra-mcp-discovery-uri
        if ( ! headers_sent() ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Cache-Control: max-age=3600, public' );
            header( 'Access-Control-Allow-Origin: *' );
            header( 'Access-Control-Allow-Methods: GET' );
            header( 'X-MCP-Discovery: draft-serra-mcp-discovery-uri-01' );
        }

        status_header( 200 );
        echo wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
        exit;
    }
}
