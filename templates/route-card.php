<?php
/**
 * Template: Route Card
 * Override: place file at {your-theme}/trailkit/route-card.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$post_id  = $post_id ?? get_the_ID();
$data     = TK_Route_Fields::get( $post_id );
$thumb    = get_the_post_thumbnail_url( $post_id, 'medium_large' );
$title    = get_the_title( $post_id );
$link     = get_permalink( $post_id );
$excerpt  = get_the_excerpt( $post_id );

$difficulty_labels = [ 'easy' => 'Easy', 'moderate' => 'Moderate', 'hard' => 'Hard', 'extreme' => 'Extreme' ];
$difficulty        = $data['difficulty'];
$diff_label        = $difficulty_labels[ $difficulty ] ?? ucfirst( $difficulty );

$activities = get_the_terms( $post_id, 'tk_activity' );
?>
<article class="tk-card tk-card--route">
    <a href="<?php echo esc_url($link) ?>" class="tk-card__link" aria-label="<?php echo esc_attr($title) ?>"></a>

    <div class="tk-card__image">
        <?php if ( $thumb ): ?>
            <img src="<?php echo esc_url($thumb) ?>" alt="<?php echo esc_attr($title) ?>" loading="lazy">
        <?php else: ?>
            <div class="tk-card__image-placeholder">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 17l4-8 4 4 3-5 4 9"/></svg>
            </div>
        <?php endif; ?>
        <span class="tk-badge tk-badge--<?php echo esc_attr($difficulty) ?>"><?php echo esc_html($diff_label) ?></span>
    </div>

    <div class="tk-card__body">
        <?php if ( $activities && ! is_wp_error($activities) ): ?>
        <div class="tk-card__meta">
            <?php foreach ( array_slice($activities, 0, 2) as $term ): ?>
                <span class="tk-tag"><?php echo esc_html($term->name) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 class="tk-card__title"><?php echo esc_html($title) ?></h3>

        <?php if ( $excerpt ): ?>
        <p class="tk-card__excerpt"><?php echo esc_html( wp_trim_words($excerpt, 18) ) ?></p>
        <?php endif; ?>

        <div class="tk-card__stats">
            <?php if ( $data['distance'] ): ?>
            <span class="tk-stat"><svg viewBox="0 0 16 16"><path d="M8 2a6 6 0 100 12A6 6 0 008 2zm0 10.5A4.5 4.5 0 1112.5 8 4.505 4.505 0 018 12.5z"/></svg><?php echo esc_html($data['distance']) ?> km</span>
            <?php endif; ?>
            <?php if ( $data['elevation'] ): ?>
            <span class="tk-stat"><svg viewBox="0 0 16 16"><path d="M8 2l3 5H5l3-5zm0 12l-3-5h6l-3 5z"/></svg>↑ <?php echo esc_html($data['elevation']) ?> m</span>
            <?php endif; ?>
            <?php if ( $data['time'] ): ?>
            <span class="tk-stat"><svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6" fill="none" stroke="currentColor"/><path d="M8 5v3l2 2"/></svg><?php echo esc_html($data['time']) ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
