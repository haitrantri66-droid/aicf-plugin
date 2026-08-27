<?php
namespace AICF\Engine;

use AICF\Logger\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class CronHandler {

    const CRON_HOOK = 'aicf_hourly_article_generation';

    public static function init() {
        add_filter('cron_schedules', [__CLASS__, 'add_hourly_schedule']);
        add_action(self::CRON_HOOK, [__CLASS__, 'execute_cron_job']);

        self::schedule_cron();
    }

    /**
     * Đăng ký Interval 1 giờ cho Cron nếu chưa có
     */
    public static function add_hourly_schedule($schedules) {
        if (!isset($schedules['hourly'])) {
            $schedules['hourly'] = [
                'interval' => 3600,
                'display'  => __('Mỗi 1 giờ', 'aicf')
            ];
        }
        return $schedules;
    }

    /**
     * Kích hoạt lịch Cron ngầm
     */
    public static function schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
            Logger::info("Đã đăng ký WP-Cron tự động tạo bài mỗi 1 giờ.", 'cron');
        }
    }

    /**
     * Thực thi Job
     */
    public static function execute_cron_job() {
        Logger::info("Kích hoạt WP-Cron hàng giờ tự động tạo bài.", 'cron');
        QueueManager::process_single_item();
    }

    /**
     * Hủy lịch Cron khi Deactivate plugin
     */
    public static function clear_cron() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            Logger::info("Đã hủy lịch WP-Cron.", 'cron');
        }
    }
}
