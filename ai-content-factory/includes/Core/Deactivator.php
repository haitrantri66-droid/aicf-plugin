<?php
namespace AICF\Core;

use AICF\Engine\TaskScheduler;

if (!defined('ABSPATH')) {
    exit;
}

class Deactivator {

    /**
     * Plugin deactivation handler.
     */
    public static function deactivate() {
        // Clear background cron jobs
        TaskScheduler::clear_cron_job();

        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }
}