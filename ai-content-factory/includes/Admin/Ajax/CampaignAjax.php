<?php
namespace AICF\Admin\Ajax;

use AICF\Campaign\CampaignManager;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignAjax {

    public static function init() {
        add_action('wp_ajax_aicf_save_campaign', [__CLASS__, 'save_campaign']);
        add_action('wp_ajax_aicf_delete_campaign', [__CLASS__, 'delete_campaign']);
    }

    public static function save_campaign() {
        @ob_clean();

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'aicf_admin_nonce')) {
            wp_send_json_error(['message' => 'Lỗi bảo mật Nonce. Hãy F5 làm mới trang!']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $name        = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : (isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '');

        if (empty($name)) {
            wp_send_json_error(['message' => 'Tên chiến dịch không được để trống!']);
        }

        $payload = [
            'name'                  => $name,
            'description'           => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'website'               => isset($_POST['website']) ? esc_url_raw($_POST['website']) : '',
            'language'              => isset($_POST['language']) ? sanitize_text_field($_POST['language']) : (isset($_POST['target_language']) ? sanitize_text_field($_POST['target_language']) : 'vi'),
            'country'               => isset($_POST['country']) ? sanitize_text_field($_POST['country']) : 'VN',
            'target_location'      => isset($_POST['target_location']) ? sanitize_text_field($_POST['target_location']) : '',
            'category_id'           => isset($_POST['category_id']) ? intval($_POST['category_id']) : 0,
            'publishing_mode'      => isset($_POST['publishing_mode']) ? sanitize_text_field($_POST['publishing_mode']) : 'draft',
            'ai_provider'          => isset($_POST['ai_provider']) ? sanitize_text_field($_POST['ai_provider']) : 'openai',
            'ai_model'             => isset($_POST['ai_model']) ? sanitize_text_field($_POST['ai_model']) : 'gpt-4o-mini',
            'daily_generate_limit'  => isset($_POST['daily_generate_limit']) ? intval($_POST['daily_generate_limit']) : 5,
            'daily_publish_limit'   => isset($_POST['daily_publish_limit']) ? intval($_POST['daily_publish_limit']) : 3,
            'auto_category'         => isset($_POST['auto_category']) ? intval($_POST['auto_category']) : 1,
            'allow_create_category' => isset($_POST['allow_create_category']) ? intval($_POST['allow_create_category']) : 0,
            'auto_tags'             => isset($_POST['auto_tags']) ? intval($_POST['auto_tags']) : 1,
            'auto_internal_links'   => isset($_POST['auto_internal_links']) ? intval($_POST['auto_internal_links']) : 1,
            'check_duplicate'       => isset($_POST['check_duplicate']) ? intval($_POST['check_duplicate']) : 1,
        ];

        if ($campaign_id > 0) {
            $updated = CampaignManager::update($campaign_id, $payload);
            if ($updated !== false) {
                wp_send_json_success(['message' => 'Cập nhật chiến dịch thành công!', 'campaign_id' => $campaign_id]);
            } else {
                wp_send_json_error(['message' => 'Không thể cập nhật chiến dịch.']);
            }
        } else {
            $new_id = CampaignManager::create($payload);
            if ($new_id) {
                wp_send_json_success(['message' => 'Tạo chiến dịch mới thành công!', 'campaign_id' => $new_id]);
            } else {
                global $wpdb;
                wp_send_json_error(['message' => 'Lỗi Database: ' . $wpdb->last_error]);
            }
        }
    }

    public static function delete_campaign() {
        @ob_clean();
        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'aicf_admin_nonce')) {
            wp_send_json_error(['message' => 'Lỗi xác thực bảo mật']);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện thao tác này.']);
        }

        $id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

        if ($id > 0) {
            $deleted = CampaignManager::delete($id);
            if ($deleted) {
                wp_send_json_success(['message' => 'Xóa chiến dịch thành công']);
            } else {
                wp_send_json_error(['message' => 'Không thể xóa chiến dịch.']);
            }
        }
        wp_send_json_error(['message' => 'ID chiến dịch không hợp lệ']);
    }
}
