<?php
namespace AICF\Admin\Dashboard;

use AICF\Security\SecurityManager;

if (!defined('ABSPATH')) {
    exit;
}

class DashboardPage {

    public static function render() {
        if (!SecurityManager::check_capability('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-content-factory'));
        }

        global $wpdb;
        $table_articles = $wpdb->prefix . 'aicf_articles';
        $table_campaigns = $wpdb->prefix . 'aicf_campaigns';

        // Calculate Real Analytics
        $total_campaigns = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_campaigns}");
        $total_articles  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_articles}");
        $published_arts  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_articles} WHERE status = 'published'");
        $avg_seo_score   = (float) $wpdb->get_var("SELECT AVG(seo_score) FROM {$table_articles} WHERE generation_state = 'ready'");

        ?>
        <div class="wrap">
            <h1>AI Content Factory — Realtime Dashboard</h1>
            <p>Welcome to your automated AI Content Generation Hub.</p>
            <hr class="wp-header-end">

            <!-- STATS CARDS -->
            <div style="display: flex; gap: 20px; margin-top: 20px;">
                <div class="stat-card">
                    <h3>Active Campaigns</h3>
                    <div class="stat-number"><?php echo $total_campaigns; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Generated Articles</h3>
                    <div class="stat-number"><?php echo $total_articles; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Published Posts</h3>
                    <div class="stat-number"><?php echo $published_arts; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Avg. SEO Score</h3>
                    <div class="stat-number" style="color:#2e7d32;"><?php echo round($avg_seo_score, 1); ?>/100</div>
                </div>
            </div>

            <!-- QUICK START -->
            <div style="margin-top: 30px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h2>Quick Actions</h2>
                <p>Manage your AI campaigns and track article queue status in real time.</p>
                <a href="<?php echo admin_url('admin.php?page=aicf-campaigns'); ?>" class="button button-primary">Manage Campaigns</a>
                <a href="<?php echo admin_url('admin.php?page=aicf-articles'); ?>" class="button button-secondary">View Articles Queue</a>
                <a href="<?php echo admin_url('admin.php?page=aicf-settings'); ?>" class="button button-secondary">API Settings</a>
            </div>
        </div>

        <style>
            .stat-card {
                flex: 1;
                background: #fff;
                padding: 18px;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .stat-card h3 { margin: 0 0 10px 0; font-size: 13px; color: #555; text-transform: uppercase; }
            .stat-number { font-size: 28px; font-weight: bold; color: #1d2327; }
        </style>
        <?php
    }
}