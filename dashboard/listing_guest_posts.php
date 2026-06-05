<?php
include('admin_elements/admin_header.php');

$module = 'blogs';
$datatableModule = 'guest_posts';
$module_caption = 'Guest Posts';
$tbl_name = DB::BLOGS;
$error_message = '';
$success_message = '';
$statusFilter = strtolower(trim((string)($_GET['status'] ?? 'pending')));

if (!in_array($statusFilter, ['pending', 'approved', 'rejected', 'all'], true)) {
    $statusFilter = 'pending';
}

include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

if ($action === 'delete_blogs' && !empty($id) && granted('delete', $module_id)) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token.';
    } else {
        $result = DeletionManager::delete(
            DB::BLOGS,
            $id,
            $session_user_id,
            [
                'verify_field' => 'title',
                'item_label' => 'Guest Post',
                'module_slug' => 'blogs'
            ]
        );

        if ($result['success']) {
            $success_message = $result['message'];
            header('Location: listing_guest_posts.php?status=' . urlencode($statusFilter) . '&msg=deleted');
            exit;
        }

        $error_message = $result['message'];
    }
}

$statusLinks = [
    'pending' => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'all' => 'All'
];
?>

<div class="content-wrapper">
    <?php include('admin_elements/messages.php'); ?>
    <?php include('admin_elements/page_header.php'); ?>

    <div class="content datatable-enhanced">
        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card mb-3">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small">Filter:</span>
                <?php foreach ($statusLinks as $key => $label): ?>
                    <a href="listing_guest_posts.php?status=<?php echo urlencode($key); ?>" class="btn btn-sm <?php echo $statusFilter === $key ? 'btn-primary' : 'btn-light'; ?>">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
                <span class="text-muted small ms-auto">Guest posts are content-only submissions and require admin review before publishing.</span>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="grid-<?php echo $datatableModule; ?>" class="custom_datatables datatable-professional display responsive no-wrap table-hover order-column" width="100%">
                    <thead>
                        <tr>
                            <th width="50" class="col-center">ID</th>
                            <th>TITLE</th>
                            <th width="150">CATEGORY</th>
                            <th width="140">AUTHOR</th>
                            <th width="70" class="col-center">VIEWS</th>
                            <th width="110">PUBLISHED</th>
                            <th width="110">UPDATED</th>
                            <th width="160" class="col-center">STATUS</th>
                            <th width="90" class="col-center">ACTIONS</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>

<script>
$(document).ready(function() {
    window.HAIDatatableInitializer.init('#grid-<?php echo $datatableModule; ?>', '<?php echo $datatableModule; ?>', {
        stateSave: false,
        deferRender: true,
        retrieve: false,
        ajax: {
            url: 'datatables.php',
            type: 'POST',
            data: function(d) {
                d.csrf_token = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
                d.action = d.action || d.ajax_action;
                d.source_filter = 'guest';
                d.status_filter = '<?php echo $statusFilter === 'all' ? '' : htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8'); ?>';
                d.edit_permission = <?php echo granted('edit', $module_id) ? '1' : '0'; ?>;
                d.delete_permission = <?php echo granted('delete', $module_id) ? '1' : '0'; ?>;
                return d;
            }
        },
        columns: [
            { data: 0, width: '50px' },
            { data: 1 },
            { data: 2, width: '150px' },
            { data: 3, width: '140px' },
            { data: 4, width: '70px' },
            { data: 5, width: '110px' },
            { data: 6, width: '110px' },
            { data: 7, width: '160px' },
            { data: 8, orderable: false, searchable: false, width: '90px' }
        ],
        columnDefs: [
            { targets: [0, 4, 7, 8], className: 'col-center' },
            { targets: 8, orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'dt-header'<'dt-head-left'fl><'dt-head-right'>>rt<'dt-footer'<'dt-foot-left'i><'dt-foot-right'p>>",
        language: {
            search: '',
            searchPlaceholder: 'Search guest posts...',
            lengthMenu: '_MENU_'
        }
    });

    $(document).on('click', '[data-action="delete_record"]', function(e) {
        e.preventDefault();

        var id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this guest post?')) {
            return;
        }

        var csrfToken = window.HAI_CSRF_TOKEN || $('input[name="csrf_token"]').first().val() || '';
        var form = $('<form>', {
            method: 'POST',
            action: 'listing_guest_posts.php?status=<?php echo urlencode($statusFilter); ?>'
        }).append($('<input>', {
            type: 'hidden',
            name: 'action',
            value: 'delete_blogs'
        })).append($('<input>', {
            type: 'hidden',
            name: 'id',
            value: id
        })).append($('<input>', {
            type: 'hidden',
            name: 'csrf_token',
            value: csrfToken
        }));

        $('body').append(form);
        form.submit();
    });
});
</script>

<?php include('admin_elements/admin_footer.php'); ?>
