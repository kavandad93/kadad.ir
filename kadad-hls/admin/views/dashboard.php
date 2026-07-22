<?php
/**
 * Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}

$dashboard = new KHC_Dashboard();
$data = $dashboard->get_dashboard_data();
$chart_data = $dashboard->get_chart_data('trend');
$category_data = $dashboard->get_chart_data('categories');
$latest_scan = $data['last_scan'] ?? null;
$status = $data['status'] ?? ['label' => 'Unknown', 'class' => 'unknown', 'color' => '#6b7280'];
?>
<div class="wrap khc-dashboard">
    <div class="khc-header">
        <div class="khc-header-content">
            <h1><?php _e('Health Check Dashboard', KHC_TEXT_DOMAIN); ?></h1>
            <div class="khc-actions">
                <button class="button button-primary" onclick="khc_run_scan()">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Run New Scan', KHC_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="khc-grid">
        <!-- Score Card -->
        <div class="khc-card score-card">
            <div class="khc-card-header">
                <h3><?php _e('Overall Health Score', KHC_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="khc-card-body">
                <div class="score-circle">
                    <svg viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e5e7eb" stroke-width="12"/>
                        <circle cx="60" cy="60" r="54" fill="none" 
                                stroke="<?php echo esc_attr($status['color']); ?>" 
                                stroke-width="12"
                                stroke-dasharray="339.292"
                                stroke-dashoffset="<?php echo esc_attr(339.292 - (339.292 * ($data['scores']['total'] / 100))); ?>"
                                transform="rotate(-90 60 60)"/>
                    </svg>
                    <div class="score-content">
                        <span class="score-number"><?php echo esc_html($data['scores']['total']); ?></span>
                        <span class="score-label"><?php _e('out of 100', KHC_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="score-status <?php echo esc_attr($status['class']); ?>">
                    <?php echo esc_html($status['label']); ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="khc-stats-grid">
            <div class="khc-card stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($data['scan_count']); ?></span>
                    <span class="stat-label"><?php _e('Total Scans', KHC_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="khc-card stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($data['issues_count']); ?></span>
                    <span class="stat-label"><?php _e('Issues Found', KHC_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="khc-card stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo esc_html($data['fixed_count']); ?></span>
                    <span class="stat-label"><?php _e('Issues Fixed', KHC_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="khc-card stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <span class="stat-number"><?php echo $latest_scan ? date('M d, Y', strtotime($latest_scan['scan_date'])) : __('Never', KHC_TEXT_DOMAIN); ?></span>
                    <span class="stat-label"><?php _e('Last Scan', KHC_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="khc-grid khc-charts-grid">
        <div class="khc-card chart-card">
            <div class="khc-card-header">
                <h3><?php _e('Score Trend', KHC_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="khc-card-body">
                <canvas id="khc-trend-chart"></canvas>
            </div>
        </div>
        <div class="khc-card chart-card">
            <div class="khc-card-header">
                <h3><?php _e('Category Scores', KHC_TEXT_DOMAIN); ?></h3>
            </div>
            <div class="khc-card-body">
                <canvas id="khc-category-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Scores -->
    <div class="khc-card categories-card">
        <div class="khc-card-header">
            <h3><?php _e('Category Scores', KHC_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="khc-card-body">
            <div class="category-grid">
                <?php
                $categories = [
                    'security' => ['label' => __('Security', KHC_TEXT_DOMAIN), 'icon' => '🔒'],
                    'performance' => ['label' => __('Performance', KHC_TEXT_DOMAIN), 'icon' => '⚡'],
                    'seo' => ['label' => __('SEO', KHC_TEXT_DOMAIN), 'icon' => '🔍'],
                    'system' => ['label' => __('System', KHC_TEXT_DOMAIN), 'icon' => '🖥️'],
                    'woocommerce' => ['label' => __('WooCommerce', KHC_TEXT_DOMAIN), 'icon' => '🛒'],
                    'images' => ['label' => __('Images', KHC_TEXT_DOMAIN), 'icon' => '🖼️'],
                    'database' => ['label' => __('Database', KHC_TEXT_DOMAIN), 'icon' => '🗄️'],
                    'updates' => ['label' => __('Updates', KHC_TEXT_DOMAIN), 'icon' => '🔄']
                ];
                foreach ($categories as $key => $category):
                    $score = $data['scores'][$key] ?? 0;
                ?>
                <div class="category-item">
                    <div class="category-icon"><?php echo esc_html($category['icon']); ?></div>
                    <div class="category-name"><?php echo esc_html($category['label']); ?></div>
                    <div class="category-score">
                        <span class="score-value <?php echo khc_get_score_status($score); ?>">
                            <?php echo esc_html($score); ?>
                        </span>
                        <div class="score-bar">
                            <div class="score-fill" style="width: <?php echo esc_attr($score); ?>%; background: <?php echo khc_get_score_color($score); ?>;"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    <?php if ($latest_scan && !empty($latest_scan['recommendations'])): ?>
    <div class="khc-card recommendations-card">
        <div class="khc-card-header">
            <h3><?php _e('Recommendations', KHC_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="khc-card-body">
            <ul class="recommendations-list">
                <?php 
                $recommendations = is_array($latest_scan['recommendations']) ? $latest_scan['recommendations'] : json_decode($latest_scan['recommendations'], true);
                if (is_array($recommendations)):
                    foreach ($recommendations as $recommendation):
                ?>
                <li>
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php echo esc_html($recommendation); ?>
                </li>
                <?php 
                    endforeach;
                endif;
                ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scan History -->
    <div class="khc-card history-card">
        <div class="khc-card-header">
            <h3><?php _e('Scan History', KHC_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="khc-card-body">
            <table class="khc-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Score', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Issues Found', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Issues Fixed', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Status', KHC_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $history = khc_get_scan_history(10);
                    if (!empty($history)):
                        foreach ($history as $scan):
                    ?>
                    <tr>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($scan['scan_date']))); ?></td>
                        <td>
                            <span class="score-badge" style="background: <?php echo khc_get_score_color($scan['total_score']); ?>;">
                                <?php echo esc_html($scan['total_score']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($scan['issues_found']); ?></td>
                        <td><?php echo esc_html($scan['issues_fixed']); ?></td>
                        <td><?php echo esc_html(khc_get_score_status($scan['total_score'])); ?></td>
                    </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <tr>
                        <td colspan="5" class="text-center"><?php _e('No scans found. Run your first scan now!', KHC_TEXT_DOMAIN); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Trend Chart
    var ctx = document.getElementById('khc-trend-chart').getContext('2d');
    var trendData = <?php echo wp_json_encode($chart_data); ?>;
    
    if (trendData.labels && trendData.labels.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: trendData.labels,
                datasets: [
                    {
                        label: '<?php _e('Total Score', KHC_TEXT_DOMAIN); ?>',
                        data: trendData.total,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: '<?php _e('Security', KHC_TEXT_DOMAIN); ?>',
                        data: trendData.security,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: '<?php _e('Performance', KHC_TEXT_DOMAIN); ?>',
                        data: trendData.performance,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: '<?php _e('SEO', KHC_TEXT_DOMAIN); ?>',
                        data: trendData.seo,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // Category Chart
    var ctx2 = document.getElementById('khc-category-chart').getContext('2d');
    var categoryData = <?php echo wp_json_encode($category_data); ?>;
    
    if (categoryData.labels && categoryData.labels.length > 0) {
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: categoryData.labels,
                datasets: [{
                    data: categoryData.values,
                    backgroundColor: categoryData.colors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
});
</script>