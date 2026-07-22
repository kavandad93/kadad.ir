<?php
/**
 * Helper Functions
 * Utility functions for the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin version
 */
function khc_get_version()
{
    return KHC_VERSION;
}

/**
 * Get scan history
 */
function khc_get_scan_history($limit = 10)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'khc_scans';
    
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY scan_date DESC LIMIT %d",
            $limit
        ),
        ARRAY_A
    );
}

/**
 * Get latest scan
 */
function khc_get_latest_scan()
{
    $scan_id = get_option('khc_last_scan');
    if (!$scan_id) {
        return null;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'khc_scans';
    
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $scan_id
        ),
        ARRAY_A
    );
}

/**
 * Format bytes to human readable
 */
function khc_format_bytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Get score color
 */
function khc_get_score_color($score)
{
    if ($score >= 95) return '#10b981';
    if ($score >= 80) return '#3b82f6';
    if ($score >= 60) return '#f59e0b';
    return '#ef4444';
}

/**
 * Get score status
 */
function khc_get_score_status($score)
{
    if ($score >= 95) return __('Excellent', KHC_TEXT_DOMAIN);
    if ($score >= 80) return __('Good', KHC_TEXT_DOMAIN);
    if ($score >= 60) return __('Needs Attention', KHC_TEXT_DOMAIN);
    return __('Critical', KHC_TEXT_DOMAIN);
}

/**
 * Check if WooCommerce is active
 */
function khc_is_woocommerce_active()
{
    return class_exists('WooCommerce');
}

/**
 * Get memory usage
 */
function khc_get_memory_usage()
{
    return khc_format_bytes(memory_get_peak_usage(true));
}

/**
 * Get server info
 */
function khc_get_server_info()
{
    return [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => phpversion(),
        'mysql_version' => $GLOBALS['wpdb']->get_var("SELECT VERSION()"),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
}

/**
 * Check if debug mode is enabled
 */
function khc_is_debug_enabled()
{
    return defined('WP_DEBUG') && WP_DEBUG === true;
}

/**
 * Check if SSL is enabled
 */
function khc_is_ssl_enabled()
{
    return is_ssl();
}

/**
 * Get site health status
 */
function khc_get_site_status()
{
    return [
        'wordpress_version' => get_bloginfo('version'),
        'site_url' => home_url(),
        'site_name' => get_bloginfo('name'),
        'admin_email' => get_option('admin_email'),
        'language' => get_bloginfo('language'),
        'timezone' => get_option('timezone_string'),
        'date_format' => get_option('date_format'),
        'time_format' => get_option('time_format')
    ];
}