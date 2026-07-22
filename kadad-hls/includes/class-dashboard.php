<?php
/**
 * Dashboard Class
 * Handles dashboard functionality and data display
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Dashboard
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_khc_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
        add_action('wp_ajax_khc_get_chart_data', [$this, 'ajax_get_chart_data']);
    }

    /**
     * Get dashboard data
     */
    public function get_dashboard_data()
    {
        $last_scan = get_option('khc_last_scan');
        $scan_data = [];

        if ($last_scan) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'khc_scans';
            
            $scan_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %d",
                    $last_scan
                ),
                ARRAY_A
            );
        }

        // Get counts
        $scan_count = $this->get_scan_count();
        $issues_count = $this->get_issues_count();
        $fixed_count = $this->get_fixed_count();

        return [
            'last_scan' => $scan_data,
            'scan_count' => $scan_count,
            'issues_count' => $issues_count,
            'fixed_count' => $fixed_count,
            'scores' => $this->calculate_scores($scan_data),
            'status' => $this->get_status($scan_data['total_score'] ?? 0)
        ];
    }

    /**
     * Get scan count
     */
    private function get_scan_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }

    /**
     * Get issues count
     */
    private function get_issues_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        return $wpdb->get_var("SELECT SUM(issues_found) FROM $table_name");
    }

    /**
     * Get fixed count
     */
    private function get_fixed_count()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        return $wpdb->get_var("SELECT SUM(issues_fixed) FROM $table_name");
    }

    /**
     * Calculate scores
     */
    private function calculate_scores($scan_data)
    {
        if (empty($scan_data)) {
            return [
                'total' => 0,
                'security' => 0,
                'performance' => 0,
                'seo' => 0,
                'system' => 0,
                'woocommerce' => 0,
                'images' => 0,
                'database' => 0,
                'updates' => 0
            ];
        }

        return [
            'total' => intval($scan_data['total_score']),
            'security' => intval($scan_data['security_score']),
            'performance' => intval($scan_data['performance_score']),
            'seo' => intval($scan_data['seo_score']),
            'system' => intval($scan_data['system_score']),
            'woocommerce' => intval($scan_data['woocommerce_score']),
            'images' => intval($scan_data['images_score']),
            'database' => intval($scan_data['database_score']),
            'updates' => intval($scan_data['updates_score'])
        ];
    }

    /**
     * Get status
     */
    private function get_status($score)
    {
        if ($score >= 95) {
            return ['label' => __('Excellent', KHC_TEXT_DOMAIN), 'class' => 'excellent', 'color' => '#10b981'];
        } elseif ($score >= 80) {
            return ['label' => __('Good', KHC_TEXT_DOMAIN), 'class' => 'good', 'color' => '#3b82f6'];
        } elseif ($score >= 60) {
            return ['label' => __('Needs Attention', KHC_TEXT_DOMAIN), 'class' => 'needs-attention', 'color' => '#f59e0b'];
        } else {
            return ['label' => __('Critical', KHC_TEXT_DOMAIN), 'class' => 'critical', 'color' => '#ef4444'];
        }
    }

    /**
     * Get chart data
     */
    public function get_chart_data($type = 'trend')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        $data = [];
        
        if ($type === 'trend') {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT scan_date, total_score, security_score, performance_score, seo_score 
                    FROM $table_name 
                    ORDER BY scan_date DESC 
                    LIMIT %d",
                    30
                ),
                ARRAY_A
            );
            
            $data = [
                'labels' => array_map(function($row) {
                    return date('M d', strtotime($row['scan_date']));
                }, array_reverse($results)),
                'total' => array_map(function($row) {
                    return intval($row['total_score']);
                }, array_reverse($results)),
                'security' => array_map(function($row) {
                    return intval($row['security_score']);
                }, array_reverse($results)),
                'performance' => array_map(function($row) {
                    return intval($row['performance_score']);
                }, array_reverse($results)),
                'seo' => array_map(function($row) {
                    return intval($row['seo_score']);
                }, array_reverse($results))
            ];
        } elseif ($type === 'categories') {
            $latest = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table_name ORDER BY scan_date DESC LIMIT 1"
                ),
                ARRAY_A
            );
            
            if ($latest) {
                $data = [
                    'labels' => [
                        __('Security', KHC_TEXT_DOMAIN),
                        __('Performance', KHC_TEXT_DOMAIN),
                        __('SEO', KHC_TEXT_DOMAIN),
                        __('System', KHC_TEXT_DOMAIN),
                        __('WooCommerce', KHC_TEXT_DOMAIN),
                        __('Images', KHC_TEXT_DOMAIN),
                        __('Database', KHC_TEXT_DOMAIN),
                        __('Updates', KHC_TEXT_DOMAIN)
                    ],
                    'values' => [
                        intval($latest['security_score']),
                        intval($latest['performance_score']),
                        intval($latest['seo_score']),
                        intval($latest['system_score']),
                        intval($latest['woocommerce_score']),
                        intval($latest['images_score']),
                        intval($latest['database_score']),
                        intval($latest['updates_score'])
                    ],
                    'colors' => [
                        '#ef4444', '#f59e0b', '#3b82f6', '#10b981',
                        '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'
                    ]
                ];
            }
        }
        
        return $data;
    }

    /**
     * AJAX: Get dashboard data
     */
    public function ajax_get_dashboard_data()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $data = $this->get_dashboard_data();
        wp_send_json_success($data);
    }

    /**
     * AJAX: Get chart data
     */
    public function ajax_get_chart_data()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'trend';
        $data = $this->get_chart_data($type);
        
        wp_send_json_success($data);
    }
}