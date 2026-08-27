<?php
namespace AICF\SEO;

if (!defined('ABSPATH')) {
    exit;
}

class InternalLinker {

    /**
     * Inject relevant internal links into HTML content.
     * 
     * @param string $content HTML content
     * @param int $current_post_id Post ID to exclude from target links
     * @param int $max_links Maximum number of internal links to insert
     * @return string Modified HTML content
     */
    public static function inject_links($content, $current_post_id = 0, $max_links = 3) {
        if (empty($content) || $max_links <= 0) {
            return $content;
        }

        $published_posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            'exclude'        => [$current_post_id],
            'orderby'        => 'rand'
        ]);

        if (empty($published_posts)) {
            return $content;
        }

        $links_inserted = 0;

        foreach ($published_posts as $post) {
            if ($links_inserted >= $max_links) {
                break;
            }

            $title = $post->post_title;
            $permalink = get_permalink($post->ID);

            if (empty($title) || empty($permalink)) {
                continue;
            }

            // Look for matching title phrases in paragraph tags only
            $pattern = '/(<p[^>]*>(?:(?!<\/p>).)*?\b)(' . preg_quote($title, '/') . ')(\b(?:(?!<\/p>).)*?<\/p>)/iu';

            if (preg_match($pattern, $content)) {
                $replacement = '$1<a href="' . esc_url($permalink) . '" title="' . esc_attr($title) . '">$2</a>$3';
                $new_content = preg_replace($pattern, $replacement, $content, 1);

                if ($new_content !== null && $new_content !== $content) {
                    $content = $new_content;
                    $links_inserted++;
                }
            }
        }

        return $content;
    }
}