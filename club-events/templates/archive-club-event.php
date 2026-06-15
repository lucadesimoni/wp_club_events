<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="ce-archive-wrap">
    <div class="ce-archive-header">
        <h1 class="ce-archive-title">
            <?php
            if ( is_tax( 'event_category' ) || is_tax( 'event_tag' ) ) {
                single_term_title();
            } else {
                esc_html_e( 'Events', 'club-events' );
            }
            ?>
        </h1>
        <div class="ce-archive-toolbar">
            <div class="ce-view-switcher">
                <a href="#" class="ce-view-btn active" data-view="timeline" title="<?php esc_attr_e( 'Timeline', 'club-events' ); ?>">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><path d="M4 6h12M4 10h8M4 14h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </a>
                <a href="#" class="ce-view-btn" data-view="overview" title="<?php esc_attr_e( 'Calendar', 'club-events' ); ?>">
                    <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="4" width="14" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                </a>
            </div>
            <a href="<?php echo esc_url( CE_ICS_Export::get_feed_url() ); ?>" class="ce-ics-link" title="<?php esc_attr_e( 'Subscribe to ICS feed', 'club-events' ); ?>">
                <svg viewBox="0 0 20 20" width="18" fill="none"><rect x="3" y="4" width="14" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M7 2v3M13 2v3M3 9h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="13" cy="13" r="4" fill="#3b82f6"/><path d="M13 11v4M11 13h4" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                <?php esc_html_e( 'Subscribe (ICS)', 'club-events' ); ?>
            </a>
        </div>
    </div>

    <div id="ce-view-timeline"><?php echo do_shortcode( '[club_events_timeline show_filter="true"]' ); ?></div>
    <div id="ce-view-overview" hidden><?php echo do_shortcode( '[club_events_overview show_filter="true"]' ); ?></div>
</div>

<script>
(function(){
    var btns = document.querySelectorAll('.ce-view-btn');
    btns.forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.preventDefault();
            btns.forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            var view = btn.dataset.view;
            document.getElementById('ce-view-timeline').hidden = (view !== 'timeline');
            document.getElementById('ce-view-overview').hidden  = (view !== 'overview');
        });
    });
})();
</script>
<?php get_footer(); ?>
