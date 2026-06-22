<?php

include('admin_elements/admin_header.php');

$token = trim((string)($_GET['token'] ?? ''));

if ($token === '') {
    flash_error('Invite token is required.');
    header('Location:index.php');
    exit;
}

$result = dashboardAcceptOrganizationInvite($token);

if (!empty($result['success'])) {
    $acceptedOrganizationId = (int)($result['organization_id'] ?? 0);
    if ($acceptedOrganizationId > 0) {
        dashboardSetActiveOrganization($acceptedOrganizationId);
    }

    flash_success((string)($result['message'] ?? 'Organization invite accepted successfully.'));
    header('Location:index.php');
    exit;
}

$errorMessage = (string)($result['message'] ?? 'Unable to accept organization invite.');
flash_error($errorMessage);
header('Location:index.php');
exit;