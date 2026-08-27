<?php
namespace AICF\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class Logger {
    public static function log($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'aicf_logs';

        $status = isset($data['status']) ? sanitize_text_field($data['status']) : 'info';

        $insert_data = [
            // level/object_type are what LogsPage.php actually reads and
            // displays, so map the AI-request status onto them too.
            'level'          => ($status === 'failed' || $status === 'error') ? 'error' : 'info',
            'object_type'    => isset($data['request_type']) ? sanitize_text_field($data['request_type']) : 'ai_request',
            'provider'       => isset($data['provider']) ? sanitize_text_field($data['provider']) : null,
            'model'          => isset($data['model']) ? sanitize_text_field($data['model']) : null,
            'request_type'   => isset($data['request_type']) ? sanitize_text_field($data['request_type']) : 'general',
            'status'         => $status,
            'duration'       => isset($data['duration']) ? floatval($data['duration']) : 0,
            'input_tokens'   => isset($data['input_tokens']) ? intval($data['input_tokens']) : 0,
            'output_tokens'  => isset($data['output_tokens']) ? intval($data['output_tokens']) : 0,
            'total_tokens'   => isset($data['total_tokens']) ? intval($data['total_tokens']) : 0,
            'estimated_cost' => isset($data['estimated_cost']) ? floatval($data['estimated_cost']) : 0.0,
            'message'        => isset($data['message']) ? sanitize_textarea_field($data['message']) : '',
            'created_at'     => current_time('mysql'),
        ];

        $wpdb->insert($table_name, $insert_data);
    }

    /**
     * Ghi log muc Info (thong tin chung)
     */
    public static function info($message, $request_type = 'system') {
        self::log([
            'status'       => 'info',
            'request_type' => $request_type,
            'message'      => $message,
        ]);
    }

    /**
     * Ghi log muc Error (loi)
     */
    public static function error($message, $request_type = 'system') {
        self::log([
            'status'       => 'failed',
            'request_type' => $request_type,
            'message'      => $message,
        ]);
    }
}