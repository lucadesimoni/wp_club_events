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
        add_action( 'wp_ajax_ce_save_event_type', [ $this, 'ajax_save_event_type' ] );
        add_action( 'wp_ajax_ce_delete_event_type', [ $this, 'ajax_delete_event_type' ] );
        add_action( 'admin_post_ce_save_settings', [ $this, 'handle_save_settings' ] );
        add_action( 'admin_post_ce_save_calendar_settings', [ $this, 'handle_save_calendar_settings' ] );
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
                'add_type'       => __( 'Add Event Type', 'club-events' ),
                'edit_type'      => __( 'Edit Event Type', 'club-events' ),
                'save_type'      => __( 'Save Type', 'club-events' ),
                'add_calendar'   => __( 'Add Calendar', 'club-events' ),
                'edit_calendar'  => __( 'Edit Calendar', 'club-events' ),
            ],
        ] );
    }

    public function register_settings() {
        $options = [
            'ce_ics_feed_enabled',
            'ce_subscription_enabled',
            'ce_subscription_from_name',
            'ce_subscription_from_email',
            'ce_self_service_enabled',
            'ce_self_service_role',
            'ce_self_service_auto_publish_role',
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
            'ce_ics_feed_enabled'        => isset( $_POST['ce_ics_feed_enabled'] ) ? '1' : '0',
            'ce_subscription_enabled'    => isset( $_POST['ce_subscription_enabled'] ) ? '1' : '0',
            'ce_subscription_from_name'  => sanitize_text_field( $_POST['ce_subscription_from_name'] ?? '' ),
            'ce_subscription_from_email' => sanitize_email( $_POST['ce_subscription_from_email'] ?? '' ),
            'ce_self_service_enabled'    => isset( $_POST['ce_self_service_enabled'] ) ? '1' : '0',
            'ce_self_service_role'       => sanitize_text_field( $_POST['ce_self_service_role'] ?? 'subscriber' ),
            'ce_self_service_auto_publish_role' => sanitize_text_field( $_POST['ce_self_service_auto_publish_role'] ?? 'editor' ),
        ];

        foreach ( $settings as $key => $value ) {
            update_option( $key, $value );
        }

        wp_redirect( add_query_arg( [ 'page' => 'ce-settings', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_save_calendar_settings() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'ce_save_calendar_settings' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'club-events' ) );
        }

        $settings = [
            'ce_google_api_key' => sanitize_text_field( $_POST['ce_google_api_key'] ?? '' ),
            'ce_sync_interval'  => sanitize_text_field( $_POST['ce_sync_interval'] ?? 'hourly' ),
            'ce_future_months'  => (string) max( 1, min( 24, (int) ( $_POST['ce_future_months'] ?? 6 ) ) ),
            'ce_past_months'    => (string) max( 0, min( 12, (int) ( $_POST['ce_past_months'] ?? 1 ) ) ),
        ];

        foreach ( $settings as $key => $value ) {
            update_option( $key, $value );
        }

        $interval = $settings['ce_sync_interval'];
        wp_clear_scheduled_hook( 'ce_google_calendar_sync' );
        wp_schedule_event( time(), $interval, 'ce_google_calendar_sync' );

        wp_redirect( add_query_arg( [ 'page' => 'ce-calendars', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
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

    public function ajax_save_event_type() {
        check_ajax_referer( 'ce_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $term_id     = (int) ( $_POST['term_id'] ?? 0 );
        $name        = sanitize_text_field( $_POST['name'] ?? '' );
        $use_theme   = ! empty( $_POST['theme_color'] );
        // Empty stored colour = "use theme" → frontend resolves to var(--ce-primary).
        $color       = $use_theme ? '' : ( sanitize_hex_color( $_POST['color'] ?? '' ) ?: '#3b82f6' );
        // What the frontend/admin should render for this type right now.
        $display     = $color ?: 'var(--ce-primary)';

        if ( empty( $name ) ) {
            wp_send_json_error( __( 'Name is required.', 'club-events' ) );
        }

        if ( $term_id ) {
            $result = wp_update_term( $term_id, 'event_type', [ 'name' => $name ] );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            update_term_meta( $term_id, '_ce_color', $color );
            $term = get_term( $term_id, 'event_type' );
            wp_send_json_success( [
                'action'   => 'updated',
                'term_id'  => $term_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'color'    => $display,
                'is_theme' => $use_theme,
                'count'    => $term->count,
            ] );
        } else {
            $result = wp_insert_term( $name, 'event_type' );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( $result->get_error_message() );
            }
            $new_id = $result['term_id'];
            update_term_meta( $new_id, '_ce_color', $color );
            $term = get_term( $new_id, 'event_type' );
            wp_send_json_success( [
                'action'   => 'created',
                'term_id'  => $new_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'color'    => $display,
                'is_theme' => $use_theme,
                'count'    => 0,
            ] );
        }
    }

    public function ajax_delete_event_type() {
        check_ajax_referer( 'ce_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }
        $term_id = (int) ( $_POST['term_id'] ?? 0 );
        if ( $term_id ) {
            wp_delete_term( $term_id, 'event_type' );
        }
        wp_send_json_success();
    }

    public function page_dashboard() {
        $total_events = wp_count_posts( 'club_event' )->publish ?? 0;
        global $wpdb;
        $total_subs    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ce_subscribers WHERE confirmed = 1" );
        $total_cals    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ce_calendars" );
        $upcoming      = CE_CPT::get_events( [ 'from' => date( 'Y-m-d H:i:s' ), 'posts_per_page' => 20 ] );
        $event_types   = get_terms( [ 'taxonomy' => 'event_type', 'hide_empty' => false ] );
        if ( is_wp_error( $event_types ) ) {
            $event_types = [];
        }
        $type_count = count( $event_types );
        require CE_PLUGIN_DIR . 'admin/views/page-dashboard.php';
    }

    public function page_calendars() {
        $calendars   = CE_Google_Calendar::get_calendar_list();
        $event_types = get_terms( [ 'taxonomy' => 'event_type', 'hide_empty' => false ] );
        if ( is_wp_error( $event_types ) ) {
            $event_types = [];
        }
        foreach ( $event_types as $et ) {
            $raw            = get_term_meta( $et->term_id, '_ce_color', true );
            $et->is_theme   = empty( $raw );
            // Stored colour for the picker; theme types fall back to the plugin
            // default in wp-admin (Astra vars aren't loaded here).
            $et->color      = $raw ?: '';
            $et->display    = $raw ?: 'var(--ce-primary)';
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
