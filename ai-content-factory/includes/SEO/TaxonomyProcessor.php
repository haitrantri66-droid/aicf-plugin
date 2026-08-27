<?php
namespace AICF\SEO;

if (!defined('ABSPATH')) {
    exit;
}

class TaxonomyProcessor {

    /**
     * Tự động xác định và gán Category phù hợp cho bài viết
     */
    public static function assign_category($title, $content, $default_cat_id = 0, $allow_create = false) {
        $categories = get_categories(['hide_empty' => false]);
        $best_cat_id = $default_cat_id > 0 ? $default_cat_id : 1; // Default Uncategorized
        $highest_score = 0;

        $text_corpus = mb_strtolower($title . ' ' . wp_strip_all_tags($content));

        foreach ($categories as $cat) {
            $cat_name = mb_strtolower($cat->name);
            if (empty($cat_name) || $cat_name === 'uncategorized' || $cat_name === 'chưa phân loại') {
                continue;
            }

            if (mb_strpos($text_corpus, $cat_name) !== false) {
                $score = mb_substr_count($text_corpus, $cat_name);
                if ($score > $highest_score) {
                    $highest_score = $score;
                    $best_cat_id = $cat->term_id;
                }
            }
        }

        return [$best_cat_id];
    }

    /**
     * Tự động lọc & tạo/gán Tag chuẩn hóa cho bài viết
     */
    public static function generate_and_assign_tags($wp_post_id, $keyword, $content, $limit = 5) {
        if (!$wp_post_id) return;

        $assigned_tags = [];

        // 1. Tag chính từ Keyword gốc
        $main_tag = self::normalize_tag($keyword);
        if (!empty($main_tag)) {
            $assigned_tags[] = $main_tag;
        }

        // 2. Lấy danh sách Tag sẵn có của WP để khớp
        $existing_tags = get_tags(['hide_empty' => false]);
        $content_lower = mb_strtolower(wp_strip_all_tags($content));

        foreach ($existing_tags as $tag) {
            if (count($assigned_tags) >= $limit) break;
            
            $normalized = self::normalize_tag($tag->name);
            if (!empty($normalized) && !in_array($normalized, $assigned_tags)) {
                if (mb_strpos($content_lower, $normalized) !== false) {
                    $assigned_tags[] = $normalized;
                }
            }
        }

        if (!empty($assigned_tags)) {
            wp_set_post_tags($wp_post_id, $assigned_tags, true);
        }
    }

    private static function normalize_tag($tag_name) {
        $tag = mb_strtolower(trim($tag_name));
        $tag = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $tag);
        return trim($tag);
    }
}
