<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_CPT {

    public static function register() {
        self::register_route();
        self::register_poi();
        self::register_guide();
        self::register_taxonomies();
    }

    /* ── Route ──────────────────────────────────── */
    private static function register_route() {
        $labels = [
            'name'               => __( 'Routes',           'trailplugin' ),
            'singular_name'      => __( 'Route',            'trailplugin' ),
            'add_new_item'       => __( 'Add New Route',    'trailplugin' ),
            'edit_item'          => __( 'Edit Route',       'trailplugin' ),
            'new_item'           => __( 'New Route',        'trailplugin' ),
            'view_item'          => __( 'View Route',       'trailplugin' ),
            'search_items'       => __( 'Search Routes',    'trailplugin' ),
            'not_found'          => __( 'No routes found',  'trailplugin' ),
            'menu_name'          => __( 'Routes',           'trailplugin' ),
        ];

        register_post_type( 'tk_route', [
            'labels'        => $labels,
            'public'        => true,
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-location-alt',
            'menu_position' => 25,
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ],
            'rewrite'       => [ 'slug' => get_option( 'tk_route_slug', 'routes' ), 'with_front' => false ],
            'show_in_rest'  => true,
        ] );
    }

    /* ── Point of Interest ──────────────────────── */
    private static function register_poi() {
        $labels = [
            'name'               => __( 'Points of Interest', 'trailplugin' ),
            'singular_name'      => __( 'Point of Interest',  'trailplugin' ),
            'add_new_item'       => __( 'Add New POI',        'trailplugin' ),
            'edit_item'          => __( 'Edit POI',           'trailplugin' ),
            'search_items'       => __( 'Search POIs',        'trailplugin' ),
            'not_found'          => __( 'No POIs found',      'trailplugin' ),
            'menu_name'          => __( 'POIs',               'trailplugin' ),
        ];

        register_post_type( 'tk_poi', [
            'labels'        => $labels,
            'public'        => true,
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-flag',
            'menu_position' => 26,
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
            'rewrite'       => [ 'slug' => get_option( 'tk_poi_slug', 'points-of-interest' ), 'with_front' => false ],
            'show_in_rest'  => true,
        ] );
    }

    /* ── Guide ──────────────────────────────────── */
    private static function register_guide() {
        $labels = [
            'name'               => __( 'Guides',          'trailplugin' ),
            'singular_name'      => __( 'Guide',           'trailplugin' ),
            'add_new_item'       => __( 'Add New Guide',   'trailplugin' ),
            'edit_item'          => __( 'Edit Guide',      'trailplugin' ),
            'search_items'       => __( 'Search Guides',   'trailplugin' ),
            'not_found'          => __( 'No guides found', 'trailplugin' ),
            'menu_name'          => __( 'Guides',          'trailplugin' ),
        ];

        register_post_type( 'tk_guide', [
            'labels'        => $labels,
            'public'        => true,
            'has_archive'   => true,
            'menu_icon'     => 'dashicons-id-alt',
            'menu_position' => 27,
            'supports'      => [ 'title', 'editor', 'thumbnail' ],
            'rewrite'       => [ 'slug' => get_option( 'tk_guide_slug', 'guides' ), 'with_front' => false ],
            'show_in_rest'  => true,
        ] );
    }

    /* ── Taxonomies ─────────────────────────────── */
    private static function register_taxonomies() {

        // Activity type — shared across Route + POI + Guide
        register_taxonomy( 'tk_activity', [ 'tk_route', 'tk_poi', 'tk_guide' ], [
            'labels'            => [
                'name'          => __( 'Activities', 'trailplugin' ),
                'singular_name' => __( 'Activity',   'trailplugin' ),
            ],
            'hierarchical'  => false,
            'show_in_rest'  => true,
            'rewrite'       => [ 'slug' => 'activity' ],
        ] );

        // Region — shared across all three
        register_taxonomy( 'tk_region', [ 'tk_route', 'tk_poi', 'tk_guide' ], [
            'labels'            => [
                'name'          => __( 'Regions', 'trailplugin' ),
                'singular_name' => __( 'Region',  'trailplugin' ),
            ],
            'hierarchical'  => true,
            'show_in_rest'  => true,
            'rewrite'       => [ 'slug' => 'region' ],
        ] );

        // POI Type — only for POIs
        register_taxonomy( 'tk_poi_type', [ 'tk_poi' ], [
            'labels'            => [
                'name'          => __( 'POI Types',  'trailplugin' ),
                'singular_name' => __( 'POI Type',   'trailplugin' ),
            ],
            'hierarchical'  => true,
            'show_in_rest'  => true,
            'rewrite'       => [ 'slug' => 'poi-type' ],
        ] );
    }

    /* ── Default activity terms ─────────────────── */
    public static function insert_default_terms() {
        $activities = [
            'hiking', 'climbing', 'mountaineering', 'cycling', 'mountain-biking',
            'kayaking', 'diving', 'rappelling', 'camping', 'photography',
            'gastronomy', 'culture', '4x4', 'motorcycling',
        ];
        foreach ( $activities as $slug ) {
            if ( ! term_exists( $slug, 'tk_activity' ) ) {
                wp_insert_term( ucfirst( str_replace( '-', ' ', $slug ) ), 'tk_activity', [ 'slug' => $slug ] );
            }
        }

        $poi_types = [ 'viewpoint', 'waterfall', 'beach', 'village', 'cave', 'lagoon', 'peak', 'river', 'ruins' ];
        foreach ( $poi_types as $slug ) {
            if ( ! term_exists( $slug, 'tk_poi_type' ) ) {
                wp_insert_term( ucfirst( $slug ), 'tk_poi_type', [ 'slug' => $slug ] );
            }
        }
    }
}
