<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ce-admin-wrap">
    <h1 class="ce-page-title">
        <span class="dashicons dashicons-google"></span>
        <?php esc_html_e( 'Google Calendars', 'club-events' ); ?>
    </h1>

    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Connected Calendars', 'club-events' ); ?></h2>
            <button class="button button-primary" id="ce-add-calendar-btn">
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
                            <?php esc_html_e( 'Leave blank to use the global API key from Settings. Useful if this calendar uses a different project.', 'club-events' ); ?>
                        </span>
                    </label>
                    <input type="text" id="ce-cal-api-key" name="api_key" class="large-text"
                           placeholder="<?php esc_attr_e( 'AIza…', 'club-events' ); ?>">
                </div>
                <?php if ( ! empty( $event_types ) ) : ?>
                <div class="ce-form-row">
                    <label><?php esc_html_e( 'Event Types', 'club-events' ); ?>
                        <span class="ce-field-hint">
                            <?php esc_html_e( 'Events imported from this calendar are automatically assigned these types.', 'club-events' ); ?>
                        </span>
                    </label>
                    <div class="ce-checkbox-group" id="ce-cal-event-types">
                        <?php foreach ( $event_types as $et ) : ?>
                        <label class="ce-checkbox-label">
                            <input type="checkbox" name="event_types[]" value="<?php echo esc_attr( $et->slug ); ?>">
                            <?php echo esc_html( $et->name ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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
                    <td><code><?php echo esc_html( $cal->calendar_id ); ?></code></td>
                    <td>
                        <?php
                        if ( ! empty( $cal->event_types ) ) {
                            $type_slugs = array_filter( array_map( 'trim', explode( ',', $cal->event_types ) ) );
                            foreach ( $type_slugs as $slug ) {
                                $term = get_term_by( 'slug', $slug, 'event_type' );
                                $label = $term ? $term->name : $slug;
                                echo '<span class="ce-badge ce-badge--blue">' . esc_html( $label ) . '</span> ';
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
                        <button class="button button-small ce-delete-cal-btn" data-id="<?php echo esc_attr( $cal->id ); ?>">
                            <?php esc_html_e( 'Delete', 'club-events' ); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="ce-card">
        <div class="ce-card-header">
            <h2><?php esc_html_e( 'Manual Sync', 'club-events' ); ?></h2>
        </div>
        <p><?php esc_html_e( 'Sync all enabled calendars now. Events are also synced automatically on the schedule configured in Settings.', 'club-events' ); ?></p>
        <button class="button button-primary" id="ce-sync-btn">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e( 'Sync All Calendars Now', 'club-events' ); ?>
        </button>
        <div id="ce-sync-result" class="ce-sync-result" hidden></div>
    </div>

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
                <p><?php esc_html_e( 'In Credentials, create an API key. Restrict it to the Calendar API and your domain for security.', 'club-events' ); ?></p>
            </li>
            <li>
                <strong><?php esc_html_e( 'Make your calendar public', 'club-events' ); ?></strong>
                <p><?php esc_html_e( 'In Google Calendar Settings → Access permissions, enable "Make available to public".', 'club-events' ); ?></p>
            </li>
            <li>
                <strong><?php esc_html_e( 'Add your API key in Settings', 'club-events' ); ?></strong>
                <p>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ce-settings' ) ); ?>">
                        <?php esc_html_e( 'Go to Settings →', 'club-events' ); ?>
                    </a>
                </p>
            </li>
            <li>
                <strong><?php esc_html_e( 'Add your calendar ID above and click Sync', 'club-events' ); ?></strong>
                <p><?php esc_html_e( 'The Calendar ID is found in Google Calendar → Settings → Integrate calendar.', 'club-events' ); ?></p>
            </li>
        </ol>
    </div>
</div>
