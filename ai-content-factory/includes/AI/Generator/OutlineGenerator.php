<?php
namespace AICF\AI\Generator;

use AICF\AI\AIManager;
use AICF\AI\DTO\AIRequest;
use AICF\AI\Prompt\PromptTemplate;

if (!defined('ABSPATH')) {
    exit;
}

class OutlineGenerator {

    private AIManager $aiManager;

    public function __construct(AIManager $aiManager) {
        $this->aiManager = $aiManager;
    }

    /**
     * Tao Dan Y cho tu khoa
     */
    public function generate(string $keyword, string $language = 'Vietnamese', string $tone = 'professional'): array {
        $systemPrompt = PromptTemplate::getOutlineSystemPrompt();
        $userPrompt   = PromptTemplate::getOutlineUserPrompt($keyword, $language, $tone);

        $request = new AIRequest(
            $userPrompt,
            $systemPrompt,
            0.7,
            2000
        );

        $response = $this->aiManager->generate_text($request);
        $content  = $response->get_content();

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Phản hồi Dàn ý từ AI không đúng định dạng JSON: " . json_last_error_msg());
        }

        return $decoded;
    }
}