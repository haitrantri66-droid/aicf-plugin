<?php
namespace AICF\SEO;

if (!defined('ABSPATH')) {
    exit;
}

class DuplicateChecker {

    private $threshold;

    public function __construct($threshold = 80) {
        $this->threshold = (float)$threshold;
    }

    /**
     * Kiểm tra độ trùng lặp bài viết với các bài hiện có trên WordPress
     * 
     * @param string $new_title
     * @param string $new_content
     * @param int $exclude_post_id
     * @return array
     */
    public function check_content($new_title, $new_content, $exclude_post_id = 0) {
        global $wpdb;

        $exclude_id = intval($exclude_post_id);
        $sql = "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_status IN ('publish', 'draft') AND post_type = 'post'";
        if ($exclude_id > 0) {
            $sql .= $wpdb->prepare(" AND ID != %d", $exclude_id);
        }

        $existing_posts = $wpdb->get_results($sql);

        if (empty($existing_posts)) {
            return [
                'is_duplicate' => false,
                'score'        => 0,
                'matched_with' => null
            ];
        }

        $clean_new_content = mb_strtolower(wp_strip_all_tags($new_content));
        $clean_new_title   = mb_strtolower(trim($new_title));

        foreach ($existing_posts as $post) {
            $clean_old_title   = mb_strtolower(trim($post->post_title));
            $clean_old_content = mb_strtolower(wp_strip_all_tags($post->post_content));

            $title_sim   = $this->calculate_similarity($clean_new_title, $clean_old_title);
            $content_sim = $this->calculate_similarity($clean_new_content, $clean_old_content);

            // Trọng số: Title 30%, Content 70%
            $total_sim = ($title_sim * 0.3) + ($content_sim * 0.7);

            if ($total_sim >= $this->threshold) {
                return [
                    'is_duplicate' => true,
                    'score'        => round($total_sim, 2),
                    'matched_with' => $post->ID
                ];
            }
        }

        return [
            'is_duplicate' => false,
            'score'        => 0,
            'matched_with' => null
        ];
    }

    private function calculate_similarity($str1, $str2) {
        if (empty($str1) || empty($str2)) {
            return 0;
        }

        // Tối ưu bộ nhớ cho chuỗi dài
        if (mb_strlen($str1) > 5000) $str1 = mb_substr($str1, 0, 5000);
        if (mb_strlen($str2) > 5000) $str2 = mb_substr($str2, 0, 5000);

        similar_text($str1, $str2, $percent);
        return $percent;
    }
}
