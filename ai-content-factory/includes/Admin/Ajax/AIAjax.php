<?php
namespace AICF\Admin\Ajax;

use AICF\Security\SecurityManager;
use AICF\Keyword\KeywordManager;
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

    /**
     * Lưu cài đặt API Keys & Provider mặc định
     */
    public static function save_settings() {
        @ob_clean();
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $openai_key = sanitize_text_field(wp_unslash($_POST['openai_key'] ?? ''));
        $gemini_key = sanitize_text_field(wp_unslash($_POST['gemini_key'] ?? ''));
        $provider   = sanitize_text_field(wp_unslash($_POST['default_provider'] ?? 'openai'));

        // Lọc sạch rác \vert nếu có vô tình dán vào ô input
        $openai_key = str_replace(['\vert', '\\vert', '\|'], '|', $openai_key);
        $gemini_key = str_replace(['\vert', '\\vert', '\|'], '|', $gemini_key);

        update_option('aicf_openai_api_key', $openai_key);
        update_option('aicf_gemini_api_key', $gemini_key);
        update_option('aicf_default_provider', $provider);

        wp_send_json_success(['message' => 'Đã lưu Cài đặt thành công!']);
    }

    /**
     * Thêm nhanh từ khóa đơn lẻ
     */
    public static function save_keyword() {
        @ob_clean();
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $keyword     = sanitize_text_field(wp_unslash($_POST['keyword'] ?? ''));
        $campaign_id = intval($_POST['campaign_id'] ?? 0);

        if (empty($keyword) || $campaign_id <= 0) {
            wp_send_json_error(['message' => 'Vui lòng chọn Campaign và nhập Từ khóa!']);
        }

        // Lọc sạch ký tự \vert trong từ khóa
        $keyword = str_replace(['\vert', '\\vert', '\|'], '|', $keyword);

        $res = KeywordManager::add($campaign_id, $keyword);

        if ($res) {
            wp_send_json_success(['message' => 'Thêm từ khóa thành công!']);
        } else {
            wp_send_json_error(['message' => 'Từ khóa đã tồn tại trong Campaign này.']);
        }
    }

    /**
     * Kiểm tra kết nối API Key
     */
    public static function test_api() {
        @ob_clean();
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $provider = sanitize_text_field(wp_unslash($_POST['provider'] ?? 'openai'));
        $key      = ($provider === 'gemini') ? get_option('aicf_gemini_api_key') : get_option('aicf_openai_api_key');

        if (empty($key)) {
            wp_send_json_error(['message' => 'Chưa điền API Key cho ' . strtoupper($provider)]);
        }

        try {
            if (class_exists('AICF\AI\AIManager')) {
                $aiManager = new AIManager();
                // Thực hiện ping thử 1 request ngắn tới Provider
                $request  = new AIRequest("Reply with 'OK'");
                $response = $aiManager->generate_text($request, $provider);

                if (!empty($response->get_content())) {
                    wp_send_json_success(['message' => 'Kết nối API ' . strtoupper($provider) . ' thành công!']);
                }
            }

            wp_send_json_success(['message' => 'API Key ' . strtoupper($provider) . ' đã được ghi nhận.']);
        } catch (\Throwable $e) {
            $msg = str_replace(['\vert', '\\vert', '\|'], '|', $e->getMessage());
            wp_send_json_error(['message' => 'Lỗi kết nối API: ' . esc_html($msg)]);
        }
    }

    /**
     * Test sinh nội dung AI nhanh từ Admin Panel
     */
    public static function generate_content() {
        @ob_clean();
        
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $keyword = sanitize_text_field(wp_unslash($_POST['keyword'] ?? ''));

        if (empty($keyword)) {
            wp_send_json_error(['message' => 'Từ khóa không hợp lệ!']);
        }

        try {
            if (class_exists('AICF\AI\AIManager')) {
                $aiManager = new AIManager();
                $request   = new AIRequest("Write SEO content for: " . $keyword);
                $response  = $aiManager->generate_text($request);
                
                $content = str_replace(['\vert', '\\vert', '\|'], '|', $response->get_content());

                wp_send_json_success([
                    'message' => 'Tạo bài viết thành công!',
                    'content' => $content
                ]);
            } else {
                wp_send_json_error(['message' => 'Không tìm thấy module AIManager.']);
            }
        } catch (\Throwable $e) {
            $msg = str_replace(['\vert', '\\vert', '\|'], '|', $e->getMessage());
            wp_send_json_error(['message' => 'Lỗi AI: ' . esc_html($msg)]);
        }
    }
}
