<?php
namespace AICF\Campaign;

use AICF\Security\SecurityManager;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignManager {

    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_campaigns';

        $insert_data = [
            'name'            => sanitize_text_field($data['name']),
            'description'     => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
            'website'         => isset($data['website']) ? esc_url_raw($data['website']) : '',
            'language'        => isset($data['language']) ? sanitize_text_field($data['language']) : 'vi',
            'country'         => isset($data['country']) ? sanitize_text_field($data['country']) : 'VN',
            'target_location' => isset($data['target_location']) ? sanitize_text_field($data['target_location']) : '',
            'category_id'     => isset($data['category_id']) ? intval($data['category_id']) : 0,
            'author_id'       => get_current_user_id(),
            'publishing_mode' => isset($data['publishing_mode']) ? sanitize_text_field($data['publishing_mode']) : 'draft',
            'ai_provider'     => isset($data['ai_provider']) ? sanitize_text_field($data['ai_provider']) : 'openai',
            'ai_model'        => isset($data['ai_model']) ? sanitize_text_field($data['ai_model']) : 'gpt-4o-mini',
            'status'          => 'active',
            'created_at'      => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);
        return $result ? $wpdb->insert_id : false;
    }

    public static function update($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_campaigns';

        $update_data = [];
        if (isset($data['name'])) $update_data['name'] = sanitize_text_field($data['name']);
        if (isset($data['description'])) $update_data['description'] = sanitize_textarea_field($data['description']);
        if (isset($data['website'])) $update_data['website'] = esc_url_raw($data['website']);
        if (isset($data['language'])) $update_data['language'] = sanitize_text_field($data['language']);
        if (isset($data['country'])) $update_data['country'] = sanitize_text_field($data['country']);
        if (isset($data['target_location'])) $update_data['target_location'] = sanitize_text_field($data['target_location']);
        if (isset($data['category_id'])) $update_data['category_id'] = intval($data['category_id']);
        if (isset($data['publishing_mode'])) $update_data['publishing_mode'] = sanitize_text_field($data['publishing_mode']);
        if (isset($data['ai_provider'])) $update_data['ai_provider'] = sanitize_text_field($data['ai_provider']);
        if (isset($data['ai_model'])) $update_data['ai_model'] = sanitize_text_field($data['ai_model']);
        if (isset($data['status'])) $update_data['status'] = sanitize_text_field($data['status']);

        return $wpdb->update($table, $update_data, ['id' => intval($id)]);
    }

    public static function delete($id) {
        global $wpdb;
        $campaign_id = intval($id);
        
        // Cascade delete keywords & jobs liên quan
        $wpdb->delete($wpdb->prefix . 'aicf_keywords', ['campaign_id' => $campaign_id]);
        $wpdb->delete($wpdb->prefix . 'aicf_content_jobs', ['campaign_id' => $campaign_id]);
        return $wpdb->delete($wpdb->prefix . 'aicf_campaigns', ['id' => $campaign_id]);
    }

    public static function duplicate($id) {
        global $wpdb;
        $campaign = self::get_by_id($id);
        if (!$campaign) return false;

        $new_data = (array) $campaign;
        unset($new_data['id']);
        $new_data['name'] = $new_data['name'] . ' (Copy)';
        $new_data['created_at'] = current_time('mysql');

        return self::create($new_data);
    }

    public static function get_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aicf_campaigns WHERE id = %d", intval($id)));
    }

    public static function get_all() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aicf_campaigns ORDER BY id DESC");
    }
}