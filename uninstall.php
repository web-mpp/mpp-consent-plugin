<?php
declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpcs_consent_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpcs_cookies" );

delete_option( 'wpcs_settings' );
delete_option( 'wpcs_db_version' );

wp_clear_scheduled_hook( 'wpcs_scheduled_scan' );
wp_clear_scheduled_hook( 'wpcs_purge_logs' );
