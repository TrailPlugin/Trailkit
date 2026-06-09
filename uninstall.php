<?php
/**
 * Fired when the plugin is deleted (not deactivated).
 * Removes all plugin data from the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// ── Options ───────────────────────────────────────
$options = [
    'tk_route_slug',
    'tk_poi_slug',
    'tk_guide_slug',
    'tk_default_lat',
    'tk_default_lng',
    'tk_default_zoom',
    'tk_demo_installed',
    'tk_license_key',
    'tk_license_status',
    'tk_license_expires',
];
foreach ( $options as $opt ) {
    delete_option( $opt );
}

// ── Transients ────────────────────────────────────
delete_transient( 'tk_pro_status' );

// Per-user transients (over-limit notices)
// Delete them for all users via direct DB query
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tk_over_limit_%' OR option_name LIKE '_transient_timeout_tk_over_limit_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

// ── Cron ─────────────────────────────────────────
wp_clear_scheduled_hook( 'tk_license_checkin' );

// ── Post meta (optional — uncomment to delete all content on uninstall) ──
// WARNING: This will permanently delete all routes, POIs, and guides.
// Only uncomment if you want aggressive cleanup.
/*
$post_types = [ 'tk_route', 'tk_poi', 'tk_guide' ];
foreach ( $post_types as $pt ) {
    $posts = get_posts( [ 'post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any' ] );
    foreach ( $posts as $post ) {
        wp_delete_post( $post->ID, true );
    }
}
*/
