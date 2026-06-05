<?php
include('admin_elements/admin_header.php');

$module = 'system_settings';
$module_caption = 'System Settings';
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
| CSRF TOKEN VALIDATION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please refresh the page and try again.';
        log_error('CSRF token validation failed', 'WARNING', __FILE__, __LINE__);
    } else if (granted_('edit', $module)) {
        // Handle settings update
        $success_message = 'System settings updated successfully.';
    }
}

include('admin_elements/breadcrumb.php');
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1><?php echo e($module_caption); ?></h1>
    </div>

    <div class="row">
        <div class="col-md-8">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#general">General</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#email">Email</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#security">Security</a>
                </li>
            </ul>

            <div class="tab-content">
                <div id="general" class="tab-pane fade show active">
                    <div class="card">
                        <div class="card-header">
                            <h5>General Settings</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="system_settings.php">
                                <?php echo csrf_field(); ?>
                                
                                <div class="form-group mb-3">
                                    <label for="site_name">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name">
                                </div>

                                <?php if (granted_('edit', $module)): ?>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="email" class="tab-pane fade">
                    <div class="card">
                        <div class="card-header">
                            <h5>Email Configuration</h5>
                        </div>
                        <div class="card-body">
                            <p>Email settings configuration.</p>
                        </div>
                    </div>
                </div>

                <div id="security" class="tab-pane fade">
                    <div class="card">
                        <div class="card-header">
                            <h5>Security Settings</h5>
                        </div>
                        <div class="card-body">
                            <p>Security configuration options.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
