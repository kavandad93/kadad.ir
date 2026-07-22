<?php
/**
 * Performance Class
 * Handles performance checks
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Performance
{
    /**
     * Check all performance aspects
     */
    public function check_all()
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        
        // Homepage load time
        $load_time = $this->get_homepage_load_time();
        if ($load_time > 2) {
            $score -= min(($load_time - 2) * 5, 20);
            $issues[] = sprintf(__('Homepage load time is high (%.2f seconds)', KHC_TEXT_DOMAIN), $load_time);
            $recommendations[] = __('Optimize images, implement caching, and minify assets', KHC_TEXT_DOMAIN);
        }
        
        // Total page size
        $page_size = $this->get_page_size();
        if ($page_size > 2000) { // 2MB
            $score -= 10;
            $issues[] = sprintf(__('Page size is large (%d KB)', KHC_TEXT_DOMAIN), $page_size);
            $recommendations[] = __('Optimize images and reduce unnecessary assets', KHC_TEXT_DOMAIN);
        }
        
        // Request count
        $request_count = $this->get_request_count();
        if ($request_count > 50) {
            $score -= 10;
            $issues[] = sprintf(__('High number of requests (%d)', KHC_TEXT_DOMAIN), $request_count);
            $recommendations[] = __('Combine CSS/JS files and reduce HTTP requests', KHC_TEXT_DOMAIN);
        }
        
        // Unused plugins
        $unused_plugins = $this->get_unused_plugins();
        if ($unused_plugins > 0) {
            $score -= min($unused_plugins * 3, 15);
            $issues[] = sprintf(__('%d unused plugins found', KHC_TEXT_DOMAIN), $unused_plugins);
            $recommendations[] = __('Deactivate and delete unused plugins', KHC_TEXT_DOMAIN);
        }
        
        // Autoload options size
        $autoload_size = $this->get_autoload_size();
        if ($autoload_size > 2000) { // 2MB
            $score -= 10;
            $issues[] = sprintf(__('Autoload options size is large (%d KB)', KHC_TEXT_DOMAIN), $autoload_size);
            $recommendations[] = __('Optimize autoload options by moving non-critical options to non-autoload', KHC_TEXT_DOMAIN);
        }
        
        // Object cache
        if (!$this->is_object_cache_enabled()) {
            $score -= 15;
            $issues[] = __('Object cache is not enabled', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Install Redis or Memcached for object caching', KHC_TEXT_DOMAIN);
        }
        
        // Opcode cache
        if (!$this->is_opcode_cache_enabled()) {
            $score -= 10;
            $issues[] = __('Opcode cache is not enabled', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Install OPcache or APC for PHP opcode caching', KHC_TEXT_DOMAIN);
        }
        
        // Gzip compression
        if (!$this->is_gzip_enabled()) {
            $score -= 5;
            $issues[] = __('Gzip compression is not enabled', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Enable Gzip compression in .htaccess or server configuration', KHC_TEXT_DOMAIN);
        }
        
        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'details' => [
                'load_time' => $load_time,
                'page_size' => $page_size,
                'request_count' => $request_count,
                'unused_plugins' => $unused_plugins,
                'autoload_size' => $autoload_size,
                'object_cache' => $this->is_object_cache_enabled(),
                'opcode_cache' => $this->is_opcode_cache_enabled(),
                'gzip_enabled' => $this->is_gzip_enabled()
            ]
        ];
    }

    /**
     * Get homepage load time
     */
    private function get_homepage_load_time()
    {
        $start = microtime(true);
        $response = wp_remote_get(home_url(), ['timeout' => 10]);
        $end = microtime(true);
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        return round($end - $start, 2);
    }

    /**
     * Get page size
     */
    private function get_page_size()
    {
        $response = wp_remote_get(home_url(), ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        $body = wp_remote_retrieve_body($response);
        return round(strlen($body) / 1024); // KB
    }

    /**
     * Get request count
     */
    private function get_request_count()
    {
        // Simplified - would need to parse HTML and count assets
        $response = wp_remote_get(home_url(), ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return 0;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Count script tags
        preg_match_all('/<script[^>]*src=["\']([^"\']+)["\'][^>]*>/i', $body, $scripts);
        $script_count = count($scripts[0]);
        
        // Count link tags (CSS)
        preg_match_all('/<link[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $body, $links);
        $css_count = count(array_filter($links[0], function($link) {
            return strpos($link, 'stylesheet') !== false;
        }));
        
        return $script_count + $css_count;
    }

    /**
     * Get unused plugins
     */
    private function get_unused_plugins()
    {
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        
        $unused = array_diff_key($all_plugins, array_flip($active_plugins));
        return count($unused);
    }

    /**
     * Get autoload size
     */
    private function get_autoload_size()
    {
        global $wpdb;
        
        $size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) / 1024 
            FROM $wpdb->options 
            WHERE autoload = 'yes'"
        );
        
        return intval($size);
    }

    /**
     * Check if object cache is enabled
     */
    private function is_object_cache_enabled()
    {
        return wp_using_ext_object_cache();
    }

    /**
     * Check if opcode cache is enabled
     */
    private function is_opcode_cache_enabled()
    {
        return extension_loaded('opcache') || extension_loaded('apc') || extension_loaded('apcu');
    }

    /**
     * Check if Gzip compression is enabled
     */
    private function is_gzip_enabled()
    {
        $response = wp_remote_get(home_url(), ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $encoding = wp_remote_retrieve_header($response, 'content-encoding');
        return $encoding === 'gzip';
    }
}