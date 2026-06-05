<?php

include('admin_elements/admin_header.php');

$token = trim((string)($_GET['token'] ?? ''));

if ($token === '') {
    header('Location:index.php?error_message=' . urlencode('Invite token is required.'));
    exit;
}

$result = dashboardAcceptOrganizationInvite($token);

if (!empty($result['success'])) {
    $acceptedOrganizationId = (int)($result['organization_id'] ?? 0);
    if ($acceptedOrganizationId > 0) {
        dashboardSetActiveOrganization($acceptedOrganizationId);
    }

    header('Location:index.php?success_message=' . urlencode((string)($result['message'] ?? 'Organization invite accepted successfully.')));
    exit;
}

$errorMessage = (string)($result['message'] ?? 'Unable to accept organization invite.');
header('Location:index.php?error_message=' . urlencode($errorMessage));
exit;