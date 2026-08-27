<?php
namespace AICF\Admin\Campaigns;

use AICF\Engine\QueueManager;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignsPage {

    public static function render() {
        global $wpdb;
        $table      = $wpdb->prefix . 'aicf_campaigns';
        $art_table  = $wpdb->prefix . 'aicf_articles';

        // Xử lý khi bấm nút "Chạy Queue ngay (Manual Run)"
        if (isset($_POST['aicf_run_queue_now'])) {
            if (check_admin_referer('aicf_manual_run_action', 'aicf_manual_nonce')) {
                $status = QueueManager::process_single_item();
                if ($status) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> Đã xử lý xong 1 bài viết từ hàng chờ.</p></div>';
                } else {
                    echo '<div class="notice notice-warning is-dismissible"><p>Hàng chờ trống hoặc tất cả chiến dịch đã đạt giới hạn trong ngày.</p></div>';
                }
            }
        }

        // Xử lý khi bấm nút "Tạo Chiến Dịch"
        if (isset($_POST['aicf_submit_campaign'])) {
            if (check_admin_referer('aicf_create_campaign_action', 'aicf_campaign_nonce')) {
                $title       = sanitize_text_field($_POST['campaign_title'] ?? '');
                $daily_limit = intval($_POST['daily_limit'] ?? 10);

                if (!empty($title)) {
                    $result = $wpdb->insert(
                        $table,
                        [
                            'title'           => $title,
                            'target_language' => sanitize_text_field($_POST['target_language'] ?? 'vi'),
                            'tone_of_voice'   => sanitize_text_field($_POST['tone_of_voice'] ?? 'professional'),
                            'ai_provider'     => sanitize_text_field($_POST['ai_provider'] ?? 'gemini'),
                            'ai_model'        => sanitize_text_field($_POST['ai_model'] ?? 'gemini-1.5-flash'),
                            'daily_limit'     => $daily_limit > 0 ? $daily_limit : 0,
                            'status'          => 'active',
                            'created_at'      => current_time('mysql')
                        ]
                    );

                    if ($result !== false) {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> Đã tạo chiến dịch mới với giới hạn ' . esc_html($daily_limit) . ' bài/ngày.</p></div>';
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

        $today_start = current_time('Y-m-d 00:00:00');
        $today_end   = current_time('Y-m-d 23:59:59');
        ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Quản Lý Chiến Dịch (Campaigns)</h1>
            
            <!-- NÚT KÍCH HOẠT CHẠY THỦ CÔNG (TEST QUEUE) -->
            <form method="post" action="" style="display:inline-block; float:right;">
                <?php wp_nonce_field('aicf_manual_run_action', 'aicf_manual_nonce'); ?>
                <input type="submit" name="aicf_run_queue_now" class="button button-secondary button-large" value="⚡ Chạy 1 Bài Ngay (Thủ Công)" onclick="return confirm('Chạy ngay 1 bài trong Queue?');">
            </form>
            
            <hr class="wp-header-end" />

            <!-- FORM TẠO CAMPAIGN MỚI -->
            <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin: 20px 0; max-width: 600px; border-radius: 4px;">
                <h2>Tạo Chiến Dịch Mới</h2>
                <form method="post" action="">
                    <?php wp_nonce_field('aicf_create_campaign_action', 'aicf_campaign_nonce'); ?>
                    
                    <p>
                        <label><strong>Tên chiến dịch:</strong></label><br>
                        <input type="text" name="campaign_title" class="widefat" placeholder="Ví dụ: Chiến dịch SEO Điện Lạnh" required style="margin-top: 5px;">
                    </p>

                    <p>
                        <label><strong>Giới hạn bài viết trong ngày (Daily Limit):</strong></label><br>
                        <input type="number" name="daily_limit" class="widefat" value="10" min="0" style="margin-top: 5px;">
                        <small style="color: #666;">Nhập 0 nếu không giới hạn. Lịch Cron sẽ tạo tối đa 1 bài/giờ cho đến khi chạm mốc này.</small>
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
                            <option value="gemini">Google Gemini</option>
                            <option value="openai">OpenAI (ChatGPT)</option>
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
                        <th>Tiến Độ Hôm Nay</th>
                        <th>Trạng Thái</th>
                        <th>Ngày Tạo</th>
                        <th width="100">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($campaigns)): ?>
                        <?php foreach ($campaigns as $c): 
                            $today_count = (int)$wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $art_table WHERE campaign_id = %d AND status = 'completed' AND created_at BETWEEN %s AND %s",
                                $c->id, $today_start, $today_end
                            ));
                            $limit_display = isset($c->daily_limit) && (int)$c->daily_limit > 0 ? $c->daily_limit : 'Không giới hạn';
                        ?>
                            <tr>
                                <td><?php echo $c->id; ?></td>
                                <td><strong><?php echo esc_html($c->title); ?></strong></td>
                                <td><?php echo esc_html(strtoupper($c->ai_provider)); ?></td>
                                <td>
                                    <strong><?php echo $today_count; ?></strong> / <?php echo esc_html($limit_display); ?> bài
                                </td>
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
                            <td colspan="7">Chưa có chiến dịch nào. Hãy điền form bên trên để tạo!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
