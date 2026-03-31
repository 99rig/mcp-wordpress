<?php
/**
 * Manifest coherence validator.
 * Checks endpoint availability, trust_class sub-fields, domain match.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Validator {

    /**
     * Run all checks against current options.
     * Returns array of [ 'level' => error|warning|info, 'code' => string, 'message' => string ]
     */
    public static function check( $options = null ) {
        if ( null === $options ) {
            $options = get_option( 'mcp_discovery_options', array() );
        }

        $errors = array();

        // Build a draft manifest for validation
        $manifest = MCP_Manifest::build();

        // 1. Endpoint presence
        $endpoint = $manifest['endpoint'] ?? '';
        if ( empty( $endpoint ) ) {
            $errors[] = array(
                'level'   => 'error',
                'code'    => 'endpoint_missing',
                'message' => 'Endpoint URL is missing. The manifest cannot be published without a valid endpoint.',
            );
        } else {
            // 2. Endpoint domain match
            $site_host     = wp_parse_url( home_url(), PHP_URL_HOST );
            $endpoint_host = wp_parse_url( $endpoint, PHP_URL_HOST );
            if ( $endpoint_host && $site_host ) {
                if ( $endpoint_host !== $site_host && ! str_ends_with( $endpoint_host, '.' . $site_host ) ) {
                    $errors[] = array(
                        'level'   => 'error',
                        'code'    => 'endpoint_domain_mismatch',
                        'message' => sprintf(
                            'Endpoint host "%s" does not match site host "%s". Per draft-04 Section 6.8 this manifest would be rejected by conforming clients.',
                            esc_html( $endpoint_host ),
                            esc_html( $site_host )
                        ),
                    );
                }
            }

            // 3. Endpoint availability (HTTP check)
            if ( ( $options['server_type'] ?? 'none' ) !== 'none' ) {
                $response = wp_remote_get( $endpoint, array(
                    'timeout'   => 5,
                    'sslverify' => false,
                ) );
                if ( is_wp_error( $response ) ) {
                    $errors[] = array(
                        'level'   => 'warning',
                        'code'    => 'endpoint_unreachable',
                        'message' => sprintf( 'Endpoint "%s" is not reachable: %s', esc_html( $endpoint ), esc_html( $response->get_error_message() ) ),
                    );
                } else {
                    $code = wp_remote_retrieve_response_code( $response );
                    if ( $code === 404 ) {
                        $errors[] = array(
                            'level'   => 'error',
                            'code'    => 'endpoint_404',
                            'message' => sprintf( 'Endpoint "%s" returns 404. The manifest declares a server that does not exist.', esc_html( $endpoint ) ),
                        );
                    } elseif ( $code >= 500 ) {
                        $errors[] = array(
                            'level'   => 'warning',
                            'code'    => 'endpoint_server_error',
                            'message' => sprintf( 'Endpoint "%s" returns HTTP %d.', esc_html( $endpoint ), $code ),
                        );
                    }
                }
            }
        }

        // 4. trust_class sub-field coherence
        $trust_class = $options['trust_class'] ?? '';

        if ( 'enterprise' === $trust_class || 'regulated' === $trust_class ) {
            $auth_type = $options['auth_type'] ?? 'none';
            if ( 'none' === $auth_type ) {
                $errors[] = array(
                    'level'   => 'error',
                    'code'    => 'enterprise_needs_auth',
                    'message' => sprintf( 'trust_class "%s" requires an authentication method. Select Bearer, API Key, OAuth 2.0, or mTLS.', esc_html( $trust_class ) ),
                );
            }
        }

        if ( 'regulated' === $trust_class ) {
            if ( empty( $options['jurisdiction'] ) ) {
                $errors[] = array(
                    'level'   => 'error',
                    'code'    => 'regulated_needs_jurisdiction',
                    'message' => 'trust_class "regulated" requires a Jurisdiction (e.g. EU, IT, US).',
                );
            }
        }

        if ( 'sandbox' === $trust_class ) {
            $expires_days = (int) ( $options['expires_days'] ?? 0 );
            if ( $expires_days < 1 || $expires_days > 365 ) {
                $errors[] = array(
                    'level'   => 'warning',
                    'code'    => 'sandbox_expires_invalid',
                    'message' => 'trust_class "sandbox" requires Manifest Expiry between 1 and 365 days.',
                );
            }
        }

        // 5. Bearer/OAuth2 need endpoint
        $auth_type = $options['auth_type'] ?? 'none';
        if ( in_array( $auth_type, array( 'bearer', 'oauth2' ), true ) && empty( $options['auth_endpoint'] ) ) {
            $errors[] = array(
                'level'   => 'warning',
                'code'    => 'auth_endpoint_missing',
                'message' => sprintf( 'Auth method "%s" should declare an Auth Endpoint URL.', esc_html( $auth_type ) ),
            );
        }

        // 6. Server type = none but endpoint is real
        if ( ( $options['server_type'] ?? '' ) === 'none' && ! empty( $endpoint ) ) {
            $errors[] = array(
                'level'   => 'info',
                'code'    => 'discovery_only',
                'message' => 'Server type is set to "Discovery only". The endpoint in the manifest points to an external server.',
            );
        }

        // If no errors, add a pass
        if ( empty( $errors ) ) {
            $errors[] = array(
                'level'   => 'success',
                'code'    => 'ok',
                'message' => 'Manifest is coherent and ready to publish.',
            );
        }

        return $errors;
    }
}
