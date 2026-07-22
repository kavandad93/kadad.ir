<?php
/**
 * Plugin uninstall handler
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('khc_scan_interval');
delete_option('khc_dark_mode');
delete_option('khc_email_recipient');
delete_option('khc_auto_reports');
delete_option('khc_auto_cleanup');
delete_option('khc_last_scan');
delete_option('khc_scan_history');
delete_option('khc_last_report');
delete_option('khc_critical_issues');

// Drop tables
global $wpdb;
$table_name = $wpdb->prefix . 'khc_scans';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Clear cron jobs
wp_clear_scheduled_hook('khc_daily_scan');
wp_clear_scheduled_hook('khc_weekly_report');