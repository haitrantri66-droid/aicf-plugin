<?php
namespace AICF\Admin\Ajax;

use AICF\Security\SecurityManager;
use AICF\Engine\ContentPipeline;

if (!defined('ABSPATH')) {
    exit;
}

class ArticleAjax {

    public static function init() {
        add_action('wp_ajax_aicf_generate_article', [__CLASS__, 'generate_article']);
    }

    /**
     * AJAX Handler để kích hoạt tiến trình tạo bài viết AI cho một keyword_id
     */
    public static function generate_article() {
        @ob_clean();
        
        // Tăng thời gian thực thi cho PHP khi gọi API AI
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $keyword_id = isset($_POST['keyword_id']) ? intval($_POST['keyword_id']) : 0;

        if ($keyword_id <= 0) {
            wp_send_json_error(['message' => 'ID Từ khóa không hợp lệ.']);
        }

        try {
            $result = ContentPipeline::process_keyword($keyword_id);

            if ($result) {
                wp_send_json_success([
                    'message' => 'Đã tạo bài viết AI chuẩn SEO thành công!',
                    'data'    => is_array($result) ? $result : []
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Lỗi khi gọi AI tạo bài viết. Vui lòng kiểm tra lại API Key hoặc Log hệ thống.'
                ]);
            }
        } catch (\Throwable $e) {
            // Làm sạch các ký tự đặc biệt/LaTeX dạng \vert hoặc \| trong thông báo lỗi
            $raw_message = $e->getMessage();
            $clean_error = str_replace(['\vert', '\\vert', '\|'], '|', $raw_message);

            wp_send_json_error([
                'message' => 'Lỗi xử lý AI: ' . esc_html($clean_error)
            ]);
        }
    }
}
