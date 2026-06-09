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
        add_meta_box( 'tk-poi-location', __( 'Location & Details', 'trailkit' ), [ self::class, 'box_location' ], 'tk_poi', 'normal', 'high' );
        add_meta_box( 'tk-poi-media',    __( 'Gallery & Links',    'trailkit' ), [ self::class, 'box_media'    ], 'tk_poi', 'side' );
    }

    public static function box_location( $post ) {
        wp_nonce_field( 'tk_poi_save', 'tk_poi_nonce' );
        $d = self::get( $post->ID );
        ?>
        <table class="tk-meta-table">
            <tr>
                <td><label><?php _e('Latitude','trailkit') ?></label>
                <input type="text" name="_tk_lat" value="<?php echo esc_attr($d['lat']) ?>" placeholder="10.4806"></td>
                <td><label><?php _e('Longitude','trailkit') ?></label>
                <input type="text" name="_tk_lng" value="<?php echo esc_attr($d['lng']) ?>" placeholder="-66.9036"></td>
            </tr>
            <tr>
                <td colspan="2"><label><?php _e('Conditions alert (optional)','trailkit') ?></label>
                <input type="text" name="_tk_conditions_alert" value="<?php echo esc_attr($d['conditions_alert']) ?>" style="width:100%"></td>
            </tr>
            <tr>
                <td><label><?php _e('Hero image position','trailkit') ?></label>
                <input type="text" name="_tk_hero_position" value="<?php echo esc_attr($d['hero_position']) ?>" placeholder="center center"></td>
                <td><label><?php _e('Google Maps link','trailkit') ?></label>
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
            <?php printf( __( '&#9888; Lite: max %d images. <a href="https://trailplugin.com" target="_blank">Upgrade to Pro</a> for unlimited.', 'trailkit' ), TK_GALLERY_LIMIT ) ?>
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
        <button type="button" class="button tk-gallery-btn" data-target="tk_poi_gallery_ids" data-preview="tk-poi-gallery-preview"><?php _e('Select Images','trailkit') ?></button>
        <?php
    }

    /* ── Save ───────────────────────────────────── */
    public static function save( $post_id ) {
        if ( ! isset( $_POST['tk_poi_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['tk_poi_nonce'], 'tk_poi_save' ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $fields = [
            '_tk_lat'              => 'sanitize_text_field',
            '_tk_lng'              => 'sanitize_text_field',
            '_tk_conditions_alert' => 'sanitize_text_field',
            '_tk_hero_position'    => 'sanitize_text_field',
            '_tk_gmaps_url'        => 'esc_url_raw',
            '_tk_gallery'          => 'tk_sanitize_gallery',
        ];

        foreach ( $fields as $key => $fn ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, $key, $fn( $_POST[ $key ] ) );
            }
        }
    }

    /* ── Getter ─────────────────────────────────── */
    public static function get( $post_id ) {
        return [
            'lat'              => get_post_meta( $post_id, '_tk_lat',              true ),
            'lng'              => get_post_meta( $post_id, '_tk_lng',              true ),
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
        echo '<div class="notice notice-warning"><p>'
           . sprintf( __( '<strong>TrailKit Lite:</strong> You have reached the %d POI limit. <a href="#">Upgrade to Pro</a> for unlimited POIs.', 'trailkit' ), TK_LIMIT )
           . '</p></div>';
    }
}
