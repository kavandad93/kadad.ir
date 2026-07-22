<?php
/**
 * Scanner Class
 * Handles all health scanning functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Scanner
{
    /**
     * @var array
     */
    private $results = [];

    /**
     * @var array
     */
    private $issues = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->results = [
            'total_score' => 0,
            'security_score' => 0,
            'performance_score' => 0,
            'seo_score' => 0,
            'system_score' => 0,
            'woocommerce_score' => 0,
            'images_score' => 0,
            'database_score' => 0,
            'updates_score' => 0,
            'issues_found' => 0,
            'issues_fixed' => 0,
            'recommendations' => [],
            'details' => []
        ];
    }

    /**
     * Run full scan
     */
    public function run_full_scan()
    {
        $this->scan_system();
        $this->scan_security();
        $this->scan_performance();
        $this->scan_seo();
        $this->scan_woocommerce();
        $this->scan_images();
        $this->scan_database();
        $this->scan_updates();
        
        $this->calculate_total_score();
        $this->save_results();
        
        return $this->results;
    }

    /**
     * Scan system
     */
    private function scan_system()
    {
        $system = new KHC_System();
        $results = $system->check_all();
        
        $this->results['system_score'] = $results['score'];
        $this->results['details']['system'] = $results;
        $this->add_issues($results['issues']);
        $this->add_recommendations($results['recommendations']);
    }

    /**
     * Scan security
     */
    private function scan_security()
    {
        $security = new KHC_Security();
        $results = $security->check_all();
        
        $this->results['security_score'] = $results['score'];
        $this->results['details']['security'] = $results;
        $this->add_issues($results['issues']);
        $this->add_recommendations($results['recommendations']);
    }

    /**
     * Scan performance
     */
    private function scan_performance()
    {
        $performance = new KHC_Performance();
        $results = $performance->check_all();
        
        $this->results['performance_score'] = $results['score'];
        $this->results['details']['performance'] = $results;
        $this->add_issues($results['issues']);
        $this->add_recommendations($results['recommendations']);
    }

    /**
     * Scan SEO
     */
    private function scan_seo()
    {
        $seo = new KHC_Seo();
        $results = $seo->check_all();
        
        $this->results['seo_score'] = $results['score'];
        $this->results['details']['seo'] = $results;
        $this->add_issues($results['issues']);
        $this->add_recommendations($results['recommendations']);
    }

    /**
     * Scan WooCommerce
     */
    private function scan_woocommerce()
    {
        if (class_exists('WooCommerce')) {
            $woocommerce = new KHC_WooCommerce();
            $results = $woocommerce->check_all();
            
            $this->results['woocommerce_score'] = $results['score'];
            $this->results['details']['woocommerce'] = $results;
            $this->add_issues($results['issues']);
            $this->add_recommendations($results['recommendations']);
        } else {
            $this->results['woocommerce_score'] = 0;
        }
    }

    /**
     * Scan images
     */
    private function scan_images()
    {
        // Image scan implementation
        $issues = [];
        $score = 100;
        
        // Check for images without ALT text
        $alt_issues = $this->check_images_alt();
        if ($alt_issues > 0) {
            $score -= min($alt_issues * 5, 30);
            $issues[] = sprintf(__('%d images missing ALT text', KHC_TEXT_DOMAIN), $alt_issues);
        }
        
        // Check for large images
        $large_images = $this->check_large_images();
        if ($large_images > 0) {
            $score -= min($large_images * 3, 20);
            $issues[] = sprintf(__('%d images are too large (> 500KB)', KHC_TEXT_DOMAIN), $large_images);
        }
        
        $this->results['images_score'] = max(0, $score);
        $this->add_issues($issues);
    }

    /**
     * Check images for ALT text
     */
    private function check_images_alt()
    {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->posts 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            AND ID NOT IN (
                SELECT post_id FROM $wpdb->postmeta 
                WHERE meta_key = '_wp_attachment_image_alt' 
                AND meta_value != ''
            )"
        );
        
        return intval($count);
    }

    /**
     * Check large images
     */
    private function check_large_images()
    {
        global $wpdb;
        
        $count = 0;
        $attachments = $wpdb->get_results(
            "SELECT ID, guid FROM $wpdb->posts 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            LIMIT 100"
        );
        
        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $size = filesize($file_path) / 1024; // KB
                if ($size > 500) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Scan database
     */
    private function scan_database()
    {
        global $wpdb;
        
        $issues = [];
        $score = 100;
        
        // Check database size
        $size = $this->get_database_size();
        if ($size > 100) {
            $score -= 10;
            $issues[] = sprintf(__('Database size is large (%d MB)', KHC_TEXT_DOMAIN), $size);
        }
        
        // Check for overhead
        $overhead = $this->get_database_overhead();
        if ($overhead > 10) {
            $score -= 5;
            $issues[] = sprintf(__('Database has %d MB overhead', KHC_TEXT_DOMAIN), $overhead);
        }
        
        // Check for orphan options
        $orphans = $this->check_orphan_options();
        if ($orphans > 0) {
            $score -= min($orphans, 15);
            $issues[] = sprintf(__('%d orphan options found', KHC_TEXT_DOMAIN), $orphans);
        }
        
        $this->results['database_score'] = max(0, $score);
        $this->add_issues($issues);
    }

    /**
     * Get database size
     */
    private function get_database_size()
    {
        global $wpdb;
        
        $size = $wpdb->get_var(
            "SELECT SUM(data_length + index_length) / 1024 / 1024 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()"
        );
        
        return floatval($size);
    }

    /**
     * Get database overhead
     */
    private function get_database_overhead()
    {
        global $wpdb;
        
        $overhead = $wpdb->get_var(
            "SELECT SUM(data_free) / 1024 / 1024 
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()"
        );
        
        return floatval($overhead);
    }

    /**
     * Check orphan options
     */
    private function check_orphan_options()
    {
        global $wpdb;
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $wpdb->options 
            WHERE option_name LIKE '_transient_%'
            OR option_name LIKE '_site_transient_%'"
        );
        
        return intval($count);
    }

    /**
     * Scan updates
     */
    private function scan_updates()
    {
        $issues = [];
        $score = 100;
        
        // Check WordPress updates
        if ($this->has_wordpress_update()) {
            $score -= 20;
            $issues[] = __('WordPress update available', KHC_TEXT_DOMAIN);
        }
        
        // Check plugin updates
        $plugin_updates = $this->get_plugin_updates();
        if ($plugin_updates > 0) {
            $score -= min($plugin_updates * 5, 30);
            $issues[] = sprintf(__('%d plugins need updates', KHC_TEXT_DOMAIN), $plugin_updates);
        }
        
        // Check theme updates
        $theme_updates = $this->get_theme_updates();
        if ($theme_updates > 0) {
            $score -= min($theme_updates * 5, 20);
            $issues[] = sprintf(__('%d themes need updates', KHC_TEXT_DOMAIN), $theme_updates);
        }
        
        $this->results['updates_score'] = max(0, $score);
        $this->add_issues($issues);
    }

    /**
     * Check WordPress update
     */
    private function has_wordpress_update()
    {
        $current = get_site_transient('update_core');
        if (isset($current->updates) && !empty($current->updates)) {
            return true;
        }
        return false;
    }

    /**
     * Get plugin updates
     */
    private function get_plugin_updates()
    {
        $update_plugins = get_site_transient('update_plugins');
        if (isset($update_plugins->response) && is_array($update_plugins->response)) {
            return count($update_plugins->response);
        }
        return 0;
    }

    /**
     * Get theme updates
     */
    private function get_theme_updates()
    {
        $update_themes = get_site_transient('update_themes');
        if (isset($update_themes->response) && is_array($update_themes->response)) {
            return count($update_themes->response);
        }
        return 0;
    }

    /**
     * Add issues
     */
    private function add_issues($issues)
    {
        if (is_array($issues)) {
            $this->results['issues_found'] += count($issues);
            $this->issues = array_merge($this->issues, $issues);
        }
    }

    /**
     * Add recommendations
     */
    private function add_recommendations($recommendations)
    {
        if (is_array($recommendations)) {
            $this->results['recommendations'] = array_merge(
                $this->results['recommendations'],
                $recommendations
            );
        }
    }

    /**
     * Calculate total score
     */
    private function calculate_total_score()
    {
        $scores = [
            $this->results['security_score'],
            $this->results['performance_score'],
            $this->results['seo_score'],
            $this->results['system_score'],
            $this->results['woocommerce_score'],
            $this->results['images_score'],
            $this->results['database_score'],
            $this->results['updates_score']
        ];
        
        // Filter out zero scores (for WooCommerce if not active)
        $scores = array_filter($scores, function($score) {
            return $score > 0;
        });
        
        if (empty($scores)) {
            $this->results['total_score'] = 0;
            return;
        }
        
        $this->results['total_score'] = round(array_sum($scores) / count($scores));
    }

    /**
     * Save results
     */
    private function save_results()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        $data = [
            'scan_date' => current_time('mysql'),
            'total_score' => $this->results['total_score'],
            'security_score' => $this->results['security_score'],
            'performance_score' => $this->results['performance_score'],
            'seo_score' => $this->results['seo_score'],
            'system_score' => $this->results['system_score'],
            'woocommerce_score' => $this->results['woocommerce_score'],
            'images_score' => $this->results['images_score'],
            'database_score' => $this->results['database_score'],
            'updates_score' => $this->results['updates_score'],
            'issues_found' => $this->results['issues_found'],
            'issues_fixed' => $this->results['issues_fixed'],
            'recommendations' => wp_json_encode($this->results['recommendations']),
            'report_data' => wp_json_encode($this->results['details'])
        ];
        
        $wpdb->insert($table_name, $data);
        $scan_id = $wpdb->insert_id;
        
        if ($scan_id) {
            update_option('khc_last_scan', $scan_id);
        }
        
        // Store critical issues
        $critical_issues = array_filter($this->issues, function($issue) {
            return strpos(strtolower($issue), 'critical') !== false;
        });
        update_option('khc_critical_issues', $critical_issues);
    }

    /**
     * Get scan results
     */
    public function get_results()
    {
        return $this->results;
    }

    /**
     * Get scan by ID
     */
    public function get_scan($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        $scan = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($scan) {
            $scan['recommendations'] = json_decode($scan['recommendations'], true);
            $scan['report_data'] = json_decode($scan['report_data'], true);
        }
        
        return $scan;
    }
}