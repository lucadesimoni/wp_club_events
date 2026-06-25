<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ce-admin-wrap">
    <h1 class="ce-page-title">
        <span class="dashicons dashicons-google"></span>
        <?php esc_html_e( 'Google Calendars', 'club-events' ); ?>
    </h1>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'club-events' ); ?></p></div>
    <?php endif; ?>

    <!-- ── Event Types ──────────────────────────────────────────────── -->
    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Event Types', 'club-events' ); ?></h2>
            <button class="button button-primary ce-btn-sm" id="ce-add-type-btn">
                + <?php esc_html_e( 'Add Type', 'club-events' ); ?>
            </button>
        </div>

        <div id="ce-type-form-wrap" class="ce-form-panel" hidden>
            <h3 id="ce-type-form-title"><?php esc_html_e( 'Add Event Type', 'club-events' ); ?></h3>
            <form id="ce-type-form" class="ce-inline-form">
                <input type="hidden" id="ce-type-term-id" name="term_id" value="">
                <div class="ce-form-row ce-form-half">
                    <div>
                        <label for="ce-type-name"><?php esc_html_e( 'Name', 'club-events' ); ?> <span class="required">*</span></label>
                        <input type="text" id="ce-type-name" name="name" class="regular-text" required
                               placeholder="<?php esc_attr_e( 'e.g. Training, Meeting, Tournament', 'club-events' ); ?>">
                    </div>
                    <div>
                        <label for="ce-type-color"><?php esc_html_e( 'Color', 'club-events' ); ?></label>
                        <input type="color" id="ce-type-color" name="color" value="#3b82f6">
                    </div>
                </div>
                <div class="ce-form-actions">
                    <button type="submit" class="button button-primary" id="ce-type-save-btn">
                        <?php esc_html_e( 'Save Type', 'club-events' ); ?>
                    </button>
                    <button type="button" class="button" id="ce-type-cancel-btn">
                        <?php esc_html_e( 'Cancel', 'club-events' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ( empty( $event_types ) ) : ?>
        <div class="ce-empty-state ce-empty-state--centered" id="ce-types-empty">
            <span class="dashicons dashicons-tag ce-empty-icon"></span>
            <p><?php esc_html_e( 'No event types created yet.', 'club-events' ); ?></p>
            <p class="ce-hint"><?php esc_html_e( 'Create event types to categorize and color-code your calendar events.', 'club-events' ); ?></p>
        </div>
        <?php endif; ?>

        <div class="ce-type-chips" id="ce-type-chips" <?php echo empty( $event_types ) ? 'hidden' : ''; ?>>
            <?php foreach ( $event_types as $et ) : ?>
            <div class="ce-type-chip" data-term-id="<?php echo esc_attr( $et->term_id ); ?>"
                 data-name="<?php echo esc_attr( $et->name ); ?>"
                 data-slug="<?php echo esc_attr( $et->slug ); ?>"
                 data-color="<?php echo esc_attr( $et->color ); ?>">
                <span class="ce-type-dot" style="background:<?php echo esc_attr( $et->color ); ?>"></span>
                <span class="ce-type-name"><?php echo esc_html( $et->name ); ?></span>
                <span class="ce-type-count"><?php echo esc_html( $et->count ); ?></span>
                <button type="button" class="ce-type-edit" title="<?php esc_attr_e( 'Edit', 'club-events' ); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </button>
                <button type="button" class="ce-type-delete" title="<?php esc_attr_e( 'Delete', 'club-events' ); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Connected Calendars ──────────────────────────────────────── -->
    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Connected Calendars', 'club-events' ); ?></h2>
            <button class="button button-primary ce-btn-sm" id="ce-add-calendar-btn">
                + <?php esc_html_e( 'Add Calendar', 'club-events' ); ?>
            </button>
        </div>

        <div id="ce-calendar-form-wrap" class="ce-form-panel" hidden>
            <h3 id="ce-cal-form-title"><?php esc_html_e( 'Add Calendar', 'club-events' ); ?></h3>
            <form id="ce-calendar-form" class="ce-inline-form">
                <input type="hidden" id="ce-cal-id" name="id" value="">
                <div class="ce-form-row ce-form-half">
                    <div>
                        <label for="ce-cal-name"><?php esc_html_e( 'Display Name', 'club-events' ); ?> <span class="required">*</span></label>
                        <input type="text" id="ce-cal-name" name="name" class="regular-text" required
                               placeholder="<?php esc_attr_e( 'e.g. Club Main Calendar', 'club-events' ); ?>">
                    </div>
                    <div>
                        <label for="ce-cal-color"><?php esc_html_e( 'Color', 'club-events' ); ?></label>
                        <input type="color" id="ce-cal-color" name="color" value="#3b82f6">
                    </div>
                </div>
                <div class="ce-form-row">
                    <label for="ce-cal-calendar-id">
                        <?php esc_html_e( 'Google Calendar ID', 'club-events' ); ?> <span class="required">*</span>
                        <span class="ce-field-hint">
                            <?php esc_html_e( 'Found in Google Calendar Settings → Integrate calendar. Looks like: abc123@group.calendar.google.com', 'club-events' ); ?>
                        </span>
                    </label>
                    <input type="text" id="ce-cal-calendar-id" name="calendar_id" class="large-text" required
                           placeholder="abc123@group.calendar.google.com">
                </div>
                <div class="ce-form-row">
                    <label for="ce-cal-api-key">
                        <?php esc_html_e( 'API Key (optional override)', 'club-events' ); ?>
                        <span class="ce-field-hint">
                            <?php esc_html_e( 'Leave blank to use the global API key. Useful if this calendar uses a different project.', 'club-events' ); ?>
                        </span>
                    </label>
                    <input type="text" id="ce-cal-api-key" name="api_key" class="large-text"
                           placeholder="<?php esc_attr_e( 'AIza…', 'club-events' ); ?>">
                </div>
                <div class="ce-form-row">
                    <label><?php esc_html_e( 'Assign Event Types', 'club-events' ); ?>
                        <span class="ce-field-hint">
                            <?php esc_html_e( 'Imported events from this calendar are automatically tagged with the selected types.', 'club-events' ); ?>
                        </span>
                    </label>
                    <div class="ce-checkbox-group" id="ce-cal-event-types">
                        <?php if ( ! empty( $event_types ) ) : ?>
                            <?php foreach ( $event_types as $et ) : ?>
                            <label class="ce-checkbox-label ce-checkbox-chip" style="--chip-color:<?php echo esc_attr( $et->color ); ?>">
                                <input type="checkbox" name="event_types[]" value="<?php echo esc_attr( $et->slug ); ?>">
                                <span class="ce-type-dot" style="background:<?php echo esc_attr( $et->color ); ?>"></span>
                                <?php echo esc_html( $et->name ); ?>
                            </label>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <span class="ce-hint" id="ce-no-types-hint"><?php esc_html_e( 'No event types yet — create one above first.', 'club-events' ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ce-form-row">
                    <label class="ce-checkbox-label">
                        <input type="checkbox" id="ce-cal-sync-enabled" name="sync_enabled" value="1" checked>
                        <?php esc_html_e( 'Enable automatic sync', 'club-events' ); ?>
                    </label>
                </div>
                <div class="ce-form-actions">
                    <button type="submit" class="button button-primary" id="ce-cal-save-btn">
                        <?php esc_html_e( 'Save Calendar', 'club-events' ); ?>
                    </button>
                    <button type="button" class="button" id="ce-cal-cancel-btn">
                        <?php esc_html_e( 'Cancel', 'club-events' ); ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ( empty( $calendars ) ) : ?>
        <div class="ce-empty-state ce-empty-state--centered">
            <span class="dashicons dashicons-google ce-empty-icon"></span>
            <p><?php esc_html_e( 'No calendars connected yet.', 'club-events' ); ?></p>
            <p class="ce-hint"><?php esc_html_e( 'Click "Add Calendar" to connect your first Google Calendar.', 'club-events' ); ?></p>
        </div>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped ce-table" id="ce-calendars-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Calendar', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Calendar ID', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Event Types', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Sync', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Last Sync', 'club-events' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'club-events' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $calendars as $cal ) : ?>
                <tr data-id="<?php echo esc_attr( $cal->id ); ?>">
                    <td>
                        <span class="ce-color-swatch" style="background:<?php echo esc_attr( $cal->color ); ?>"></span>
                        <strong><?php echo esc_html( $cal->name ); ?></strong>
                    </td>
                    <td><code class="ce-cal-id-code"><?php echo esc_html( $cal->calendar_id ); ?></code></td>
                    <td>
                        <?php
                        if ( ! empty( $cal->event_types ) ) {
                            $type_slugs = array_filter( array_map( 'trim', explode( ',', $cal->event_types ) ) );
                            foreach ( $type_slugs as $slug ) {
                                $term  = get_term_by( 'slug', $slug, 'event_type' );
                                $label = $term ? $term->name : $slug;
                                $color = $term ? ( get_term_meta( $term->term_id, '_ce_color', true ) ?: '#3b82f6' ) : '#3b82f6';
                                echo '<span class="ce-badge ce-badge--type" style="--badge-color:' . esc_attr( $color ) . '">' . esc_html( $label ) . '</span> ';
                            }
                        } else {
                            echo '<span class="ce-hint">—</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <?php if ( $cal->sync_enabled ) : ?>
                        <span class="ce-badge ce-badge--green"><?php esc_html_e( 'Enabled', 'club-events' ); ?></span>
                        <?php else : ?>
                        <span class="ce-badge ce-badge--gray"><?php esc_html_e( 'Disabled', 'club-events' ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        if ( $cal->last_sync ) {
                            echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $cal->last_sync ) ) );
                        } else {
                            echo '<span class="ce-hint">' . esc_html__( 'Never', 'club-events' ) . '</span>';
                        }
                        ?>
                    </td>
                    <td class="ce-row-actions">
                        <button class="button button-small ce-edit-cal-btn"
                                data-id="<?php echo esc_attr( $cal->id ); ?>"
                                data-name="<?php echo esc_attr( $cal->name ); ?>"
                                data-calendar-id="<?php echo esc_attr( $cal->calendar_id ); ?>"
                                data-api-key="<?php echo esc_attr( $cal->api_key ); ?>"
                                data-color="<?php echo esc_attr( $cal->color ); ?>"
                                data-event-types="<?php echo esc_attr( $cal->event_types ?? '' ); ?>"
                                data-sync-enabled="<?php echo esc_attr( $cal->sync_enabled ); ?>">
                            <?php esc_html_e( 'Edit', 'club-events' ); ?>
                        </button>
                        <button class="button button-small button-link-delete ce-delete-cal-btn" data-id="<?php echo esc_attr( $cal->id ); ?>">
                            <?php esc_html_e( 'Delete', 'club-events' ); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── Sync Settings ────────────────────────────────────────────── -->
    <div class="ce-settings-grid">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="ce_save_calendar_settings">
            <?php wp_nonce_field( 'ce_save_calendar_settings' ); ?>

            <div class="ce-card">
                <div class="ce-card-header"><h2><?php esc_html_e( 'Sync Settings', 'club-events' ); ?></h2></div>

                <div class="ce-form-row">
                    <label for="ce_google_api_key">
                        <?php esc_html_e( 'Google API Key', 'club-events' ); ?>
                        <span class="ce-field-hint">
                            <?php esc_html_e( 'Used for all calendars unless overridden per-calendar. Restrict it in Google Cloud Console.', 'club-events' ); ?>
                        </span>
                    </label>
                    <input type="text" id="ce_google_api_key" name="ce_google_api_key" class="large-text"
                           value="<?php echo esc_attr( get_option( 'ce_google_api_key', '' ) ); ?>"
                           placeholder="AIza…">
                </div>

                <div class="ce-form-row ce-form-third">
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
                        <label for="ce_past_months"><?php esc_html_e( 'Past (months)', 'club-events' ); ?></label>
                        <input type="number" id="ce_past_months" name="ce_past_months" min="0" max="12" class="small-text"
                               value="<?php echo esc_attr( get_option( 'ce_past_months', '1' ) ); ?>">
                    </div>
                    <div>
                        <label for="ce_future_months"><?php esc_html_e( 'Future (months)', 'club-events' ); ?></label>
                        <input type="number" id="ce_future_months" name="ce_future_months" min="1" max="24" class="small-text"
                               value="<?php echo esc_attr( get_option( 'ce_future_months', '6' ) ); ?>">
                    </div>
                </div>

                <?php $next = wp_next_scheduled( 'ce_google_calendar_sync' ); ?>
                <?php if ( $next ) : ?>
                <div class="ce-form-row">
                    <p class="ce-next-sync">
                        <span class="dashicons dashicons-clock"></span>
                        <?php printf( esc_html__( 'Next sync: %s', 'club-events' ), esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) ) ); ?>
                    </p>
                </div>
                <?php endif; ?>

                <div class="ce-form-row ce-form-actions-row">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e( 'Save Settings', 'club-events' ); ?>
                    </button>
                    <button type="button" class="button" id="ce-sync-btn">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e( 'Sync All Now', 'club-events' ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="ce-sync-result" class="ce-sync-result" hidden></div>

    <!-- ── Setup Guide ──────────────────────────────────────────────── -->
    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Setup Guide', 'club-events' ); ?></h2>
        </div>
        <ol class="ce-setup-steps">
            <li>
                <strong><?php esc_html_e( 'Create a Google Cloud project', 'club-events' ); ?></strong>
                <p><?php esc_html_e( 'Go to console.cloud.google.com, create a project, and enable the Google Calendar API.', 'club-events' ); ?></p>
            </li>
            <li>
                <strong><?php esc_html_e( 'Create an API key', 'club-events' ); ?></strong>
                <p><?php esc_html_e( 'In Credentials, create an API key. Restrict it to the Calendar API and your domain.', 'club-events' ); ?></p>
            </li>
            <li>
                <strong><?php esc_html_e( 'Make your calendar public', 'club-events' ); ?></strong>
                <p><?php esc_html_e( 'In Google Calendar Settings → Access permissions, enable "Make available to public".', 'club-events' ); ?></p>
            </li>
            <li>
                <strong><?php esc_html_e( 'Add your API key above and connect calendars', 'club-events' ); ?></strong>
                <p><?php esc_html_e( 'Enter the global API key in Sync Settings, then add each calendar with its Calendar ID.', 'club-events' ); ?></p>
            </li>
        </ol>
    </div>
</div>
