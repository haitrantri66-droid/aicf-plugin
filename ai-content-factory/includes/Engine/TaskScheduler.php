<?php
namespace AICF\Engine;

if (!defined('ABSPATH')) {
    exit;
}

class TaskScheduler {

    /**
     * Initialize cron hooks and actions.
     */
    public function init() {
        add_action('aicf_process_queue_cron', [$this, 'execute_queue_job']);
        add_filter('cron_schedules', [$this, 'add_custom_cron_intervals']);

        self::schedule_cron_job();
    }

    /**
     * Register custom cron intervals.
     */
    public function add_custom_cron_intervals($schedules) {
        $schedules['aicf_five_minutes'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes', 'ai-content-factory')
        ];
        return $schedules;
    }

    /**
     * Schedule recurring cron job if not already scheduled.
     */
    public static function schedule_cron_job() {
        if (!wp_next_scheduled('aicf_process_queue_cron')) {
            wp_schedule_event(time(), 'aicf_five_minutes', 'aicf_process_queue_cron');
        }
    }

    /**
     * Unschedule cron job on plugin deactivation.
     */
    public static function clear_cron_job() {
        $timestamp = wp_next_scheduled('aicf_process_queue_cron');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'aicf_process_queue_cron');
        }
    }

    /**
     * Execute background queue job triggered by WP-Cron.
     */
    public function execute_queue_job() {
        // Step 1: Cleanup stuck/timed-out tasks
        self::recover_stuck_tasks();

        // Step 2: Process next item in queue
        BatchProcessor::process_next_in_queue();
    }

    /**
     * Recover or fail tasks stuck in non-terminal states for longer than 15 minutes.
     */
    public static function recover_stuck_tasks() {
        global $wpdb;

        $table_articles = $wpdb->prefix . 'aicf_articles';
        $fifteen_minutes_ago = date('Y-m-d H:i:s', strtotime('-15 minutes'));

        $stuck_states = ['researching', 'brief_generated', 'outline_generated', 'generating_sections', 'assembling', 'seo_analyzing'];

        $stuck_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$table_articles} 
                 WHERE generation_state IN (" . implode(',', array_fill(0, count($stuck_states), '%s')) . ") 
                 AND updated_at < %s",
                array_merge($stuck_states, [$fifteen_minutes_ago])
            )
        );

        if (!empty($stuck_ids)) {
            foreach ($stuck_ids as $id) {
                $wpdb->update(
                    $table_articles,
                    [
                        'generation_state' => 'failed',
                        'error_message'    => 'Task timed out after 15 minutes of inactivity.',
                        'updated_at'       => current_time('mysql')
                    ],
                    ['id' => intval($id)],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
            }
        }
    }
}