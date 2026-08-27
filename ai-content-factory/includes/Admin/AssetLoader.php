<?php
namespace AICF\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AssetLoader {

    public static function init() {
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets($hook) {
        
wp_enqueue_style('aicf-admin-style', AICF_URL . 'admin/css/admin-style.css', [], '1.1.1');
wp_enqueue_script('aicf-admin-script', AICF_URL . 'admin/js/admin-script.js', ['jquery'], '1.1.1', true);
        wp_localize_script('aicf-admin-script', 'aicfAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('aicf_admin_nonce')
        ]);
    }
}