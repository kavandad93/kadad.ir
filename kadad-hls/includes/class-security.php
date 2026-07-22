<?php
/**
 * Security Class
 * Handles security checks
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Security
{
    /**
     * Check all security aspects
     */
    public function check_all()
    {
        $issues = [];
        $recommendations = [];
        $score = 100;
        
        // Debug mode
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            $score -= 10;
            $issues[] = __('WP_DEBUG is enabled on production site', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Disable WP_DEBUG on production sites', KHC_TEXT_DOMAIN);
        }
        
        // File editing
        if (!defined('DISALLOW_FILE_EDIT') || DISALLOW_FILE_EDIT !== true) {
            $score -= 5;
            $issues[] = __('File editing is allowed', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add define("DISALLOW_FILE_EDIT", true); to wp-config.php', KHC_TEXT_DOMAIN);
        }
        
        // File modifications
        if (!defined('DISALLOW_FILE_MODS') || DISALLOW_FILE_MODS !== true) {
            $score -= 5;
            $issues[] = __('File modifications are allowed', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Add define("DISALLOW_FILE_MODS", true); to wp-config.php', KHC_TEXT_DOMAIN);
        }
        
        // XML-RPC
        if ($this->is_xmlrpc_enabled()) {
            $score -= 5;
            $issues[] = __('XML-RPC is enabled', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Disable XML-RPC or add security filters', KHC_TEXT_DOMAIN);
        }
        
        // Directory listing
        if ($this->is_directory_listing_enabled()) {
            $score -= 10;
            $issues[] = __('Directory listing is enabled', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Disable directory listing by adding "Options -Indexes" to .htaccess', KHC_TEXT_DOMAIN);
        }
        
        // Security headers
        $headers = $this->check_security_headers();
        $missing_headers = array_keys(array_filter($headers, function($header) {
            return $header === false;
        }));
        
        if (!empty($missing_headers)) {
            $score -= count($missing_headers) * 5;
            $issues[] = sprintf(__('Missing security headers: %s', KHC_TEXT_DOMAIN), implode(', ', $missing_headers));
            $recommendations[] = __('Add missing security headers to .htaccess or server configuration', KHC_TEXT_DOMAIN);
        }
        
        // Admin username
        if ($this->is_default_admin_username()) {
            $score -= 15;
            $issues[] = __('Default "admin" username is being used', KHC_TEXT_DOMAIN);
            $recommendations[] = __('Change the admin username to a unique name', KHC_TEXT_DOMAIN);
        }
        
        // Weak passwords check (simplified)
        $weak_passwords = $this->check_weak_passwords();
        if ($weak_passwords > 0) {
            $score -= min($weak_passwords * 5, 20);
            $issues[] = sprintf(__('%d users have weak passwords', KHC_TEXT_DOMAIN), $weak_passwords);
            $recommendations[] = __('Enforce strong password policies', KHC_TEXT_DOMAIN);
        }
        
        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'recommendations' => $recommendations,
            'details' => [
                'wp_debug' => defined('WP_DEBUG') && WP_DEBUG === true,
                'file_edit' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT === true,
                'file_mods' => defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS === true,
                'xmlrpc' => $this->is_xmlrpc_enabled(),
                'directory_listing' => $this->is_directory_listing_enabled(),
                'security_headers' => $headers,
                'default_admin' => $this->is_default_admin_username(),
                'weak_passwords' => $weak_passwords
            ]
        ];
    }

    /**
     * Check if XML-RPC is enabled
     */
    private function is_xmlrpc_enabled()
    {
        return apply_filters('xmlrpc_enabled', true) && !defined('XMLRPC_REQUEST');
    }

    /**
     * Check if directory listing is enabled
     */
    private function is_directory_listing_enabled()
    {
        $test_url = home_url('wp-content/');
        $response = wp_remote_get($test_url, ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return strpos($body, 'Index of /') !== false;
    }

    /**
     * Check security headers
     */
    private function check_security_headers()
    {
        $headers = [
            'X-Frame-Options' => false,
            'X-Content-Type-Options' => false,
            'Content-Security-Policy' => false,
            'Referrer-Policy' => false,
            'Strict-Transport-Security' => false
        ];
        
        $response = wp_remote_get(home_url(), ['timeout' => 5]);
        
        if (!is_wp_error($response)) {
            $response_headers = wp_remote_retrieve_headers($response);
            
            foreach ($headers as $header => $value) {
                if (isset($response_headers[$header])) {
                    $headers[$header] = true;
                }
            }
        }
        
        return $headers;
    }

    /**
     * Check if default admin username exists
     */
    private function is_default_admin_username()
    {
        $user = get_user_by('login', 'admin');
        return $user !== false;
    }

    /**
     * Check weak passwords
     */
    private function check_weak_passwords()
    {
        $users = get_users();
        $weak_count = 0;
        $weak_passwords = ['123456', 'password', 'admin', '12345678', 'qwerty', 'abc123'];
        
        foreach ($users as $user) {
            // Simplified check - in reality you'd use password strength validation
            $password = get_user_meta($user->ID, 'khc_password_strength', true);
            if ($password && $password < 3) {
                $weak_count++;
            }
        }
        
        return $weak_count;
    }
}