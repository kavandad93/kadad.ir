<?php
/**
 * Report Class
 * Handles report generation and export
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHC_Report
{
    /**
     * Generate report
     */
    public function generate_report($scan_id = null)
    {
        if (!$scan_id) {
            $scan_id = get_option('khc_last_scan');
        }
        
        if (!$scan_id) {
            return false;
        }
        
        $scanner = new KHC_Scanner();
        $scan_data = $scanner->get_scan($scan_id);
        
        if (empty($scan_data)) {
            return false;
        }
        
        $report_data = [
            'generated' => current_time('mysql'),
            'scan_id' => $scan_id,
            'total_score' => $scan_data['total_score'],
            'categories' => [
                'security' => $scan_data['security_score'],
                'performance' => $scan_data['performance_score'],
                'seo' => $scan_data['seo_score'],
                'system' => $scan_data['system_score'],
                'woocommerce' => $scan_data['woocommerce_score'],
                'images' => $scan_data['images_score'],
                'database' => $scan_data['database_score'],
                'updates' => $scan_data['updates_score']
            ],
            'issues_found' => $scan_data['issues_found'],
            'issues_fixed' => $scan_data['issues_fixed'],
            'recommendations' => $scan_data['recommendations'],
            'details' => $scan_data['report_data']
        ];
        
        // Save report
        update_option('khc_last_report', $report_data);
        
        return $report_data;
    }

    /**
     * Export data
     */
    public function export_data($format = 'json')
    {
        $report_data = get_option('khc_last_report');
        
        if (empty($report_data)) {
            return false;
        }
        
        $data = $this->format_data($report_data);
        
        if ($format === 'json') {
            return $this->export_json($data);
        } elseif ($format === 'csv') {
            return $this->export_csv($data);
        } elseif ($format === 'pdf') {
            return $this->export_pdf($data);
        }
        
        return false;
    }

    /**
     * Format data for export
     */
    private function format_data($data)
    {
        return [
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'generated' => $data['generated'],
            'score' => $data['total_score'],
            'categories' => $data['categories'],
            'issues' => $data['issues_found'],
            'fixed' => $data['issues_fixed'],
            'recommendations' => $data['recommendations']
        ];
    }

    /**
     * Export JSON
     */
    private function export_json($data)
    {
        $json = wp_json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            return false;
        }
        
        return [
            'data' => $json,
            'filename' => 'khc-report-' . date('Y-m-d-H-i') . '.json',
            'mime_type' => 'application/json'
        ];
    }

    /**
     * Export CSV
     */
    private function export_csv($data)
    {
        $output = fopen('php://memory', 'w');
        
        // Headers
        fputcsv($output, [
            __('Site URL', KHC_TEXT_DOMAIN),
            __('Site Name', KHC_TEXT_DOMAIN),
            __('Generated', KHC_TEXT_DOMAIN),
            __('Total Score', KHC_TEXT_DOMAIN),
            __('Issues Found', KHC_TEXT_DOMAIN),
            __('Issues Fixed', KHC_TEXT_DOMAIN)
        ]);
        
        // Data row
        fputcsv($output, [
            $data['site_url'],
            $data['site_name'],
            $data['generated'],
            $data['score'],
            $data['issues'],
            $data['fixed']
        ]);
        
        // Category headers
        fputcsv($output, [__('Category', KHC_TEXT_DOMAIN), __('Score', KHC_TEXT_DOMAIN)]);
        foreach ($data['categories'] as $category => $score) {
            fputcsv($output, [ucfirst($category), $score]);
        }
        
        // Recommendations
        fputcsv($output, [__('Recommendations', KHC_TEXT_DOMAIN)]);
        foreach ($data['recommendations'] as $recommendation) {
            fputcsv($output, [$recommendation]);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return [
            'data' => $csv,
            'filename' => 'khc-report-' . date('Y-m-d-H-i') . '.csv',
            'mime_type' => 'text/csv'
        ];
    }

    /**
     * Export PDF (simplified)
     */
    private function export_pdf($data)
    {
        // In a real implementation, you'd use a PDF library like Dompdf or TCPDF
        // For this example, we'll return HTML that can be printed as PDF
        $html = $this->generate_pdf_html($data);
        
        return [
            'data' => $html,
            'filename' => 'khc-report-' . date('Y-m-d-H-i') . '.html',
            'mime_type' => 'text/html'
        ];
    }

    /**
     * Generate PDF HTML
     */
    private function generate_pdf_html($data)
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Health Check Report</title>
            <style>
                body { font-family: 'Helvetica', Arial, sans-serif; margin: 40px; }
                .report-header { text-align: center; margin-bottom: 30px; }
                .report-title { font-size: 24px; font-weight: bold; color: #333; }
                .report-subtitle { color: #666; margin-top: 5px; }
                .score-box { 
                    text-align: center; 
                    padding: 20px; 
                    background: #f8f9fa; 
                    border-radius: 8px;
                    margin: 20px 0;
                }
                .score-number { font-size: 48px; font-weight: bold; }
                .score-excellent { color: #10b981; }
                .score-good { color: #3b82f6; }
                .score-needs { color: #f59e0b; }
                .score-critical { color: #ef4444; }
                .category-grid { 
                    display: grid; 
                    grid-template-columns: repeat(4, 1fr); 
                    gap: 10px;
                    margin: 20px 0;
                }
                .category-item { 
                    padding: 15px; 
                    background: #f8f9fa; 
                    border-radius: 4px;
                    text-align: center;
                }
                .category-name { font-size: 14px; color: #666; }
                .category-score { font-size: 24px; font-weight: bold; }
                .section { margin: 30px 0; }
                .section-title { font-size: 18px; font-weight: bold; border-bottom: 2px solid #eee; padding-bottom: 10px; }
                .recommendation-item { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
                .footer { text-align: center; margin-top: 50px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="report-header">
                <div class="report-title">WordPress Health Check Report</div>
                <div class="report-subtitle">Generated: <?php echo esc_html($data['generated']); ?></div>
                <div class="report-subtitle">Site: <?php echo esc_html($data['site_name']); ?></div>
            </div>
            
            <div class="score-box">
                <div>Overall Health Score</div>
                <div class="score-number <?php echo $this->get_score_class($data['score']); ?>">
                    <?php echo esc_html($data['score']); ?>/100
                </div>
                <div>Status: <?php echo $this->get_score_status($data['score']); ?></div>
            </div>
            
            <div class="category-grid">
                <?php foreach ($data['categories'] as $category => $score): ?>
                <div class="category-item">
                    <div class="category-name"><?php echo esc_html(ucfirst($category)); ?></div>
                    <div class="category-score <?php echo $this->get_score_class($score); ?>">
                        <?php echo esc_html($score); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="section">
                <div class="section-title">Issues Found: <?php echo esc_html($data['issues']); ?></div>
                <?php if (!empty($data['recommendations'])): ?>
                <div class="section-title" style="margin-top: 20px;">Recommendations</div>
                <?php foreach ($data['recommendations'] as $recommendation): ?>
                <div class="recommendation-item">• <?php echo esc_html($recommendation); ?></div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="footer">Report generated by Kadad Health Check Plugin v<?php echo KHC_VERSION; ?></div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get score class
     */
    private function get_score_class($score)
    {
        if ($score >= 95) return 'score-excellent';
        if ($score >= 80) return 'score-good';
        if ($score >= 60) return 'score-needs';
        return 'score-critical';
    }

    /**
     * Get score status
     */
    private function get_score_status($score)
    {
        if ($score >= 95) return __('Excellent', KHC_TEXT_DOMAIN);
        if ($score >= 80) return __('Good', KHC_TEXT_DOMAIN);
        if ($score >= 60) return __('Needs Attention', KHC_TEXT_DOMAIN);
        return __('Critical', KHC_TEXT_DOMAIN);
    }
}