<?php
/**
 * Single Guide Template
 * Override: copy to {theme}/trailkit/single-guide.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();
$guide   = TK_Guide_Fields::get( $post_id );
$regions = get_the_terms( $post_id, 'tk_region' );
$specs   = $guide['specialties'] ? json_decode($guide['specialties'], true) : [];

$photo_url = '';
if ( $guide['photo_id'] ) $photo_url = wp_get_attachment_image_url($guide['photo_id'], 'large');
if ( ! $photo_url )       $photo_url = get_the_post_thumbnail_url($post_id, 'large');

get_header();
?>
<div class="tk-single tk-single--guide">

    <div class="tk-single__hero tk-single__hero--guide" style="background-color:#0d1117">
        <div class="tk-single__hero-overlay" style="opacity:0.3"></div>
        <div class="tk-single__hero-content tk-single__hero-content--guide">
            <div class="tk-single__breadcrumb">
                <a href="<?php echo esc_url( get_post_type_archive_link('tk_guide') ) ?>"><?php esc_html_e('Guides','trailkit') ?></a>
                <span>›</span>
                <span><?php the_title() ?></span>
            </div>
            <div class="tk-guide-header">
                <div class="tk-guide-header__avatar">
                    <?php if ( $photo_url ): ?>
                        <img src="<?php echo esc_url($photo_url) ?>" alt="<?php echo esc_attr(get_the_title()) ?>">
                    <?php else: ?>
                        <div class="tk-guide-header__avatar-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="48" height="48"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        </div>
                    <?php endif; ?>
                    <?php if ( $guide['is_featured'] ): ?>
                        <span class="tk-guide-header__featured">★ Featured</span>
                    <?php endif; ?>
                </div>
                <div class="tk-guide-header__info">
                    <h1 class="tk-single__title"><?php echo esc_html( get_the_title() ) ?></h1>
                    <?php if ( $guide['price_from'] ): ?>
                        <p class="tk-guide-header__price"><?php
                        /* translators: %s = formatted price number */
                        printf( esc_html__( 'From $%s / day', 'trailkit' ), esc_html( number_format( $guide['price_from'] ) ) ) ?></p>
                    <?php endif; ?>
                    <?php if ( $regions && ! is_wp_error($regions) ): ?>
                        <p class="tk-single__region">
                            <svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor"><path d="M8 2C5.8 2 4 3.8 4 6c0 3 4 8 4 8s4-5 4-8c0-2.2-1.8-4-4-4zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg>
                            <?php echo esc_html( implode(', ', wp_list_pluck($regions,'name')) ) ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $specs ): ?>
                    <div class="tk-single__tags" style="margin-top:8px">
                        <?php foreach ($specs as $s): ?>
                            <span class="tk-tag"><?php echo esc_html(TK_Guide_Fields::$specialties[$s] ?? $s) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="tk-single__body">

        <div class="tk-single__guide-layout">

            <?php /* ── Bio ── */ ?>
            <div class="tk-single__guide-main">
                <h2 class="tk-single__section-title"><?php esc_html_e('About','trailkit') ?></h2>
                <div class="tk-single__content">
                    <?php the_content() ?>
                </div>

                <?php if ( $guide['lat'] && $guide['lng'] ): ?>
                <div class="tk-single__section" style="margin-top:2rem">
                    <h2 class="tk-single__section-title"><?php esc_html_e('Service Area','trailkit') ?></h2>
                    <div id="tk-single-map" class="tk-map" style="height:320px;border-radius:12px;overflow:hidden;border:1px solid var(--tk-border)"></div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof L === 'undefined') return;
                        var map = L.map('tk-single-map').setView([<?php echo floatval($guide['lat']) ?>, <?php echo floatval($guide['lng']) ?>], 8);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap', maxZoom:19 }).addTo(map);
                        L.circle([<?php echo floatval($guide['lat']) ?>, <?php echo floatval($guide['lng']) ?>], {
                            radius: <?php echo intval($guide['radius_km']) * 1000 ?>,
                            color: '#0df246', fillColor: '#0df246', fillOpacity: 0.1, weight: 2
                        }).addTo(map);
                        L.marker([<?php echo floatval($guide['lat']) ?>, <?php echo floatval($guide['lng']) ?>]).addTo(map);
                    });
                    </script>
                </div>
                <?php endif; ?>
            </div>

            <?php /* ── Contact sidebar ── */ ?>
            <aside class="tk-single__guide-sidebar">
                <div class="tk-contact-card">
                    <h3 class="tk-contact-card__title"><?php esc_html_e('Contact','trailkit') ?></h3>

                    <?php if ( $guide['whatsapp'] ): ?>
                    <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/','', $guide['whatsapp'])) ?>" target="_blank" rel="noopener" class="tk-contact-card__btn tk-contact-card__btn--wa">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
                        WhatsApp
                    </a>
                    <?php endif; ?>

                    <?php if ( $guide['email'] ): ?>
                    <a href="mailto:<?php echo esc_attr($guide['email']) ?>" class="tk-contact-card__btn tk-contact-card__btn--email">
                        <svg viewBox="0 0 20 20" fill="currentColor" width="18" height="18"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z"/><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z"/></svg>
                        <?php echo esc_html($guide['email']) ?>
                    </a>
                    <?php endif; ?>

                    <?php if ( $guide['instagram'] ): ?>
                    <a href="https://instagram.com/<?php echo esc_attr(ltrim($guide['instagram'],'@')) ?>" target="_blank" rel="noopener" class="tk-contact-card__btn tk-contact-card__btn--ig">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        @<?php echo esc_html(ltrim($guide['instagram'],'@')) ?>
                    </a>
                    <?php endif; ?>

                    <?php if ( $guide['price_from'] ): ?>
                    <div class="tk-contact-card__price">
                        <span class="tk-contact-card__price-label"><?php esc_html_e('Starting from','trailkit') ?></span>
                        <span class="tk-contact-card__price-value">$<?php echo esc_html(number_format($guide['price_from'])) ?> <small><?php esc_html_e('/day','trailkit') ?></small></span>
                    </div>
                    <?php endif; ?>
                </div>
            </aside>

        </div>
    </div>
</div>
<?php get_footer(); ?>
