<?php
/**
 * Admin settings page for MCP Discovery.
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
        $output['enabled']     = ! empty( $input['enabled'] );
        $output['name']        = sanitize_text_field( $input['name'] ?? '' );
        $output['description'] = sanitize_textarea_field( $input['description'] ?? '' );
        $output['endpoint']    = esc_url_raw( $input['endpoint'] ?? '' );
        $output['auth_type']   = in_array( $input['auth_type'] ?? 'none', array( 'none', 'apikey', 'oauth2' ) ) ? $input['auth_type'] : 'none';
        $output['categories']  = sanitize_text_field( $input['categories'] ?? '' );
        $output['contact']     = sanitize_email( $input['contact'] ?? '' );
        $output['docs']        = esc_url_raw( $input['docs'] ?? '' );
        $output['crawl']       = ! empty( $input['crawl'] );
        return $output;
    }

    public function render_page() {
        $options      = get_option( 'mcp_discovery_options', array() );
        $manifest_url = home_url( '/.well-known/mcp-server' );
        $mcp_uri      = 'mcp://' . wp_parse_url( home_url(), PHP_URL_HOST );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'MCP Discovery', 'mcp-discovery' ); ?></h1>
            <p><?php
                // translators: %s is the URL to the IETF draft specification.
                printf(
                    wp_kses(
                        __( 'This plugin exposes <code>/.well-known/mcp-server</code> so AI agents can discover your MCP server. Implements <a href="%s" target="_blank">draft-serra-mcp-discovery-uri</a>.', 'mcp-discovery' ),
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
                        <th><?php esc_html_e( 'Authentication', 'mcp-discovery' ); ?></th>
                        <td>
                            <select name="mcp_discovery_options[auth_type]">
                                <option value="none" <?php selected( $options['auth_type'] ?? 'none', 'none' ); ?>>None</option>
                                <option value="apikey" <?php selected( $options['auth_type'] ?? 'none', 'apikey' ); ?>>API Key</option>
                                <option value="oauth2" <?php selected( $options['auth_type'] ?? 'none', 'oauth2' ); ?>>OAuth 2.0</option>
                            </select>
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
                        <th><?php esc_html_e( 'Manifest Expiry (days)', 'mcp-discovery' ); ?></th>
                        <td>
                            <input type="number" name="mcp_discovery_options[expires_days]" min="1" max="365"
                                value="<?php echo esc_attr( $options['expires_days'] ?? 90 ); ?>" style="width:80px" />
                            <p class="description"><?php esc_html_e( 'How many days before the manifest expires and must be re-fetched (1-365). Default: 90.', 'mcp-discovery' ); ?></p>
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
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
