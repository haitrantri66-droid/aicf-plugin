<?php
namespace AICF\SEO;

if (!defined('ABSPATH')) {
    exit;
}

class SchemaGenerator {

    /**
     * Generate JSON-LD Article Schema array.
     * 
     * @param string $title Article Title
     * @param string $description Meta Description
     * @param string $url Article Permalink
     * @param string $image_url Image URL
     * @param string $author_name Author Name
     * @param string $date_published ISO Date
     * @return array Structured Schema Array
     */
    public static function generate_article_schema($title, $description, $url = '', $image_url = '', $author_name = '', $date_published = '') {
        if (empty($author_name)) {
            $author_name = get_bloginfo('name');
        }

        if (empty($date_published)) {
            $date_published = current_time('c');
        }

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => wp_strip_all_tags($title),
            'description'      => wp_strip_all_tags($description),
            'datePublished'    => $date_published,
            'dateModified'     => current_time('c'),
            'author'           => [
                '@type' => 'Person',
                'name'  => sanitize_text_field($author_name)
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => get_bloginfo('name'),
                'url'   => home_url()
            ]
        ];

        if (!empty($url)) {
            $schema['mainEntityOfPage'] = [
                '@type' => 'WebPage',
                '@id'   => esc_url($url)
            ];
        }

        if (!empty($image_url)) {
            $schema['image'] = esc_url($image_url);
        }

        return $schema;
    }

    /**
     * Format schema array to HTML Script tag for rendering.
     * 
     * @param array $schema_array
     * @return string
     */
    public static function to_script_tag(array $schema_array) {
        if (empty($schema_array)) {
            return '';
        }

        return '<script type="application/ld+json">' . json_encode($schema_array, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}