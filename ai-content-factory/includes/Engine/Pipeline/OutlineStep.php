<?php
namespace AICF\Engine\Pipeline;

use AICF\AI\AIFactory;
use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\ContentBrief;

if (!defined('ABSPATH')) {
    exit;
}

class OutlineStep {

    public function run(ContentBrief $brief, array $campaign) {
        $ai_client = AIFactory::get_client_from_campaign($campaign);

        $prompt = "Target Keyword: " . $brief->getTargetKeyword() . "\n";
        $prompt .= "Suggested Title: " . $brief->getSuggestedTitle() . "\n";
        $prompt .= "Core Angles: " . implode(', ', $brief->getCoreAngles()) . "\n\n";
        $prompt .= "Create a logical, well-structured H2/H3 outline for this article in valid JSON array format:\n";
        $prompt .= "[{\"heading\": \"Section Heading\", \"level\": \"h2\"}, ...]";

        $request = new AIRequest($prompt);
        $request->setSystemPrompt("You are a professional content architect. Output ONLY valid JSON array.")
                ->setJsonMode(true)
                ->setTemperature(0.5);

        $response = $ai_client->generate($request);
        $json = json_decode($response->getContent(), true);

        if (!is_array($json)) {
            return [
                ['heading' => 'Introduction', 'level' => 'h2'],
                ['heading' => 'Main Details', 'level' => 'h2'],
                ['heading' => 'Conclusion', 'level' => 'h2']
            ];
        }

        return $json;
    }
}