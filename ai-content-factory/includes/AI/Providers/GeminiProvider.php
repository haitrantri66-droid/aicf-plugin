<?php
namespace AICF\AI\Providers;

use AICF\AI\Interfaces\AIProviderInterface;
use AICF\AI\DTO\AIRequest;
use AICF\AI\DTO\AIResponse;

if (!defined('ABSPATH')) {
    exit;
}

class GeminiProvider implements AIProviderInterface {

    private $api_key;

    public function __construct($api_key = '') {
        $this->api_key = $api_key ?: get_option('aicf_gemini_api_key', '');
    }

    public function generateText(AIRequest $request): AIResponse {
        if (empty($this->api_key)) {
            throw new \Exception('Chưa nhập Gemini API Key trong Cài đặt.');
        }

        $clean_key = trim($this->api_key);
        $promptText = ($request->getSystemPrompt() ? $request->getSystemPrompt() . "\n\n" : '') . $request->getPrompt();

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $promptText]
                    ]
                ]
            ]
        ];

                // Danh sách model chuẩn của Google Gemini (cập nhật 08/2026 - dòng 2.5-lite đã bị Google khai tử)
        $models = [
            'gemini-3.5-flash',
            'gemini-3.1-pro',
            'gemini-3.5-flash-lite',
            'gemini-2.5-flash'
        ];

        $last_error = '';

        foreach ($models as $model_name) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model_name}:generateContent?key=" . $clean_key;

            $response = wp_remote_post($endpoint, [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($body),
                'timeout' => 120,
            ]);

            if (is_wp_error($response)) {
                $last_error = 'Lỗi kết nối Server: ' . $response->get_error_message();
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);
            $res_body = wp_remote_retrieve_body($response);
            
            // Xử lý làm sạch chuỗi response thô trước khi decode JSON
            $res_body_clean = str_replace(array('\vert', '\\vert', '\|'), '|', $res_body);
            $data = json_decode($res_body_clean, true);

            if ($code === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $content = $data['candidates'][0]['content']['parts'][0]['text'];
                return new AIResponse($content, 0, 'gemini', $model_name);
            }

            if (isset($data['error']['message'])) {
                $last_error = "Model [{$model_name}]: " . $data['error']['message'];
            } else {
                $last_error = "Mã lỗi HTTP {$code}";
            }
        }

        // Làm sạch biến error cuối cùng trước khi quăng Exception
        $clean_final_error = str_replace(array('\vert', '\\vert', '\|'), '|', $last_error);
        throw new \Exception('Google Gemini API Error: ' . $clean_final_error);
    }

    public function generate(AIRequest $request): AIResponse {
        return $this->generateText($request);
    }
}