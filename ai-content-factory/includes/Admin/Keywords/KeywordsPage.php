<?php
namespace AICF\Admin\Keywords;

use AICF\Engine\ContentPipeline;

if (!defined('ABSPATH')) {
    exit;
}

class KeywordsPage {

    public static function render() {
        global $wpdb;
        $kw_table = $wpdb->prefix . 'aicf_keywords';
        $camp_table = $wpdb->prefix . 'aicf_campaigns';

        // 1. Xử lý khi bấm nút "Viết bài AI"
        if (isset($_POST['aicf_generate_single_kw'])) {
            $kw_id = intval($_POST['keyword_id'] ?? 0);
            if ($kw_id > 0 && check_admin_referer('aicf_generate_kw_' . $kw_id)) {
                @set_time_limit(120);

                try {
                    $result = ContentPipeline::process_keyword($kw_id);
                    if ($result) {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> AI đã tạo xong bài viết và lưu vào Bản nháp (Draft) trong mục Bài viết.</p></div>';
                    }
                } catch (\Throwable $e) {
                    // Cập nhật trạng thái failed
                    $wpdb->update($kw_table, ['status' => 'failed'], ['id' => $kw_id]);
                    // IN THẲNG LỖI THẬT RA MÀN HÌNH
                    echo '<div class="notice notice-error"><p><strong>Lỗi thực sự từ API:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
                }
            }
        }

        // 2. Thêm Từ Khóa
        if (isset($_POST['aicf_submit_keywords'])) {
            if (check_admin_referer('aicf_add_keywords_action', 'aicf_keyword_nonce')) {
                $campaign_id = intval($_POST['campaign_id'] ?? 0);
                $keywords_raw = sanitize_textarea_field($_POST['keywords_list'] ?? '');

                if ($campaign_id > 0 && !empty($keywords_raw)) {
                    $lines = explode("\n", str_replace("\r", "", $keywords_raw));
                    $added_count = 0;

                    foreach ($lines as $line) {
                        $keyword = trim($line);
                        if (!empty($keyword)) {
                            $wpdb->insert(
                                $kw_table,
                                [
                                    'campaign_id' => $campaign_id,
                                    'keyword'     => $keyword,
                                    'status'      => 'pending',
                                    'created_at'  => current_time('mysql')
                                ]
                            );
                            $added_count++;
                        }
                    }

                    if ($added_count > 0) {
                        echo '<div class="notice notice-success is-dismissible"><p>Đã thêm ' . $added_count . ' từ khóa.</p></div>';
                    }
                }
            }
        }

        // 3. Xóa Từ Khóa
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $delete_id = intval($_GET['id']);
            if ($delete_id > 0 && check_admin_referer('aicf_delete_kw_' . $delete_id)) {
                $wpdb->delete($kw_table, ['id' => $delete_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Đã xóa từ khóa.</p></div>';
            }
        }

        $campaigns = $wpdb->get_results("SELECT id, title FROM $camp_table ORDER BY id DESC");
        $keywords = $wpdb->get_results("
            SELECT k.*, c.title as campaign_title 
            FROM $kw_table k 
            LEFT JOIN $camp_table c ON k.campaign_id = c.id 
            ORDER BY k.id DESC
        ");
        ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Quản Lý Từ Khóa (Keywords)</h1>
            <hr class="wp-header-end" />

            <!-- FORM THÊM TỪ KHÓA -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0; max-width: 600px; border-radius: 4px;">
                <h2>Thêm Từ Khóa Mới</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('aicf_add_keywords_action', 'aicf_keyword_nonce'); ?>
                    
                    <p>
                        <label><strong>Chọn Chiến Dịch:</strong></label><br>
                        <select name="campaign_id" class="widefat" required style="margin-top: 5px;">
                            <option value="">-- Chọn Campaign --</option>
                            <?php if (!empty($campaigns)): ?>
                                <?php foreach ($campaigns as $c): ?>
                                    <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->title); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </p>

                    <p>
                        <label><strong>Danh sách từ khóa (Mỗi từ 1 dòng):</strong></label><br>
                        <textarea name="keywords_list" rows="3" class="widefat" placeholder="Cửa cuốn giá rẻ" required style="margin-top: 5px;"></textarea>
                    </p>

                    <p style="margin-top: 15px;">
                        <input type="submit" name="aicf_submit_keywords" class="button button-primary" value="Thêm Danh Sách Từ Khóa">
                    </p>
                </form>
            </div>
            <!-- BOX GỢI Ý TỪ KHÓA BẰNG AI -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0; max-width: 700px; border-radius: 4px;">
                <h2>🔍 Phân Tích SEO &amp; Gợi Ý Từ Khóa (AI)</h2>
                <p class="description">Nhập 1 chủ đề/từ khóa gốc, AI sẽ phân tích và gợi ý bộ từ khóa liên quan kèm search intent, cluster.</p>

                <p>
                    <label><strong>Chiến Dịch:</strong></label><br>
                    <select id="aicf-suggest-campaign" class="widefat" style="margin-top:5px;">
                        <option value="">-- Chọn Campaign --</option>
                        <?php foreach ($campaigns as $c): ?>
                            <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->title); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label><strong>Từ khóa / Chủ đề gốc:</strong></label><br>
                    <input type="text" id="aicf-suggest-seed" class="widefat" placeholder="Ví dụ: cửa cuốn công nghiệp" style="margin-top:5px;">
                </p>

                <p>
                    <label><strong>Bối cảnh / Ngành nghề (không bắt buộc):</strong></label><br>
                    <textarea id="aicf-suggest-context" rows="2" class="widefat" placeholder="Ví dụ: công ty chuyên lắp đặt cửa cuốn tại Hà Nội" style="margin-top:5px;"></textarea>
                </p>

                <p>
                    <button type="button" id="aicf-btn-suggest" class="button button-primary">✨ Phân Tích &amp; Gợi Ý Từ Khóa</button>
                </p>

                <div id="aicf-suggest-results" style="margin-top:15px; display:none;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="30"><input type="checkbox" id="aicf-check-all"></th>
                                <th>Từ Khóa</th>
                                <th width="120">Intent</th>
                                <th width="150">Cluster</th>
                                <th width="80">Priority</th>
                            </tr>
                        </thead>
                        <tbody id="aicf-suggest-tbody"></tbody>
                    </table>
                    <p style="margin-top:10px;">
                        <button type="button" id="aicf-btn-add-selected" class="button button-secondary">➕ Thêm Các Từ Khóa Đã Chọn</button>
                    </p>
                </div>
            </div>
            <!-- BẢNG DANH SÁCH -->
            <h2>Danh Sách Từ Khóa</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Từ Khóa</th>
                        <th>Chiến Dịch</th>
                        <th width="120">Trạng Thái</th>
                        <th width="150">Hành Động AI</th>
                        <th width="80">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($keywords)): ?>
                        <?php foreach ($keywords as $k): ?>
                            <tr>
                                <td><?php echo $k->id; ?></td>
                                <td><strong><?php echo esc_html($k->keyword); ?></strong></td>
                                <td><?php echo esc_html($k->campaign_title ?: 'Khác'); ?></td>
                                <td>
                                    <?php 
                                    $status = $k->status;
                                    $bg = '#f0f0f1'; $color = '#333';
                                    if ($status === 'completed') { $bg = '#e7f4e8'; $color = '#0e6251'; }
                                    if ($status === 'failed') { $bg = '#fbeaea'; $color = '#900'; }
                                    ?>
                                    <span style="background:<?php echo $bg; ?>; color:<?php echo $color; ?>; padding:3px 8px; border-radius:3px; font-weight:bold;">
                                        <?php echo esc_html(strtoupper($status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" action="" style="margin:0;">
                                        <?php wp_nonce_field('aicf_generate_kw_' . $k->id); ?>
                                        <input type="hidden" name="keyword_id" value="<?php echo $k->id; ?>">
                                        <input type="submit" name="aicf_generate_single_kw" class="button button-secondary" value="⚡ Viết bài AI" onclick="this.value='Đang viết...';">
                                    </form>
                                </td>
                                <td>
                                    <?php 
                                    $delete_url = wp_nonce_url(
                                        admin_url('admin.php?page=aicf-keywords&action=delete&id=' . $k->id),
                                        'aicf_delete_kw_' . $k->id
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Xóa từ khóa này?');" style="color: #a00;">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Chưa có từ khóa nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}