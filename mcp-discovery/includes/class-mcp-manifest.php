<?php
/**
 * Builds the MCP manifest JSON from WordPress/WooCommerce settings.
 * Compatible with WordPress 5.0+ and PHP 7.4+.
 * Implements draft-serra-mcp-discovery-uri-04.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Manifest {

    const DEFAULT_PROTOCOL_VERSION = '2025-06-18';

    public static function build() {
        $options     = get_option( 'mcp_discovery_options', array() );
        $mcp_version = get_option( 'mcp_protocol_version', self::DEFAULT_PROTOCOL_VERSION );
        $server_type = $options['server_type'] ?? 'none';

        $manifest = array(
            'mcp_version' => $mcp_version,
            'name'        => self::get_name( $options ),
            'endpoint'    => self::get_endpoint( $options, $server_type ),
            'transport'   => 'http',
        );

        $description = self::get_description( $options );
        if ( $description ) {
            $manifest['description'] = $description;
        }

        $manifest['auth']         = self::get_auth( $options );
        $manifest['capabilities'] = array( 'tools', 'resources' );

        $categories = self::get_categories( $options );
        if ( ! empty( $categories ) ) {
            $manifest['categories'] = $categories;
        }

        $languages = self::get_languages();
        if ( ! empty( $languages ) ) {
            $manifest['languages'] = $languages;
        }

        $coverage = ! empty( $options['coverage'] ) ? strtoupper( sanitize_text_field( $options['coverage'] ) ) : '';
        if ( $coverage ) {
            $manifest['coverage'] = $coverage;
        }

        $contact = self::get_contact( $options );
        if ( $contact ) {
            $manifest['contact'] = $contact;
        }

        $docs = ! empty( $options['docs'] ) ? esc_url_raw( $options['docs'] ) : '';
        if ( $docs ) {
            $manifest['docs'] = $docs;
        }

        $manifest['last_updated'] = gmdate( 'c' );
        $manifest['crawl']        = isset( $options['crawl'] ) ? (bool) $options['crawl'] : true;
        $manifest['cache_ttl']    = isset( $options['cache_ttl'] ) ? (int) $options['cache_ttl'] : 3600;

        // trust_class (MAY)
        $trust_class   = $options['trust_class'] ?? '';
        $allowed_trust = array( 'public', 'sandbox', 'enterprise', 'regulated' );

        if ( in_array( $trust_class, $allowed_trust, true ) ) {
            $manifest['trust_class'] = $trust_class;

            if ( 'sandbox' === $trust_class ) {
                $expires_days        = isset( $options['expires_days'] ) ? (int) $options['expires_days'] : 30;
                $manifest['expires'] = gmdate( 'c', strtotime( "+{$expires_days} days" ) );
            }

            if ( 'regulated' === $trust_class ) {
                $jurisdiction = isset( $options['jurisdiction'] ) ? sanitize_text_field( $options['jurisdiction'] ) : '';
                if ( $jurisdiction ) {
                    $compliance = array( 'jurisdiction' => $jurisdiction );
                    $frameworks = isset( $options['frameworks'] ) ? sanitize_text_field( $options['frameworks'] ) : '';
                    if ( $frameworks ) {
                        $compliance['frameworks'] = array_map( 'trim', explode( ',', $frameworks ) );
                    }
                    $manifest['compliance'] = $compliance;
                }
                $manifest['logging'] = array(
                    'required'       => true,
                    'retention_days' => isset( $options['log_retention_days'] ) ? (int) $options['log_retention_days'] : 90,
                );
                if ( ! isset( $options['cache_ttl'] ) ) {
                    $manifest['cache_ttl'] = 300;
                }
            }
        }

        return $manifest;
    }

    private static function get_endpoint( $options, $server_type ) {
        // Internal WordPress server — always use REST API URL
        if ( 'internal' === $server_type ) {
            return function_exists( 'rest_url' ) ? rest_url( 'mcp/v1' ) : home_url( '/wp-json/mcp/v1' );
        }
        // External or discovery-only — use configured endpoint
        if ( ! empty( $options['endpoint'] ) ) {
            return esc_url_raw( $options['endpoint'] );
        }
        // Fallback
        return function_exists( 'rest_url' ) ? rest_url( 'mcp/v1' ) : home_url( '/mcp' );
    }

    private static function get_name( $options ) {
        if ( ! empty( $options['name'] ) ) {
            return sanitize_text_field( $options['name'] );
        }
        return get_bloginfo( 'name' ) . ' MCP Server';
    }

    private static function get_description( $options ) {
        if ( ! empty( $options['description'] ) ) {
            return sanitize_textarea_field( $options['description'] );
        }
        return get_bloginfo( 'description' );
    }

    private static function get_auth( $options ) {
        $method       = isset( $options['auth_type'] ) ? $options['auth_type'] : 'none';
        $allowed_core = array( 'none', 'bearer', 'mtls', 'apikey', 'oauth2' );

        if ( ! in_array( $method, $allowed_core, true ) ) {
            if ( strpos( $method, 'x-' ) !== 0 ) {
                $method = 'none';
            }
        }

        $auth = array(
            'required' => ( 'none' !== $method ),
            'methods'  => array( $method ),
        );

        if ( in_array( $method, array( 'bearer', 'oauth2' ), true ) ) {
            $endpoint = ! empty( $options['auth_endpoint'] ) ? esc_url_raw( $options['auth_endpoint'] ) : '';
            if ( $endpoint ) {
                $auth['endpoint'] = $endpoint;
            }
        }

        return $auth;
    }

    private static function get_categories( $options ) {
        $cats = array();
        if ( class_exists( 'WooCommerce' ) ) {
            $cats[] = 'e-commerce';
        }
        if ( ! empty( $options['categories'] ) ) {
            $user_cats = array_map( 'trim', explode( ',', $options['categories'] ) );
            $user_cats = array_map( 'sanitize_text_field', $user_cats );
            $cats      = array_unique( array_merge( $cats, $user_cats ) );
        }
        return array_values( array_filter( $cats ) );
    }

    private static function get_languages() {
        $locale = get_locale();
        $lang   = strtolower( substr( $locale, 0, 2 ) );
        return array( $lang );
    }

    private static function get_contact( $options ) {
        if ( ! empty( $options['contact'] ) ) {
            return sanitize_email( $options['contact'] );
        }
        return sanitize_email( get_option( 'admin_email', '' ) );
    }
}
