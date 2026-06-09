<?php
/**
 * Single POI Template
 * Override: copy to {theme}/trailplugin/single-poi.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
$data    = TK_POI_Fields::get( $post_id );
$thumb   = get_the_post_thumbnail_url( $post_id, 'full' );
$types   = get_the_terms( $post_id, 'tk_poi_type' );
$regions = get_the_terms( $post_id, 'tk_region' );

get_header();
?>
<div class="tk-single tk-single--poi">

    <div class="tk-single__hero" style="<?php echo $thumb ? 'background-image:url(' . esc_url($thumb) . ');background-position:' . esc_attr($data['hero_position']) : 'background-color:#0a1628' ?>">
        <div class="tk-single__hero-overlay"></div>
        <div class="tk-single__hero-content">
            <div class="tk-single__breadcrumb">
                <a href="<?php echo esc_url( get_post_type_archive_link('tk_poi') ) ?>"><?php _e('Points of Interest','trailplugin') ?></a>
                <span>›</span>
                <span><?php the_title() ?></span>
            </div>
            <?php if ( $types && ! is_wp_error($types) ): ?>
            <div class="tk-single__tags">
                <?php foreach ( $types as $t ): ?>
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

    <?php if ( $data['lat'] && $data['lng'] ): ?>
    <div class="tk-single__stats-wrap">
        <div class="tk-single__stats">
            <div class="tk-single__stat">
                <span class="tk-single__stat-value" style="font-family:monospace"><?php echo esc_html( round(floatval($data['lat']),4) . ', ' . round(floatval($data['lng']),4) ) ?></span>
                <span class="tk-single__stat-label"><?php _e('Coordinates','trailplugin') ?></span>
            </div>
            <?php if ( $types && ! is_wp_error($types) ): ?>
            <div class="tk-single__stat">
                <span class="tk-single__stat-value"><?php echo esc_html($types[0]->name) ?></span>
                <span class="tk-single__stat-label"><?php _e('Type','trailplugin') ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="tk-single__body">

        <?php if ( $data['conditions_alert'] ): ?>
        <div class="tk-alert">
            <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z"/></svg>
            <?php echo esc_html($data['conditions_alert']) ?>
        </div>
        <?php endif; ?>

        <div class="tk-single__content">
            <?php the_content() ?>
        </div>

        <?php if ( $data['lat'] && $data['lng'] ): ?>
        <div class="tk-single__section">
            <h2 class="tk-single__section-title">
                <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.387 1.436-.957 2.255-1.716C15.046 15.23 17 12.558 17 9A7 7 0 103 9c0 3.558 1.954 6.23 3.373 7.633.819.76 1.635 1.329 2.255 1.716a13.4 13.4 0 001.061.571l.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>
                <?php _e('Location','trailplugin') ?>
            </h2>
            <div id="tk-single-map" class="tk-map" style="height:380px;border-radius:12px;overflow:hidden;border:1px solid var(--tk-border)"></div>
            <?php if ( $data['gmaps_url'] ): ?>
            <a href="<?php echo esc_url($data['gmaps_url']) ?>" target="_blank" rel="noopener" class="tk-btn tk-btn--outline" style="margin-top:12px;display:inline-flex">
                <svg viewBox="0 0 20 20" fill="currentColor" width="16" height="16"><path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.387 1.436-.957 2.255-1.716C15.046 15.23 17 12.558 17 9A7 7 0 103 9c0 3.558 1.954 6.23 3.373 7.633.819.76 1.635 1.329 2.255 1.716a13.4 13.4 0 001.061.571l.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/></svg>
                <?php _e('Open in Google Maps','trailplugin') ?>
            </a>
            <?php endif; ?>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof L === 'undefined') return;
                var map = L.map('tk-single-map').setView([<?php echo floatval($data['lat']) ?>, <?php echo floatval($data['lng']) ?>], 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom: 19 }).addTo(map);
                L.marker([<?php echo floatval($data['lat']) ?>, <?php echo floatval($data['lng']) ?>]).addTo(map).bindPopup("<?php echo esc_js(get_the_title()) ?>").openPopup();
            });
            </script>
        </div>
        <?php endif; ?>

        <?php
        $gallery_ids = $data['gallery'] ? json_decode($data['gallery'], true) : [];
        if ( $gallery_ids && is_array($gallery_ids) && count($gallery_ids) ):
        ?>
        <div class="tk-single__section">
            <h2 class="tk-single__section-title"><?php _e('Gallery','trailplugin') ?></h2>
            <div class="tk-gallery">
                <?php foreach ( $gallery_ids as $img_id ):
                    $src  = wp_get_attachment_image_url($img_id,'medium_large');
                    $full = wp_get_attachment_image_url($img_id,'full');
                    if (!$src) continue;
                ?>
                <a href="<?php echo esc_url($full) ?>" class="tk-gallery__item">
                    <img src="<?php echo esc_url($src) ?>" alt="<?php echo esc_attr( sprintf( __( '%s — gallery image', 'trailplugin' ), get_the_title() ) ) ?>" loading="lazy">
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php get_footer(); ?>
