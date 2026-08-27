<?php
namespace AICF\Keyword;

use AICF\AI\AIFactory;
use AICF\AI\DTO\AIRequest;

if (!defined('ABSPATH')) {
    exit;
}

class KeywordSuggester {

    /**
     * Dùng AI để phân tích 1 từ khóa/chủ đề gốc và gợi ý ra bộ từ khóa liên quan,
     * kèm search intent và cluster, phục vụ nghiên cứu từ khóa (keyword research).
     *
     * Lưu ý: đây là gợi ý dựa trên kiến thức ngôn ngữ của AI, KHÔNG phải số liệu
     * search volume/difficulty thật từ Google. Muốn có số liệu thật cần tích hợp
     * thêm API riêng (Google Keyword Planner, Ahrefs, Semrush...).
     */
    public static function suggest($seed_keyword, $context = '', $provider_type = '') {
        $seed_keyword = trim($seed_keyword);
        if (empty($seed_keyword)) {
            throw new \Exception('Vui lòng nhập từ khóa hoặc chủ đề gốc.');
        }

        $provider_type = $provider_type ?: get_option('aicf_default_provider', 'gemini');
        $provider = AIFactory::create($provider_type);

        $system_prompt = "Bạn là chuyên gia nghiên cứu từ khóa SEO (Keyword Research). "
            . "CHỈ trả lời bằng một mảng JSON thuần túy, không markdown, không giải thích, không bọc trong ```.";

        $context_line = !empty($context) ? "\nBối cảnh/ngành nghề: {$context}" : '';

        $user_prompt = "Từ khóa/chủ đề gốc: \"{$seed_keyword}\"{$context_line}\n\n"
            . "Hãy gợi ý 15-20 từ khóa SEO liên quan (bao gồm cả long-tail), phù hợp để viết bài chuẩn SEO. "
            . "Với mỗi từ khóa, xác định:\n"
            . "- keyword: từ khóa cụ thể\n"
            . "- intent: một trong các giá trị informational | commercial | transactional | navigational\n"
            . "- cluster: nhóm chủ đề mà từ khóa này thuộc về (ví dụ: 'Giá cả', 'So sánh', 'Hướng dẫn'...)\n"
            . "- priority: số 1-5, 5 là ưu tiên viết bài trước (dựa trên mức độ phổ biến/giá trị thương mại ước tính)\n\n"
            . "Trả về đúng định dạng JSON:\n"
            . "[{\"keyword\":\"...\",\"intent\":\"...\",\"cluster\":\"...\",\"priority\":3}, ...]";

        $request = new AIRequest($user_prompt, $system_prompt, '', 0.6);
        $response = $provider->generateText($request);

        $raw = trim($response->getContent());

        // Làm sạch nếu AI lỡ bọc trong ```json ... ```
        $raw = preg_replace('/^```json\s*/i', '', $raw);
        $raw = preg_replace('/^```\s*/i', '', $raw);
        $raw = preg_replace('/```$/', '', $raw);
        $raw = trim($raw);

        $data = json_decode($raw, true);

        if (!is_array($data) || empty($data)) {
            throw new \Exception('AI không trả về danh sách từ khóa hợp lệ. Vui lòng thử lại.');
        }

        $allowed_intents = ['informational', 'commercial', 'transactional', 'navigational'];
        $clean = [];

        foreach ($data as $item) {
            if (empty($item['keyword'])) continue;

            $intent = strtolower(trim($item['intent'] ?? 'informational'));
            if (!in_array($intent, $allowed_intents)) {
                $intent = 'informational';
            }

            $priority = intval($item['priority'] ?? 3);
            $priority = max(1, min(5, $priority));

            $clean[] = [
                'keyword'  => sanitize_text_field($item['keyword']),
                'intent'   => $intent,
                'cluster'  => sanitize_text_field($item['cluster'] ?? ''),
                'priority' => $priority,
            ];
        }

        if (empty($clean)) {
            throw new \Exception('Không phân tích được từ khóa nào từ kết quả AI.');
        }

        return $clean;
    }
}