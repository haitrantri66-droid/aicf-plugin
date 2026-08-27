<?php
namespace AICF\AI\DTO;

if (!defined('ABSPATH')) {
    exit;
}

class AIRequest {
    private $prompt;
    private $system_prompt;
    private $model;
    private $temperature;

    public function __construct($prompt, $system_prompt = '', $model = '', $temperature = 0.7) {
        $this->prompt = $prompt;
        $this->system_prompt = $system_prompt;
        $this->model = $model;
        $this->temperature = $temperature;
    }

    public function getPrompt() {
        return $this->prompt;
    }

    public function getSystemPrompt() {
        return $this->system_prompt;
    }

    public function getModel() {
        return $this->model;
    }

    public function getTemperature() {
        return $this->temperature;
    }
}