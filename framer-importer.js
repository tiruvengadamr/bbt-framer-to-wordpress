/* global jQuery, BBTFramerImporter */
/**
 * JavaScript for BBT Framer to WordPress Importer.
 *
 * Handles uploading a CSV, mapping columns to post fields, and processing the import via AJAX.
 */
(function ($) {
    'use strict';

    const $step1 = $('#bbt-import-step1');
    const $step2 = $('#bbt-import-step2');
    const $step3 = $('#bbt-import-step3');
    const $uploadForm = $('#bbt-upload-form');
    const $mapForm = $('#bbt-map-form');
    const $progress = $('#bbt-import-progress');
    const $results = $('#bbt-import-results');
    const $retryContainer = $('#bbt-import-retry-container');
    const $retryButton = $('#bbt-import-retry');

    let currentToken = '';
    let mapping = {};
    let metaKeys = {};
    let skipDupes = 0;

    function showError(msg) {
        window.alert(msg);
    }

    function buildMappingUI(data) {
        $step1.hide();
        $step2.show();
        currentToken = data.token;
        $('#bbt_map_token').val(currentToken);
        const $table = $('<table class="widefat striped"><thead><tr><th>' +
            BBTFramerImporter.headersLabel + '</th><th>' + BBTFramerImporter.mapToLabel + '</th><th>' + BBTFramerImporter.metaKeyLabel + '</th></tr></thead><tbody></tbody></table>');
        const $tbody = $table.find('tbody');
        data.headers.forEach(function (header) {
            const $row = $('<tr class="bbt-map-row"></tr>').attr('data-header', header);
            $row.append('<td><strong>' + header + '</strong></td>');
            const $select = $('<select class="bbt-map-select"></select>');
            $select.append('<option value="">—</option>');
            $.each(data.fields, function (key, label) {
                $select.append('<option value="' + key + '">' + label + '</option>');
            });
            const $metaInput = $('<input type="text" class="bbt-meta-key" style="display:none;" placeholder="meta key" />');
            const $selectCell = $('<td></td>').append($select);
            const $metaCell = $('<td></td>').append($metaInput);
            $row.append($selectCell).append($metaCell);
            $tbody.append($row);
        });
        $mapForm.find('.mapping-table').remove();
        $('<div class="mapping-table"></div>').append($table).insertBefore($mapForm.find('p:first'));
    }

    $uploadForm.on('submit', function (e) {
        e.preventDefault();
        const fileInput = $('#bbt_csv_file')[0];
        if (!fileInput.files.length) {
            showError(BBTFramerImporter.errorNoFile || 'Please select a CSV file.');
            return;
        }
        const formData = new FormData();
        formData.append('action', 'bbt_framer_prepare');
        formData.append('nonce', BBTFramerImporter.nonce);
        formData.append('csv_file', fileInput.files[0]);
        $('#bbt-upload-button').prop('disabled', true);
        $.ajax({
            url: BBTFramerImporter.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        }).done(function (res) {
            $('#bbt-upload-button').prop('disabled', false);
            if (res.success) {
                buildMappingUI(res.data);
            } else {
                showError(res.data || 'Error preparing CSV import.');
            }
        }).fail(function () {
            $('#bbt-upload-button').prop('disabled', false);
            showError('Unexpected error while uploading the CSV.');
        });
    });

    // Show/hide meta key input when selecting custom field.
    $(document).on('change', '.bbt-map-select', function () {
        const $row = $(this).closest('.bbt-map-row');
        const $metaInput = $row.find('.bbt-meta-key');
        if ($(this).val() === 'custom_field') {
            $metaInput.show();
        } else {
            $metaInput.hide();
        }
    });

    $mapForm.on('submit', function (e) {
        e.preventDefault();
        mapping = {};
        metaKeys = {};
        $('.bbt-map-row').each(function () {
            const header = $(this).data('header');
            const field = $(this).find('select.bbt-map-select').val();
            if (field) {
                mapping[header] = field;
                if (field === 'custom_field') {
                    const keyVal = $(this).find('input.bbt-meta-key').val();
                    if (keyVal) {
                        metaKeys[header] = keyVal;
                    }
                }
            }
        });
        if ($.isEmptyObject(mapping)) {
            showError(BBTFramerImporter.errorNoMapping || 'Please map at least one column.');
            return;
        }
        skipDupes = $('#bbt-skip-duplicates').is(':checked') ? 1 : 0;
        $step2.hide();
        $step3.show();
        $progress.text('Starting import…');
        $results.empty();
        $retryContainer.hide();
        processBatch(false);
    });

    function processBatch(retry) {
        const postData = {
            action: retry ? 'bbt_framer_retry_failed' : 'bbt_framer_process_batch',
            nonce: BBTFramerImporter.nonce,
            token: currentToken,
            retry: retry ? 1 : 0
        };
        if (!retry && $progress.text() === 'Starting import…') {
            postData.mapping = mapping;
            postData.meta_keys = metaKeys;
            postData.skip_duplicates = skipDupes;
        }
        $.ajax({
            url: BBTFramerImporter.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: postData
        }).done(function (res) {
            if (res.success) {
                const data = res.data;
                const imported = data.imported || 0;
                const skipped = data.skipped || 0;
                const failed = data.failed || 0;
                const total = data.total || 0;
                const done = data.done;
                const retryable = data.retryable;
                $progress.text('Imported: ' + imported + ' | Skipped: ' + skipped + ' | Failed: ' + failed + ' | Total: ' + total);
                if (done) {
                    $results.text('Import complete. Imported: ' + imported + ', Skipped: ' + skipped + ', Failed: ' + failed + '.');
                    if (retryable) {
                        $retryContainer.show();
                    }
                } else {
                    setTimeout(function () {
                        processBatch(retry);
                    }, 200);
                }
            } else {
                showError(res.data || 'Error during import.');
            }
        }).fail(function () {
            showError('Unexpected error processing the import batch.');
        });
    }

    $retryButton.on('click', function (e) {
        e.preventDefault();
        $retryContainer.hide();
        $progress.text('Retrying failed rows…');
        processBatch(true);
    });
})(jQuery);