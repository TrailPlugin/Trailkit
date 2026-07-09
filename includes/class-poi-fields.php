<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_POI_Fields {

    public static function init() {
        add_action( 'add_meta_boxes', [ self::class, 'register_meta_boxes' ] );
        add_action( 'save_post_tk_poi', [ self::class, 'save' ], 10, 2 );
        if ( TK_LITE ) add_action( 'admin_notices', [ self::class, 'lite_notice' ] );
    }

    /* ── Native meta boxes ──────────────────────── */
    public static function register_meta_boxes() {
        add_meta_box( 'tk-poi-import',   __( 'Import from TrailKit Planner', 'trailkit' ), [ self::class, 'box_import'   ], 'tk_poi', 'normal', 'high' );
        add_meta_box( 'tk-poi-location', __( 'Location & Details',           'trailkit' ), [ self::class, 'box_location' ], 'tk_poi', 'normal', 'high' );
        add_meta_box( 'tk-poi-media',    __( 'Gallery & Links',              'trailkit' ), [ self::class, 'box_media'    ], 'tk_poi', 'side' );
        add_meta_box( 'tk-poi-embed',    __( 'Map Embed / Shortcode',        'trailkit' ), [ 'TK_Shortcodes', 'embed_builder_box' ], 'tk_poi', 'side', 'default', [ 'toggles' => [
            'title'       => __( 'Title',              'trailkit' ),
            'category'    => __( 'Category',           'trailkit' ),
            'coords'      => __( 'Coordinates',        'trailkit' ),
            'description' => __( 'Description',         'trailkit' ),
            'gallery'     => __( 'Gallery',            'trailkit' ),
            'mapsbtn'     => __( 'Google Maps button', 'trailkit' ),
        ] ] );
    }

    public static function box_import( $post ) {
        ?>
        <p style="margin:0 0 8px;color:#6b7280;font-size:13px">
            <?php esc_html_e( 'Paste a GPX file (open it in a text editor and copy all) or the JSON copied from the TrailKit Planner, then click Import.', 'trailkit' ) ?>
        </p>
        <textarea id="tk-poi-import-data" rows="5" placeholder="<?php esc_attr_e( 'Paste GPX content or JSON here…', 'trailkit' ) ?>" style="width:100%;font-family:monospace;font-size:11px;border:1px solid #d1d5db;border-radius:4px;padding:6px 8px;box-sizing:border-box;resize:vertical"></textarea>
        <button type="button" id="tk-poi-import-btn" class="button button-secondary" style="margin-top:6px">
            <?php esc_html_e( 'Import', 'trailkit' ) ?>
        </button>
        <span id="tk-poi-import-msg" style="margin-left:8px;font-size:12px;color:#059669;display:none"><?php esc_html_e( '✓ Fields filled — save the post to apply.', 'trailkit' ) ?></span>
        <span id="tk-poi-import-err" style="margin-left:8px;font-size:12px;color:#dc2626;display:none"><?php esc_html_e( 'Could not parse — check the pasted content.', 'trailkit' ) ?></span>
        <script>
        (function(){
            function fillFields(d) {
                if (d.name) {
                    var t = document.getElementById('title');
                    if (t) t.value = d.name;
                }
                if (d.lat  != null) document.querySelector('[name="_tk_lat"]').value         = d.lat;
                if (d.lng  != null) document.querySelector('[name="_tk_lng"]').value         = d.lng;
                if (d.category)     document.querySelector('[name="_tk_category"]').value    = d.category;
                if (d.description)  document.querySelector('[name="_tk_description"]').value = d.description;
                if (d.lat != null && d.lng != null) {
                    document.querySelector('[name="_tk_gmaps_url"]').value =
                        'https://www.google.com/maps?q=' + parseFloat(d.lat).toFixed(7) + ',' + parseFloat(d.lng).toFixed(7);
                }
            }

            document.getElementById('tk-poi-import-btn').addEventListener('click', function(){
                var msg  = document.getElementById('tk-poi-import-msg');
                var err  = document.getElementById('tk-poi-import-err');
                var text = document.getElementById('tk-poi-import-data').value.trim();
                msg.style.display = err.style.display = 'none';
                try {
                    var d;
                    if (text.startsWith('<')) {
                        // GPX / XML — grab first <wpt> element
                        var xml = new DOMParser().parseFromString(text, 'text/xml');
                        var wpt = xml.querySelector('wpt');
                        if (!wpt) throw new Error('no wpt');
                        var get = function(tag){ var el = wpt.querySelector(tag); return el ? el.textContent.trim() : ''; };
                        d = {
                            name:        get('name'),
                            lat:         parseFloat(wpt.getAttribute('lat')),
                            lng:         parseFloat(wpt.getAttribute('lon')),
                            category:    get('type'),
                            description: get('desc'),
                        };
                    } else {
                        // JSON (single object or first item of array)
                        var parsed = JSON.parse(text);
                        d = Array.isArray(parsed) ? parsed[0] : parsed;
                    }
                    fillFields(d);
                    msg.style.display = 'inline';
                } catch(e) {
                    err.style.display = 'inline';
                }
            });
        })();
        </script>
        <?php
    }

    public static function box_location( $post ) {
        wp_nonce_field( 'tk_poi_save', 'tk_poi_nonce' );
        $d = self::get( $post->ID );
        ?>
        <table class="tk-meta-table">
            <tr>
                <td><label><?php esc_html_e('Latitude','trailkit') ?></label>
                <input type="text" name="_tk_lat" value="<?php echo esc_attr($d['lat']) ?>" placeholder="10.4806"></td>
                <td><label><?php esc_html_e('Longitude','trailkit') ?></label>
                <input type="text" name="_tk_lng" value="<?php echo esc_attr($d['lng']) ?>" placeholder="-66.9036"></td>
            </tr>
            <tr>
                <td colspan="2"><label><?php esc_html_e('Category','trailkit') ?></label>
                <input type="text" name="_tk_category" value="<?php echo esc_attr($d['category']) ?>" placeholder="e.g. Waterfall, Mountain, Cave…" style="width:100%"></td>
            </tr>
            <tr>
                <td colspan="2"><label><?php esc_html_e('Description','trailkit') ?></label>
                <textarea name="_tk_description" rows="3" style="width:100%;resize:vertical"><?php echo esc_textarea($d['description']) ?></textarea></td>
            </tr>
            <tr>
                <td colspan="2"><label><?php esc_html_e('Conditions alert (optional)','trailkit') ?></label>
                <input type="text" name="_tk_conditions_alert" value="<?php echo esc_attr($d['conditions_alert']) ?>" style="width:100%"></td>
            </tr>
            <tr>
                <td><label><?php esc_html_e('Hero image position','trailkit') ?></label>
                <input type="text" name="_tk_hero_position" value="<?php echo esc_attr($d['hero_position']) ?>" placeholder="center center"></td>
                <td><label><?php esc_html_e('Google Maps link','trailkit') ?></label>
                <input type="url" name="_tk_gmaps_url" value="<?php echo esc_attr($d['gmaps_url']) ?>" style="width:100%"></td>
            </tr>
        </table>
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
        <input type="hidden" name="_tk_gallery" id="tk_poi_gallery_ids" value="<?php echo esc_attr($d['gallery']) ?>">
        <div id="tk-poi-gallery-preview" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
            <?php
            $ids = $d['gallery'] ? json_decode( $d['gallery'], true ) : [];
            foreach ( (array) $ids as $id ) {
                $src = wp_get_attachment_image_url( $id, 'thumbnail' );
                if ( $src ) echo '<img src="' . esc_url($src) . '" width="60" height="60" style="object-fit:cover;border-radius:4px">';
            }
            ?>
        </div>
        <button type="button" class="button tk-gallery-btn" data-target="tk_poi_gallery_ids" data-preview="tk-poi-gallery-preview"><?php esc_html_e('Select Images','trailkit') ?></button>
        <?php
    }

    /* ── Save ───────────────────────────────────── */
    public static function save( $post_id ) {
        if ( ! isset( $_POST['tk_poi_nonce'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tk_poi_nonce'] ) ), 'tk_poi_save' ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = [
            '_tk_lat'              => 'sanitize_text_field',
            '_tk_lng'              => 'sanitize_text_field',
            '_tk_category'         => 'sanitize_text_field',
            '_tk_description'      => 'sanitize_textarea_field',
            '_tk_conditions_alert' => 'sanitize_text_field',
            '_tk_hero_position'    => 'sanitize_text_field',
            '_tk_gmaps_url'        => 'esc_url_raw',
            '_tk_gallery'          => 'tk_sanitize_gallery',
        ];

        foreach ( $fields as $key => $fn ) {
            if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via $fn callback above
                update_post_meta( $post_id, $key, $fn( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            }
        }
    }

    /* ── Getter ─────────────────────────────────── */
    public static function get( $post_id ) {
        return [
            'lat'              => get_post_meta( $post_id, '_tk_lat',              true ),
            'lng'              => get_post_meta( $post_id, '_tk_lng',              true ),
            'category'         => get_post_meta( $post_id, '_tk_category',         true ),
            'description'      => get_post_meta( $post_id, '_tk_description',      true ),
            'conditions_alert' => get_post_meta( $post_id, '_tk_conditions_alert', true ),
            'hero_position'    => get_post_meta( $post_id, '_tk_hero_position',    true ) ?: 'center center',
            'gmaps_url'        => get_post_meta( $post_id, '_tk_gmaps_url',        true ),
            'gallery'          => get_post_meta( $post_id, '_tk_gallery',          true ),
        ];
    }

    public static function lite_notice() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'tk_poi' ) return;
        if ( ! tk_at_limit( 'tk_poi' ) ) return;
        printf(
            '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
            esc_html__( 'TrailKit Lite limit reached.', 'trailkit' ),
            sprintf(
                /* translators: %1$d = POI limit, %2$s = upgrade link */
                esc_html__( 'You have reached the %1$d-POI limit. %2$s for unlimited POIs.', 'trailkit' ),
                intval( TK_LIMIT ),
                '<a href="' . esc_url( 'https://trailplugin.com' ) . '" target="_blank">' . esc_html__( 'Upgrade to Pro', 'trailkit' ) . '</a>'
            )
        );
    }
}
