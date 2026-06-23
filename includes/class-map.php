<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Map {

    /* Called via AJAX to supply map marker data */
    public static function get_markers( $type = 'routes', $region = '' ) {
        $markers = [];

        if ( in_array( $type, [ 'routes', 'all' ] ) ) {
            $args = [ 'post_type' => 'tk_route', 'posts_per_page' => 200, 'post_status' => 'publish' ];
            if ( $region ) $args['tax_query'] = [ [ 'taxonomy' => 'tk_region', 'field' => 'slug', 'terms' => $region ] ];
            $q = new WP_Query( $args );
            foreach ( $q->posts as $p ) {
                $lat = get_post_meta( $p->ID, '_tk_lat', true );
                $lng = get_post_meta( $p->ID, '_tk_lng', true );
                if ( ! $lat || ! $lng ) continue;
                $markers[] = [
                    'type'       => 'route',
                    'id'         => $p->ID,
                    'title'      => $p->post_title,
                    'url'        => get_permalink( $p->ID ),
                    'lat'        => floatval( $lat ),
                    'lng'        => floatval( $lng ),
                    'difficulty' => get_post_meta( $p->ID, '_tk_difficulty', true ),
                    'distance'   => get_post_meta( $p->ID, '_tk_distance',   true ),
                    'thumb'      => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) ?: '',
                ];
            }
            wp_reset_postdata();
        }

        if ( in_array( $type, [ 'pois', 'all' ] ) ) {
            $args = [ 'post_type' => 'tk_poi', 'posts_per_page' => 200, 'post_status' => 'publish' ];
            if ( $region ) $args['tax_query'] = [ [ 'taxonomy' => 'tk_region', 'field' => 'slug', 'terms' => $region ] ];
            $q = new WP_Query( $args );
            foreach ( $q->posts as $p ) {
                $lat = get_post_meta( $p->ID, '_tk_lat', true );
                $lng = get_post_meta( $p->ID, '_tk_lng', true );
                if ( ! $lat || ! $lng ) continue;
                $types = get_the_terms( $p->ID, 'tk_poi_type' );
                $markers[] = [
                    'type'  => 'poi',
                    'id'    => $p->ID,
                    'title' => $p->post_title,
                    'url'   => get_permalink( $p->ID ),
                    'lat'   => floatval( $lat ),
                    'lng'   => floatval( $lng ),
                    'poi_type' => ( $types && ! is_wp_error($types) ) ? $types[0]->name : '',
                    'thumb' => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) ?: '',
                ];
            }
            wp_reset_postdata();
        }

        if ( in_array( $type, [ 'guides', 'all' ] ) ) {
            $args = [ 'post_type' => 'tk_guide', 'posts_per_page' => 200, 'post_status' => 'publish' ];
            $q = new WP_Query( $args );
            foreach ( $q->posts as $p ) {
                $lat = get_post_meta( $p->ID, '_tk_lat', true );
                $lng = get_post_meta( $p->ID, '_tk_lng', true );
                if ( ! $lat || ! $lng ) continue;
                $specs_raw   = get_post_meta( $p->ID, '_tk_specialties', true );
                $specialties = $specs_raw ? json_decode( wp_unslash( $specs_raw ), true ) : [];
                $markers[] = [
                    'type'        => 'guide',
                    'id'          => $p->ID,
                    'title'       => $p->post_title,
                    'url'         => get_permalink( $p->ID ),
                    'lat'         => floatval( $lat ),
                    'lng'         => floatval( $lng ),
                    'radius_km'   => intval( get_post_meta( $p->ID, '_tk_radius_km',   true ) ?: 50 ),
                    'price_from'  => floatval( get_post_meta( $p->ID, '_tk_price_from', true ) ),
                    'specialties' => is_array( $specialties ) ? $specialties : [],
                    'thumb'       => get_the_post_thumbnail_url( $p->ID, 'thumbnail' ) ?: '',
                ];
            }
            wp_reset_postdata();
        }

        return $markers;
    }
}
