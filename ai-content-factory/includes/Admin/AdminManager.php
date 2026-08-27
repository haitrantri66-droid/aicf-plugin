<?php
namespace AICF\Admin;

use AICF\Admin\Dashboard\DashboardPage;
use AICF\Admin\Campaigns\CampaignsPage;
use AICF\Admin\Keywords\KeywordsPage;
use AICF\Admin\Articles\ArticlesPage;
use AICF\Admin\Logs\LogsPage;
use AICF\Admin\Settings;

if (!defined('ABSPATH')) {
    exit;
}

class AdminManager {

    public function init() {
        add_action('admin_menu', [$this, 'register_menu_pages']);
    }

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