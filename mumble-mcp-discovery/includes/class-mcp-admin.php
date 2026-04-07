<?php
/**
 * Admin settings page for MCP Discovery — tab UI.
 * Implements draft-serra-mcp-discovery-uri-04.
 *
 * @package MumbleMCPDiscovery
 */

defined( 'ABSPATH' ) || exit;

class MMCD_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        add_action( 'admin_menu',            array( $this, 'add_menu' ) );
        add_action( 'admin_init',            array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_mmcd_validate',  array( $this, 'ajax_validate' ) );
    }

    public function enqueue_scripts( $hook ) {
        if ( 'settings_page_mumble-mcp-discovery' !== $hook ) {
            return;
        }
        wp_register_script( 'mmcd-admin', '', array( 'jquery' ), MMCD_VERSION, true );
        wp_enqueue_script( 'mmcd-admin' );
        wp_add_inline_script( 'mmcd-admin', $this->get_inline_js() );
    }

    private function get_inline_js() {
        return "
        jQuery(function($){

            // Tab switching
            $('.mmcd-tab-nav a').on('click', function(e){
                e.preventDefault();
                var target = $(this).data('tab');
                $('.mmcd-tab-nav a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                $('.mmcd-tab-panel').hide();
                $('#mmcd-tab-' + target).show();
                localStorage.setItem('mmcd_active_tab', target);
            });

            // Restore last active tab
            var saved = localStorage.getItem('mmcd_active_tab') || 'general';
            $('.mmcd-tab-nav a[data-tab=\"' + saved + '\"]').trigger('click');

            // Show/hide sections based on server type
            function toggleServerType() {
                var type = $('input[name=\"mmcd_options[server_type]\"]:checked').val();
                $('#mmcd-endpoint-row').toggle(type !== 'internal');
                $('#mmcd-internal-row').toggle(type === 'internal');
            }

            // Show/hide auth endpoint
            function toggleAuthEndpoint() {
                var method = $('#mmcd_auth_type').val();
                $('#mmcd_auth_endpoint_row').toggle(method === 'bearer' || method === 'oauth2');
            }

            // Show/hide compliance section
            function toggleCompliance() {
                var tc = $('#mmcd_trust_class').val();
                $('#mmcd-compliance-section').toggle(tc === 'regulated');
                $('#mmcd-expires-row').toggle(tc === 'sandbox');
                $('#mmcd-auth-required-note').toggle(tc === 'enterprise' || tc === 'regulated');
            }

            $('input[name=\"mmcd_options[server_type]\"]').on('change', toggleServerType);
            $('#mmcd_auth_type').on('change', toggleAuthEndpoint);
            $('#mmcd_trust_class').on('change', toggleCompliance);

            toggleServerType();
            toggleAuthEndpoint();
            toggleCompliance();

            // AJAX validate
            $('#mmcd-validate-btn').on('click', function(e){
                e.preventDefault();
                var btn = $(this);
                btn.prop('disabled', true).text('Checking...');
                $('#mmcd-validation-results').html('');
                $.post(ajaxurl, {
                    action: 'mmcd_validate',
                    nonce:  $('#mmcd_nonce').val()
                }, function(resp){
                    btn.prop('disabled', false).text('Check coherence');
                    if (!resp.success) return;
                    var html = '<ul class=\"mcp-checks\">';
                    $.each(resp.data, function(i, c){
                        var icon = {error:'✗', warning:'⚠', success:'✓', info:'ℹ'}[c.level] || 'ℹ';
                        html += '<li class=\"mcp-check-' + c.level + '\"><span class=\"mcp-icon\">' + icon + '</span> ' + c.message + '</li>';
                    });
                    html += '</ul>';
                    $('#mmcd-validation-results').html(html);
                });
            });
        });
        ";
    }

    public function ajax_validate() {
        check_ajax_referer( 'mmcd_validate_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        wp_send_json_success( MMCD_Validator::check() );
    }

    public function add_menu() {
        add_options_page(
            __( 'MCP Discovery', 'mumble-mcp-discovery' ),
            __( 'MCP Discovery', 'mumble-mcp-discovery' ),
            'manage_options',
            'mumble-mcp-discovery',
            array( $this, 'render_page' )
        );
    }

    public function register_settings() {
        register_setting( 'mmcd_settings', 'mmcd_options', array(
            'sanitize_callback' => array( $this, 'sanitize_options' ),
        ) );
    }

    public function sanitize_options( $input ) {
        $out = array();

        $allowed_types        = array( 'none', 'external', 'internal' );
        $out['server_type']   = in_array( $input['server_type'] ?? 'none', $allowed_types, true ) ? $input['server_type'] : 'none';
        $out['enabled']       = ! empty( $input['enabled'] );
        $out['name']          = sanitize_text_field( $input['name'] ?? '' );
        $out['description']   = sanitize_textarea_field( $input['description'] ?? '' );
        $out['endpoint']      = esc_url_raw( $input['endpoint'] ?? '' );
        $out['categories']    = sanitize_text_field( $input['categories'] ?? '' );
        $out['contact']       = sanitize_email( $input['contact'] ?? '' );
        $out['docs']          = esc_url_raw( $input['docs'] ?? '' );
        $out['crawl']         = ! empty( $input['crawl'] );
        $out['coverage']      = strtoupper( sanitize_text_field( $input['coverage'] ?? '' ) );
        $out['cache_ttl']     = max( 60, (int) ( $input['cache_ttl'] ?? 3600 ) );

        $allowed_auth  = array( 'none', 'bearer', 'mtls', 'apikey', 'oauth2' );
        $auth_type     = $input['auth_type'] ?? 'none';
        if ( ! in_array( $auth_type, $allowed_auth, true ) ) {
            $auth_type = ( strpos( $auth_type, 'x-' ) === 0 ) ? $auth_type : 'none';
        }
        $out['auth_type']     = $auth_type;
        $out['auth_endpoint'] = esc_url_raw( $input['auth_endpoint'] ?? '' );

        $allowed_trust  = array( '', 'public', 'sandbox', 'enterprise', 'regulated' );
        $out['trust_class']   = in_array( $input['trust_class'] ?? '', $allowed_trust, true ) ? $input['trust_class'] : '';

        $out['expires_days']       = min( 365, max( 1, (int) ( $input['expires_days'] ?? 30 ) ) );
        $out['jurisdiction']       = sanitize_text_field( $input['jurisdiction'] ?? '' );
        $out['frameworks']         = sanitize_text_field( $input['frameworks'] ?? '' );
        $out['log_retention_days'] = max( 1, (int) ( $input['log_retention_days'] ?? 90 ) );

        return $out;
    }

    public function render_page() {
        $options      = get_option( 'mmcd_options', array() );
        $manifest_url = home_url( '/.well-known/mcp-server' );
        $mcp_uri      = 'mcp://' . wp_parse_url( home_url(), PHP_URL_HOST );
        $server_type  = $options['server_type'] ?? 'none';
        $trust_class  = $options['trust_class'] ?? '';
        $auth_type    = $options['auth_type'] ?? 'none';
        $nonce        = wp_create_nonce( 'mmcd_validate_nonce' );

        $checks      = MMCD_Validator::check( $options );
        $has_errors  = ! empty( array_filter( $checks, fn( $c ) => $c['level'] === 'error' ) );
        $has_warn    = ! empty( array_filter( $checks, fn( $c ) => $c['level'] === 'warning' ) );
        $status_bg   = $has_errors ? '#fdecea' : ( $has_warn ? '#fdf6e3' : '#e6f4ea' );
        $status_bd   = $has_errors ? '#f5c2be' : ( $has_warn ? '#f5e0a0' : '#b2dfb8' );
        $status_col  = $has_errors ? '#8b2a20' : ( $has_warn ? '#7a5c00' : '#276b37' );
        $status_icon = $has_errors ? '✗' : ( $has_warn ? '⚠' : '✓' );
        $status_txt  = $has_errors ? 'Issues found' : ( $has_warn ? 'Warnings' : 'Ready' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Mumble MCP Discovery', 'mumble-mcp-discovery' ); ?></h1>
            <p style="color:#666;font-size:13px;"><?php
                // translators: %s is the URL of the IETF draft specification.
                printf(
                    wp_kses( __( 'Exposes <code>/.well-known/mcp-server</code> so AI agents can discover your site via <code>mcp://</code>. Implements <a href="%s" target="_blank">draft-serra-mcp-discovery-uri-04</a>.', 'mumble-mcp-discovery' ),
                        array( 'code' => array(), 'a' => array( 'href' => array(), 'target' => array() ) ) ),
                    esc_url( 'https://datatracker.ietf.org/doc/draft-serra-mcp-discovery-uri/' )
                );
            ?></p>

            <?php /* Status bar */ ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-radius:4px;margin-bottom:16px;background:<?php echo esc_attr($status_bg); ?>;border:1px solid <?php echo esc_attr($status_bd); ?>;">
                <div>
                    <strong style="color:<?php echo esc_attr($status_col); ?>;"><?php echo esc_html($status_icon . ' ' . $status_txt); ?></strong>
                    &nbsp;&nbsp;
                    <a href="<?php echo esc_url($manifest_url); ?>" target="_blank" style="font-size:13px;"><?php esc_html_e('View manifest','mumble-mcp-discovery'); ?></a>
                    &nbsp;&nbsp;
                    <code style="font-size:12px;background:rgba(0,0,0,.05);padding:2px 6px;border-radius:3px;"><?php echo esc_html($mcp_uri); ?></code>
                </div>
                <div>
                    <input type="hidden" id="mmcd_nonce" value="<?php echo esc_attr($nonce); ?>">
                    <button id="mmcd-validate-btn" class="button button-secondary"><?php esc_html_e('Check coherence','mumble-mcp-discovery'); ?></button>
                </div>
            </div>
            <div id="mmcd-validation-results" style="margin-bottom:12px;"></div>

            <style>
                .mmcd-checks { margin:0; padding:0; list-style:none; }
                .mmcd-checks li { padding:7px 12px; margin-bottom:3px; border-radius:3px; font-size:13px; display:flex; gap:8px; }
                .mmcd-check-error   { background:#fdecea; color:#8b2a20; }
                .mmcd-check-warning { background:#fdf6e3; color:#7a5c00; }
                .mmcd-check-success { background:#e6f4ea; color:#276b37; }
                .mmcd-check-info    { background:#e8f0f7; color:#2d6a9f; }
                .mmcd-icon { font-weight:700; flex-shrink:0; }
                .mmcd-tab-panel { display:none; }
            </style>

            <form method="post" action="options.php">
                <?php settings_fields( 'mmcd_settings' ); ?>

                <nav class="nav-tab-wrapper mmcd-tab-nav" style="margin-bottom:0;">
                    <a href="#" data-tab="general" class="nav-tab"><?php esc_html_e('General','mumble-mcp-discovery'); ?></a>
                    <a href="#" data-tab="advanced" class="nav-tab"><?php esc_html_e('Advanced','mumble-mcp-discovery'); ?></a>
                </nav>

                <?php /* ===== TAB: GENERAL ===== */ ?>
                <div id="mmcd-tab-general" class="mmcd-tab-panel" style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:20px 20px 4px;">

                    <h3 style="margin-top:0;"><?php esc_html_e('Server type','mumble-mcp-discovery'); ?></h3>
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th><?php esc_html_e('What does this site do?','mumble-mcp-discovery'); ?></th>
                            <td>
                                <label style="display:block;margin-bottom:8px;">
                                    <input type="radio" name="mmcd_options[server_type]" value="none" <?php checked($server_type,'none'); ?> />
                                    <strong><?php esc_html_e('Discovery only','mumble-mcp-discovery'); ?></strong>
                                    <span class="description"> — <?php esc_html_e('Advertise discovery without a real MCP server.','mumble-mcp-discovery'); ?></span>
                                </label>
                                <label style="display:block;margin-bottom:8px;">
                                    <input type="radio" name="mmcd_options[server_type]" value="external" <?php checked($server_type,'external'); ?> />
                                    <strong><?php esc_html_e('External MCP server','mumble-mcp-discovery'); ?></strong>
                                    <span class="description"> — <?php esc_html_e('My MCP server runs elsewhere. I provide its URL.','mumble-mcp-discovery'); ?></span>
                                </label>
                                <label style="display:block;">
                                    <input type="radio" name="mmcd_options[server_type]" value="internal" <?php checked($server_type,'internal'); ?> />
                                    <strong><?php esc_html_e('WordPress MCP server','mumble-mcp-discovery'); ?></strong>
                                    <span class="description"> — <?php esc_html_e('Activate the built-in endpoint on this site (wp-json/mcp/v1).','mumble-mcp-discovery'); ?></span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Enable','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="checkbox" name="mmcd_options[enabled]" value="1" <?php checked($options['enabled'] ?? true); ?> />
                                <?php esc_html_e('Publish /.well-known/mcp-server','mumble-mcp-discovery'); ?>
                            </td>
                        </tr>
                        <tr id="mmcd-endpoint-row">
                            <th><?php esc_html_e('MCP Endpoint URL','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="url" name="mmcd_options[endpoint]" class="regular-text"
                                    value="<?php echo esc_attr($options['endpoint'] ?? ''); ?>"
                                    placeholder="https://yoursite.com/mcp" />
                                <p class="description"><?php esc_html_e('Leave empty to use the WordPress REST API default.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                        <tr id="mmcd-internal-row">
                            <th><?php esc_html_e('Built-in endpoint','mumble-mcp-discovery'); ?></th>
                            <td>
                                <code><?php echo esc_html(rest_url('mcp/v1')); ?></code>
                                <p class="description"><?php esc_html_e('The built-in MCP endpoint will be activated at this URL.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e('Server information','mumble-mcp-discovery'); ?></h3>
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th><?php esc_html_e('Server Name','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="text" name="mmcd_options[name]" class="regular-text"
                                    value="<?php echo esc_attr($options['name'] ?? ''); ?>"
                                    placeholder="<?php echo esc_attr(get_bloginfo('name') . ' MCP Server'); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Description','mumble-mcp-discovery'); ?></th>
                            <td>
                                <textarea name="mmcd_options[description]" class="large-text" rows="2"
                                    placeholder="<?php echo esc_attr(get_bloginfo('description')); ?>"><?php echo esc_textarea($options['description'] ?? ''); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Categories','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="text" name="mmcd_options[categories]" class="regular-text"
                                    value="<?php echo esc_attr($options['categories'] ?? ''); ?>"
                                    placeholder="e-commerce, hardware" />
                                <p class="description"><?php esc_html_e('Comma-separated. WooCommerce adds "e-commerce" automatically.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Coverage','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="text" name="mmcd_options[coverage]" style="width:80px"
                                    value="<?php echo esc_attr($options['coverage'] ?? ''); ?>"
                                    placeholder="IT" />
                                <p class="description"><?php esc_html_e('ISO 3166-1 country code. Examples: IT, EU, WW.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Contact Email','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="email" name="mmcd_options[contact]" class="regular-text"
                                    value="<?php echo esc_attr($options['contact'] ?? get_option('admin_email')); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Docs URL','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="url" name="mmcd_options[docs]" class="regular-text"
                                    value="<?php echo esc_attr($options['docs'] ?? ''); ?>" />
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Allow Crawling','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="checkbox" name="mmcd_options[crawl]" value="1" <?php checked($options['crawl'] ?? true); ?> />
                                <?php esc_html_e('Allow MCP crawlers to index this server.','mumble-mcp-discovery'); ?>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( __('Save and publish manifest','mumble-mcp-discovery') ); ?>
                </div>

                <?php /* ===== TAB: ADVANCED ===== */ ?>
                <div id="mmcd-tab-advanced" class="mmcd-tab-panel" style="background:#fff;border:1px solid #c3c4c7;border-top:none;padding:20px 20px 4px;">

                    <h3 style="margin-top:0;"><?php esc_html_e('Authentication','mumble-mcp-discovery'); ?></h3>
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th><?php esc_html_e('Auth Method','mumble-mcp-discovery'); ?></th>
                            <td>
                                <select name="mmcd_options[auth_type]" id="mmcd_auth_type">
                                    <option value="none"   <?php selected($auth_type,'none'); ?>><?php esc_html_e('None (public)','mumble-mcp-discovery'); ?></option>
                                    <option value="bearer" <?php selected($auth_type,'bearer'); ?>><?php esc_html_e('Bearer Token','mumble-mcp-discovery'); ?></option>
                                    <option value="apikey" <?php selected($auth_type,'apikey'); ?>><?php esc_html_e('API Key','mumble-mcp-discovery'); ?></option>
                                    <option value="oauth2" <?php selected($auth_type,'oauth2'); ?>><?php esc_html_e('OAuth 2.0','mumble-mcp-discovery'); ?></option>
                                    <option value="mtls"   <?php selected($auth_type,'mtls'); ?>><?php esc_html_e('Mutual TLS','mumble-mcp-discovery'); ?></option>
                                </select>
                                <p id="mmcd-auth-required-note" class="description" style="color:#c0392b;"><?php esc_html_e('Required for enterprise and regulated trust class.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                        <tr id="mmcd_auth_endpoint_row">
                            <th><?php esc_html_e('Auth Endpoint URL','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="url" name="mmcd_options[auth_endpoint]" class="regular-text"
                                    value="<?php echo esc_attr($options['auth_endpoint'] ?? ''); ?>"
                                    placeholder="https://yoursite.com/.well-known/oauth-authorization-server" />
                                <p class="description"><?php esc_html_e('Required for Bearer and OAuth 2.0.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e('Cache','mumble-mcp-discovery'); ?></h3>
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th><?php esc_html_e('Cache TTL (seconds)','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="number" name="mmcd_options[cache_ttl]" min="60" max="86400"
                                    value="<?php echo esc_attr($options['cache_ttl'] ?? 3600); ?>" style="width:100px" />
                                <p class="description"><?php esc_html_e('Default: 3600 (1 hour). Use 300 or less for regulated servers.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e('Security posture','mumble-mcp-discovery'); ?></h3>
                    <p class="description" style="margin-bottom:12px;"><?php esc_html_e('Declares the server trust class per draft-04 Section 6.10. If not declared, clients treat this server as "public".','mumble-mcp-discovery'); ?></p>
                    <table class="form-table" style="margin-top:0;">
                        <tr>
                            <th><?php esc_html_e('Trust Class','mumble-mcp-discovery'); ?></th>
                            <td>
                                <select name="mmcd_options[trust_class]" id="mmcd_trust_class">
                                    <option value=""           <?php selected($trust_class,''); ?>><?php esc_html_e('— not declared (defaults to public) —','mumble-mcp-discovery'); ?></option>
                                    <option value="public"     <?php selected($trust_class,'public'); ?>><?php esc_html_e('public — no restrictions','mumble-mcp-discovery'); ?></option>
                                    <option value="sandbox"    <?php selected($trust_class,'sandbox'); ?>><?php esc_html_e('sandbox — non-production / test','mumble-mcp-discovery'); ?></option>
                                    <option value="enterprise" <?php selected($trust_class,'enterprise'); ?>><?php esc_html_e('enterprise — controlled access, auth required','mumble-mcp-discovery'); ?></option>
                                    <option value="regulated"  <?php selected($trust_class,'regulated'); ?>><?php esc_html_e('regulated — compliance required','mumble-mcp-discovery'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr id="mmcd-expires-row">
                            <th><?php esc_html_e('Manifest Expiry (days)','mumble-mcp-discovery'); ?></th>
                            <td>
                                <input type="number" name="mmcd_options[expires_days]" min="1" max="365"
                                    value="<?php echo esc_attr($options['expires_days'] ?? 30); ?>" style="width:80px" />
                                <p class="description"><?php esc_html_e('Required for sandbox. Default: 30 days.','mumble-mcp-discovery'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <div id="mmcd-compliance-section">
                        <h3><?php esc_html_e('Compliance (regulated only)','mumble-mcp-discovery'); ?></h3>
                        <table class="form-table" style="margin-top:0;">
                            <tr>
                                <th><?php esc_html_e('Jurisdiction','mumble-mcp-discovery'); ?></th>
                                <td>
                                    <input type="text" name="mmcd_options[jurisdiction]" class="regular-text"
                                        value="<?php echo esc_attr($options['jurisdiction'] ?? ''); ?>"
                                        placeholder="EU" />
                                    <p class="description"><?php esc_html_e('ISO 3166-1 or regional code: EU, EEA, UK, IT, DE, US, etc.','mumble-mcp-discovery'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Frameworks','mumble-mcp-discovery'); ?></th>
                                <td>
                                    <input type="text" name="mmcd_options[frameworks]" class="regular-text"
                                        value="<?php echo esc_attr($options['frameworks'] ?? ''); ?>"
                                        placeholder="GDPR, ISO27001" />
                                    <p class="description"><?php esc_html_e('Comma-separated: GDPR, HIPAA, ISO27001, PCI-DSS, SOC2.','mumble-mcp-discovery'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Log Retention (days)','mumble-mcp-discovery'); ?></th>
                                <td>
                                    <input type="number" name="mmcd_options[log_retention_days]" min="1"
                                        value="<?php echo esc_attr($options['log_retention_days'] ?? 90); ?>" style="width:80px" />
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button( __('Save and publish manifest','mumble-mcp-discovery') ); ?>
                </div>

            </form>
        </div>
        <?php
    }
}
