<?php
namespace AICF\AI;

use AICF\AI\Interfaces\AIProviderInterface;
use AICF\AI\Providers\OpenAIProvider;
use AICF\AI\Providers\GeminiProvider;
use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\AIResponse;
use AICF\AI\Exceptions\AIException;
use AICF\Security\SecurityManager;
use AICF\Logger\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class AIManager {
    private $providers = [];

    public function __construct() {
        $settings = get_option('aicf_settings', []);
        
        $openai_key = !empty($settings['openai_api_key']) ? SecurityManager::decrypt($settings['openai_api_key']) : '';
        $gemini_key = !empty($settings['gemini_api_key']) ? SecurityManager::decrypt($settings['gemini_api_key']) : '';

        if (!empty($openai_key)) {
            $this->providers['openai'] = new OpenAIProvider($openai_key);
        }

        if (!empty($gemini_key)) {
            $this->providers['gemini'] = new GeminiProvider($gemini_key);
        }
    }

    public function get_provider($name): AIProviderInterface {
        $name = strtolower($name);
        if (!isset($this->providers[$name])) {
            throw new AIException('AI Provider "' . $name . '" chưa được cấu hình hoặc không khả dụng.', 400, false);
        }
        return $this->providers[$name];
    }

    public function generate_text(AIRequest $request, $provider_name = '', $model = ''): AIResponse {
        $settings = get_option('aicf_settings', []);
        if (empty($provider_name)) {
            $provider_name = isset($settings['default_provider']) ? $settings['default_provider'] : 'openai';
        }

        $provider = $this->get_provider($provider_name);

        try {
            $response = $provider->generate_text($request, $model);
            
            // Log thành công
            Logger::log([
                'provider'       => $response->get_provider(),
                'model'          => $response->get_model(),
                'request_type'   => 'text_generation',
                'status'         => 'success',
                'duration'       => $response->get_duration(),
                'input_tokens'   => $response->get_input_tokens(),
                'output_tokens'  => $response->get_output_tokens(),
                'total_tokens'   => $response->get_total_tokens(),
                'estimated_cost' => $response->get_estimated_cost(),
                'message'        => 'Thành công',
            ]);

            return $response;

        } catch (AIException $e) {
            // Log thất bại
            Logger::log([
                'provider'       => $provider_name,
                'model'          => $model,
                'request_type'   => 'text_generation',
                'status'         => 'failed',
                'duration'       => 0,
                'message'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}