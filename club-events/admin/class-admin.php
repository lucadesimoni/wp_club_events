<?php
defined( 'ABSPATH' ) || exit;

class CE_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'wp_ajax_ce_save_calendar', [ $this, 'ajax_save_calendar' ] );
        add_action( 'wp_ajax_ce_delete_calendar', [ $this, 'ajax_delete_calendar' ] );
        add_action( 'wp_ajax_ce_delete_subscriber', [ $this, 'ajax_delete_subscriber' ] );
        add_action( 'admin_post_ce_save_settings', [ $this, 'handle_save_settings' ] );
    }

    public function register_menus() {
        add_menu_page(
            __( 'Club Events', 'club-events' ),
            __( 'Club Events', 'club-events' ),
            'manage_options',
            'club-events',
            [ $this, 'page_dashboard' ],
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page( 'club-events', __( 'All Events', 'club-events' ), __( 'All Events', 'club-events' ), 'edit_posts', 'edit.php?post_type=club_event' );
        add_submenu_page( 'club-events', __( 'Add Event', 'club-events' ), __( 'Add Event', 'club-events' ), 'edit_posts', 'post-new.php?post_type=club_event' );
        add_submenu_page( 'club-events', __( 'Google Calendars', 'club-events' ), __( 'Google Calendars', 'club-events' ), 'manage_options', 'ce-calendars', [ $this, 'page_calendars' ] );
        add_submenu_page( 'club-events', __( 'Subscribers', 'club-events' ), __( 'Subscribers', 'club-events' ), 'manage_options', 'ce-subscribers', [ $this, 'page_subscribers' ] );
        add_submenu_page( 'club-events', __( 'Settings', 'club-events' ), __( 'Settings', 'club-events' ), 'manage_options', 'ce-settings', [ $this, 'page_settings' ] );
    }

    public function enqueue_assets( $hook ) {
        $ce_pages = [ 'toplevel_page_club-events', 'club-events_page_ce-calendars', 'club-events_page_ce-subscribers', 'club-events_page_ce-settings' ];
        if ( ! in_array( $hook, $ce_pages, true ) ) {
            return;
        }

        wp_enqueue_style( 'club-events-admin', CE_PLUGIN_URL . 'public/css/club-events-admin.css', [], CE_VERSION );
        wp_enqueue_script( 'club-events-admin', CE_PLUGIN_URL . 'public/js/club-events-admin.js', [], CE_VERSION, true );
        wp_localize_script( 'club-events-admin', 'CE_ADMIN', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'ce_admin_nonce' ),
            'i18n'    => [
                'confirm_delete' => __( 'Are you sure? This cannot be undone.', 'club-events' ),
                'syncing'        => __( 'Syncing…', 'club-events' ),
                'sync_done'      => __( 'Sync complete!', 'club-events' ),
                'sync_error'     => __( 'Sync failed. Check your API key and calendar ID.', 'club-events' ),
                'saving'         => __( 'Saving…', 'club-events' ),
                'saved'          => __( 'Saved!', 'club-events' ),
            ],
        ] );
    }

    public function register_settings() {
        $options = [
            'ce_google_api_key',
            'ce_sync_interval',
            'ce_ics_feed_enabled',
            'ce_subscription_enabled',
            'ce_subscription_from_name',
            'ce_subscription_from_email',
            'ce_future_months',
            'ce_past_months',
        ];
        foreach ( $options as $opt ) {
            register_setting( 'ce_settings', $opt, [ 'sanitize_callback' => 'sanitize_text_field' ] );
        }
    }

    public function handle_save_settings() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ce_save_settings' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'club-events' ) );
        }

        $settings = [
            'ce_google_api_key'          => sanitize_text_field( $_POST['ce_google_api_key'] ?? '' ),
            'ce_sync_interval'           => sanitize_text_field( $_POST['ce_sync_interval'] ?? 'hourly' ),
            'ce_ics_feed_enabled'        => isset( $_POST['ce_ics_feed_enabled'] ) ? '1' : '0',
            'ce_subscription_enabled'    => isset( $_POST['ce_subscription_enabled'] ) ? '1' : '0',
            'ce_subscription_from_name'  => sanitize_text_field( $_POST['ce_subscription_from_name'] ?? '' ),
            'ce_subscription_from_email' => sanitize_email( $_POST['ce_subscription_from_email'] ?? '' ),
            'ce_future_months'           => (string) max( 1, min( 24, (int) ( $_POST['ce_future_months'] ?? 6 ) ) ),
            'ce_past_months'             => (string) max( 0, min( 12, (int) ( $_POST['ce_past_months'] ?? 1 ) ) ),
        ];

        foreach ( $settings as $key => $value ) {
            update_option( $key, $value );
        }

        $interval = $settings['ce_sync_interval'];
        wp_clear_scheduled_hook( 'ce_google_calendar_sync' );
        wp_schedule_event( time(), $interval, 'ce_google_calendar_sync' );

        wp_redirect( add_query_arg( [ 'page' => 'ce-settings', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function ajax_save_calendar() {
        check_ajax_referer( 'ce_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $id   = (int) ( $_POST['id'] ?? 0 );
        $raw_types = isset( $_POST['event_types'] ) && is_array( $_POST['event_types'] )
            ? array_map( 'sanitize_text_field', $_POST['event_types'] )
            : [];
        $data = [
            'name'         => $_POST['name'] ?? '',
            'calendar_id'  => $_POST['calendar_id'] ?? '',
            'api_key'      => $_POST['api_key'] ?? '',
            'color'        => $_POST['color'] ?? '#3b82f6',
            'event_types'  => implode( ',', $raw_types ),
            'sync_enabled' => ! empty( $_POST['sync_enabled'] ),
        ];

        if ( $id ) {
            CE_Google_Calendar::update_calendar( $id, $data );
            wp_send_json_success( [ 'action' => 'updated' ] );
        } else {
            $new_id = CE_Google_Calendar::add_calendar( $data );
            wp_send_json_success( [ 'action' => 'created', 'id' => $new_id ] );
        }
    }

    public function ajax_delete_calendar() {
        check_ajax_referer( 'ce_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }
        CE_Google_Calendar::delete_calendar( (int) ( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public function ajax_delete_subscriber() {
        check_ajax_referer( 'ce_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }
        CE_Subscription::delete_subscriber( (int) ( $_POST['id'] ?? 0 ) );
        wp_send_json_success();
    }

    public function page_dashboard() {
        $total_events = wp_count_posts( 'club_event' )->publish ?? 0;
        global $wpdb;
        $total_subs    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ce_subscribers WHERE confirmed = 1" );
        $total_cals    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ce_calendars" );
        $upcoming      = CE_CPT::get_events( [ 'from' => date( 'Y-m-d H:i:s' ), 'posts_per_page' => 5 ] );
        require CE_PLUGIN_DIR . 'admin/views/page-dashboard.php';
    }

    public function page_calendars() {
        $calendars   = CE_Google_Calendar::get_calendar_list();
        $event_types = get_terms( [ 'taxonomy' => 'event_type', 'hide_empty' => false ] );
        if ( is_wp_error( $event_types ) ) {
            $event_types = [];
        }
        require CE_PLUGIN_DIR . 'admin/views/page-calendars.php';
    }

    public function page_subscribers() {
        $page    = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $result  = CE_Subscription::get_subscribers( $page );
        require CE_PLUGIN_DIR . 'admin/views/page-subscribers.php';
    }

    public function page_settings() {
        require CE_PLUGIN_DIR . 'admin/views/page-settings.php';
    }
}
