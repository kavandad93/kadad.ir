<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = [
    'scan_interval' => get_option('khc_scan_interval', 'daily'),
    'dark_mode' => get_option('khc_dark_mode', 'off'),
    'email_recipient' => get_option('khc_email_recipient', get_option('admin_email')),
    'auto_reports' => get_option('khc_auto_reports', 'on'),
    'auto_cleanup' => get_option('khc_auto_cleanup', 'off')
];
?>
<div class="wrap khc-settings">
    <div class="khc-header">
        <div class="khc-header-content">
            <h1><?php _e('Settings', KHC_TEXT_DOMAIN); ?></h1>
        </div>
    </div>

    <form id="khc-settings-form" class="khc-card">
        <div class="khc-card-body">
            <div class="khc-settings-group">
                <h3><?php _e('General Settings', KHC_TEXT_DOMAIN); ?></h3>
                
                <div class="khc-setting-row">
                    <label for="khc_scan_interval"><?php _e('Scan Interval', KHC_TEXT_DOMAIN); ?></label>
                    <select id="khc_scan_interval" name="scan_interval">
                        <option value="daily" <?php selected($settings['scan_interval'], 'daily'); ?>><?php _e('Daily', KHC_TEXT_DOMAIN); ?></option>
                        <option value="weekly" <?php selected($settings['scan_interval'], 'weekly'); ?>><?php _e('Weekly', KHC_TEXT_DOMAIN); ?></option>
                        <option value="monthly" <?php selected($settings['scan_interval'], 'monthly'); ?>><?php _e('Monthly', KHC_TEXT_DOMAIN); ?></option>
                    </select>
                    <p class="description"><?php _e('How often to automatically scan your site.', KHC_TEXT_DOMAIN); ?></p>
                </div>

                <div class="khc-setting-row">
                    <label for="khc_dark_mode"><?php _e('Dark Mode', KHC_TEXT_DOMAIN); ?></label>
                    <select id="khc_dark_mode" name="dark_mode">
                        <option value="off" <?php selected($settings['dark_mode'], 'off'); ?>><?php _e('Off', KHC_TEXT_DOMAIN); ?></option>
                        <option value="on" <?php selected($settings['dark_mode'], 'on'); ?>><?php _e('On', KHC_TEXT_DOMAIN); ?></option>
                    </select>
                    <p class="description"><?php _e('Enable dark mode for the dashboard.', KHC_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <div class="khc-settings-group">
                <h3><?php _e('Email Settings', KHC_TEXT_DOMAIN); ?></h3>
                
                <div class="khc-setting-row">
                    <label for="khc_email_recipient"><?php _e('Email Recipient', KHC_TEXT_DOMAIN); ?></label>
                    <input type="email" id="khc_email_recipient" name="email_recipient" 
                           value="<?php echo esc_attr($settings['email_recipient']); ?>" />
                    <p class="description"><?php _e('Email address to receive reports and notifications.', KHC_TEXT_DOMAIN); ?></p>
                </div>

                <div class="khc-setting-row">
                    <label for="khc_auto_reports"><?php _e('Auto Reports', KHC_TEXT_DOMAIN); ?></label>
                    <select id="khc_auto_reports" name="auto_reports">
                        <option value="on" <?php selected($settings['auto_reports'], 'on'); ?>><?php _e('On', KHC_TEXT_DOMAIN); ?></option>
                        <option value="off" <?php selected($settings['auto_reports'], 'off'); ?>><?php _e('Off', KHC_TEXT_DOMAIN); ?></option>
                    </select>
                    <p class="description"><?php _e('Automatically send reports via email.', KHC_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <div class="khc-settings-group">
                <h3><?php _e('Maintenance Settings', KHC_TEXT_DOMAIN); ?></h3>
                
                <div class="khc-setting-row">
                    <label for="khc_auto_cleanup"><?php _e('Auto Cleanup', KHC_TEXT_DOMAIN); ?></label>
                    <select id="khc_auto_cleanup" name="auto_cleanup">
                        <option value="on" <?php selected($settings['auto_cleanup'], 'on'); ?>><?php _e('On', KHC_TEXT_DOMAIN); ?></option>
                        <option value="off" <?php selected($settings['auto_cleanup'], 'off'); ?>><?php _e('Off', KHC_TEXT_DOMAIN); ?></option>
                    </select>
                    <p class="description"><?php _e('Automatically clean up expired transients, revisions, and spam.', KHC_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <div class="khc-settings-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Save Settings', KHC_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </form>

    <div class="khc-card">
        <div class="khc-card-header">
            <h3><?php _e('System Info', KHC_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="khc-card-body">
            <div class="khc-system-info">
                <?php $info = khc_get_server_info(); ?>
                <div class="info-row">
                    <span class="info-label"><?php _e('WordPress Version', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('PHP Version', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html($info['php_version']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('MySQL Version', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html($info['mysql_version']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('Server Software', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html($info['software']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('Memory Limit', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html($info['memory_limit']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('Max Execution Time', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html($info['max_execution_time']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('Upload Max Filesize', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo esc_html($info['upload_max_filesize']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('SSL Enabled', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo khc_is_ssl_enabled() ? '✅' : '❌'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><?php _e('Debug Mode', KHC_TEXT_DOMAIN); ?></span>
                    <span class="info-value"><?php echo khc_is_debug_enabled() ? '✅' : '❌'; ?></span>
                </div>
            </div>
        </div>
    </div>
</div>