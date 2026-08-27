<?php
namespace AICF\Keyword;

if (!defined('ABSPATH')) {
    exit;
}

class KeywordManager {

    public static function add($campaign_id, $keyword, $intent = '', $cluster = '', $priority = 3, $target_url = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_keywords';

        $campaign_id = intval($campaign_id);
        $keyword = sanitize_text_field($keyword);

        if (empty($keyword) || $campaign_id <= 0) return false;

        // Check duplicate trong cùng campaign
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE campaign_id = %d AND keyword = %s",
            $campaign_id,
            $keyword
        ));

        if ($exists) return false; // Tránh trùng lặp

        $insert_data = [
            'campaign_id' => $campaign_id,
            'keyword'     => $keyword,
            'intent'      => sanitize_text_field($intent),
            'cluster'     => sanitize_text_field($cluster),
            'priority'    => intval($priority),
            'status'      => 'pending',
            'target_url'  => esc_url_raw($target_url),
            'created_at'  => current_time('mysql'),
        ];

        $result = $wpdb->insert($table, $insert_data);
        return $result ? $wpdb->insert_id : false;
    }

    public static function delete($id) {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'aicf_keywords', ['id' => intval($id)]);
    }

    public static function bulk_delete($ids) {
        global $wpdb;
        if (!is_array($ids) || empty($ids)) return 0;

        $sanitized_ids = array_map('intval', $ids);
        $in_clause = implode(',', $sanitized_ids);
        return $wpdb->query("DELETE FROM {$wpdb->prefix}aicf_keywords WHERE id IN ({$in_clause})");
    }

    public static function import_csv($campaign_id, $file_path) {
        if (!file_exists($file_path)) return false;

        $handle = fopen($file_path, 'r');
        if (!$handle) return false;

        $imported = 0;
        $row = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $row++;
            if ($row === 1 && strtolower(trim($data[0])) === 'keyword') {
                continue; // Skip header
            }

            $keyword    = isset($data[0]) ? trim($data[0]) : '';
            $intent     = isset($data[1]) ? trim($data[1]) : '';
            $cluster    = isset($data[2]) ? trim($data[2]) : '';
            $priority   = isset($data[3]) ? intval($data[3]) : 3;
            $target_url = isset($data[4]) ? trim($data[4]) : '';

            if (!empty($keyword)) {
                $res = self::add($campaign_id, $keyword, $intent, $cluster, $priority, $target_url);
                if ($res) $imported++;
            }
        }

        fclose($handle);
        return $imported;
    }

    public static function export_csv($campaign_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_keywords';

        if ($campaign_id > 0) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE campaign_id = %d ORDER BY id DESC", intval($campaign_id)), ARRAY_A);
        } else {
            $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=keywords_export_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Keyword', 'Intent', 'Cluster', 'Priority', 'Status', 'Target URL']);

        foreach ($results as $row) {
            fputcsv($output, [
                $row['keyword'],
                $row['intent'],
                $row['cluster'],
                $row['priority'],
                $row['status'],
                $row['target_url']
            ]);
        }
        fclose($output);
        exit;
    }

    public static function get_list($campaign_id = 0, $search = '', $status = '', $limit = 20, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_keywords';

        $where = ["1=1"];
        $params = [];

        if ($campaign_id > 0) {
            $where[] = "campaign_id = %d";
            $params[] = intval($campaign_id);
        }

        if (!empty($search)) {
            $where[] = "keyword LIKE %s";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        if (!empty($status)) {
            $where[] = "status = %s";
            $params[] = sanitize_text_field($status);
        }

        $where_sql = implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $params[] = intval($limit);
        $params[] = intval($offset);

        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
}