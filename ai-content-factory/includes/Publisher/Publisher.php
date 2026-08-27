<?php
namespace AICF\Publisher;

use AICF\Article\ArticleRepository;
use AICF\SEO\SchemaGenerator;

if (!defined('ABSPATH')) {
    exit;
}

class Publisher {

    /**
     * Publish an AICF article record to native WordPress post.
     * 
     * @param int $article_id
     * @param string $post_status 'publish' | 'draft' | 'pending'
     * @param int $author_id
     * @param array $categories Array of category IDs
     * @return int|false WP Post ID on success, false on failure
     */
    public static function publish_to_wp($article_id, $post_status = 'publish', $author_id = 0, array $categories = []) {
        $article = ArticleRepository::get_by_id($article_id);
        if (!$article || empty($article->content)) {
            return false;
        }

        if (empty($author_id)) {
            $author_id = get_current_user_id() ?: 1;
        }

        // Generate Schema JSON-LD and append to post content or meta
        $schema_array = SchemaGenerator::generate_article_schema(
            $article->title,
            $article->meta_description,
            '',
            '',
            get_the_author_meta('display_name', $author_id)
        );
        $schema_script = SchemaGenerator::to_script_tag($schema_array);

        $final_post_content = $article->content . "\n\n" . $schema_script;

        $post_data = [
            'post_title'    => !empty($article->title) ? $article->title : 'Untitled Article',
            'post_content'  => $final_post_content,
            'post_excerpt'  => $article->excerpt ?: '',
            'post_status'   => sanitize_text_field($post_status),
            'post_author'   => intval($author_id),
            'post_type'     => 'post',
        ];

        if (!empty($article->wp_post_id) && get_post($article->wp_post_id)) {
            $post_data['ID'] = intval($article->wp_post_id);
            $wp_post_id = wp_update_post($post_data, true);
        } else {
            $wp_post_id = wp_insert_post($post_data, true);
        }

        if (is_wp_error($wp_post_id) || !$wp_post_id) {
            return false;
        }

        // Save Schema in Meta
        update_post_meta($wp_post_id, '_aicf_json_ld_schema', json_encode($schema_array));

        // Set Categories if provided
        if (!empty($categories)) {
            wp_set_post_categories($wp_post_id, array_map('intval', $categories));
        }

        // Save Meta Title & Meta Description for Yoast / RankMath compatibility
        if (!empty($article->meta_title)) {
            update_post_meta($wp_post_id, '_yoast_wpseo_title', $article->meta_title);
            update_post_meta($wp_post_id, 'rank_math_title', $article->meta_title);
        }

        if (!empty($article->meta_description)) {
            update_post_meta($wp_post_id, '_yoast_wpseo_metadesc', $article->meta_description);
            update_post_meta($wp_post_id, 'rank_math_description', $article->meta_description);
        }

        // Update AICF Article Status
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'aicf_articles',
            [
                'wp_post_id' => intval($wp_post_id),
                'status'     => 'published',
                'updated_at' => current_time('mysql')
            ],
            ['id' => intval($article_id)],
            ['%d', '%s', '%s'],
            ['%d']
        );

        return $wp_post_id;
    }
}