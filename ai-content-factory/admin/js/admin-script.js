jQuery(document).ready(function ($) {
    console.log('AICF Core Engine Active');

    // 1. XỬ LÝ FORM CAMPAIGN
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

    // 2. XỬ LÝ FORM SETTINGS
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
    // 4. GỢI Ý TỪ KHÓA BẰNG AI
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
    // 3. XỬ LÝ FORM ADD KEYWORD
    $(document).on('submit', '#aicf-keyword-form', function (e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('Đang thêm...');

        var data = {
            action: 'aicf_save_keyword',
            nonce: aicfAdmin.nonce,
            campaign_id: $form.find('[name="campaign_id"]').val(),
            keyword: $form.find('[name="keyword"]').val()
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
});