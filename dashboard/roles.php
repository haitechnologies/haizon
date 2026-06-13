<?php

use App\Core\DB;
use App\Security\Roles;
include('admin_elements/admin_header.php');
Roles::requireAdminAccess();

$module             = 'roles';
$module_caption     = 'Roles & Permissions';
$tbl_name             = $tbl_prefix . $module;
$error_message         = '';
$success_message     = '';

/*
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
|--------------------------------------------------------------------------|
*/

if (isset($_POST['is_active']))       $is_active     = 1;
else $is_active = 0;


/*
|--------------------------------------------------------------------------
| 	GET ALL VARIABLES ADD/UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" || $action == "add_$module") {
    // CSRF validation
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed in roles.php', 'WARNING', __FILE__, __LINE__);
    } else {
    $role_name          = e_s__($_POST['role_name']);
    $description        = e_s__($_POST['description']);
    }
} else {
    $description        = '';
    $role_name          = '';
}




// --------------------------------------------------------------------------
// -------------------- POPULATE PERMISSIONS --------------------------------
// --------------------------------------------------------------------------
$permissions_arr = array();


$result = $mysqli->query("SELECT * FROM `" . DB::MODULES . "` ORDER BY module_name");
while ($row = $result->fetch_array()) {

    $module_id         = $row['id'];
    $module_slug     = $row['slug'];

    if (isset($_POST[$module_slug])) {
        $permissions     = $_POST[$module_slug];
        foreach ($permissions as $key => $value) {
            // array_push($permissions_arr,        e_s__($_POST['permission'][$key]));
            array_push($permissions_arr,        e_s__($value));
        }
    }
} // while


/*
|--------------------------------------------------------------------------
| 	UPDATE
|--------------------------------------------------------------------------
|
*/
if ($action == "update_$module" && !empty($id)) {


    if (empty($role_name)) {
        $error_message = 'Role Name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'role_name', $role_name) && $role_name != getTableAttr('role_name', $tbl_name, $id)) {
        $error_message = 'Duplicate Role Name. Please enter different.';
    } else {

        $update_row = $mysqli->query("
											UPDATE `$tbl_name` SET
												role_name 			= '" . $role_name . "',
												description 		= '" . $description . "',
												is_active 			= '" . $publish . "'
											WHERE id=$id");
        if ($update_row) {
            $success_message = "The $module_caption has been updated successfully.";
            fp__($tbl_name, $id);

            // Delete old permissions using prepared statement
            $stmt = $mysqli->prepare("DELETE FROM " . DB::PERMISSIONS . " WHERE role_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // ---------------------------------------------------------------------------------
            // ------------------------- GRANTED PERMISSIONS -----------------------------------
            // ---------------------------------------------------------------------------------
            $result = $mysqli->query("SELECT * FROM `" . DB::MODULES . "` ORDER BY module_name");
            while ($row = $result->fetch_array()) {

                $module_id         = $row['id'];
                $module_slug        = $row['slug'];

                if (isset($_POST[$module_slug])) {

                    $permissions     = $_POST[$module_slug];

                    foreach ($permissions as $key => $value) {

                        $granted_permission = intval(e_s__($value)); // Sanitize to integer
                        
                        // Use prepared statement to prevent SQL injection
                        $stmt = $mysqli->prepare("INSERT INTO " . DB::PERMISSIONS . " (role_id, permission_id, module_id) VALUES (?, ?, ?)");
                        $stmt->bind_param('iii', $id, $granted_permission, $module_id);
                        $stmt->execute();
                        $insert_id = $stmt->insert_id;
                        $stmt->close();
                        
                        fp__($tbl_name, $insert_id);
                    }
                }
            } // while

            // ---------------------------------------------------------------------------------
            // header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be updated. Please try again.";
            //header("Location:$module.php?action=edit_$module&id=$id&error_message=$error_message");
        }
    }

    /*
|--------------------------------------------------------------------------
| 	ADD
|--------------------------------------------------------------------------
|
*/
} else if ($action == "add_$module") {

    if (empty($role_name)) {
        $error_message = 'Role name is mandatory.';
    } else if (checkDuplicateRow($tbl_name, 'role_name', $role_name)) {
        $error_message = 'Duplicate Role Name. Please enter different.';
    } else {

        $insert_row = $mysqli->query("INSERT INTO `$tbl_name`(role_name, description, is_active) VALUES ('" . $role_name . "', '" . $description . "', '" . $is_active . "'); ");

        if ($insert_row) {
            $id = $mysqli->insert_id;
            fp__($tbl_name, $id);

            // ---------------------------------------------------------------------------------
            // ------------------------- GRANTED PERMISSIONS -----------------------------------
            // ---------------------------------------------------------------------------------
            $result = $mysqli->query("SELECT * FROM `" . DB::MODULES . "` ORDER BY module_name");
            while ($row = $result->fetch_array()) {

                $module_id      = $row['id'];
                $module_slug    = $row['slug'];

                if (isset($_POST[$module_slug])) {

                    $permissions     = $_POST[$module_slug];

                    foreach ($permissions as $key => $value) {

                        $granted_permission = intval(e_s__($value)); // Sanitize to integer
                        
                        // Use prepared statement to prevent SQL injection
                        $stmt = $mysqli->prepare("INSERT INTO " . DB::PERMISSIONS . " (role_id, permission_id, module_id) VALUES (?, ?, ?)");
                        $stmt->bind_param('iii', $id, $granted_permission, $module_id);
                        $stmt->execute();
                        $insert_id = $stmt->insert_id;
                        $stmt->close();
                        
                        fp__($tbl_name, $insert_id);

                        // echo '<br />';
                        // print($value);
                        // echo ' <br />';
                    }
                }
            } // while

            // ---------------------------------------------------------------------------------

            $success_message = "The $module_caption has been saved successfully.";
            // header("Location:listing_$module.php?success_message=$success_message");
        } else {
            $error_message = "The $module_caption could not be saved. Please try again.";
            //header("Location:$module.php?error_message=$error_message");
        }
    }
}


/*
|--------------------------------------------------------------------------
| EDIT
|--------------------------------------------------------------------------
|
*/
if (!empty($id)) {

    $result = $mysqli->query("SELECT * FROM `$tbl_name` WHERE id=$id");
    $row = $result->fetch_array();

    $role_name          = s__($row['role_name']);
    $description        = s__($row['description']);
    $is_active = s__($row['is_active']);


    // --------------------------------------------------------------------------
    // -------------------- POPULATE PERMISSIONS --------------------------------
    // --------------------------------------------------------------------------
    $result_permissions         = $mysqli->query("SELECT * FROM `" . DB::PERMISSIONS . "` WHERE role_id=$id");
    while ($row_permissions     = $result_permissions->fetch_array()) {
        array_push($permissions_arr,        $row_permissions['permission_id']);
    }
}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

?>
<div class="content-wrapper">

    <!-- Page header -->
    <div class="page-header page-header-light shadow carriers-page-header">
        <div class="page-header-content border-top py-2 px-3 carriers-page-header-content">
            <div class="my-1">
                <h5 class="mb-0"><?php if (($action == "edit_$module" || $action == "update_$module" || $action == "change_password") && !empty($id)) { ?>Edit<?php } else { ?>New<?php } ?> <?php echo $module_caption; ?></h5>
            </div>

            <div class="my-1 d-inline-flex align-items-center me-2">
                <div class="form-check form-check-inline form-switch mb-0">
                    <input type="checkbox" class="form-check-input form-check-input-success" name="is_active" id="is_active" <?php if ($is_active == '1') { ?>checked="checked" <?php } ?> form="frmroles">
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="my-1">
                <?php if (empty($id) || (isset($module_id) && granted('create', $module_id)) || (isset($module_id) && granted('edit', $module_id)) || $file === 'profile.php' || $file === 'change_password.php') { ?>
                    <button type="submit" form="frmroles" class="btn btn-primary btn-sm me-2">Save</button>
                <?php } ?>
                <a href="listing_<?php echo $module; ?>.php" class="btn btn-light btn-sm">Cancel</a>
            </div>
        </div>
    </div>
    <!-- /page header -->

    <div class="content-inner">
        <div class="content">

            <?php include('admin_elements/breadcrumb.php'); ?>

            <form class="steps-basic clearfix" method="post" id="frm<?php echo $module; ?>" name="frm<?php echo $module; ?>" action="<?php echo $module; ?>.php" autocomplete="off" enctype="multipart/form-data">
        <?php if (($action == "edit_$module" || $action == "update_$module") && !empty($id)) { ?>
            <input type="hidden" name="action" id="action" value="update_<?php echo $module; ?>" />
            <input type="hidden" name="id" id="id" value="<?php echo $id; ?>" />
        <?php } else { ?>
            <input type="hidden" name="action" id="action" value="add_<?php echo $module; ?>" />
        <?php } ?>
        <?php echo csrf_field(); ?>

        <!-- Page header -->


                   <div class="card border-0 shadow-sm">

                       <div class="card-body" style="background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%); padding: 1.5rem;">

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="role_name">Role Name: <span class="text-danger">*</span></label>
                                    <input type="text" name="role_name" id="role_name" value="<?php echo $role_name; ?>" class="form-control" aria-required="true" aria-label="Role name (required)">
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" for="description">Description: </label>
                                    <input type="text" name="description" id="description" value="<?php echo $description; ?>" class="form-control" aria-label="Role description">
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Permissions Section -->
                   <div class="card border-0 shadow-sm">
                       <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 1.25rem;">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                   <h2 class="mb-0 text-white">
                                       <i class="ph-lock-key me-2" style="font-size: 1.5rem; vertical-align: middle;"></i>
                                    Module Permissions
                                       <span class="badge bg-white text-dark ms-2" id="selectedPermissionsCount" style="font-size: 0.75rem; padding: 0.35rem 0.65rem;">0 selected</span>
                                </h2>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end gap-2">
                                       <button type="button" class="btn btn-sm btn-light select-all-permissions" style="font-weight: 500;">
                                        <i class="ph-check-square me-1"></i>Select All
                                    </button>
                                       <button type="button" class="btn btn-sm btn-outline-light clear-all-permissions" style="font-weight: 500;">
                                        <i class="ph-x-square me-1"></i>Clear All
                                    </button>
                                       <input type="text" id="moduleSearch" class="form-control form-control-sm filter-search" placeholder="🔍 Search modules..." style="max-width: 200px; border: 2px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.95);" data-target=".module-item">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="modulesContainer">

                        <?php
                        $result = $mysqli->query("SELECT * FROM `" . DB::MODULES . "` ORDER BY module_name");
                        while ($row = $result->fetch_array()) {
                            $module_id         = $row['id'];
                            $module_name     = $row['module_name'];
                            $module_slug     = $row['slug'];
                        ?>

                            <div class="col-lg-4 col-md-6 module-item" data-module-name="<?php echo strtolower($module_name); ?>">
                                   <div class="card mb-3 shadow-sm border-0 module-card">
                                       <div class="card-header gradient-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.875rem 1rem;">
                                           <div class="form-check mb-0">
                                               <input type="checkbox" id="main-checkbox-<?php echo $module_slug; ?>" class="form-check-input main-module-checkbox check-uncheck-all" data-target=".permission-<?php echo $module_slug; ?>" style="cursor: pointer; border-color: rgba(255,255,255,0.5); background-color: rgba(255,255,255,0.2);">
                                               <label class="form-check-label fw-bold text-white" for="main-checkbox-<?php echo $module_slug; ?>" style="cursor: pointer; font-size: 0.9375rem; letter-spacing: 0.3px;">
                                                   <i class="ph-folder-open me-1" style="font-size: 1.1rem;"></i>
                                                <?php echo ucwords($module_name); ?>
                                            </label>
                                        </div>
                                    </div>
                                       <div class="card-body pt-3 pb-3" style="background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);">
                                        <div class="permission-list">

                                        <?php
                                        $result_         = $mysqli->query("SELECT * FROM `" . DB::MODULE_PERMISSIONS . "` WHERE module_id=$module_id  ORDER BY permission_name");
                                        while ($row_     = $result_->fetch_array()) {
                                            $permission_id         =  $row_['id'];
                                            $permission_name     =  str_ireplace('_', '', $row_['permission_name']);
                                            
                                            // Icon mapping for different permission types
                                            $icon = 'ph-circle';
                                            $color_class = '';
                                            if (stripos($permission_name, 'view') !== false) {
                                                $icon = 'ph-eye';
                                                $color_class = 'text-info';
                                            } elseif (stripos($permission_name, 'create') !== false || stripos($permission_name, 'add') !== false) {
                                                $icon = 'ph-plus-circle';
                                                $color_class = 'text-success';
                                            } elseif (stripos($permission_name, 'edit') !== false || stripos($permission_name, 'update') !== false) {
                                                $icon = 'ph-pencil';
                                                $color_class = 'text-warning';
                                            } elseif (stripos($permission_name, 'delete') !== false) {
                                                $icon = 'ph-trash';
                                                $color_class = 'text-danger';
                                            }
                                        ?>
                                            <div class="form-check mb-2">
                                                <input
                                                    type="checkbox"
                                                       class="form-check-input permission-checkbox permission-<?php echo $module_slug; ?> child-permission"
                                                    data-parent="#main-checkbox-<?php echo $module_slug; ?>"
                                                    name="<?php echo $row['slug']; ?>[]"
                                                    id="<?php echo $row_['id']; ?>"
                                                    value="<?php echo $row_['id']; ?>"
                                                    <?php if (in_array($permission_id, $permissions_arr)) { ?> checked <?php } ?>>
                                                   <label class="form-check-label <?php echo $color_class; ?>" for="<?php echo $row_['id']; ?>" style="cursor: pointer; font-size: 0.875rem; user-select: none;">
                                                       <i class="<?php echo $icon; ?> me-1" style="opacity: 0.8;"></i>
                                                    <?php echo ucwords($permission_name); ?>
                                                </label>
                                            </div>
                                        <?php } //while
                                        ?>

                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php
                        } // while 
                        ?>
                        </div>
                        
                           <div id="noModulesFound" class="alert alert-warning text-center border-0 shadow-sm" style="display: none; background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); color: white;">
                            <i class="ph-magnifying-glass me-2"></i>
                            No modules found matching your search.
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <script>
        /**
         * Check/uncheck all sub-permissions when main module checkbox is clicked
         */
        function check_uncheck_all(moduleSlug) {
            var mainCheckbox = document.getElementById('main-checkbox-' + moduleSlug);
            if (!mainCheckbox) return;

            var isChecked = mainCheckbox.checked;
            var checkboxes = document.querySelectorAll('.permission-' + moduleSlug);

            checkboxes.forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
            
            updatePermissionCount();
        }

        /**
         * Update main module checkbox based on sub-permissions state
         */
        function update_parent_checkbox(moduleSlug) {
            var checkboxes = document.querySelectorAll('.permission-' + moduleSlug);
            var mainCheckbox = document.getElementById('main-checkbox-' + moduleSlug);

            if (!mainCheckbox) return;

            var anyChecked = Array.from(checkboxes).some(function(checkbox) {
                return checkbox.checked;
            });

            mainCheckbox.checked = anyChecked;
        }
        
        /**
         * Select all permissions across all modules
         */
        function selectAllPermissions() {
            document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
                checkbox.checked = true;
            });
            document.querySelectorAll('.main-module-checkbox').forEach(function(checkbox) {
                checkbox.checked = true;
            });
            updatePermissionCount();
        }
        
        /**
         * Clear all permissions across all modules
         */
        function clearAllPermissions() {
            document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
                checkbox.checked = false;
            });
            document.querySelectorAll('.main-module-checkbox').forEach(function(checkbox) {
                checkbox.checked = false;
            });
            updatePermissionCount();
        }
        
        /**
         * Filter modules based on search input
         */
        function filterModules() {
            var searchValue = document.getElementById('moduleSearch').value.toLowerCase();
            var modules = document.querySelectorAll('.module-item');
            var visibleCount = 0;
            
            modules.forEach(function(module) {
                var moduleName = module.getAttribute('data-module-name');
                if (moduleName.includes(searchValue)) {
                       module.style.display = '';
                       setTimeout(function() {
                           module.style.opacity = '1';
                           module.style.transform = 'scale(1)';
                       }, 10);
                    visibleCount++;
                } else {
                       module.style.opacity = '0';
                       module.style.transform = 'scale(0.95)';
                       setTimeout(function() {
                           module.style.display = 'none';
                       }, 300);
                }
            });
            
            // Show/hide "no results" message
            var noResultsMsg = document.getElementById('noModulesFound');
            if (visibleCount === 0) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        }
        
        /**
         * Update the permissions counter badge
         */
        function updatePermissionCount() {
            var checkedCount = document.querySelectorAll('.permission-checkbox:checked').length;
            var totalCount = document.querySelectorAll('.permission-checkbox').length;
            var counterBadge = document.getElementById('selectedPermissionsCount');
            
            if (counterBadge) {
                counterBadge.textContent = checkedCount + ' of ' + totalCount + ' selected';
                
                // Change badge color based on selection
                   counterBadge.className = 'badge ms-2';
                   counterBadge.style.fontSize = '0.75rem';
                   counterBadge.style.padding = '0.35rem 0.65rem';
                if (checkedCount === 0) {
                       counterBadge.classList.add('bg-secondary');
                       counterBadge.style.backgroundColor = 'rgba(255,255,255,0.7)';
                       counterBadge.style.color = '#6c757d';
                } else if (checkedCount === totalCount) {
                       counterBadge.classList.add('bg-success');
                       counterBadge.style.backgroundColor = '#28a745';
                       counterBadge.style.color = 'white';
                } else {
                       counterBadge.classList.add('bg-primary');
                       counterBadge.style.backgroundColor = 'white';
                       counterBadge.style.color = '#667eea';
                }
            }
        }
        
        // Initialize counter on page load
        document.addEventListener('DOMContentLoaded', function() {
            
                        // Wire up Select All button
                        document.querySelector('.select-all-permissions')?.addEventListener('click', selectAllPermissions);
            
                        // Wire up Clear All button
                        document.querySelector('.clear-all-permissions')?.addEventListener('click', clearAllPermissions);
            
                        // Wire up module search
                        document.getElementById('moduleSearch')?.addEventListener('input', filterModules);
            
                        // Wire up main module checkboxes
                        document.querySelectorAll('.check-uncheck-all').forEach(function(checkbox) {
                            checkbox.addEventListener('change', function() {
                                var moduleSlug = this.id.replace('main-checkbox-', '');
                                check_uncheck_all(moduleSlug);
                            });
                        });
            
                        // Wire up child permission checkboxes
                        document.querySelectorAll('.child-permission').forEach(function(checkbox) {
                            checkbox.addEventListener('change', function() {
                                var parentId = this.getAttribute('data-parent');
                                if (parentId) {
                                    var moduleSlug = parentId.replace('#main-checkbox-', '');
                                    update_parent_checkbox(moduleSlug);
                                }
                                updatePermissionCount();
                            });
                        });
            updatePermissionCount();
        });
        </script>

        <style>
        /* Enhanced module card styling */
        .module-card {
            transition: all 0.3s ease;
            overflow: hidden;
              animation: fadeInUp 0.5s ease forwards;
              opacity: 0;
          }
        
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
        
                /* Stagger animation delay for cards */
                .module-item:nth-child(1) .module-card { animation-delay: 0.05s; }
                .module-item:nth-child(2) .module-card { animation-delay: 0.1s; }
                .module-item:nth-child(3) .module-card { animation-delay: 0.15s; }
                .module-item:nth-child(4) .module-card { animation-delay: 0.2s; }
                .module-item:nth-child(5) .module-card { animation-delay: 0.25s; }
                .module-item:nth-child(6) .module-card { animation-delay: 0.3s; }
                .module-item:nth-child(7) .module-card { animation-delay: 0.35s; }
                .module-item:nth-child(8) .module-card { animation-delay: 0.4s; }
                .module-item:nth-child(9) .module-card { animation-delay: 0.45s; }
                .module-item:nth-child(n+10) .module-card { animation-delay: 0.5s; }
        
                /* Smooth filtering transition */
                .module-item {
                    transition: opacity 0.3s ease, transform 0.3s ease, max-height 0.3s ease;
                    max-height: 500px;
                }
        
                .module-item[style*="display: none"] {
                    opacity: 0;
                    transform: scale(0.95);
                    max-height: 0;
                    overflow: hidden;
                }
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3) !important;
        }
        
        .gradient-header {
            position: relative;
            overflow: hidden;
        }
        
        .gradient-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .module-card:hover .gradient-header::before {
            left: 100%;
        }
        
        /* Checkbox styling */
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .main-module-checkbox:checked {
            background-color: rgba(255,255,255,0.9) !important;
            border-color: rgba(255,255,255,1) !important;
        }
        
        /* Permission list styling */
        .permission-list .form-check {
            padding: 0.375rem 0.5rem;
            border-radius: 0.25rem;
            transition: background-color 0.2s ease;
        }
        
        .permission-list .form-check:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .permission-list .form-check-input {
            margin-top: 0.25rem;
        }
        
        /* Card body styling */
        .module-card .card-body {
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        /* Search box enhancement */
        #moduleSearch {
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        #moduleSearch:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        
                /* Button enhancements */
                .select-all-permissions:hover,
                .clear-all-permissions:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
        
                /* Badge color overrides for the counter */
                #selectedPermissionsCount.bg-secondary {
                    background-color: rgba(255,255,255,0.7) !important;
                    color: #6c757d !important;
                }
        
                #selectedPermissionsCount.bg-success {
                    background-color: #28a745 !important;
                    color: white !important;
                }
        
                #selectedPermissionsCount.bg-primary {
                    background-color: white !important;
                    color: #667eea !important;
                }
        }
        </style>


        </form>
    <?php include('admin_elements/copyright.php'); ?>
</div>

</div>
<?php include('admin_elements/admin_footer.php'); ?>