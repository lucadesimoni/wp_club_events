<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ce-admin-wrap">
    <h1 class="ce-page-title">
        <span class="dashicons dashicons-calendar-alt"></span>
        <?php esc_html_e( 'Club Events', 'club-events' ); ?>
    </h1>

    <div class="ce-dashboard-stats">
        <div class="ce-stat-card">
            <div class="ce-stat-icon ce-stat-icon--blue">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="ce-stat-body">
                <div class="ce-stat-value"><?php echo esc_html( $total_events ); ?></div>
                <div class="ce-stat-label"><?php esc_html_e( 'Published Events', 'club-events' ); ?></div>
            </div>
        </div>
        <div class="ce-stat-card">
            <div class="ce-stat-icon ce-stat-icon--green">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="ce-stat-body">
                <div class="ce-stat-value"><?php echo esc_html( $total_subs ); ?></div>
                <div class="ce-stat-label"><?php esc_html_e( 'Subscribers', 'club-events' ); ?></div>
            </div>
        </div>
        <div class="ce-stat-card">
            <div class="ce-stat-icon ce-stat-icon--purple">
                <span class="dashicons dashicons-google"></span>
            </div>
            <div class="ce-stat-body">
                <div class="ce-stat-value"><?php echo esc_html( $total_cals ); ?></div>
                <div class="ce-stat-label"><?php esc_html_e( 'Google Calendars', 'club-events' ); ?></div>
            </div>
        </div>
    </div>

    <div class="ce-dashboard-grid">
        <div class="ce-card">
            <div class="ce-card-header">
                <h2><?php esc_html_e( 'Upcoming Events', 'club-events' ); ?></h2>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=club_event' ) ); ?>" class="ce-card-link">
                    <?php esc_html_e( 'View all', 'club-events' ); ?> →
                </a>
            </div>
            <?php if ( empty( $upcoming ) ) : ?>
            <p class="ce-empty-state"><?php esc_html_e( 'No upcoming events. Create your first event!', 'club-events' ); ?></p>
            <?php else : ?>
            <ul class="ce-upcoming-list">
                <?php foreach ( $upcoming as $post ) :
                    $start = get_post_meta( $post->ID, '_ce_start_date', true );
                    $color = get_post_meta( $post->ID, '_ce_color', true ) ?: '#3b82f6';
                    $location = get_post_meta( $post->ID, '_ce_location', true );
                ?>
                <li class="ce-upcoming-item" style="--ce-color:<?php echo esc_attr( $color ); ?>">
                    <div class="ce-upcoming-date">
                        <?php if ( $start ) : ?>
                        <span class="ce-upcoming-day"><?php echo esc_html( date_i18n( 'd', strtotime( $start ) ) ); ?></span>
                        <span class="ce-upcoming-mon"><?php echo esc_html( date_i18n( 'M', strtotime( $start ) ) ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ce-upcoming-info">
                        <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="ce-upcoming-title">
                            <?php echo esc_html( $post->post_title ); ?>
                        </a>
                        <?php if ( $location ) : ?>
                        <span class="ce-upcoming-loc"><?php echo esc_html( $location ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ce-upcoming-actions">
                        <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank" class="button button-small">
                            <?php esc_html_e( 'View', 'club-events' ); ?>
                        </a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <div class="ce-card">
            <div class="ce-card-header">
                <h2><?php esc_html_e( 'Quick Actions', 'club-events' ); ?></h2>
            </div>
            <div class="ce-quick-actions">
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=club_event' ) ); ?>" class="ce-quick-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e( 'New Event', 'club-events' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ce-calendars' ) ); ?>" class="ce-quick-btn">
                    <span class="dashicons dashicons-google"></span>
                    <?php esc_html_e( 'Manage Calendars', 'club-events' ); ?>
                </a>
                <button class="ce-quick-btn" id="ce-sync-btn">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e( 'Sync Now', 'club-events' ); ?>
                </button>
                <a href="<?php echo esc_url( home_url( '/events.ics' ) ); ?>" target="_blank" class="ce-quick-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e( 'ICS Feed', 'club-events' ); ?>
                </a>
                <a href="<?php echo esc_url( home_url( '/events' ) ); ?>" target="_blank" class="ce-quick-btn">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e( 'View Events Page', 'club-events' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ce-settings' ) ); ?>" class="ce-quick-btn">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Settings', 'club-events' ); ?>
                </a>
            </div>
            <div id="ce-sync-result" class="ce-sync-result" hidden></div>
        </div>
    </div>

    <div class="ce-card ce-shortcodes-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Shortcodes & Blocks', 'club-events' ); ?></h2>
        </div>
        <div class="ce-shortcode-grid">
            <div class="ce-shortcode-item">
                <code>[club_events_timeline]</code>
                <p><?php esc_html_e( 'Vertical timeline of upcoming events with month groupings.', 'club-events' ); ?></p>
                <div class="ce-shortcode-attrs">
                    <span><code>limit="20"</code></span>
                    <span><code>category="slug"</code></span>
                    <span><code>show_past="true"</code></span>
                    <span><code>show_filter="true"</code></span>
                </div>
            </div>
            <div class="ce-shortcode-item">
                <code>[club_events_overview]</code>
                <p><?php esc_html_e( 'Monthly calendar grid overview with event dots and list.', 'club-events' ); ?></p>
                <div class="ce-shortcode-attrs">
                    <span><code>category="slug"</code></span>
                    <span><code>show_filter="true"</code></span>
                </div>
            </div>
            <div class="ce-shortcode-item">
                <code>[club_events_list]</code>
                <p><?php esc_html_e( 'Compact upcoming events list for sidebars or widgets.', 'club-events' ); ?></p>
                <div class="ce-shortcode-attrs">
                    <span><code>limit="5"</code></span>
                    <span><code>category="slug"</code></span>
                </div>
            </div>
            <div class="ce-shortcode-item">
                <code>[club_events_subscribe]</code>
                <p><?php esc_html_e( 'Email subscription form with double opt-in confirmation.', 'club-events' ); ?></p>
            </div>
        </div>
    </div>
</div>
