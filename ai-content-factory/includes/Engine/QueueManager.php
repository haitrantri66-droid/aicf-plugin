<?php
namespace AICF\Engine;

use AICF\Logger\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class QueueManager {

    /**
     * Lấy từ khóa tiếp theo đủ điều kiện để xử lý (Tối đa 1 bài / lần gọi)
     */
    public static function get_next_keyword_to_process() {
        global $wpdb;

        $kw_table  = $wpdb->prefix . 'aicf_keywords';
        $camp_table = $wpdb->prefix . 'aicf_campaigns';
        $art_table  = $wpdb->prefix . 'aicf_articles';

        // Lấy danh sách campaign đang active
        $active_campaigns = $wpdb->get_results("SELECT id, daily_limit FROM $camp_table WHERE status = 'active'");
        if (empty($active_campaigns)) {
            return null;
        }

        $today_start = current_time('Y-m-d 00:00:00');
        $today_end   = current_time('Y-m-d 23:59:59');

        foreach ($active_campaigns as $camp) {
            $daily_limit = (int)$camp->daily_limit;

            // Nếu daily_limit > 0, kiểm tra số lượng bài viết đã hoàn thành hôm nay
            if ($daily_limit > 0) {
                $created_today = (int)$wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $art_table 
                     WHERE campaign_id = %d 
                     AND status = 'completed' 
                     AND created_at BETWEEN %s AND %s",
                    $camp->id,
                    $today_start,
                    $today_end
                ));

                if ($created_today >= $daily_limit) {
                    Logger::info("Chiến dịch ID {$camp->id} đã đạt giới hạn {$daily_limit} bài/ngày ({$created_today}/{$daily_limit}). Đang bỏ qua.", 'queue');
                    continue; // Chuyển sang chiến dịch khác
                }
            }

            // Lấy 1 từ khóa đang chờ (pending) thuộc chiến dịch này
            $keyword = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $kw_table WHERE campaign_id = %d AND status = 'pending' ORDER BY id ASC LIMIT 1",
                $camp->id
            ));

            if ($keyword) {
                return $keyword;
            }
        }

        return null;
    }

    /**
     * Xử lý 1 từ khóa duy nhất theo lịch
     */
    public static function process_single_item() {
        $keyword = self::get_next_keyword_to_process();

        if (!$keyword) {
            Logger::info("Queue trống hoặc tất cả chiến dịch đã đạt giới hạn trong ngày.", 'queue');
            return false;
        }

        global $wpdb;
        $kw_table = $wpdb->prefix . 'aicf_keywords';

        // Đánh dấu từ khóa đang xử lý
        $wpdb->update($kw_table, ['status' => 'processing'], ['id' => $keyword->id]);

        try {
            ContentPipeline::process_keyword($keyword->id);
            return true;
        } catch (\Exception $e) {
            Logger::error("Lỗi khi chạy Queue từ khóa ID {$keyword->id}: " . $e->getMessage(), 'queue');
            $wpdb->update($kw_table, ['status' => 'failed'], ['id' => $keyword->id]);
            return false;
        }
    }
}
