<?php
namespace AICF\Admin\Ajax;

use AICF\Security\SecurityManager;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignAjax {

    public static function init() {
        add_action('wp_ajax_aicf_toggle_campaign_status', [__CLASS__, 'toggle_status']);
        add_action('wp_ajax_aicf_edit_campaign', [__CLASS__, 'edit_campaign']);
        add_action('wp_ajax_aicf_delete_campaign', [__CLASS__, 'delete_campaign']);
    }

    /**
     * Bật / Tắt trạng thái Campaign (Active <-> Paused)
     */
    public static function toggle_status() {
        @ob_clean();
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        if ($campaign_id <= 0) {
            wp_send_json_error(['message' => 'ID Campaign không hợp lệ.']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aicf_campaigns';

        // Lấy trạng thái hiện tại
        $current_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$table} WHERE id = %d", $campaign_id));

        if (!$current_status) {
            wp_send_json_error(['message' => 'Không tìm thấy Chiến dịch.']);
        }

        $new_status = ($current_status === 'active') ? 'paused' : 'active';

        $updated = $wpdb->update(
            $table,
            ['status' => $new_status, 'updated_at' => current_time('mysql')],
            ['id' => $campaign_id],
            ['%s', '%s'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success([
                'message'    => 'Đã ' . ($new_status === 'active' ? 'kích hoạt' : 'TẠM DỪNG') . ' chiến dịch thành công!',
                'new_status' => $new_status
            ]);
        }

        wp_send_json_error(['message' => 'Không thể cập nhật trạng thái chiến dịch.']);
    }

    /**
     * Cập nhật thông tin Chiến dịch (Tên, Mô tả, Prompt Template...)
     */
    public static function edit_campaign() {
        @ob_clean();
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $name        = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

        if ($campaign_id <= 0 || empty($name)) {
            wp_send_json_error(['message' => 'Tên chiến dịch không được để trống.']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'aicf_campaigns';

        $data = [
            'name'        => $name,
            'description' => $description,
            'updated_at'  => current_time('mysql')
        ];

        $updated = $wpdb->update(
            $table,
            $data,
            ['id' => $campaign_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($updated !== false) {
            wp_send_json_success(['message' => 'Cập nhật chiến dịch thành công!']);
        }

        wp_send_json_error(['message' => 'Không có thay đổi hoặc có lỗi xảy ra.']);
    }

    /**
     * Xóa Chiến dịch (Kèm dọn dẹp data liên quan)
     */
    public static function delete_campaign() {
        @ob_clean();
        check_ajax_referer(SecurityManager::NONCE_ACTION, 'nonce');
        SecurityManager::check_capability();

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        if ($campaign_id <= 0) {
            wp_send_json_error(['message' => 'ID Campaign không hợp lệ.']);
        }

        global $wpdb;
        $table_campaigns = $wpdb->prefix . 'aicf_campaigns';
        $table_keywords  = $wpdb->prefix . 'aicf_keywords';
        $table_articles  = $wpdb->prefix . 'aicf_articles';

        // 1. Xóa các Keywords thuộc Campaign
        $wpdb->delete($table_keywords, ['campaign_id' => $campaign_id], ['%d']);

        // 2. Cập nhật các Bài viết thuộc Campaign về campaign_id = NULL (hoặc xóa luôn tùy quy trình)
        $wpdb->update($table_articles, ['campaign_id' => null], ['campaign_id' => $campaign_id]);

        // 3. Xóa Campaign
        $deleted = $wpdb->delete($table_campaigns, ['id' => $campaign_id], ['%d']);

        if ($deleted) {
            wp_send_json_success(['message' => 'Đã xóa chiến dịch thành công!']);
        }

        wp_send_json_error(['message' => 'Không thể xóa chiến dịch.']);
    }
}
