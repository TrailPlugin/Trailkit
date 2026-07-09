<?php
/**
 * Plugin Name: TrailKit — Adventure Routes, POIs & Guides
 * Plugin URI:  https://trailplugin.com
 * Description: Adventure Routes, Points of Interest & Guides for WordPress. Works with any theme.
 * Version:     1.0.3
 * Author:      Gabriel Arias
 * Author URI:  https://gabriel-arias-portfolio.vercel.app/
 * Text Domain: trailkit
 * Domain Path: /languages
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'TK_VERSION',     '1.0.3' );
define( 'TK_DIR',         plugin_dir_path( __FILE__ ) );
define( 'TK_URL',         plugin_dir_url( __FILE__ ) );
define( 'TK_API_URL',     'https://trailplugin.com' );
define( 'TK_LIMIT',         3 );   // Lite: max published items per CPT
define( 'TK_GALLERY_LIMIT', 3 );   // Lite: max images per gallery

/* ── License helpers ─────────────────────────────── */
// Returns true if a valid Pro license is active (checks 7-day transient first).
function tk_is_pro(): bool {
    $cached = get_transient( 'tk_pro_status' );
    if ( $cached !== false ) return $cached === 'active';
    return get_option( 'tk_license_status', '' ) === 'active';
}

// TK_LITE is the inverse of Pro status — used throughout the plugin for feature gating.
// PHP function declarations are hoisted, so tk_is_pro() is available here.
define( 'TK_LITE', ! tk_is_pro() );

// Weekly background checkin — scheduled via wp_cron.
add_action( 'tk_license_checkin', 'tk_run_license_checkin' );
function tk_run_license_checkin() {
    $key = get_option( 'tk_license_key', '' );
    if ( ! $key ) return;

    $res = wp_remote_post( TK_API_URL . '/api/licenses/checkin', [
        'body'    => wp_json_encode( [
            'key'            => $key,
            'domain'         => wp_parse_url( home_url(), PHP_URL_HOST ),
            'plugin_version' => TK_VERSION,
            'wp_version'     => get_bloginfo( 'version' ),
        ] ),
        'headers' => [ 'Content-Type' => 'application/json' ],
        'timeout' => 10,
    ] );

    if ( is_wp_error( $res ) ) return;
    // Only act on a clean 200 — transient server errors (500, 502, timeouts) must
    // not downgrade active sites; keep last known status until the next check.
    if ( wp_remote_retrieve_response_code( $res ) !== 200 ) return;
    $body   = json_decode( wp_remote_retrieve_body( $res ), true );
    $status = ! empty( $body['valid'] ) ? 'active' : 'inactive';
    update_option( 'tk_license_status', $status );
    set_transient( 'tk_pro_status', $status, WEEK_IN_SECONDS );
}

// Schedule weekly checkin on activation, clear on deactivation.
register_activation_hook(   __FILE__, function() {
    if ( ! wp_next_scheduled( 'tk_license_checkin' ) )
        wp_schedule_event( time(), 'weekly', 'tk_license_checkin' );
} );
register_deactivation_hook( __FILE__, function() {
    wp_clear_scheduled_hook( 'tk_license_checkin' );
} );

/* ── Includes ─────────────────────────────────────── */
require TK_DIR . 'includes/class-cpt.php';
require TK_DIR . 'includes/class-route-fields.php';
require TK_DIR . 'includes/class-poi-fields.php';
require TK_DIR . 'includes/class-guide-fields.php';
require TK_DIR . 'includes/class-shortcodes.php';
require TK_DIR . 'includes/class-map.php';
require TK_DIR . 'includes/class-ajax.php';
require TK_DIR . 'includes/class-demo.php';
require TK_DIR . 'admin/settings.php';
require TK_DIR . 'admin/documentation.php';

/* ── Hooks ────────────────────────────────────────── */

add_action( 'init', [ 'TK_CPT',          'register' ] );
add_action( 'init', [ 'TK_Route_Fields', 'init' ] );
add_action( 'init', [ 'TK_POI_Fields',   'init' ] );
add_action( 'init', [ 'TK_Guide_Fields', 'init' ] );
add_action( 'init', [ 'TK_Shortcodes',   'register' ] );
add_action( 'wp_enqueue_scripts',    'tk_enqueue' );
add_action( 'admin_enqueue_scripts', 'tk_admin_enqueue' );
add_filter( 'template_include',      'tk_template_include' );
register_activation_hook( __FILE__,  'tk_activate' );

/* ── Activation ───────────────────────────────────── */
function tk_activate() {
    TK_CPT::register();
    TK_CPT::insert_default_terms();
    flush_rewrite_rules();
}

/* ── Demo data AJAX handler ───────────────────────── */
add_action( 'wp_ajax_tk_install_demo', 'tk_ajax_install_demo' );
function tk_ajax_install_demo() {
    check_ajax_referer( 'tk_demo_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
    delete_option( 'tk_demo_installed' ); // allow re-install
    TK_Demo::install();
    wp_send_json_success( [ 'message' => __( 'Demo data installed. Pages created: Routes, Points of Interest, Guides.', 'trailkit' ) ] );
}

add_action( 'wp_ajax_tk_remove_demo', 'tk_ajax_remove_demo' );
function tk_ajax_remove_demo() {
    check_ajax_referer( 'tk_demo_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
    TK_Demo::uninstall();
    wp_send_json_success( [ 'message' => __( 'Demo data removed.', 'trailkit' ) ] );
}

/* ── Frontend assets (only where needed) ─────────── */
function tk_enqueue() {
    if ( ! tk_needs_assets() ) return;

    wp_enqueue_style(  'leaflet',   TK_URL . 'assets/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet',   TK_URL . 'assets/leaflet.js',  [], '1.9.4', true );
    wp_enqueue_style(  'trailkit',  TK_URL . 'assets/trailplugin.css', [ 'leaflet' ], TK_VERSION );
    wp_enqueue_script( 'trailkit',  TK_URL . 'assets/trailplugin.js',  [ 'leaflet' ], TK_VERSION, true );

    if ( ! TK_LITE ) {
        tk_output_color_css();
    }

    wp_localize_script( 'trailkit', 'tkData', [
        'ajaxurl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'tk_nonce' ),
        'lite'      => TK_LITE,
        'pluginUrl' => TK_URL,
    ] );
}

function tk_output_color_css() {
    $primary    = get_option( 'tk_color_primary',    '' );
    $bg_card    = get_option( 'tk_color_bg_card',    '' );
    $text       = get_option( 'tk_color_text',       '' );
    $text_muted = get_option( 'tk_color_text_muted', '' );
    $border     = get_option( 'tk_color_border',     '' );

    if ( ! $primary && ! $bg_card && ! $text && ! $text_muted && ! $border ) return;

    $css = '';

    if ( $primary ) {
        $hex = ltrim( $primary, '#' );
        if ( strlen( $hex ) === 6 ) {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
            $css .= ':root{--tk-primary:' . $primary . ';--tk-primary-dim:rgba(' . $r . ',' . $g . ',' . $b . ',.12);}';
        }
    }

    $scoped = '';
    if ( $bg_card )    $scoped .= '--tk-bg-card:' . $bg_card . ';';
    if ( $text )       $scoped .= '--tk-text:' . $text . ';';
    if ( $text_muted ) $scoped .= '--tk-text-muted:' . $text_muted . ';';
    if ( $border )     $scoped .= '--tk-border:' . $border . ';';
    if ( $scoped ) {
        $css .= '.tk-card,.tk-grid,.tk-single,.tk-single__stats-wrap,.tk-map-wrap,.tk-contact-card,.tk-alert,.tk-empty{' . $scoped . '}';
    }

    if ( $css ) {
        wp_add_inline_style( 'trailkit', $css );
    }
}

function tk_needs_assets() {
    if ( is_singular( [ 'tk_route', 'tk_poi', 'tk_guide' ] ) ) return true;
    if ( is_post_type_archive( [ 'tk_route', 'tk_poi', 'tk_guide' ] ) ) return true;
    if ( is_tax( [ 'tk_activity', 'tk_region', 'tk_poi_type' ] ) ) return true;
    global $post;
    if ( $post && ( has_shortcode( $post->post_content, 'tk_routes' )
                 || has_shortcode( $post->post_content, 'tk_pois' )
                 || has_shortcode( $post->post_content, 'tk_guides' )
                 || has_shortcode( $post->post_content, 'tk_map' ) ) ) return true;
    return false;
}

/* ── Admin assets ─────────────────────────────────── */
function tk_admin_enqueue( $hook ) {
    global $post;
    $cpts = [ 'tk_route', 'tk_poi', 'tk_guide' ];
    $on_cpt_edit = in_array( $hook, [ 'post.php', 'post-new.php' ] )
                   && $post && in_array( $post->post_type, $cpts );
    $on_settings = ( $hook === 'trailkit_page_trailkit-settings' );

    if ( ! $on_cpt_edit && ! $on_settings ) return;

    wp_enqueue_media();
    wp_enqueue_style(  'trailkit-admin', TK_URL . 'assets/admin.css', [], TK_VERSION );
    // 'media-editor' dependency ensures wp.media is initialized before our script runs.
    wp_enqueue_script( 'trailkit', TK_URL . 'assets/trailplugin.js', [ 'jquery', 'media-editor' ], TK_VERSION, true );
    wp_localize_script( 'trailkit', 'tkData', [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'tk_nonce' ),
        'lite'    => TK_LITE,
    ] );
}

/* ── Template loader (theme-overrideable) ─────────── */
function tk_get_template( $name, $vars = [] ) {
    $theme_file  = get_stylesheet_directory() . '/trailkit/' . $name;
    $plugin_file = TK_DIR . 'templates/' . $name;
    $file        = file_exists( $theme_file ) ? $theme_file : $plugin_file;
    if ( ! file_exists( $file ) ) return;
    if ( $vars ) extract( $vars ); // phpcs:ignore
    include $file;
}

/* ── Single / archive template loader ────────────── */
function tk_template_include( $template ) {
    $map = [
        'tk_route' => 'single-route.php',
        'tk_poi'   => 'single-poi.php',
        'tk_guide' => 'single-guide.php',
    ];

    if ( is_singular( array_keys( $map ) ) ) {
        $post_type = get_post_type();
        $tpl_name  = $map[ $post_type ] ?? null;
        if ( $tpl_name ) {
            $theme_file  = get_stylesheet_directory() . '/trailkit/' . $tpl_name;
            $plugin_file = TK_DIR . 'templates/' . $tpl_name;
            $found = file_exists( $theme_file ) ? $theme_file : ( file_exists( $plugin_file ) ? $plugin_file : null );
            if ( $found ) return $found;
        }
    }

    return $template;
}

/* ── Lite limit helper ────────────────────────────── */
function tk_at_limit( $post_type ) {
    if ( ! TK_LITE ) return false;
    $limits = tk_limits();
    if ( ! isset( $limits[ $post_type ] ) ) return false;
    $counts = wp_count_posts( $post_type );
    $total  = (int) ( $counts->publish ?? 0 )
            + (int) ( $counts->private ?? 0 )
            + (int) ( $counts->future  ?? 0 );
    return $total >= $limits[ $post_type ];
}

function tk_limits() {
    return [
        'tk_route' => TK_LIMIT,
        'tk_poi'   => TK_LIMIT,
        'tk_guide' => 1,
    ];
}

function tk_sanitize_gallery( $value ) {
    $ids = $value ? json_decode( wp_unslash( $value ), true ) : [];
    if ( ! is_array( $ids ) ) return '';
    $ids = array_values( array_filter( array_map( 'absint', $ids ) ) );
    if ( TK_LITE ) $ids = array_slice( $ids, 0, TK_GALLERY_LIMIT );
    return $ids ? wp_json_encode( $ids ) : '';
}

/* ── Enforce Lite limits on save ──────────────────── */
add_filter( 'wp_insert_post_data', 'tk_enforce_limit', 10, 2 );
function tk_enforce_limit( $data, $postarr ) {
    if ( ! TK_LITE ) return $data;

    $limits = tk_limits();
    $type   = $data['post_type'];

    if ( ! isset( $limits[ $type ] ) ) return $data;

    // Only enforce when transitioning to a public status
    $public = [ 'publish', 'private', 'future' ];
    if ( ! in_array( $data['post_status'], $public, true ) ) return $data;

    // Always allow edits to already-public posts (re-saving stays permitted)
    if ( ! empty( $postarr['ID'] ) ) {
        $current = get_post_field( 'post_status', (int) $postarr['ID'] );
        if ( in_array( $current, $public, true ) ) return $data;
    }

    $counts = wp_count_posts( $type );
    $total  = (int) ( $counts->publish ?? 0 )
            + (int) ( $counts->private ?? 0 )
            + (int) ( $counts->future  ?? 0 );

    if ( $total >= $limits[ $type ] ) {
        $data['post_status'] = 'draft';
        set_transient( 'tk_over_limit_' . get_current_user_id(), [ 'type' => $type, 'limit' => $limits[ $type ] ], 60 );
    }

    return $data;
}

/* ── License AJAX: activate ───────────────────────── */
add_action( 'wp_ajax_tk_activate_license', 'tk_ajax_activate_license' );
function tk_ajax_activate_license() {
    check_ajax_referer( 'tk_license_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );

    $key = sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) );
    if ( ! $key ) wp_send_json_error( [ 'message' => __( 'Please enter a license key.', 'trailkit' ) ] );

    $res = wp_remote_post( TK_API_URL . '/api/licenses/validate', [
        'body'    => wp_json_encode( [
            'key'            => $key,
            'domain'         => wp_parse_url( home_url(), PHP_URL_HOST ),
            'plugin_version' => TK_VERSION,
            'wp_version'     => get_bloginfo( 'version' ),
        ] ),
        'headers' => [ 'Content-Type' => 'application/json' ],
        'timeout' => 12,
    ] );

    if ( is_wp_error( $res ) ) {
        wp_send_json_error( [ 'message' => __( 'Could not reach the license server. Check your internet connection.', 'trailkit' ) ] );
    }

    $body = json_decode( wp_remote_retrieve_body( $res ), true );

    if ( empty( $body['valid'] ) ) {
        wp_send_json_error( [ 'message' => $body['message'] ?? __( 'Invalid license key.', 'trailkit' ) ] );
    }

    update_option( 'tk_license_key',     $key );
    update_option( 'tk_license_status',  'active' );
    update_option( 'tk_license_expires', $body['expires_at'] ?? '' );
    set_transient( 'tk_pro_status', 'active', WEEK_IN_SECONDS );

    wp_send_json_success( [
        'message' => __( 'License activated successfully! Pro features are now unlocked.', 'trailkit' ),
        'expires' => $body['expires_at'] ?? '',
        'plan'    => $body['plan'] ?? 'pro',
    ] );
}

/* ── License AJAX: deactivate ─────────────────────── */
add_action( 'wp_ajax_tk_deactivate_license', 'tk_ajax_deactivate_license' );
function tk_ajax_deactivate_license() {
    check_ajax_referer( 'tk_license_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );

    delete_option( 'tk_license_key' );
    delete_option( 'tk_license_status' );
    delete_option( 'tk_license_expires' );
    delete_transient( 'tk_pro_status' );

    wp_send_json_success( [ 'message' => __( 'License removed.', 'trailkit' ) ] );
}

/* ── Lite limit admin notice ──────────────────────── */
add_action( 'admin_notices', 'tk_over_limit_notice' );
function tk_over_limit_notice() {
    $info = get_transient( 'tk_over_limit_' . get_current_user_id() );
    if ( ! $info ) return;
    delete_transient( 'tk_over_limit_' . get_current_user_id() );
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== $info['type'] ) return;

    printf(
        '<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
        esc_html__( 'TrailKit Lite limit reached.', 'trailkit' ),
        sprintf(
            /* translators: %1$d = limit, %2$s = upgrade link */
            esc_html__( 'You have reached the %1$d item limit. The item was saved as a Draft. %2$s', 'trailkit' ),
            intval( $info['limit'] ),
            '<a href="https://trailplugin.com" target="_blank">' . esc_html__( 'Upgrade to Pro →', 'trailkit' ) . '</a>'
        )
    );
}
