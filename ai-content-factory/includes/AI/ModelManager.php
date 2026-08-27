<?php
namespace AICF\AI;

if (!defined('ABSPATH')) {
    exit;
}

class ModelManager {

    /**
     * Danh sách các model hỗ trợ theo từng Provider
     */
    public static function getAvailableModels(): array {
        return [
            'openai' => [
                'gpt-4o'         => 'GPT-4o (High Intelligence)',
                'gpt-4o-mini'    => 'GPT-4o Mini (Fast & Cost-Efficient)',
                'gpt-4-turbo'    => 'GPT-4 Turbo',
                'gpt-3.5-turbo'  => 'GPT-3.5 Turbo',
            ],
            'gemini' => [
                'gemini-1.5-pro'   => 'Gemini 1.5 Pro (Complex Reasoning)',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Fast & Lightweight)',
                'gemini-1.0-pro'   => 'Gemini 1.0 Pro',
            ],
        ];
    }

    /**
     * Lấy danh sách model của một provider cụ thể
     */
    public static function getModelsByProvider(string $provider): array {
        $models = self::getAvailableModels();
        return $models[$provider] ?? [];
    }

    /**
     * Validate xem model có hợp lệ theo provider hay không
     */
    public static function validateModel(string $provider, string $model): bool {
        $allowed = self::getModelsByProvider($provider);
        return array_key_exists($model, $allowed);
    }

    /**
     * Lấy model mặc định cho provider
     */
    public static function getDefaultModel(string $provider): string {
        switch ($provider) {
            case 'gemini':
                return 'gemini-1.5-flash';
            case 'openai':
            default:
                return 'gpt-4o-mini';
        }
    }
}