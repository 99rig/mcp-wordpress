<?php
/**
 * Admin settings page for MCP Discovery.
 * Implements draft-serra-mcp-discovery-uri-04.
 *
 * @package MCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MCP_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu() {
        add_options_page(
            __( 'MCP Discovery', 'mcp-discovery' ),
            __( 'MCP Discovery', 'mcp-discovery' ),
            'manage_options',
            'mcp-discovery',
            array( $this, 'render_page' )
        );
    }

    public function register_settings() {
        register_setting( 'mcp_discovery', 'mcp_discovery_options', array(
            'sanitize_callback' => array( $this, 'sanitize_options' ),
        ) );
    }

    public function sanitize_options( $input ) {
        $output = array();

        // Base
        $output['enabled']     = ! empty( $input['enabled'] );
        $output['name']        = sanitize_text_field( $input['name'] ?? '' );
        $output['description'] = sanitize_textarea_field( $input['description'] ?? '' );
        $output['endpoint']    = esc_url_raw( $input['endpoint'] ?? '' );
        $output['categories']  = sanitize_text_field( $input['categories'] ?? '' );
        $output['contact']     = sanitize_email( $input['contact'] ?? '' );
        $output['docs']        = esc_url_raw( $input['docs'] ?? '' );
        $output['crawl']       = ! empty( $input['crawl'] );

        // Auth — draft-04 core vocabulary + x- extensions
        $allowed_auth = array( 'none', 'bearer', 'mtls', 'apikey', 'oauth2' );
        $auth_type = $input['auth_type'] ?? 'none';
        if ( ! in_array( $auth_type, $allowed_auth, true ) ) {
            if ( strpos( $auth_type, 'x-' ) !== 0 ) {
                $auth_type = 'none';
            }
        }
        $output['auth_type']     = $auth_type;
        $output['auth_endpoint'] = esc_url_raw( $input['auth_endpoint'] ?? '' );

        // trust_class — draft-04 Section 6.10.2
        $allowed_trust = array( '', 'public', 'sandbox', 'enterprise', 'regulated' );
        $output['trust_class'] = in_array( $input['trust_class'] ?? '', $allowed_trust, true )
            ? $input['trust_class']
            : '';

        // sandbox
        $output['expires_days'] = min( 365, max( 1, (int) ( $input['expires_days'] ?? 30 ) ) );

        // regulated
        $output['jurisdiction']      = sanitize_text_field( $input['jurisdiction'] ?? '' );
        $output['frameworks']        = sanitize_text_field( $input['frameworks'] ?? '' );
        $output['log_retention_days'] = max( 1, (int) ( $input['log_retention_days'] ?? 90 ) );

        // cache_ttl
        $output['cache_ttl'] = max( 60, (int) ( $input['cache_ttl'] ?? 3600 ) );

        return $output;
    }

    public function render_page() {
        $options      = get_option( 'mcp_discovery_options', array() );
        $manifest_url = home_url( '/.well-known/mcp-server' );
        $mcp_uri      = 'mcp://' . wp_parse_url( home_url(), PHP_URL_HOST );
        $trust_class  = $options['trust_class'] ?? '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MCP Discovery', 'mcp-discovery' ); ?></h1>
            <p><?php
                printf(
                    wp_kses(
                        __( 'This plugin exposes <code>/.well-known/mcp-server</code> so AI agents can discover your MCP server. Implements <a href="%s" target="_blank">draft-serra-mcp-discovery-uri-04</a>.', 'mcp-discovery' ),
                        array( 'code' => array(), 'a' => array( 'href' => array(), 'target' => array() ) )
                    ),
                    esc_url( 'https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/' )
                );
            ?></p>

            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e( 'Your MCP URI:', 'mcp-discovery' ); ?></strong>
                    <code><?php echo esc_html( $mcp_uri ); ?></code>
                    &nbsp;|&nbsp;
                    <a href="<?php echo esc_url( $manifest_url ); ?>" target="_blank">
                        <?php esc_html_e( 'View manifest', 'mcp-discovery' ); ?>
                    </a>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'mcp_discovery' ); ?>

                <h2><?php esc_html_e( 'General', 'mcp-discovery' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Enable', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="checkbox" name="mcp_discovery_options[enabled]" value="1"
                                <?php checked( $options['enabled'] ?? true ); ?> />
                            <?php esc_html_e( 'Expose /.well-known/mcp-server', 'mcp-discovery' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Server Name', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="text" name="mcp_discovery_options[name]" class="regular-text"
                                value="<?php echo esc_attr( $options['name'] ?? '' ); ?>"
                                placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) . ' MCP Server' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Description', 'mcp-discovery' ); ?></th>
                        <td>
                            <textarea name="mcp_discovery_options[description]" class="large-text" rows="3"
                                placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $options['description'] ?? '' ); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'MCP Endpoint URL', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="url" name="mcp_discovery_options[endpoint]" class="regular-text"
                                value="<?php echo esc_attr( $options['endpoint'] ?? '' ); ?>"
                                placeholder="<?php echo esc_attr( rest_url( 'mcp/v1' ) ); ?>" />
                            <p class="description"><?php esc_html_e( 'Leave empty to use the default REST API endpoint.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Categories', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="text" name="mcp_discovery_options[categories]" class="regular-text"
                                value="<?php echo esc_attr( $options['categories'] ?? '' ); ?>"
                                placeholder="e-commerce, hardware, fashion" />
                            <p class="description"><?php esc_html_e( 'Comma-separated. WooCommerce adds "e-commerce" automatically.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Contact Email', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="email" name="mcp_discovery_options[contact]" class="regular-text"
                                value="<?php echo esc_attr( $options['contact'] ?? get_option( 'admin_email' ) ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Docs URL', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="url" name="mcp_discovery_options[docs]" class="regular-text"
                                value="<?php echo esc_attr( $options['docs'] ?? '' ); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Allow Crawling', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="checkbox" name="mcp_discovery_options[crawl]" value="1"
                                <?php checked( $options['crawl'] ?? true ); ?> />
                            <?php esc_html_e( 'Allow MCP crawlers to index this server.', 'mcp-discovery' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Cache TTL (seconds)', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="number" name="mcp_discovery_options[cache_ttl]" min="60" max="86400"
                                value="<?php echo esc_attr( $options['cache_ttl'] ?? 3600 ); ?>" style="width:100px" />
                            <p class="description"><?php esc_html_e( 'How long clients should cache this manifest. Default: 3600 (1 hour). For regulated servers use 300 or less.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Authentication', 'mcp-discovery' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Auth Method', 'mcp-discovery' ); ?></th>
                        <td>
                            <select name="mcp_discovery_options[auth_type]">
                                <option value="none"   <?php selected( $options['auth_type'] ?? 'none', 'none' ); ?>><?php esc_html_e( 'None (public)', 'mcp-discovery' ); ?></option>
                                <option value="bearer" <?php selected( $options['auth_type'] ?? 'none', 'bearer' ); ?>><?php esc_html_e( 'Bearer Token', 'mcp-discovery' ); ?></option>
                                <option value="apikey" <?php selected( $options['auth_type'] ?? 'none', 'apikey' ); ?>><?php esc_html_e( 'API Key', 'mcp-discovery' ); ?></option>
                                <option value="oauth2" <?php selected( $options['auth_type'] ?? 'none', 'oauth2' ); ?>><?php esc_html_e( 'OAuth 2.0', 'mcp-discovery' ); ?></option>
                                <option value="mtls"   <?php selected( $options['auth_type'] ?? 'none', 'mtls' ); ?>><?php esc_html_e( 'Mutual TLS', 'mcp-discovery' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Auth Endpoint URL', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="url" name="mcp_discovery_options[auth_endpoint]" class="regular-text"
                                value="<?php echo esc_attr( $options['auth_endpoint'] ?? '' ); ?>"
                                placeholder="https://yoursite.com/.well-known/oauth-authorization-server" />
                            <p class="description"><?php esc_html_e( 'Required for Bearer and OAuth 2.0 methods.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Security Posture', 'mcp-discovery' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Declares the server trust class per draft-04 Section 6.10. If left empty, clients treat this server as "public".', 'mcp-discovery' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Trust Class', 'mcp-discovery' ); ?></th>
                        <td>
                            <select name="mcp_discovery_options[trust_class]">
                                <option value=""           <?php selected( $trust_class, '' ); ?>><?php esc_html_e( '— not declared (defaults to public) —', 'mcp-discovery' ); ?></option>
                                <option value="public"     <?php selected( $trust_class, 'public' ); ?>><?php esc_html_e( 'public — no restrictions', 'mcp-discovery' ); ?></option>
                                <option value="sandbox"    <?php selected( $trust_class, 'sandbox' ); ?>><?php esc_html_e( 'sandbox — non-production / test', 'mcp-discovery' ); ?></option>
                                <option value="enterprise" <?php selected( $trust_class, 'enterprise' ); ?>><?php esc_html_e( 'enterprise — controlled access', 'mcp-discovery' ); ?></option>
                                <option value="regulated"  <?php selected( $trust_class, 'regulated' ); ?>><?php esc_html_e( 'regulated — compliance required', 'mcp-discovery' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Manifest Expiry (days)', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="number" name="mcp_discovery_options[expires_days]" min="1" max="365"
                                value="<?php echo esc_attr( $options['expires_days'] ?? 30 ); ?>" style="width:80px" />
                            <p class="description"><?php esc_html_e( 'Required when trust class is "sandbox". Default: 30 days.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Compliance (regulated only)', 'mcp-discovery' ); ?></h2>
                <p class="description"><?php esc_html_e( 'Required when trust class is "regulated".', 'mcp-discovery' ); ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Jurisdiction', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="text" name="mcp_discovery_options[jurisdiction]" class="regular-text"
                                value="<?php echo esc_attr( $options['jurisdiction'] ?? '' ); ?>"
                                placeholder="EU" />
                            <p class="description"><?php esc_html_e( 'ISO 3166-1 country code or regional code: EU, EEA, UK, IT, DE, US, etc.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Compliance Frameworks', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="text" name="mcp_discovery_options[frameworks]" class="regular-text"
                                value="<?php echo esc_attr( $options['frameworks'] ?? '' ); ?>"
                                placeholder="GDPR, ISO27001" />
                            <p class="description"><?php esc_html_e( 'Comma-separated. Examples: GDPR, HIPAA, ISO27001, PCI-DSS, SOC2.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Log Retention (days)', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="number" name="mcp_discovery_options[log_retention_days]" min="1"
                                value="<?php echo esc_attr( $options['log_retention_days'] ?? 90 ); ?>" style="width:80px" />
                            <p class="description"><?php esc_html_e( 'Minimum log retention period declared to clients. Default: 90 days.', 'mcp-discovery' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
