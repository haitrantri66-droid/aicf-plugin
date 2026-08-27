<?php
namespace AICF\AI\Prompt;

use AICF\AI\DTO\ContentBrief;

if (!defined('ABSPATH')) {
    exit;
}

class ContextAssembler {

    private $campaign_data = [];
    private $brand_voice = [];
    private $knowledge_base = [];
    private $content_brief = null;

    public function setCampaignData(array $campaign) {
        $this->campaign_data = $campaign;
        return $this;
    }

    public function setBrandVoice(array $brand_voice) {
        $this->brand_voice = $brand_voice;
        return $this;
    }

    public function setKnowledgeBase(array $kb) {
        $this->knowledge_base = $kb;
        return $this;
    }

    public function setContentBrief(ContentBrief $brief) {
        $this->content_brief = $brief;
        return $this;
    }

    /**
     * Assemble unified context prompt string.
     * 
     * @return string
     */
    public function assembleSystemContext(): string {
        $context = [];

        if (!empty($this->campaign_data)) {
            $lang = $this->campaign_data['target_language'] ?? 'vi';
            $tone = $this->campaign_data['tone_of_voice'] ?? 'professional';
            $context[] = "Target Language: {$lang}";
            $context[] = "Tone of Voice: {$tone}";
        }

        if (!empty($this->brand_voice)) {
            if (!empty($this->brand_voice['brand_name'])) {
                $context[] = "Brand Name: " . $this->brand_voice['brand_name'];
            }
            if (!empty($this->brand_voice['writing_style'])) {
                $context[] = "Writing Style: " . $this->brand_voice['writing_style'];
            }
        }

        if ($this->content_brief instanceof ContentBrief) {
            $context[] = "Primary Keyword: " . $this->content_brief->getPrimaryKeyword();
            if (!empty($this->content_brief->getSecondaryKeywords())) {
                $context[] = "Secondary Keywords: " . implode(', ', $this->content_brief->getSecondaryKeywords());
            }
            $context[] = "Search Intent: " . $this->content_brief->getSearchIntent();
            $context[] = "Target Audience: " . $this->content_brief->getTargetAudience();
            if (!empty($this->content_brief->getContentAngle())) {
                $context[] = "Content Angle: " . $this->content_brief->getContentAngle();
            }
        }

        $result = implode("\n", $context);
        return str_replace(array('\vert', '\\vert', '\|'), '|', $result);
    }
}