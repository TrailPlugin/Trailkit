<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class TK_Shortcodes {

    public static function register() {
        add_shortcode( 'tk_routes', [ self::class, 'routes'  ] );
        add_shortcode( 'tk_pois',   [ self::class, 'pois'    ] );
        add_shortcode( 'tk_guides', [ self::class, 'guides'  ] );
        add_shortcode( 'tk_map',    [ self::class, 'map'     ] );
    }

    /* ── Editor meta box: [tk_map] shortcode generator ──
     * Registered by each CPT's *_Fields::register_meta_boxes().
     * $box['args']['toggles'] = [ attr => label ] for that post type.
     */
    public static function embed_builder_box( $post, $box ) {
        $toggles = isset( $box['args']['toggles'] ) ? $box['args']['toggles'] : [];
        $uid     = 'tk-embed-' . intval( $post->ID );
        ?>
        <p style="margin:0 0 8px;color:#6b7280;font-size:12px">
            <?php esc_html_e( 'Pick what to show, then copy the shortcode into any post or page.', 'trailkit' ) ?>
        </p>
        <div class="tk-embed-builder" id="<?php echo esc_attr( $uid ) ?>" data-id="<?php echo esc_attr( $post->ID ) ?>">
            <p style="margin:0 0 8px">
                <label style="font-size:12px;font-weight:600"><?php esc_html_e( 'Map zoom', 'trailkit' ) ?>
                <input type="number" class="tk-embed-zoom" value="13" min="1" max="19" style="width:60px;margin-left:6px"></label>
            </p>
            <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:10px">
                <?php foreach ( $toggles as $attr => $label ): ?>
                    <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                        <input type="checkbox" class="tk-embed-toggle" data-attr="<?php echo esc_attr( $attr ) ?>">
                        <?php echo esc_html( $label ) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <textarea class="tk-embed-output" readonly rows="2" style="width:100%;font-family:monospace;font-size:12px;border:1px solid #d1d5db;border-radius:4px;padding:6px;box-sizing:border-box;resize:vertical"></textarea>
            <button type="button" class="button button-primary tk-embed-copy" style="margin-top:6px;width:100%"><?php esc_html_e( 'Copy shortcode', 'trailkit' ) ?></button>
            <?php if ( $post->post_status !== 'publish' ): ?>
            <p style="margin:8px 0 0;color:#b45309;font-size:11px"><?php esc_html_e( 'Publish this item first — the shortcode only renders for published items.', 'trailkit' ) ?></p>
            <?php endif; ?>
        </div>
        <script>
        (function(){
            var w = document.getElementById(<?php echo wp_json_encode( $uid ) ?>);
            if ( ! w ) return;
            function build(){
                var id   = w.getAttribute('data-id');
                var zoom = w.querySelector('.tk-embed-zoom').value || '13';
                var parts = ['id="' + id + '"', 'zoom="' + zoom + '"'];
                w.querySelectorAll('.tk-embed-toggle').forEach(function(cb){
                    if ( cb.checked ) parts.push(cb.getAttribute('data-attr') + '="on"');
                });
                w.querySelector('.tk-embed-output').value = '[tk_map ' + parts.join(' ') + ']';
            }
            w.addEventListener('input', build);
            w.addEventListener('change', build);
            build();
            w.querySelector('.tk-embed-copy').addEventListener('click', function(){
                var out = w.querySelector('.tk-embed-output');
                out.select();
                if ( navigator.clipboard ) navigator.clipboard.writeText(out.value);
                else document.execCommand('copy');
                var b = w.querySelector('.tk-embed-copy'), t = b.textContent;
                b.textContent = '✓ Copied'; setTimeout(function(){ b.textContent = t; }, 1200);
            });
        })();
        </script>
        <?php
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
    // Collection map:  [tk_map type="routes" region="andes" height="450px" zoom="7" lat="10.48" lng="-66.90"]
    // Single item map: [tk_map id="123" zoom="13" mapsbtn="on" title="on" …]  (id = a poi / route / guide)
    public static function map( $atts ) {
        $a = shortcode_atts( [
            'type'   => 'routes',   // routes | pois | guides | all
            'region' => '',
            'height' => '450px',
            'zoom'   => '7',
            'lat'    => '',
            'lng'    => '',
            'id'     => '',         // when set → render a single item's map (poi / route / guide)
            // ── Info toggles ("on"/"true"/"1"/"yes" to show) ──
            'title'       => '',    // all
            'mapsbtn'     => '',    // all  — "Open in Google Maps" button
            'category'    => '',    // poi
            'coords'      => '',    // poi
            'description' => '',    // poi
            'gallery'     => '',    // poi / route
            'difficulty'  => '',    // route
            'distance'    => '',    // route
            'elevation'   => '',    // route
            'time'        => '',    // route
            'specialties' => '',    // guide
            'contact'     => '',    // guide
            'price'       => '',    // guide
            'radius'      => '',    // guide — draw service-area circle
        ], $atts, 'tk_map' );

        // Validate height to a safe CSS dimension — prevents CSS injection via the style attribute.
        $height = preg_match( '/^\d+(\.\d+)?(%|px|em|rem|vh|vw)$/', trim( $a['height'] ) )
            ? $a['height']
            : '450px';

        // ── Single-item mode (poi / route / guide) ──
        if ( $a['id'] ) {
            return self::single_map( intval( $a['id'] ), $height, $a, $atts );
        }

        $map_id     = 'tk-map-' . wp_unique_id();
        $center_lat = $a['lat'] ?: get_option( 'tk_default_lat', '8.0' );
        $center_lng = $a['lng'] ?: get_option( 'tk_default_lng', '-66.0' );

        $config = wp_json_encode( [
            'id'     => $map_id,
            'lat'    => floatval( $center_lat ),
            'lng'    => floatval( $center_lng ),
            'zoom'   => intval( $a['zoom'] ),
            'type'   => sanitize_key( $a['type'] ),
            'region' => sanitize_text_field( $a['region'] ),
        ] );

        $map = sprintf(
            '<div class="tk-map-wrap" style="height:%s"><div id="%s" class="tk-map" style="width:100%%;height:100%%"></div></div>',
            esc_attr( $height ),
            esc_attr( $map_id )
        );

        // Emit the init inline (deferred to DOMContentLoaded) rather than via
        // wp_add_inline_script — reliable inside block-theme content where the
        // enqueued script's "after" data does not always get printed.
        return $map . self::inline_script( "if(typeof tkInitMap==='function')tkInitMap({$config});" );
    }

    /* ── Single-item map (used by [tk_map id="…"]) — poi | route | guide ── */
    private static function single_map( $id, $height, $a, $atts ) {
        $post  = $id ? get_post( $id ) : null;
        $types = [ 'tk_poi', 'tk_route', 'tk_guide' ];
        if ( ! $post || ! in_array( $post->post_type, $types, true ) || $post->post_status !== 'publish' ) {
            return '<p class="tk-empty">' . esc_html__( 'Item not found.', 'trailkit' ) . '</p>';
        }

        $type = $post->post_type;
        $d    = $type === 'tk_route' ? TK_Route_Fields::get( $id )
              : ( $type === 'tk_guide' ? TK_Guide_Fields::get( $id ) : TK_POI_Fields::get( $id ) );

        if ( $d['lat'] === '' || $d['lng'] === '' ) {
            return '<p class="tk-empty">' . esc_html__( 'This item has no coordinates yet.', 'trailkit' ) . '</p>';
        }

        // Toggle helper — accepts on/true/1/yes.
        $on = static function ( $v ) {
            return in_array( strtolower( (string) $v ), [ 'on', 'true', '1', 'yes' ], true );
        };

        $lat    = floatval( $d['lat'] );
        $lng    = floatval( $d['lng'] );
        $title  = get_the_title( $id );
        $map_id = 'tk-map-' . wp_unique_id();
        $zoom   = isset( $atts['zoom'] ) ? intval( $atts['zoom'] ) : 13;

        ob_start();
        echo '<div class="tk-embed tk-embed--' . esc_attr( str_replace( 'tk_', '', $type ) ) . '">';

        if ( $on( $a['title'] ) ) {
            echo '<h3 class="tk-embed__title">' . esc_html( $title ) . '</h3>';
        }

        // Info blocks per type
        if ( $type === 'tk_poi' ) {
            self::embed_poi_info( $a, $d, $id, $on );
        } elseif ( $type === 'tk_route' ) {
            self::embed_route_info( $a, $d, $on );
        } else {
            self::embed_guide_info( $a, $d, $on );
        }

        // Map container
        printf(
            '<div class="tk-map-wrap" style="height:%s"><div id="%s" class="tk-map" style="width:100%%;height:100%%"></div></div>',
            esc_attr( $height ),
            esc_attr( $map_id )
        );

        // "Open in Google Maps" button
        if ( $on( $a['mapsbtn'] ) ) {
            $gmaps_url = ( $d['gmaps_url'] ?? '' ) ?: 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
            echo '<div class="tk-maps-nav-wrap"><a href="' . esc_url( $gmaps_url ) . '" target="_blank" rel="noopener" class="tk-maps-nav-btn tk-maps-nav-btn--gmaps">'
               . '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>'
               . esc_html__( 'Open in Google Maps', 'trailkit' ) . '</a></div>';
        }

        // Gallery (poi / route)
        if ( $on( $a['gallery'] ) && in_array( $type, [ 'tk_poi', 'tk_route' ], true ) ) {
            self::embed_gallery( $d, $title );
        }

        // Self-contained Leaflet init, emitted inline (deferred to DOMContentLoaded so
        // Leaflet — enqueued in the footer — is ready). Skipped during REST/AJAX so the
        // Gutenberg block-renderer response stays script-free.
        echo self::inline_script( self::map_init_js( $type, $map_id, $lat, $lng, $zoom, $title, $d, $on( $a['radius'] ) ) );

        echo '</div>';

        return ob_get_clean();
    }

    /* Wrap map init JS in a DOMContentLoaded listener; omit in REST/AJAX contexts. */
    private static function inline_script( $js ) {
        if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            return '';
        }
        return '<script>document.addEventListener("DOMContentLoaded",function(){' . $js . '});</script>';
    }

    private static function embed_poi_info( $a, $d, $id, $on ) {
        if ( $on( $a['category'] ) ) {
            $types = get_the_terms( $id, 'tk_poi_type' );
            $cat   = ( $types && ! is_wp_error( $types ) ) ? $types[0]->name : $d['category'];
            if ( $cat ) echo '<p class="tk-embed__meta"><span class="tk-tag">' . esc_html( $cat ) . '</span></p>';
        }
        if ( $on( $a['coords'] ) ) {
            echo '<p class="tk-embed__coords" style="font-family:monospace">'
               . esc_html( round( floatval( $d['lat'] ), 4 ) . ', ' . round( floatval( $d['lng'] ), 4 ) ) . '</p>';
        }
        if ( $on( $a['description'] ) && $d['description'] ) {
            echo '<p class="tk-embed__description">' . esc_html( $d['description'] ) . '</p>';
        }
    }

    private static function embed_route_info( $a, $d, $on ) {
        $stats = [];
        if ( $on( $a['difficulty'] ) && $d['difficulty'] )    $stats[] = [ __( 'Difficulty', 'trailkit' ),     ucfirst( $d['difficulty'] ) ];
        if ( $on( $a['distance'] )   && $d['distance']  !== '' ) $stats[] = [ __( 'Distance', 'trailkit' ),      $d['distance'] . ' km' ];
        if ( $on( $a['elevation'] )  && $d['elevation'] !== '' ) $stats[] = [ __( 'Elevation gain', 'trailkit' ), $d['elevation'] . ' m' ];
        if ( $on( $a['time'] )       && $d['time'] )          $stats[] = [ __( 'Est. time', 'trailkit' ),      $d['time'] ];
        if ( ! $stats ) return;
        echo '<div class="tk-embed__stats" style="display:flex;flex-wrap:wrap;gap:20px;margin:8px 0">';
        foreach ( $stats as $st ) {
            echo '<div class="tk-embed__stat"><strong>' . esc_html( $st[1] ) . '</strong>'
               . '<br><span style="font-size:.8em;color:var(--tk-text-muted,#6b7280)">' . esc_html( $st[0] ) . '</span></div>';
        }
        echo '</div>';
    }

    private static function embed_guide_info( $a, $d, $on ) {
        if ( $on( $a['specialties'] ) && $d['specialties'] ) {
            $specs = json_decode( $d['specialties'], true ) ?: [];
            if ( $specs ) {
                echo '<p class="tk-embed__meta">';
                foreach ( $specs as $slug ) {
                    $label = TK_Guide_Fields::$specialties[ $slug ] ?? $slug;
                    echo '<span class="tk-tag">' . esc_html( $label ) . '</span> ';
                }
                echo '</p>';
            }
        }
        if ( $on( $a['price'] ) && $d['price_from'] ) {
            /* translators: %s = price per day in USD */
            echo '<p class="tk-embed__price">' . esc_html( sprintf( __( 'From $%s / day', 'trailkit' ), $d['price_from'] ) ) . '</p>';
        }
        if ( $on( $a['contact'] ) ) {
            $c = [];
            if ( $d['whatsapp'] )  $c[] = '<a href="https://wa.me/' . esc_attr( preg_replace( '/[^0-9]/', '', $d['whatsapp'] ) ) . '" target="_blank" rel="noopener">WhatsApp</a>';
            if ( $d['email'] )     $c[] = '<a href="mailto:' . esc_attr( $d['email'] ) . '">' . esc_html__( 'Email', 'trailkit' ) . '</a>';
            if ( $d['instagram'] ) $c[] = '<a href="https://instagram.com/' . esc_attr( ltrim( $d['instagram'], '@' ) ) . '" target="_blank" rel="noopener">Instagram</a>';
            if ( $c ) echo '<p class="tk-embed__contact">' . implode( ' · ', $c ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- anchors built with escaped parts above
        }
    }

    private static function embed_gallery( $d, $title ) {
        $ids = $d['gallery'] ? json_decode( $d['gallery'], true ) : [];
        if ( ! is_array( $ids ) || ! count( $ids ) ) return;
        echo '<div class="tk-gallery" style="margin-top:16px">';
        foreach ( $ids as $img_id ) {
            $src  = wp_get_attachment_image_url( $img_id, 'medium_large' );
            $full = wp_get_attachment_image_url( $img_id, 'full' );
            if ( ! $src ) continue;
            printf(
                '<a href="%s" class="tk-gallery__item"><img src="%s" alt="%s" loading="lazy"></a>',
                esc_url( $full ),
                esc_url( $src ),
                /* translators: %s = item title */
                esc_attr( sprintf( esc_html__( '%s — gallery image', 'trailkit' ), $title ) )
            );
        }
        echo '</div>';
    }

    private static function map_init_js( $type, $map_id, $lat, $lng, $zoom, $title, $d, $show_radius ) {
        $id_js    = wp_json_encode( $map_id );
        $title_js = wp_json_encode( wp_strip_all_tags( $title ) );
        $tile     = 'L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"© OpenStreetMap",maxZoom:19}).addTo(m);';

        // Route with a GPS track → polyline fitted to bounds.
        if ( $type === 'tk_route' ) {
            $coords = [];
            $pts    = ! empty( $d['points'] ) ? json_decode( $d['points'], true ) : null;
            if ( is_array( $pts ) ) {
                foreach ( $pts as $p ) {
                    if ( isset( $p['lat'], $p['lng'] ) ) {
                        $coords[] = [ round( floatval( $p['lat'] ), 6 ), round( floatval( $p['lng'] ), 6 ) ];
                    }
                }
            }
            if ( count( $coords ) >= 2 ) {
                return sprintf(
                    '(function(){var el=document.getElementById(%1$s);if(!el||typeof L==="undefined")return;'
                  . 'var m=L.map(%1$s);%2$s'
                  . 'var line=L.polyline(%3$s,{color:"#0df246",weight:4}).addTo(m);'
                  . 'm.fitBounds(line.getBounds(),{padding:[20,20]});'
                  . 'L.marker(%3$s[0]).addTo(m).bindPopup(%4$s);})();',
                    $id_js, $tile, wp_json_encode( $coords ), $title_js
                );
            }
        }

        // Guide service area → marker + radius circle fitted to bounds.
        if ( $type === 'tk_guide' && $show_radius ) {
            $radius_m = intval( $d['radius_km'] ?: 50 ) * 1000;
            return sprintf(
                '(function(){var el=document.getElementById(%1$s);if(!el||typeof L==="undefined")return;'
              . 'var m=L.map(%1$s);%2$s'
              . 'var c=L.circle([%3$F,%4$F],{radius:%5$d,color:"#0df246",fillColor:"#0df246",fillOpacity:0.12}).addTo(m);'
              . 'm.fitBounds(c.getBounds(),{padding:[20,20]});'
              . 'L.marker([%3$F,%4$F]).addTo(m).bindPopup(%6$s).openPopup();})();',
                $id_js, $tile, $lat, $lng, $radius_m, $title_js
            );
        }

        // Default: single marker.
        return sprintf(
            '(function(){var el=document.getElementById(%1$s);if(!el||typeof L==="undefined")return;'
          . 'var m=L.map(%1$s).setView([%2$F,%3$F],%4$d);%5$s'
          . 'L.marker([%2$F,%3$F]).addTo(m).bindPopup(%6$s).openPopup();})();',
            $id_js, $lat, $lng, $zoom, $tile, $title_js
        );
    }
}
