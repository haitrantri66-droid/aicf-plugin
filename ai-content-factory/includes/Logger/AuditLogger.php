<?php
namespace AICF\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class AuditLogger {

    public static function log($message, $level = 'info', $object_type = 'system', $object_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_logs';

        $inserted = $wpdb->insert(
            $table,
            [
                'level'       => sanitize_text_field($level),
                'object_type' => sanitize_text_field($object_type),
                'object_id'   => intval($object_id),
                'message'     => sanitize_textarea_field($message),
                'created_at'  => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%s', '%s']
        );

        return (bool) $inserted;
    }

    public static function info($message, $object_type = 'system', $object_id = 0) {
        return self::log($message, 'info', $object_type, $object_id);
    }

    public static function warning($message, $object_type = 'system', $object_id = 0) {
        return self::log($message, 'warning', $object_type, $object_id);
    }

    public static function error($message, $object_type = 'system', $object_id = 0) {
        return self::log($message, 'error', $object_type, $object_id);
    }
}