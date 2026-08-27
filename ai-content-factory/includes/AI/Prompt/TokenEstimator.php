<?php
namespace AICF\AI\Prompt;

if (!defined('ABSPATH')) {
    exit;
}

class TokenEstimator {

    /**
     * Uoc tinh so token tu mot chuoi van ban
     * Trung binh 1 token ~ 4 ký tự (tiếng Anh) hoặc ~ 1.5 - 2 ký tự (tiếng Việt UTF-8)
     */
    public static function estimateTextTokens(string $text, string $language = 'vietnamese'): int {
        $charCount = mb_strlen($text, 'UTF-8');
        
        if (strtolower($language) === 'vietnamese') {
            return (int) ceil($charCount / 2.2);
        }

        return (int) ceil($charCount / 4.0);
    }

    /**
     * Kiem tra xem tong so token co vuot qua Max Tokens cua model hay khong
     */
    public static function isWithinLimit(string $prompt, int $maxAllowed = 4096): bool {
        $estimated = self::estimateTextTokens($prompt);
        return $estimated <= $maxAllowed;
    }
}