<?php
namespace AICF\Admin\Ajax;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignAjax {

    public static function init() {
        add_action('wp_ajax_aicf_save_campaign', [__CLASS__, 'save_campaign']);
        add_action('wp_ajax_aicf_delete_campaign', [__CLASS__, 'delete_campaign']);
    }

    public static function save_campaign() {
        @ob_clean(); // Xóa rác output buffer nếu có warning PHP

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'aicf_admin_nonce')) {
            wp_send_json_error(['message' => 'Lỗi bảo mật Nonce. Hãy F5 làm mới trang!']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aicf_campaigns';

        $title       = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $target_lang = isset($_POST['target_language']) ? sanitize_text_field($_POST['target_language']) : 'vi';
        $tone        = isset($_POST['tone_of_voice']) ? sanitize_text_field($_POST['tone_of_voice']) : 'professional';
        $provider    = isset($_POST['ai_provider']) ? sanitize_text_field($_POST['ai_provider']) : 'openai';
        $model       = isset($_POST['ai_model']) ? sanitize_text_field($_POST['ai_model']) : 'gpt-4o-mini';

        if (empty($title)) {
            wp_send_json_error(['message' => 'Tên chiến dịch không được để trống!']);
        }

        $result = $wpdb->insert(
            $table,
            [
                'title'           => $title,
                'target_language' => $target_lang,
                'tone_of_voice'   => $tone,
                'ai_provider'     => $provider,
                'ai_model'        => $model,
                'status'          => 'active',
                'created_at'      => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result !== false) {
            wp_send_json_success(['message' => 'Tạo chiến dịch thành công!', 'campaign_id' => $wpdb->insert_id]);
        } else {
            wp_send_json_error(['message' => 'Lỗi Database: ' . $wpdb->last_error]);
        }
    }

    public static function delete_campaign() {
        @ob_clean();
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'aicf_admin_nonce')) {
            wp_send_json_error(['message' => 'Lỗi xác thực bảo mật']);
        }

        global $wpdb;
        $id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

        if ($id > 0) {
            $wpdb->delete($wpdb->prefix . 'aicf_campaigns', ['id' => $id], ['%d']);
            wp_send_json_success(['message' => 'Xóa chiến dịch thành công']);
        }
        wp_send_json_error(['message' => 'ID không hợp lệ']);
    }
}