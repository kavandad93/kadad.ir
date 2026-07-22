<?php
/**
 * System Class
 * Handles system checks
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_System
{
    /**
     * Check all system aspects
     */
    public function check_all()
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        
        // WordPress version
        $wp_version = get_bloginfo('version');
        if (version_compare($wp_version, '6.0', '<')) {
            $score -= 15;
            $issues[] = __('WordPress version is outdated', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Update WordPress to the latest version', KHC_TEXT_DOMAIN);
        }
        
        // PHP version
        $php_version = phpversion();
        if (version_compare($php_version, '8.0', '<')) {
            $score -= 15;
            $issues[] = sprintf(__('PHP version %s is outdated', KHC_TEXT_DOMAIN), $php_version);
            $recommendations[] = __('Update PHP to version 8.0 or higher', KHC_TEXT_DOMAIN);
        }
        
        // MySQL version
        global $wpdb;
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        if (version_compare($mysql_version, '5.7', '<')) {
            $score -= 10;
            $issues[] = sprintf(__('MySQL version %s is outdated', KHC_TEXT_DOMAIN), $mysql_version);
            $recommendations[] = __('Update MySQL to version 5.7 or higher', KHC_TEXT_DOMAIN);
        }
        
        // Memory limit
        $memory_limit = $this->get_memory_limit();
        if ($memory_limit < 128) {
            $score -= 10;
            $issues[] = sprintf(__('Memory limit is low (%d MB)', KHC_TEXT_DOMAIN), $memory_limit);
            $recommendations[] = __('Increase memory limit to at least 256MB', KHC_TEXT_DOMAIN);
        }
        
        // Execution time
        $execution_time = ini_get('max_execution_time');
        if ($execution_time < 60) {
            $score -= 5;
            $issues[] = sprintf(__('Execution time is low (%d seconds)', KHC_TEXT_DOMAIN), $execution_time);
            $recommendations[] = __('Increase max_execution_time to at least 120', KHC_TEXT_DOMAIN);
        }
        
        // Upload max filesize
        $upload_max = $this->get_upload_max_filesize();
        if ($upload_max < 2) {
            $score -= 5;
            $issues[] = sprintf(__('Upload max filesize is low (%d MB)', KHC_TEXT_DOMAIN), $upload_max);
            $recommendations[] = __('Increase upload_max_filesize to at least 8MB', KHC_TEXT_DOMAIN);
        }
        
        // SSL/HTTPS
        if (!$this->is_ssl_enabled()) {
            $score -= 20;
            $issues[] = __('SSL/HTTPS is not enabled', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Enable SSL/HTTPS for security', KHC_TEXT_DOMAIN);
        }
        
        // REST API
        if (!$this->is_rest_api_enabled()) {
            $score -= 10;
            $issues[] = __('REST API is not available', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Enable REST API for better functionality', KHC_TEXT_DOMAIN);
        }
        
        // Cron jobs
        if (!$this->is_cron_running()) {
            $score -= 10;
            $issues[] = __('WordPress cron is not running properly', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Set up a proper cron job for WordPress', KHC_TEXT_DOMAIN);
        }
        
        // Debug mode
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            $score -= 5;
            $issues[] = __('Debug mode is enabled on production', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Disable debug mode on production sites', KHC_TEXT_DOMAIN);
        }
        
        // Maintenance mode
        if ($this->is_maintenance_mode_enabled()) {
            $score -= 10;
            $issues[] = __('Site is in maintenance mode', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Take site out of maintenance mode', KHC_TEXT_DOMAIN);
        }
        
        // File permissions
        $permissions = $this->check_file_permissions();
        if (!empty($permissions)) {
            $score -= 10;
            $issues = array_merge($issues, $permissions);
            $recommendations[] = __('Fix file permissions to proper values', KHC_TEXT_DOMAIN);
        }
        
        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'details' => [
                'wp_version' => $wp_version,
                'php_version' => $php_version,
                'mysql_version' => $mysql_version,
                'memory_limit' => $memory_limit,
                'execution_time' => $execution_time,
                'upload_max' => $upload_max,
                'ssl_enabled' => $this->is_ssl_enabled(),
                'rest_api' => $this->is_rest_api_enabled(),
                'cron_running' => $this->is_cron_running(),
                'debug_mode' => defined('WP_DEBUG') && WP_DEBUG === true,
                'maintenance_mode' => $this->is_maintenance_mode_enabled(),
                'permissions' => $permissions
            ]
        ];
    }

    /**
     * Get memory limit in MB
     */
    private function get_memory_limit()
    {
        $memory_limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)([MG])$/', $memory_limit, $matches)) {
            $value = intval($matches[1]);
            $unit = $matches[2];
            return $unit === 'M' ? $value : $value * 1024;
        }
        return intval($memory_limit);
    }

    /**
     * Get upload max filesize in MB
     */
    private function get_upload_max_filesize()
    {
        $size = ini_get('upload_max_filesize');
        if (preg_match('/^(\d+)([MG])$/', $size, $matches)) {
            $value = intval($matches[1]);
            $unit = $matches[2];
            return $unit === 'M' ? $value : $value * 1024;
        }
        return intval($size);
    }

    /**
     * Check if SSL is enabled
     */
    private function is_ssl_enabled()
    {
        return is_ssl();
    }

    /**
     * Check if REST API is enabled
     */
    private function is_rest_api_enabled()
    {
        $response = wp_remote_get(rest_url(), ['timeout' => 5]);
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Check if cron is running
     */
    private function is_cron_running()
    {
        $cron = _get_cron_array();
        return !empty($cron);
    }

    /**
     * Check if maintenance mode is enabled
     */
    private function is_maintenance_mode_enabled()
    {
        return file_exists(ABSPATH . '.maintenance');
    }

    /**
     * Check file permissions
     */
    private function check_file_permissions()
    {
        $issues = [];
        $files_to_check = [
            'wp-config.php' => ABSPATH . 'wp-config.php',
            'index.php' => ABSPATH . 'index.php',
            'wp-admin/index.php' => ABSPATH . 'wp-admin/index.php',
            'wp-includes/index.php' => ABSPATH . 'wp-includes/index.php'
        ];
        
        foreach ($files_to_check as $name => $path) {
            if (file_exists($path)) {
                $perms = fileperms($path);
                if ($perms !== false) {
                    $perms = substr(sprintf('%o', $perms), -4);
                    if ($perms === '0777' || $perms === '0666') {
                        $issues[] = sprintf(__('File %s has insecure permissions (%s)', KHC_TEXT_DOMAIN), $name, $perms);
                    }
                }
            }
        }
        
        return $issues;
    }
}