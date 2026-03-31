<?php
/**
 * Builds the MCP manifest JSON from WordPress/WooCommerce settings.
 *
 * Compatible with WordPress 5.0+ and PHP 7.4+.
 * Implements draft-serra-mcp-discovery-uri-04.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Manifest {

    /**
     * Default MCP protocol version.
     * Override via WordPress option 'mcp_protocol_version'.
     */
    const DEFAULT_PROTOCOL_VERSION = '2025-06-18';

    /**
     * Build and return the manifest array.
     *
     * @return array
     */
    public static function build() {
        $options = get_option( 'mcp_discovery_options', array() );

        // MCP protocol version — configurable
        $mcp_version = get_option( 'mcp_protocol_version', self::DEFAULT_PROTOCOL_VERSION );

        // Required fields (MUST per draft-serra-mcp-discovery-uri-04)
        $manifest = array(
            'mcp_version' => $mcp_version,
            'name'        => self::get_name( $options ),
            'endpoint'    => self::get_endpoint( $options ),
            'transport'   => 'http',
        );

        // Recommended fields (SHOULD)
        $description = self::get_description( $options );
        if ( $description ) {
            $manifest['description'] = $description;
        }
        $manifest['auth']         = self::get_auth( $options );
        $manifest['capabilities'] = array( 'tools', 'resources' );

        // Optional fields (MAY)
        $categories = self::get_categories( $options );
        if ( ! empty( $categories ) ) {
            $manifest['categories'] = $categories;
        }

        $languages = self::get_languages();
        if ( ! empty( $languages ) ) {
            $manifest['languages'] = $languages;
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

        // crawl defaults to true — opt-out is explicit
        $manifest['crawl'] = isset( $options['crawl'] ) ? (bool) $options['crawl'] : true;

        // cache_ttl (MAY) — default 3600
        $cache_ttl = isset( $options['cache_ttl'] ) ? (int) $options['cache_ttl'] : 3600;
        $manifest['cache_ttl'] = $cache_ttl;

        // trust_class (MAY) — draft-04 Section 6.10.2
        // If absent, clients MUST treat as "public". Absence is never elevated privilege.
        $trust_class = isset( $options['trust_class'] ) ? $options['trust_class'] : '';
        $allowed_trust = array( 'public', 'sandbox', 'enterprise', 'regulated' );

        if ( in_array( $trust_class, $allowed_trust, true ) ) {
            $manifest['trust_class'] = $trust_class;

            // sandbox: expires REQUIRED
            if ( 'sandbox' === $trust_class ) {
                $expires_days = isset( $options['expires_days'] ) ? (int) $options['expires_days'] : 30;
                $manifest['expires'] = gmdate( 'c', strtotime( "+{$expires_days} days" ) );
            }

            // regulated: compliance + logging REQUIRED
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
                // regulated: cache_ttl REQUIRED — use shorter default
                if ( ! isset( $options['cache_ttl'] ) ) {
                    $manifest['cache_ttl'] = 300;
                }
            }
        }

        return $manifest;
    }

    /**
     * @param array $options
     * @return string
     */
    private static function get_name( $options ) {
        if ( ! empty( $options['name'] ) ) {
            return sanitize_text_field( $options['name'] );
        }
        return get_bloginfo( 'name' ) . ' MCP Server';
    }

    /**
     * @param array $options
     * @return string
     */
    private static function get_endpoint( $options ) {
        if ( ! empty( $options['endpoint'] ) ) {
            return esc_url_raw( $options['endpoint'] );
        }
        if ( function_exists( 'rest_url' ) ) {
            return rest_url( 'mcp/v1' );
        }
        return home_url( '/mcp' );
    }

    /**
     * @param array $options
     * @return string
     */
    private static function get_description( $options ) {
        if ( ! empty( $options['description'] ) ) {
            return sanitize_textarea_field( $options['description'] );
        }
        return get_bloginfo( 'description' );
    }

    /**
     * Builds auth object per draft-04 Section 6.10.4.
     * Core vocabulary: none, bearer, mtls, apikey, oauth2.
     * Extensions must use x- prefix.
     *
     * @param array $options
     * @return array
     */
    private static function get_auth( $options ) {
        $method = isset( $options['auth_type'] ) ? $options['auth_type'] : 'none';
        $allowed_core = array( 'none', 'bearer', 'mtls', 'apikey', 'oauth2' );

        // Validate — accept core vocabulary or x- prefixed extensions
        if ( ! in_array( $method, $allowed_core, true ) ) {
            if ( strpos( $method, 'x-' ) !== 0 ) {
                $method = 'none';
            }
        }

        $auth = array(
            'required' => ( 'none' !== $method ),
            'methods'  => array( $method ),
        );

        // endpoint REQUIRED for bearer and oauth2
        if ( in_array( $method, array( 'bearer', 'oauth2' ), true ) ) {
            $endpoint = ! empty( $options['auth_endpoint'] ) ? esc_url_raw( $options['auth_endpoint'] ) : '';
            if ( $endpoint ) {
                $auth['endpoint'] = $endpoint;
            }
        }

        return $auth;
    }

    /**
     * Auto-detects WooCommerce and merges with user-defined categories.
     *
     * @param array $options
     * @return array
     */
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

    /**
     * Returns ISO 639-1 language code from WordPress locale.
     *
     * @return array
     */
    private static function get_languages() {
        $locale = get_locale();
        $lang = strtolower( substr( $locale, 0, 2 ) );
        return array( $lang );
    }

    /**
     * @param array $options
     * @return string
     */
    private static function get_contact( $options ) {
        if ( ! empty( $options['contact'] ) ) {
            return sanitize_email( $options['contact'] );
        }
        return sanitize_email( get_option( 'admin_email', '' ) );
    }
}
