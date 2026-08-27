<?php
namespace AICF\AI\DTO;

if (!defined('ABSPATH')) {
    exit;
}

class ContentBrief {

    private $primary_keyword = '';
    private $secondary_keywords = [];
    private $search_intent = 'informational';
    private $target_audience = 'General Audience';
    private $target_location = 'Global / Vietnam';
    private $suggested_title = '';
    private $recommended_length = 2000;
    private $h1_heading = '';
    private $subheadings = [];
    private $entities = [];
    private $faqs = [];
    private $cta = '';
    private $content_angle = '';

    public function __construct(array $data = []) {
        if (!empty($data)) {
            $this->fromArray($data);
        }
    }

    public function fromArray(array $data) {
        $this->primary_keyword = sanitize_text_field($data['primary_keyword'] ?? '');
        
        if (isset($data['secondary_keywords']) && is_array($data['secondary_keywords'])) {
            $this->secondary_keywords = array_map('sanitize_text_field', $data['secondary_keywords']);
        }
        
        $this->search_intent = sanitize_text_field($data['search_intent'] ?? 'informational');
        $this->target_audience = sanitize_text_field($data['target_audience'] ?? 'General Audience');
        $this->target_location = sanitize_text_field($data['target_location'] ?? 'Global / Vietnam');
        $this->suggested_title = sanitize_text_field($data['suggested_title'] ?? '');
        $this->recommended_length = intval($data['recommended_length'] ?? 2000);
        $this->h1_heading = sanitize_text_field($data['h1_heading'] ?? '');
        
        if (isset($data['subheadings']) && is_array($data['subheadings'])) {
            $this->subheadings = array_map('sanitize_text_field', $data['subheadings']);
        }
        
        if (isset($data['entities']) && is_array($data['entities'])) {
            $this->entities = array_map('sanitize_text_field', $data['entities']);
        }
        
        if (isset($data['faqs']) && is_array($data['faqs'])) {
            $this->faqs = $data['faqs'];
        }
        
        $this->cta = sanitize_text_field($data['cta'] ?? '');
        $this->content_angle = sanitize_text_field($data['content_angle'] ?? '');
    }

    public function toArray() {
        return [
            'primary_keyword'    => $this->primary_keyword,
            'secondary_keywords'  => $this->secondary_keywords,
            'search_intent'      => $this->search_intent,
            'target_audience'    => $this->target_audience,
            'target_location'    => $this->target_location,
            'suggested_title'    => $this->suggested_title,
            'recommended_length' => $this->recommended_length,
            'h1_heading'         => $this->h1_heading,
            'subheadings'        => $this->subheadings,
            'entities'           => $this->entities,
            'faqs'               => $this->faqs,
            'cta'                => $this->cta,
            'content_angle'      => $this->content_angle
        ];
    }

    public function toJson() {
        return wp_json_encode($this->toArray());
    }

    public static function fromJson($json) {
        $data = json_decode($json, true);
        if (is_array($data)) {
            return new self($data);
        }
        return new self();
    }

    // Getters and Setters
    public function getPrimaryKeyword() { return $this->primary_keyword; }
    public function setPrimaryKeyword($val) { $this->primary_keyword = sanitize_text_field($val); }

    public function getSecondaryKeywords() { return $this->secondary_keywords; }
    public function setSecondaryKeywords(array $val) { $this->secondary_keywords = array_map('sanitize_text_field', $val); }

    public function getSearchIntent() { return $this->search_intent; }
    public function setSearchIntent($val) { $this->search_intent = sanitize_text_field($val); }

    public function getTargetAudience() { return $this->target_audience; }
    public function setTargetAudience($val) { $this->target_audience = sanitize_text_field($val); }

    public function getSuggestedTitle() { return $this->suggested_title; }
    public function setSuggestedTitle($val) { $this->suggested_title = sanitize_text_field($val); }

    public function getRecommendedLength() { return $this->recommended_length; }
    public function setRecommendedLength($val) { $this->recommended_length = intval($val); }

    public function getH1Heading() { return $this->h1_heading; }
    public function setH1Heading($val) { $this->h1_heading = sanitize_text_field($val); }

    public function getSubheadings() { return $this->subheadings; }
    public function setSubheadings(array $val) { $this->subheadings = array_map('sanitize_text_field', $val); }

    public function getEntities() { return $this->entities; }
    public function setEntities(array $val) { $this->entities = array_map('sanitize_text_field', $val); }

    public function getFaqs() { return $this->faqs; }
    public function setFaqs(array $val) { $this->faqs = $val; }

    public function getCta() { return $this->cta; }
    public function setCta($val) { $this->cta = sanitize_text_field($val); }

    public function getContentAngle() { return $this->content_angle; }
    public function setContentAngle($val) { $this->content_angle = sanitize_text_field($val); }
}