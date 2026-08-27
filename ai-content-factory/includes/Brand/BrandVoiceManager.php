<?php
namespace AICF\Brand;

if (!defined('ABSPATH')) {
    exit;
}

class BrandVoiceManager {

    const OPTION_KEY = 'aicf_brand_settings';

    /**
     * Get all brand settings.
     * 
     * @return array
     */
    public static function get_settings() {
        $defaults = [
            'brand_name'        => '',
            'tone_of_voice'     => 'professional', // professional, casual, authoritative, friendly, humorous
            'writing_style'     => 'informative and engaging',
            'negative_keywords' => '',
            'knowledge_base'    => ''
        ];

        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, $defaults);
    }

    /**
     * Update brand settings.
     * 
     * @param array $settings
     * @return bool
     */
    public static function save_settings(array $settings) {
        $clean = [
            'brand_name'        => sanitize_text_field($settings['brand_name'] ?? ''),
            'tone_of_voice'     => sanitize_text_field($settings['tone_of_voice'] ?? 'professional'),
            'writing_style'     => sanitize_text_field($settings['writing_style'] ?? ''),
            'negative_keywords' => sanitize_textarea_field($settings['negative_keywords'] ?? ''),
            'knowledge_base'    => sanitize_textarea_field($settings['knowledge_base'] ?? '')
        ];

        return update_option(self::OPTION_KEY, $clean);
    }

    /**
     * Build prompt instructions context for AI requests.
     * 
     * @return string
     */
    public static function build_system_context() {
        $settings = self::get_settings();
        $context = [];

        if (!empty($settings['brand_name'])) {
            $context[] = "Brand Name: " . $settings['brand_name'];
        }

        if (!empty($settings['tone_of_voice'])) {
            $context[] = "Tone of Voice: " . $settings['tone_of_voice'];
        }

        if (!empty($settings['writing_style'])) {
            $context[] = "Writing Style: " . $settings['writing_style'];
        }

        if (!empty($settings['negative_keywords'])) {
            $context[] = "Forbidden Words/Phrases (DO NOT USE): " . $settings['negative_keywords'];
        }

        if (!empty($settings['knowledge_base'])) {
            $context[] = "Company Knowledge & Background Info:\n" . $settings['knowledge_base'];
        }

        if (empty($context)) {
            return '';
        }

        return "\n--- BRAND VOICE & KNOWLEDGE BASE GUIDELINES ---\n" . implode("\n", $context) . "\n---------------------------------------------\n";
    }
}