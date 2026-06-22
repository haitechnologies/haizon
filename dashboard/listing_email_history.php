<?php

declare(strict_types=1);

use App\Core\DB;
use App\Core\Session;

include('admin_elements/admin_header.php');

$module = 'email_history';
$module_caption = 'Email History';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::EMAIL_HISTORY;
$error_message = '';
$success_message = '';
$hide_add_button = true;

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

$detailsModalHtml = '
<div class="modal fade" id="emailHistoryDetailsModal" tabindex="-1" aria-labelledby="emailHistoryDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailHistoryDetailsModalLabel">Email History Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="email-history-details-loading" class="text-muted">Loading details...</div>
                <div id="email-history-details-content" class="d-none">
                    <div class="row g-3 small">
                        <div class="col-md-3"><strong>ID:</strong> <span id="ehd-id">-</span></div>
                        <div class="col-md-3"><strong>Status:</strong> <span id="ehd-status">-</span></div>
                        <div class="col-md-3"><strong>Source:</strong> <span id="ehd-source">-</span></div>
                        <div class="col-md-3"><strong>Recipient:</strong> <span id="ehd-recipient">-</span></div>
                        <div class="col-md-3"><strong>Company ID:</strong> <span id="ehd-company-id">-</span></div>
                        <div class="col-md-3"><strong>Campaign ID:</strong> <span id="ehd-campaign-id">-</span></div>
                        <div class="col-md-3"><strong>Campaign Name:</strong> <span id="ehd-campaign-name">-</span></div>
                        <div class="col-md-3"><strong>Provider ID:</strong> <span id="ehd-provider-id">-</span></div>
                        <div class="col-md-3"><strong>Provider:</strong> <span id="ehd-provider">-</span></div>
                        <div class="col-md-3"><strong>User ID:</strong> <span id="ehd-user-id">-</span></div>
                        <div class="col-md-3"><strong>User Name:</strong> <span id="ehd-user-name">-</span></div>
                        <div class="col-md-3"><strong>User Email:</strong> <span id="ehd-user-email">-</span></div>
                        <div class="col-md-3"><strong>Provider Email:</strong> <span id="ehd-provider-email">-</span></div>
                        <div class="col-md-3"><strong>Sent At:</strong> <span id="ehd-sent-at">-</span></div>
                        <div class="col-md-3"><strong>Delivered At:</strong> <span id="ehd-delivered-at">-</span></div>
                        <div class="col-md-3"><strong>Opened At:</strong> <span id="ehd-opened-at">-</span></div>
                        <div class="col-md-3"><strong>Clicked At:</strong> <span id="ehd-clicked-at">-</span></div>
                        <div class="col-md-6"><strong>Message ID:</strong> <span id="ehd-message-id">-</span></div>
                        <div class="col-md-6"><strong>Tracking ID:</strong> <span id="ehd-tracking-id">-</span></div>
                        <div class="col-md-6"><strong>From Name:</strong> <span id="ehd-from-name">-</span></div>
                        <div class="col-md-6"><strong>From Email:</strong> <span id="ehd-from-email">-</span></div>
                        <div class="col-md-12"><strong>Subject:</strong> <div id="ehd-subject" class="border rounded p-2 bg-light">-</div></div>
                        <div class="col-md-12"><strong>Error Message:</strong> <div id="ehd-error-message" class="border rounded p-2 bg-light text-danger">-</div></div>
                        <div class="col-md-12"><strong>Body:</strong> <pre id="ehd-body" class="border rounded p-2 bg-light" style="max-height: 320px; white-space: pre-wrap; word-break: break-word;">-</pre></div>
                        <div class="col-md-6"><strong>Created At:</strong> <span id="ehd-created-at">-</span></div>
                        <div class="col-md-6"><strong>Updated At:</strong> <span id="ehd-updated-at">-</span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>';

$listingConfig = [
    'module' => $module,
    'module_caption' => $module_caption,
    'hide_add_button' => true,
    'thead' => '
        <th width="60">ID</th>
        <th>Campaign</th>
        <th width="100">Source</th>
        <th>Recipient Email</th>
        <th>Status</th>
        <th>Sent</th>
        <th width="75">Opened</th>
        <th width="75">Clicked</th>
        <th width="90">Created At</th>
    ',
    'columns' => [
        ['data' => 0],
        ['data' => 1],
        ['data' => 2],
        ['data' => 3],
        ['data' => 4],
        ['data' => 5],
        ['data' => 6],
        ['data' => 7],
        ['data' => 8],
    ],
    'order' => [[0, 'desc']],
    'page_length' => 10,
    'extra_header' => (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar'))
        ? renderEmailQuickbar($visibleEmailLinks, $current_page) : '',
    'extra_js' => "
        var tableSelector = '#grid-{$module}';
        var detailsModalEl = document.getElementById('emailHistoryDetailsModal');
        var detailsModal = detailsModalEl ? new bootstrap.Modal(detailsModalEl) : null;

        function safeText(value) {
            if (value === null || value === undefined || value === '') return '-';
            return String(value);
        }

        function setDetailsValue(selector, value) { $(selector).text(safeText(value)); }
        function setDetailsHtml(selector, value) { $(selector).text(safeText(value)); }

        function resetDetailsModal(loadingText) {
            $('#email-history-details-loading').text(loadingText || 'Loading details...').removeClass('d-none');
            $('#email-history-details-content').addClass('d-none');
            var fields = ['ehd-id','ehd-status','ehd-source','ehd-recipient','ehd-company-id','ehd-campaign-id',
                'ehd-campaign-name','ehd-provider-id','ehd-provider','ehd-user-id','ehd-user-name','ehd-user-email',
                'ehd-provider-email','ehd-sent-at','ehd-delivered-at','ehd-opened-at','ehd-clicked-at',
                'ehd-message-id','ehd-tracking-id','ehd-from-name','ehd-from-email','ehd-created-at','ehd-updated-at'];
            fields.forEach(function(f) { setDetailsValue('#' + f, '-'); });
            ['ehd-subject','ehd-error-message','ehd-body'].forEach(function(f) { setDetailsHtml('#' + f, '-'); });
        }

        function fillDetailsModal(data) {
            setDetailsValue('#ehd-id', data.id);
            setDetailsValue('#ehd-status', data.status);
            setDetailsValue('#ehd-source', data.source);
            setDetailsValue('#ehd-recipient', data.recipient_email);
            setDetailsValue('#ehd-company-id', data.company_id);
            setDetailsValue('#ehd-campaign-id', data.campaign_id);
            setDetailsValue('#ehd-campaign-name', data.campaign_name);
            setDetailsValue('#ehd-provider-id', data.provider_id);
            setDetailsValue('#ehd-provider', data.provider_name);
            setDetailsValue('#ehd-user-id', data.user_id);
            setDetailsValue('#ehd-user-name', data.user_name);
            setDetailsValue('#ehd-user-email', data.user_email);
            setDetailsValue('#ehd-provider-email', data.provider_email);
            setDetailsValue('#ehd-sent-at', data.sent_at);
            setDetailsValue('#ehd-delivered-at', data.delivered_at);
            setDetailsValue('#ehd-opened-at', data.opened_at);
            setDetailsValue('#ehd-clicked-at', data.clicked_at);
            setDetailsValue('#ehd-message-id', data.message_id);
            setDetailsValue('#ehd-tracking-id', data.tracking_id);
            setDetailsValue('#ehd-from-name', data.from_name);
            setDetailsValue('#ehd-from-email', data.from_email);
            setDetailsHtml('#ehd-subject', data.subject);
            setDetailsHtml('#ehd-error-message', data.error_message);
            setDetailsHtml('#ehd-body', data.body);
            setDetailsValue('#ehd-created-at', data.created_at);
            setDetailsValue('#ehd-updated-at', data.updated_at);
        }

        $(tableSelector + ' tbody').on('click', 'tr', function(event) {
            if ($(event.target).closest('a, button, .dropdown, .dropdown-menu').length > 0) return;
            var dt = $(tableSelector).DataTable();
            var rowData = dt.row(this).data();
            if (!rowData || typeof rowData[0] === 'undefined') return;
            var rawId = $('<div>').html(String(rowData[0])).text();
            var recordId = parseInt(rawId, 10);
            if (!recordId || Number.isNaN(recordId)) return;
            if (!detailsModal) return;

            resetDetailsModal('Loading details for #' + recordId + '...');
            detailsModal.show();

            $.ajax({
                url: 'ajax_email_history_details.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: recordId,
                    csrf_token: window.HAI_CSRF_TOKEN || $('input[name=\"csrf_token\"]').first().val() || ''
                }
            }).done(function(response) {
                if (!response || !response.success || !response.data) {
                    resetDetailsModal((response && response.error) ? response.error : 'Failed to load details.');
                    return;
                }
                fillDetailsModal(response.data);
                $('#email-history-details-loading').addClass('d-none');
                $('#email-history-details-content').removeClass('d-none');
            }).fail(function(xhr) {
                var msg = 'Unable to load details.';
                if (xhr && xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
                resetDetailsModal(msg);
            });
        });
    ",
];

include('admin_elements/listing_template.php');

echo $detailsModalHtml;

include('admin_elements/admin_footer.php');
