<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Settings {

    public static function init() {
        add_action( 'admin_menu',    [ self::class, 'menu'   ] );
        add_action( 'admin_init',    [ self::class, 'fields' ] );
        add_action( 'admin_post_tk_save_settings', [ self::class, 'save' ] );
        add_action( 'wp_ajax_tk_test_connection',  [ self::class, 'ajax_test_connection' ] );
    }

    public static function ajax_test_connection() {
        check_ajax_referer( 'tk_license_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );

        $health_url = TK_API_URL . '/api/health';
        $res = wp_remote_get( $health_url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $res ) ) {
            wp_send_json_error( [
                'step'    => 'wp_remote_get failed',
                'message' => $res->get_error_message(),
                'code'    => $res->get_error_code(),
                'url'     => $health_url,
            ] );
        }

        $http_code = wp_remote_retrieve_response_code( $res );
        $raw_body  = wp_remote_retrieve_body( $res );
        $body      = json_decode( $raw_body, true );

        $payload = [
            'http_code' => $http_code,
            'status'    => $body['status'] ?? null,
            'db'        => $body['db_connected'] ?? null,
            'raw'       => substr( $raw_body, 0, 300 ),
            'url'       => $health_url,
        ];

        if ( $http_code === 200 && isset( $body['status'] ) ) {
            wp_send_json_success( $payload );
        } else {
            wp_send_json_error( $payload );
        }
    }

    public static function menu() {
        add_submenu_page(
            'edit.php?post_type=tk_route',
            __( 'TrailKit Settings', 'trailkit' ),
            __( 'Settings', 'trailkit' ),
            'manage_options',
            'trailkit-settings',
            [ self::class, 'render' ]
        );
    }

    public static function fields() {
        register_setting( 'trailkit', 'tk_route_slug',    [ 'sanitize_callback' => 'sanitize_title' ] );
        register_setting( 'trailkit', 'tk_poi_slug',     [ 'sanitize_callback' => 'sanitize_title' ] );
        register_setting( 'trailkit', 'tk_guide_slug',   [ 'sanitize_callback' => 'sanitize_title' ] );
        register_setting( 'trailkit', 'tk_default_lat',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'trailkit', 'tk_default_lng',  [ 'sanitize_callback' => 'sanitize_text_field' ] );
        register_setting( 'trailkit', 'tk_default_zoom', [ 'sanitize_callback' => 'sanitize_text_field' ] );
    }

    public static function save() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        check_admin_referer( 'tk_settings' );

        $options = [ 'tk_route_slug', 'tk_poi_slug', 'tk_guide_slug', 'tk_default_lat', 'tk_default_lng', 'tk_default_zoom' ];
        foreach ( $options as $opt ) {
            if ( isset( $_POST[ $opt ] ) ) {
                update_option( $opt, sanitize_text_field( wp_unslash( $_POST[ $opt ] ) ) );
            }
        }
        flush_rewrite_rules();
        wp_safe_redirect( add_query_arg( 'saved', '1', wp_get_referer() ) );
        exit;
    }

    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $license_key     = get_option( 'tk_license_key', '' );
        $license_expires = get_option( 'tk_license_expires', '' );
        $is_pro          = tk_is_pro();
        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                <?php esc_html_e( 'TrailKit Settings', 'trailkit' ) ?>
                <span style="display:flex;gap:8px">
                    <a href="<?php echo esc_url( TK_Documentation::DOCS_URL ) ?>" target="_blank" rel="noopener"
                       class="button" style="display:flex;align-items:center;gap:6px">
                        <span>📖</span> <?php esc_html_e( 'Documentation', 'trailkit' ) ?>
                    </a>
                </span>
            </h1>

            <?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'trailkit' ) ?></p></div>
            <?php endif; ?>

            <?php /* ── License Section ─────────────────────────── */ ?>
            <div style="background:#fff;border:1px solid <?php echo $is_pro ? '#16a34a' : '#d1d5db' ?>;border-radius:8px;padding:20px 24px;margin:16px 0 24px;max-width:700px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
                    <h2 style="margin:0;font-size:1rem"><?php esc_html_e( 'Pro License', 'trailkit' ) ?></h2>
                    <?php if ( $is_pro ) : ?>
                        <span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:99px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em">
                            ✓ <?php esc_html_e( 'Active', 'trailkit' ) ?>
                        </span>
                    <?php elseif ( $license_key ) : ?>
                        <span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:99px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em">
                            <?php esc_html_e( 'Inactive', 'trailkit' ) ?>
                        </span>
                    <?php else : ?>
                        <span style="background:#f3f4f6;color:#6b7280;padding:3px 10px;border-radius:99px;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em">
                            <?php esc_html_e( 'Not Activated', 'trailkit' ) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ( $is_pro ) : ?>
                    <p style="margin:0 0 12px;color:#374151;font-size:0.875rem">
                        <strong><?php esc_html_e( 'Key:', 'trailkit' ) ?></strong>
                        <?php echo esc_html( substr( $license_key, 0, 7 ) . str_repeat( '•', 12 ) ) ?>
                        <?php if ( $license_expires ) : ?>
                        &nbsp;·&nbsp;
                        <strong><?php esc_html_e( 'Expires:', 'trailkit' ) ?></strong>
                        <?php echo $license_expires === null ? esc_html__( 'Never (Lifetime)', 'trailkit' )
                                                             : esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license_expires ) ) ) ?>
                        <?php endif; ?>
                    </p>
                    <button id="tk-deactivate-license" class="button" style="color:#dc2626;border-color:#dc2626">
                        <?php esc_html_e( 'Remove License', 'trailkit' ) ?>
                    </button>

                <?php else : ?>
                    <p style="margin:0 0 12px;color:#6b7280;font-size:0.875rem">
                        <?php esc_html_e( 'Enter your TrailKit Pro license key to unlock unlimited content and all Pro features.', 'trailkit' ) ?>
                        <a href="<?php echo esc_url( 'https://trailplugin.com' ) ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get a license →', 'trailkit' ) ?></a>
                    </p>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        <input type="text" id="tk-license-key-input"
                            value="<?php echo esc_attr( $license_key ) ?>"
                            placeholder="TK-XXXX-XXXX-XXXX-XXXX"
                            style="flex:1;min-width:260px;max-width:360px;font-family:monospace">
                        <button id="tk-activate-license" class="button button-primary">
                            <?php esc_html_e( 'Activate License', 'trailkit' ) ?>
                        </button>
                    </div>

                    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f3f4f6">
                        <p style="margin:0;color:#6b7280;font-size:0.8rem">
                            <?php
                            printf(
                                /* translators: %s = link to trailplugin.com trial page */
                                esc_html__( 'Want to try Pro first? %s — no credit card required.', 'trailkit' ),
                                '<a href="' . esc_url( 'https://trailplugin.com/#trial' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Start a free 14-day trial at trailplugin.com', 'trailkit' ) . '</a>'
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div id="tk-license-result" style="display:none;margin-top:12px;padding:8px 12px;border-radius:6px;font-size:0.85rem"></div>

                <?php if ( ! $is_pro ) : ?>
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f0f0f0">
                    <button id="tk-test-connection" class="button" style="font-size:12px">
                        🔌 <?php esc_html_e( 'Test server connection', 'trailkit' ) ?>
                    </button>
                    <div id="tk-connection-result" style="display:none;margin-top:8px;padding:8px 12px;border-radius:6px;font-size:12px;font-family:monospace;background:#f8f8f8;border:1px solid #ddd;white-space:pre-wrap;word-break:break-all"></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( TK_LITE ) : ?>
            <div class="notice notice-info" style="border-left-color:#0df246;background:#f0fff4">
                <p>
                    <strong><?php esc_html_e( 'TrailKit Lite', 'trailkit' ) ?></strong> &mdash;
                    <?php
                    printf(
                        /* translators: %1$d = route/POI limit, %2$d = same limit, %3$d = gallery limit, %4$s = upgrade link */
                        esc_html__( 'Free version. Limits: %1$d routes, %2$d POIs, 1 guide, %3$d gallery images. %4$s for unlimited content, GPS maps, elevation profiles and more.', 'trailkit' ),
                        intval( TK_LIMIT ),
                        intval( TK_LIMIT ),
                        intval( TK_GALLERY_LIMIT ),
                        '<a href="' . esc_url( 'https://trailplugin.com' ) . '" target="_blank">' . esc_html__( 'Upgrade to Pro', 'trailkit' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ) ?>">
                <?php wp_nonce_field( 'tk_settings' ) ?>
                <input type="hidden" name="action" value="tk_save_settings">

                <h2><?php esc_html_e( 'URL Slugs', 'trailkit' ) ?></h2>
                <p class="description"><?php esc_html_e( 'Change these slugs then save Permalinks in Settings → Permalinks.', 'trailkit' ) ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Routes slug', 'trailkit' ) ?></th>
                        <td><input type="text" name="tk_route_slug" value="<?php echo esc_attr( get_option( 'tk_route_slug', 'routes' ) ) ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'POIs slug', 'trailkit' ) ?></th>
                        <td><input type="text" name="tk_poi_slug" value="<?php echo esc_attr( get_option( 'tk_poi_slug', 'points-of-interest' ) ) ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Guides slug', 'trailkit' ) ?></th>
                        <td><input type="text" name="tk_guide_slug" value="<?php echo esc_attr( get_option( 'tk_guide_slug', 'guides' ) ) ?>" class="regular-text"></td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Default Map Center', 'trailkit' ) ?></h2>
                <p class="description"><?php esc_html_e( 'Used by [tk_map] when no lat/lng is specified.', 'trailkit' ) ?></p>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Latitude', 'trailkit' ) ?></th>
                        <td><input type="text" name="tk_default_lat" value="<?php echo esc_attr( get_option( 'tk_default_lat', '8.0' ) ) ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Longitude', 'trailkit' ) ?></th>
                        <td><input type="text" name="tk_default_lng" value="<?php echo esc_attr( get_option( 'tk_default_lng', '-66.0' ) ) ?>" class="small-text"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Default zoom', 'trailkit' ) ?></th>
                        <td><input type="number" name="tk_default_zoom" value="<?php echo esc_attr( get_option( 'tk_default_zoom', '7' ) ) ?>" class="small-text" min="1" max="18"></td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Settings', 'trailkit' ) ) ?>
            </form>

            <?php /* ── Pro Features — Weather & GPX ─────────────── */ ?>
            <hr>
            <h2><?php esc_html_e( 'Pro Features', 'trailkit' ) ?></h2>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;max-width:860px;margin-bottom:24px">

                <?php /* Weather widget */ ?>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:18px 20px">
                    <h3 style="margin:0 0 6px;font-size:0.95rem;display:flex;align-items:center;gap:8px">
                        ⛅ <?php esc_html_e( 'Live Weather Widget', 'trailkit' ) ?>
                        <?php if ( TK_LITE ) : ?>
                            <span style="background:#fef3c7;color:#92400e;font-size:0.72em;padding:2px 7px;border-radius:4px;font-weight:700">Pro</span>
                        <?php else : ?>
                            <span style="background:#dcfce7;color:#166534;font-size:0.72em;padding:2px 7px;border-radius:4px;font-weight:700">Active</span>
                        <?php endif; ?>
                    </h3>
                    <p style="margin:0 0 10px;color:#6b7280;font-size:0.85rem">
                        <?php esc_html_e( 'Shows real-time temperature and weather conditions alongside the difficulty badge on each route page.', 'trailkit' ) ?>
                    </p>
                    <p style="margin:0 0 6px;color:#374151;font-size:0.85rem">
                        <strong><?php esc_html_e( 'How to enable:', 'trailkit' ) ?></strong>
                        <?php esc_html_e( 'Open any Route in the editor → Route Details → check "Show live weather widget".', 'trailkit' ) ?>
                    </p>
                    <p style="margin:0;color:#6b7280;font-size:0.82rem">
                        <?php
                        printf(
                            '%s <a href="%s" target="_blank" rel="noopener">%s</a>. %s',
                            esc_html__( 'Uses', 'trailkit' ),
                            esc_url( 'https://open-meteo.com' ),
                            esc_html__( 'Open-Meteo', 'trailkit' ),
                            esc_html__( 'Free · No API key required · No account needed.', 'trailkit' )
                        );
                        ?>
                    </p>
                </div>

                <?php /* GPX import */ ?>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:18px 20px">
                    <h3 style="margin:0 0 6px;font-size:0.95rem;display:flex;align-items:center;gap:8px">
                        🗺 <?php esc_html_e( 'GPX Track Import', 'trailkit' ) ?>
                        <?php if ( TK_LITE ) : ?>
                            <span style="background:#fef3c7;color:#92400e;font-size:0.72em;padding:2px 7px;border-radius:4px;font-weight:700">Pro</span>
                        <?php else : ?>
                            <span style="background:#dcfce7;color:#166534;font-size:0.72em;padding:2px 7px;border-radius:4px;font-weight:700">Active</span>
                        <?php endif; ?>
                    </h3>
                    <p style="margin:0 0 10px;color:#6b7280;font-size:0.85rem">
                        <?php esc_html_e( 'Upload a .gpx file recorded with your GPS device or an app like Wikiloc, Komoot, or Garmin Connect. The track is imported automatically and drawn as a polyline on the map.', 'trailkit' ) ?>
                    </p>
                    <p style="margin:0 0 6px;color:#374151;font-size:0.85rem">
                        <strong><?php esc_html_e( 'How to use:', 'trailkit' ) ?></strong>
                        <?php esc_html_e( 'Open any Route → "GPS & Map" box → click "Import GPX" and select your file. Points are simplified automatically if the track exceeds 500 points.', 'trailkit' ) ?>
                    </p>
                    <p style="margin:0;color:#6b7280;font-size:0.82rem">
                        <?php esc_html_e( 'Compatible with any standard GPX 1.0/1.1 file. Processes entirely in your browser — no file is uploaded to external servers.', 'trailkit' ) ?>
                    </p>
                </div>
            </div>

            <hr>
            <h2><?php esc_html_e( 'Demo Data', 'trailkit' ) ?></h2>
            <p class="description"><?php esc_html_e( 'Install sample Routes, Points of Interest, Guides, and demo pages to explore the plugin. Safe to remove at any time.', 'trailkit' ) ?></p>

            <div id="tk-demo-result" style="display:none;margin:8px 0 12px;padding:10px 14px;border-radius:6px;font-size:13px"></div>

            <button id="tk-install-demo" class="button button-primary"><?php esc_html_e( 'Install Demo Data', 'trailkit' ) ?></button>
            &nbsp;
            <button id="tk-remove-demo" class="button button-secondary"><?php esc_html_e( 'Remove Demo Data', 'trailkit' ) ?></button>

            <script>
            (function($){
                var licenseNonce = '<?php echo esc_js( wp_create_nonce( 'tk_license_nonce' ) ) ?>';

                /* ── License activate ── */
                $('#tk-activate-license').on('click', function(){
                    var btn = $(this);
                    var key = $('#tk-license-key-input').val().trim();
                    if (!key) { tkLicenseMsg('<?php echo esc_js( __( 'Please enter a license key.', 'trailkit' ) ) ?>', false); return; }
                    btn.prop('disabled', true).text('<?php echo esc_js( __( 'Activating…', 'trailkit' ) ) ?>');
                    $.post(ajaxurl, { action:'tk_activate_license', nonce:licenseNonce, license_key:key }, function(res){
                        tkLicenseMsg(res.data ? res.data.message : 'Done.', res.success);
                        if (res.success) setTimeout(function(){ location.reload(); }, 1200);
                        else btn.prop('disabled', false).text('<?php echo esc_js( __( 'Activate License', 'trailkit' ) ) ?>');
                    });
                });

                /* ── Test connection ── */
                $('#tk-test-connection').on('click', function(){
                    var btn = $(this);
                    btn.prop('disabled', true).text('Testing...');
                    $.post(ajaxurl, { action:'tk_test_connection', nonce:licenseNonce }, function(res){
                        var $r = $('#tk-connection-result');
                        $r.show();
                        var d = res.data || {};
                        if (res.success) {
                            var statusLabel = d.status === 'ok' ? '✅ ok' : '⚠ ' + d.status;
                            $r.css({ background:'#f0fff4', borderColor:'#86efac', color:'#166534' });
                            $r.text('✅ Connection OK\nHTTP: ' + d.http_code + '\nStatus: ' + statusLabel + '\nDB: ' + (d.db ? 'connected' : 'error') + '\nURL: ' + d.url);
                        } else {
                            $r.css({ background:'#fff0f0', borderColor:'#fca5a5', color:'#991b1b' });
                            var msg = d.step
                                ? '❌ Connection FAILED\nStep: ' + d.step + '\nError: ' + d.message + '\nURL: ' + d.url
                                : '❌ Server error\nHTTP: ' + d.http_code + '\nURL: ' + d.url + (d.raw ? '\n\n' + d.raw : '');
                            $r.text(msg);
                        }
                        btn.prop('disabled', false).text('🔌 <?php echo esc_js( __( 'Test server connection', 'trailkit' ) ) ?>');
                    });
                });

                /* ── License deactivate ── */
                $('#tk-deactivate-license').on('click', function(){
                    if (!confirm('<?php echo esc_js( __( 'Remove the Pro license from this site?', 'trailkit' ) ) ?>')) return;
                    var btn = $(this);
                    btn.prop('disabled', true).text('<?php echo esc_js( __( 'Removing…', 'trailkit' ) ) ?>');
                    $.post(ajaxurl, { action:'tk_deactivate_license', nonce:licenseNonce }, function(res){
                        if (res.success) location.reload();
                        else { tkLicenseMsg('Error removing license.', false); btn.prop('disabled',false).text('<?php echo esc_js( __( 'Remove License', 'trailkit' ) ) ?>'); }
                    });
                });

                function tkLicenseMsg(msg, success) {
                    var $r = $('#tk-license-result');
                    $r.show().css({
                        background: success ? '#f0fff4' : '#fff0f0',
                        border: '1px solid ' + (success ? '#86efac' : '#fca5a5'),
                        color:  success ? '#166534' : '#991b1b',
                    }).text(msg);
                }

                /* ── Demo data ── */
                var demoNonce = '<?php echo esc_js( wp_create_nonce( 'tk_demo_nonce' ) ) ?>';
                function tkDemoAction(action, btn) {
                    btn.prop('disabled', true).text(action === 'tk_install_demo' ? 'Installing...' : 'Removing...');
                    $.post(ajaxurl, { action: action, nonce: demoNonce }, function(res) {
                        var $r = $('#tk-demo-result');
                        $r.show().css({ background: res.success ? '#f0fff4' : '#fff0f0', border: '1px solid ' + (res.success ? '#86efac' : '#fca5a5'), color: res.success ? '#166534' : '#991b1b' });
                        $r.text(res.data ? res.data.message : 'Done.');
                        btn.prop('disabled', false).text(action === 'tk_install_demo' ? '<?php echo esc_js( __( 'Install Demo Data', 'trailkit' ) ) ?>' : '<?php echo esc_js( __( 'Remove Demo Data', 'trailkit' ) ) ?>');
                    });
                }
                $('#tk-install-demo').on('click', function(){ tkDemoAction('tk_install_demo', $(this)); });
                $('#tk-remove-demo').on('click', function(){ if(confirm('<?php echo esc_js( __( 'Remove all demo content?', 'trailkit' ) ) ?>')) tkDemoAction('tk_remove_demo', $(this)); });
            }(jQuery));
            </script>
        </div>
        <?php
    }
}

TK_Settings::init();
