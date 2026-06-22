<?php
defined( 'ABSPATH' ) || exit;

class CE_Frontend_Submit {

    public function __construct() {
        add_shortcode( 'club_events_submit', [ $this, 'render_form' ] );
        add_shortcode( 'club_events_my_events', [ $this, 'render_my_events' ] );
        add_action( 'wp_ajax_ce_submit_event', [ $this, 'handle_submit' ] );
        add_action( 'wp_ajax_ce_delete_my_event', [ $this, 'handle_delete' ] );
    }

    public static function render_form_static( $atts = [] ) {
        return do_shortcode( '[club_events_submit]' );
    }

    public static function render_my_events_static( $atts = [] ) {
        return do_shortcode( '[club_events_my_events]' );
    }

    /* ─── Submit Form Shortcode ────────────────────────────────────────── */
    public function render_form( $atts = [] ) {
        if ( get_option( 'ce_self_service_enabled', '0' ) !== '1' ) {
            return '';
        }

        if ( ! is_user_logged_in() ) {
            return '<div class="ce-submit-notice">'
                . '<p>' . sprintf(
                    esc_html__( 'Please %s to submit events.', 'club-events' ),
                    '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'log in', 'club-events' ) . '</a>'
                ) . '</p></div>';
        }

        $min_role = get_option( 'ce_self_service_role', 'subscriber' );
        if ( ! $this->user_has_min_role( $min_role ) ) {
            return '<div class="ce-submit-notice"><p>'
                . esc_html__( 'You do not have permission to submit events.', 'club-events' )
                . '</p></div>';
        }

        $categories  = get_terms( [ 'taxonomy' => 'event_category', 'hide_empty' => false ] );
        $event_types = get_terms( [ 'taxonomy' => 'event_type', 'hide_empty' => false ] );

        ob_start();
        ?>
        <div class="ce-submit-wrap">
            <form class="ce-submit-form" id="ce-submit-form">
                <?php wp_nonce_field( 'ce_submit_event', 'ce_submit_nonce' ); ?>

                <div class="ce-form-row">
                    <label for="ce-submit-title"><?php esc_html_e( 'Event Title', 'club-events' ); ?> <span class="required">*</span></label>
                    <input type="text" id="ce-submit-title" name="title" required maxlength="200">
                </div>

                <div class="ce-form-row ce-form-half">
                    <div>
                        <label for="ce-submit-start"><?php esc_html_e( 'Start', 'club-events' ); ?> <span class="required">*</span></label>
                        <input type="datetime-local" id="ce-submit-start" name="start_date" required>
                    </div>
                    <div>
                        <label for="ce-submit-end"><?php esc_html_e( 'End', 'club-events' ); ?></label>
                        <input type="datetime-local" id="ce-submit-end" name="end_date">
                    </div>
                </div>

                <div class="ce-form-row">
                    <label class="ce-checkbox-label">
                        <input type="checkbox" name="all_day" id="ce-submit-allday" value="1">
                        <?php esc_html_e( 'All-day event', 'club-events' ); ?>
                    </label>
                </div>

                <div class="ce-form-row">
                    <label for="ce-submit-location"><?php esc_html_e( 'Location', 'club-events' ); ?></label>
                    <input type="text" id="ce-submit-location" name="location" maxlength="300">
                </div>

                <div class="ce-form-row">
                    <label for="ce-submit-description"><?php esc_html_e( 'Description', 'club-events' ); ?></label>
                    <textarea id="ce-submit-description" name="description" rows="4" maxlength="2000"></textarea>
                </div>

                <div class="ce-form-row ce-form-half">
                    <?php if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) : ?>
                    <div>
                        <label for="ce-submit-category"><?php esc_html_e( 'Category', 'club-events' ); ?></label>
                        <select id="ce-submit-category" name="category">
                            <option value=""><?php esc_html_e( '— Select —', 'club-events' ); ?></option>
                            <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <?php if ( ! is_wp_error( $event_types ) && ! empty( $event_types ) ) : ?>
                    <div>
                        <label for="ce-submit-type"><?php esc_html_e( 'Event Type', 'club-events' ); ?></label>
                        <select id="ce-submit-type" name="event_type">
                            <option value=""><?php esc_html_e( '— Select —', 'club-events' ); ?></option>
                            <?php foreach ( $event_types as $type ) : ?>
                            <option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ce-form-row">
                    <label for="ce-submit-color"><?php esc_html_e( 'Color', 'club-events' ); ?></label>
                    <input type="color" id="ce-submit-color" name="color" value="#3b82f6">
                </div>

                <div class="ce-form-row">
                    <button type="submit" class="ce-btn ce-btn-primary" id="ce-submit-btn">
                        <?php esc_html_e( 'Submit Event', 'club-events' ); ?>
                    </button>
                </div>

                <div id="ce-submit-msg" class="ce-form-msg" hidden></div>
            </form>
        </div>
        <script>
        (function(){
            var form = document.getElementById('ce-submit-form');
            if (!form) return;

            var allDay = document.getElementById('ce-submit-allday');
            if (allDay) {
                allDay.addEventListener('change', function() {
                    var startInput = document.getElementById('ce-submit-start');
                    var endInput = document.getElementById('ce-submit-end');
                    startInput.type = this.checked ? 'date' : 'datetime-local';
                    endInput.type = this.checked ? 'date' : 'datetime-local';
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn = document.getElementById('ce-submit-btn');
                var msg = document.getElementById('ce-submit-msg');
                btn.disabled = true;
                btn.textContent = '<?php echo esc_js( __( 'Submitting…', 'club-events' ) ); ?>';

                var data = new FormData(form);
                data.append('action', 'ce_submit_event');

                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST', body: data
                }).then(function(r){ return r.json(); }).then(function(res) {
                    msg.hidden = false;
                    msg.className = 'ce-form-msg ' + (res.success ? 'ce-form-msg--success' : 'ce-form-msg--error');
                    msg.textContent = res.data;
                    if (res.success) {
                        form.reset();
                        document.getElementById('ce-submit-color').value = '#3b82f6';
                    }
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js( __( 'Submit Event', 'club-events' ) ); ?>';
                }).catch(function() {
                    msg.hidden = false;
                    msg.className = 'ce-form-msg ce-form-msg--error';
                    msg.textContent = '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'club-events' ) ); ?>';
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js( __( 'Submit Event', 'club-events' ) ); ?>';
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /* ─── My Events Shortcode ──────────────────────────────────────────── */
    public function render_my_events( $atts = [] ) {
        if ( get_option( 'ce_self_service_enabled', '0' ) !== '1' ) {
            return '';
        }

        if ( ! is_user_logged_in() ) {
            return '<div class="ce-submit-notice"><p>'
                . sprintf(
                    esc_html__( 'Please %s to view your events.', 'club-events' ),
                    '<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'log in', 'club-events' ) . '</a>'
                ) . '</p></div>';
        }

        $user_id = get_current_user_id();
        $posts   = get_posts( [
            'post_type'      => 'club_event',
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'author'         => $user_id,
            'posts_per_page' => 50,
            'meta_key'       => '_ce_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
        ] );

        ob_start();
        ?>
        <div class="ce-my-events">
            <?php if ( empty( $posts ) ) : ?>
            <p class="ce-empty"><?php esc_html_e( 'You have not submitted any events yet.', 'club-events' ); ?></p>
            <?php else : ?>
            <table class="ce-my-events-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Event', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'club-events' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'club-events' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $posts as $post ) :
                    $ev     = CE_CPT::format_event( $post->ID );
                    $status = $post->post_status;
                    $badge  = 'publish' === $status ? 'ce-badge--green' : ( 'pending' === $status ? 'ce-badge--yellow' : 'ce-badge--gray' );
                    $labels = [ 'publish' => __( 'Published', 'club-events' ), 'pending' => __( 'Pending', 'club-events' ), 'draft' => __( 'Draft', 'club-events' ) ];
                ?>
                <tr data-id="<?php echo esc_attr( $post->ID ); ?>">
                    <td>
                        <strong>
                            <?php if ( 'publish' === $status ) : ?>
                            <a href="<?php echo esc_url( $ev['url'] ); ?>"><?php echo esc_html( $ev['title'] ); ?></a>
                            <?php else : ?>
                            <?php echo esc_html( $ev['title'] ); ?>
                            <?php endif; ?>
                        </strong>
                        <?php if ( $ev['location'] ) : ?>
                        <br><small><?php echo esc_html( $ev['location'] ); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html( $ev['start'] ? date_i18n( get_option( 'date_format' ), strtotime( $ev['start'] ) ) : '—' ); ?>
                    </td>
                    <td><span class="ce-badge <?php echo esc_attr( $badge ); ?>"><?php echo esc_html( $labels[ $status ] ?? $status ); ?></span></td>
                    <td>
                        <?php if ( 'publish' !== $status ) : ?>
                        <button type="button" class="ce-btn ce-btn-sm ce-btn-outline ce-delete-my-event" data-id="<?php echo esc_attr( $post->ID ); ?>">
                            <?php esc_html_e( 'Delete', 'club-events' ); ?>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <script>
        (function(){
            document.addEventListener('click', function(e) {
                var btn = e.target.closest('.ce-delete-my-event');
                if (!btn) return;
                if (!confirm('<?php echo esc_js( __( 'Delete this event?', 'club-events' ) ); ?>')) return;
                var row = btn.closest('tr');
                var data = new FormData();
                data.append('action', 'ce_delete_my_event');
                data.append('id', btn.dataset.id);
                data.append('nonce', '<?php echo esc_js( wp_create_nonce( 'ce_delete_my_event' ) ); ?>');
                fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    method: 'POST', body: data
                }).then(function(r){ return r.json(); }).then(function(res) {
                    if (res.success && row) {
                        row.style.opacity = '0';
                        row.style.transition = 'opacity .3s';
                        setTimeout(function(){ row.remove(); }, 300);
                    }
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /* ─── AJAX: Submit Event ───────────────────────────────────────────── */
    public function handle_submit() {
        check_ajax_referer( 'ce_submit_event', 'ce_submit_nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'You must be logged in.', 'club-events' ) );
        }

        if ( get_option( 'ce_self_service_enabled', '0' ) !== '1' ) {
            wp_send_json_error( __( 'Event submissions are not enabled.', 'club-events' ) );
        }

        $min_role = get_option( 'ce_self_service_role', 'subscriber' );
        if ( ! $this->user_has_min_role( $min_role ) ) {
            wp_send_json_error( __( 'You do not have permission to submit events.', 'club-events' ) );
        }

        $title = sanitize_text_field( $_POST['title'] ?? '' );
        if ( empty( $title ) ) {
            wp_send_json_error( __( 'Please enter an event title.', 'club-events' ) );
        }

        $start = sanitize_text_field( $_POST['start_date'] ?? '' );
        if ( empty( $start ) ) {
            wp_send_json_error( __( 'Please enter a start date.', 'club-events' ) );
        }

        $all_day     = ! empty( $_POST['all_day'] );
        $end         = sanitize_text_field( $_POST['end_date'] ?? '' );
        $location    = sanitize_text_field( $_POST['location'] ?? '' );
        $description = sanitize_textarea_field( $_POST['description'] ?? '' );
        $color       = sanitize_hex_color( $_POST['color'] ?? '' ) ?: '#3b82f6';
        $category    = sanitize_text_field( $_POST['category'] ?? '' );
        $event_type  = sanitize_text_field( $_POST['event_type'] ?? '' );

        if ( $all_day ) {
            $start_date = date( 'Y-m-d', strtotime( $start ) ) . ' 00:00:00';
            $end_date   = $end ? date( 'Y-m-d', strtotime( $end ) ) . ' 23:59:59' : date( 'Y-m-d', strtotime( $start ) ) . ' 23:59:59';
        } else {
            $start_date = date( 'Y-m-d H:i:s', strtotime( $start ) );
            $end_date   = $end ? date( 'Y-m-d H:i:s', strtotime( $end ) ) : '';
        }

        $auto_publish_role = get_option( 'ce_self_service_auto_publish_role', 'editor' );
        $status = $this->user_has_min_role( $auto_publish_role ) ? 'publish' : 'pending';

        $post_id = wp_insert_post( [
            'post_type'    => 'club_event',
            'post_status'  => $status,
            'post_title'   => $title,
            'post_content' => $description ? '<p>' . esc_html( $description ) . '</p>' : '',
            'post_excerpt' => $description,
            'post_author'  => get_current_user_id(),
        ], true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() );
        }

        update_post_meta( $post_id, '_ce_start_date', $start_date );
        update_post_meta( $post_id, '_ce_end_date', $end_date );
        update_post_meta( $post_id, '_ce_all_day', $all_day ? '1' : '0' );
        update_post_meta( $post_id, '_ce_location', $location );
        update_post_meta( $post_id, '_ce_color', $color );
        update_post_meta( $post_id, '_ce_source', 'manual' );

        if ( $category ) {
            wp_set_object_terms( $post_id, [ $category ], 'event_category' );
        }
        if ( $event_type ) {
            wp_set_object_terms( $post_id, [ $event_type ], 'event_type' );
        }

        $this->notify_admin_new_submission( $post_id, $title, $status );

        if ( 'publish' === $status ) {
            wp_send_json_success( __( 'Event published successfully!', 'club-events' ) );
        } else {
            wp_send_json_success( __( 'Event submitted for review. An admin will approve it shortly.', 'club-events' ) );
        }
    }

    /* ─── AJAX: Delete Own Event ───────────────────────────────────────── */
    public function handle_delete() {
        check_ajax_referer( 'ce_delete_my_event', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Not authorized.', 'club-events' ) );
        }

        $post_id = (int) ( $_POST['id'] ?? 0 );
        $post    = get_post( $post_id );

        if ( ! $post || 'club_event' !== $post->post_type ) {
            wp_send_json_error( __( 'Event not found.', 'club-events' ) );
        }

        if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You can only delete your own events.', 'club-events' ) );
        }

        if ( 'publish' === $post->post_status && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Published events can only be deleted by an admin.', 'club-events' ) );
        }

        wp_trash_post( $post_id );
        wp_send_json_success();
    }

    /* ─── Admin notification ───────────────────────────────────────────── */
    private function notify_admin_new_submission( $post_id, $title, $status ) {
        if ( 'publish' === $status ) {
            return;
        }
        $admin_email = get_option( 'admin_email' );
        $user        = wp_get_current_user();
        $review_url  = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

        $subject = sprintf( __( '[%s] New event submission: %s', 'club-events' ), get_bloginfo( 'name' ), $title );
        $message = sprintf( __( '%s submitted a new event for review.', 'club-events' ), $user->display_name ) . "\n\n"
            . sprintf( __( 'Event: %s', 'club-events' ), $title ) . "\n"
            . sprintf( __( 'Review: %s', 'club-events' ), $review_url ) . "\n";

        wp_mail( $admin_email, $subject, $message );
    }

    /* ─── Role check helper ────────────────────────────────────────────── */
    private function user_has_min_role( string $min_role ): bool {
        $hierarchy = [ 'subscriber' => 1, 'contributor' => 2, 'author' => 3, 'editor' => 4, 'administrator' => 5 ];
        $required  = $hierarchy[ $min_role ] ?? 1;

        if ( current_user_can( 'manage_options' ) ) return true;
        if ( $required <= 4 && current_user_can( 'edit_others_posts' ) ) return true;
        if ( $required <= 3 && current_user_can( 'publish_posts' ) ) return true;
        if ( $required <= 2 && current_user_can( 'edit_posts' ) ) return true;
        return $required <= 1;
    }
}
