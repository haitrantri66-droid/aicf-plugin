<?php
namespace AICF\Engine\Pipeline;

use AICF\AI\AIFactory;
use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\ContentBrief;
use AICF\Brand\BrandVoiceManager;

if (!defined('ABSPATH')) {
    exit;
}

class BriefStep {

    public function run($keyword, array $campaign) {
        $ai_client = AIFactory::get_client_from_campaign($campaign);
        $brand_context = class_exists('AICF\Brand\BrandVoiceManager') ? BrandVoiceManager::build_system_context() : '';

        $prompt = "Target Keyword: {$keyword}\n";
        if (!empty($campaign['target_audience'])) {
            $prompt .= "Target Audience: {$campaign['target_audience']}\n";
        }
        
        $prompt .= $brand_context;
        $prompt .= "\nAnalyze search intent for this keyword and generate a detailed SEO brief in valid JSON format with keys:\n";
        $prompt .= "- target_keyword (string)\n";
        $prompt .= "- search_intent (informational, commercial, transactional)\n";
        $prompt .= "- target_audience (string)\n";
        $prompt .= "- suggested_title (string)\n";
        $prompt .= "- core_angles (array of strings)\n";
        $prompt .= "- secondary_keywords (array of strings)\n";

        $request = new AIRequest($prompt);
        $request->setSystemPrompt("You are an expert SEO strategist. Always respond ONLY in raw JSON.")
                ->setJsonMode(true)
                ->setTemperature(0.5);

        $response = $ai_client->generate($request);
        return ContentBrief::from_ai_response($response->getContent(), $keyword);
    }
}