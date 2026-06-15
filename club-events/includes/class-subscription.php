<?php
defined( 'ABSPATH' ) || exit;

class CE_Subscription {

    public function __construct() {
        add_action( 'wp_ajax_nopriv_ce_subscribe', [ $this, 'handle_subscribe' ] );
        add_action( 'wp_ajax_ce_subscribe', [ $this, 'handle_subscribe' ] );
        add_action( 'init', [ $this, 'handle_confirm' ] );
        add_action( 'init', [ $this, 'handle_unsubscribe' ] );
        add_action( 'publish_club_event', [ $this, 'notify_subscribers' ], 20, 2 );
    }

    public function handle_subscribe() {
        check_ajax_referer( 'ce_subscribe_nonce', 'nonce' );

        if ( get_option( 'ce_subscription_enabled', '1' ) !== '1' ) {
            wp_send_json_error( __( 'Subscriptions are disabled.', 'club-events' ) );
        }

        $email = sanitize_email( $_POST['email'] ?? '' );
        $name  = sanitize_text_field( $_POST['name'] ?? '' );
        $cats  = sanitize_text_field( $_POST['categories'] ?? '' );

        if ( ! is_email( $email ) ) {
            wp_send_json_error( __( 'Please enter a valid email address.', 'club-events' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ce_subscribers';

        $existing = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE email = %s",
            $email
        ) );

        if ( $existing && $existing->confirmed ) {
            wp_send_json_error( __( 'You are already subscribed.', 'club-events' ) );
        }

        $token = bin2hex( random_bytes( 32 ) );

        if ( $existing ) {
            $wpdb->update( $table, [ 'token' => $token, 'name' => $name, 'categories' => $cats ], [ 'email' => $email ], [ '%s', '%s', '%s' ], [ '%s' ] );
        } else {
            $wpdb->insert( $table, [
                'email'      => $email,
                'name'       => $name,
                'token'      => $token,
                'categories' => $cats,
                'confirmed'  => 0,
                'created_at' => current_time( 'mysql' ),
            ], [ '%s', '%s', '%s', '%s', '%d', '%s' ] );
        }

        $this->send_confirmation_email( $email, $name, $token );

        wp_send_json_success( __( 'Please check your email to confirm your subscription.', 'club-events' ) );
    }

    private function send_confirmation_email( $email, $name, $token ) {
        $confirm_url = add_query_arg( [
            'ce_confirm' => rawurlencode( $token ),
        ], home_url() );

        $from_name  = get_option( 'ce_subscription_from_name', get_bloginfo( 'name' ) );
        $from_email = get_option( 'ce_subscription_from_email', get_option( 'admin_email' ) );

        $greeting = $name ? sprintf( __( 'Hi %s,', 'club-events' ), $name ) : __( 'Hi there,', 'club-events' );

        $subject = sprintf( __( 'Confirm your event subscription — %s', 'club-events' ), get_bloginfo( 'name' ) );
        $message = $greeting . "\n\n"
            . __( 'Please click the link below to confirm your subscription to event updates:', 'club-events' ) . "\n\n"
            . $confirm_url . "\n\n"
            . __( 'If you did not request this, please ignore this email.', 'club-events' ) . "\n\n"
            . '— ' . $from_name;

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        ];

        wp_mail( $email, $subject, $message, $headers );
    }

    public function handle_confirm() {
        if ( empty( $_GET['ce_confirm'] ) ) {
            return;
        }

        $token = sanitize_text_field( rawurldecode( $_GET['ce_confirm'] ) );
        if ( strlen( $token ) !== 64 || ! ctype_xdigit( $token ) ) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'ce_subscribers';

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE token = %s", $token ) );

        if ( ! $row ) {
            wp_die( esc_html__( 'Invalid or expired confirmation link.', 'club-events' ) );
        }

        $wpdb->update( $table, [ 'confirmed' => 1 ], [ 'id' => $row->id ], [ '%d' ], [ '%d' ] );

        wp_redirect( add_query_arg( 'ce_subscribed', '1', home_url() ) );
        exit;
    }

    public function handle_unsubscribe() {
        if ( empty( $_GET['ce_unsubscribe'] ) ) {
            return;
        }

        $token = sanitize_text_field( rawurldecode( $_GET['ce_unsubscribe'] ) );
        if ( strlen( $token ) !== 64 || ! ctype_xdigit( $token ) ) {
            return;
        }

        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'ce_subscribers', [ 'token' => $token ], [ '%s' ] );

        wp_redirect( add_query_arg( 'ce_unsubscribed', '1', home_url() ) );
        exit;
    }

    public function notify_subscribers( $post_id, $post ) {
        if ( 'club_event' !== $post->post_type ) {
            return;
        }

        if ( get_post_meta( $post_id, '_ce_notification_sent', true ) ) {
            return;
        }

        global $wpdb;
        $subscribers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ce_subscribers WHERE confirmed = 1" );

        if ( empty( $subscribers ) ) {
            return;
        }

        $event      = CE_CPT::format_event( $post_id );
        $from_name  = get_option( 'ce_subscription_from_name', get_bloginfo( 'name' ) );
        $from_email = get_option( 'ce_subscription_from_email', get_option( 'admin_email' ) );
        $headers    = [
            'Content-Type: text/plain; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
        ];

        $tz_string = get_option( 'timezone_string', 'UTC' );

        $start_formatted = $event['start']
            ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $event['start'] ) )
            : '';

        foreach ( $subscribers as $sub ) {
            $unsub_url = add_query_arg( [
                'ce_unsubscribe' => rawurlencode( $sub->token ),
            ], home_url() );

            $greeting = $sub->name ? sprintf( __( 'Hi %s,', 'club-events' ), $sub->name ) : __( 'Hi there,', 'club-events' );

            $message = $greeting . "\n\n"
                . sprintf( __( 'A new event has been posted: %s', 'club-events' ), $event['title'] ) . "\n\n"
                . ( $start_formatted ? sprintf( __( 'Date: %s', 'club-events' ), $start_formatted ) . "\n" : '' )
                . ( $event['location'] ? sprintf( __( 'Location: %s', 'club-events' ), $event['location'] ) . "\n" : '' )
                . "\n"
                . __( 'More information:', 'club-events' ) . ' ' . $event['url'] . "\n\n"
                . '—' . "\n"
                . sprintf( __( 'Unsubscribe: %s', 'club-events' ), $unsub_url );

            $subject = sprintf( __( 'New Event: %s', 'club-events' ), $event['title'] );
            wp_mail( $sub->email, $subject, $message, $headers );
        }

        update_post_meta( $post_id, '_ce_notification_sent', '1' );
    }

    public static function get_subscribers( $page = 1, $per_page = 50 ) {
        global $wpdb;
        $offset = ( $page - 1 ) * $per_page;
        $table  = $wpdb->prefix . 'ce_subscribers';
        return [
            'rows'  => $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) ),
            'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" ),
        ];
    }

    public static function delete_subscriber( $id ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . 'ce_subscribers', [ 'id' => (int) $id ], [ '%d' ] );
    }
}
