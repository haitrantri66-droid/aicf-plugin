<?php
namespace AICF\SEO;

if (!defined('ABSPATH')) {
    exit;
}

class SEOAnalyzer {

    /**
     * Perform comprehensive SEO analysis on HTML content and return metrics with score.
     * 
     * @param string $content HTML content
     * @param string $primary_keyword Target keyword
     * @param string $title Article Title
     * @return array Analytical metrics including seo_score (0-100), meta_title, and meta_description
     */
    public static function analyze($content, $primary_keyword, $title = '') {
        $clean_text = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_text);

        $kw_matches = 0;
        if (!empty($primary_keyword)) {
            $kw_matches = substr_count(mb_strtolower($clean_text), mb_strtolower($primary_keyword));
        }

        $keyword_density = ($word_count > 0) ? round(($kw_matches / $word_count) * 100, 2) : 0;

        // Check Headings
        $has_h2 = (preg_match('/<h2[^>]*>/i', $content) === 1);
        $has_h3 = (preg_match('/<h3[^>]*>/i', $content) === 1);

        // Check Links & Images
        $has_internal_link = (preg_match('/<a[^>]+href=["\'][^"\']+["\'][^>]*>/i', $content) === 1);
        $has_image = (preg_match('/<img[^>]+src=["\'][^"\']+["\'][^>]*>/i', $content) === 1);
        $has_image_alt = (preg_match('/<img[^>]+alt=["\'][^"\']+["\'][^>]*>/i', $content) === 1);

        // Calculate SEO Score
        $score = 0;

        // Word count evaluation (max 30 pts)
        if ($word_count >= 1500) {
            $score += 30;
        } elseif ($word_count >= 800) {
            $score += 20;
        } elseif ($word_count >= 400) {
            $score += 10;
        }

        // Keyword Density evaluation (max 20 pts)
        if ($keyword_density >= 0.8 && $keyword_density <= 2.5) {
            $score += 20;
        } elseif ($keyword_density > 0 && $keyword_density < 0.8) {
            $score += 10;
        }

        // Structure Heading evaluation (max 20 pts)
        if ($has_h2) $score += 12;
        if ($has_h3) $score += 8;

        // Links presence (max 15 pts)
        if ($has_internal_link) $score += 15;

        // Media & Alt presence (max 15 pts)
        if ($has_image) $score += 10;
        if ($has_image_alt) $score += 5;

        // Generate Meta Title & Description
        $meta_title = !empty($title) ? $title : $primary_keyword;
        if (mb_strlen($meta_title) > 60) {
            $meta_title = mb_substr($meta_title, 0, 57) . '...';
        }

        $meta_description = mb_substr($clean_text, 0, 155);
        if (mb_strlen($clean_text) > 155) {
            $meta_description .= '...';
        }

        return [
            'seo_score'        => min(100, $score),
            'word_count'       => $word_count,
            'keyword_density'  => $keyword_density,
            'meta_title'       => $meta_title,
            'meta_description' => $meta_description
        ];
    }
}