<?php
/**
 * Duck Race uninstall handler.
 *
 * Data is removed only when administrator has explicitly confirmed destructive uninstall
 * through plugin settings.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'duck_race_settings', [] );
$confirmed = ! empty( $settings['confirm_uninstall_data_removal'] );

if ( ! $confirmed ) {
    // Explicit confirmation is required; keep all data by default.
    return;
}

global $wpdb;

$tables = [
    'races',
    'contacts',
    'purchases',
    'entries',
    'duck_status',
    'email_log',
    'audit_log',
    'winner_positions',
];

foreach ( $tables as $suffix ) {
    $table = $wpdb->prefix . 'duck_race_' . $suffix;
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

delete_option( 'duck_race_db_version' );
delete_option( 'duck_race_settings' );
delete_option( 'duck_race_retention_last_run_at' );
delete_option( 'duck_race_retention_last_run_count' );
