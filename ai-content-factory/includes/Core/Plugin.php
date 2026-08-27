<?php
namespace AICF\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_components();
    }

    private function init_components() {
        if (class_exists('AICF\Database\SchemaManager')) {
            \AICF\Database\SchemaManager::init();
        }

        if (is_admin()) {
            if (class_exists('AICF\Admin\AdminManager')) {
                $admin_manager = new \AICF\Admin\AdminManager();
                $admin_manager->init();
            }

            if (class_exists('AICF\Admin\AssetLoader')) {
                \AICF\Admin\AssetLoader::init();
            }

            if (class_exists('AICF\Admin\Ajax\AIAjax')) {
                \AICF\Admin\Ajax\AIAjax::init();
            }
            if (class_exists('AICF\Admin\Ajax\ProgressAjax')) {
                \AICF\Admin\Ajax\ProgressAjax::init();
            }
            if (class_exists('AICF\Admin\Ajax\CampaignAjax')) {
                \AICF\Admin\Ajax\CampaignAjax::init();
            }
            if (class_exists('AICF\Admin\Ajax\KeywordAjax')) {
                \AICF\Admin\Ajax\KeywordAjax::init();
            }
            if (class_exists('AICF\Admin\Ajax\ArticleAjax')) {
                \AICF\Admin\Ajax\ArticleAjax::init();
            }
        }

        if (class_exists('AICF\Engine\TaskScheduler')) {
            $scheduler = new \AICF\Engine\TaskScheduler();
            $scheduler->init();
        }
    }

    public function run() {}
}