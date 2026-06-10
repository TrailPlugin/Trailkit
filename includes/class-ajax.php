<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Ajax {

    public static function init() {
        add_action( 'wp_ajax_tk_get_map_markers',        [ self::class, 'get_map_markers' ] );
        add_action( 'wp_ajax_nopriv_tk_get_map_markers', [ self::class, 'get_map_markers' ] );
    }

    public static function get_map_markers() {
        check_ajax_referer( 'tk_nonce', 'nonce' );
        $type   = sanitize_key( $_POST['type']   ?? 'routes' );
        $region = sanitize_text_field( wp_unslash( $_POST['region'] ?? '' ) );
        wp_send_json_success( TK_Map::get_markers( $type, $region ) );
    }
}

TK_Ajax::init();
