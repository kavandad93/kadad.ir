<?php
/**
 * Plugin Name: Kadad Coin
 * Plugin URI: https://kadad.ir
 * Description: سیستم مدیریت سکه برای کاربران وردپرس
 * Version: 1.0.0
 * Author: Kadad Co
 * Text Domain: kadad-coin
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌های افزونه
define('KADAD_COIN_VERSION', '1.0.0');
define('KADAD_COIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KADAD_COIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KADAD_COIN_META_KEY', 'kadad_coin');

/**
 * کلاس اصلی افزونه Kadad Coin
 */
class KadadCoin {
    
    /**
     * نمونه Singleton کلاس
     *
     * @var KadadCoin|null
     */
    private static $instance = null;
    
    /**
     * دریافت نمونه Singleton
     *
     * @return KadadCoin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * سازنده کلاس - راه‌اندازی هوک‌ها
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * مقداردهی اولیه هوک‌ها
     */
    private function init_hooks() {
        // بارگذاری تنظیمات
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // بررسی فعال بودن افزونه
        if (!$this->is_plugin_active()) {
            return;
        }
        
        // افزودن منوی مدیریت
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // فعال‌سازی Settings API
        add_action('admin_init', array($this, 'register_settings'));
        
        // هوک ثبت‌نام کاربر
        add_action('user_register', array($this, 'handle_user_registration'), 10, 1);
        
        // هوک ورود کاربر
        add_action('wp_login', array($this, 'handle_daily_login'), 10, 2);
        
        // هوک تکمیل سفارش ووکامرس
        add_action('woocommerce_order_status_completed', array($this, 'handle_woocommerce_order'), 10, 1);
        
        // هوک تأیید دیدگاه
        add_action('comment_post', array($this, 'handle_comment_approval'), 10, 3);
        
        // ثبت شورت‌کد
        add_shortcode('kadad_coin', array($this, 'render_shortcode'));
        
        // بارگذاری فایل تنظیمات
        require_once KADAD_COIN_PLUGIN_DIR . 'settings.php';
    }
    
    /**
     * بارگذاری فایل‌های زبان
     */
    public function load_textdomain() {
        load_plugin_textdomain('kadad-coin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * بررسی فعال بودن افزونه
     *
     * @return bool
     */
    private function is_plugin_active() {
        $options = get_option('kadad_coin_settings', array());
        return isset($options['is_active']) && $options['is_active'] === '1';
    }
    
    /**
     * افزودن منوی مدیریت
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Kadad Coin', 'kadad-coin'),
            __('Kadad Coin', 'kadad-coin'),
            'manage_options',
            'kadad-coin',
            array($this, 'render_settings_page'),
            'dashicons-money-alt',
            30
        );
    }
    
    /**
     * رندر صفحه تنظیمات
     */
    public function render_settings_page() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die(__('شما دسترسی لازم برای مشاهده این صفحه را ندارید.', 'kadad-coin'));
        }
        
        // بارگذاری فایل تنظیمات
        if (file_exists(KADAD_COIN_PLUGIN_DIR . 'settings.php')) {
            include KADAD_COIN_PLUGIN_DIR . 'settings.php';
        }
    }
    
    /**
     * ثبت تنظیمات با Settings API
     */
    public function register_settings() {
        register_setting(
            'kadad_coin_settings_group',
            'kadad_coin_settings',
            array($this, 'sanitize_settings')
        );
        
        // بخش اصلی تنظیمات
        add_settings_section(
            'kadad_coin_main_section',
            __('تنظیمات اصلی', 'kadad-coin'),
            array($this, 'render_section_description'),
            'kadad-coin'
        );
        
        // فیلدهای تنظیمات
        $fields = array(
            'is_active' => __('فعال/غیرفعال', 'kadad-coin'),
            'coin_name' => __('نام سکه', 'kadad-coin'),
            'coin_icon' => __('آیکون سکه', 'kadad-coin'),
            'register_amount' => __('مقدار سکه ثبت‌نام', 'kadad-coin'),
            'daily_login_amount' => __('مقدار سکه ورود روزانه', 'kadad-coin'),
            'woocommerce_amount' => __('مقدار سکه خرید ووکامرس', 'kadad-coin'),
            'comment_amount' => __('مقدار سکه ثبت دیدگاه', 'kadad-coin')
        );
        
        foreach ($fields as $field_id => $field_label) {
            add_settings_field(
                $field_id,
                $field_label,
                array($this, 'render_field'),
                'kadad-coin',
                'kadad_coin_main_section',
                array(
                    'label_for' => $field_id,
                    'field_type' => $this->get_field_type($field_id),
                    'field_id' => $field_id
                )
            );
        }
    }
    
    /**
     * تعیین نوع فیلد بر اساس شناسه
     *
     * @param string $field_id
     * @return string
     */
    private function get_field_type($field_id) {
        $checkbox_fields = array('is_active');
        return in_array($field_id, $checkbox_fields) ? 'checkbox' : 'text';
    }
    
    /**
     * رندر توضیحات بخش
     */
    public function render_section_description() {
        echo '<p>' . __('تنظیمات مربوط به سکه کاربران را در این بخش پیکربندی کنید.', 'kadad-coin') . '</p>';
    }
    
    /**
     * رندر فیلدهای تنظیمات
     *
     * @param array $args
     */
    public function render_field($args) {
        $options = get_option('kadad_coin_settings', array());
        $field_id = $args['field_id'];
        $field_type = $args['field_type'];
        $value = isset($options[$field_id]) ? $options[$field_id] : '';
        
        if ($field_type === 'checkbox') {
            $checked = checked('1', $value, false);
            echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="kadad_coin_settings[' . esc_attr($field_id) . ']" value="1" ' . $checked . ' />';
        } else {
            echo '<input type="text" id="' . esc_attr($field_id) . '" name="kadad_coin_settings[' . esc_attr($field_id) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
            
            // توضیحات ویژه برای برخی فیلدها
            $descriptions = array(
                'coin_name' => __('مثال: طلا', 'kadad-coin'),
                'coin_icon' => __('مثال: 🪙 یا مسیر تصویر', 'kadad-coin'),
                'register_amount' => __('عدد صحیح وارد کنید', 'kadad-coin'),
                'daily_login_amount' => __('عدد صحیح وارد کنید', 'kadad-coin'),
                'woocommerce_amount' => __('عدد صحیح وارد کنید', 'kadad-coin'),
                'comment_amount' => __('عدد صحیح وارد کنید', 'kadad-coin')
            );
            
            if (isset($descriptions[$field_id])) {
                echo '<p class="description">' . esc_html($descriptions[$field_id]) . '</p>';
            }
        }
    }
    
    /**
     * پالایش تنظیمات قبل از ذخیره
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // پالایش هر فیلد
        $sanitized['is_active'] = isset($input['is_active']) ? '1' : '0';
        $sanitized['coin_name'] = isset($input['coin_name']) ? sanitize_text_field($input['coin_name']) : 'سکه';
        $sanitized['coin_icon'] = isset($input['coin_icon']) ? sanitize_text_field($input['coin_icon']) : '🪙';
        $sanitized['register_amount'] = isset($input['register_amount']) ? intval($input['register_amount']) : 0;
        $sanitized['daily_login_amount'] = isset($input['daily_login_amount']) ? intval($input['daily_login_amount']) : 0;
        $sanitized['woocommerce_amount'] = isset($input['woocommerce_amount']) ? intval($input['woocommerce_amount']) : 0;
        $sanitized['comment_amount'] = isset($input['comment_amount']) ? intval($input['comment_amount']) : 0;
        
        // اطمینان از غیرمنفی بودن مقادیر
        $sanitized['register_amount'] = max(0, $sanitized['register_amount']);
        $sanitized['daily_login_amount'] = max(0, $sanitized['daily_login_amount']);
        $sanitized['woocommerce_amount'] = max(0, $sanitized['woocommerce_amount']);
        $sanitized['comment_amount'] = max(0, $sanitized['comment_amount']);
        
        return $sanitized;
    }
    
    /**
     * دریافت موجودی سکه کاربر
     *
     * @param int $user_id
     * @return int
     */
    public function get_coin($user_id) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return 0;
        }
        
        $amount = get_user_meta($user_id, KADAD_COIN_META_KEY, true);
        return $amount !== '' ? intval($amount) : 0;
    }
    
    /**
     * تنظیم موجودی سکه کاربر
     *
     * @param int $user_id
     * @param int $amount
     * @return bool
     */
    public function set_coin($user_id, $amount) {
        $user_id = absint($user_id);
        if ($user_id === 0) {
            return false;
        }
        
        // جلوگیری از منفی شدن
        $amount = max(0, intval($amount));
        return update_user_meta($user_id, KADAD_COIN_META_KEY, $amount);
    }
    
    /**
     * افزایش موجودی سکه کاربر
     *
     * @param int $user_id
     * @param int $amount
     * @return bool
     */
    public function add_coin($user_id, $amount) {
        $user_id = absint($user_id);
        if ($user_id === 0 || intval($amount) <= 0) {
            return false;
        }
        
        $current = $this->get_coin($user_id);
        $new_amount = $current + intval($amount);
        return $this->set_coin($user_id, $new_amount);
    }
    
    /**
     * کاهش موجودی سکه کاربر
     *
     * @param int $user_id
     * @param int $amount
     * @return bool
     */
    public function remove_coin($user_id, $amount) {
        $user_id = absint($user_id);
        if ($user_id === 0 || intval($amount) <= 0) {
            return false;
        }
        
        $current = $this->get_coin($user_id);
        $new_amount = $current - intval($amount);
        
        // جلوگیری از منفی شدن
        if ($new_amount < 0) {
            $new_amount = 0;
        }
        
        return $this->set_coin($user_id, $new_amount);
    }
    
    /**
     * مدیریت ثبت‌نام کاربر
     *
     * @param int $user_id
     */
    public function handle_user_registration($user_id) {
        // بررسی اینکه آیا کاربر قبلاً سکه ثبت‌نام دریافت کرده است
        $already_received = get_user_meta($user_id, 'kadad_coin_registration_reward', true);
        if ($already_received) {
            return;
        }
        
        $options = get_option('kadad_coin_settings', array());
        $amount = isset($options['register_amount']) ? intval($options['register_amount']) : 0;
        
        if ($amount > 0) {
            $this->add_coin($user_id, $amount);
            // ثبت دریافت پاداش ثبت‌نام
            update_user_meta($user_id, 'kadad_coin_registration_reward', '1');
        }
    }
    
    /**
     * مدیریت ورود روزانه کاربر
     *
     * @param string $user_login
     * @param WP_User $user
     */
    public function handle_daily_login($user_login, $user) {
        $user_id = $user->ID;
        
        // بررسی اینکه آیا امروز قبلاً دریافت کرده است
        $last_daily_login = get_user_meta($user_id, 'kadad_coin_last_daily_login', true);
        $today = date('Y-m-d');
        
        if ($last_daily_login === $today) {
            return;
        }
        
        $options = get_option('kadad_coin_settings', array());
        $amount = isset($options['daily_login_amount']) ? intval($options['daily_login_amount']) : 0;
        
        if ($amount > 0) {
            $this->add_coin($user_id, $amount);
            update_user_meta($user_id, 'kadad_coin_last_daily_login', $today);
        }
    }
    
    /**
     * مدیریت تکمیل سفارش ووکامرس
     *
     * @param int $order_id
     */
    public function handle_woocommerce_order($order_id) {
        // بررسی نصب و فعال بودن ووکامرس
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        
        $user_id = $order->get_customer_id();
        if ($user_id === 0) {
            return;
        }
        
        // بررسی اینکه آیا این سفارش قبلاً سکه دریافت کرده است
        $already_rewarded = get_post_meta($order_id, 'kadad_coin_order_rewarded', true);
        if ($already_rewarded) {
            return;
        }
        
        $options = get_option('kadad_coin_settings', array());
        $amount = isset($options['woocommerce_amount']) ? intval($options['woocommerce_amount']) : 0;
        
        if ($amount > 0) {
            $this->add_coin($user_id, $amount);
            update_post_meta($order_id, 'kadad_coin_order_rewarded', '1');
        }
    }
    
    /**
     * مدیریت تأیید دیدگاه
     *
     * @param int $comment_id
     * @param int $comment_approved
     * @param array $commentdata
     */
    public function handle_comment_approval($comment_id, $comment_approved, $commentdata) {
        // فقط در صورت تأیید دیدگاه
        if ($comment_approved !== 1) {
            return;
        }
        
        // بررسی نصب و فعال بودن ووکامرس
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // بررسی اینکه آیا کاربر وارد شده است
        if (!is_user_logged_in()) {
            return;
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            return;
        }
        
        $user_id = $comment->user_id;
        $post_id = $comment->comment_post_ID;
        
        // بررسی اینکه آیا این دیدگاه قبلاً سکه دریافت کرده است
        $already_rewarded = get_comment_meta($comment_id, 'kadad_coin_comment_rewarded', true);
        if ($already_rewarded) {
            return;
        }
        
        // بررسی اینکه آیا کاربر این محصول را خریداری کرده است
        if (!$this->has_user_purchased_product($user_id, $post_id)) {
            return;
        }
        
        $options = get_option('kadad_coin_settings', array());
        $amount = isset($options['comment_amount']) ? intval($options['comment_amount']) : 0;
        
        if ($amount > 0) {
            $this->add_coin($user_id, $amount);
            update_comment_meta($comment_id, 'kadad_coin_comment_rewarded', '1');
        }
    }
    
    /**
     * بررسی اینکه آیا کاربر یک محصول خاص را خریداری کرده است
     *
     * @param int $user_id
     * @param int $product_id
     * @return bool
     */
    private function has_user_purchased_product($user_id, $product_id) {
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        $orders = wc_get_orders(array(
            'customer' => $user_id,
            'status' => array('completed', 'processing'),
            'limit' => -1
        ));
        
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                if ($item->get_product_id() == $product_id) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * رندر شورت‌کد
     *
     * @param array $atts
     * @return string
     */
    public function render_shortcode($atts) {
        // تنظیمات شورت‌کد
        $atts = shortcode_atts(array(
            'show_icon' => 'true',
            'show_name' => 'true'
        ), $atts);
        
        // بررسی ورود کاربر
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('لطفاً وارد حساب کاربری خود شوید تا موجودی سکه خود را مشاهده کنید.', 'kadad-coin') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $amount = $this->get_coin($user_id);
        
        $options = get_option('kadad_coin_settings', array());
        $coin_name = isset($options['coin_name']) ? $options['coin_name'] : __('سکه', 'kadad-coin');
        $coin_icon = isset($options['coin_icon']) ? $options['coin_icon'] : '🪙';
        
        $output = '<div class="kadad-coin-display">';
        
        if ($atts['show_icon'] === 'true') {
            $output .= '<span class="kadad-coin-icon">' . esc_html($coin_icon) . '</span> ';
        }
        
        $output .= '<span class="kadad-coin-amount">' . esc_html($amount) . '</span>';
        
        if ($atts['show_name'] === 'true') {
            $output .= ' <span class="kadad-coin-name">' . esc_html($coin_name) . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
}

// راه‌اندازی افزونه
function kadad_coin_init() {
    return KadadCoin::get_instance();
}
add_action('plugins_loaded', 'kadad_coin_init');

// توابع دسترسی عمومی به سکه
function kadad_coin_get($user_id) {
    $instance = KadadCoin::get_instance();
    return $instance->get_coin($user_id);
}

function kadad_coin_set($user_id, $amount) {
    $instance = KadadCoin::get_instance();
    return $instance->set_coin($user_id, $amount);
}

function kadad_coin_add($user_id, $amount) {
    $instance = KadadCoin::get_instance();
    return $instance->add_coin($user_id, $amount);
}

function kadad_coin_remove($user_id, $amount) {
    $instance = KadadCoin::get_instance();
    return $instance->remove_coin($user_id, $amount);
}

// اضافه کردن استایل‌های ساده برای شورت‌کد
function kadad_coin_shortcode_styles() {
    echo '<style>
        .kadad-coin-display {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .kadad-coin-icon {
            font-size: 1.2em;
        }
        .kadad-coin-amount {
            font-weight: bold;
            font-size: 1.1em;
        }
        .kadad-coin-name {
            color: #6c757d;
        }
    </style>';
}
add_action('wp_head', 'kadad_coin_shortcode_styles');