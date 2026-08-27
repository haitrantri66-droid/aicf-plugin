<?php
namespace AICF\Admin\Ajax;

use AICF\AI\AIManager;
use AICF\AI\DTO\AIRequest;

if (!defined('ABSPATH')) {
    exit;
}

class AIAjax {

    public static function init() {
        add_action('wp_ajax_aicf_save_settings', [__CLASS__, 'save_settings']);
        add_action('wp_ajax_aicf_save_keyword', [__CLASS__, 'save_keyword']);
        add_action('wp_ajax_aicf_test_api', [__CLASS__, 'test_api']);
        add_action('wp_ajax_aicf_generate_content', [__CLASS__, 'generate_content']);
    }

    public static function save_settings() {
        @ob_clean();
        check_ajax_referer('aicf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Không đủ quyền truy cập']);
        }

        $openai_key = sanitize_text_field($_POST['openai_key'] ?? '');
        $gemini_key = sanitize_text_field($_POST['gemini_key'] ?? '');
        $provider   = sanitize_text_field($_POST['default_provider'] ?? 'openai');

        // Lọc sạch rác \vert nếu có vô tình dán vào ô input Settings
        $openai_key = str_replace(array('\vert', '\\vert', '\|'), '|', $openai_key);
        $gemini_key = str_replace(array('\vert', '\\vert', '\|'), '|', $gemini_key);

        update_option('aicf_openai_api_key', $openai_key);
        update_option('aicf_gemini_api_key', $gemini_key);
        update_option('aicf_default_provider', $provider);

        wp_send_json_success(['message' => 'Đã lưu Cài đặt thành công!']);
    }

    public static function save_keyword() {
        @ob_clean();
        check_ajax_referer('aicf_admin_nonce', 'nonce');

        global $wpdb;
        $keyword     = sanitize_text_field($_POST['keyword'] ?? '');
        $campaign_id = intval($_POST['campaign_id'] ?? 0);

        if (empty($keyword)) {
            wp_send_json_error(['message' => 'Từ khóa không được để trống!']);
        }

        // Lọc sạch ký tự \vert trong từ khóa
        $keyword = str_replace(array('\vert', '\\vert', '\|'), '|', $keyword);

        $result = $wpdb->insert(
            $wpdb->prefix . 'aicf_keywords',
            [
                'campaign_id' => $campaign_id,
                'keyword'     => $keyword,
                'status'      => 'pending',
                'created_at'  => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Thêm từ khóa thành công!']);
        } else {
            wp_send_json_error(['message' => 'Lỗi DB: ' . $wpdb->last_error]);
        }
    }

    public static function test_api() {
        @ob_clean();
        check_ajax_referer('aicf_admin_nonce', 'nonce');

        $provider = sanitize_text_field($_POST['provider'] ?? 'openai');
        $key = ($provider === 'gemini') ? get_option('aicf_gemini_api_key') : get_option('aicf_openai_api_key');

        if (empty($key)) {
            wp_send_json_error(['message' => 'Chưa điền API Key cho ' . strtoupper($provider)]);
        }

        wp_send_json_success(['message' => 'Kết nối API ' . strtoupper($provider) . ' hợp lệ!']);
    }

    public static function generate_content() {
        @ob_clean();
        check_ajax_referer('aicf_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Không đủ quyền truy cập']);
        }

        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        
        if (empty($keyword)) {
            wp_send_json_error(['message' => 'Từ khóa không hợp lệ!']);
        }

        try {
            if (class_exists('AICF\AI\AIManager')) {
                $aiManager = new AIManager();
                $request = new AIRequest("Write SEO content for: " . $keyword);
                $response = $aiManager->generate_text($request);
                $content = str_replace(array('\vert', '\\vert'), '|', $response->get_content());
                
                wp_send_json_success([
                    'message' => 'Tạo bài viết thành công!',
                    'content' => $content
                ]);
            } else {
                wp_send_json_error(['message' => 'Không tìm thấy module AIManager']);
            }
        } catch (\Throwable $e) {
            $msg = str_replace(array('\vert', '\\vert'), '|', $e->getMessage());
            wp_send_json_error(['message' => $msg]);
        }
    }
}