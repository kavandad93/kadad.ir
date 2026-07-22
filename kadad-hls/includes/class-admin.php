<?php
/**
 * Admin Class
 * Handles admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Admin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        add_action('admin_notices', [$this, 'show_admin_notices']);
        add_action('wp_ajax_khc_run_scan', [$this, 'ajax_run_scan']);
        add_action('wp_ajax_khc_get_scan_history', [$this, 'ajax_get_scan_history']);
        add_action('wp_ajax_khc_apply_fix', [$this, 'ajax_apply_fix']);
        add_action('wp_ajax_khc_generate_report', [$this, 'ajax_generate_report']);
        add_action('wp_ajax_khc_export_data', [$this, 'ajax_export_data']);
        add_action('wp_ajax_khc_save_settings', [$this, 'ajax_save_settings']);
    }

    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar)
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node([
            'id' => 'khc-health-check',
            'title' => '<span class="ab-icon dashicons-heart"></span> ' . __('Health', KHC_TEXT_DOMAIN),
            'href' => admin_url('admin.php?page=kadad-health-check'),
            'meta' => ['title' => __('Health Check', KHC_TEXT_DOMAIN)]
        ]);

        $wp_admin_bar->add_node([
            'parent' => 'khc-health-check',
            'id' => 'khc-run-scan',
            'title' => __('Run Scan', KHC_TEXT_DOMAIN),
            'href' => '#',
            'meta' => ['onclick' => 'khc_run_scan(); return false;']
        ]);
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $last_scan = get_option('khc_last_scan');
        if (empty($last_scan)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . __('<strong>Kadad Health Check:</strong> You haven\'t performed any health scans yet. ', KHC_TEXT_DOMAIN);
            echo '<a href="' . admin_url('admin.php?page=kadad-health-check-scanner') . '">' . __('Run your first scan now!', KHC_TEXT_DOMAIN) . '</a>';
            echo '</p></div>';
        }

        // Show critical issues
        $critical_issues = get_option('khc_critical_issues', []);
        if (!empty($critical_issues)) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . __('Kadad Health Check:', KHC_TEXT_DOMAIN) . '</strong> ';
            echo sprintf(__('Found %d critical issues that need immediate attention.', KHC_TEXT_DOMAIN), count($critical_issues));
            echo ' <a href="' . admin_url('admin.php?page=kadad-health-check') . '">' . __('View Details', KHC_TEXT_DOMAIN) . '</a>';
            echo '</p></div>';
        }
    }

    /**
     * AJAX: Run scan
     */
    public function ajax_run_scan()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $scanner = new KHC_Scanner();
        $results = $scanner->run_full_scan();

        if ($results) {
            wp_send_json_success([
                'message' => __('Scan completed successfully', KHC_TEXT_DOMAIN),
                'data' => $results
            ]);
        } else {
            wp_send_json_error(['message' => __('Scan failed', KHC_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Get scan history
     */
    public function ajax_get_scan_history()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'khc_scans';
        
        $history = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY scan_date DESC LIMIT %d",
                30
            )
        );

        wp_send_json_success(['data' => $history]);
    }

    /**
     * AJAX: Apply fix
     */
    public function ajax_apply_fix()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $fix_type = sanitize_text_field($_POST['fix_type']);
        $fixes = new KHC_Fixes();
        
        $result = $fixes->apply_fix($fix_type);

        if ($result) {
            wp_send_json_success([
                'message' => __('Fix applied successfully', KHC_TEXT_DOMAIN),
                'data' => $result
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to apply fix', KHC_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Generate report
     */
    public function ajax_generate_report()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $report_id = intval($_POST['report_id']);
        $report = new KHC_Report();
        
        $result = $report->generate_report($report_id);

        if ($result) {
            wp_send_json_success([
                'message' => __('Report generated successfully', KHC_TEXT_DOMAIN),
                'data' => $result
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to generate report', KHC_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Export data
     */
    public function ajax_export_data()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $format = sanitize_text_field($_POST['format']);
        $report = new KHC_Report();
        
        $result = $report->export_data($format);

        if ($result) {
            wp_send_json_success([
                'message' => __('Data exported successfully', KHC_TEXT_DOMAIN),
                'data' => $result
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to export data', KHC_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings()
    {
        check_ajax_referer('khc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', KHC_TEXT_DOMAIN)]);
        }

        $settings = [
            'scan_interval' => sanitize_text_field($_POST['scan_interval']),
            'dark_mode' => sanitize_text_field($_POST['dark_mode']),
            'email_recipient' => sanitize_email($_POST['email_recipient']),
            'auto_reports' => sanitize_text_field($_POST['auto_reports']),
            'auto_cleanup' => sanitize_text_field($_POST['auto_cleanup'])
        ];

        foreach ($settings as $key => $value) {
            update_option('khc_' . $key, $value);
        }

        wp_send_json_success(['message' => __('Settings saved successfully', KHC_TEXT_DOMAIN)]);
    }
}