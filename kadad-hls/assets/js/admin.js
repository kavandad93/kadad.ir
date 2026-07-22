/**
 * Kadad Health Check Admin Scripts
 */

(function($) {
    'use strict';

    // Run scan
    window.khc_run_scan = function() {
        $('#khc-scan-progress').show();
        $('#khc-scan-results').hide();
        
        // Reset status dots
        $('.khc-scan-category .status-dot').removeClass('running complete failed').addClass('pending');
        $('.khc-scan-category .status-text').text(khc_ajax.i18n.scanning);
        
        // Update progress
        var progress = 0;
        var statuses = ['system', 'security', 'performance', 'seo', 'woocommerce', 'images', 'database', 'updates'];
        
        $.ajax({
            url: khc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'khc_run_scan',
                nonce: khc_ajax.nonce
            },
            beforeSend: function() {
                $('#khc-scan-status').text('Starting scan...');
                updateProgress(0);
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        updateProgress(percentComplete * 100);
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                    $('.khc-scan-category .status-dot').removeClass('pending running').addClass('complete');
                    $('.khc-scan-category .status-text').text('Complete');
                    $('#khc-scan-status').text('Scan completed successfully!');
                } else {
                    showError(response.data.message || 'Scan failed');
                }
            },
            error: function() {
                showError('An error occurred during the scan.');
            },
            complete: function() {
                updateProgress(100);
            }
        });
    };

    // Update progress
    function updateProgress(percent) {
        $('.khc-progress-fill').css('width', percent + '%');
        $('#khc-scan-status').text('Scanning... ' + Math.round(percent) + '%');
    }

    // Display results
    function displayResults(data) {
        var html = '<div class="scan-results">';
        
        // Score
        html += '<div class="result-score">';
        html += '<h4>Total Score: <span style="color:' + getScoreColor(data.total_score) + '">' + data.total_score + '</span></h4>';
        html += '</div>';
        
        // Categories
        html += '<div class="result-categories">';
        var categories = ['security', 'performance', 'seo', 'system', 'woocommerce', 'images', 'database', 'updates'];
        var labels = ['Security', 'Performance', 'SEO', 'System', 'WooCommerce', 'Images', 'Database', 'Updates'];
        
        categories.forEach(function(cat, index) {
            var score = data[cat + '_score'] || 0;
            html += '<div class="result-category">';
            html += '<span>' + labels[index] + ':</span>';
            html += '<span style="color:' + getScoreColor(score) + ';">' + score + '</span>';
            html += '</div>';
        });
        html += '</div>';
        
        // Issues
        html += '<div class="result-issues">';
        html += '<h4>Issues Found: ' + data.issues_found + '</h4>';
        html += '</div>';
        
        // Recommendations
        if (data.recommendations && data.recommendations.length > 0) {
            html += '<div class="result-recommendations">';
            html += '<h4>Recommendations:</h4>';
            html += '<ul>';
            data.recommendations.forEach(function(rec) {
                html += '<li>' + rec + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#khc-results-content').html(html);
        $('#khc-scan-results').show();
    }

    // Get score color
    function getScoreColor(score) {
        if (score >= 95) return '#10b981';
        if (score >= 80) return '#3b82f6';
        if (score >= 60) return '#f59e0b';
        return '#ef4444';
    }

    // Show error
    function showError(message) {
        $('#khc-scan-status').text('Error: ' + message);
        $('.khc-scan-category .status-dot').removeClass('pending running').addClass('failed');
        $('.khc-scan-category .status-text').text('Failed');
    }

    // Apply fix
    window.khc_apply_fix = function(fixType) {
        if (!confirm('Are you sure you want to apply this fix?')) {
            return;
        }
        
        var button = $('[data-fix="' + fixType + '"]');
        button.prop('disabled', true).text(khc_ajax.i18n.fixing);
        
        $.ajax({
            url: khc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'khc_apply_fix',
                nonce: khc_ajax.nonce,
                fix_type: fixType
            },
            success: function(response) {
                if (response.success) {
                    button.text(khc_ajax.i18n.fixed).addClass('button-success');
                    showNotification('Fix applied successfully!', 'success');
                } else {
                    button.text('Failed').addClass('button-error');
                    showNotification(response.data.message || 'Fix failed', 'error');
                }
            },
            error: function() {
                button.text('Failed').addClass('button-error');
                showNotification('An error occurred while applying the fix.', 'error');
            },
            complete: function() {
                setTimeout(function() {
                    button.prop('disabled', false);
                }, 3000);
            }
        });
    };

    // Generate report
    window.khc_generate_report = function() {
        var button = $('[onclick="khc_generate_report()"]');
        button.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: khc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'khc_generate_report',
                nonce: khc_ajax.nonce,
                report_id: 0
            },
            success: function(response) {
                if (response.success) {
                    showNotification('Report generated successfully!', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data.message || 'Report generation failed', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while generating the report.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Generate Report');
            }
        });
    };

    // Export data
    window.khc_export_data = function(format) {
        var button = $('[onclick="khc_export_data(\'' + format + '\')"]');
        button.prop('disabled', true).text('Exporting...');
        
        $.ajax({
            url: khc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'khc_export_data',
                nonce: khc_ajax.nonce,
                format: format
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data.data;
                    var filename = response.data.filename;
                    var mimeType = response.data.mime_type;
                    
                    // Create download link
                    var blob = new Blob([data], {type: mimeType});
                    var link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('Data exported successfully!', 'success');
                } else {
                    showNotification(response.data.message || 'Export failed', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while exporting data.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Export ' + format.toUpperCase());
            }
        });
    };

    // View report
    window.khc_view_report = function(id) {
        // Implement report viewing
        showNotification('Viewing report ID: ' + id, 'info');
    };

    // Save settings
    $('#khc-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var button = $(this).find('button[type="submit"]');
        button.prop('disabled', true).text('Saving...');
        
        var data = {
            action: 'khc_save_settings',
            nonce: khc_ajax.nonce,
            scan_interval: $('#khc_scan_interval').val(),
            dark_mode: $('#khc_dark_mode').val(),
            email_recipient: $('#khc_email_recipient').val(),
            auto_reports: $('#khc_auto_reports').val(),
            auto_cleanup: $('#khc_auto_cleanup').val()
        };
        
        $.ajax({
            url: khc_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    showNotification('Settings saved successfully!', 'success');
                } else {
                    showNotification(response.data.message || 'Settings save failed', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred while saving settings.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Save Settings');
            }
        });
    });

    // Show notification
    function showNotification(message, type) {
        var color = '';
        var icon = '';
        
        switch(type) {
            case 'success':
                color = '#10b981';
                icon = '✅';
                break;
            case 'error':
                color = '#ef4444';
                icon = '❌';
                break;
            case 'warning':
                color = '#f59e0b';
                icon = '⚠️';
                break;
            default:
                color = '#3b82f6';
                icon = 'ℹ️';
        }
        
        var notification = $('<div class="khc-notification" style="position:fixed;top:20px;right:20px;z-index:999999;background:white;padding:15px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);border-left:4px solid ' + color + ';max-width:400px;display:flex;align-items:center;gap:10px;">');
        notification.html('<span style="font-size:20px;">' + icon + '</span><span>' + message + '</span>');
        notification.appendTo('body');
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                notification.remove();
            });
        }, 5000);
    }

    // Dark mode toggle
    if ($('#khc_dark_mode').val() === 'on') {
        $('body').addClass('khc-dark-mode');
    }

    $('#khc_dark_mode').on('change', function() {
        if ($(this).val() === 'on') {
            $('body').addClass('khc-dark-mode');
        } else {
            $('body').removeClass('khc-dark-mode');
        }
    });

})(jQuery);