<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AICF
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Kiểm tra cấu hình có cho phép xóa dữ liệu khi gỡ plugin không
$delete_data = get_option('aicf_delete_data_on_uninstall', 0);

if ($delete_data) {
    global $wpdb;

    // Xóa các custom tables
    $tables = [
        $wpdb->prefix . 'aicf_campaigns',
        $wpdb->prefix . 'aicf_keywords',
        $wpdb->prefix . 'aicf_content_jobs',
        $wpdb->prefix . 'aicf_articles',
        $wpdb->prefix . 'aicf_logs',
        $wpdb->prefix . 'aicf_brand_profiles',
        $wpdb->prefix . 'aicf_knowledge_base',
        $wpdb->prefix . 'aicf_internal_links',
    ];

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    // Xóa các options
    $options = [
        'aicf_db_version',
        'aicf_version',
        'aicf_provider',
        'aicf_openai_api_key',
        'aicf_gemini_api_key',
        'aicf_enable_openai',
        'aicf_enable_gemini',
        'aicf_request_timeout',
        'aicf_delete_data_on_uninstall',
    ];

    foreach ($options as $option) {
        delete_option($option);
    }

    // Dọn dẹp Cron Tasks
    wp_clear_scheduled_hook('aicf_cron_batch_process');
}