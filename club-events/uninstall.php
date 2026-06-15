<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Remove custom tables
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ce_subscribers" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ce_calendars" );

// Remove plugin options
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
    delete_option( $opt );
}

// Remove all club_event posts and their meta
$post_ids = get_posts( [
    'post_type'      => 'club_event',
    'post_status'    => 'any',
    'numberposts'    => -1,
    'fields'         => 'ids',
] );
foreach ( $post_ids as $id ) {
    wp_delete_post( (int) $id, true );
}

flush_rewrite_rules();
