<?php
namespace AICF\AI;

use AICF\AI\Providers\GeminiProvider;
use AICF\AI\Providers\OpenAIProvider;

if (!defined('ABSPATH')) {
    exit;
}

class AIFactory {

    public static function create($provider_type = 'gemini') {
        $provider_type = strtolower(trim($provider_type));

        if ($provider_type === 'openai') {
            return new OpenAIProvider();
        }

        return new GeminiProvider();
    }

    public static function get_client_from_campaign(array $campaign = []) {
        $provider_type = $campaign['provider'] ?? get_option('aicf_default_provider', 'gemini');
        return self::create($provider_type);
    }
}