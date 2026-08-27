<?php
namespace AICF\AI\DTO;

if (!defined('ABSPATH')) {
    exit;
}

class AIResponse {
    private $content;
    private $tokens;
    private $provider;
    private $model;

    public function __construct($content, $tokens = 0, $provider = '', $model = '') {
        $this->content = $content;
        $this->tokens = $tokens;
        $this->provider = $provider;
        $this->model = $model;
    }

    public function getContent() {
        return $this->content;
    }

    public function getTokens() {
        return $this->tokens;
    }

    public function getProvider() {
        return $this->provider;
    }

    public function getModel() {
        return $this->model;
    }
}