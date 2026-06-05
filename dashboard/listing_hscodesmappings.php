<?php

use App\Core\DB;
include('admin_elements/admin_header.php');

$module = 'category_hs_mappings';
$module_caption = 'HS Code Mappings';
$error_message = '';
$success_message = '';

/*
|--------------------------------------------------------------------------
| PERMISSIONS CHECK
|--------------------------------------------------------------------------
*/
include('admin_elements/permissions.php');

$activeOrganizationId = dashboardRequireActiveOrganization();
$hide_add_button = true;

/*
|--------------------------------------------------------------------------
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
| Validate CSRF token for all POST requests
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in listing_hscodesmappings.php', 'WARNING', __FILE__, __LINE__);
    }
}

/*
|--------------------------------------------------------------------------
| ACTIONS: LINK/UNLINK HS CODES (HARMONIZED SYSTEM)
|--------------------------------------------------------------------------
*/

// Handle linking HS code to category
if ($action == 'link_hs_code' && !empty($_POST['category_id']) && !empty($_POST['hs_code_id'])) {
    if (!granted('edit', $module_id)) {
        $error_message = "You don't have permission to edit HS code mappings.";
    } else {
        $category_id = (int)$_POST['category_id'];
        $hs_code_id = (int)$_POST['hs_code_id'];
        $relevance = !empty($_POST['relevance']) ? (int)$_POST['relevance'] : 1;
        $notes = !empty($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        
        $query = "INSERT INTO " . DB::CATEGORY_HS_CODES . " 
                  (category_id, hs_code_id, relevance, notes) 
                  VALUES ({$category_id}, {$hs_code_id}, {$relevance}, '{$notes}')
                  ON DUPLICATE KEY UPDATE 
                    relevance = {$relevance},
                    notes = '{$notes}',
                    updated_at = NOW()";
        
        if ($conn->query($query)) {
            $success_message = "HS Code linked to category successfully!";
        } else {
            $error_message = "Error linking HS Code: " . $conn->error;
        }
    }
}

// Handle unlinking HS code from category
if ($action == 'unlink_hs_code' && !empty($id)) {
    if (!granted('delete', $module_id)) {
        $error_message = "You don't have permission to delete HS code mappings.";
    } else {
        $query = "DELETE FROM " . DB::CATEGORY_HS_CODES . " WHERE id = " . (int)$id;
        if ($conn->query($query)) {
            $success_message = "HS Code unlinked from category successfully!";
        } else {
            $error_message = "Error unlinking HS Code: " . $conn->error;
        }
    }
}

/*
|--------------------------------------------------------------------------
| PAGE INITIALIZATION
|--------------------------------------------------------------------------
*/

// Get selected category (if any)
$selected_category_id = !empty($_GET['cat']) ? (int)$_GET['cat'] : 0;

// Get all categories
$categories = [];
$cat_query = "SELECT id, name FROM " . DB::CATEGORIES . " ORDER BY name ASC LIMIT 100";
$cat_result = $conn->query($cat_query);
if ($cat_result) {
    $categories = $cat_result->fetch_all(MYSQLI_ASSOC);
}

// Get HS Codes (Harmonized System) linked to selected category
$linked_hscodes = [];
if ($selected_category_id > 0) {
    $query = "SELECT chc.id, h.code, h.id as hs_code_id, t.long_desc, t.short_desc, chc.relevance, chc.notes
              FROM " . DB::CATEGORY_HS_CODES . " chc
              INNER JOIN " . DB::HS_CODES . " h ON chc.hs_code_id = h.id
              LEFT JOIN " . DB::HS_CODE_TEXTS . " t ON h.id = t.hs_code_id AND t.lang = 'en'
              WHERE chc.category_id = {$selected_category_id}
              ORDER BY chc.relevance ASC, h.code ASC";
    
    $result = $conn->query($query);
    if ($result) {
        $linked_hscodes = $result->fetch_all(MYSQLI_ASSOC);
    }
}

?>

<div class="content-wrapper">

    <!-- Page header -->
    <?php include('admin_elements/page_header.php'); ?>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content datatable-enhanced">

            <?php include('admin_elements/breadcrumb.php'); ?>
<div class="row">
                <!-- Category Selector -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <span class="fw-semibold"><i class="fa fa-list"></i> Select Category</span>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="mb-3">
                                    <label class="form-label">Category:</label>
                                    <select class="form-select" name="cat" onchange="this.form.submit();">
                                        <option value="">-- Select a Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $selected_category_id == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo e($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Add New HS Codes (Harmonized System) Mapping -->
                    <?php if ($selected_category_id > 0 && granted('edit', $module_id)): ?>
                    <div class="card mt-3">
                        <div class="card-header">
                            <span class="fw-semibold"><i class="fa fa-plus"></i> Link HS Code</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="link_hs_code">
                                <input type="hidden" name="category_id" value="<?php echo $selected_category_id; ?>">
                                <?php echo csrf_field(); ?>

                                <div class="mb-3">
                                    <label class="form-label">HS Code:</label>
                                    <select class="form-select" name="hs_code_id" required>
                                        <option value="">-- Select HS Code --</option>
                                        <?php
                                        $hs_query = "SELECT h.id, h.code, t.long_desc
                                                    FROM " . DB::HS_CODES . " h
                                                    LEFT JOIN " . DB::HS_CODE_TEXTS . " t 
                                                      ON h.id = t.hs_code_id AND t.lang = 'en'
                                                    WHERE h.id NOT IN (
                                                        SELECT hs_code_id FROM " . DB::CATEGORY_HS_CODES . "
                                                        WHERE category_id = {$selected_category_id}
                                                    )
                                                    ORDER BY h.code ASC LIMIT 50";
                                        
                                        $hs_result = $conn->query($hs_query);
                                        if ($hs_result) {
                                            while ($hs = $hs_result->fetch_assoc()) {
                                                echo '<option value="' . $hs['id'] . '">' . 
                                                     e($hs['code'] . ' - ' . ($hs['long_desc'] ?? 'N/A')) . 
                                                     '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Relevance:</label>
                                    <select class="form-select" name="relevance">
                                        <option value="1">Primary</option>
                                        <option value="2">Secondary</option>
                                        <option value="3">Related</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notes (optional):</label>
                                    <textarea class="form-control" name="notes" rows="2" placeholder="Add notes..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                    <i class="fa fa-link"></i> Link HS Code
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                    <!-- Linked HS Codes (Harmonized System) -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <span class="fw-semibold">
                                <i class="fa fa-cubes"></i> 
                                Linked HS Codes (Harmonized System)
                                <?php if ($selected_category_id > 0): ?>
                                    <span class="badge bg-info"><?php echo count($linked_hscodes); ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if ($selected_category_id == 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> Select a category from the left panel to view and manage HS code mappings.
                                </div>
                            <?php elseif (empty($linked_hscodes)): ?>
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> No HS Codes (Harmonized System) linked to this category yet.
                                </div>
                            <?php else: ?>
                                <div class="table datatable-professional-responsive">
                                    <table class="table datatable-professional table-hover table-sm">
                                        <thead>
                                            <tr>
                                                <th width="80">HS Code</th>
                                                <th>Description</th>
                                                <th width="100">Relevance</th>
                                                <th>Notes</th>
                                                <th width="80">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($linked_hscodes as $hs): ?>
                                            <tr>
                                                <td><strong><?php echo e($hs['code']); ?></strong></td>
                                                <td><?php echo e(truncateText($hs['long_desc'] ?? $hs['short_desc'] ?? 'N/A', 60)); ?></td>
                                                <td>
                                                    <?php
                                                    $relevance_labels = [1 => 'Primary', 2 => 'Secondary', 3 => 'Related'];
                                                    $relevance_badge = [1 => 'badge-success', 2 => 'badge-warning', 3 => 'badge-secondary'];
                                                    $label = $relevance_labels[$hs['relevance']] ?? 'Unknown';
                                                    echo '<span class="badge ' . $relevance_badge[$hs['relevance']] . '">' . $label . '</span>';
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if (!empty($hs['notes'])) {
                                                        echo '<small class="text-muted">' . e(truncateText($hs['notes'], 40)) . '</small>';
                                                    } else {
                                                        echo '<span class="text-muted">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (granted('delete', $module_id)): ?>
                                                        <a href="?action=unlink_hs_code&id=<?php echo $hs['id']; ?>&cat=<?php echo $selected_category_id; ?>" 
                                                           class="btn btn-sm btn-danger" onclick="return confirm('Unlink this HS code?');">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <?php include('admin_elements/copyright.php'); ?>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>



