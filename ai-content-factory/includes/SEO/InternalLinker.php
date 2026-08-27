<?php
namespace AICF\SEO;

if (!defined('ABSPATH')) {
    exit;
}

class InternalLinker {

    /**
     * Chèn liên kết nội bộ an toàn vào nội dung HTML.
     * Tránh chèn vào thẻ Heading (h1-h6), thẻ <a> hiện có, và các thuộc tính HTML.
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
            'posts_per_page' => 30,
            'exclude'        => [$current_post_id],
            'orderby'        => 'rand'
        ]);

        if (empty($published_posts)) {
            return $content;
        }

        $links_inserted = 0;
        $used_urls = [];

        foreach ($published_posts as $post) {
            if ($links_inserted >= $max_links) {
                break;
            }

            $title = trim($post->post_title);
            $permalink = get_permalink($post->ID);

            if (empty($title) || empty($permalink) || in_array($permalink, $used_urls)) {
                continue;
            }

            // Bỏ qua tiêu đề quá ngắn (< 3 ký tự) để tránh replace nhầm
            if (mb_strlen($title) < 3) {
                continue;
            }

            // Pattern nâng cấp: Chỉ tìm từ khóa nằm trong đoạn văn <p> và KHÔNG nằm trong thẻ <a> hoặc <h1-h6>
            $quoted_title = preg_quote($title, '/');
            
            // Tách bài viết thành các đoạn <p> để xử lý an toàn
            $content = preg_replace_callback(
                '/<p[^>]*>(.*?)<\/p>/is',
                function ($matches) use ($quoted_title, $permalink, $title, &$links_inserted, $max_links, &$used_urls) {
                    if ($links_inserted >= $max_links) {
                        return $matches[0];
                    }

                    $p_content = $matches[1];

                    // Nếu đoạn văn đã chứa thẻ <a> hoặc từ khóa đã nằm trong thẻ <a> thì bỏ qua
                    if (preg_match('/<a[^>]*>.*?<\/a>/is', $p_content)) {
                        // Kiểm tra nếu keyword nằm ngoài thẻ <a> trong cùng đoạn <p>
                        $split_by_a = preg_split('/(<a[^>]*>.*?<\/a>)/is', $p_content, -1, PREG_SPLIT_DELIM_CAPTURE);
                        $new_p = '';
                        $replaced_in_p = false;

                        foreach ($split_by_a as $chunk) {
                            if (!$replaced_in_p && !preg_match('/^<a[^>]*>/is', $chunk) && $links_inserted < $max_links) {
                                $pattern = '/\b(' . $quoted_title . ')\b/iu';
                                if (preg_match($pattern, $chunk)) {
                                    $replacement = '<a href="' . esc_url($permalink) . '" title="' . esc_attr($title) . '">$1</a>';
                                    $chunk = preg_replace($pattern, $replacement, $chunk, 1);
                                    $replaced_in_p = true;
                                    $links_inserted++;
                                    $used_urls[] = $permalink;
                                }
                            }
                            $new_p .= $chunk;
                        }
                        return '<p>' . $new_p . '</p>';
                    }

                    // Nếu đoạn văn chưa có thẻ <a> nào
                    $pattern = '/\b(' . $quoted_title . ')\b/iu';
                    if (preg_match($pattern, $p_content)) {
                        $replacement = '<a href="' . esc_url($permalink) . '" title="' . esc_attr($title) . '">$1</a>';
                        $p_content = preg_replace($pattern, $replacement, $p_content, 1);
                        $links_inserted++;
                        $used_urls[] = $permalink;
                    }

                    return '<p>' . $p_content . '</p>';
                },
                $content
            );
        }

        return $content;
    }
}
