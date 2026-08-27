<?php
namespace AICF\Article;

use AICF\AI\DTO\ContentBrief;

if (!defined('ABSPATH')) {
    exit;
}

class ArticleRepository {

    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'aicf_articles';
    }

    /**
     * Create a new article record in database.
     * 
     * @param int $campaign_id
     * @param int $keyword_id
     * @param string $title
     * @return int Article ID
     */
    public static function create($campaign_id, $keyword_id, $title = '') {
        global $wpdb;
        
        $table = self::get_table_name();
        $wpdb->insert(
            $table,
            [
                'campaign_id'      => intval($campaign_id),
                'keyword_id'       => intval($keyword_id),
                'title'            => sanitize_text_field($title),
                'status'           => 'draft',
                'generation_state' => 'queued',
                'created_at'       => current_time('mysql'),
                'updated_at'       => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    /**
     * Get article by ID.
     * 
     * @param int $id
     * @return object|null
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = self::get_table_name();
        
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", intval($id))
        );
    }

    /**
     * Update article generation state.
     * Valid states: queued, researching, brief_generated, outline_generated, generating_sections, assembling, seo_analyzing, ready, published, failed, cancelled
     * 
     * @param int $id
     * @param string $state
     * @param string $error_message
     * @return bool
     */
    public static function update_state($id, $state, $error_message = null) {
        global $wpdb;
        $table = self::get_table_name();

        $data = [
            'generation_state' => sanitize_text_field($state),
            'updated_at'       => current_time('mysql')
        ];

        $format = ['%s', '%s'];

        if ($error_message !== null) {
            $data['error_message'] = sanitize_textarea_field($error_message);
            $format[] = '%s';
        }

        return $wpdb->update(
            $table,
            $data,
            ['id' => intval($id)],
            $format,
            ['%d']
        ) !== false;
    }

    /**
     * Save Content Brief to article record.
     * 
     * @param int $id
     * @param ContentBrief $brief
     * @return bool
     */
    public static function save_brief($id, ContentBrief $brief) {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->update(
            $table,
            [
                'brief'            => $brief->toJson(),
                'generation_state' => 'brief_generated',
                'updated_at'       => current_time('mysql')
            ],
            ['id' => intval($id)],
            ['%s', '%s', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Save Article Outline.
     * 
     * @param int $id
     * @param array|string $outline
     * @return bool
     */
    public static function save_outline($id, $outline) {
        global $wpdb;
        $table = self::get_table_name();
        
        $outline_str = is_array($outline) ? wp_json_encode($outline) : $outline;

        return $wpdb->update(
            $table,
            [
                'outline'          => $outline_str,
                'generation_state' => 'outline_generated',
                'updated_at'       => current_time('mysql')
            ],
            ['id' => intval($id)],
            ['%s', '%s', '%s'],
            ['%d']
        ) !== false;
    }

    /**
     * Update full generated content.
     * 
     * @param int $id
     * @param string $content
     * @param string $title
     * @param string $excerpt
     * @return bool
     */
    public static function update_content($id, $content, $title = '', $excerpt = '') {
        global $wpdb;
        $table = self::get_table_name();

        $data = [
            'content'    => wp_kses_post($content),
            'updated_at' => current_time('mysql')
        ];
        $format = ['%s', '%s'];

        if (!empty($title)) {
            $data['title'] = sanitize_text_field($title);
            $format[] = '%s';
        }
        if (!empty($excerpt)) {
            $data['excerpt'] = sanitize_textarea_field($excerpt);
            $format[] = '%s';
        }

        return $wpdb->update(
            $table,
            $data,
            ['id' => intval($id)],
            $format,
            ['%d']
        ) !== false;
    }

    /**
     * Delete article record.
     * 
     * @param int $id
     * @return bool
     */
    public static function delete($id) {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->delete(
            $table,
            ['id' => intval($id)],
            ['%d']
        ) !== false;
    }
}