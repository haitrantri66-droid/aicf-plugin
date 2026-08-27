<?php
namespace AICF\AI\Providers;

use AICF\AI\Interfaces\AIProviderInterface;
use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\AIResponse;

if (!defined('ABSPATH')) {
    exit;
}

class OpenAIProvider implements AIProviderInterface {

    private $api_key;

    public function __construct($api_key = '') {
        $this->api_key = $api_key ?: get_option('aicf_openai_api_key', '');
    }

    public function generateText(AIRequest $request): AIResponse {
        if (empty($this->api_key)) {
            throw new \Exception('Chưa nhập OpenAI API Key trong Settings.');
        }

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        $messages = [];
        if ($request->getSystemPrompt()) {
            $messages[] = ['role' => 'system', 'content' => $request->getSystemPrompt()];
        }
        $messages[] = ['role' => 'user', 'content' => $request->getPrompt()];

        $body = [
            'model'       => $request->getModel() ?: 'gpt-4o-mini',
            'messages'    => $messages,
            'temperature' => $request->getTemperature() ?: 0.7,
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . trim($this->api_key),
            ],
            'body'    => json_encode($body),
            'timeout' => 120,
        ]);

        if (is_wp_error($response)) {
            throw new \Exception('Lỗi cURL tới OpenAI: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $res_body = wp_remote_retrieve_body($response);
        $data = json_decode($res_body, true);

        if ($code !== 200) {
            $msg = $data['error']['message'] ?? 'Lỗi kết nối OpenAI (Code ' . $code . ')';
            throw new \Exception('OpenAI Error: ' . $msg);
        }

        $content = $data['choices'][0]['message']['content'] ?? '';

        return new AIResponse($content, 0, 'openai', 'gpt-4o-mini');
    }
}