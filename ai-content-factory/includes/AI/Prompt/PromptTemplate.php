<?php
namespace AICF\AI\Prompt;

if (!defined('ABSPATH')) {
    exit;
}

class PromptTemplate {

    /**
     * Hàm dùng chung làm sạch rác \vert
     */
    private static function clean(string $text): string {
        return str_replace(array('\vert', '\\vert', '\|'), '|', $text);
    }

    /**
     * Lay Prompt he thong cho viec tao dan y
     */
    public static function getOutlineSystemPrompt(): string {
        $prompt = "You are an expert SEO content strategist and editor. Your task is to generate a comprehensive, well-structured article outline based on the provided topic, keyword, and guidelines. Output must be in valid JSON format.";
        return self::clean($prompt);
    }

    /**
     * Lay Prompt nguoi dung cho viec tao dan y
     */
    public static function getOutlineUserPrompt(string $keyword, string $language = 'Vietnamese', string $tone = 'professional'): string {
        $prompt = sprintf(
            "Create a detailed article outline for the target keyword: \"%s\".\nLanguage: %s\nTone: %s\n\nReturn JSON in the following format:\n{\n  \"title\": \"Proposed Article Title\",\n  \"sections\": [\n    {\n      \"heading\": \"H2 Heading\",\n      \"subheadings\": [\"H3 Subheading 1\", \"H3 Subheading 2\"]\n    }\n  ]\n}",
            esc_html($keyword),
            esc_html($language),
            esc_html($tone)
        );
        return self::clean($prompt);
    }

    /**
     * Lay Prompt tao noi dung theo phan/doan
     */
    public static function getContentSectionPrompt(string $heading, array $subheadings, string $keyword, string $tone = 'professional'): string {
        $subStr = !empty($subheadings) ? "Subheadings to cover:\n- " . implode("\n- ", $subheadings) : "";
        $prompt = sprintf(
            "Write an engaging, highly informative, and SEO-optimized section for an article.\nMain Heading (H2): \"%s\"\n%s\nTarget Keyword: \"%s\"\nTone: %s\n\nFormatting Guidelines:\n- Use clean HTML tags (<p>, <h3>, <ul>, <li>, <strong>).\n- Do NOT wrap in <html> or <body> tags.\n- Do NOT include <h1> tag.",
            esc_html($heading),
            esc_html($subStr),
            esc_html($keyword),
            esc_html($tone)
        );
        return self::clean($prompt);
    }

    /**
     * Lay Prompt tao Meta SEO
     */
    public static function getSEOMetaPrompt(string $title, string $contentSnippet, string $keyword): string {
        $prompt = sprintf(
            "Based on the article title \"%s\" and target keyword \"%s\", generate SEO metadata.\nContent snippet: \"%s\"\n\nReturn JSON format:\n{\n  \"seo_title\": \"SEO Title under 60 chars\",\n  \"meta_description\": \"Meta description under 155 chars containing the focus keyword.\"\n}",
            esc_html($title),
            esc_html($keyword),
            esc_html(mb_substr($contentSnippet, 0, 300))
        );
        return self::clean($prompt);
    }
}