<?php
/**
 * Scanner View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap khc-scanner">
    <div class="khc-header">
        <div class="khc-header-content">
            <h1><?php _e('Health Scanner', KHC_TEXT_DOMAIN); ?></h1>
            <div class="khc-actions">
                <button class="button button-primary" onclick="khc_run_scan()">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Run Full Scan', KHC_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>

    <div id="khc-scan-progress" style="display: none;">
        <div class="khc-card">
            <div class="khc-card-body">
                <h3><?php _e('Scanning...', KHC_TEXT_DOMAIN); ?></h3>
                <div class="khc-progress-bar">
                    <div class="khc-progress-fill" style="width: 0%;"></div>
                </div>
                <p id="khc-scan-status"><?php _e('Initializing scan...', KHC_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>

    <div id="khc-scan-results" style="display: none;">
        <div class="khc-card">
            <div class="khc-card-header">
                <h3><?php _e('Scan Results', KHC_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="khc-card-body" id="khc-results-content">
                <!-- Results will be loaded here -->
            </div>
        </div>
    </div>

    <div class="khc-card">
        <div class="khc-card-header">
            <h3><?php _e('Scan Categories', KHC_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="khc-card-body">
            <div class="khc-scan-categories">
                <?php
                $categories = [
                    'system' => ['label' => __('System', KHC_TEXT_DOMAIN), 'icon' => '🖥️'],
                    'security' => ['label' => __('Security', KHC_TEXT_DOMAIN), 'icon' => '🔒'],
                    'performance' => ['label' => __('Performance', KHC_TEXT_DOMAIN), 'icon' => '⚡'],
                    'seo' => ['label' => __('SEO', KHC_TEXT_DOMAIN), 'icon' => '🔍'],
                    'woocommerce' => ['label' => __('WooCommerce', KHC_TEXT_DOMAIN), 'icon' => '🛒'],
                    'images' => ['label' => __('Images', KHC_TEXT_DOMAIN), 'icon' => '🖼️'],
                    'database' => ['label' => __('Database', KHC_TEXT_DOMAIN), 'icon' => '🗄️'],
                    'updates' => ['label' => __('Updates', KHC_TEXT_DOMAIN), 'icon' => '🔄']
                ];
                foreach ($categories as $key => $category):
                ?>
                <div class="khc-scan-category" data-category="<?php echo esc_attr($key); ?>">
                    <div class="category-icon"><?php echo esc_html($category['icon']); ?></div>
                    <div class="category-name"><?php echo esc_html($category['label']); ?></div>
                    <div class="category-status" id="status-<?php echo esc_attr($key); ?>">
                        <span class="status-dot pending"></span>
                        <span class="status-text"><?php _e('Pending', KHC_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>