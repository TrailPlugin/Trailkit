<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Guide_Fields {

    public static $specialties = [
        'hiking'        => 'Hiking',
        'climbing'      => 'Climbing',
        'mountaineering'=> 'Mountaineering',
        'cycling'       => 'Cycling',
        'mountain-biking'=> 'Mountain Biking',
        'kayaking'      => 'Kayaking',
        'diving'        => 'Diving',
        'rappelling'    => 'Rappelling',
        'camping'       => 'Camping',
        'photography'   => 'Photography',
        'gastronomy'    => 'Gastronomy',
        '4x4'           => '4x4 Off-road',
        'motorcycling'  => 'Motorcycling',
    ];

    public static function init() {
        add_action( 'add_meta_boxes', [ self::class, 'register_meta_boxes' ] );
        add_action( 'save_post_tk_guide', [ self::class, 'save' ], 10, 2 );
        if ( TK_LITE ) add_action( 'admin_notices', [ self::class, 'lite_notice' ] );
    }

    /* ── Native meta boxes ──────────────────────── */
    public static function register_meta_boxes() {
        add_meta_box( 'tk-guide-contact',     __( 'Contact Info',   'trailkit' ), [ self::class, 'box_contact'     ], 'tk_guide', 'normal', 'high' );
        add_meta_box( 'tk-guide-specialties', __( 'Specialties',    'trailkit' ), [ self::class, 'box_specialties' ], 'tk_guide', 'normal' );
        add_meta_box( 'tk-guide-location',    __( 'Service Area',   'trailkit' ), [ self::class, 'box_location'    ], 'tk_guide', 'normal' );
        add_meta_box( 'tk-guide-photo',       __( 'Profile Photo',  'trailkit' ), [ self::class, 'box_photo'       ], 'tk_guide', 'side' );
    }

    public static function box_contact( $post ) {
        wp_nonce_field( 'tk_guide_save', 'tk_guide_nonce' );
        $d = self::get( $post->ID );
        ?>
        <table class="tk-meta-table">
            <tr>
                <td><label><?php esc_html_e('WhatsApp','trailkit') ?></label>
                <input type="text" name="_tk_whatsapp" value="<?php echo esc_attr($d['whatsapp']) ?>" placeholder="+1 555 000 0000"></td>
                <td><label><?php esc_html_e('Email','trailkit') ?></label>
                <input type="email" name="_tk_email" value="<?php echo esc_attr($d['email']) ?>"></td>
            </tr>
            <tr>
                <td><label><?php esc_html_e('Instagram handle','trailkit') ?></label>
                <input type="text" name="_tk_instagram" value="<?php echo esc_attr($d['instagram']) ?>" placeholder="handle (no @)"></td>
                <td><label><?php esc_html_e('Price from (USD/day)','trailkit') ?></label>
                <input type="number" name="_tk_price_from" value="<?php echo esc_attr($d['price_from']) ?>" min="0" step="1"></td>
            </tr>
            <tr>
                <td colspan="2">
                    <label><input type="checkbox" name="_tk_is_featured" value="1" <?php checked($d['is_featured'], '1') ?>>
                    <?php esc_html_e('Featured guide (shown first in directory)','trailkit') ?></label>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function box_specialties( $post ) {
        $saved = self::get( $post->ID )['specialties'];
        $selected = $saved ? json_decode( $saved, true ) : [];
        echo '<div style="display:flex;flex-wrap:wrap;gap:8px;padding:4px 0">';
        foreach ( self::$specialties as $slug => $label ) {
            echo '<label style="display:flex;align-items:center;gap:4px;padding:4px 10px;border:1px solid #ddd;border-radius:20px;cursor:pointer;font-size:13px">'
               . '<input type="checkbox" name="_tk_specialties[]" value="' . esc_attr($slug) . '" ' . checked( in_array( $slug, (array) $selected ), true, false ) . '>'
               . esc_html($label) . '</label>';
        }
        echo '</div>';
    }

    public static function box_location( $post ) {
        $d = self::get( $post->ID );
        ?>
        <p class="description"><?php esc_html_e('Center of your service area. Used for the guides map directory.','trailkit') ?></p>
        <table class="tk-meta-table">
            <tr>
                <td><label><?php esc_html_e('Latitude','trailkit') ?></label>
                <input type="text" name="_tk_lat" value="<?php echo esc_attr($d['lat']) ?>"></td>
                <td><label><?php esc_html_e('Longitude','trailkit') ?></label>
                <input type="text" name="_tk_lng" value="<?php echo esc_attr($d['lng']) ?>"></td>
                <td><label><?php esc_html_e('Radius (km)','trailkit') ?></label>
                <input type="number" name="_tk_radius_km" value="<?php echo esc_attr($d['radius_km']) ?>" min="1" placeholder="50"></td>
            </tr>
        </table>
        <?php
    }

    public static function box_photo( $post ) {
        $photo_id = get_post_meta( $post->ID, '_tk_photo_id', true );
        $src      = $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : '';
        ?>
        <div id="tk-guide-photo-wrap">
            <?php if ( $src ): ?>
                <img id="tk-guide-photo-preview" src="<?php echo esc_url($src) ?>" style="width:100%;border-radius:6px;margin-bottom:8px">
            <?php else: ?>
                <div id="tk-guide-photo-preview" style="width:100%;height:120px;background:#f0f0f0;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;color:#999">No photo</div>
            <?php endif; ?>
        </div>
        <input type="hidden" name="_tk_photo_id" id="tk_photo_id_field" value="<?php echo esc_attr($photo_id) ?>">
        <button type="button" class="button tk-photo-btn" data-target="tk_photo_id_field" data-preview="tk-guide-photo-preview"><?php esc_html_e('Select Photo','trailkit') ?></button>
        <?php if ( $photo_id ): ?>
        <button type="button" class="button tk-photo-remove" data-target="tk_photo_id_field" data-preview="tk-guide-photo-preview" style="margin-left:4px"><?php esc_html_e('Remove','trailkit') ?></button>
        <?php endif; ?>
        <?php
    }

    /* ── Save ───────────────────────────────────── */
    public static function save( $post_id ) {
        if ( ! isset( $_POST['tk_guide_nonce'] ) ) return;
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tk_guide_nonce'] ) ), 'tk_guide_save' ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $text_fields = [ '_tk_whatsapp', '_tk_instagram', '_tk_lat', '_tk_lng' ];
        foreach ( $text_fields as $k ) {
            update_post_meta( $post_id, $k, sanitize_text_field( wp_unslash( $_POST[$k] ?? '' ) ) );
        }
        update_post_meta( $post_id, '_tk_email',      sanitize_email(    wp_unslash( $_POST['_tk_email']      ?? '' ) ) );
        update_post_meta( $post_id, '_tk_price_from', floatval(          $_POST['_tk_price_from'] ?? 0  ) );
        update_post_meta( $post_id, '_tk_radius_km',  intval(            $_POST['_tk_radius_km']  ?? 50 ) );
        update_post_meta( $post_id, '_tk_is_featured', isset($_POST['_tk_is_featured']) ? '1' : '0' );
        update_post_meta( $post_id, '_tk_photo_id',   intval(            $_POST['_tk_photo_id']   ?? 0  ) );

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- unslash+sanitize via nested array_map below
        $specs = isset( $_POST['_tk_specialties'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['_tk_specialties'] ) ) : [];
        update_post_meta( $post_id, '_tk_specialties', wp_slash( json_encode( $specs ) ) );
    }

    /* ── Getter ─────────────────────────────────── */
    public static function get( $post_id ) {
        return [
            'whatsapp'   => get_post_meta( $post_id, '_tk_whatsapp',   true ),
            'email'      => get_post_meta( $post_id, '_tk_email',      true ),
            'instagram'  => get_post_meta( $post_id, '_tk_instagram',  true ),
            'price_from' => get_post_meta( $post_id, '_tk_price_from', true ),
            'specialties'=> get_post_meta( $post_id, '_tk_specialties',true ),
            'photo_id'   => get_post_meta( $post_id, '_tk_photo_id',   true ),
            'is_featured'=> get_post_meta( $post_id, '_tk_is_featured',true ),
            'lat'        => get_post_meta( $post_id, '_tk_lat',        true ),
            'lng'        => get_post_meta( $post_id, '_tk_lng',        true ),
            'radius_km'  => get_post_meta( $post_id, '_tk_radius_km',  true ) ?: 50,
        ];
    }

    public static function lite_notice() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== 'tk_guide' ) return;
        if ( ! tk_at_limit( 'tk_guide' ) ) return;
        printf(
            '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong> %s</p></div>',
            esc_html__( 'TrailKit Lite limit reached.', 'trailkit' ),
            sprintf(
                /* translators: %s = upgrade link */
                esc_html__( 'You have reached the 1-guide limit. %s for unlimited guides.', 'trailkit' ),
                '<a href="' . esc_url( 'https://trailplugin.com' ) . '" target="_blank">' . esc_html__( 'Upgrade to Pro', 'trailkit' ) . '</a>'
            )
        );
    }
}
