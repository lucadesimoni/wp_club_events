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
        <div class="ce-stat-card">
            <div class="ce-stat-icon ce-stat-icon--orange">
                <span class="dashicons dashicons-tag"></span>
            </div>
            <div class="ce-stat-body">
                <div class="ce-stat-value"><?php echo esc_html( $type_count ); ?></div>
                <div class="ce-stat-label"><?php esc_html_e( 'Event Types', 'club-events' ); ?></div>
            </div>
        </div>
    </div>

    <!-- ── Upcoming Events — switchable views ───────────────────── -->
    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Upcoming Events', 'club-events' ); ?>
                <span class="ce-count-badge"><?php echo esc_html( count( $upcoming ) ); ?></span>
            </h2>
            <div class="ce-view-toggle">
                <button class="ce-view-toggle-btn active" data-view="tiles" title="<?php esc_attr_e( 'Tiles', 'club-events' ); ?>">
                    <span class="dashicons dashicons-grid-view"></span>
                </button>
                <button class="ce-view-toggle-btn" data-view="table" title="<?php esc_attr_e( 'Table', 'club-events' ); ?>">
                    <span class="dashicons dashicons-list-view"></span>
                </button>
                <button class="ce-view-toggle-btn" data-view="timeline" title="<?php esc_attr_e( 'Timeline', 'club-events' ); ?>">
                    <span class="dashicons dashicons-editor-ul"></span>
                </button>
            </div>
        </div>

        <?php if ( empty( $upcoming ) ) : ?>
        <div class="ce-empty-state ce-empty-state--centered">
            <span class="dashicons dashicons-calendar-alt ce-empty-icon"></span>
            <p><?php esc_html_e( 'No upcoming events. Create your first event!', 'club-events' ); ?></p>
            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=club_event' ) ); ?>" class="button button-primary">
                <?php esc_html_e( 'Add Event', 'club-events' ); ?>
            </a>
        </div>
        <?php else : ?>

        <!-- ── TILES VIEW ──────────────────────────────────────── -->
        <div class="ce-dash-view ce-dash-tiles" data-view="tiles">
            <div class="ce-tiles-grid">
                <?php foreach ( $upcoming as $post ) :
                    $start    = get_post_meta( $post->ID, '_ce_start_date', true );
                    $end      = get_post_meta( $post->ID, '_ce_end_date', true );
                    $color    = get_post_meta( $post->ID, '_ce_color', true ) ?: '#3b82f6';
                    $location = get_post_meta( $post->ID, '_ce_location', true );
                    $all_day  = get_post_meta( $post->ID, '_ce_all_day', true );
                    $source   = get_post_meta( $post->ID, '_ce_source', true ) ?: 'manual';
                    $types    = wp_get_post_terms( $post->ID, 'event_type', [ 'fields' => 'names' ] );
                    if ( is_wp_error( $types ) ) $types = [];
                ?>
                <div class="ce-tile" style="--tile-color:<?php echo esc_attr( $color ); ?>">
                    <div class="ce-tile-date">
                        <?php if ( $start ) : ?>
                        <span class="ce-tile-day"><?php echo esc_html( date_i18n( 'd', strtotime( $start ) ) ); ?></span>
                        <span class="ce-tile-month"><?php echo esc_html( date_i18n( 'M', strtotime( $start ) ) ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ce-tile-body">
                        <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="ce-tile-title">
                            <?php echo esc_html( $post->post_title ); ?>
                        </a>
                        <?php if ( $start ) : ?>
                        <span class="ce-tile-meta">
                            <span class="dashicons dashicons-clock"></span>
                            <?php
                            if ( $all_day ) {
                                esc_html_e( 'All day', 'club-events' );
                            } else {
                                echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $start ) ) );
                                if ( $end ) {
                                    echo ' – ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $end ) ) );
                                }
                            }
                            ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( $location ) : ?>
                        <span class="ce-tile-meta">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html( $location ); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ( ! empty( $types ) ) : ?>
                        <div class="ce-tile-types">
                            <?php foreach ( array_slice( $types, 0, 2 ) as $type_name ) : ?>
                            <span class="ce-tile-type-badge"><?php echo esc_html( $type_name ); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="ce-tile-source">
                        <?php if ( 'google' === $source ) : ?>
                        <span class="ce-tile-source-dot ce-tile-source--google" title="Google Calendar"></span>
                        <?php else : ?>
                        <span class="ce-tile-source-dot ce-tile-source--manual" title="Manual"></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── TABLE VIEW ──────────────────────────────────────── -->
        <div class="ce-dash-view ce-dash-table" data-view="table" hidden>
            <table class="wp-list-table widefat fixed striped ce-table">
                <thead>
                    <tr>
                        <th class="ce-th-date"><?php esc_html_e( 'Date', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Event', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Time', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Location', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Source', 'club-events' ); ?></th>
                        <th class="ce-th-actions"><?php esc_html_e( 'Actions', 'club-events' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $upcoming as $post ) :
                        $start    = get_post_meta( $post->ID, '_ce_start_date', true );
                        $end      = get_post_meta( $post->ID, '_ce_end_date', true );
                        $color    = get_post_meta( $post->ID, '_ce_color', true ) ?: '#3b82f6';
                        $location = get_post_meta( $post->ID, '_ce_location', true );
                        $all_day  = get_post_meta( $post->ID, '_ce_all_day', true );
                        $source   = get_post_meta( $post->ID, '_ce_source', true ) ?: 'manual';
                        $types    = wp_get_post_terms( $post->ID, 'event_type', [ 'fields' => 'names' ] );
                        if ( is_wp_error( $types ) ) $types = [];
                    ?>
                    <tr>
                        <td>
                            <span class="ce-color-swatch" style="background:<?php echo esc_attr( $color ); ?>"></span>
                            <?php echo $start ? esc_html( date_i18n( 'D, d M Y', strtotime( $start ) ) ) : '—'; ?>
                        </td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
                                    <?php echo esc_html( $post->post_title ); ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <?php
                            if ( $all_day ) {
                                esc_html_e( 'All day', 'club-events' );
                            } elseif ( $start ) {
                                echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $start ) ) );
                                if ( $end ) {
                                    echo ' – ' . esc_html( date_i18n( get_option( 'time_format' ), strtotime( $end ) ) );
                                }
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html( $location ?: '—' ); ?></td>
                        <td>
                            <?php if ( ! empty( $types ) ) : ?>
                                <?php foreach ( $types as $tn ) : ?>
                                <span class="ce-badge ce-badge--blue"><?php echo esc_html( $tn ); ?></span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span class="ce-hint">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( 'google' === $source ) : ?>
                            <span class="ce-badge ce-badge--blue"><?php esc_html_e( 'Google', 'club-events' ); ?></span>
                            <?php else : ?>
                            <span class="ce-badge ce-badge--gray"><?php esc_html_e( 'Manual', 'club-events' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="ce-row-actions">
                            <a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Edit', 'club-events' ); ?>
                            </a>
                            <a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank" class="button button-small">
                                <?php esc_html_e( 'View', 'club-events' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ── TIMELINE VIEW ───────────────────────────────────── -->
        <div class="ce-dash-view ce-dash-timeline" data-view="timeline" hidden>
            <ul class="ce-upcoming-list">
                <?php foreach ( $upcoming as $post ) :
                    $start    = get_post_meta( $post->ID, '_ce_start_date', true );
                    $color    = get_post_meta( $post->ID, '_ce_color', true ) ?: '#3b82f6';
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
        </div>

        <div class="ce-card-footer-link">
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=club_event' ) ); ?>">
                <?php esc_html_e( 'View all events', 'club-events' ); ?> →
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Quick Actions + Shortcodes side-by-side ──────────── -->
    <div class="ce-dashboard-grid">
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

        <div class="ce-card ce-shortcodes-card">
            <div class="ce-card-header">
                <h2><?php esc_html_e( 'Shortcodes & Blocks', 'club-events' ); ?></h2>
            </div>
            <div class="ce-shortcode-list">
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_timeline]</code>
                    <span><?php esc_html_e( 'Vertical timeline with month groups', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_overview]</code>
                    <span><?php esc_html_e( 'Monthly calendar grid + event list', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_cards]</code>
                    <span><?php esc_html_e( 'Responsive card grid', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_tiles]</code>
                    <span><?php esc_html_e( 'Tiles preview, filterable by type', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_yearly]</code>
                    <span><?php esc_html_e( 'Full-year agenda by month', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_list]</code>
                    <span><?php esc_html_e( 'Compact list for sidebars', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_subscribe]</code>
                    <span><?php esc_html_e( 'Email subscription form', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_submit]</code>
                    <span><?php esc_html_e( 'Frontend event submission', 'club-events' ); ?></span>
                </div>
                <div class="ce-shortcode-item-compact">
                    <code>[club_events_my_events]</code>
                    <span><?php esc_html_e( 'User event dashboard', 'club-events' ); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
