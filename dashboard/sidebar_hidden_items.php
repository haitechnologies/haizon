<?php
/**
 * Sidebar Hidden Items Management
 * View and manage hidden sidebar menu items
 */

include('admin_elements/admin_header.php');
$module = 'sidebar_hidden_items';
$module_caption = 'Sidebar Management';
$tbl_name = '';
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();

// Define all hideable items with their metadata
$hideableItems = [
    'pages' => [
        'label' => 'Static Pages',
        'description' => 'Manage static website pages and content',
        'module' => 'pages',
        'href' => 'listing_pages.php',
        'icon' => 'ph-file-doc',
        'section' => 'Content'
    ]
];

// Handle unhide action
$message = '';
$messageType = '';
if (!empty($_POST['action']) && $_POST['action'] === 'unhide' && !empty($_POST['item_key'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $itemKey = trim((string)$_POST['item_key']);
    if (isset($hideableItems[$itemKey])) {
        // Store hidden state in database via system settings
        $hiddenItemsJson = getSystemSetting('sidebar_hidden_items', '[]');
        $hiddenItems = json_decode($hiddenItemsJson, true) ?: [];
        
        // Remove from hidden list
        $hiddenItems = array_filter($hiddenItems, fn($i) => $i !== $itemKey);
        
        // Save back to settings
        $success = updateSystemSetting($mysqli, 'sidebar_hidden_items', json_encode(array_values($hiddenItems)));
        
        if ($success) {
            $message = ucfirst(str_replace('_', ' ', $itemKey)) . ' has been unhidden from the sidebar.';
            $messageType = 'success';
        } else {
            $message = 'Error updating settings. Please try again.';
            $messageType = 'error';
        }
    }
}

// Get currently hidden items from settings
$defaultHiddenItems = array_keys($hideableItems);
$hiddenItemsJson = getSystemSetting('sidebar_hidden_items', json_encode($defaultHiddenItems));
$storedHiddenItems = json_decode($hiddenItemsJson, true);
$hiddenItems = array_values(array_unique(array_merge($defaultHiddenItems, is_array($storedHiddenItems) ? $storedHiddenItems : [])));

// Group hidden items by section
$hiddenBySection = [];
foreach ($hiddenItems as $itemKey) {
    if (isset($hideableItems[$itemKey])) {
        $section = $hideableItems[$itemKey]['section'];
        if (!isset($hiddenBySection[$section])) {
            $hiddenBySection[$section] = [];
        }
        $hiddenBySection[$section][] = $itemKey;
    }
}
?>

<div class="content-wrapper">
    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h1 class="h5 mb-0 d-inline-flex align-items-center gap-2">
                    <?php echo e($module_caption); ?>
                </h1>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">
            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo e($messageType === 'success' ? 'success' : 'danger'); ?> alert-dismissible fade show" role="alert">
                    <?php echo e($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Info Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Hidden Sidebar Items</h4>
                            <p class="text-muted mb-0">
                                These menu items have been hidden from your dashboard sidebar for organizational purposes. 
                                You can unhide any item below to restore it to the sidebar navigation.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Items by Section -->
            <?php if (!empty($hiddenBySection)): ?>
                <?php foreach ($hiddenBySection as $section => $items): ?>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0"><?php echo e($section); ?> Section</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-sm mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Description</th>
                                                    <th style="width: 120px;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $itemKey): ?>
                                                    <?php $item = $hideableItems[$itemKey]; ?>
                                                    <tr>
                                                        <td>
                                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                                <i class="<?php echo e($item['icon']); ?>" style="font-size: 18px; color: #667eea;"></i>
                                                                <strong><?php echo e($item['label']); ?></strong>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <small class="text-muted"><?php echo e($item['description']); ?></small>
                                                        </td>
                                                        <td>
                                                            <form method="POST" style="display: inline;">
                                                                <?php echo csrf_field(); ?>
                                                                <input type="hidden" name="action" value="unhide">
                                                                <input type="hidden" name="item_key" value="<?php echo e($itemKey); ?>">
                                                                <button type="submit" class="btn btn-sm btn-success">
                                                                    <i class="ph-eye"></i> Unhide
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="ph-info"></i> No hidden items at this time. All sidebar menu items are visible.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Notes Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title mb-3">📝 Notes</h6>
                            <ul class="mb-0" style="font-size: 13px; color: #555;">
                                <li>Hidden items remain fully functional in the backend—they're only hidden from the sidebar.</li>
                                <li>Direct links to hidden modules will still work (e.g., <code>listing_blogs.php</code>)</li>
                                <li>Hidden items can be unhidden at any time from this page.</li>
                                <li>These settings are stored in your system configuration and persist across sessions.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <?php include('admin_elements/copyright.php'); ?>
</div>
<?php include('admin_elements/admin_footer.php'); ?>
