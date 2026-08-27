<?php
namespace AICF\Admin\Articles;

use AICF\Security\SecurityManager;
use AICF\Publisher\Publisher;

if (!defined('ABSPATH')) {
    exit;
}

class ArticlesPage {

    public static function render() {
        if (!SecurityManager::check_capability('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-content-factory'));
        }

        global $wpdb;
        $table_articles = $wpdb->prefix . 'aicf_articles';

        $notice = '';

        // Handle Manual Publish Trigger
        if (isset($_GET['action']) && $_GET['action'] === 'publish' && isset($_GET['id']) && check_admin_referer('aicf_publish_article_' . $_GET['id'])) {
            $article_id = intval($_GET['id']);
            $wp_id = Publisher::publish_to_wp($article_id, 'publish');
            
            if ($wp_id) {
                $notice = '<div class="notice notice-success is-dismissible"><p>Article published successfully to WordPress Posts! (Post ID: ' . $wp_id . ')</p></div>';
            } else {
                $notice = '<div class="notice notice-error is-dismissible"><p>Failed to publish article to WordPress.</p></div>';
            }
        }

        $articles = $wpdb->get_results("SELECT * FROM {$table_articles} ORDER BY id DESC LIMIT 50");

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">AI Generated Articles</h1>
            <hr class="wp-header-end">

            <?php echo $notice; ?>

            <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top:15px;">
                <thead>
                    <tr>
                        <th scope="col" style="width: 50px;">ID</th>
                        <th scope="col">Title / Keyword</th>
                        <th scope="col" style="width: 140px;">Pipeline State</th>
                        <th scope="col" style="width: 100px;">SEO Score</th>
                        <th scope="col" style="width: 100px;">WP Status</th>
                        <th scope="col" style="width: 150px;">Created At</th>
                        <th scope="col" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articles)): ?>
                        <tr>
                            <td colspan="7">No articles generated yet. Run a campaign to start generating content.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($articles as $art): ?>
                            <tr>
                                <td><strong>#<?php echo esc_html($art->id); ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($art->title ?: 'Pending Title Generation'); ?></strong>
                                    <?php if (!empty($art->meta_description)): ?>
                                        <p class="description"><?php echo esc_html(wp_trim_words($art->meta_description, 15)); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge state-<?php echo esc_attr($art->generation_state); ?>">
                                        <?php echo esc_html(str_replace('_', ' ', strtoupper($art->generation_state))); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $score = intval($art->seo_score);
                                    $color = ($score >= 80) ? '#46b450' : (($score >= 50) ? '#ffb900' : '#dc3232');
                                    ?>
                                    <span style="font-weight:bold; color: <?php echo $color; ?>;">
                                        <?php echo $score; ?> / 100
                                    </span>
                                </td>
                                <td>
                                    <?php if ($art->wp_post_id): ?>
                                        <a href="<?php echo get_edit_post_link($art->wp_post_id); ?>" target="_blank">Post #<?php echo esc_html($art->wp_post_id); ?></a>
                                    <?php else: ?>
                                        <em>Not Published</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($art->created_at); ?></td>
                                <td>
                                    <?php if ($art->generation_state === 'ready' && empty($art->wp_post_id)): ?>
                                        <?php 
                                        $pub_url = wp_nonce_url(
                                            admin_url('admin.php?page=aicf-articles&action=publish&id=' . $art->id),
                                            'aicf_publish_article_' . $art->id
                                        );
                                        ?>
                                        <a href="<?php echo esc_url($pub_url); ?>" class="button button-small button-primary">Publish to WP</a>
                                    <?php elseif ($art->wp_post_id): ?>
                                        <a href="<?php echo esc_url(get_permalink($art->wp_post_id)); ?>" target="_blank" class="button button-small">View Live</a>
                                    <?php else: ?>
                                        <span class="spinner is-active" style="float:none;"></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <style>
            .badge { display:inline-block; padding:3px 7px; border-radius:3px; font-size:11px; font-weight:bold; background:#e5e5e5; color:#333; }
            .badge.state-ready { background:#d4edda; color:#155724; }
            .badge.state-failed { background:#f8d7da; color:#721c24; }
            .badge.state-generating_sections { background:#cce5ff; color:#004085; }
        </style>
        <?php
    }
}