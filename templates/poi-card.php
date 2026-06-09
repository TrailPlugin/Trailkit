<?php
/**
 * Template: POI Card
 * Override: place file at {your-theme}/trailplugin/poi-card.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = $post_id ?? get_the_ID();
$data    = TK_POI_Fields::get( $post_id );
$thumb   = get_the_post_thumbnail_url( $post_id, 'medium_large' );
$title   = get_the_title( $post_id );
$link    = get_permalink( $post_id );
$excerpt = get_the_excerpt( $post_id );
$types   = get_the_terms( $post_id, 'tk_poi_type' );
?>
<article class="tk-card tk-card--poi">
    <a href="<?php echo esc_url($link) ?>" class="tk-card__link" aria-label="<?php echo esc_attr($title) ?>"></a>

    <div class="tk-card__image">
        <?php if ( $thumb ): ?>
            <img src="<?php echo esc_url($thumb) ?>" alt="<?php echo esc_attr($title) ?>" loading="lazy">
        <?php else: ?>
            <div class="tk-card__image-placeholder">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            </div>
        <?php endif; ?>
        <?php if ( $types && ! is_wp_error($types) ): ?>
            <span class="tk-badge tk-badge--type"><?php echo esc_html($types[0]->name) ?></span>
        <?php endif; ?>
    </div>

    <div class="tk-card__body">
        <h3 class="tk-card__title"><?php echo esc_html($title) ?></h3>
        <?php if ( $excerpt ): ?>
        <p class="tk-card__excerpt"><?php echo esc_html( wp_trim_words($excerpt, 18) ) ?></p>
        <?php endif; ?>
        <?php if ( $data['lat'] && $data['lng'] ): ?>
        <div class="tk-card__coords">
            <svg viewBox="0 0 16 16"><path d="M8 2C5.8 2 4 3.8 4 6c0 3 4 8 4 8s4-5 4-8c0-2.2-1.8-4-4-4zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z"/></svg>
            <?php echo esc_html( round(floatval($data['lat']), 4) . ', ' . round(floatval($data['lng']), 4) ) ?>
        </div>
        <?php endif; ?>
    </div>
</article>
