<?php
namespace AICF\Admin\Logs;

use AICF\Security\SecurityManager;

if (!defined('ABSPATH')) {
    exit;
}

class LogsPage {

    public static function render() {
        if (!SecurityManager::check_capability('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-content-factory'));
        }

        global $wpdb;
        $table_logs = $wpdb->prefix . 'aicf_logs';

        // Filter parameters
        $filter_level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : '';

        $query = "SELECT * FROM {$table_logs}";
        if (!empty($filter_level)) {
            $query .= $wpdb->prepare(" WHERE level = %s", $filter_level);
        }
        $query .= " ORDER BY id DESC LIMIT 100";

        $logs = $wpdb->get_results($query);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">System Audit Logs</h1>
            <hr class="wp-header-end">

            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="aicf-logs" />
                        <select name="level">
                            <option value="">All Severity Levels</option>
                            <option value="info" <?php selected($filter_level, 'info'); ?>>Info</option>
                            <option value="warning" <?php selected($filter_level, 'warning'); ?>>Warning</option>
                            <option value="error" <?php selected($filter_level, 'error'); ?>>Error</option>
                        </select>
                        <input type="submit" class="button" value="Filter">
                    </form>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 60px;">ID</th>
                        <th scope="col" style="width: 100px;">Level</th>
                        <th scope="col" style="width: 120px;">Context</th>
                        <th scope="col">Message</th>
                        <th scope="col" style="width: 160px;">Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="5">No system log entries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>#<?php echo esc_html($log->id); ?></td>
                                <td>
                                    <?php
                                    $level_class = ($log->level === 'error') ? 'badge-error' : (($log->level === 'warning') ? 'badge-warning' : 'badge-info');
                                    ?>
                                    <span class="badge <?php echo $level_class; ?>">
                                        <?php echo esc_html(strtoupper($log->level)); ?>
                                    </span>
                                </td>
                                <td><code><?php echo esc_html($log->object_type); ?></code></td>
                                <td><?php echo esc_html($log->message); ?></td>
                                <td><?php echo esc_html($log->created_at); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <style>
            .badge { display:inline-block; padding:3px 7px; border-radius:3px; font-size:11px; font-weight:bold; }
            .badge-info { background:#e1f5fe; color:#0288d1; }
            .badge-warning { background:#fff8e1; color:#ffa000; }
            .badge-error { background:#ffebee; color:#d32f2f; }
        </style>
        <?php
    }
}