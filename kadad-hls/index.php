<?php
/**
 * Plugin Name: Kadad Health Check
 * Plugin URI: https://kadad.ir
 * Description: Comprehensive WordPress and WooCommerce health analysis with automated checks, recommendations, and one-click fixes.
 * Version: 1.0.0
 * Author: Kadad
 * Author URI: https://kadad.ir
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kadad-health-check
 * Domain Path: /languages
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * Tested up to: 6.6
 * WC requires at least: 7.0
 * WC tested up to: 9.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KHC_VERSION', '1.0.0');
define('KHC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KHC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KHC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('KHC_TEXT_DOMAIN', 'kadad-health-check');

/**
 * Main plugin class
 */
final class KadadHealthCheck
{
    /**
     * @var KadadHealthCheck
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $modules = [];

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->load_dependencies();
        $this->init_modules();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Load dependencies
     */
    private function load_dependencies()
    {
        // Core classes
        require_once KHC_PLUGIN_DIR . 'includes/class-loader.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-admin.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-dashboard.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-scanner.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-security.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-performance.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-seo.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-system.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-report.php';
        require_once KHC_PLUGIN_DIR . 'includes/class-fixes.php';
        require_once KHC_PLUGIN_DIR . 'includes/helpers.php';
    }

    /**
     * Initialize modules
     */
    private function init_modules()
    {
        $this->modules = [
            'loader' => new KHC_Loader(),
            'admin' => new KHC_Admin(),
            'dashboard' => new KHC_Dashboard(),
            'scanner' => new KHC_Scanner(),
            'security' => new KHC_Security(),
            'performance' => new KHC_Performance(),
            'seo' => new KHC_Seo(),
            'system' => new KHC_System(),
            'report' => new KHC_Report(),
            'fixes' => new KHC_Fixes()
        ];
    }

    /**
     * Activation hook
     */
    public function activate()
    {
        // Create required database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('khc_daily_scan')) {
            wp_schedule_event(time(), 'daily', 'khc_daily_scan');
        }
        
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook
     */
    public function deactivate()
    {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('khc_daily_scan');
        wp_clear_scheduled_hook('khc_weekly_report');
        
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables()
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'khc_scans';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            scan_date datetime DEFAULT CURRENT_TIMESTAMP,
            total_score int(3) NOT NULL,
            security_score int(3) NOT NULL,
            performance_score int(3) NOT NULL,
            seo_score int(3) NOT NULL,
            system_score int(3) NOT NULL,
            woocommerce_score int(3) NOT NULL,
            images_score int(3) NOT NULL,
            database_score int(3) NOT NULL,
            updates_score int(3) NOT NULL,
            issues_found int(5) NOT NULL,
            issues_fixed int(5) DEFAULT 0,
            recommendations text,
            report_data longtext,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private function set_default_options()
    {
        $defaults = [
            'khc_scan_interval' => 'daily',
            'khc_dark_mode' => 'off',
            'khc_email_recipient' => get_option('admin_email'),
            'khc_auto_reports' => 'on',
            'khc_auto_cleanup' => 'off',
            'khc_last_scan' => '',
            'khc_scan_history' => []
        ];
        
        foreach ($defaults as $key => $value) {
            if (!get_option($key)) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Load text domain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            KHC_TEXT_DOMAIN,
            false,
            dirname(KHC_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Initialize
     */
    public function init()
    {
        // Register shortcodes
        add_shortcode('khc_dashboard', [$this, 'render_dashboard_shortcode']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        // Main menu
        add_menu_page(
            __('Kadad Health Check', KHC_TEXT_DOMAIN),
            __('Health Check', KHC_TEXT_DOMAIN),
            'manage_options',
            'kadad-health-check',
            [$this, 'render_dashboard_page'],
            'dash-heart',
            30
        );
        
        // Submenus
        add_submenu_page(
            'kadad-health-check',
            __('Dashboard', KHC_TEXT_DOMAIN),
            __('Dashboard', KHC_TEXT_DOMAIN),
            'manage_options',
            'kadad-health-check',
            [$this, 'render_dashboard_page']
        );
        
        add_submenu_page(
            'kadad-health-check',
            __('Scanner', KHC_TEXT_DOMAIN),
            __('Scanner', KHC_TEXT_DOMAIN),
            'manage_options',
            'kadad-health-check-scanner',
            [$this, 'render_scanner_page']
        );
        
        add_submenu_page(
            'kadad-health-check',
            __('Reports', KHC_TEXT_DOMAIN),
            __('Reports', KHC_TEXT_DOMAIN),
            'manage_options',
            'kadad-health-check-reports',
            [$this, 'render_reports_page']
        );
        
        add_submenu_page(
            'kadad-health-check',
            __('Settings', KHC_TEXT_DOMAIN),
            __('Settings', KHC_TEXT_DOMAIN),
            'manage_options',
            'kadad-health-check-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'kadad-health-check') === false) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'khc-admin',
            KHC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            KHC_VERSION
        );
        
        // Enqueue Google Fonts
        wp_enqueue_style(
            'khc-google-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
            [],
            null
        );
        
        // Enqueue Chart.js
        wp_enqueue_script(
            'khc-chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );
        
        // Enqueue main JS
        wp_enqueue_script(
            'khc-admin',
            KHC_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'khc-chart-js'],
            KHC_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('khc-admin', 'khc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('khc_nonce'),
            'i18n' => [
                'scanning' => __('Scanning...', KHC_TEXT_DOMAIN),
                'complete' => __('Scan Complete!', KHC_TEXT_DOMAIN),
                'error' => __('Error', KHC_TEXT_DOMAIN),
                'fixing' => __('Fixing...', KHC_TEXT_DOMAIN),
                'fixed' => __('Fixed!', KHC_TEXT_DOMAIN)
            ]
        ]);
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include_once KHC_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render scanner page
     */
    public function render_scanner_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include_once KHC_PLUGIN_DIR . 'admin/views/scanner.php';
    }

    /**
     * Render reports page
     */
    public function render_reports_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include_once KHC_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        include_once KHC_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Shortcode renderer
     */
    public function render_dashboard_shortcode($atts)
    {
        ob_start();
        include_once KHC_PLUGIN_DIR . 'admin/partials/shortcode-dashboard.php';
        return ob_get_clean();
    }
}

/**
 * Initialize the plugin
 */
function kadad_health_check_init()
{
    return KadadHealthCheck::get_instance();
}

// Start the plugin
add_action('plugins_loaded', 'kadad_health_check_init');