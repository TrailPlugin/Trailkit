<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Route_Fields {

    public static function init() {
        add_action( 'add_meta_boxes', [ self::class, 'register_meta_boxes' ] );
        add_action( 'save_post_tk_route', [ self::class, 'save' ], 10, 2 );

        if ( TK_LITE ) {
            add_action( 'admin_notices', [ self::class, 'lite_notice' ] );
        }
    }

    /* ── Native meta boxes (no ACF) ────────────── */
    public static function register_meta_boxes() {
        add_meta_box( 'tk-route-import', __( 'Import from TrailKit Planner', 'trailkit' ), [ self::class, 'box_import' ], 'tk_route', 'normal', 'high' );
        add_meta_box( 'tk-route-stats',  __( 'Route Details', 'trailkit' ),    [ self::class, 'box_stats'  ], 'tk_route', 'normal', 'high' );
        add_meta_box( 'tk-route-gps',    __( 'GPS & Map',     'trailkit' ),    [ self::class, 'box_gps'    ], 'tk_route', 'normal' );
        add_meta_box( 'tk-route-media',  __( 'Gallery & Links', 'trailkit' ),  [ self::class, 'box_media'  ], 'tk_route', 'side' );
        add_meta_box( 'tk-route-embed',  __( 'Map Embed / Shortcode', 'trailkit' ), [ 'TK_Shortcodes', 'embed_builder_box' ], 'tk_route', 'side', 'default', [ 'toggles' => [
            'title'      => __( 'Title',              'trailkit' ),
            'difficulty' => __( 'Difficulty',         'trailkit' ),
            'distance'   => __( 'Distance',           'trailkit' ),
            'elevation'  => __( 'Elevation gain',     'trailkit' ),
            'time'       => __( 'Estimated time',     'trailkit' ),
            'gallery'    => __( 'Gallery',            'trailkit' ),
            'mapsbtn'    => __( 'Google Maps button', 'trailkit' ),
        ] ] );
    }

    public static function box_import( $post ) {
        ?>
        <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
            <?php esc_html_e( 'Paste the JSON copied from the TrailKit Planner (⎘ Copy JSON) or a GPX file content. Auto-fills distance, elevation, start coords, and GPS track.', 'trailkit' ) ?>
        </p>
        <textarea id="tk-route-import-data" rows="5" placeholder="<?php esc_attr_e( 'Paste route JSON or GPX content here…', 'trailkit' ) ?>" style="width:100%;font-family:monospace;font-size:11px;border:1px solid #d1d5db;border-radius:4px;padding:6px 8px;box-sizing:border-box;resize:vertical"></textarea>
        <button type="button" id="tk-route-import-btn" class="button button-secondary" style="margin-top:6px">
            <?php esc_html_e( 'Import', 'trailkit' ) ?>
        </button>
        <span id="tk-route-import-msg" style="margin-left:8px;font-size:12px;color:#059669;display:none"><?php esc_html_e( '✓ Fields filled — save the post to apply.', 'trailkit' ) ?></span>
        <span id="tk-route-import-err" style="margin-left:8px;font-size:12px;color:#dc2626;display:none"><?php esc_html_e( 'Could not parse — check the pasted content.', 'trailkit' ) ?></span>
        <script>
        (function(){
            function fmtDur(sec) {
                var h = Math.floor(sec / 3600), m = Math.floor((sec % 3600) / 60);
                return h > 0 ? h + 'h ' + m + 'm' : m + ' min';
            }
            function fillRoute(d) {
                if (d.distance_km  != null) document.querySelector('[name="_tk_distance"]').value  = parseFloat(d.distance_km).toFixed(2);
                if (d.elev_gain    != null) document.querySelector('[name="_tk_elevation"]').value = Math.round(d.elev_gain);
                if (d.duration_sec != null) document.querySelector('[name="_tk_time"]').value      = fmtDur(d.duration_sec);
                if (d.start) {
                    if (d.start.lat != null) document.querySelector('[name="_tk_lat"]').value = d.start.lat;
                    if (d.start.lng != null) document.querySelector('[name="_tk_lng"]').value = d.start.lng;
                }
                if (d.points && d.points.length) {
                    document.querySelector('[name="_tk_points"]').value = JSON.stringify(d.points);
                    if (!d.start) {
                        document.querySelector('[name="_tk_lat"]').value = d.points[0].lat;
                        document.querySelector('[name="_tk_lng"]').value = d.points[0].lng;
                    }
                }
            }
            document.getElementById('tk-route-import-btn').addEventListener('click', function(){
                var msg  = document.getElementById('tk-route-import-msg');
                var err  = document.getElementById('tk-route-import-err');
                var text = document.getElementById('tk-route-import-data').value.trim();
                msg.style.display = err.style.display = 'none';
                try {
                    var d;
                    if (text.startsWith('<')) {
                        var xml   = new DOMParser().parseFromString(text, 'text/xml');
                        var trkpts = Array.from(xml.querySelectorAll('trkpt'));
                        if (!trkpts.length) throw new Error('no trkpt');
                        var points = trkpts.map(function(pt){
                            var ele = pt.querySelector('ele');
                            return { lat: parseFloat(pt.getAttribute('lat')), lng: parseFloat(pt.getAttribute('lon')), ele: ele ? parseFloat(ele.textContent) : 0 };
                        });
                        d = { points: points, start: { lat: points[0].lat, lng: points[0].lng } };
                    } else {
                        d = JSON.parse(text);
                    }
                    fillRoute(d);
                    msg.style.display = 'inline';
                } catch(e) {
                    err.style.display = 'inline';
                }
            });
        })();
        </script>
        <?php
    }

    public static function box_stats( $post ) {
        wp_nonce_field( 'tk_route_save', 'tk_route_nonce' );
        $d = self::get( $post->ID );
        ?>
        <table class="tk-meta-table">
            <tr>
                <td><label><?php esc_html_e('Difficulty','trailkit') ?></label>
                <select name="_tk_difficulty">
                    <?php foreach ( ['easy'=>'Easy','moderate'=>'Moderate','hard'=>'Hard','extreme'=>'Extreme'] as $v => $l ): ?>
                        <option value="<?php echo esc_attr($v) ?>" <?php selected($d['difficulty'],$v) ?>><?php echo esc_html($l) ?></option>
                    <?php endforeach; ?>
                </select></td>
                <td><label><?php esc_html_e('Distance (km)','trailkit') ?></label>
                <input type="number" step="0.1" name="_tk_distance" value="<?php echo esc_attr($d['distance']) ?>"></td>
            </tr>
            <tr>
                <td><label><?php esc_html_e('Elevation gain (m)','trailkit') ?></label>
                <input type="number" name="_tk_elevation" value="<?php echo esc_attr($d['elevation']) ?>"></td>
                <td><label><?php esc_html_e('Estimated time','trailkit') ?></label>
                <input type="text" name="_tk_time" value="<?php echo esc_attr($d['time']) ?>" placeholder="e.g. 3-4 hours"></td>
            </tr>
            <tr>
                <td><label><?php esc_html_e('Start latitude','trailkit') ?></label>
                <input type="text" name="_tk_lat" value="<?php echo esc_attr($d['lat']) ?>" placeholder="10.4806"></td>
                <td><label><?php esc_html_e('Start longitude','trailkit') ?></label>
                <input type="text" name="_tk_lng" value="<?php echo esc_attr($d['lng']) ?>" placeholder="-66.9036"></td>
            </tr>
            <tr>
                <td colspan="2"><label><?php esc_html_e('Conditions alert (optional)','trailkit') ?></label>
                <input type="text" name="_tk_conditions_alert" value="<?php echo esc_attr($d['conditions_alert']) ?>" style="width:100%" placeholder="e.g. Trail closed in rainy season"></td>
            </tr>
            <tr>
                <td><label><?php esc_html_e('Hero image position','trailkit') ?></label>
                <input type="text" name="_tk_hero_position" value="<?php echo esc_attr($d['hero_position']) ?>" placeholder="center center"></td>
                <td><label><?php esc_html_e('Google Maps link','trailkit') ?></label>
                <input type="url" name="_tk_gmaps_url" value="<?php echo esc_attr($d['gmaps_url']) ?>" style="width:100%"></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top:10px;border-top:1px solid #e5e7eb">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                        <input type="checkbox" name="_tk_weather_enabled" value="1" <?php checked( $d['weather_enabled'], '1' ) ?>
                            <?php if ( TK_LITE ) echo 'disabled'; ?>>
                        <span><?php esc_html_e( 'Show live weather widget alongside difficulty', 'trailkit' ) ?></span>
                        <?php if ( TK_LITE ): ?>
                            <span style="background:#fef3c7;color:#92400e;font-size:0.72em;padding:2px 7px;border-radius:4px;font-weight:700">
                                <?php esc_html_e( 'Pro', 'trailkit' ) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#6b7280;font-size:0.8em">
                                <?php esc_html_e( '(fetches from Open-Meteo — free, no API key needed)', 'trailkit' ) ?>
                            </span>
                        <?php endif; ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function box_gps( $post ) {
        $points = get_post_meta( $post->ID, '_tk_points', true );
        $json   = $points ? $points : '';
        ?>
        <p class="description">
            <?php esc_html_e( 'Paste a JSON array of GPS points:', 'trailkit' ) ?>
            <code>[{"lat":10.48,"lng":-66.90,"ele":900},...]</code>.
            <?php esc_html_e( 'Or upload a .gpx file.', 'trailkit' ) ?>
        </p>
        <textarea name="_tk_points" rows="6" style="width:100%;font-family:monospace;font-size:12px"><?php echo esc_textarea( $json ) ?></textarea>
        <?php if ( ! TK_LITE ): ?>
        <p style="margin-top:8px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <button type="button" class="button tk-gpx-trigger" id="tk-gpx-trigger">
                <?php esc_html_e( 'Import GPX', 'trailkit' ) ?>
            </button>
            <input type="file" id="tk-gpx-file" name="tk_gpx_file" accept=".gpx" style="display:none">
            <span id="tk-gpx-status" style="font-size:0.85em;color:#6b7280"></span>
        </p>
        <?php else: ?>
        <p class="tk-lite-note"><?php esc_html_e( 'GPX import available in TrailKit Pro.', 'trailkit' ) ?></p>
        <?php endif; ?>
        <?php
    }

    public static function box_media( $post ) {
        $d = self::get( $post->ID );
        ?>
        <?php if ( TK_LITE ): ?>
        <p class="description" style="margin-bottom:6px;color:#b45309">
            <?php
            printf(
                /* translators: %1$d = max gallery images, %2$s = upgrade link (HTML anchor) */
                esc_html__( '⚠ Lite: max %1$d images. %2$s for unlimited.', 'trailkit' ),
                intval( TK_GALLERY_LIMIT ),
                '<a href="' . esc_url( 'https://trailplugin.com' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Upgrade to Pro', 'trailkit' ) . '</a>'
            );
            ?>
        </p>
        <?php endif; ?>
        <p><label><?php esc_html_e('Gallery','trailkit') ?></label></p>
        <input type="hidden" name="_tk_gallery" id="tk_gallery_ids" value="<?php echo esc_attr($d['gallery']) ?>">
        <div id="tk-gallery-preview" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
            <?php
            $ids = $d['gallery'] ? json_decode( $d['gallery'], true ) : [];
            foreach ( (array) $ids as $id ) {
                $src = wp_get_attachment_image_url( $id, 'thumbnail' );
                if ( $src ) echo '<img src="' . esc_url($src) . '" width="60" height="60" style="object-fit:cover;border-radius:4px">';
            }
            ?>
        </div>
        <button type="button" class="button tk-gallery-btn" data-target="tk_gallery_ids" data-preview="tk-gallery-preview"><?php esc_html_e('Select Images','trailkit') ?></button>
        <?php
    }

    /* ── Save ───────────────────────────────────── */
    public static function save( $post_id, $post ) {
        if ( ! isset( $_POST['tk_route_nonce'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tk_route_nonce'] ) ), 'tk_route_save' ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = [
            '_tk_difficulty'       => 'sanitize_text_field',
            '_tk_distance'         => 'floatval',
            '_tk_elevation'        => 'intval',
            '_tk_time'             => 'sanitize_text_field',
            '_tk_lat'              => 'sanitize_text_field',
            '_tk_lng'              => 'sanitize_text_field',
            '_tk_conditions_alert' => 'sanitize_text_field',
            '_tk_hero_position'    => 'sanitize_text_field',
            '_tk_gmaps_url'        => 'esc_url_raw',
            '_tk_gallery'          => 'tk_sanitize_gallery',
        ];

        foreach ( $fields as $key => $fn ) {
            if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                update_post_meta( $post_id, $key, $fn( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            }
        }

        // Weather toggle — checkbox (absent when unchecked)
        update_post_meta( $post_id, '_tk_weather_enabled', isset( $_POST['_tk_weather_enabled'] ) && ! TK_LITE ? '1' : '' );

        // GPS points — JSON string from client-side GPX parser; json_decode/encode round-trip validates structure.
        if ( isset( $_POST['_tk_points'] ) ) {
            $raw    = wp_unslash( $_POST['_tk_points'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $parsed = json_decode( $raw, true );
            update_post_meta( $post_id, '_tk_points', $parsed !== null ? wp_slash( json_encode( $parsed ) ) : '' );
        }
    }

    /* ── Data getter ────────────────────────────── */
    public static function get( $post_id ) {
        return [
            'difficulty'       => get_post_meta( $post_id, '_tk_difficulty',       true ) ?: 'moderate',
            'distance'         => get_post_meta( $post_id, '_tk_distance',         true ),
            'elevation'        => get_post_meta( $post_id, '_tk_elevation',        true ),
            'time'             => get_post_meta( $post_id, '_tk_time',             true ),
            'lat'              => get_post_meta( $post_id, '_tk_lat',              true ),
            'lng'              => get_post_meta( $post_id, '_tk_lng',              true ),
            'conditions_alert' => get_post_meta( $post_id, '_tk_conditions_alert', true ),
            'gmaps_url'        => get_post_meta( $post_id, '_tk_gmaps_url',        true ),
            'hero_position'    => get_post_meta( $post_id, '_tk_hero_position',    true ) ?: 'center center',
            'gallery'          => get_post_meta( $post_id, '_tk_gallery',          true ),
            'points'           => get_post_meta( $post_id, '_tk_points',           true ),
            'weather_enabled'  => get_post_meta( $post_id, '_tk_weather_enabled',  true ),
        ];
    }

    /* ── Lite notice ────────────────────────────── */
    public static function lite_notice() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'tk_route' ) return;
        if ( ! tk_at_limit( 'tk_route' ) ) return;
        printf(
            '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
            esc_html__( 'TrailKit Lite limit reached.', 'trailkit' ),
            sprintf(
                /* translators: %1$d = route limit, %2$s = upgrade link */
                esc_html__( 'You have reached the %1$d-route limit. %2$s for unlimited routes.', 'trailkit' ),
                intval( TK_LIMIT ),
                '<a href="' . esc_url( 'https://trailplugin.com' ) . '" target="_blank">' . esc_html__( 'Upgrade to Pro', 'trailkit' ) . '</a>'
            )
        );
    }
}
