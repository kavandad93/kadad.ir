<?php
/**
 * Reports View
 */

if (!defined('ABSPATH')) {
    exit;
}

$report = new KHC_Report();
$last_report = get_option('khc_last_report');
$history = khc_get_scan_history(20);
?>
<div class="wrap khc-reports">
    <div class="khc-header">
        <div class="khc-header-content">
            <h1><?php _e('Reports', KHC_TEXT_DOMAIN); ?></h1>
            <div class="khc-actions">
                <button class="button button-primary" onclick="khc_generate_report()">
                    <span class="dashicons dashicons-media-document"></span>
                    <?php _e('Generate Report', KHC_TEXT_DOMAIN); ?>
                </button>
                <button class="button" onclick="khc_export_data('json')">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export JSON', KHC_TEXT_DOMAIN); ?>
                </button>
                <button class="button" onclick="khc_export_data('csv')">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export CSV', KHC_TEXT_DOMAIN); ?>
                </button>
                <button class="button" onclick="khc_export_data('pdf')">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export PDF', KHC_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </div>

    <?php if ($last_report): ?>
    <div class="khc-card">
        <div class="khc-card-header">
            <h3><?php _e('Latest Report', KHC_TEXT_DOMAIN); ?></h3>
            <span class="report-date"><?php echo esc_html($last_report['generated']); ?></span>
        </div>
        <div class="khc-card-body">
            <div class="report-summary">
                <div class="report-score">
                    <span class="report-score-number"><?php echo esc_html($last_report['total_score']); ?></span>
                    <span class="report-score-label"><?php _e('Health Score', KHC_TEXT_DOMAIN); ?></span>
                </div>
                <div class="report-stats">
                    <div class="report-stat">
                        <span class="stat-label"><?php _e('Issues Found', KHC_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo esc_html($last_report['issues_found']); ?></span>
                    </div>
                    <div class="report-stat">
                        <span class="stat-label"><?php _e('Issues Fixed', KHC_TEXT_DOMAIN); ?></span>
                        <span class="stat-value"><?php echo esc_html($last_report['issues_fixed']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="report-categories">
                <h4><?php _e('Category Scores', KHC_TEXT_DOMAIN); ?></h4>
                <div class="category-scores-grid">
                    <?php foreach ($last_report['categories'] as $category => $score): ?>
                    <div class="category-score-item">
                        <span class="category-name"><?php echo esc_html(ucfirst($category)); ?></span>
                        <span class="category-score <?php echo khc_get_score_status($score); ?>">
                            <?php echo esc_html($score); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (!empty($last_report['recommendations'])): ?>
            <div class="report-recommendations">
                <h4><?php _e('Recommendations', KHC_TEXT_DOMAIN); ?></h4>
                <ul>
                    <?php foreach ($last_report['recommendations'] as $recommendation): ?>
                    <li><?php echo esc_html($recommendation); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="khc-card">
        <div class="khc-card-header">
            <h3><?php _e('Scan History', KHC_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="khc-card-body">
            <table class="khc-table">
                <thead>
                    <tr>
                        <th><?php _e('Date', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Score', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Issues', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Fixed', KHC_TEXT_DOMAIN); ?></th>
                        <th><?php _e('Actions', KHC_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($history)): ?>
                        <?php foreach ($history as $scan): ?>
                        <tr>
                            <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($scan['scan_date']))); ?></td>
                            <td>
                                <span class="score-badge" style="background: <?php echo khc_get_score_color($scan['total_score']); ?>;">
                                    <?php echo esc_html($scan['total_score']); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html($scan['issues_found']); ?></td>
                            <td><?php echo esc_html($scan['issues_fixed']); ?></td>
                            <td>
                                <button class="button button-small" onclick="khc_view_report(<?php echo esc_js($scan['id']); ?>)">
                                    <?php _e('View', KHC_TEXT_DOMAIN); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center"><?php _e('No scans found.', KHC_TEXT_DOMAIN); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>