<?php
/**
 * Single Route Template
 * Override: copy to {theme}/trailkit/single-route.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
$data    = TK_Route_Fields::get( $post_id );
$thumb   = get_the_post_thumbnail_url( $post_id, 'full' );

// Navigation URLs — Pro only, auto-generated from GPS track or coordinates
$nav_mobile_url  = '';
$nav_desktop_url = '';
if ( ! TK_LITE ) {
    if ( $data['gmaps_url'] ) {
        // Manual override takes precedence for both desktop and mobile
        $nav_mobile_url = $nav_desktop_url = $data['gmaps_url'];
    } elseif ( $data['points'] ) {
        $all_pts = json_decode( $data['points'], true );
        if ( $all_pts && is_array( $all_pts ) && count( $all_pts ) >= 2 ) {
            $pt_start  = $all_pts[0];
            $pt_end    = $all_pts[ count( $all_pts ) - 1 ];
            $start_str = floatval( $pt_start['lat'] ) . ',' . floatval( $pt_start['lng'] );
            $end_str   = floatval( $pt_end['lat'] ) . ',' . floatval( $pt_end['lng'] );
            // Mobile: daddr chain — no saddr means navigation starts from current device location
            $nav_mobile_url  = 'https://maps.google.com/maps?daddr=' . $start_str . '+to:' . $end_str . '&directionsmode=driving';
            // Desktop: explicit origin and destination
            $nav_desktop_url = 'https://www.google.com/maps/dir/?api=1&origin=' . $start_str . '&destination=' . $end_str . '&travelmode=driving';
        }
    } elseif ( $data['lat'] && $data['lng'] ) {
        $ll_str          = floatval( $data['lat'] ) . ',' . floatval( $data['lng'] );
        $nav_mobile_url  = 'https://www.google.com/maps/search/?api=1&query=' . $ll_str;
        $nav_desktop_url = $nav_mobile_url;
    }
}

// If lat/lng not set manually, derive from the first GPS track point
if ( ( ! $data['lat'] || ! $data['lng'] ) && $data['points'] ) {
    $pts = json_decode( $data['points'], true );
    if ( $pts && is_array( $pts ) && isset( $pts[0]['lat'], $pts[0]['lng'] ) ) {
        $data['lat'] = $data['lat'] ?: (string) $pts[0]['lat'];
        $data['lng'] = $data['lng'] ?: (string) $pts[0]['lng'];
    }
}

$diff_labels = [ 'easy' => 'Easy', 'moderate' => 'Moderate', 'hard' => 'Hard', 'extreme' => 'Extreme' ];
$diff        = $data['difficulty'];
$diff_label  = $diff_labels[ $diff ] ?? ucfirst( $diff );

$activities = get_the_terms( $post_id, 'tk_activity' );
$regions    = get_the_terms( $post_id, 'tk_region' );

get_header();
?>
<div class="tk-single tk-single--route">

    <?php /* ── Hero ── */ ?>
    <div class="tk-single__hero" style="<?php echo $thumb ? 'background-image:url(' . esc_url($thumb) . ');background-position:' . esc_attr($data['hero_position']) : 'background-color:#102214' ?>">
        <div class="tk-single__hero-overlay"></div>
        <div class="tk-single__hero-content">
            <div class="tk-single__breadcrumb">
                <a href="<?php echo esc_url( get_post_type_archive_link('tk_route') ) ?>"><?php esc_html_e('Routes','trailkit') ?></a>
                <span>›</span>
                <span><?php the_title() ?></span>
            </div>
            <?php if ( $activities && ! is_wp_error($activities) ): ?>
            <div class="tk-single__tags">
                <?php foreach ( $activities as $t ): ?>
                    <span class="tk-tag"><?php echo esc_html($t->name) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <h1 class="tk-single__title"><?php echo esc_html( get_the_title() ) ?></h1>
            <?php if ( $regions && ! is_wp_error($regions) ): ?>
            <p class="tk-single__region">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><path d="M8 2C5.8 2 4 3.8 4 6c0 3 4 8 4 8s4-5 4-8c0-2.2-1.8-4-4-4zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg>
                <?php echo esc_html( implode(', ', wp_list_pluck($regions,'name')) ) ?>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <?php /* ── Stats bar ── */ ?>
    <div class="tk-single__stats-wrap">
        <div class="tk-single__stats">
            <div class="tk-single__stat">
                <span class="tk-single__stat-icon tk-single__stat-icon--<?php echo esc_attr($diff) ?>">
                    <?php echo esc_html($diff_label) ?>
                </span>
                <span class="tk-single__stat-label"><?php esc_html_e('Difficulty','trailkit') ?></span>
            </div>
            <?php /* ── Weather widget — Pro only, toggle per route ── */ ?>
            <?php if ( ! TK_LITE && $data['weather_enabled'] && $data['lat'] && $data['lng'] ): ?>
            <div class="tk-single__stat tk-weather-stat"
                 data-lat="<?php echo esc_attr( $data['lat'] ) ?>"
                 data-lon="<?php echo esc_attr( $data['lng'] ) ?>">
                <span class="tk-single__stat-value" id="tk-weather-display">
                    <span class="tk-weather-loading"></span>
                </span>
                <span class="tk-single__stat-label"><?php esc_html_e('Weather','trailkit') ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $data['distance'] ): ?>
            <div class="tk-single__stat">
                <span class="tk-single__stat-value"><?php echo esc_html($data['distance']) ?> km</span>
                <span class="tk-single__stat-label"><?php esc_html_e('Distance','trailkit') ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $data['elevation'] ): ?>
            <div class="tk-single__stat">
                <span class="tk-single__stat-value">↑ <?php echo esc_html($data['elevation']) ?> m</span>
                <span class="tk-single__stat-label"><?php esc_html_e('Elevation gain','trailkit') ?></span>
            </div>
            <?php endif; ?>
            <?php if ( $data['time'] ): ?>
            <div class="tk-single__stat">
                <span class="tk-single__stat-value"><?php echo esc_html($data['time']) ?></span>
                <span class="tk-single__stat-label"><?php esc_html_e('Duration','trailkit') ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="tk-single__body">

        <?php /* ── Alert ── */ ?>
        <?php if ( $data['conditions_alert'] ): ?>
        <div class="tk-alert">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"/></svg>
            <?php echo esc_html($data['conditions_alert']) ?>
        </div>
        <?php endif; ?>

        <?php /* ── Description ── */ ?>
        <div class="tk-single__content">
            <?php the_content() ?>
        </div>

        <?php /* ── Map ── */ ?>
        <?php if ( $data['lat'] && $data['lng'] ): ?>
        <?php
        $points = $data['points'] ? json_decode( $data['points'], true ) : null;
        $has_track = ! TK_LITE && $points && is_array( $points ) && count( $points ) > 1;
        ?>
        <div class="tk-single__section">
            <h2 class="tk-single__section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.387 1.436-.957 2.255-1.716C15.046 15.23 17 12.558 17 9A7 7 0 103 9c0 3.558 1.954 6.23 3.373 7.633.819.76 1.635 1.329 2.255 1.716a13.4 13.4 0 001.061.571l.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>
                <?php esc_html_e('Map & Location','trailkit') ?>
            </h2>
            <div id="tk-single-map" class="tk-map" style="height:400px;border-radius:12px;overflow:hidden;border:1px solid var(--tk-border)"></div>
            <?php if ( ! TK_LITE && $nav_desktop_url ): ?>
            <div class="tk-maps-nav-wrap">
                <a href="<?php echo esc_url( $nav_desktop_url ) ?>" target="_blank" rel="noopener" class="tk-maps-nav-btn tk-maps-nav-btn--gmaps">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    <?php esc_html_e( 'Open in Google Maps', 'trailkit' ) ?>
                </a>
            </div>
            <?php elseif ( TK_LITE && $data['gmaps_url'] ): ?>
            <a href="<?php echo esc_url( $data['gmaps_url'] ) ?>" target="_blank" rel="noopener" class="tk-btn tk-btn--outline" style="margin-top:12px;display:inline-flex">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.387 1.436-.957 2.255-1.716C15.046 15.23 17 12.558 17 9A7 7 0 103 9c0 3.558 1.954 6.23 3.373 7.633.819.76 1.635 1.329 2.255 1.716a13.4 13.4 0 001.061.571l.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>
                <?php esc_html_e( 'Open in Google Maps', 'trailkit' ) ?>
            </a>
            <?php endif; ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof L === 'undefined') return;
                var map = L.map('tk-single-map', { zoomControl: true });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>', maxZoom: 19 }).addTo(map);
                <?php if ( $has_track ): ?>
                var latlngs = <?php
                    echo json_encode( array_map( function($p) {
                        return [ floatval($p['lat']), floatval($p['lng']) ];
                    }, $points ) );
                ?>;
                var poly = L.polyline(latlngs, { color:'#0df246', weight:4, opacity:0.85 }).addTo(map);
                map.fitBounds(poly.getBounds(), { padding:[40,40] });
                L.marker(latlngs[0]).addTo(map).bindPopup('<?php echo esc_js(__('Start','trailkit')) ?>').openPopup();
                L.marker(latlngs[latlngs.length - 1]).addTo(map).bindPopup('<?php echo esc_js(__('End','trailkit')) ?>');
                <?php else: ?>
                map.setView([<?php echo floatval($data['lat']) ?>, <?php echo floatval($data['lng']) ?>], 13);
                L.marker([<?php echo floatval($data['lat']) ?>, <?php echo floatval($data['lng']) ?>]).addTo(map).bindPopup('<?php echo esc_js(get_the_title()) ?>').openPopup();
                <?php endif; ?>
            });
            </script>
        </div>
        <?php endif; ?>

        <?php /* ── Elevation chart — Pro only ── */ ?>
        <?php
        $ele_x = []; // cumulative km
        $ele_y = []; // elevation in metres
        if ( ! TK_LITE && $data['points'] ) {
            $all_pts = json_decode( $data['points'], true );
            if ( $all_pts && is_array( $all_pts ) ) {
                $R      = 6371.0;
                $cum_km = 0.0;
                $prev   = null;
                foreach ( $all_pts as $p ) {
                    if ( ! isset( $p['lat'], $p['lng'] ) ) continue;
                    if ( $prev ) {
                        $dLat   = deg2rad( $p['lat'] - $prev['lat'] );
                        $dLng   = deg2rad( $p['lng'] - $prev['lng'] );
                        $a      = sin( $dLat / 2 ) ** 2
                                + cos( deg2rad( $prev['lat'] ) ) * cos( deg2rad( $p['lat'] ) )
                                * sin( $dLng / 2 ) ** 2;
                        $cum_km += $R * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
                    }
                    if ( isset( $p['ele'] ) && (int) $p['ele'] > 0 ) {
                        $ele_x[] = round( $cum_km, 3 );
                        $ele_y[] = (int) $p['ele'];
                    }
                    $prev = $p;
                }
            }
        }
        // Elevation gain — sum of positive differences
        $ele_gain = 0;
        for ( $i = 1; $i < count( $ele_y ); $i++ ) {
            $diff = $ele_y[ $i ] - $ele_y[ $i - 1 ];
            if ( $diff > 0 ) $ele_gain += $diff;
        }
        // Downsample to 200 pts max so the canvas renders correctly on long routes
        $max_chart = 200;
        $n_pts = count( $ele_x );
        if ( $n_pts > $max_chart ) {
            $sx = []; $sy = [];
            for ( $i = 0; $i < $max_chart; $i++ ) {
                $idx  = (int) round( $i * ( $n_pts - 1 ) / ( $max_chart - 1 ) );
                $sx[] = $ele_x[ $idx ];
                $sy[] = $ele_y[ $idx ];
            }
            $ele_x = $sx;
            $ele_y = $sy;
        }
        if ( count( $ele_x ) > 1 ):
        ?>
        <div class="tk-single__section">
            <h2 class="tk-single__section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M2 14l4.5-7 3.5 4 3-3.5L17 14H2z"/></svg>
                <?php esc_html_e( 'Elevation Profile', 'trailkit' ) ?>
                <span class="tk-ele-gain">+<?php echo esc_html( round( $ele_gain ) ) ?> m</span>
            </h2>
            <div id="tk-ele-chart-wrap" class="tk-ele-chart-wrap">
                <div id="tk-ele-chart"></div>
            </div>
            <script>
            (function () {
                var xArr = <?php echo wp_json_encode( $ele_x ) ?>;
                var yArr = <?php echo wp_json_encode( $ele_y ) ?>;
                var wrap = document.getElementById('tk-ele-chart');
                if (!wrap || xArr.length < 2) return;

                var W   = wrap.offsetWidth || 700;
                var H   = 160;
                var PAD = 44;

                var canvas = document.createElement('canvas');
                canvas.width  = W;
                canvas.height = H;
                canvas.style.width  = '100%';
                canvas.style.height = H + 'px';
                wrap.appendChild(canvas);
                var ctx = canvas.getContext('2d');

                var minX = xArr[0], maxX = xArr[xArr.length - 1];
                var minY = 0, maxY = yArr.reduce(function(a,b){return a>b?a:b;});
                var rangeY = maxY || 1;

                function pt(x, y) {
                    return {
                        x: PAD + (x - minX) / (maxX - minX) * (W - PAD * 2),
                        y: H - PAD - (y - minY) / rangeY * (H - PAD * 1.4)
                    };
                }

                var gridColor  = 'rgba(255,255,255,.08)';
                var labelColor = 'rgba(255,255,255,.4)';

                function drawGrid() {
                    ctx.font = '10px sans-serif';
                    [0, 0.25, 0.5, 0.75, 1].forEach(function (t) {
                        var val = minY + t * rangeY;
                        var p   = pt(minX, val);
                        ctx.strokeStyle = gridColor; ctx.lineWidth = 1;
                        ctx.beginPath(); ctx.moveTo(PAD, p.y); ctx.lineTo(W - PAD, p.y); ctx.stroke();
                        ctx.fillStyle = labelColor; ctx.textAlign = 'right';
                        ctx.fillText(Math.round(val) + 'm', PAD - 4, p.y + 3);
                    });
                    ctx.fillStyle = labelColor; ctx.textAlign = 'center';
                    [0, 0.25, 0.5, 0.75, 1].forEach(function (t) {
                        var x = minX + t * (maxX - minX);
                        var p = pt(x, minY);
                        ctx.fillText(x.toFixed(1) + ' km', p.x, H - 6);
                    });
                }

                var pts = xArr.map(function (x, i) { return pt(x, yArr[i]); });

                function drawFrame(fraction) {
                    ctx.clearRect(0, 0, W, H);
                    drawGrid();
                    var count = Math.max(2, Math.round(pts.length * fraction));
                    var sl    = pts.slice(0, count);
                    var p0    = sl[0];
                    var pL    = sl[sl.length - 1];
                    var i, mx, my;

                    // Fill — continuous path, no moveTo breakpoints
                    ctx.beginPath();
                    ctx.moveTo(p0.x, H - PAD);
                    ctx.lineTo(p0.x, p0.y);
                    for (i = 0; i < sl.length - 1; i++) {
                        mx = (sl[i].x + sl[i + 1].x) / 2;
                        my = (sl[i].y + sl[i + 1].y) / 2;
                        ctx.quadraticCurveTo(sl[i].x, sl[i].y, mx, my);
                    }
                    ctx.lineTo(pL.x, pL.y);
                    ctx.lineTo(pL.x, H - PAD);
                    ctx.closePath();
                    ctx.fillStyle = 'rgba(13,242,70,0.12)';
                    ctx.fill();

                    // Line
                    ctx.beginPath();
                    ctx.moveTo(p0.x, p0.y);
                    for (i = 0; i < sl.length - 1; i++) {
                        mx = (sl[i].x + sl[i + 1].x) / 2;
                        my = (sl[i].y + sl[i + 1].y) / 2;
                        ctx.quadraticCurveTo(sl[i].x, sl[i].y, mx, my);
                    }
                    ctx.lineTo(pL.x, pL.y);
                    ctx.strokeStyle = '#0df246'; ctx.lineWidth = 2; ctx.stroke();

                    // Tip dot
                    ctx.beginPath();
                    ctx.arc(pL.x, pL.y, 4, 0, Math.PI * 2);
                    ctx.fillStyle = '#0df246'; ctx.fill();
                }

                var animated = false;
                function animate() {
                    if (animated) return;
                    animated = true;
                    var start    = null;
                    var DURATION = 1600;
                    function step(ts) {
                        if (!start) start = ts;
                        var prog = Math.min((ts - start) / DURATION, 1);
                        var ease = 1 - Math.pow(1 - prog, 3);
                        drawFrame(ease);
                        if (prog < 1) requestAnimationFrame(step);
                    }
                    requestAnimationFrame(step);
                }

                drawGrid();

                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function (entries) {
                        if (entries[0].isIntersecting) { animate(); io.disconnect(); }
                    }, { threshold: 0.3 });
                    io.observe(document.getElementById('tk-ele-chart-wrap'));
                } else {
                    animate();
                }
            }());
            </script>
        </div>
        <?php endif; ?>

        <?php /* ── Gallery ── */ ?>
        <?php
        $gallery_ids = $data['gallery'] ? json_decode($data['gallery'], true) : [];
        if ( $gallery_ids && is_array($gallery_ids) && count($gallery_ids) ):
        ?>
        <div class="tk-single__section">
            <h2 class="tk-single__section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M1 5.25A2.25 2.25 0 013.25 3h13.5A2.25 2.25 0 0119 5.25v9.5A2.25 2.25 0 0116.75 17H3.25A2.25 2.25 0 011 14.75v-9.5zm1.5 5.81v3.69c0 .414.336.75.75.75h13.5a.75.75 0 00.75-.75v-2.69l-2.22-2.219a.75.75 0 00-1.06 0l-1.91 1.909.47.47a.75.75 0 11-1.06 1.06L6.53 8.091a.75.75 0 00-1.06 0l-3 2.97v-.001zm6.063-6.81a1.688 1.688 0 110 3.375 1.688 1.688 0 010-3.375z" clip-rule="evenodd"/></svg>
                <?php esc_html_e('Gallery','trailkit') ?>
            </h2>
            <div class="tk-gallery">
                <?php foreach ( $gallery_ids as $img_id ):
                    $src = wp_get_attachment_image_url($img_id, 'medium_large');
                    $full = wp_get_attachment_image_url($img_id, 'full');
                    if (!$src) continue;
                ?>
                <a href="<?php echo esc_url($full) ?>" class="tk-gallery__item">
                    <img src="<?php echo esc_url($src) ?>" alt="<?php
                    /* translators: %s = route title */
                    echo esc_attr( sprintf( esc_html__( '%s — gallery image', 'trailkit' ), get_the_title() ) ) ?>" loading="lazy">
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- .tk-single__body -->
</div><!-- .tk-single -->

<?php if ( ! TK_LITE && $nav_mobile_url ): ?>
<div class="tk-maps-mobile-bar">
    <a href="<?php echo esc_url( $nav_mobile_url ) ?>" target="_blank" rel="noopener" class="tk-maps-mobile-bar__btn tk-maps-mobile-bar__btn--gmaps">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        <?php esc_html_e( 'Open in Google Maps', 'trailkit' ) ?>
    </a>
</div>
<?php endif; ?>

<?php get_footer(); ?>
