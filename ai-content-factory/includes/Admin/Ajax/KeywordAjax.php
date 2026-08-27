<?php
namespace AICF\Admin\Ajax;

use AICF\Security\SecurityManager;
use AICF\Keyword\KeywordManager;
use AICF\Keyword\KeywordSuggester;

if (!defined('ABSPATH')) {
    exit;
}

class KeywordAjax {

    public static function init() {
        add_action('wp_ajax_aicf_add_keyword', [__CLASS__, 'add_keyword']);
        add_action('wp_ajax_aicf_delete_keyword', [__CLASS__, 'delete_keyword']);
        add_action('wp_ajax_aicf_bulk_delete_keywords', [__CLASS__, 'bulk_delete_keywords']);
        add_action('wp_ajax_aicf_import_keywords_csv', [__CLASS__, 'import_csv']);
	add_action('wp_ajax_aicf_suggest_keywords', [__CLASS__, 'suggest_keywords']);
        add_action('wp_ajax_aicf_bulk_add_suggested', [__CLASS__, 'bulk_add_suggested']);
    }

    public static function add_keyword() {
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $keyword     = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $intent      = isset($_POST['intent']) ? sanitize_text_field($_POST['intent']) : '';
        $cluster     = isset($_POST['cluster']) ? sanitize_text_field($_POST['cluster']) : '';

        if (empty($keyword) || $campaign_id <= 0) {
            wp_send_json_error(['message' => 'Vui lòng chọn Campaign và nhập Keyword.']);
        }

        $res = KeywordManager::add($campaign_id, $keyword, $intent, $cluster);

        if ($res) {
            wp_send_json_success(['message' => 'Thêm Keyword thành công!']);
        } else {
            wp_send_json_error(['message' => 'Keyword đã tồn tại trong Campaign này.']);
        }
    }

    public static function delete_keyword() {
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id > 0 && KeywordManager::delete($id)) {
            wp_send_json_success(['message' => 'Xóa Keyword thành công.']);
        }
        wp_send_json_error(['message' => 'Không thể xóa Keyword.']);
    }

    public static function bulk_delete_keywords() {
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
        $count = KeywordManager::bulk_delete($ids);

        if ($count > 0) {
            wp_send_json_success(['message' => 'Đã xóa ' . $count . ' Keyword.']);
        }
        wp_send_json_error(['message' => 'Không có Keyword nào được xóa.']);
    }

    public static function import_csv() {
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        if ($campaign_id <= 0 || empty($_FILES['csv_file']['tmp_name'])) {
            wp_send_json_error(['message' => 'Vui lòng chọn Campaign và File CSV.']);
        }

        $count = KeywordManager::import_csv($campaign_id, $_FILES['csv_file']['tmp_name']);
        wp_send_json_success(['message' => 'Đã import thành công ' . $count . ' keywords.']);
    }
    public static function suggest_keywords() {
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $seed_keyword = isset($_POST['seed_keyword']) ? sanitize_text_field($_POST['seed_keyword']) : '';
        $context      = isset($_POST['context']) ? sanitize_textarea_field($_POST['context']) : '';

        if (empty($seed_keyword)) {
            wp_send_json_error(['message' => 'Vui lòng nhập từ khóa hoặc chủ đề gốc.']);
        }

        try {
            $suggestions = KeywordSuggester::suggest($seed_keyword, $context);
            wp_send_json_success(['keywords' => $suggestions]);
        } catch (\Throwable $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public static function bulk_add_suggested() {
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $items       = isset($_POST['items']) ? (array) $_POST['items'] : [];

        if ($campaign_id <= 0 || empty($items)) {
            wp_send_json_error(['message' => 'Thiếu Campaign hoặc danh sách từ khóa.']);
        }

        $added = 0;
        foreach ($items as $item) {
            $keyword  = sanitize_text_field($item['keyword'] ?? '');
            $intent   = sanitize_text_field($item['intent'] ?? '');
            $cluster  = sanitize_text_field($item['cluster'] ?? '');
            $priority = intval($item['priority'] ?? 3);

            if (empty($keyword)) continue;

            if (KeywordManager::add($campaign_id, $keyword, $intent, $cluster, $priority)) {
                $added++;
            }
        }

        if ($added > 0) {
            wp_send_json_success(['message' => "Đã thêm {$added} từ khóa vào Campaign."]);
        }
        wp_send_json_error(['message' => 'Không thêm được từ khóa nào (có thể đã trùng lặp).']);
    }
        
}