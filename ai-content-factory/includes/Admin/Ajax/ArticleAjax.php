<?php
namespace AICF\Admin\Ajax;

use AICF\Engine\ContentPipeline;

if (!defined('ABSPATH')) {
    exit;
}

class ArticleAjax {

    public static function init() {
        add_action('wp_ajax_aicf_generate_article', [__CLASS__, 'generate_article']);
    }

    public static function generate_article() {
        @ob_clean();
        check_ajax_referer('aicf_admin_nonce', 'nonce');

        $keyword_id = isset($_POST['keyword_id']) ? intval($_POST['keyword_id']) : 0;

        if ($keyword_id <= 0) {
            wp_send_json_error(['message' => 'ID Từ khóa không hợp lệ']);
        }

        try {
            $result = ContentPipeline::process_keyword($keyword_id);

            if ($result) {
                wp_send_json_success(['message' => 'Đã tạo bài viết AI chuẩn SEO thành công!']);
            } else {
                wp_send_json_error(['message' => 'Lỗi khi gọi AI tạo bài viết. Vui lòng kiểm tra lại API Key trong Cài Đặt.']);
            }
        } catch (\Throwable $e) {
            // Chặn và làm sạch mọi ký tự \vert trong thông báo lỗi trước khi gửi về client
            $clean_error = str_replace(array('\vert', '\\vert', '\|'), '|', $e->getMessage());
            wp_send_json_error(['message' => 'Lỗi xử lý AI: ' . $clean_error]);
        }
    }
}