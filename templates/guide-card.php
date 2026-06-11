<?php
/**
 * Template: Guide Card
 * Override: place file at {your-theme}/trailkit/guide-card.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = $post_id ?? get_the_ID();
$guide   = $guide   ?? TK_Guide_Fields::get( $post_id );
$title   = get_the_title( $post_id );
$link    = get_permalink( $post_id );
$bio     = get_the_excerpt( $post_id );

$photo_url = '';
if ( $guide['photo_id'] ) {
    $photo_url = wp_get_attachment_image_url( $guide['photo_id'], 'thumbnail' );
}
if ( ! $photo_url ) {
    $photo_url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
}

$specs = $guide['specialties'] ? json_decode( $guide['specialties'], true ) : [];
$regions = get_the_terms( $post_id, 'tk_region' );
?>
<article class="tk-card tk-card--guide<?php echo $guide['is_featured'] ? ' tk-card--featured' : '' ?>">
    <a href="<?php echo esc_url($link) ?>" class="tk-card__link" aria-label="<?php echo esc_attr($title) ?>"></a>

    <div class="tk-card__guide-header">
        <div class="tk-card__avatar">
            <?php if ( $photo_url ): ?>
                <img src="<?php echo esc_url($photo_url) ?>" alt="<?php echo esc_attr($title) ?>">
            <?php else: ?>
                <div class="tk-card__avatar-placeholder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                </div>
            <?php endif; ?>
            <?php if ( $guide['is_featured'] ): ?>
                <span class="tk-card__featured-badge" title="<?php esc_attr_e( 'Featured', 'trailkit' ) ?>">★</span>
            <?php endif; ?>
        </div>
        <div class="tk-card__guide-info">
            <h3 class="tk-card__title"><?php echo esc_html($title) ?></h3>
            <?php if ( $guide['price_from'] ): ?>
                <span class="tk-card__price"><?php
                /* translators: %s = formatted price number */
                printf( esc_html__( 'From $%s/day', 'trailkit' ), esc_html( number_format( $guide['price_from'] ) ) ) ?></span>
            <?php endif; ?>
            <?php if ( $regions && ! is_wp_error($regions) ): ?>
                <span class="tk-card__region"><?php echo esc_html( implode(', ', wp_list_pluck($regions,'name')) ) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( $bio ): ?>
    <p class="tk-card__excerpt"><?php echo esc_html( wp_trim_words($bio, 18) ) ?></p>
    <?php endif; ?>

    <?php if ( $specs ): ?>
    <div class="tk-card__specialties">
        <?php foreach ( array_slice($specs, 0, 4) as $spec ): ?>
            <span class="tk-tag"><?php echo esc_html( TK_Guide_Fields::$specialties[$spec] ?? $spec ) ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="tk-card__guide-contact">
        <?php if ( $guide['whatsapp'] ): ?>
        <a href="https://wa.me/<?php echo esc_attr( preg_replace('/\D/','', $guide['whatsapp']) ) ?>" class="tk-contact-btn tk-contact-btn--wa" target="_blank" rel="noopener" onclick="event.stopPropagation()">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
            WhatsApp
        </a>
        <?php endif; ?>
        <?php if ( $guide['email'] ): ?>
        <a href="mailto:<?php echo esc_attr($guide['email']) ?>" class="tk-contact-btn tk-contact-btn--email" onclick="event.stopPropagation()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Email
        </a>
        <?php endif; ?>
    </div>
</article>
