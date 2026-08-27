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

        $updated = $wpdb->update(
            $table,
            [
                'name'        => $name,
                'description' => $description,
                'updated_at'  => current_time('mysql')
            ],
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
     * Xóa Chiến dịch -> TỰ ĐỘNG XÓA SẠCH TẤT CẢ TỪ KHÓA LÊN QUAN
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

        // 1. XÓA TOÀN BỘ KEYWORDS THUỘC CAMPAIGN NÀY
        $deleted_keywords = $wpdb->delete($table_keywords, ['campaign_id' => $campaign_id], ['%d']);

        // 2. Gán bài viết thuộc Campaign này về NULL (để giữ bài viết đã sinh)
        $wpdb->update($table_articles, ['campaign_id' => null], ['campaign_id' => $campaign_id]);

        // 3. Xóa chính Campaign
        $deleted_campaign = $wpdb->delete($table_campaigns, ['id' => $campaign_id], ['%d']);

        if ($deleted_campaign) {
            wp_send_json_success([
                'message'          => 'Đã xóa chiến dịch thành công!',
                'deleted_keywords' => intval($deleted_keywords)
            ]);
        }

        wp_send_json_error(['message' => 'Không thể xóa chiến dịch.']);
    }
}
