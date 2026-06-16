<?php
/**
 * Template: events archive + taxonomy (event_category / event_tag).
 * Works standalone and inside Astra (Free + Premium).
 *
 * Astra notes:
 *  - Uses Astra's content-column wrappers when the theme is active.
 *  - Respects the site's configured sidebar position (left/right/none).
 *  - CE_Astra_Compat sets content-width to 100 for archives with no sidebar
 *    so the views have maximum horizontal space.
 */
defined( 'ABSPATH' ) || exit;

get_header();

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

<div class="ce-archive-wrap">
    <div class="ce-archive-header">
        <h1 class="ce-archive-title">
            <?php
            if ( is_tax( 'event_category' ) || is_tax( 'event_type' ) || is_tax( 'event_tag' ) ) {
                single_term_title();
            } elseif ( is_tax() ) {
                single_term_title();
            } else {
                esc_html_e( 'Events', 'club-events' );
            }
            ?>
        </h1>
        <div class="ce-archive-toolbar">
            <div class="ce-view-switcher">
                <a href="#" class="ce-view-btn active" data-view="timeline"
                   title="<?php esc_attr_e( 'Timeline', 'club-events' ); ?>">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M4 6h12M4 10h8M4 14h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </a>
                <a href="#" class="ce-view-btn" data-view="cards"
                   title="<?php esc_attr_e( 'Cards', 'club-events' ); ?>">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="2" y="2" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="11" y="2" width="7" height="9" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="2" y="13" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.5"/><rect x="11" y="13" width="7" height="5" rx="1.5" stroke="currentColor" stroke-width="1.5"/></svg>
                </a>
                <a href="#" class="ce-view-btn" data-view="overview"
                   title="<?php esc_attr_e( 'Calendar', 'club-events' ); ?>">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="4" width="14" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </a>
            </div>
            <?php if ( get_option( 'ce_ics_feed_enabled', '1' ) === '1' ) : ?>
            <a href="<?php echo esc_url( CE_ICS_Export::get_feed_url() ); ?>"
               class="ce-ics-link"
               title="<?php esc_attr_e( 'Subscribe — add all events to your calendar', 'club-events' ); ?>">
                <svg viewBox="0 0 20 20" width="16" fill="none"><rect x="3" y="4" width="14" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="14" cy="14" r="5" fill="#3b82f6"/><path d="M14 12v4M12 14h4" stroke="#fff" stroke-width="1.5" stroke-linecap="round"/></svg>
                <?php esc_html_e( 'Subscribe (ICS)', 'club-events' ); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div id="ce-view-timeline">
        <?php echo do_shortcode( '[club_events_timeline show_filter="true" limit="30"]' ); ?>
    </div>
    <div id="ce-view-cards" hidden>
        <?php echo do_shortcode( '[club_events_cards show_filter="true" columns="3" limit="12"]' ); ?>
    </div>
    <div id="ce-view-overview" hidden>
        <?php echo do_shortcode( '[club_events_overview show_filter="true"]' ); ?>
    </div>
</div>

<script>
(function () {
    var btns = document.querySelectorAll('.ce-view-btn');
    var views = {
        timeline: document.getElementById('ce-view-timeline'),
        cards:    document.getElementById('ce-view-cards'),
        overview: document.getElementById('ce-view-overview'),
    };
    btns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            btns.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var view = btn.dataset.view;
            Object.keys(views).forEach(function (k) {
                if (views[k]) views[k].hidden = (k !== view);
            });
        });
    });
})();
</script>

<?php
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
