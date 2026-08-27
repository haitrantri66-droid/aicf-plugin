<?php
namespace AICF\Media;

if (!defined('ABSPATH')) {
    exit;
}

class MediaManager {

    /**
     * Download an image from URL and attach it to WordPress Media Library.
     * 
     * @param string $file_url Remote Image URL
     * @param int $post_id WP Post ID to attach image to
     * @param string $alt_text SEO Alt Text
     * @return int|false Attachment ID on success, false on failure
     */
    public static function attach_remote_image($file_url, $post_id = 0, $alt_text = '') {
        if (empty($file_url)) {
            return false;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Download file to temp location
        $tmp = download_url($file_url);
        if (is_wp_error($tmp)) {
            return false;
        }

        $file_array = [
            'name'     => sanitize_file_name(basename(parse_url($file_url, PHP_URL_PATH))) ?: 'aicf-image-' . time() . '.jpg',
            'tmp_name' => $tmp
        ];

        // Sideload file into media library
        $id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return false;
        }

        if (!empty($alt_text)) {
            update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }

        return $id;
    }

    /**
     * Set a media attachment as Featured Image for a post.
     * 
     * @param int $post_id
     * @param int $attachment_id
     * @return bool
     */
    public static function set_featured_image($post_id, $attachment_id) {
        if ($post_id > 0 && $attachment_id > 0) {
            return (bool) set_post_thumbnail($post_id, $attachment_id);
        }
        return false;
    }
}