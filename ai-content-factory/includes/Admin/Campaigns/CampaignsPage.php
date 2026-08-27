<?php
namespace AICF\Admin\Campaigns;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignsPage {

    public static function render() {
        global $wpdb;
        $table = $wpdb->prefix . 'aicf_campaigns';

        // Xử lý khi bấm nút "Tạo Chiến Dịch" (Chạy trực tiếp bằng PHP Form)
        if (isset($_POST['aicf_submit_campaign'])) {
            if (check_admin_referer('aicf_create_campaign_action', 'aicf_campaign_nonce')) {
                $title = sanitize_text_field($_POST['campaign_title'] ?? '');
                
                if (!empty($title)) {
                    $result = $wpdb->insert(
                        $table,
                        [
                            'title'           => $title,
                            'target_language' => sanitize_text_field($_POST['target_language'] ?? 'vi'),
                            'tone_of_voice'   => sanitize_text_field($_POST['tone_of_voice'] ?? 'professional'),
                            'ai_provider'     => sanitize_text_field($_POST['ai_provider'] ?? 'openai'),
                            'ai_model'        => sanitize_text_field($_POST['ai_model'] ?? 'gpt-4o-mini'),
                            'status'          => 'active',
                            'created_at'      => current_time('mysql')
                        ]
                    );

                    if ($result !== false) {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> Đã tạo chiến dịch mới.</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p><strong>Lỗi Database:</strong> ' . esc_html($wpdb->last_error) . '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-warning"><p>Vui lòng nhập tên chiến dịch!</p></div>';
                }
            }
        }

        // Xử lý Xóa Campaign
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
            $delete_id = intval($_GET['id']);
            if ($delete_id > 0 && check_admin_referer('aicf_delete_campaign_' . $delete_id)) {
                $wpdb->delete($table, ['id' => $delete_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Đã xóa chiến dịch thành công.</p></div>';
            }
        }

        // Lấy danh sách Campaign từ Database
        $campaigns = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Quản Lý Chiến Dịch (Campaigns)</h1>
            <hr class="wp-header-end" />

            <!-- FORM TẠO CAMPAIGN MỚI -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0; max-width: 600px; border-radius: 4px;">
                <h2>Tạo Chiến Dịch Mới</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('aicf_create_campaign_action', 'aicf_campaign_nonce'); ?>
                    
                    <p>
                        <label><strong>Tên chiến dịch:</strong></label><br>
                        <input type="text" name="campaign_title" class="widefat" placeholder="Ví dụ: Chiến dịch SEO Cửa Cuốn" required style="margin-top: 5px;">
                    </p>

                    <p>
                        <label><strong>Ngôn ngữ:</strong></label><br>
                        <select name="target_language" class="widefat">
                            <option value="vi">Tiếng Việt</option>
                            <option value="en">Tiếng Anh</option>
                        </select>
                    </p>

                    <p>
                        <label><strong>Provider AI:</strong></label><br>
                        <select name="ai_provider" class="widefat">
                            <option value="openai">OpenAI (ChatGPT)</option>
                            <option value="gemini">Google Gemini</option>
                        </select>
                    </p>

                    <p style="margin-top: 15px;">
                        <input type="submit" name="aicf_submit_campaign" class="button button-primary button-large" value="Tạo Chiến Dịch Ngay">
                    </p>
                </form>
            </div>

            <!-- BẢNG DANH SÁCH CAMPAIGN -->
            <h2>Danh Sách Chiến Dịch</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Tên Chiến Dịch</th>
                        <th>Provider</th>
                        <th>Trạng Thái</th>
                        <th>Ngày Tạo</th>
                        <th width="100">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($campaigns)): ?>
                        <?php foreach ($campaigns as $c): ?>
                            <tr>
                                <td><?php echo $c->id; ?></td>
                                <td><strong><?php echo esc_html($c->title); ?></strong></td>
                                <td><?php echo esc_html(strtoupper($c->ai_provider)); ?></td>
                                <td><span class="badge" style="background:#e7f4e8; color:#0e6251; padding:3px 8px; border-radius:3px;"><?php echo esc_html($c->status); ?></span></td>
                                <td><?php echo esc_html($c->created_at); ?></td>
                                <td>
                                    <?php 
                                    $delete_url = wp_nonce_url(
                                        admin_url('admin.php?page=aicf-campaigns&action=delete&id=' . $c->id),
                                        'aicf_delete_campaign_' . $c->id
                                    );
                                    ?>
                                    <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('Xóa chiến dịch này?');" style="color: #a00;">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Chưa có chiến dịch nào. Hãy điền form bên trên để tạo!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}