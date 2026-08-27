<?php
namespace AICF\Engine;

use AICF\AI\DTO\AIRequest;
use AICF\AI\AIFactory;
use AICF\SEO\SEOAnalyzer;
use AICF\SEO\DuplicateChecker;
use AICF\SEO\InternalLinker;
use AICF\SEO\TaxonomyProcessor;
use AICF\Logger\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class ContentPipeline {

    public static function process_keyword($keyword_id) {
        // Tăng thời gian thực thi tối đa lên 180s tránh bị Timeout khi AI sinh bài dài
        @set_time_limit(180);

        global $wpdb;

        $kw_table  =$wpdb->prefix . 'aicf_keywords';
        $art_table =$wpdb->prefix . 'aicf_articles';

        $kw =$wpdb->get_row($wpdb->prepare("SELECT * FROM $kw_table WHERE id = %d", $keyword_id));
        if (!$kw) {
            $msg = 'Không tìm thấy từ khóa ID: ' . $keyword_id;
            Logger::error($msg, 'pipeline');
            throw new \Exception($msg);
        }

        $keyword_clean = trim($kw->keyword);
        Logger::info("Bắt đầu xử lý từ khóa: '{$keyword_clean}' (ID: {$keyword_id})", 'pipeline');

        try {
            $provider_type = get_option('aicf_default_provider', 'gemini');
            $provider      = AIFactory::create($provider_type);

            $current_year  = date('Y');

            // Lấy thông tin Doanh nghiệp từ DB
            $brand_name   = get_option('aicf_brand_name', '');
            $company_name = get_option('aicf_company_name', '');$website      = get_option('aicf_company_website', '');
            $hotline      = get_option('aicf_company_hotline', '');$address      = get_option('aicf_company_address', '');

            // Prompt mặc định nếu chưa lưu Settings
            $default_prompt = "Bạn là Chuyên gia Kỹ thuật và Biên tập viên Content SEO xuất sắc cho {brand_name}.\n"
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

            // Ép dọn sạch toàn bộ Slashes, Entity Escape
            $template_prompt = wp_unslash($raw_template);
            $template_prompt = html_entity_decode($template_prompt, ENT_QUOTES, 'UTF-8');

            // Thay thế các biến động vào Prompt
            $prompt = str_replace(
                ['{keyword}', '{year}', '{brand_name}', '{company_name}', '{website}', '{hotline}', '{address}'],
                [$keyword_clean,$current_year, $brand_name,$company_name, $website,$hotline, $address],$template_prompt
            );

            $start_time = microtime(true);$request    = new AIRequest($prompt);$response   = $provider->generateText($request);
            $duration   = microtime(true) -$start_time;

            // SỬA LỖI TẠI ĐÂY: Đổi \vert{}\vert{} thành toán tử OR chuẩn ||
            if (!$response \vert{}\vert{} empty($response->getContent())) {
                throw new \Exception('AI không trả về nội dung.');
            }

            $content =$response->getContent();

            // Ghi log tạo bài thành công
            Logger::log([
                'status'       => 'success',
                'request_type' => 'ai_generation',
                'provider'     => $provider_type,
                'duration'     => $duration,
                'message'      => "Sinh nội dung AI thành công cho từ khóa '{$keyword_clean}' trong " . round($duration, 2) . "s"
            ]);

            // Làm sạch code Markdown & Lọc bỏ ký tự gạch đứng bị lỗi mã hóa
            $content = preg_replace('/^```html\s*/i', '', $content);
            $content = preg_replace('/^```\s*/i', '', $content);$content = preg_replace('/```$/', '', $content);
            $content = str_replace(array('\vert', '\\vert', '\Vert{}'), '|', $content);

            // Trích xuất tiêu đề bài viết
            $title = '';
            if (preg_match('/<h1\b[^>]*>(.*?)<\/h1>/is', $content, $matches)) {
                $title = wp_strip_all_tags($matches[1]);
                $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
                $title = trim(preg_replace('/\s+/', ' ', $title));
            }

            if (empty($title)) {
                $title = mb_convert_case($keyword_clean, MB_CASE_TITLE, 'UTF-8');
            }

            // === HẬU KỲ 1: KIỂM TRA TRÙNG LẶP NỘI DUNG ===
            $dup_checker = new DuplicateChecker(80);
            $dup_result  = $dup_checker->check_content($title, $content);

            if ($dup_result['is_duplicate']) {
                Logger::warning("Cảnh báo trùng lặp ({$dup_result['score']}%) với bài viết ID: {$dup_result['matched_with']} cho từ khóa '{$keyword_clean}'", 'pipeline');
            }

            // === HẬU KỲ 2: TỰ ĐỘNG CHÈN INTERNAL LINKS ===
            $max_links       = (int) get_option('aicf_max_internal_links', 3);
            $original_length = strlen($content);
            $content         = InternalLinker::inject_links($content, 0, $max_links);

            if (strlen($content) !== $original_length) {
                Logger::info("Đã chèn liên kết nội bộ tự động vào bài viết '{$title}'", 'pipeline');
            }

            // Phân tích SEO tự động để lấy Meta Title / Description / Score
            $seo = SEOAnalyzer::analyze($content, $keyword_clean, $title);

            // Tự động phân tích & tìm Category phù hợp
            $cat_ids = TaxonomyProcessor::assign_category($title, $content);

            // Lưu bài viết vào WordPress (Draft)
            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_content' => trim($content),
                'post_status'  => 'draft',
                'post_type'    => 'post',
                'post_category'=> $cat_ids
            ]);

            if (is_wp_error($post_id)) {
                throw new \Exception('Lỗi tạo Bài viết WordPress: ' . $post_id->get_error_message());
            }

            // === HẬU KỲ 3: TỰ ĐỘNG GÁN TAG CHUẨN HÓA ===
            TaxonomyProcessor::generate_and_assign_tags($post_id, $keyword_clean, $content, 5);

            // Gán Meta Data cho RankMath SEO
            update_post_meta($post_id, 'rank_math_focus_keyword', $keyword_clean);
            update_post_meta($post_id, 'rank_math_title', $seo['meta_title']);
            update_post_meta($post_id, 'rank_math_description', $seo['meta_description']);

            // Gán Meta Data cho Yoast SEO
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword_clean);
            update_post_meta($post_id, '_yoast_wpseo_title', $seo['meta_title']);
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo['meta_description']);

            // Lưu thông tin bài viết vào DB plugin
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

            Logger::info("Hoàn tất xử lý thành công cho từ khóa '{$keyword_clean}' (WP Post ID: {$post_id})", 'pipeline');
            return true;

        } catch (\Exception $e) {
            $error_msg = "Lỗi xử lý Pipeline (Từ khóa ID {$keyword_id}): " . $e->getMessage();
            Logger::error($error_msg, 'pipeline');
            $wpdb->update($kw_table, ['status' => 'failed'], ['id' => $keyword_id]);
            throw $e;
        }
    }
}
