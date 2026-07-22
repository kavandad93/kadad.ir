<?php
/**
 * صفحه تنظیمات افزونه Kadad Coin
 *
 * @package KadadCoin
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// بررسی دسترسی مدیر
if (!current_user_can('manage_options')) {
    wp_die(__('شما دسترسی لازم برای مشاهده این صفحه را ندارید.', 'kadad-coin'));
}

// دریافت مقادیر تنظیمات
$options = get_option('kadad_coin_settings', array());

// تنظیمات پیش‌فرض
$defaults = array(
    'is_active' => '1',
    'coin_name' => __('سکه', 'kadad-coin'),
    'coin_icon' => '🪙',
    'register_amount' => '10',
    'daily_login_amount' => '5',
    'woocommerce_amount' => '20',
    'comment_amount' => '3'
);

// ادغام با مقادیر پیش‌فرض
$options = wp_parse_args($options, $defaults);

// نمایش پیام‌ها
if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php esc_html_e('تنظیمات با موفقیت ذخیره شد.', 'kadad-coin'); ?></strong></p>
    </div>
    <?php
}

// نمایش وضعیت ووکامرس
$woocommerce_active = class_exists('WooCommerce');

?>
<div class="wrap">
    <h1><?php esc_html_e('تنظیمات Kadad Coin', 'kadad-coin'); ?></h1>
    
    <div class="kadad-coin-status">
        <h2><?php esc_html_e('وضعیت سیستم', 'kadad-coin'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('وضعیت افزونه', 'kadad-coin'); ?></th>
                <td>
                    <?php if ($options['is_active'] === '1') : ?>
                        <span style="color: green; font-weight: bold;">✓ <?php esc_html_e('فعال', 'kadad-coin'); ?></span>
                    <?php else : ?>
                        <span style="color: red; font-weight: bold;">✗ <?php esc_html_e('غیرفعال', 'kadad-coin'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('وضعیت ووکامرس', 'kadad-coin'); ?></th>
                <td>
                    <?php if ($woocommerce_active) : ?>
                        <span style="color: green; font-weight: bold;">✓ <?php esc_html_e('نصب و فعال', 'kadad-coin'); ?></span>
                    <?php else : ?>
                        <span style="color: orange; font-weight: bold;">⚠ <?php esc_html_e('نصب نیست یا غیرفعال است', 'kadad-coin'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>

    <form method="post" action="options.php">
        <?php
        // اضافه کردن Nonce برای امنیت
        settings_fields('kadad_coin_settings_group');
        do_settings_sections('kadad-coin');
        submit_button();
        ?>
    </form>

    <div class="kadad-coin-info">
        <h2><?php esc_html_e('اطلاعات مفید', 'kadad-coin'); ?></h2>
        
        <h3><?php esc_html_e('شورت‌کد', 'kadad-coin'); ?></h3>
        <p><?php esc_html_e('برای نمایش موجودی سکه کاربر از شورت‌کد زیر استفاده کنید:', 'kadad-coin'); ?></p>
        <code>[kadad_coin]</code>
        
        <h4><?php esc_html_e('پارامترهای شورت‌کد:', 'kadad-coin'); ?></h4>
        <ul>
            <li><code>show_icon</code> - <?php esc_html_e('نمایش آیکون (true/false)', 'kadad-coin'); ?></li>
            <li><code>show_name</code> - <?php esc_html_e('نمایش نام سکه (true/false)', 'kadad-coin'); ?></li>
        </ul>
        
        <h3><?php esc_html_e('توابع قابل استفاده در قالب', 'kadad-coin'); ?></h3>
        <ul>
            <li><code>kadad_coin_get($user_id)</code> - <?php esc_html_e('دریافت موجودی سکه کاربر', 'kadad-coin'); ?></li>
            <li><code>kadad_coin_set($user_id, $amount)</code> - <?php esc_html_e('تنظیم موجودی سکه کاربر', 'kadad-coin'); ?></li>
            <li><code>kadad_coin_add($user_id, $amount)</code> - <?php esc_html_e('افزایش موجودی سکه کاربر', 'kadad-coin'); ?></li>
            <li><code>kadad_coin_remove($user_id, $amount)</code> - <?php esc_html_e('کاهش موجودی سکه کاربر', 'kadad-coin'); ?></li>
        </ul>
        
        <h3><?php esc_html_e('راهنمای استفاده', 'kadad-coin'); ?></h3>
        <p><?php esc_html_e('افزونه Kadad Coin به صورت خودکار به کاربران بر اساس رویدادهای زیر سکه تعلق می‌دهد:', 'kadad-coin'); ?></p>
        <ul>
            <li><strong><?php esc_html_e('ثبت‌نام:', 'kadad-coin'); ?></strong> <?php esc_html_e('کاربر پس از ثبت‌نام، سکه ثبت‌نام را دریافت می‌کند.', 'kadad-coin'); ?></li>
            <li><strong><?php esc_html_e('ورود روزانه:', 'kadad-coin'); ?></strong> <?php esc_html_e('کاربر با ورود به سایت، روزانه یک بار سکه دریافت می‌کند.', 'kadad-coin'); ?></li>
            <li><strong><?php esc_html_e('خرید ووکامرس:', 'kadad-coin'); ?></strong> <?php esc_html_e('پس از تکمیل سفارش، خریدار سکه دریافت می‌کند.', 'kadad-coin'); ?></li>
            <li><strong><?php esc_html_e('ثبت دیدگاه:', 'kadad-coin'); ?></strong> <?php esc_html_e('کاربرانی که محصول را خریداری کرده‌اند، پس از تأیید دیدگاه سکه دریافت می‌کنند.', 'kadad-coin'); ?></li>
        </ul>
    </div>
</div>

<style>
    .kadad-coin-status,
    .kadad-coin-info {
        background: #fff;
        padding: 15px 20px;
        margin: 20px 0;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        border-radius: 4px;
    }
    .kadad-coin-status table {
        margin: 10px 0;
    }
    .kadad-coin-status table th {
        width: 200px;
        padding: 8px 0;
        text-align: left;
    }
    .kadad-coin-status table td {
        padding: 8px 0;
    }
    .kadad-coin-info ul {
        margin: 10px 0 10px 20px;
        list-style: disc;
    }
    .kadad-coin-info ul li {
        margin: 5px 0;
    }
    .kadad-coin-info code {
        background: #f0f0f1;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 13px;
    }
    .wrap h1 {
        margin-bottom: 20px;
    }
    .form-table th {
        width: 200px;
    }
</style>