<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Shortcodes {

    public static function register() {
        add_shortcode( 'tk_routes', [ self::class, 'routes'  ] );
        add_shortcode( 'tk_pois',   [ self::class, 'pois'    ] );
        add_shortcode( 'tk_guides', [ self::class, 'guides'  ] );
        add_shortcode( 'tk_map',    [ self::class, 'map'     ] );
    }

    /* ── [tk_routes] ────────────────────────────── */
    // Usage: [tk_routes activity="hiking" difficulty="moderate" region="andes" limit="6" columns="3"]
    public static function routes( $atts ) {
        $a = shortcode_atts( [
            'activity'   => '',
            'difficulty' => '',
            'region'     => '',
            'limit'      => 9,
            'columns'    => 3,
            'orderby'    => 'date',
            'order'      => 'DESC',
        ], $atts, 'tk_routes' );

        $args = [
            'post_type'      => 'tk_route',
            'posts_per_page' => intval( $a['limit'] ),
            'orderby'        => sanitize_key( $a['orderby'] ),
            'order'          => strtoupper( $a['order'] ) === 'ASC' ? 'ASC' : 'DESC',
            'post_status'    => 'publish',
        ];

        $tax_query = [];
        if ( $a['activity'] ) {
            $tax_query[] = [ 'taxonomy' => 'tk_activity', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['activity'] ) ) ];
        }
        if ( $a['region'] ) {
            $tax_query[] = [ 'taxonomy' => 'tk_region', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['region'] ) ) ];
        }
        if ( count( $tax_query ) > 1 ) $tax_query['relation'] = 'AND';
        if ( $tax_query ) $args['tax_query'] = $tax_query;

        if ( $a['difficulty'] ) {
            $args['meta_query'] = [ [ 'key' => '_tk_difficulty', 'value' => sanitize_text_field( $a['difficulty'] ) ] ];
        }

        $query = new WP_Query( $args );

        ob_start();
        if ( $query->have_posts() ) {
            echo '<div class="tk-grid tk-grid--' . intval( $a['columns'] ) . 'col">';
            while ( $query->have_posts() ) {
                $query->the_post();
                tk_get_template( 'route-card.php', [ 'post_id' => get_the_ID() ] );
            }
            echo '</div>';
        } else {
            echo '<p class="tk-empty">' . esc_html__( 'No routes found.', 'trailkit' ) . '</p>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    /* ── [tk_pois] ──────────────────────────────── */
    // Usage: [tk_pois type="waterfall" region="andes" limit="9" columns="3"]
    public static function pois( $atts ) {
        $a = shortcode_atts( [
            'type'    => '',
            'region'  => '',
            'limit'   => 9,
            'columns' => 3,
            'orderby' => 'date',
            'order'   => 'DESC',
        ], $atts, 'tk_pois' );

        $args = [
            'post_type'      => 'tk_poi',
            'posts_per_page' => intval( $a['limit'] ),
            'orderby'        => sanitize_key( $a['orderby'] ),
            'order'          => strtoupper( $a['order'] ) === 'ASC' ? 'ASC' : 'DESC',
            'post_status'    => 'publish',
        ];

        $tax_query = [];
        if ( $a['type'] ) {
            $tax_query[] = [ 'taxonomy' => 'tk_poi_type', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['type'] ) ) ];
        }
        if ( $a['region'] ) {
            $tax_query[] = [ 'taxonomy' => 'tk_region', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['region'] ) ) ];
        }
        if ( count( $tax_query ) > 1 ) $tax_query['relation'] = 'AND';
        if ( $tax_query ) $args['tax_query'] = $tax_query;

        $query = new WP_Query( $args );

        ob_start();
        if ( $query->have_posts() ) {
            echo '<div class="tk-grid tk-grid--' . intval( $a['columns'] ) . 'col">';
            while ( $query->have_posts() ) {
                $query->the_post();
                tk_get_template( 'poi-card.php', [ 'post_id' => get_the_ID() ] );
            }
            echo '</div>';
        } else {
            echo '<p class="tk-empty">' . esc_html__( 'No points of interest found.', 'trailkit' ) . '</p>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    /* ── [tk_guides] ────────────────────────────── */
    // Usage: [tk_guides featured="true" specialty="climbing" region="andes" limit="12" columns="3"]
    public static function guides( $atts ) {
        $a = shortcode_atts( [
            'featured'  => '',
            'specialty' => '',
            'region'    => '',
            'limit'     => 12,
            'columns'   => 3,
        ], $atts, 'tk_guides' );

        $args = [
            'post_type'      => 'tk_guide',
            'posts_per_page' => intval( $a['limit'] ),
            'post_status'    => 'publish',
            'orderby'        => 'meta_value',
            'meta_key'       => '_tk_is_featured',
            'order'          => 'DESC',
        ];

        if ( $a['featured'] === 'true' ) {
            $args['meta_query'] = [ [ 'key' => '_tk_is_featured', 'value' => '1' ] ];
        }

        if ( $a['region'] ) {
            $args['tax_query'] = [ [ 'taxonomy' => 'tk_region', 'field' => 'slug', 'terms' => array_map( 'trim', explode( ',', $a['region'] ) ) ] ];
        }

        $query = new WP_Query( $args );

        ob_start();
        if ( $query->have_posts() ) {
            echo '<div class="tk-grid tk-grid--' . intval( $a['columns'] ) . 'col">';
            while ( $query->have_posts() ) {
                $query->the_post();
                $g = TK_Guide_Fields::get( get_the_ID() );
                // Filter by specialty in PHP (JSON array search)
                if ( $a['specialty'] && $g['specialties'] ) {
                    $specs = json_decode( $g['specialties'], true ) ?: [];
                    if ( ! in_array( sanitize_text_field( $a['specialty'] ), $specs ) ) continue;
                }
                tk_get_template( 'guide-card.php', [ 'post_id' => get_the_ID(), 'guide' => $g ] );
            }
            echo '</div>';
        } else {
            echo '<p class="tk-empty">' . esc_html__( 'No guides found.', 'trailkit' ) . '</p>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    /* ── [tk_map] ───────────────────────────────── */
    // Usage: [tk_map type="routes" region="andes" height="450px" zoom="7" lat="10.48" lng="-66.90"]
    public static function map( $atts ) {
        $a = shortcode_atts( [
            'type'   => 'routes',   // routes | pois | all
            'region' => '',
            'height' => '450px',
            'zoom'   => '7',
            'lat'    => '',
            'lng'    => '',
        ], $atts, 'tk_map' );

        $map_id  = 'tk-map-' . wp_unique_id();
        $center_lat = $a['lat'] ?: get_option( 'tk_default_lat', '8.0' );
        $center_lng = $a['lng'] ?: get_option( 'tk_default_lng', '-66.0' );

        ob_start();
        ?>
        <div class="tk-map-wrap" style="height:<?php echo esc_attr($a['height']) ?>">
            <div id="<?php echo esc_attr($map_id) ?>" class="tk-map" style="width:100%;height:100%"></div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof L === 'undefined') return;
            tkInitMap(<?php echo json_encode([
                'id'     => $map_id,
                'lat'    => floatval($center_lat),
                'lng'    => floatval($center_lng),
                'zoom'   => intval($a['zoom']),
                'type'   => sanitize_key($a['type']),
                'region' => sanitize_text_field($a['region']),
            ]) ?>);
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
