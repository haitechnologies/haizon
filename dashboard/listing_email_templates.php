<?php

include('admin_elements/admin_header.php');

$module = 'email_templates';
$module_caption = 'Email Template';
$module_id = getModuleIdBySlug($module, $mysqli);
$tbl_name = DB::EMAIL_TEMPLATES;  // Email templates table
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
| DELETE
|--------------------------------------------------------------------------
*/
if (($action == "delete_$module" && !empty($id)) && granted('delete', $module_id)) {

	$result = DeletionManager::delete(
		$tbl_name,
		$id,
		$session_user_id,
		['verify_field' => 'template_name', 'item_label' => 'Email Template', 'module_slug' => 'email_templates']
	);
	if ($result['success']) {
		$success_message = $result['message'];
	} else {
		$error_message = $result['message'];
	}
}

/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/
?>

<div class="content-wrapper">
	<?php if (!empty($visibleEmailLinks) && $isEmailRelatedPage && function_exists('renderEmailQuickbar')): ?>
		<?php renderEmailQuickbar($visibleEmailLinks, $current_page); ?>
	<?php endif; ?>

	<!-- Page header -->
	<?php include('admin_elements/page_header.php'); ?>
	<!-- /page header -->

    <div class="content datatable-enhanced">

		<?php include('admin_elements/breadcrumb.php'); ?>

        <div style="display:none;">
            <?php echo csrf_field(); ?>
        </div>

        <div class="card">

			<div class="card-body">
				<table id="grid-<?php echo $module; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover" width="100%">
					<thead>
						<tr>
							<th width="60">ID</th>
							<th>NAME</th>
							<th>SUBJECT</th>
							<th>DEFAULT</th>
							<th>SYSTEM</th>
							<th>CREATED</th>
							<th width="120">ACTION</th>
						</tr>
					</thead>
				</table>
			</div>
        </div>

    </div>

    <!-- Email Template Preview Modal -->
    <div class="modal fade" id="emailTemplatePreviewModal" tabindex="-1" role="dialog" aria-labelledby="emailTemplatePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailTemplatePreviewModalLabel">
                        Email Template Preview
                    </h5>
                    <span id="templatePreviewDefault"></span>
                    <span id="templatePreviewSystem"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><strong>Template Name:</strong></label>
                        <div id="templatePreviewName" class="form-control-plaintext"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Subject:</strong></label>
                        <div id="templatePreviewSubject" class="form-control-plaintext text-monospace"></div>
                    </div>
                    
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="html-tab" data-bs-toggle="tab" data-bs-target="#htmlPreview" type="button" role="tab" aria-controls="htmlPreview" aria-selected="true">
                                HTML Preview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="text-tab" data-bs-toggle="tab" data-bs-target="#textPreview" type="button" role="tab" aria-controls="textPreview" aria-selected="false">
                                Text Version
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-2">
                        <div class="tab-pane fade show active" id="htmlPreview" role="tabpanel" aria-labelledby="html-tab">
                            <iframe id="templatepreviewIframe" style="width:100%; height:400px; border:1px solid #ddd; border-radius:4px;"></iframe>
                        </div>
                        <div class="tab-pane fade" id="textPreview" role="tabpanel" aria-labelledby="text-tab">
                            <pre id="templatePreviewText" style="background:#f5f5f5; padding:15px; border-radius:4px; max-height:400px; overflow-y:auto;"></pre>
                        </div>
                    </div>
                    
                    <div class="mt-3 text-muted small">
                        <strong>Created:</strong> <span id="templatePreviewCreated"></span> | 
                        <strong>Updated:</strong> <span id="templatePreviewUpdated"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    function decodeHtmlEntities(value) {
        if (!value || typeof value !== 'string') {
            return '';
        }
        var txt = document.createElement('textarea');
        txt.innerHTML = value;
        return txt.value;
    }

    function normalizePreviewHtml(value) {
        if (!value || typeof value !== 'string') {
            return '';
        }

        // If content looks entity-encoded and has no real tags, decode it for preview.
        if (value.indexOf('<') === -1 && /&lt;|&gt;|&amp;[a-zA-Z0-9#]+;/.test(value)) {
            return decodeHtmlEntities(value);
        }

        return value;
    }

    var tableSelector = '#grid-<?php echo $module; ?>';

    window.HAIDatatableInitializer.init(tableSelector, '<?php echo $module; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.action = '<?php echo $action; ?>';
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                d.session_user_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['user_id'] ?? ''; ?>';
                d.dt_session_role_id = '<?php echo $_SESSION[$project_pre]['DASHBOARD']['role_id'] ?? ''; ?>';
                return d;
            },
            error: function(xhr, status, error) {
                console.error('[<?php echo ucfirst($module); ?>] DataTable AJAX error:', error);
                console.error('[<?php echo ucfirst($module); ?>] Response:', xhr.responseText);
            }
        },
        columns: [
            { data: 0 },
            { data: 1 },
            { data: 2 },
            { data: 3 },
            { data: 4 },
            { data: 5 },
            { data: 6, orderable: false, searchable: false }
        ],
        columnDefs: [
            { targets: [0, 3, 4, 6], className: 'col-center' },
            { targets: 6, orderable: false }
        ],
        order: [[5, 'desc']],
        language: {
            search: "",
            searchPlaceholder: "Search email templates...",
            lengthMenu: "_MENU_"
        }
    });

    // ========================================
    // Delete Record Handler
    // ========================================
    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var module = $(this).data('module');
        
        if (confirm('Are you sure you want to delete this template?')) {
            // Create form and submit
            var form = $('<form>', {
                'method': 'POST',
                'action': 'listing_<?php echo $module; ?>.php'
            }).append($('<input>', {
                'type': 'hidden',
                'name': 'action',
                'value': 'delete_' + module
            })).append($('<input>', {
                'type': 'hidden',
                'name': 'id',
                'value': id
            }));
            
            $('body').append(form);
            form.submit();
        }
    });

    // View Template Handler
    $(document).on('click', '[data-action="view_template"]', function(e) {
        e.preventDefault();
        
        var id = $(this).data('id');
        var csrfToken = $('input[name="csrf_token"]').val();
        
        $.ajax({
            url: 'ajax_email_template_preview.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: id,
                csrf_token: csrfToken
            },
            success: function(response) {
                if (response.success && response.data) {
                    var data = response.data;
                    
                    // Populate modal fields
                    $('#templatePreviewName').text(data.name);
                    $('#templatePreviewSubject').text(data.subject_default);
                    $('#templatePreviewCreated').text(data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A');
                    $('#templatePreviewUpdated').text(data.updated_at ? new Date(data.updated_at).toLocaleString() : 'N/A');
                    
                    // HTML Preview
                    var previewHtml = normalizePreviewHtml(data.html_body || '');
                    var iframe = document.getElementById('templatepreviewIframe');
                    if (iframe) {
                        if ('srcdoc' in iframe) {
                            iframe.srcdoc = previewHtml || '<p>No HTML content</p>';
                        } else {
                            var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                            iframeDoc.open();
                            iframeDoc.write(previewHtml || '<p>No HTML content</p>');
                            iframeDoc.close();
                        }
                    }
                    
                    // Text Preview
                    $('#templatePreviewText').text(data.text_body || 'No text content');
                    
                    // Badges
                    var defaultBadge = data.is_default ? '<span class="badge bg-success ms-2">Default</span>' : '';
                    var systemBadge = data.is_system ? '<span class="badge bg-info ms-2">System</span>' : '';
                    
                    $('#templatePreviewDefault').html(defaultBadge);
                    $('#templatePreviewSystem').html(systemBadge);
                    
                    // Show modal
                    var modal = new bootstrap.Modal(document.getElementById('emailTemplatePreviewModal'));
                    modal.show();
                } else {
                    alert('Error: ' + (response.error || 'Unable to load template'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Template preview error:', error);
                alert('Error loading template preview. See console for details.');
            }
        });
    });

});
</script>
<?php include('admin_elements/admin_footer.php'); ?>


