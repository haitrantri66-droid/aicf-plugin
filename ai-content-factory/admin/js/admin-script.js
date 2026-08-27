jQuery(document).ready(function ($) {
    'use strict';

    console.log('AICF Admin JS Active');

    /**
     * =========================================================
     * HELPER
     * =========================================================
     */

    function getMessage(response, fallback) {
        if (
            response &&
            response.data &&
            typeof response.data === 'object' &&
            response.data.message
        ) {
            return response.data.message;
        }

        if (response && response.data && typeof response.data === 'string') {
            return response.data;
        }

        return fallback || 'Có lỗi xảy ra.';
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    /**
     * =========================================================
     * 1. SAVE CAMPAIGN
     * =========================================================
     */

    $(document).on(
        'submit',
        '#aicf-campaign-form, .aicf-campaign-form',
        function (e) {
            e.preventDefault();

            var $form = $(this);
            var $btn = $form.find(
                'button[type="submit"], input[type="submit"]'
            );

            var originalText = $btn.is('input')
                ? $btn.val()
                : $btn.text();

            $btn.prop('disabled', true);

            if ($btn.is('input')) {
                $btn.val('Đang lưu...');
            } else {
                $btn.text('Đang lưu...');
            }

            var data = {
                action: 'aicf_save_campaign',
                nonce: aicfAdmin.nonce,
                title: $form.find('[name="title"]').val(),
                target_language:
                    $form.find('[name="target_language"]').val() || 'vi',
                tone_of_voice:
                    $form.find('[name="tone_of_voice"]').val() ||
                    'professional',
                ai_provider:
                    $form.find('[name="ai_provider"]').val() || 'gemini',
                ai_model:
                    $form.find('[name="ai_model"]').val() || ''
            };

            $.ajax({
                url: aicfAdmin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: data
            })
                .done(function (res) {
                    if (res.success) {
                        alert(getMessage(res, 'Lưu Campaign thành công.'));
                        window.location.reload();
                    } else {
                        alert(
                            'Lỗi: ' +
                                getMessage(
                                    res,
                                    'Không thể lưu Campaign.'
                                )
                        );
                    }
                })
                .fail(function (xhr) {
                    console.error(
                        'AICF save campaign error:',
                        xhr.responseText
                    );

                    alert(
                        'Lỗi kết nối máy chủ. Vui lòng kiểm tra Console/Debug Log.'
                    );
                })
                .always(function () {
                    $btn.prop('disabled', false);

                    if ($btn.is('input')) {
                        $btn.val(originalText);
                    } else {
                        $btn.text(originalText);
                    }
                });
        }
    );

    /**
     * =========================================================
     * 2. SAVE SETTINGS
     * =========================================================
     */

    $(document).on('submit', '#aicf-settings-form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $('#btn-save-settings');

        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Đang lưu...');

        var data = {
            action: 'aicf_save_settings',
            nonce: aicfAdmin.nonce,
            openai_key: $('#openai_key').val() || '',
            gemini_key: $('#gemini_key').val() || '',
            default_provider: $('#default_provider').val() || 'gemini'
        };

        $.ajax({
            url: aicfAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: data
        })
            .done(function (res) {
                if (res.success) {
                    alert(
                        getMessage(
                            res,
                            'Đã lưu cài đặt thành công.'
                        )
                    );
                } else {
                    alert(
                        'Lỗi: ' +
                            getMessage(
                                res,
                                'Không thể lưu cài đặt.'
                            )
                    );
                }
            })
            .fail(function (xhr) {
                console.error(
                    'AICF save settings error:',
                    xhr.responseText
                );

                alert(
                    'Lỗi kết nối máy chủ. Vui lòng kiểm tra Console/Debug Log.'
                );
            })
            .always(function () {
                $btn.prop('disabled', false).text(originalText);
            });
    });

    /**
     * =========================================================
     * 3. ADD KEYWORD MANUALLY
     * =========================================================
     *
     * IMPORTANT:
     * Plugin sử dụng action:
     * aicf_add_keyword
     *
     * Không dùng aicf_save_keyword.
     */

    $(document).on('submit', '#aicf-keyword-form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');

        var originalText = $btn.text();

        var campaignId = $form
            .find('[name="campaign_id"]')
            .val();

        var keyword = $form
            .find('[name="keyword"]')
            .val();

        var intent = $form
            .find('[name="intent"]')
            .val() || '';

        var cluster = $form
            .find('[name="cluster"]')
            .val() || '';

        if (!campaignId) {
            alert('Vui lòng chọn Campaign.');
            return;
        }

        if (!keyword || !keyword.trim()) {
            alert('Vui lòng nhập Keyword.');
            return;
        }

        $btn.prop('disabled', true).text('Đang thêm...');

        $.ajax({
            url: aicfAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'aicf_add_keyword',
                nonce: aicfAdmin.nonce,
                campaign_id: campaignId,
                keyword: keyword,
                intent: intent,
                cluster: cluster
            }
        })
            .done(function (res) {
                if (res.success) {
                    alert(
                        getMessage(
                            res,
                            'Thêm Keyword thành công.'
                        )
                    );

                    window.location.reload();
                } else {
                    alert(
                        'Lỗi: ' +
                            getMessage(
                                res,
                                'Không thể thêm Keyword.'
                            )
                    );
                }
            })
            .fail(function (xhr) {
                console.error(
                    'AICF add keyword error:',
                    xhr.responseText
                );

                alert(
                    'Lỗi kết nối máy chủ. Vui lòng kiểm tra Console/Debug Log.'
                );
            })
            .always(function () {
                $btn.prop('disabled', false).text(originalText);
            });
    });

    /**
     * =========================================================
     * 4. AI KEYWORD ANALYSIS / SUGGESTION
     * =========================================================
     */

    $(document).on('click', '#aicf-btn-suggest', function (e) {
        e.preventDefault();

        var $btn = $(this);

        var seed = $.trim(
            $('#aicf-suggest-seed').val() || ''
        );

        var context = $.trim(
            $('#aicf-suggest-context').val() || ''
        );

        if (!seed) {
            alert(
                'Vui lòng nhập từ khóa hoặc chủ đề gốc.'
            );

            $('#aicf-suggest-seed').focus();

            return;
        }

        var originalText = $btn.text();

        $btn
            .prop('disabled', true)
            .text('Đang phân tích bằng AI...');

        $('#aicf-suggest-results').hide();
        $('#aicf-suggest-tbody').empty();

        $.ajax({
            url: aicfAdmin.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 180000,
            data: {
                action: 'aicf_suggest_keywords',
                nonce: aicfAdmin.nonce,
                seed_keyword: seed,
                context: context
            }
        })
            .done(function (res) {
                console.log(
                    'AICF keyword analysis response:',
                    res
                );

                if (!res || !res.success) {
                    alert(
                        'Lỗi: ' +
                            getMessage(
                                res,
                                'AI không thể phân tích từ khóa.'
                            )
                    );

                    return;
                }

                var keywords = [];

                if (
                    res.data &&
                    Array.isArray(res.data.keywords)
                ) {
                    keywords = res.data.keywords;
                }

                if (!keywords.length) {
                    alert(
                        'AI không trả về từ khóa nào. Vui lòng thử lại.'
                    );

                    return;
                }

                var $tbody =
                    $('#aicf-suggest-tbody');

                $.each(keywords, function (index, kw) {
                    if (
                        !kw ||
                        !kw.keyword ||
                        !String(kw.keyword).trim()
                    ) {
                        return;
                    }

                    var keyword = String(
                        kw.keyword
                    ).trim();

                    var intent = String(
                        kw.intent || 'informational'
                    ).trim();

                    var cluster = String(
                        kw.cluster || ''
                    ).trim();

                    var priority = parseInt(
                        kw.priority,
                        10
                    );

                    if (
                        isNaN(priority) ||
                        priority < 1
                    ) {
                        priority = 3;
                    }

                    if (priority > 5) {
                        priority = 5;
                    }

                    var priorityLabel =
                        'Priority ' + priority;

                    var row =
                        '<tr>' +
                        '<td>' +
                        '<input type="checkbox" ' +
                        'class="aicf-kw-check" ' +
                        'data-keyword="' +
                        escapeHtml(keyword) +
                        '" ' +
                        'data-intent="' +
                        escapeHtml(intent) +
                        '" ' +
                        'data-cluster="' +
                        escapeHtml(cluster) +
                        '" ' +
                        'data-priority="' +
                        priority +
                        '" checked>' +
                        '</td>' +

                        '<td>' +
                        '<strong>' +
                        escapeHtml(keyword) +
                        '</strong>' +
                        '</td>' +

                        '<td>' +
                        escapeHtml(intent) +
                        '</td>' +

                        '<td>' +
                        escapeHtml(cluster) +
                        '</td>' +

                        '<td>' +
                        escapeHtml(priorityLabel) +
                        '</td>' +

                        '</tr>';

                    $tbody.append(row);
                });

                if (!$tbody.children().length) {
                    alert(
                        'Kết quả AI không chứa dữ liệu từ khóa hợp lệ.'
                    );

                    return;
                }

                $('#aicf-check-all').prop(
                    'checked',
                    true
                );

                $('#aicf-suggest-results').show();

                // Scroll nhẹ tới kết quả.
                $('html, body').animate(
                    {
                        scrollTop:
                            $('#aicf-suggest-results').offset()
                                ? $('#aicf-suggest-results')
                                      .offset().top - 50
                                : 0
                    },
                    300
                );
            })
            .fail(function (xhr, status) {
                console.error(
                    'AICF keyword analysis error:',
                    status,
                    xhr.responseText
                );

                if (status === 'timeout') {
                    alert(
                        'AI xử lý quá lâu và trình duyệt đã timeout. ' +
                        'Vui lòng thử lại hoặc giảm độ dài Bối cảnh.'
                    );
                } else {
                    alert(
                        'Lỗi kết nối máy chủ khi phân tích từ khóa. ' +
                        'Vui lòng kiểm tra Console và WordPress Debug Log.'
                    );
                }
            })
            .always(function () {
                $btn
                    .prop('disabled', false)
                    .text(originalText);
            });
    });

    /**
     * =========================================================
     * 5. CHECK / UNCHECK ALL
     * =========================================================
     */

    $(document).on(
        'change',
        '#aicf-check-all',
        function () {
            $('.aicf-kw-check').prop(
                'checked',
                $(this).is(':checked')
            );
        }
    );

    /**
     * Nếu bỏ chọn từng item thì cập nhật checkbox All.
     */

    $(document).on(
        'change',
        '.aicf-kw-check',
        function () {
            var total =
                $('.aicf-kw-check').length;

            var checked =
                $('.aicf-kw-check:checked').length;

            $('#aicf-check-all').prop(
                'checked',
                total > 0 && total === checked
            );
        }
    );

    /**
     * =========================================================
     * 6. ADD SELECTED AI KEYWORDS TO CAMPAIGN
     * =========================================================
     */

    $(document).on(
        'click',
        '#aicf-btn-add-selected',
        function (e) {
            e.preventDefault();

            var $btn = $(this);

            var campaignId =
                $('#aicf-suggest-campaign').val();

            if (!campaignId) {
                alert(
                    'Vui lòng chọn Campaign trước khi thêm từ khóa.'
                );

                return;
            }

            var items = [];

            $('.aicf-kw-check:checked').each(
                function () {
                    var $checkbox = $(this);

                    items.push({
                        keyword:
                            $checkbox.attr(
                                'data-keyword'
                            ) || '',

                        intent:
                            $checkbox.attr(
                                'data-intent'
                            ) || 'informational',

                        cluster:
                            $checkbox.attr(
                                'data-cluster'
                            ) || '',

                        priority: parseInt(
                            $checkbox.attr(
                                'data-priority'
                            ),
                            10
                        ) || 3
                    });
                }
            );

            if (!items.length) {
                alert(
                    'Vui lòng chọn ít nhất 1 từ khóa.'
                );

                return;
            }

            var originalText = $btn.text();

            $btn
                .prop('disabled', true)
                .text('Đang thêm ' + items.length + ' từ khóa...');

            $.ajax({
                url: aicfAdmin.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'aicf_bulk_add_suggested',
                    nonce: aicfAdmin.nonce,
                    campaign_id: campaignId,
                    items: items
                }
            })
                .done(function (res) {
                    if (res.success) {
                        alert(
                            getMessage(
                                res,
                                'Đã thêm từ khóa thành công.'
                            )
                        );

                        window.location.reload();
                    } else {
                        alert(
                            'Lỗi: ' +
                                getMessage(
                                    res,
                                    'Không thể thêm từ khóa.'
                                )
                        );
                    }
                })
                .fail(function (xhr) {
                    console.error(
                        'AICF bulk add keywords error:',
                        xhr.responseText
                    );

                    alert(
                        'Lỗi kết nối máy chủ. Vui lòng thử lại.'
                    );
                })
                .always(function () {
                    $btn
                        .prop('disabled', false)
                        .text(originalText);
                });
        }
    );
});
