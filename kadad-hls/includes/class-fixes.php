<?php
/**
 * Fixes Class
 * Handles one-click fixes
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Fixes
{
    /**
     * Available fixes
     */
    private $fixes = [
        'debug_mode' => 'fix_debug_mode',
        'optimize_db' => 'fix_optimize_db',
        'expired_transients' => 'fix_expired_transients',
        'revisions' => 'fix_revisions',
        'spam' => 'fix_spam',
        'gzip' => 'fix_gzip',
        'robots' => 'fix_robots',
        'sitemap' => 'fix_sitemap',
        'regenerate_thumbs' => 'fix_regenerate_thumbs',
        'optimize_autoload' => 'fix_optimize_autoload',
        'flush_cache' => 'fix_flush_cache',
        'flush_rewrite' => 'fix_flush_rewrite'
    ];

    /**
     * Apply fix
     */
    public function apply_fix($fix_type)
    {
        if (!isset($this->fixes[$fix_type])) {
            return false;
        }

        $method = $this->fixes[$fix_type];
        if (!method_exists($this, $method)) {
            return false;
        }

        return $this->$method();
    }

    /**
     * Fix debug mode
     */
    private function fix_debug_mode()
    {
        $wp_config = ABSPATH . 'wp-config.php';
        if (!file_exists($wp_config) || !is_writable($wp_config)) {
            return false;
        }

        $content = file_get_contents($wp_config);
        $content = preg_replace(
            "/define\(\s*['\"]WP_DEBUG['\"],\s*(true|false)\s*\);/i",
            "define('WP_DEBUG', false);",
            $content
        );
        
        return file_put_contents($wp_config, $content) !== false;
    }

    /**
     * Optimize database
     */
    private function fix_optimize_db()
    {
        global $wpdb;
        
        $tables = $wpdb->get_results(
            "SELECT table_name FROM information_schema.tables 
            WHERE table_schema = DATABASE()"
        );
        
        $success = true;
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table->table_name}");
            if ($result === false) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Delete expired transients
     */
    private function fix_expired_transients()
    {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM $wpdb->options 
            WHERE option_name LIKE '_transient_%' 
            AND option_value < NOW()"
        );
        
        $site_count = $wpdb->query(
            "DELETE FROM $wpdb->options 
            WHERE option_name LIKE '_site_transient_%' 
            AND option_value < NOW()"
        );
        
        return $count !== false && $site_count !== false;
    }

    /**
     * Delete revisions
     */
    private function fix_revisions()
    {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM $wpdb->posts 
            WHERE post_type = 'revision' 
            AND post_status = 'inherit'"
        );
        
        return $count !== false;
    }

    /**
     * Delete spam comments
     */
    private function fix_spam()
    {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM $wpdb->comments 
            WHERE comment_approved = 'spam'"
        );
        
        return $count !== false;
    }

    /**
     * Enable Gzip
     */
    private function fix_gzip()
    {
        $htaccess = ABSPATH . '.htaccess';
        if (!file_exists($htaccess) || !is_writable($htaccess)) {
            return false;
        }
        
        $content = file_get_contents($htaccess);
        $gzip_rules = "# Enable Gzip compression\n<IfModule mod_deflate.c>\nAddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript\n</IfModule>\n";
        
        if (strpos($content, 'mod_deflate') !== false) {
            return true;
        }
        
        return file_put_contents($htaccess, $gzip_rules . "\n" . $content) !== false;
    }

    /**
     * Generate robots.txt
     */
    private function fix_robots()
    {
        $robots_path = ABSPATH . 'robots.txt';
        $robots_content = "User-agent: *\nDisallow: /wp-admin/\nDisallow: /wp-includes/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: " . home_url('sitemap.xml');
        
        return file_put_contents($robots_path, $robots_content) !== false;
    }

    /**
     * Generate sitemap
     */
    private function fix_sitemap()
    {
        // In a real implementation, you'd generate a proper sitemap
        // For this example, we'll create a simple sitemap
        $sitemap_path = ABSPATH . 'sitemap.xml';
        
        $posts = get_posts([
            'post_type' => 'any',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($posts as $post) {
            $xml .= '<url>';
            $xml .= '<loc>' . get_permalink($post->ID) . '</loc>';
            $xml .= '<lastmod>' . get_the_modified_date('Y-m-d', $post->ID) . '</lastmod>';
            $xml .= '</url>';
        }
        
        $xml .= '</urlset>';
        
        return file_put_contents($sitemap_path, $xml) !== false;
    }

    /**
     * Regenerate thumbnails
     */
    private function fix_regenerate_thumbs()
    {
        global $wpdb;
        
        $attachments = $wpdb->get_results(
            "SELECT ID FROM $wpdb->posts 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            LIMIT 100"
        );
        
        $success = true;
        foreach ($attachments as $attachment) {
            $file = get_attached_file($attachment->ID);
            if ($file && file_exists($file)) {
                $metadata = wp_generate_attachment_metadata($attachment->ID, $file);
                if (!is_wp_error($metadata)) {
                    wp_update_attachment_metadata($attachment->ID, $metadata);
                } else {
                    $success = false;
                }
            }
        }
        
        return $success;
    }

    /**
     * Optimize autoload
     */
    private function fix_optimize_autoload()
    {
        global $wpdb;
        
        // Get options that are autoloaded but shouldn't be
        $options = $wpdb->get_results(
            "SELECT option_name, option_value FROM $wpdb->options 
            WHERE autoload = 'yes' 
            AND LENGTH(option_value) > 10000"
        );
        
        $success = true;
        foreach ($options as $option) {
            // Move to non-autoload
            $result = $wpdb->update(
                $wpdb->options,
                ['autoload' => 'no'],
                ['option_name' => $option->option_name]
            );
            if ($result === false) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Flush cache
     */
    private function fix_flush_cache()
    {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_%'");
        
        return true;
    }

    /**
     * Flush rewrite rules
     */
    private function fix_flush_rewrite()
    {
        flush_rewrite_rules(true);
        return true;
    }
}