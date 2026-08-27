<?php
namespace AICF\AI;

if (!defined('ABSPATH')) {
    exit;
}

class CostCalculator {
    private static $pricing = [
        'openai' => [
            'gpt-4o' => ['input' => 0.0025, 'output' => 0.0100],
            'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
            'gpt-4-turbo' => ['input' => 0.0100, 'output' => 0.0300],
        ],
        'gemini' => [
            'gemini-1.5-pro' => ['input' => 0.00125, 'output' => 0.0050],
            'gemini-1.5-flash' => ['input' => 0.000075, 'output' => 0.0003],
            'gemini-2.0-flash' => ['input' => 0.00010, 'output' => 0.0004],
        ]
    ];

    public static function calculate($provider, $model, $input_tokens, $output_tokens) {
        $provider = strtolower($provider);
        $model = strtolower($model);

        if (isset(self::$pricing[$provider][$model])) {
            $rates = self::$pricing[$provider][$model];
            $cost_input = ($input_tokens / 1000) * $rates['input'];
            $cost_output = ($output_tokens / 1000) * $rates['output'];
            return round($cost_input + $cost_output, 6);
        }

        return 0.0;
    }
}