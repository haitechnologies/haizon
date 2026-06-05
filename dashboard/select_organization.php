<?php

include('admin_elements/admin_header.php');

$organizations = dashboardGetAccessibleOrganizations();
$activeOrganizationId = dashboardGetActiveOrganizationId(false);
$requestedOrganizationId = (int)($_GET['organization_id'] ?? 0);

if ($requestedOrganizationId > 0) {
    if (dashboardSetActiveOrganization($requestedOrganizationId)) {
        header('Location:index.php?success_message=' . urlencode('Organization switched successfully.'));
        exit;
    }

    header('Location:select_organization.php?error_message=' . urlencode('You do not have access to that organization.'));
    exit;
}

?>

<div class="content-wrapper">
    <?php include('admin_elements/page_header.php'); ?>

    <div class="content">
        <?php include('admin_elements/breadcrumb.php'); ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Select Organization</h5>
                <?php if (function_exists('dashboardCanCreateOrganizations') && dashboardCanCreateOrganizations()) { ?>
                    <a href="organizations.php" class="btn btn-primary btn-sm">Create Organization</a>
                <?php } ?>
            </div>

            <div class="list-group list-group-flush">
                <?php if (empty($organizations)) { ?>
                    <div class="list-group-item py-4 text-center text-muted">
                        No organizations are assigned to your account yet.
                    </div>
                <?php } else { ?>
                    <?php foreach ($organizations as $organization) { ?>
                        <?php $organizationId = (int)($organization['id'] ?? 0); ?>
                        <?php $isActive = $organizationId === $activeOrganizationId; ?>
                        <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center gap-3 py-3">
                            <div class="flex-fill">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-semibold"><?php echo htmlspecialchars((string)($organization['warehouse_name'] ?? 'Organization'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($isActive) { ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php } ?>
                                </div>
                                <div class="text-muted small mt-1">
                                    ID: <?php echo $organizationId; ?>
                                    <?php if (!empty($organization['slug'])) { ?>
                                        | Slug: <?php echo htmlspecialchars((string)$organization['slug'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php } ?>
                                    <?php if (!empty($organization['status'])) { ?>
                                        | Status: <?php echo htmlspecialchars((string)$organization['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php } ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($isActive) { ?>
                                    <a href="organizations.php?action=edit_organizations&id=<?php echo $organizationId; ?>" class="btn btn-light btn-sm">Manage</a>
                                <?php } else { ?>
                                    <a href="select_organization.php?organization_id=<?php echo $organizationId; ?>" class="btn btn-primary btn-sm">Switch</a>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php include('admin_elements/copyright.php'); ?>
</div>