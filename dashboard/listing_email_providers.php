<?php

include('admin_elements/admin_header.php');

$module = 'email_providers';
$module_caption = 'Email Provider';
$tbl_name = DB::EMAIL_PROVIDERS;
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

/*
|--------------------------------------------------------------------------
| Note: Publish/Unpublish not applicable for email providers
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| DELETE
|--------------------------------------------------------------------------
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

    $result = DeletionManager::delete(
        $tbl_name,
        $id,
        $session_user_id,
        ['verify_field' => 'provider_name', 'item_label' => 'Email Provider', 'module_slug' => 'email_providers']
    );
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

/*
|--------------------------------------------------------------------------
| AGGREGATE DAILY QUOTA STATS
|--------------------------------------------------------------------------
*/
$ep_stats_result = $mysqli->query(
    "SELECT
        COALESCE(SUM(CASE WHEN daily_limit > 0 THEN daily_limit ELSE 100 END), 0) AS total_daily_limit,
        COALESCE(SUM(CASE WHEN per_hour_limit > 0 THEN per_hour_limit ELSE 50 END), 0) AS total_hourly_limit,
        COUNT(*) AS provider_count
     FROM `" . DB::EMAIL_PROVIDERS . "` WHERE is_active = 1"
);
$ep_stats = $ep_stats_result ? $ep_stats_result->fetch_assoc() : [];
$ep_total_daily_limit = (int)($ep_stats['total_daily_limit'] ?? 0);
$ep_total_hourly_limit = (int)($ep_stats['total_hourly_limit'] ?? 0);

$ep_sent_today_result = $mysqli->query(
    "SELECT COUNT(*) AS cnt FROM `" . DB::EMAIL_HISTORY . "`
     WHERE status = 'sent' AND DATE(COALESCE(sent_at, created_at)) = CURDATE()"
);
$ep_sent_today = (int)(($ep_sent_today_result ? $ep_sent_today_result->fetch_assoc() : [])['cnt'] ?? 0);
$ep_remaining_today = max(0, $ep_total_daily_limit - $ep_sent_today);
?>

<div class="content-wrapper">
    <?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
        <?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header page-header-light shadow-sm mb-2">
        <div class="page-header-content d-flex py-2">
            <div class="page-title">
                <h4><i class="ph-envelope-simple me-2"></i><span class="fw-semibold"><?php echo $module_caption; ?></span></h4>
            </div>
            <div class="my-auto ms-auto">
                <a href="listing_email_queue.php" class="btn btn-outline-info btn-labeled btn-labeled-start me-2">
                    <span class="btn-labeled-icon bg-info"><i class="ph-list-bullets"></i></span>
                    Email History
                </a>
                <?php if (granted('add', $module_id)): ?>
                    <a href="email_providers.php?action=add_<?php echo $module; ?>" class="btn btn-primary btn-labeled btn-labeled-start">
                        <span class="btn-labeled-icon bg-black bg-opacity-20"><i class="ph-plus"></i></span>
                        Add New
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- /page header -->


    <div class="content datatable-enhanced email-providers-compact">

        <style>
            .email-providers-compact .card {
                border-radius: 10px;
                box-shadow: 0 1px 8px rgba(15, 23, 42, 0.05);
            }

            .email-providers-compact .card .card-body {
                padding: 0.9rem 1rem;
            }

            .providers-limit-strip {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 10px;
                flex-wrap: wrap;
                padding: 10px 12px;
                border: 1px solid #dbeafe;
                border-radius: 10px;
                background: linear-gradient(90deg, #f8fbff 0%, #eef6ff 100%);
                margin-bottom: 10px;
            }

            .providers-limit-strip .strip-title {
                font-size: 12px;
                font-weight: 600;
                color: #0f3f77;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .providers-limit-strip .strip-badges {
                display: inline-flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .providers-limit-strip .strip-badge {
                font-size: 11px;
                line-height: 1;
                border: 1px solid #c7dcff;
                color: #1d4f91;
                background: #ffffff;
                border-radius: 999px;
                padding: 5px 8px;
                white-space: nowrap;
            }

            .providers-limit-strip .strip-help {
                font-size: 11px;
                color: #58779f;
            }

            #grid-<?php echo $module; ?> td {
                vertical-align: middle;
            }

            .provider-mini-badge {
                font-size: 10px;
                line-height: 1.1;
                padding: 2px 6px;
                border-radius: 999px;
                font-weight: 600;
                letter-spacing: .01em;
            }

            .email-provider-actions {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                flex-wrap: nowrap;
                white-space: nowrap;
            }

            .email-provider-actions .btn {
                white-space: nowrap;
                padding: 0.25rem 0.45rem;
                font-size: 12px;
                line-height: 1.15;
            }

            .email-provider-actions .action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 28px;
                height: 28px;
                border-radius: 6px;
            }

            .mini-candle-graph {
                display: inline-flex;
                align-items: flex-end;
                gap: 6px;
                height: 42px;
                width: 42px;
                padding: 3px 5px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #f8fafc;
            }
            .daily-graph-cell {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .mini-candle {
                display: inline-block;
                width: 9px;
                min-height: 8px;
                border-radius: 4px 4px 2px 2px;
                position: relative;
            }
            .mini-candle::before {
                content: '';
                position: absolute;
                left: 50%;
                top: -5px;
                transform: translateX(-50%);
                width: 1px;
                height: 5px;
                background: #94a3b8;
            }
            .mini-candle.used {
                background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
            }
            .mini-candle.remaining {
                background: linear-gradient(180deg, #22c55e 0%, #15803d 100%);
            }

            .email-usage-wrap.limit-healthy .mini-candle.used {
                background: linear-gradient(180deg, #3b82f6 0%, #2563eb 100%);
            }

            .email-usage-wrap.limit-warning .mini-candle.used {
                background: linear-gradient(180deg, #f59e0b 0%, #d97706 100%);
            }

            .email-usage-wrap.limit-critical .mini-candle.used {
                background: linear-gradient(180deg, #ef4444 0%, #b91c1c 100%);
            }

            .email-usage-wrap.limit-warning .mini-candle-graph,
            .email-usage-wrap.limit-critical .mini-candle-graph {
                border-color: #fbbf24;
                background: #fff7ed;
            }

            .email-usage-wrap.limit-critical .mini-candle-graph {
                border-color: #f87171;
                background: #fef2f2;
            }

            .usage-state {
                display: inline-block;
                margin-left: 6px;
                padding: 1px 6px;
                border-radius: 999px;
                font-size: 10px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .02em;
                background: #e2e8f0;
                color: #334155;
            }

            .email-usage-wrap.limit-warning .usage-state {
                background: #fef3c7;
                color: #92400e;
            }

            .email-usage-wrap.limit-critical .usage-state {
                background: #fee2e2;
                color: #991b1b;
            }

            .email-usage-wrap.limit-healthy .usage-state {
                background: #dbeafe;
                color: #1d4ed8;
            }
            .mini-candle-label {
                font-size: 11px;
                color: #64748b;
                margin-top: 4px;
            }
        </style>

        <?php include('admin_elements/breadcrumb.php'); ?>


        <div class="providers-limit-strip" role="note" aria-label="Email quota summary">
            <div class="strip-title">
                <i class="ph-chart-bar"></i>
                Today's Quota (<?php echo date('d M Y'); ?>)
            </div>
            <div class="strip-badges">
                <span class="strip-badge"><strong><?php echo number_format($ep_sent_today); ?></strong> Sent Today</span>
                <span class="strip-badge"><strong><?php echo number_format($ep_total_daily_limit); ?></strong> Daily Limit</span>
                <span class="strip-badge <?php echo $ep_remaining_today <= 0 ? 'text-danger fw-bold' : ''; ?>"><strong><?php echo number_format($ep_remaining_today); ?></strong> Remaining</span>
                <span class="strip-badge"><strong><?php echo number_format($ep_total_hourly_limit); ?></strong> /hour Limit</span>
            </div>
            <span class="strip-help">Titan Free Plan: 2000/day per domain, 100/day per mailbox.</span>
        </div>

        <div class="card">

            <div class="card-body">
                <table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
                    <thead>
                        <tr>
                            <th width="200">PROVIDER NAME</th>
                            <th width="200">EMAIL ADDRESS</th>
                            <th>SMTP CONFIGURATION</th>
                            <th width="220">DAILY USAGE</th>
                            <th width="90">USAGE GRAPH</th>
                            <th width="210">ACTION</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <!--
==============================
 Titan SMTP Reference Links
==============================
-->
        <div class="accordion mt-3 mb-3" id="smtpReferenceAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header" id="smtpReferenceHeading">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#smtpReferenceCollapse" aria-expanded="false" aria-controls="smtpReferenceCollapse">
                        <strong>Titan SMTP Setup Reference</strong>
                    </button>
                </h2>
                <div id="smtpReferenceCollapse" class="accordion-collapse collapse" aria-labelledby="smtpReferenceHeading" data-bs-parent="#smtpReferenceAccordion">
                    <div class="accordion-body py-2">
                        <ul class="mb-2">
                            <li><a href="https://support.titan.email/hc/en-us/articles/900001846283-Send-email-using-WP-Mail-SMTP-plugin" target="_blank" rel="noopener">Send email using WP Mail SMTP plugin (Titan Support)</a></li>
                            <li><a href="https://support.titan.email/hc/en-us/articles/900000215446-Configure-Titan-on-other-apps-using-IMAP-POP" target="_blank" rel="noopener">Configure Titan on other apps using IMAP/POP (Titan Support)</a></li>
                        </ul>
                        <div class="small text-muted">For best results, always use port <strong>465</strong> (SSL) for SMTP with Titan.</div>
                    </div>
                </div>
            </div>
        </div>

        
    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
    $(document).ready(function() {
        var tableSelector = '#grid-<?php echo $module; ?>';

        window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
            responsive: true,
            pageLength: 10,
            language: {
                searchPlaceholder: 'Provider name, code...',
                sLengthMenu: 'Show _MENU_'
            },
            searchHighlight: true,
            order: [
                [0, 'desc']
            ],
            stateSave: false,
            deferRender: true,
            retrieve: false,
            dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            ajax: {
                url: 'datatables.php',
                type: 'POST',
                data: function(d) {
                    d.action = '<?php echo $action; ?>';
                    d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                    d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                    d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                    return d;
                },
                error: function() {
                    $('.grid-error').html('');
                    $(tableSelector).append('<tbody class="grid-error"><tr><th colspan="6">No Results Found.</th></tr></tbody>');
                    $(tableSelector + '_processing').css('display', 'none');
                }
            },
            columns: [{
                    data: 'provider_name'
                },
                {
                    data: 'email'
                },
                {
                    data: 'smtp_details'
                },
                {
                    data: 'daily_usage',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'daily_usage_graph',
                    orderable: false,
                    searchable: false,
                    className: 'daily-graph-cell'
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row, meta) {
                        var id = row.actions.match(/id=(\d+)/) ? RegExp.$1 : '';
                        var url = 'email_test.php?provider_id=' + encodeURIComponent(id);
                        var btn = '';
                        btn += '<div class="email-provider-actions">';
                        btn += '<a href="' + url + '" class="btn btn-sm btn-outline-primary" title="Send Test Email">';
                        btn += '<i class="ph-paper-plane-tilt me-1"></i>Test Email</a> ';
                        btn += row.actions;
                        btn += '</div>';
                        return btn;
                    }
                }
            ],
            order: [
                [0, 'desc']
            ]
        });


    });

    // No test email modal or popup. Use the standalone page: email_test.php
</script>




<?php include('admin_elements/admin_footer.php'); ?>