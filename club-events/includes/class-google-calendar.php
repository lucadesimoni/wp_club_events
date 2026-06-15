<?php
defined( 'ABSPATH' ) || exit;

class CE_Google_Calendar {

    const API_BASE = 'https://www.googleapis.com/calendar/v3/calendars/';

    public function __construct() {
        add_action( 'ce_google_calendar_sync', [ $this, 'sync_all' ] );
        add_action( 'wp_ajax_ce_sync_now', [ $this, 'ajax_sync_now' ] );
    }

    public function ajax_sync_now() {
        check_ajax_referer( 'ce_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'club-events' ) );
        }
        $results = $this->sync_all();
        wp_send_json_success( $results );
    }

    public function sync_all() {
        global $wpdb;
        $calendars = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ce_calendars WHERE sync_enabled = 1" );

        if ( empty( $calendars ) ) {
            return [ 'message' => __( 'No calendars configured.', 'club-events' ) ];
        }

        $global_api_key = get_option( 'ce_google_api_key', '' );
        $results        = [];

        foreach ( $calendars as $cal ) {
            $api_key = $cal->api_key ?: $global_api_key;
            if ( empty( $api_key ) ) {
                $results[] = [
                    'calendar' => $cal->name,
                    'status'   => 'skipped',
                    'message'  => __( 'No API key configured.', 'club-events' ),
                ];
                continue;
            }

            $result = $this->sync_calendar( $cal, $api_key );
            $results[] = $result;

            $wpdb->update(
                $wpdb->prefix . 'ce_calendars',
                [ 'last_sync' => current_time( 'mysql' ) ],
                [ 'id' => $cal->id ],
                [ '%s' ],
                [ '%d' ]
            );
        }

        return $results;
    }

    private function sync_calendar( $cal, $api_key ) {
        $future_months = (int) get_option( 'ce_future_months', 6 );
        $past_months   = (int) get_option( 'ce_past_months', 1 );

        $time_min = date( 'c', strtotime( "-{$past_months} months" ) );
        $time_max = date( 'c', strtotime( "+{$future_months} months" ) );

        $url = add_query_arg( [
            'key'          => $api_key,
            'timeMin'      => $time_min,
            'timeMax'      => $time_max,
            'singleEvents' => 'true',
            'orderBy'      => 'startTime',
            'maxResults'   => 500,
        ], self::API_BASE . rawurlencode( $cal->calendar_id ) . '/events' );

        $response = wp_remote_get( $url, [ 'timeout' => 20 ] );

        if ( is_wp_error( $response ) ) {
            return [
                'calendar' => $cal->name,
                'status'   => 'error',
                'message'  => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code ) {
            $msg = isset( $body['error']['message'] ) ? $body['error']['message'] : "HTTP {$code}";
            return [
                'calendar' => $cal->name,
                'status'   => 'error',
                'message'  => $msg,
            ];
        }

        $items        = $body['items'] ?? [];
        $synced_ids   = [];
        $created      = 0;
        $updated      = 0;

        foreach ( $items as $item ) {
            if ( 'cancelled' === ( $item['status'] ?? '' ) ) {
                continue;
            }

            $google_id = $item['id'];
            $synced_ids[] = $google_id;

            $start   = $this->parse_date( $item['start'] ?? [] );
            $end     = $this->parse_date( $item['end'] ?? [] );
            $all_day = isset( $item['start']['date'] ) && ! isset( $item['start']['dateTime'] );

            $existing = $this->find_by_google_id( $google_id );

            $post_data = [
                'post_type'    => 'club_event',
                'post_status'  => 'publish',
                'post_title'   => sanitize_text_field( $item['summary'] ?? __( '(No title)', 'club-events' ) ),
                'post_content' => wp_kses_post( $item['description'] ?? '' ),
            ];

            if ( $existing ) {
                $post_data['ID'] = $existing;
                wp_update_post( $post_data );
                $post_id = $existing;
                $updated++;
            } else {
                $post_id = wp_insert_post( $post_data );
                $created++;
            }

            if ( is_wp_error( $post_id ) || ! $post_id ) {
                continue;
            }

            update_post_meta( $post_id, '_ce_source', 'google' );
            update_post_meta( $post_id, '_ce_google_event_id', $google_id );
            update_post_meta( $post_id, '_ce_calendar_id', (string) $cal->id );
            update_post_meta( $post_id, '_ce_start_date', $start );
            update_post_meta( $post_id, '_ce_end_date', $end );
            update_post_meta( $post_id, '_ce_all_day', $all_day ? '1' : '0' );
            update_post_meta( $post_id, '_ce_location', sanitize_text_field( $item['location'] ?? '' ) );
            update_post_meta( $post_id, '_ce_color', sanitize_hex_color( $cal->color ) ?: '#3b82f6' );
            update_post_meta( $post_id, '_ce_ical_uid', sanitize_text_field( $item['iCalUID'] ?? '' ) );
        }

        $this->delete_stale_events( (string) $cal->id, $synced_ids, $time_min, $time_max );

        return [
            'calendar' => $cal->name,
            'status'   => 'success',
            'created'  => $created,
            'updated'  => $updated,
        ];
    }

    private function parse_date( $date_field ) {
        if ( isset( $date_field['dateTime'] ) ) {
            return date( 'Y-m-d H:i:s', strtotime( $date_field['dateTime'] ) );
        }
        if ( isset( $date_field['date'] ) ) {
            return date( 'Y-m-d', strtotime( $date_field['date'] ) ) . ' 00:00:00';
        }
        return '';
    }

    private function find_by_google_id( $google_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ce_google_event_id' AND meta_value = %s LIMIT 1",
            $google_id
        ) );
    }

    private function delete_stale_events( $cal_db_id, array $synced_ids, $time_min, $time_max ) {
        if ( empty( $synced_ids ) ) {
            return;
        }

        $placeholders = implode( ',', array_fill( 0, count( $synced_ids ), '%s' ) );
        global $wpdb;

        $stale_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_cal ON p.ID = pm_cal.post_id
                 AND pm_cal.meta_key = '_ce_calendar_id' AND pm_cal.meta_value = %s
             INNER JOIN {$wpdb->postmeta} pm_gid ON p.ID = pm_gid.post_id
                 AND pm_gid.meta_key = '_ce_google_event_id'
                 AND pm_gid.meta_value NOT IN ($placeholders)
             INNER JOIN {$wpdb->postmeta} pm_start ON p.ID = pm_start.post_id
                 AND pm_start.meta_key = '_ce_start_date'
                 AND pm_start.meta_value BETWEEN %s AND %s
             WHERE p.post_type = 'club_event' AND p.post_status = 'publish'",
            array_merge( [ $cal_db_id ], $synced_ids, [
                date( 'Y-m-d H:i:s', strtotime( $time_min ) ),
                date( 'Y-m-d H:i:s', strtotime( $time_max ) ),
            ] )
        ) );

        foreach ( $stale_ids as $id ) {
            wp_trash_post( (int) $id );
        }
    }

    public static function get_calendar_list() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ce_calendars ORDER BY name ASC" );
    }

    public static function add_calendar( $data ) {
        global $wpdb;
        return $wpdb->insert(
            $wpdb->prefix . 'ce_calendars',
            [
                'name'         => sanitize_text_field( $data['name'] ),
                'calendar_id'  => sanitize_text_field( $data['calendar_id'] ),
                'api_key'      => sanitize_text_field( $data['api_key'] ?? '' ),
                'color'        => sanitize_hex_color( $data['color'] ?? '#3b82f6' ) ?: '#3b82f6',
                'sync_enabled' => empty( $data['sync_enabled'] ) ? 0 : 1,
            ],
            [ '%s', '%s', '%s', '%s', '%d' ]
        );
    }

    public static function update_calendar( $id, $data ) {
        global $wpdb;
        return $wpdb->update(
            $wpdb->prefix . 'ce_calendars',
            [
                'name'         => sanitize_text_field( $data['name'] ),
                'calendar_id'  => sanitize_text_field( $data['calendar_id'] ),
                'api_key'      => sanitize_text_field( $data['api_key'] ?? '' ),
                'color'        => sanitize_hex_color( $data['color'] ?? '#3b82f6' ) ?: '#3b82f6',
                'sync_enabled' => empty( $data['sync_enabled'] ) ? 0 : 1,
            ],
            [ 'id' => (int) $id ],
            [ '%s', '%s', '%s', '%s', '%d' ],
            [ '%d' ]
        );
    }

    public static function delete_calendar( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'ce_calendars', [ 'id' => (int) $id ], [ '%d' ] );
    }
}
