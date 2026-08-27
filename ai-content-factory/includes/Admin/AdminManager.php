<?php
namespace AICF\Admin;

use AICF\Admin\Dashboard\DashboardPage;
use AICF\Admin\Campaigns\CampaignsPage;
use AICF\Admin\Keywords\KeywordsPage;
use AICF\Admin\Articles\ArticlesPage;
use AICF\Admin\Logs\LogsPage;
use AICF\Admin\Settings;

// Import đầy đủ 5 Ajax Handlers
use AICF\Admin\Ajax\ProgressAjax;
use AICF\Admin\Ajax\KeywordAjax;
use AICF\Admin\Ajax\ArticleAjax;
use AICF\Admin\Ajax\AIAjax;
use AICF\Admin\Ajax\CampaignAjax;

if (!defined('ABSPATH')) {
    exit;
}

class AdminManager {

    public function init() {
        // Đăng ký Admin Menu Pages
        add_action('admin_menu', [$this, 'register_menu_pages']);

        // Khởi tạo các Ajax Handlers
        $this->init_ajax_handlers();
    }

    /**
     * Tự động khởi tạo toàn bộ AJAX Endpoints cho hệ thống Admin
     */
    private function init_ajax_handlers() {
        ProgressAjax::init();
        KeywordAjax::init();
        ArticleAjax::init();
        AIAjax::init();
        CampaignAjax::init();
    }

    /**
     * Đăng ký cấu trúc Menu & Submenu cho Plugin
     */
    public function register_menu_pages() {
        add_menu_page(
            __('AI Content Factory', 'ai-content-factory'),
            __('AI Factory', 'ai-content-factory'),
            'manage_options',
            'aicf-dashboard',
            [DashboardPage::class, 'render'],
            'dashicons-superhero',
            30
        );

        add_submenu_page(
            'aicf-dashboard',
            __('Dashboard', 'ai-content-factory'),
            __('Dashboard', 'ai-content-factory'),
            'manage_options',
            'aicf-dashboard',
            [DashboardPage::class, 'render']
        );

        add_submenu_page(
            'aicf-dashboard',
            __('Campaigns', 'ai-content-factory'),
            __('Campaigns', 'ai-content-factory'),
            'manage_options',
            'aicf-campaigns',
            [CampaignsPage::class, 'render']
        );

        add_submenu_page(
            'aicf-dashboard',
            __('Keywords', 'ai-content-factory'),
            __('Keywords', 'ai-content-factory'),
            'manage_options',
            'aicf-keywords',
            [KeywordsPage::class, 'render']
        );

        add_submenu_page(
            'aicf-dashboard',
            __('Articles', 'ai-content-factory'),
            __('Articles', 'ai-content-factory'),
            'manage_options',
            'aicf-articles',
            [ArticlesPage::class, 'render']
        );

        add_submenu_page(
            'aicf-dashboard',
            __('System Logs', 'ai-content-factory'),
            __('System Logs', 'ai-content-factory'),
            'manage_options',
            'aicf-logs',
            [LogsPage::class, 'render']
        );

        add_submenu_page(
            'aicf-dashboard',
            __('Settings', 'ai-content-factory'),
            __('Settings', 'ai-content-factory'),
            'manage_options',
            'aicf-settings',
            [Settings::class, 'render']
        );
    }
}
