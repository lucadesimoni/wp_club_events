<?php
/**
 * Template: single club event.
 * Works standalone and inside Astra (Free + Premium).
 *
 * Astra notes:
 *  - CE_Astra_Compat forces "no-sidebar" layout via the astra_page_layout filter.
 *  - The hero section uses CSS full-bleed (width:100vw; negative margins) to escape
 *    the content column — see ce-astra-bridge <style> in wp_head.
 *  - Astra's entry-header is suppressed to avoid a duplicate title.
 *  - JSON-LD Event schema is injected via wp_head.
 */
defined( 'ABSPATH' ) || exit;

get_header();

// ── Astra-compatible content wrappers ─────────────────────────────────────
$is_astra = class_exists( 'CE_Astra_Compat' ) && CE_Astra_Compat::is_active();

if ( $is_astra ) {
    do_action( 'astra_content_top' );
    ?>
    <div id="primary" <?php if ( function_exists( 'astra_primary_content_class' ) ) astra_primary_content_class(); else echo 'class="content-area primary"'; ?>>
    <?php if ( function_exists( 'astra_primary_content_top' ) ) astra_primary_content_top(); ?>
    <main id="main" class="site-main" role="main">
    <?php
} else {
    echo '<main class="ce-main" role="main">';
}
?>

<?php while ( have_posts() ) : the_post(); ?>
<?php
    $post_id  = get_the_ID();
    $start    = get_post_meta( $post_id, '_ce_start_date', true );
    $end      = get_post_meta( $post_id, '_ce_end_date', true );
    $all_day  = get_post_meta( $post_id, '_ce_all_day', true );
    $location = get_post_meta( $post_id, '_ce_location', true );
    $loc_url  = get_post_meta( $post_id, '_ce_location_url', true );
    $ext_url  = get_post_meta( $post_id, '_ce_external_url', true );
    $color    = get_post_meta( $post_id, '_ce_color', true ) ?: '#3b82f6';
    $cats     = wp_get_post_terms( $post_id, 'event_category' );
    $ics_url  = CE_ICS_Export::get_single_url( $post_id );

    $date_fmt = get_option( 'date_format' );
    $time_fmt = get_option( 'time_format' );
    $start_ts = $start ? strtotime( $start ) : null;
    $end_ts   = $end   ? strtotime( $end )   : null;
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'ce-single-event' ); ?>>

    <?php /* ── Hero — full-bleed via CSS when inside Astra container ── */ ?>
    <div class="ce-event-hero" style="--ce-color:<?php echo esc_attr( $color ); ?>">
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="ce-event-hero-img"><?php the_post_thumbnail( 'full' ); ?></div>
        <?php endif; ?>
        <div class="ce-event-hero-content">
            <div class="ce-event-hero-inner">

                <?php if ( ! is_wp_error( $cats ) && $cats ) : ?>
                <div class="ce-event-cats">
                    <?php foreach ( $cats as $cat ) : ?>
                    <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="ce-category-badge">
                        <?php echo esc_html( $cat->name ); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <h1 class="ce-event-title"><?php the_title(); ?></h1>

                <div class="ce-event-meta-bar">
                    <?php if ( $start_ts ) : ?>
                    <div class="ce-meta-pill">
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><rect x="3" y="4" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <span>
                            <?php if ( $all_day ) : ?>
                                <?php echo esc_html( date_i18n( $date_fmt, $start_ts ) ); ?>
                                <?php if ( $end_ts && $end_ts !== $start_ts ) echo ' – ' . esc_html( date_i18n( $date_fmt, $end_ts ) ); ?>
                            <?php else : ?>
                                <?php echo esc_html( date_i18n( $date_fmt, $start_ts ) ); ?>
                                <?php echo esc_html( date_i18n( $time_fmt, $start_ts ) ); ?>
                                <?php if ( $end_ts ) echo ' – ' . esc_html( date_i18n( $time_fmt, $end_ts ) ); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ( $location ) : ?>
                    <div class="ce-meta-pill">
                        <svg viewBox="0 0 20 20" width="16" height="16" fill="none"><path d="M10 2a6 6 0 0 1 6 6c0 5-6 10-6 10S4 13 4 8a6 6 0 0 1 6-6z" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="8" r="2" fill="currentColor"/></svg>
                        <?php if ( $loc_url ) : ?>
                        <a href="<?php echo esc_url( $loc_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $location ); ?></a>
                        <?php else : ?>
                        <span><?php echo esc_html( $location ); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <?php /* ── Breadcrumbs ── */ ?>
    <?php if ( function_exists( 'astra_breadcrumb' ) ) : ?>
    <div class="ce-breadcrumb-wrap"><?php astra_breadcrumb(); ?></div>
    <?php endif; ?>

    <?php /* ── Two-column body ── */ ?>
    <div class="ce-event-body-wrap">

        <div class="ce-event-content">
            <?php the_content(); ?>
        </div>

        <aside class="ce-event-sidebar">
            <div class="ce-sidebar-card">
                <h3><?php esc_html_e( 'Event Details', 'club-events' ); ?></h3>

                <?php if ( $start_ts ) : ?>
                <div class="ce-detail-row">
                    <span class="ce-detail-icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none"><rect x="3" y="4" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </span>
                    <div>
                        <strong><?php esc_html_e( 'Date', 'club-events' ); ?></strong>
                        <p>
                            <?php if ( $all_day ) : ?>
                                <?php echo esc_html( date_i18n( $date_fmt, $start_ts ) ); ?>
                                <?php if ( $end_ts && date( 'Ymd', $start_ts ) !== date( 'Ymd', $end_ts ) ) echo ' – ' . esc_html( date_i18n( $date_fmt, $end_ts ) ); ?>
                            <?php else : ?>
                                <?php echo esc_html( date_i18n( $date_fmt . ' ' . $time_fmt, $start_ts ) ); ?>
                                <?php if ( $end_ts ) echo ' – ' . esc_html( date_i18n( $time_fmt, $end_ts ) ); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( $location ) : ?>
                <div class="ce-detail-row">
                    <span class="ce-detail-icon">
                        <svg viewBox="0 0 20 20" width="16" fill="none"><path d="M10 2a6 6 0 0 1 6 6c0 5-6 10-6 10S4 13 4 8a6 6 0 0 1 6-6z" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="8" r="2" fill="currentColor"/></svg>
                    </span>
                    <div>
                        <strong><?php esc_html_e( 'Location', 'club-events' ); ?></strong>
                        <p>
                            <?php if ( $loc_url ) : ?>
                            <a href="<?php echo esc_url( $loc_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $location ); ?></a>
                            <?php else : ?>
                            <?php echo esc_html( $location ); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="ce-sidebar-actions">
                    <a href="<?php echo esc_url( $ics_url ); ?>" class="ce-btn ce-btn-outline ce-btn-full">
                        <svg viewBox="0 0 20 20" width="16" fill="none"><rect x="3" y="4" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M10 12v-2M10 14v.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        <?php esc_html_e( 'Add to Calendar (.ics)', 'club-events' ); ?>
                    </a>
                    <?php if ( $ext_url ) : ?>
                    <a href="<?php echo esc_url( $ext_url ); ?>" target="_blank" rel="noopener" class="ce-btn ce-btn-primary ce-btn-full">
                        <?php esc_html_e( 'More Information', 'club-events' ); ?> →
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $share_url   = get_permalink( $post_id );
            $share_title = get_the_title( $post_id );
            echo CE_Shortcodes::share_buttons( $share_url, $share_title );
            ?>

            <?php if ( get_option( 'ce_subscription_enabled', '1' ) === '1' ) : ?>
            <div class="ce-sidebar-card">
                <?php echo do_shortcode( '[club_events_subscribe]' ); ?>
            </div>
            <?php endif; ?>
        </aside>

    </div>

    <div class="ce-event-footer">
        <a href="<?php echo esc_url( get_post_type_archive_link( 'club_event' ) ); ?>" class="ce-back-link">
            ← <?php esc_html_e( 'All Events', 'club-events' ); ?>
        </a>
    </div>

</article>

<?php endwhile; ?>

<?php
// ── Close Astra wrappers ──────────────────────────────────────────────────
if ( $is_astra ) {
    echo '</main>';
    if ( function_exists( 'astra_primary_content_bottom' ) ) astra_primary_content_bottom();
    echo '</div>'; // #primary
    if ( function_exists( 'astra_sidebar' ) ) astra_sidebar();
    do_action( 'astra_content_bottom' );
} else {
    echo '</main>';
}

get_footer();
