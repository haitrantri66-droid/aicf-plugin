<?php
namespace AICF\Engine;

use AICF\Article\ArticleRepository;

if (!defined('ABSPATH')) {
    exit;
}

class BatchProcessor {

    /**
     * Process next queued article using ContentPipeline.
     * 
     * @return bool
     */
    public static function process_next_in_queue() {
        global $wpdb;

        $table_articles = $wpdb->prefix . 'aicf_articles';
        
        $queued_article = $wpdb->get_row(
            "SELECT id FROM {$table_articles} WHERE generation_state = 'queued' ORDER BY id ASC LIMIT 1"
        );

        if (!$queued_article) {
            return false;
        }

        return ContentPipeline::run($queued_article->id);
    }
}