<?php
namespace AICF\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'ai-content-factory'));
        }

        // Xử lý lưu form cài đặt
        if (isset($_POST['aicf_save_settings'])) {
            if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'aicf_save_settings_action')) {
                
                // Keys & Providers
                if (isset($_POST['aicf_gemini_api_key'])) {
                    update_option('aicf_gemini_api_key', sanitize_text_field(wp_unslash($_POST['aicf_gemini_api_key'])));
                }
                if (isset($_POST['aicf_openai_api_key'])) {
                    update_option('aicf_openai_api_key', sanitize_text_field(wp_unslash($_POST['aicf_openai_api_key'])));
                }
                if (isset($_POST['aicf_default_provider'])) {
                    update_option('aicf_default_provider', sanitize_text_field(wp_unslash($_POST['aicf_default_provider'])));
                }

                // Business Info
                if (isset($_POST['aicf_company_name'])) {
                    update_option('aicf_company_name', sanitize_text_field(wp_unslash($_POST['aicf_company_name'])));
                }
                if (isset($_POST['aicf_brand_name'])) {
                    update_option('aicf_brand_name', sanitize_text_field(wp_unslash($_POST['aicf_brand_name'])));
                }
                if (isset($_POST['aicf_company_website'])) {
                    update_option('aicf_company_website', esc_url_raw(wp_unslash($_POST['aicf_company_website'])));
                }
                if (isset($_POST['aicf_company_hotline'])) {
                    update_option('aicf_company_hotline', sanitize_text_field(wp_unslash($_POST['aicf_company_hotline'])));
                }
                if (isset($_POST['aicf_company_address'])) {
                    update_option('aicf_company_address', sanitize_text_field(wp_unslash($_POST['aicf_company_address'])));
                }

                // Cấu Hình Hậu Kỳ SEO (Mới bổ sung cho Giai đoạn 3 & 4)
                if (isset($_POST['aicf_max_internal_links'])) {
                    update_option('aicf_max_internal_links', intval($_POST['aicf_max_internal_links']));
                }
                if (isset($_POST['aicf_duplicate_threshold'])) {
                    update_option('aicf_duplicate_threshold', intval($_POST['aicf_duplicate_threshold']));
                }

                // System Prompt
                if (isset($_POST['aicf_custom_system_prompt'])) {
                    $clean_prompt = wp_unslash($_POST['aicf_custom_system_prompt']);
                    $clean_prompt = str_replace(array('\vert', '\\|'), '|', $clean_prompt);
                    update_option('aicf_custom_system_prompt', wp_kses_post($clean_prompt));
                }

                echo '<div class="notice notice-success is-dismissible" style="margin-top:15px;"><p><strong>' . __('Lưu cài đặt thành công!', 'ai-content-factory') . '</strong></p></div>';
            }
        }

        $gemini_key = get_option('aicf_gemini_api_key', '');
        $openai_key = get_option('aicf_openai_api_key', '');
        $provider   = get_option('aicf_default_provider', 'gemini');

        // Business Values
        $company_name    = get_option('aicf_company_name', '');
        $brand_name      = get_option('aicf_brand_name', '');
        $company_website = get_option('aicf_company_website', '');
        $company_hotline = get_option('aicf_company_hotline', '');
        $company_address = get_option('aicf_company_address', '');

        // SEO Post-Processing Options
        $max_internal_links  = get_option('aicf_max_internal_links', 3);
        $duplicate_threshold = get_option('aicf_duplicate_threshold', 80);

        // Prompt Mặc định chuẩn sạch
        $default_prompt = "Bạn là Chuyên gia Kỹ thuật & Biên tập viên Content SEO với 15 năm kinh nghiệm thực chiến tại {brand_name}.\n\n"
                        . "Hãy viết một bài viết phân tích chuyên sâu, sắc bén và tối ưu SEO cho từ khóa: '{keyword}'.\n\n"
                        . "QUY TẮC NỘI DUNG & VĂN PHONG:\n"
                        . "1. VĂN PHONG: Trực diện, đanh thép, góc nhìn chuyên gia thực tế. Tránh xa lối viết lý thuyết sáo rỗng.\n"
                        . "2. TỪ CẤM AI: Tuyệt đối KHÔNG dùng các từ sáo rỗng: 'Trong thế giới ngày nay', 'Không thể phủ nhận', 'Kinh nghiệm xương máu', 'Trái tim của', 'Bài viết này sẽ giúp bạn'.\n"
                        . "3. BẢNG SO SÁNH / BÁO GIÁ: Bắt buộc có 1 Bảng thống kê, bóc tách chi phí hoặc thông số kỹ thuật thực tế năm {year}.\n"
                        . "4. KÊU GỌI HÀNG ĐỘNG (CTA): Đoạn cuối bài viết chèn phần thông tin liên hệ của {brand_name} (Hotline/Zalo: {hotline}, Website: {website}, Địa chỉ: {address}) để thu hút khách hàng tư vấn và báo giá.\n"
                        . "5. ĐỊNH DẠNG XUẤT RÀ: Chỉ sử dụng các thẻ HTML chuẩn: <h2>, <h3>, <p>, <ul>, <li>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <strong>, blockquote. Tuyệt đối KHÔNG dùng cú pháp Markdown.\n"
                        . "6. Bắt đầu ngay bài viết bằng thẻ <h2> hoặc <p>, không viết lời chào vô nghĩa.";

        $system_prompt = get_option('aicf_custom_system_prompt', $default_prompt);
        $system_prompt = str_replace(array('\vert', '\\|'), '|', $system_prompt);
        ?>
        <div class="wrap">
            <h1><?php _e('Cài Đặt AI Content Factory', 'ai-content-factory'); ?></h1>
            <hr />
            <form method="post" action="">
                <?php wp_nonce_field('aicf_save_settings_action'); ?>
                
                <h2>1. API Keys & Provider Mặc Định</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="aicf_gemini_api_key">Gemini API Key</label></th>
                            <td>
                                <input type="password" name="aicf_gemini_api_key" id="aicf_gemini_api_key" value="<?php echo esc_attr($gemini_key); ?>" class="regular-text" placeholder="Nhập Gemini API Key..." />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_openai_api_key">OpenAI API Key</label></th>
                            <td>
                                <input type="password" name="aicf_openai_api_key" id="aicf_openai_api_key" value="<?php echo esc_attr($openai_key); ?>" class="regular-text" placeholder="Nhập OpenAI API Key..." />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_default_provider">Provider Mặc Định</label></th>
                            <td>
                                <select name="aicf_default_provider" id="aicf_default_provider">
                                    <option value="gemini" <?php selected($provider, 'gemini'); ?>>Google Gemini</option>
                                    <option value="openai" <?php selected($provider, 'openai'); ?>>ChatGPT (OpenAI)</option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <hr />
                <h2>2. Thông Tin Doanh Nghiệp (Dùng trong Bài Viết)</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="aicf_company_name">Tên Doanh Nghiệp / Công Ty</label></th>
                            <td>
                                <input type="text" name="aicf_company_name" id="aicf_company_name" value="<?php echo esc_attr($company_name); ?>" class="regular-text" placeholder="Ví dụ: Công ty TNHH Kỹ Thuật Điện Lạnh" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_brand_name">Tên Thương Hiệu (Ngắn)</label></th>
                            <td>
                                <input type="text" name="aicf_brand_name" id="aicf_brand_name" value="<?php echo esc_attr($brand_name); ?>" class="regular-text" placeholder="Ví dụ: Kỹ Thuật Điện Lạnh" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_company_website">Website</label></th>
                            <td>
                                <input type="url" name="aicf_company_website" id="aicf_company_website" value="<?php echo esc_attr($company_website); ?>" class="regular-text" placeholder="https://dichvudienlanh.com" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_company_hotline">Hotline / Zalo</label></th>
                            <td>
                                <input type="text" name="aicf_company_hotline" id="aicf_company_hotline" value="<?php echo esc_attr($company_hotline); ?>" class="regular-text" placeholder="1800.00.08" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_company_address">Địa Chỉ / Xưởng / Showroom</label></th>
                            <td>
                                <input type="text" name="aicf_company_address" id="aicf_company_address" value="<?php echo esc_attr($company_address); ?>" class="large-text" placeholder="Địa chỉ chi nhánh..." />
                            </td>
                        </tr>
                    </tbody>
                </table>

                <hr />
                <h2>3. Tự Động Hóa & Tối Ưu SEO Hậu Kỳ</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="aicf_max_internal_links">Số lượng Internal Links tối đa / bài</label></th>
                            <td>
                                <input type="number" name="aicf_max_internal_links" id="aicf_max_internal_links" value="<?php echo esc_attr($max_internal_links); ?>" min="0" max="10" class="small-text" />
                                <span class="description">Tự động quét từ khóa và chèn link liên quan vào bài viết (mặc định: 3 links).</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="aicf_duplicate_threshold">Ngưỡng cảnh báo trùng lặp nội dung (%)</label></th>
                            <td>
                                <input type="number" name="aicf_duplicate_threshold" id="aicf_duplicate_threshold" value="<?php echo esc_attr($duplicate_threshold); ?>" min="50" max="100" class="small-text" /> %
                                <span class="description">Ghi log cảnh báo nếu bài viết mới sinh ra có mức độ tương đồng vượt quá ngưỡng này so với bài cũ (mặc định: 80%).</span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <hr />
                <h2>4. System Prompt (Khung Viết Bài Tự Động)</h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="aicf_custom_system_prompt">Cấu Trúc Prompt Mặc Định</label></th>
                            <td>
                                <textarea name="aicf_custom_system_prompt" id="aicf_custom_system_prompt" rows="15" class="large-text code" style="font-family: monospace; line-height: 1.5;"><?php echo esc_textarea($system_prompt); ?></textarea>
                                <p class="description" style="margin-top: 8px;">
                                    <strong>Các biến tự động sẽ được chèn khi tạo bài:</strong><br>
                                    - <code>{keyword}</code>: Từ khóa chính.<br>
                                    - <code>{year}</code>: Năm hiện tại.<br>
                                    - <code>{company_name}</code>: Tên Doanh Nghiệp.<br>
                                    - <code>{brand_name}</code>: Tên Thương Hiệu.<br>
                                    - <code>{website}</code>: Website.<br>
                                    - <code>{hotline}</code>: Hotline/Zalo.<br>
                                    - <code>{address}</code>: Địa chỉ.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="aicf_save_settings" class="button button-primary" value="<?php _e('Lưu Cài Đặt', 'ai-content-factory'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
}
