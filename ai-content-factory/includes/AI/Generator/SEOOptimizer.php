<?php
namespace AICF\AI\Generator;

use AICF\AI\AIManager;
use AICF\AI\DTO\AIRequest;
use AICF\AI\Prompt\PromptTemplate;

if (!defined('ABSPATH')) {
    exit;
}

class SEOOptimizer {

    private AIManager $aiManager;

    public function __construct(AIManager $aiManager) {
        $this->aiManager = $aiManager;
    }

    /**
     * Tao Meta SEO (Title & Description)
     */
    public function generateSEOMeta(string $title, string $content, string $keyword): array {
        $userPrompt = PromptTemplate::getSEOMetaPrompt($title, $content, $keyword);

        $request = new AIRequest(
            $userPrompt,
            'You are an SEO expert.',
            0.5,
            500
        );

        $response = $this->aiManager->generate_text($request);
        $decoded  = json_decode($response->get_content(), true);

        return [
            'seo_title'        => $decoded['seo_title'] ?? $title,
            'meta_description' => $decoded['meta_description'] ?? ''
        ];
    }
}