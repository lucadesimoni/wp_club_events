<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ce-admin-wrap">
    <h1 class="ce-page-title">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e( 'Settings', 'club-events' ); ?>
    </h1>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'club-events' ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="ce_save_settings">
        <?php wp_nonce_field( 'ce_save_settings' ); ?>

        <div class="ce-card">
            <div class="ce-card-header"><h2><?php esc_html_e( 'Google Calendar', 'club-events' ); ?></h2></div>

            <div class="ce-form-row">
                <label for="ce_google_api_key">
                    <?php esc_html_e( 'Google API Key', 'club-events' ); ?>
                    <span class="ce-field-hint">
                        <?php esc_html_e( 'Used for all calendars without a per-calendar override. Keep this safe — restrict it in Google Cloud Console.', 'club-events' ); ?>
                    </span>
                </label>
                <input type="text" id="ce_google_api_key" name="ce_google_api_key" class="large-text"
                       value="<?php echo esc_attr( get_option( 'ce_google_api_key', '' ) ); ?>"
                       placeholder="AIza…">
            </div>

            <div class="ce-form-row ce-form-half">
                <div>
                    <label for="ce_sync_interval"><?php esc_html_e( 'Sync Interval', 'club-events' ); ?></label>
                    <select id="ce_sync_interval" name="ce_sync_interval" class="regular-text">
                        <?php
                        $intervals = [
                            'hourly'     => __( 'Hourly', 'club-events' ),
                            'twicedaily' => __( 'Twice Daily', 'club-events' ),
                            'daily'      => __( 'Daily', 'club-events' ),
                        ];
                        $current = get_option( 'ce_sync_interval', 'hourly' );
                        foreach ( $intervals as $val => $label ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $val ),
                                selected( $current, $val, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <?php $next = wp_next_scheduled( 'ce_google_calendar_sync' ); ?>
                    <label><?php esc_html_e( 'Next Scheduled Sync', 'club-events' ); ?></label>
                    <p class="description">
                        <?php echo $next
                            ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) )
                            : esc_html__( 'Not scheduled', 'club-events' ); ?>
                    </p>
                </div>
            </div>

            <div class="ce-form-row ce-form-half">
                <div>
                    <label for="ce_past_months"><?php esc_html_e( 'Sync Past (months)', 'club-events' ); ?></label>
                    <input type="number" id="ce_past_months" name="ce_past_months" min="0" max="12" class="small-text"
                           value="<?php echo esc_attr( get_option( 'ce_past_months', '1' ) ); ?>">
                </div>
                <div>
                    <label for="ce_future_months"><?php esc_html_e( 'Sync Future (months)', 'club-events' ); ?></label>
                    <input type="number" id="ce_future_months" name="ce_future_months" min="1" max="24" class="small-text"
                           value="<?php echo esc_attr( get_option( 'ce_future_months', '6' ) ); ?>">
                </div>
            </div>
        </div>

        <div class="ce-card">
            <div class="ce-card-header"><h2><?php esc_html_e( 'ICS / Calendar Feed', 'club-events' ); ?></h2></div>

            <div class="ce-form-row">
                <label class="ce-checkbox-label">
                    <input type="checkbox" name="ce_ics_feed_enabled" value="1"
                           <?php checked( get_option( 'ce_ics_feed_enabled', '1' ), '1' ); ?>>
                    <?php esc_html_e( 'Enable public ICS feed', 'club-events' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Allows external calendar apps (Google Calendar, Apple Calendar, Outlook) to subscribe.', 'club-events' ); ?>
                </p>
            </div>

            <?php if ( get_option( 'ce_ics_feed_enabled', '1' ) === '1' ) : ?>
            <div class="ce-feed-urls">
                <div class="ce-feed-url-row">
                    <label><?php esc_html_e( 'All Events Feed URL', 'club-events' ); ?></label>
                    <div class="ce-copy-row">
                        <input type="text" readonly value="<?php echo esc_attr( CE_ICS_Export::get_feed_url() ); ?>" class="large-text">
                        <button type="button" class="button ce-copy-btn" data-target="events-ics">
                            <?php esc_html_e( 'Copy', 'club-events' ); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="ce-card">
            <div class="ce-card-header"><h2><?php esc_html_e( 'Email Subscriptions', 'club-events' ); ?></h2></div>

            <div class="ce-form-row">
                <label class="ce-checkbox-label">
                    <input type="checkbox" name="ce_subscription_enabled" value="1"
                           <?php checked( get_option( 'ce_subscription_enabled', '1' ), '1' ); ?>>
                    <?php esc_html_e( 'Enable event subscription system', 'club-events' ); ?>
                </label>
            </div>

            <div class="ce-form-row ce-form-half">
                <div>
                    <label for="ce_subscription_from_name"><?php esc_html_e( 'From Name', 'club-events' ); ?></label>
                    <input type="text" id="ce_subscription_from_name" name="ce_subscription_from_name" class="regular-text"
                           value="<?php echo esc_attr( get_option( 'ce_subscription_from_name', get_bloginfo( 'name' ) ) ); ?>">
                </div>
                <div>
                    <label for="ce_subscription_from_email"><?php esc_html_e( 'From Email', 'club-events' ); ?></label>
                    <input type="email" id="ce_subscription_from_email" name="ce_subscription_from_email" class="regular-text"
                           value="<?php echo esc_attr( get_option( 'ce_subscription_from_email', get_option( 'admin_email' ) ) ); ?>">
                </div>
            </div>

            <div class="ce-form-row">
                <p class="description">
                    <?php esc_html_e( 'Use the shortcode', 'club-events' ); ?>
                    <code>[club_events_subscribe]</code>
                    <?php esc_html_e( 'to add a subscription form to any page.', 'club-events' ); ?>
                </p>
            </div>
        </div>

        <div class="ce-card">
            <div class="ce-card-header"><h2><?php esc_html_e( 'Self-Service / Frontend Submission', 'club-events' ); ?></h2></div>

            <div class="ce-form-row">
                <label class="ce-checkbox-label">
                    <input type="checkbox" name="ce_self_service_enabled" value="1"
                           <?php checked( get_option( 'ce_self_service_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Enable frontend event submission', 'club-events' ); ?>
                </label>
                <p class="description">
                    <?php esc_html_e( 'Allows logged-in users to submit events from the frontend.', 'club-events' ); ?>
                </p>
            </div>

            <div class="ce-form-row ce-form-half">
                <div>
                    <label for="ce_self_service_role"><?php esc_html_e( 'Minimum Role to Submit', 'club-events' ); ?></label>
                    <select id="ce_self_service_role" name="ce_self_service_role" class="regular-text">
                        <?php
                        $roles = [
                            'subscriber'   => __( 'Subscriber', 'club-events' ),
                            'contributor'  => __( 'Contributor', 'club-events' ),
                            'author'       => __( 'Author', 'club-events' ),
                            'editor'       => __( 'Editor', 'club-events' ),
                            'administrator'=> __( 'Administrator', 'club-events' ),
                        ];
                        $current_role = get_option( 'ce_self_service_role', 'subscriber' );
                        foreach ( $roles as $val => $label ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $val ),
                                selected( $current_role, $val, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label for="ce_self_service_auto_publish_role"><?php esc_html_e( 'Auto-Publish Role', 'club-events' ); ?></label>
                    <select id="ce_self_service_auto_publish_role" name="ce_self_service_auto_publish_role" class="regular-text">
                        <?php
                        $current_auto = get_option( 'ce_self_service_auto_publish_role', 'editor' );
                        foreach ( $roles as $val => $label ) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr( $val ),
                                selected( $current_auto, $val, false ),
                                esc_html( $label )
                            );
                        }
                        ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e( 'Users at or above this role will have events published immediately. Lower roles go to "Pending Review".', 'club-events' ); ?>
                    </p>
                </div>
            </div>

            <div class="ce-form-row">
                <p class="description">
                    <?php esc_html_e( 'Shortcodes:', 'club-events' ); ?>
                    <code>[club_events_submit]</code> — <?php esc_html_e( 'submission form', 'club-events' ); ?>,
                    <code>[club_events_my_events]</code> — <?php esc_html_e( 'user\'s event list', 'club-events' ); ?>
                </p>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php esc_html_e( 'Save Settings', 'club-events' ); ?>
            </button>
        </p>
    </form>
</div>
