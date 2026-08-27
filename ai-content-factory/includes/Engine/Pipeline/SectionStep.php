<?php
namespace AICF\Engine\Pipeline;

use AICF\AI\AIFactory;
use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\ContentBrief;
use AICF\Brand\BrandVoiceManager;

if (!defined('ABSPATH')) {
    exit;
}

class SectionStep {

    public function run(array $section, ContentBrief $brief, array $campaign) {
        $ai_client = AIFactory::get_client_from_campaign($campaign);
        $brand_context = class_exists('AICF\Brand\BrandVoiceManager') ? BrandVoiceManager::build_system_context() : '';

        $heading = $section['heading'] ?? '';
        $level = $section['level'] ?? 'h2';

        $prompt = "Write a comprehensive section for an article.\n";
        $prompt .= "Section Heading: {$heading}\n";
        $prompt .= "Main Keyword: " . $brief->getTargetKeyword() . "\n";
        $prompt .= "Secondary Keywords to naturally include: " . implode(', ', $brief->getSecondaryKeywords()) . "\n";
        
        $prompt .= $brand_context;

        $prompt .= "\nRequirements:\n";
        $prompt .= "1. Format response directly in clean HTML without <html> or <body> tags.\n";
        $prompt .= "2. Wrap heading with <{$level}>{$heading}</{$level}>.\n";
        $prompt .= "3. Use <p>, <ul>, <li>, <strong> tags naturally.\n";
        $prompt .= "4. Provide high-value, actionable content.\n";

        $request = new AIRequest($prompt);
        $request->setSystemPrompt("You are a professional content writer. Output ONLY clean HTML formatting.")
                ->setTemperature(0.7);

        $response = $ai_client->generate($request);
        return $response->getContent();
    }
}