<?php
/**
 * Builds the MCP manifest JSON from WordPress/WooCommerce settings.
 *
 * Compatible with WordPress 5.0+ and PHP 7.4+.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Manifest {

    /**
     * Build and return the manifest array.
     *
     * @return array
     */
    public static function build() {
        $options = get_option( 'mcp_discovery_options', array() );

        // Required fields (MUST per draft-serra-mcp-discovery-uri)
        $manifest = array(
            'mcp_version' => '2025-06-18',
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
        // Use REST API base if available (WP 4.4+), otherwise home URL
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
     * @param array $options
     * @return array
     */
    private static function get_auth( $options ) {
        $type = isset( $options['auth_type'] ) ? $options['auth_type'] : 'none';
        $allowed = array( 'none', 'apikey', 'oauth2' );
        if ( ! in_array( $type, $allowed, true ) ) {
            $type = 'none';
        }
        return array( 'type' => $type );
    }

    /**
     * Auto-detects WooCommerce and merges with user-defined categories.
     *
     * @param array $options
     * @return array
     */
    private static function get_categories( $options ) {
        $cats = array();

        // Auto-detect WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            $cats[] = 'e-commerce';
        }

        // User-defined categories (comma-separated)
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
        // e.g. it_IT → it, en_US → en
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
