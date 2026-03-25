<?php
/**
 * Builds the MCP manifest JSON from WordPress/WooCommerce settings.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Manifest {

    /**
     * Build and return the manifest array.
     */
    public static function build() {
        $options = get_option( 'mcp_discovery_options', array() );

        $manifest = array(
            'mcp_version' => '2025-06-18',
            'name'        => self::get_name( $options ),
            'endpoint'    => self::get_endpoint( $options ),
            'transport'   => 'http',
        );

        // SHOULD fields
        $description = self::get_description( $options );
        if ( $description ) {
            $manifest['description'] = $description;
        }

        $manifest['auth']         = self::get_auth( $options );
        $manifest['capabilities'] = self::get_capabilities();

        // MAY fields
        $categories = self::get_categories( $options );
        if ( ! empty( $categories ) ) {
            $manifest['categories'] = $categories;
        }

        $languages = self::get_languages();
        if ( ! empty( $languages ) ) {
            $manifest['languages'] = $languages;
        }

        $contact = isset( $options['contact'] ) ? sanitize_email( $options['contact'] ) : get_option( 'admin_email' );
        if ( $contact ) {
            $manifest['contact'] = $contact;
        }

        $manifest['docs']         = isset( $options['docs'] ) ? esc_url( $options['docs'] ) : '';
        $manifest['last_updated'] = gmdate( 'c' );
        $manifest['crawl']        = isset( $options['crawl'] ) ? (bool) $options['crawl'] : true;

        // Remove empty values
        $manifest = array_filter( $manifest, function( $v ) {
            return $v !== '' && $v !== null;
        });

        return $manifest;
    }

    private static function get_name( $options ) {
        if ( ! empty( $options['name'] ) ) {
            return sanitize_text_field( $options['name'] );
        }
        return get_bloginfo( 'name' ) . ' MCP Server';
    }

    private static function get_endpoint( $options ) {
        if ( ! empty( $options['endpoint'] ) ) {
            return esc_url( $options['endpoint'] );
        }
        return rest_url( 'mcp/v1' );
    }

    private static function get_description( $options ) {
        if ( ! empty( $options['description'] ) ) {
            return sanitize_textarea_field( $options['description'] );
        }
        $tagline = get_bloginfo( 'description' );
        return $tagline ?: '';
    }

    private static function get_auth( $options ) {
        $type = isset( $options['auth_type'] ) ? $options['auth_type'] : 'none';
        if ( $type === 'none' ) {
            return array( 'type' => 'none' );
        }
        return array( 'type' => sanitize_text_field( $type ) );
    }

    private static function get_capabilities() {
        $caps = array( 'tools', 'resources' );
        return $caps;
    }

    private static function get_categories( $options ) {
        $cats = array();

        // Auto-detect WooCommerce
        if ( class_exists( 'WooCommerce' ) ) {
            $cats[] = 'e-commerce';
        }

        // User-defined categories
        if ( ! empty( $options['categories'] ) ) {
            $user_cats = array_map( 'sanitize_text_field', explode( ',', $options['categories'] ) );
            $cats      = array_unique( array_merge( $cats, $user_cats ) );
        }

        return array_values( array_filter( $cats ) );
    }

    private static function get_languages() {
        $locale = get_locale();
        // Convert WordPress locale (e.g. it_IT) to ISO 639-1 (e.g. it)
        $lang = strtolower( substr( $locale, 0, 2 ) );
        return array( $lang );
    }
}
