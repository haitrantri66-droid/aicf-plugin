jQuery(document).ready(function ($) {
    console.log('AICF Core Engine Active');

    // ==========================================
    // 1. QUẢN LÝ CAMPAIGN (Thêm, Sửa, Xóa)
    // ==========================================

    // Thêm / Lưu Campaign
    $(document).on('submit', '#aicf-campaign-form, .aicf-campaign-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"], input[type="submit"]');

        $btn.prop('disabled', true).text('Đang lưu...');

        var data = {
            action: 'aicf_save_campaign',
            nonce: aicfAdmin.nonce,
            title: $form.find('[name="title"]').val(),
            target_language: $form.find('[name="target_language"]').val() || 'vi',
            tone_of_voice: $form.find('[name="tone_of_voice"]').val() || 'professional',
            ai_provider: $form.find('[name="ai_provider"]').val() || 'openai',
            ai_model: $form.find('[name="ai_model"]').val() || 'gpt-4o-mini'
        };

        $.post(aicfAdmin.ajax_url, data, function (res) {
            $btn.prop('disabled', false).text('Lưu Chiến Dịch');
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + res.data.message);
            }
        });
    });

    // Xóa Campaign (Tự động xóa sạch toàn bộ Keywords liên quan)
    $(document).on('click', '.btn-delete-campaign, #aicf-btn-delete-campaign', function (e) {
        e.preventDefault();
        var campaignId = $(this).data('id');

        if (!campaignId) return;

        if (!confirm('CẢNH BÁO: Xóa Chiến dịch này sẽ XÓA SẠCH toàn bộ danh sách từ khóa bên trong! Bạn có chắc chắn muốn xóa?')) {
            return;
        }

        $.post(aicfAdmin.ajax_url, {
            action: 'aicf_delete_campaign',
            nonce: aicfAdmin.nonce,
            campaign_id: campaignId
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + (res.data.message || 'Không thể xóa chiến dịch.'));
            }
        });
    });


    // ==========================================
    // 2. QUẢN LÝ TỪ KHÓA (Thêm, Sửa, Xóa Hàng Loạt)
    // ==========================================

    // Thêm từ khóa thủ công
    $(document).on('submit', '#aicf-keyword-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('Đang thêm...');

        var data = {
            action: 'aicf_add_keyword',
            nonce: aicfAdmin.nonce,
            campaign_id: $form.find('[name="campaign_id"]').val(),
            keyword: $form.find('[name="keyword"]').val(),
            intent: $form.find('[name="intent"]').val() || '',
            cluster: $form.find('[name="cluster"]').val() || ''
        };

        $.post(aicfAdmin.ajax_url, data, function (res) {
            $btn.prop('disabled', false).text('Thêm Từ Khóa');
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + res.data.message);
            }
        });
    });

    // Sửa từ khóa thủ công (Nút Sửa kế bên nút Xóa)
    $(document).on('click', '.btn-edit-keyword, .aicf-btn-edit-keyword', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var id = $btn.data('id');
        var currentKw = $btn.data('keyword') || $btn.closest('tr').find('.keyword-text').text().trim();

        var newKw = prompt('Chỉnh sửa từ khóa:', currentKw);
        if (newKw === null || newKw.trim() === '') return;

        $.post(aicfAdmin.ajax_url, {
            action: 'aicf_update_keyword',
            nonce: aicfAdmin.nonce,
            id: id,
            keyword: newKw.trim()
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + (res.data.message || 'Không thể cập nhật từ khóa.'));
            }
        });
    });

    // Xóa 1 từ khóa đơn lẻ
    $(document).on('click', '.btn-delete-keyword, .aicf-btn-delete-keyword', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        if (!id) return;

        if (!confirm('Bạn có chắc chắn muốn xóa từ khóa này?')) return;

        $.post(aicfAdmin.ajax_url, {
            action: 'aicf_delete_keyword',
            nonce: aicfAdmin.nonce,
            id: id
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + (res.data.message || 'Không thể xóa từ khóa.'));
            }
        });
    });

    // TÍCH CHỌN TẤT CẢ (Select All Keywords) BẢNG BÀI VIẾT / TỪ KHÓA
    $(document).on('change', '#aicf-select-all-keywords, .aicf-select-all-keywords', function () {
        var isChecked = $(this).is(':checked');
        $('.aicf-keyword-cb, .keyword-cb').prop('checked', isChecked);
        toggleBulkDeleteButton();
    });

    $(document).on('change', '.aicf-keyword-cb, .keyword-cb', function () {
        var total = $('.aicf-keyword-cb, .keyword-cb').length;
        var checked = $('.aicf-keyword-cb:checked, .keyword-cb:checked').length;
        $('#aicf-select-all-keywords, .aicf-select-all-keywords').prop('checked', total === checked && total > 0);
        toggleBulkDeleteButton();
    });

    function toggleBulkDeleteButton() {
        var checkedCount = $('.aicf-keyword-cb:checked, .keyword-cb:checked').length;
        var $btnBulk = $('#aicf-btn-bulk-delete-keywords, #btn-bulk-delete-keywords');
        
        if (checkedCount > 0) {
            $btnBulk.show().removeClass('hidden').text('Xóa Đã Chọn (' + checkedCount + ')');
        } else {
            $btnBulk.hide().addClass('hidden');
        }
    }

    // XÓA HÀNG LOẠT TỪ KHÓA ĐÃ CHỌN
    $(document).on('click', '#aicf-btn-bulk-delete-keywords, #btn-bulk-delete-keywords', function (e) {
        e.preventDefault();
        var selectedIds = [];
        $('.aicf-keyword-cb:checked, .keyword-cb:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) return;

        if (!confirm('Bạn có chắc chắn muốn xóa ' + selectedIds.length + ' từ khóa đã chọn?')) {
            return;
        }

        $.post(aicfAdmin.ajax_url, {
            action: 'aicf_bulk_delete_keywords',
            nonce: aicfAdmin.nonce,
            ids: selectedIds
        }, function (res) {
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + (res.data.message || 'Có lỗi xảy ra khi xóa.'));
            }
        });
    });


    // ==========================================
    // 3. GỢI Ý TỪ KHÓA BẰNG AI
    // ==========================================

    $(document).on('click', '#aicf-btn-suggest', function () {
        var $btn = $(this);
        var seed = $('#aicf-suggest-seed').val();
        var context = $('#aicf-suggest-context').val();

        if (!seed) {
            alert('Vui lòng nhập từ khóa hoặc chủ đề gốc.');
            return;
        }

        $btn.prop('disabled', true).text('Đang phân tích...');
        $('#aicf-suggest-results').hide();
        $('#aicf-suggest-tbody').empty();

        $.post(aicfAdmin.ajax_url, {
            action: 'aicf_suggest_keywords',
            nonce: aicfAdmin.nonce,
            seed_keyword: seed,
            context: context
        }, function (res) {
            $btn.prop('disabled', false).text('✨ Phân Tích & Gợi Ý Từ Khóa');

            if (!res.success) {
                alert('Lỗi: ' + res.data.message);
                return;
            }

            var $tbody = $('#aicf-suggest-tbody');
            $.each(res.data.keywords, function (i, kw) {
                var row = '<tr>' +
                    '<td><input type="checkbox" class="aicf-kw-check" ' +
                        'data-keyword="' + $('<div>').text(kw.keyword).html() + '" ' +
                        'data-intent="' + kw.intent + '" ' +
                        'data-cluster="' + $('<div>').text(kw.cluster).html() + '" ' +
                        'data-priority="' + kw.priority + '" checked></td>' +
                    '<td><strong>' + $('<div>').text(kw.keyword).html() + '</strong></td>' +
                    '<td>' + kw.intent + '</td>' +
                    '<td>' + $('<div>').text(kw.cluster).html() + '</td>' +
                    '<td>' + kw.priority + '</td>' +
                    '</tr>';
                $tbody.append(row);
            });

            $('#aicf-suggest-results').show();
        }).fail(function () {
            $btn.prop('disabled', false).text('✨ Phân Tích & Gợi Ý Từ Khóa');
            alert('Lỗi kết nối, vui lòng thử lại.');
        });
    });

    $(document).on('change', '#aicf-check-all', function () {
        $('.aicf-kw-check').prop('checked', $(this).is(':checked'));
    });

    $(document).on('click', '#aicf-btn-add-selected', function () {
        var $btn = $(this);
        var campaign_id = $('#aicf-suggest-campaign').val();

        if (!campaign_id) {
            alert('Vui lòng chọn Campaign trước khi thêm từ khóa.');
            return;
        }

        var items = [];
        $('.aicf-kw-check:checked').each(function () {
            items.push({
                keyword: $(this).data('keyword'),
                intent: $(this).data('intent'),
                cluster: $(this).data('cluster'),
                priority: $(this).data('priority')
            });
        });

        if (items.length === 0) {
            alert('Vui lòng chọn ít nhất 1 từ khóa.');
            return;
        }

        $btn.prop('disabled', true).text('Đang thêm...');

        $.post(aicfAdmin.ajax_url, {
            action: 'aicf_bulk_add_suggested',
            nonce: aicfAdmin.nonce,
            campaign_id: campaign_id,
            items: items
        }, function (res) {
            $btn.prop('disabled', false).text('➕ Thêm Các Từ Khóa Đã Chọn');
            if (res.success) {
                alert(res.data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + res.data.message);
            }
        });
    });


    // ==========================================
    // 4. XỬ LÝ FORM SETTINGS
    // ==========================================

    $(document).on('submit', '#aicf-settings-form', function (e) {
        e.preventDefault();
        var $btn = $('#btn-save-settings');
        $btn.prop('disabled', true).text('Đang lưu...');

        var data = {
            action: 'aicf_save_settings',
            nonce: aicfAdmin.nonce,
            openai_key: $('#openai_key').val(),
            gemini_key: $('#gemini_key').val(),
            default_provider: $('#default_provider').val()
        };

        $.post(aicfAdmin.ajax_url, data, function (res) {
            $btn.prop('disabled', false).text('Lưu Cài Đặt');
            alert(res.data.message);
        });
    });
});
