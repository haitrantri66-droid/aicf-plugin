<?php
namespace AICF\AI\Interfaces;

use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\AIResponse;

if (!defined('ABSPATH')) {
    exit;
}

interface AIProviderInterface {
    public function generateText(AIRequest $request): AIResponse;
}