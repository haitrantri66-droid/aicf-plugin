<?php
namespace AICF\Core;

use AICF\Database\SchemaManager;

if (!defined('ABSPATH')) {
    exit;
}

class Activator {

    public static function activate() {
        // Tự động khởi tạo và nâng cấp Database Schema
        if (class_exists('AICF\Database\SchemaManager')) {
            SchemaManager::init();
        }

        // Đăng ký lại Rewrite Rules nếu cần
        flush_rewrite_rules();
    }
}