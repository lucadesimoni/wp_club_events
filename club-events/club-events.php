<?php
/**
 * Plugin Name: Club Events Manager
 * Plugin URI:  https://github.com/lucadesimoni/wp_club_events
 * Description: Modern event management for clubs — sync multiple Google Calendars, timeline & overview views, blog embeds, ICS export, and email subscriptions.
 * Version:     1.0.0
 * Author:      Club Events Manager
 * License:     GPL-2.0+
 * Text Domain: club-events
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p><strong>Club Events Manager</strong> requires PHP 7.4 or higher. You are running PHP ' . esc_html( PHP_VERSION ) . '.</p></div>';
    } );
    return;
}

define( 'CE_VERSION',     '1.0.0' );
define( 'CE_PLUGIN_FILE', __FILE__ );
define( 'CE_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CE_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'CE_PLUGIN_BASE', plugin_basename( __FILE__ ) );

require_once CE_PLUGIN_DIR . 'includes/class-plugin.php';

function ce_plugin() {
    return CE_Plugin::instance();
}
add_action( 'plugins_loaded', 'ce_plugin' );

register_activation_hook(   __FILE__, [ 'CE_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'CE_Plugin', 'deactivate' ] );
