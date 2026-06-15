<?php
defined( 'ABSPATH' ) || exit;

class CE_Plugin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->boot();
        }
        return self::$instance;
    }

    private function boot() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once CE_PLUGIN_DIR . 'includes/class-cpt.php';
        require_once CE_PLUGIN_DIR . 'includes/class-google-calendar.php';
        require_once CE_PLUGIN_DIR . 'includes/class-ics-export.php';
        require_once CE_PLUGIN_DIR . 'includes/class-subscription.php';
        require_once CE_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once CE_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once CE_PLUGIN_DIR . 'admin/class-admin.php';
    }

    private function init_hooks() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );

        new CE_CPT();
        new CE_Google_Calendar();
        new CE_ICS_Export();
        new CE_Subscription();
        new CE_Shortcodes();
        new CE_REST_API();

        if ( is_admin() ) {
            new CE_Admin();
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'club-events', false, dirname( CE_PLUGIN_BASE ) . '/languages' );
    }

    public function enqueue_public_assets() {
        wp_enqueue_style(
            'club-events',
            CE_PLUGIN_URL . 'public/css/club-events-public.css',
            [],
            CE_VERSION
        );
        wp_enqueue_script(
            'club-events',
            CE_PLUGIN_URL . 'public/js/club-events-public.js',
            [],
            CE_VERSION,
            true
        );
        wp_localize_script( 'club-events', 'CE', [
            'restUrl'   => esc_url_raw( rest_url( 'club-events/v1' ) ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'pluginUrl' => CE_PLUGIN_URL,
            'i18n'      => [
                'noEvents'    => __( 'No events found.', 'club-events' ),
                'loading'     => __( 'Loading…', 'club-events' ),
                'subscribe'   => __( 'Subscribe', 'club-events' ),
                'subscribed'  => __( 'Subscribed!', 'club-events' ),
                'addToCalendar' => __( 'Add to Calendar', 'club-events' ),
            ],
        ] );
    }

    public static function activate() {
        self::create_tables();
        self::set_defaults();
        flush_rewrite_rules();

        if ( ! wp_next_scheduled( 'ce_google_calendar_sync' ) ) {
            wp_schedule_event( time(), 'hourly', 'ce_google_calendar_sync' );
        }
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'ce_google_calendar_sync' );
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ce_subscribers (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email varchar(200) NOT NULL,
            name varchar(200) DEFAULT '',
            token varchar(64) NOT NULL,
            categories varchar(500) DEFAULT '',
            confirmed tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY token (token)
        ) $charset;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ce_calendars (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            calendar_id varchar(500) NOT NULL,
            api_key varchar(500) DEFAULT '',
            color varchar(20) DEFAULT '#3b82f6',
            sync_enabled tinyint(1) DEFAULT 1,
            last_sync datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    private static function set_defaults() {
        $defaults = [
            'ce_sync_interval'          => 'hourly',
            'ce_ics_feed_enabled'       => '1',
            'ce_subscription_enabled'   => '1',
            'ce_subscription_from_name' => get_bloginfo( 'name' ),
            'ce_subscription_from_email'=> get_option( 'admin_email' ),
            'ce_future_months'          => '6',
            'ce_past_months'            => '1',
        ];
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
}
