<?php
namespace AICF\Engine;

use AICF\AI\DTO\AIRequest;
use AICF\AI\AIFactory;
use AICF\SEO\SEOAnalyzer;

if (!defined('ABSPATH')) {
    exit;
}

class ContentPipeline {

    public static function process_keyword($keyword_id) {
        global $wpdb;

        $kw_table  =$wpdb->prefix . 'aicf_keywords';
        $art_table =$wpdb->prefix . 'aicf_articles';

        $kw =$wpdb->get_row($wpdb->prepare("SELECT * FROM $kw_table WHERE id = %d", $keyword_id));
        if (!$kw) {
            throw new \Exception('Không tìm thấy từ khóa ID: ' . $keyword_id);
        }

        $provider_type = get_option('aicf_default_provider', 'gemini');
        $provider = AIFactory::create($provider_type);

        $current_year  = date('Y');
        $keyword_clean = trim($kw->keyword);

        // Lấy thông tin Doanh nghiệp từ DB
        $brand_name   = get_option('aicf_brand_name', '');
        $company_name = get_option('aicf_company_name', '');$website      = get_option('aicf_company_website', '');
        $hotline      = get_option('aicf_company_hotline', '');$address      = get_option('aicf_company_address', '');

        // Prompt mặc định nếu chưa lưu Settings
        $default_prompt = $default_prompt = "Bạn là Chuyên gia Kỹ thuật và Biên tập viên Content SEO xuất sắc cho {brand_name}.\n"
                        . "Hãy viết bài chuẩn SEO chất lượng cao cho từ khóa: '{keyword}'.\n\n"
                        . "YÊU CẦU TIÊU ĐỀ (H1):\n"
                        . "- Dòng ĐẦU TIÊN của câu trả lời phải là Tiêu đề bài viết chuẩn SEO, hấp dẫn, giật gân hoặc đánh đúng tâm lý người mua\n"
                        . "- KHÔNG lặp lại cụm từ cố định một cách công nghiệp.\n"
                        . "- Đặt tiêu đề trong thẻ <h1> [Tiêu đề ở đây] </h1>.\n\n"
                        . "THÔNG TIN DOANH NGHIỆP:\n"
                        . "- Công ty: {company_name}\n"
                        . "- Website: {website}\n"
                        . "- Hotline: {hotline}\n"
                        . "- Địa chỉ: {address}\n"
                        . "Năm thực hiện: {year}";

        $raw_template = get_option('aicf_custom_system_prompt',$default_prompt);

        // Ép dọn sạch toàn bộ Slashes, Entity Escape, \vert từ Database ra
        $template_prompt = wp_unslash($raw_template);
        $template_prompt = html_entity_decode($template_prompt, ENT_QUOTES, 'UTF-8');
        $template_prompt = str_replace(array('\vert', '\\vert', '\Vert{}'), '\vert{}',$template_prompt);

        // Thay thế các biến động vào Prompt
        $prompt = str_replace(
            ['{keyword}', '{year}', '{brand_name}', '{company_name}', '{website}', '{hotline}', '{address}'],
            [$keyword_clean,$current_year, $brand_name,$company_name, $website,$hotline, $address],$template_prompt
        );

        $request = new AIRequest($prompt);$response = $provider->generateText($request);

        if (!$response || empty($response->getContent())) {
            throw new \Exception('AI không trả về nội dung.');
        }

        $content =$response->getContent();

        // Làm sạch code Markdown nếu AI lỡ bọc vào
        $content = preg_replace('/^```html\s*/i', '', $content);
        $content = preg_replace('/^```\s*/i', '', $content);$content = preg_replace('/```$/', '', $content);
        $content = str_replace(array('\vert', '\\vert'), '|', $content);

        // Tạo tiêu đề bài viết
        $title = '';

if (preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
    $title = wp_strip_all_tags($matches[1]);
    $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
    $title = trim(preg_replace('/\s+/', ' ', $title));
}

// Nếu AI không tạo H1 hợp lệ thì dùng keyword làm fallback
if (empty($title)) {
    $title = mb_convert_case($keyword_clean, MB_CASE_TITLE, 'UTF-8');
}

                // Lưu bài viết vào Nháp (Draft)
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => trim($content),
            'post_status'  => 'draft',
            'post_type'    => 'post',
        ]);

        if (is_wp_error($post_id)) {
            throw new \Exception('Lỗi tạo Bài viết WordPress: ' . $post_id->get_error_message());
        }

        // Phân tích SEO tự động để lấy Meta Title / Meta Description / SEO Score
        $seo = SEOAnalyzer::analyze($content, $keyword_clean, $title);

        // Gán Focus Keyword + Meta Title/Description cho RankMath SEO
        update_post_meta($post_id, 'rank_math_focus_keyword', $keyword_clean);
        update_post_meta($post_id, 'rank_math_title', $seo['meta_title']);
        update_post_meta($post_id, 'rank_math_description', $seo['meta_description']);

        // Đồng thời gán cho Yoast SEO (nếu website dùng Yoast thay vì RankMath)
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword_clean);
        update_post_meta($post_id, '_yoast_wpseo_title', $seo['meta_title']);
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo['meta_description']);

        // Lưu thông tin vào Database
        $wpdb->insert($art_table, [
            'campaign_id'      => $kw->campaign_id,
            'keyword_id'       => $kw->id,
            'wp_post_id'       => $post_id,
            'title'            => $title,
            'content'          => $content,
            'meta_title'       => $seo['meta_title'],
            'meta_description' => $seo['meta_description'],
            'seo_score'        => $seo['seo_score'],
            'status'           => 'completed',
            'generation_state' => 'completed',
            'created_at'       => current_time('mysql')
        ]);

        $wpdb->update($kw_table, ['status' => 'completed'], ['id' => $keyword_id]);

        return true;
    }
}