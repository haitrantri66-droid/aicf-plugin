<?php
/**
 * Plugin Name: AI Content Factory
 * Plugin URI:   https://example.com/ai-content-factory
 * Description: Automated SEO Content Generation System using AI.
 * Version:      1.2.0
 * Author:       AI Factory Team
 * Text Domain: ai-content-factory
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AICF_PATH', plugin_dir_path(__FILE__));
define('AICF_URL', plugin_dir_url(__FILE__));

// Autoloader chuẩn cho toàn bộ namespace AICF\
spl_autoload_register(function ($class) {
    $prefix = 'AICF\\';
    $base_dir = AICF_PATH . 'includes/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require_once $file;
});

// 1. Tạo bảng Database & Lập lịch Cron ngay khi Active plugin
register_activation_hook(__FILE__, function() {
    if (class_exists('AICF\Database\SchemaManager')) {
        \AICF\Database\SchemaManager::create_tables();
    }

    if (class_exists('AICF\Engine\CronHandler')) {
        \AICF\Engine\CronHandler::schedule_cron();
    }
});

// 2. Hủy lịch Cron khi Deactivate plugin
register_deactivation_hook(__FILE__, function() {
    if (class_exists('AICF\Engine\CronHandler')) {
        \AICF\Engine\CronHandler::clear_cron();
    }
});

// 3. Nạp Scripts & Styles
add_action('admin_enqueue_scripts', function($hook) {
    wp_enqueue_style('aicf-admin-css', AICF_URL . 'admin/css/admin-style.css', [], '1.2.0');
    wp_enqueue_script('aicf-admin-js', AICF_URL . 'admin/js/admin-script.js', ['jquery'], '1.2.0', true);

    wp_localize_script('aicf-admin-js', 'aicfAdmin', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('aicf_admin_nonce')
    ]);
});

// 4. Khởi chạy Core Engine & Đăng ký TẤT CẢ các lớp AJAX Handlers cùng lúc
add_action('plugins_loaded', function() {
    // Khởi tạo WP-Cron Engine
    if (class_exists('AICF\Engine\CronHandler')) {
        \AICF\Engine\CronHandler::init();
    }

    $ajax_classes = [
        'AICF\Admin\Ajax\CampaignAjax',
        'AICF\Admin\Ajax\KeywordAjax',
        'AICF\Admin\Ajax\ArticleAjax',
        'AICF\Admin\Ajax\AIAjax',
        'AICF\Admin\Ajax\ProgressAjax'
    ];

    foreach ($ajax_classes as $class) {
        if (class_exists($class) && method_exists($class, 'init')) {
            $class::init();
        }
    }

    if (class_exists('AICF\Core\Plugin')) {
        \AICF\Core\Plugin::get_instance()->run();
    }
});

// 5. Tự động dọn sạch rác \vert trong Database mỗi khi load WordPress
add_action('init', function() {
    global $wpdb;
    
    // Tẩy sạch rác \vert trong bảng wp_options
    $wpdb->query("UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, '\\\\vert', '|') WHERE option_name LIKE 'aicf_%'");
    $wpdb->query("UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, '\\vert', '|') WHERE option_name LIKE 'aicf_%'");
});
