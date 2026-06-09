<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * TK_Demo — creates sample Routes, POIs, Guides and demo pages.
 * Matches Lite limits: 3 routes, 3 POIs, 1 guide.
 */
class TK_Demo {

    /* ── Public entry point ─────────────────────── */
    public static function install() {
        if ( get_option( 'tk_demo_installed' ) ) return;

        self::maybe_create_terms();

        self::create_routes();
        self::create_pois();
        self::create_guides();
        self::create_pages();

        update_option( 'tk_demo_installed', '1' );
    }

    public static function uninstall() {
        $posts = get_posts( [
            'post_type'   => [ 'tk_route', 'tk_poi', 'tk_guide', 'page' ],
            'meta_key'    => '_tk_is_demo',
            'meta_value'  => '1',
            'numberposts' => -1,
            'post_status' => 'any',
        ] );
        foreach ( $posts as $p ) {
            wp_delete_post( $p->ID, true );
        }
        delete_option( 'tk_demo_installed' );
    }

    /* ── Terms ──────────────────────────────────── */
    private static function maybe_create_terms() {
        TK_CPT::insert_default_terms();

        $regions = [
            'gran-sabana' => 'Gran Sabana',
            'andes'       => 'Andes',
            'caribe'      => 'Caribbean Coast',
        ];
        foreach ( $regions as $slug => $name ) {
            if ( ! term_exists( $slug, 'tk_region' ) ) {
                wp_insert_term( $name, 'tk_region', [ 'slug' => $slug ] );
            }
        }
    }

    /* ── Routes (3) ─────────────────────────────── */
    private static function create_routes() {
        $routes = [
            [
                'title'    => 'Roraima Trek',
                'excerpt'  => 'The legendary tabletop mountain on the Venezuela-Brazil-Guyana border. One of the most iconic treks in South America with unique flora found nowhere else on Earth.',
                'content'  => '<p>Mount Roraima is the highest of the Pakaraima chain of tepuis plateau in South America. First described by Sir Walter Raleigh in 1596, its 31 km² summit area is bounded on all sides by sheer 400-metre cliffs.</p><p>The trek begins at the village of Paraitepui and takes approximately 6 days round trip. The trail crosses savannas, cloud forests, and the mystical summit plateau covered in prehistoric plants.</p>',
                'meta'     => [
                    '_tk_difficulty'    => 'hard',
                    '_tk_distance'      => '35',
                    '_tk_elevation'     => '1400',
                    '_tk_time'          => '5-6 days',
                    '_tk_lat'           => '5.1429',
                    '_tk_lng'           => '-60.7625',
                    '_tk_gmaps_url'     => 'https://maps.google.com/?q=5.1429,-60.7625',
                    '_tk_hero_position' => 'center 30%',
                ],
                'activity' => 'hiking',
                'region'   => 'gran-sabana',
            ],
            [
                'title'    => 'Pico Bolívar Summit',
                'excerpt'  => 'The highest peak in Venezuela at 4,978 m. A challenging mountaineering route through glaciers and rocky ridges with breathtaking Andean panoramas.',
                'content'  => '<p>Pico Bolívar is the highest mountain in Venezuela, located in the Cordillera de Mérida. The standard route departs from the upper station of the Mérida Cable Car at 4,765 m and involves technical sections on mixed terrain.</p><p>Acclimatization is essential. Spend at least 2–3 days in Mérida at altitude before attempting the summit.</p>',
                'meta'     => [
                    '_tk_difficulty'    => 'extreme',
                    '_tk_distance'      => '12',
                    '_tk_elevation'     => '2800',
                    '_tk_time'          => '2 days',
                    '_tk_lat'           => '8.5396',
                    '_tk_lng'           => '-71.0426',
                    '_tk_gmaps_url'     => 'https://maps.google.com/?q=8.5396,-71.0426',
                    '_tk_hero_position' => 'center center',
                ],
                'activity' => 'mountaineering',
                'region'   => 'andes',
            ],
            [
                'title'    => 'Chorros de Milla Loop',
                'excerpt'  => 'A relaxing half-day loop near Mérida through cloud forest and past scenic waterfalls. Perfect for families and beginners.',
                'content'  => '<p>The Chorros de Milla trail is one of the most accessible hikes near Mérida city. The loop takes you through lush cloud forest to a series of small waterfalls before returning via a viewpoint overlooking the valley.</p><p>The trail is well-marked and suitable for all fitness levels — a great warm-up before tackling more challenging Andean routes.</p>',
                'meta'     => [
                    '_tk_difficulty'    => 'easy',
                    '_tk_distance'      => '8',
                    '_tk_elevation'     => '350',
                    '_tk_time'          => '3-4 hours',
                    '_tk_lat'           => '8.5925',
                    '_tk_lng'           => '-71.1724',
                    '_tk_gmaps_url'     => 'https://maps.google.com/?q=8.5925,-71.1724',
                    '_tk_hero_position' => 'center 40%',
                ],
                'activity' => 'hiking',
                'region'   => 'andes',
            ],
        ];

        foreach ( $routes as $r ) {
            $id = wp_insert_post( [
                'post_title'   => $r['title'],
                'post_excerpt' => $r['excerpt'],
                'post_content' => $r['content'],
                'post_type'    => 'tk_route',
                'post_status'  => 'publish',
            ] );
            if ( is_wp_error( $id ) ) continue;

            foreach ( $r['meta'] as $k => $v ) update_post_meta( $id, $k, $v );
            update_post_meta( $id, '_tk_is_demo', '1' );

            $term = get_term_by( 'slug', $r['activity'], 'tk_activity' );
            if ( $term ) wp_set_object_terms( $id, $term->term_id, 'tk_activity' );
            $term = get_term_by( 'slug', $r['region'], 'tk_region' );
            if ( $term ) wp_set_object_terms( $id, $term->term_id, 'tk_region' );
        }
    }

    /* ── POIs (3) ───────────────────────────────── */
    private static function create_pois() {
        $pois = [
            [
                'title'   => 'Angel Falls (Salto Ángel)',
                'excerpt' => "The world's highest uninterrupted waterfall at 979 m, plunging from the Auyán-tepui plateau in Canaima National Park.",
                'content' => "<p>Angel Falls is the world's highest uninterrupted waterfall, with a height of 979 metres and a plunge of 807 metres. Access is by small aircraft to Canaima village, followed by a motorized canoe journey and a short jungle hike.</p><p>The falls are most impressive during and just after the rainy season (June–November).</p>",
                'meta'    => [
                    '_tk_lat'               => '5.9697',
                    '_tk_lng'               => '-62.5353',
                    '_tk_conditions_alert'  => 'Best visited Jun–Nov during rainy season',
                    '_tk_gmaps_url'         => 'https://maps.google.com/?q=5.9697,-62.5353',
                ],
                'type'   => 'waterfall',
                'region' => 'gran-sabana',
            ],
            [
                'title'   => 'Los Roques Archipelago',
                'excerpt' => 'A UNESCO Biosphere Reserve of coral reefs, white-sand beaches, and crystal-clear turquoise lagoons 166 km north of Caracas.',
                'content' => '<p>The Los Roques Archipelago National Park covers 225,153 hectares and consists of approximately 350 islands, islets, and reefs. The lagoon waters display spectacular shades of turquoise and emerald green.</p><p>Activities include snorkeling, diving, kite surfing, and bone fishing.</p>',
                'meta'    => [
                    '_tk_lat'       => '11.9333',
                    '_tk_lng'       => '-66.6667',
                    '_tk_gmaps_url' => 'https://maps.google.com/?q=11.9333,-66.6667',
                ],
                'type'   => 'beach',
                'region' => 'caribe',
            ],
            [
                'title'   => 'Laguna de Mucubají',
                'excerpt' => 'A stunning glacial lake at 3,550 m in the Mérida Andes, surrounded by the endemic frailejón plants of the high páramo.',
                'content' => '<p>Laguna de Mucubají is one of Venezuela\'s most accessible high-altitude lakes, located within the Sierra Nevada National Park. The lake sits in a glacial cirque carved during the last ice age.</p><p>The surrounding páramo is covered in frailejones (Espeletia), giant rosette plants endemic to the northern Andes.</p>',
                'meta'    => [
                    '_tk_lat'       => '8.7706',
                    '_tk_lng'       => '-70.8175',
                    '_tk_gmaps_url' => 'https://maps.google.com/?q=8.7706,-70.8175',
                ],
                'type'   => 'lagoon',
                'region' => 'andes',
            ],
        ];

        foreach ( $pois as $p ) {
            $id = wp_insert_post( [
                'post_title'   => $p['title'],
                'post_excerpt' => $p['excerpt'],
                'post_content' => $p['content'],
                'post_type'    => 'tk_poi',
                'post_status'  => 'publish',
            ] );
            if ( is_wp_error( $id ) ) continue;

            foreach ( $p['meta'] as $k => $v ) update_post_meta( $id, $k, $v );
            update_post_meta( $id, '_tk_is_demo', '1' );

            $term = get_term_by( 'slug', $p['type'], 'tk_poi_type' );
            if ( $term ) wp_set_object_terms( $id, $term->term_id, 'tk_poi_type' );
            $term = get_term_by( 'slug', $p['region'], 'tk_region' );
            if ( $term ) wp_set_object_terms( $id, $term->term_id, 'tk_region' );
        }
    }

    /* ── Guides (1) ─────────────────────────────── */
    private static function create_guides() {
        $id = wp_insert_post( [
            'post_title'   => 'Carlos Rodríguez',
            'post_content' => 'Certified mountain guide with 15 years leading expeditions across the Venezuelan Andes. Specializes in multi-day treks to Pico Bolívar and the Sierra Nevada traverse. Fluent in English and Spanish.',
            'post_type'    => 'tk_guide',
            'post_status'  => 'publish',
        ] );
        if ( is_wp_error( $id ) ) return;

        $meta = [
            '_tk_whatsapp'    => '+58 414 555 0001',
            '_tk_email'       => 'carlos@example.com',
            '_tk_instagram'   => 'carlos_andes_guide',
            '_tk_price_from'  => '80',
            '_tk_specialties' => '["mountaineering","hiking","camping"]',
            '_tk_is_featured' => '1',
            '_tk_lat'         => '8.5916',
            '_tk_lng'         => '-71.1441',
            '_tk_radius_km'   => '150',
        ];
        foreach ( $meta as $k => $v ) update_post_meta( $id, $k, $v );
        update_post_meta( $id, '_tk_is_demo', '1' );

        $term = get_term_by( 'slug', 'andes', 'tk_region' );
        if ( $term ) wp_set_object_terms( $id, $term->term_id, 'tk_region' );
    }

    /* ── Demo pages ─────────────────────────────── */
    private static function create_pages() {
        $pages = [
            [
                'title'   => 'Routes',
                'slug'    => 'trailplugin-routes',
                'content' => '<!-- wp:paragraph --><p>Explore our curated collection of adventure routes. Filter by activity, difficulty, or region.</p><!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tk_routes columns="3" limit="9"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[tk_map type="routes" height="450px"]
<!-- /wp:shortcode -->',
            ],
            [
                'title'   => 'Points of Interest',
                'slug'    => 'trailplugin-pois',
                'content' => '<!-- wp:paragraph --><p>Discover remarkable natural and cultural points of interest across the region.</p><!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tk_pois columns="3" limit="9"]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[tk_map type="pois" height="400px"]
<!-- /wp:shortcode -->',
            ],
            [
                'title'   => 'Guides',
                'slug'    => 'trailplugin-guides',
                'content' => '<!-- wp:paragraph --><p>Connect with certified local guides for a safe and authentic adventure experience.</p><!-- /wp:paragraph -->

<!-- wp:shortcode -->
[tk_guides columns="3" limit="6"]
<!-- /wp:shortcode -->',
            ],
        ];

        foreach ( $pages as $p ) {
            if ( get_page_by_path( $p['slug'] ) ) continue;
            $id = wp_insert_post( [
                'post_title'   => $p['title'],
                'post_name'    => $p['slug'],
                'post_content' => $p['content'],
                'post_type'    => 'page',
                'post_status'  => 'publish',
            ] );
            if ( ! is_wp_error( $id ) ) {
                update_post_meta( $id, '_tk_is_demo', '1' );
            }
        }
    }
}
