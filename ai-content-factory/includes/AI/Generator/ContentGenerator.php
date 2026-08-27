<?php
namespace AICF\AI\Generator;

use AICF\AI\AIManager;
use AICF\AI\DTO\AIRequest;
use AICF\AI\Prompt\PromptTemplate;

if (!defined('ABSPATH')) {
    exit;
}

class ContentGenerator {

    private AIManager $aiManager;

    public function __construct(AIManager $aiManager) {
        $this->aiManager = $aiManager;
    }

    /**
     * Tao noi dung cho toan bo dan y
     */
    public function generateFullArticle(array $outline, string $keyword, string $tone = 'professional'): string {
        $fullContent = '';

        if (!empty($outline['sections'])) {
            foreach ($outline['sections'] as $section) {
                $heading     = $section['heading'] ?? '';
                $subheadings = $section['subheadings'] ?? [];

                $sectionHTML = $this->generateSection($heading, $subheadings, $keyword, $tone);
                $fullContent .= "<h2>" . esc_html($heading) . "</h2>\n" . $sectionHTML . "\n\n";
            }
        }

        return $fullContent;
    }

    /**
     * Tao noi dung chi tiet cho tung Section
     */
    public function generateSection(string $heading, array $subheadings, string $keyword, string $tone): string {
        $userPrompt = PromptTemplate::getContentSectionPrompt($heading, $subheadings, $keyword, $tone);
        $userPrompt = str_replace(array('\vert', '\\vert', '\|'), '|', $userPrompt);

        $request = new AIRequest($userPrompt, '', 0.7, 1500);
        $response = $this->aiManager->generate_text($request);

        $content = $response->get_content();
        return str_replace(array('\vert', '\\vert'), '|', $content);
    }
}