<?php
namespace AICF\Admin\Ajax;

use AICF\Security\SecurityManager;

if (!defined('ABSPATH')) {
    exit;
}

class ProgressAjax {

    public static function init() {
        add_action('wp_ajax_aicf_get_generation_progress', [__CLASS__, 'get_progress']);
    }

    /**
     * AJAX handler to get realtime pipeline progress for active campaigns/articles.
     */
    public static function get_progress() {
        if (!SecurityManager::check_capability('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized user capability']);
        }

        // NOTE: admin-script.js polls this endpoint with $.post(), so the
        // nonce/campaign_id arrive in $_POST, not $_GET. Reading only
        // $_GET here meant this handler always failed with "Invalid
        // security token" even once the action name matched. $_REQUEST
        // covers both GET and POST so this keeps working either way.
        $nonce = $_REQUEST['nonce'] ?? '';
        if (!$nonce || !SecurityManager::verify_nonce($nonce, 'aicf_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }

        $campaign_id = isset($_REQUEST['campaign_id']) ? intval($_REQUEST['campaign_id']) : 0;

        global $wpdb;
        $table_articles = $wpdb->prefix . 'aicf_articles';

        if ($campaign_id > 0) {
            $articles = $wpdb->get_results(
                $wpdb->prepare("SELECT id, title, generation_state, seo_score, error_message FROM {$table_articles} WHERE campaign_id = %d", $campaign_id)
            );
        } else {
            $articles = $wpdb->get_results(
                "SELECT id, title, generation_state, seo_score, error_message FROM {$table_articles} ORDER BY id DESC LIMIT 20"
            );
        }

        // NOTE: admin-script.js's progress bar expects an aggregate shape
        // (percentage/total/completed/processing/failed/pending), not a
        // per-article list. Compute that aggregate here instead of
        // changing the response to a shape nothing on the page could use.
        $total = count($articles);
        $completed = 0;
        $processing = 0;
        $failed = 0;
        $pending = 0;
        $percentage_sum = 0;

        $formatted = [];
        foreach ($articles as $art) {
            $pct = self::map_state_to_percentage($art->generation_state);
            $percentage_sum += $pct;

            if (in_array($art->generation_state, ['ready', 'published'], true)) {
                $completed++;
            } elseif ($art->generation_state === 'failed') {
                $failed++;
            } elseif ($art->generation_state === 'queued') {
                $pending++;
            } else {
                $processing++;
            }

            $formatted[] = [
                'id'            => $art->id,
                'title'         => $art->title ?: 'Generating Title...',
                'state'         => $art->generation_state,
                'progress_pct'  => $pct,
                'seo_score'     => intval($art->seo_score),
                'error_message' => $art->error_message
            ];
        }

        wp_send_json_success([
            'timestamp'  => current_time('mysql'),
            'percentage' => $total > 0 ? round($percentage_sum / $total) : 0,
            'total'      => $total,
            'completed'  => $completed,
            'processing' => $processing,
            'failed'     => $failed,
            'pending'    => $pending,
            'articles'   => $formatted
        ]);
    }

    /**
     * Helper to convert string pipeline states to completion percentage.
     * 
     * @param string $state
     * @return int
     */
    private static function map_state_to_percentage($state) {
        switch ($state) {
            case 'queued':
                return 5;
            case 'researching':
                return 20;
            case 'brief_generated':
                return 40;
            case 'outline_generated':
                return 55;
            case 'generating_sections':
                return 75;
            case 'assembling':
                return 85;
            case 'seo_analyzing':
                return 95;
            case 'ready':
            case 'published':
                return 100;
            case 'failed':
            default:
                return 0;
        }
    }
}