<?php
namespace AICF\Admin\Campaigns;

use AICF\Engine\QueueManager;

if (!defined('ABSPATH')) {
    exit;
}

class CampaignsPage {

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'aicf'));
        }

        global $wpdb;
        $table      = $wpdb->prefix . 'aicf_campaigns';
        $art_table  = $wpdb->prefix . 'aicf_articles';

        // TỰ ĐỘNG BỔ SUNG CỘT BỊ THIẾU (Phòng ngừa lỗi Unknown column)
        $column = $wpdb->get_results("SHOW COLUMNS FROM `{$table}` LIKE 'daily_limit'");
        if (empty($column)) {
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN `daily_limit` INT DEFAULT 0");
        }

        // 1. Xử lý Chạy Queue ngay (Manual Run)
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

        // 2. Xử lý Thao tác URL (Kích hoạt / Tạm dừng / Xóa)
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action_id = intval($_GET['id']);
            $action    = sanitize_text_field($_GET['action']);

            if ($action_id > 0 && check_admin_referer('aicf_campaign_action_' . $action_id)) {
                if ($action === 'activate') {
                    $wpdb->update($table, ['status' => 'active'], ['id' => $action_id]);
                    echo '<div class="notice notice-success is-dismissible"><p>Đã kích hoạt chiến dịch thành công.</p></div>';
                } elseif ($action === 'pause') {
                    $wpdb->update($table, ['status' => 'paused'], ['id' => $action_id]);
                    echo '<div class="notice notice-warning is-dismissible"><p>Đã tạm dừng chiến dịch.</p></div>';
                } elseif ($action === 'delete') {
                    $wpdb->delete($table, ['id' => $action_id]);
                    $wpdb->delete($wpdb->prefix . 'aicf_keywords', ['campaign_id' => $action_id]);
                    echo '<div class="notice notice-success is-dismissible"><p>Đã xóa chiến dịch thành công.</p></div>';
                }
            }
        }

        // 3. Xử lý Cập nhật Chiến Dịch (Khi bấm Lưu ở modal Sửa)
        if (isset($_POST['aicf_update_campaign'])) {
            $edit_id = intval($_POST['edit_campaign_id'] ?? 0);
            if ($edit_id > 0 && check_admin_referer('aicf_edit_campaign_action_' . $edit_id)) {
                $title       = sanitize_text_field($_POST['campaign_title'] ?? '');
                $daily_limit = intval($_POST['daily_limit'] ?? 10);

                if (!empty($title)) {
                    $wpdb->update(
                        $table,
                        [
                            'title'           => $title,
                            'target_language' => sanitize_text_field($_POST['target_language'] ?? 'vi'),
                            'ai_provider'     => sanitize_text_field($_POST['ai_provider'] ?? 'gemini'),
                            'daily_limit'     => $daily_limit > 0 ? $daily_limit : 0,
                        ],
                        ['id' => $edit_id]
                    );
                    echo '<div class="notice notice-success is-dismissible"><p>Đã cập nhật chiến dịch thành công!</p></div>';
                }
            }
        }

        // 4. Xử lý Tạo Chiến Dịch Mới
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
                        echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> Đã tạo chiến dịch mới.</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p><strong>Lỗi Database:</strong> ' . esc_html($wpdb->last_error) . '</p></div>';
                    }
                } else {
                    echo '<div class="notice notice-warning"><p>Vui lòng nhập tên chiến dịch!</p></div>';
                }
            }
        }

        // Lấy danh sách Campaign
        $campaigns   = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC");
        $today_start = current_time('Y-m-d 00:00:00');
        $today_end   = current_time('Y-m-d 23:59:59');

        // Kiểm tra xem có đang mở Modal Sửa không
        $editing_campaign = null;
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
            $edit_id = intval($_GET['id']);
            if ($edit_id > 0) {
                $editing_campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
            }
        }
        ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Quản Lý Chiến Dịch (Campaigns)</h1>
            
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
                        <small style="color: #666;">Nhập 0 nếu không giới hạn.</small>
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

            <!-- MODAL HOẶC BOX CHỈNH SỬA CHIẾN DỊCH (CHỈ HIỆN KHI BẤM NÚT SỬA) -->
            <?php if ($editing_campaign): ?>
                <div style="background: #fff8e5; padding: 20px; border: 1px solid #faebcc; margin: 20px 0; max-width: 600px; border-radius: 4px;">
                    <h2>✏️ Chỉnh Sửa Chiến Dịch #<?php echo esc_html($editing_campaign->id); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=aicf-campaigns')); ?>">
                        <?php wp_nonce_field('aicf_edit_campaign_action_' . $editing_campaign->id); ?>
                        <input type="hidden" name="edit_campaign_id" value="<?php echo esc_attr($editing_campaign->id); ?>">

                        <p>
                            <label><strong>Tên chiến dịch:</strong></label><br>
                            <input type="text" name="campaign_title" class="widefat" value="<?php echo esc_attr($editing_campaign->title); ?>" required style="margin-top: 5px;">
                        </p>

                        <p>
                            <label><strong>Giới hạn bài viết trong ngày:</strong></label><br>
                            <input type="number" name="daily_limit" class="widefat" value="<?php echo esc_attr($editing_campaign->daily_limit ?? 0); ?>" min="0" style="margin-top: 5px;">
                        </p>

                        <p>
                            <label><strong>Ngôn ngữ:</strong></label><br>
                            <select name="target_language" class="widefat">
                                <option value="vi" <?php selected($editing_campaign->target_language, 'vi'); ?>>Tiếng Việt</option>
                                <option value="en" <?php selected($editing_campaign->target_language, 'en'); ?>>Tiếng Anh</option>
                            </select>
                        </p>

                        <p>
                            <label><strong>Provider AI:</strong></label><br>
                            <select name="ai_provider" class="widefat">
                                <option value="gemini" <?php selected($editing_campaign->ai_provider, 'gemini'); ?>>Google Gemini</option>
                                <option value="openai" <?php selected($editing_campaign->ai_provider, 'openai'); ?>>OpenAI (ChatGPT)</option>
                            </select>
                        </p>

                        <p style="margin-top: 15px;">
                            <input type="submit" name="aicf_update_campaign" class="button button-primary" value="Lưu Thay Đổi">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=aicf-campaigns')); ?>" class="button">Hủy</a>
                        </p>
                    </form>
                </div>
            <?php endif; ?>

            <!-- BẢNG DANH SÁCH CAMPAIGN -->
            <h2>Danh Sách Chiến Dịch</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Tên Chiến Dịch</th>
                        <th width="100">Provider</th>
                        <th width="140">Tiến Độ Hôm Nay</th>
                        <th width="110">Trạng Thái</th>
                        <th width="140">Ngày Tạo</th>
                        <th width="210">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($campaigns)): ?>
                        <?php foreach ($campaigns as $c): 
                            $today_count = (int)$wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $art_table WHERE campaign_id = %d AND status = 'completed' AND created_at BETWEEN %s AND %s",
                                $c->id, $today_start, $today_end
                            ));
                            $limit_display = isset($c->daily_limit) && (int)$c->daily_limit > 0 ? $c->daily_limit : 'Không GH';
                            $status = $c->status ?? 'active';
                        ?>
                            <tr>
                                <td><?php echo esc_html($c->id); ?></td>
                                <td><strong><?php echo esc_html($c->title); ?></strong></td>
                                <td><?php echo esc_html(strtoupper($c->ai_provider)); ?></td>
                                <td>
                                    <strong><?php echo esc_html($today_count); ?></strong> / <?php echo esc_html($limit_display); ?> bài
                                </td>
                                <td>
                                    <?php if ($status === 'active'): ?>
                                        <span style="background:#e7f4e8; color:#0e6251; padding:3px 8px; border-radius:3px; font-weight:bold;">Đang chạy</span>
                                    <?php else: ?>
                                        <span style="background:#fcf8e3; color:#8a6d3b; padding:3px 8px; border-radius:3px; font-weight:bold;">Tạm dừng</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($c->created_at); ?></td>
                                
                                <!-- CỘT THAO TÁC ĐÃ BỔ SUNG CÁC NÚT KÍCH HOẠT / TẠM DỪNG / SỬA / XÓA -->
                                <td>
                                    <?php 
                                    $base_url     = admin_url('admin.php?page=aicf-campaigns&id=' . $c->id);
                                    $activate_url = wp_nonce_url($base_url . '&action=activate', 'aicf_campaign_action_' . $c->id);
                                    $pause_url    = wp_nonce_url($base_url . '&action=pause', 'aicf_campaign_action_' . $c->id);
                                    $delete_url   = wp_nonce_url($base_url . '&action=delete', 'aicf_campaign_action_' . $c->id);
                                    $edit_url     = admin_url('admin.php?page=aicf-campaigns&action=edit&id=' . $c->id);
                                    ?>

                                    <?php if ($status === 'active'): ?>
                                        <a href="<?php echo esc_url($pause_url); ?>" class="button button-small">Tạm dừng</a>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($activate_url); ?>" class="button button-small button-primary">Kích hoạt</a>
                                    <?php endif; ?>

                                    <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Sửa</a>

                                    <a href="<?php echo esc_url($delete_url); ?>" class="button button-small" onclick="return confirm('CẢNH BÁO: Xóa chiến dịch sẽ xóa sạch các từ khóa liên quan?');" style="color: #a00; border-color: #a00;">Xóa</a>
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
