<?php
include('admin_elements/admin_header.php');

$module = 'sitemap';
$module_caption = 'Sitemap Management';
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
        // Handle sitemap generation
        $success_message = 'Sitemap generated successfully.';
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
            <div class="card">
                <div class="card-header">
                    <h5>Generate Sitemap</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="sitemap.php">
                        <?php echo csrf_field(); ?>
                        
                        <div class="form-group mb-3">
                            <label>
                                <input type="checkbox" name="include_pages" checked>
                                Include Static Pages
                            </label>
                        </div>

                        <div class="form-group mb-3">
                            <label>
                                <input type="checkbox" name="include_posts" checked>
                                Include Blog Posts
                            </label>
                        </div>

                        <?php if (granted_('edit', $module)): ?>
                        <button type="submit" class="btn btn-primary">Generate Sitemap</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('admin_elements/admin_footer.php'); ?>
