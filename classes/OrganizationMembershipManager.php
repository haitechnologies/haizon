<?php

class OrganizationMembershipManager
{
    public static function hasActiveMembership(mysqli $mysqli, int $organizationId, int $userId): bool
    {
        return self::userBelongsToOrganization($mysqli, $organizationId, $userId);
    }

    public static function countActiveMembers(mysqli $mysqli, int $organizationId): int
    {
        $stmt = $mysqli->prepare(
            "SELECT COUNT(*) AS cnt FROM `" . DB::ORGANIZATION_MEMBERSHIPS . "` WHERE organization_id = ? AND membership_status = 'active'"
        );
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $organizationId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['cnt'] ?? 0);
    }

    public static function createInvite(mysqli $mysqli, int $organizationId, int $inviterUserId, string $email, ?int $roleId = null, int $expiresInDays = 7): array
    {
        $email = strtolower(trim($email));
        if ($organizationId <= 0 || $inviterUserId <= 0) {
            return ['success' => false, 'message' => 'Invalid organization or inviter.'];
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'A valid email address is required.'];
        }

        if (!self::userBelongsToOrganization($mysqli, $organizationId, $inviterUserId)) {
            return ['success' => false, 'message' => 'You do not belong to this organization.'];
        }

        $existingUserId = self::findUserIdByEmail($mysqli, $email);
        if ($existingUserId > 0 && self::userBelongsToOrganization($mysqli, $organizationId, $existingUserId)) {
            return ['success' => false, 'message' => 'This user is already a member of the organization.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(1, $expiresInDays) . ' days'));

        $existingInvite = self::findPendingInviteByEmail($mysqli, $organizationId, $email);
        if ($existingInvite) {
            $inviteId = (int)$existingInvite['id'];
            $stmt = $mysqli->prepare(
                "UPDATE `" . DB::ORGANIZATION_INVITES . "`
                 SET invite_token = ?, invited_by = ?, role_id = ?, invite_status = 'pending', expires_at = ?, accepted_at = NULL
                 WHERE id = ?"
            );
            if (!$stmt) {
                return ['success' => false, 'message' => 'Failed to refresh organization invite.'];
            }

            $stmt->bind_param('siisi', $token, $inviterUserId, $roleId, $expiresAt, $inviteId);
            $success = $stmt->execute();
            $stmt->close();

            if (!$success) {
                return ['success' => false, 'message' => 'Failed to refresh organization invite.'];
            }

            return [
                'success' => true,
                'message' => 'Organization invite refreshed successfully.',
                'invite_token' => $token,
                'invite_id' => $inviteId,
                'email' => $email,
            ];
        }

        $stmt = $mysqli->prepare(
            "INSERT INTO `" . DB::ORGANIZATION_INVITES . "`
             (organization_id, email, invite_token, invited_by, role_id, invite_status, expires_at)
             VALUES (?, ?, ?, ?, ?, 'pending', ?)"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to create organization invite.'];
        }

        $stmt->bind_param('isssis', $organizationId, $email, $token, $inviterUserId, $roleId, $expiresAt);
        $success = $stmt->execute();
        $inviteId = (int)$mysqli->insert_id;
        $stmt->close();

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to create organization invite.'];
        }

        return [
            'success' => true,
            'message' => 'Organization invite created successfully.',
            'invite_token' => $token,
            'invite_id' => $inviteId,
            'email' => $email,
        ];
    }

    public static function acceptInviteByToken(mysqli $mysqli, string $token, int $userId): array
    {
        $token = trim($token);
        if ($token === '' || $userId <= 0) {
            return ['success' => false, 'message' => 'Invalid invite token or user.'];
        }

        $invite = self::findInviteByToken($mysqli, $token);
        if (!$invite) {
            return ['success' => false, 'message' => 'Invite not found.'];
        }

        if (($invite['invite_status'] ?? '') !== 'pending') {
            return ['success' => false, 'message' => 'Invite is no longer active.'];
        }

        if (!empty($invite['expires_at']) && strtotime((string)$invite['expires_at']) < time()) {
            self::markInviteExpired($mysqli, (int)$invite['id']);
            return ['success' => false, 'message' => 'Invite has expired.'];
        }

        $userEmail = self::findUserEmailById($mysqli, $userId);
        if ($userEmail === '' || strtolower($userEmail) !== strtolower((string)$invite['email'])) {
            return ['success' => false, 'message' => 'Invite email does not match the current user.'];
        }

        $organizationId = (int)$invite['organization_id'];
        if (self::userBelongsToOrganization($mysqli, $organizationId, $userId)) {
            self::markInviteAccepted($mysqli, (int)$invite['id']);
            return ['success' => true, 'message' => 'User already belongs to this organization.'];
        }

        $stmt = $mysqli->prepare(
            "INSERT INTO `" . DB::ORGANIZATION_MEMBERSHIPS . "` (organization_id, user_id, membership_status, is_owner, invited_by, joined_at) VALUES (?, ?, 'active', 0, ?, NOW())"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to create organization membership.'];
        }

        $invitedBy = (int)($invite['invited_by'] ?? 0);
        $stmt->bind_param('iii', $organizationId, $userId, $invitedBy);
        $success = $stmt->execute();
        $membershipId = (int)$mysqli->insert_id;
        $stmt->close();

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to create organization membership.'];
        }

        $roleId = (int)($invite['role_id'] ?? 0);
        if ($roleId > 0) {
            $roleStmt = $mysqli->prepare(
                "INSERT IGNORE INTO `" . DB::ORGANIZATION_MEMBER_ROLES . "` (membership_id, role_id) VALUES (?, ?)"
            );
            if ($roleStmt) {
                $roleStmt->bind_param('ii', $membershipId, $roleId);
                $roleStmt->execute();
                $roleStmt->close();
            }
        }

        self::markInviteAccepted($mysqli, (int)$invite['id']);

        return ['success' => true, 'message' => 'Organization invite accepted successfully.', 'organization_id' => $organizationId];
    }

    public static function resendInvite(
        mysqli $mysqli,
        int $organizationId,
        int $inviteId,
        int $requesterUserId,
        ?int $roleId = null,
        int $expiresInDays = 7,
        bool $allowWithoutMembership = false
    ): array {
        if ($organizationId <= 0 || $inviteId <= 0 || $requesterUserId <= 0) {
            return ['success' => false, 'message' => 'Invalid organization, invite, or user.'];
        }

        if (!$allowWithoutMembership && !self::userBelongsToOrganization($mysqli, $organizationId, $requesterUserId)) {
            return ['success' => false, 'message' => 'You do not belong to this organization.'];
        }

        $invite = self::findPendingInviteById($mysqli, $organizationId, $inviteId);
        if (!$invite) {
            return ['success' => false, 'message' => 'Only pending invites can be resent.'];
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . max(1, $expiresInDays) . ' days'));
        $resolvedRoleId = $roleId !== null ? $roleId : (int)($invite['role_id'] ?? 0);

        $stmt = $mysqli->prepare(
            "UPDATE `" . DB::ORGANIZATION_INVITES . "`
             SET invite_token = ?, invited_by = ?, role_id = ?, invite_status = 'pending', expires_at = ?, accepted_at = NULL
             WHERE id = ? AND organization_id = ?"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to resend organization invite.'];
        }

        $stmt->bind_param('siisii', $token, $requesterUserId, $resolvedRoleId, $expiresAt, $inviteId, $organizationId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to resend organization invite.'];
        }

        return [
            'success' => true,
            'message' => 'Organization invite resent successfully.',
            'invite_id' => $inviteId,
            'invite_token' => $token,
            'email' => (string)($invite['email'] ?? ''),
        ];
    }

    public static function revokeInvite(
        mysqli $mysqli,
        int $organizationId,
        int $inviteId,
        int $requesterUserId,
        bool $allowWithoutMembership = false
    ): array {
        if ($organizationId <= 0 || $inviteId <= 0 || $requesterUserId <= 0) {
            return ['success' => false, 'message' => 'Invalid organization, invite, or user.'];
        }

        if (!$allowWithoutMembership && !self::userBelongsToOrganization($mysqli, $organizationId, $requesterUserId)) {
            return ['success' => false, 'message' => 'You do not belong to this organization.'];
        }

        $invite = self::findPendingInviteById($mysqli, $organizationId, $inviteId);
        if (!$invite) {
            return ['success' => false, 'message' => 'Only pending invites can be revoked.'];
        }

        $stmt = $mysqli->prepare(
            "UPDATE `" . DB::ORGANIZATION_INVITES . "`
             SET invite_status = 'revoked', accepted_at = NULL
             WHERE id = ? AND organization_id = ? AND invite_status = 'pending'"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to revoke organization invite.'];
        }

        $stmt->bind_param('ii', $inviteId, $organizationId);
        $success = $stmt->execute();
        $stmt->close();

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to revoke organization invite.'];
        }

        return ['success' => true, 'message' => 'Organization invite revoked successfully.', 'invite_id' => $inviteId];
    }

    private static function userBelongsToOrganization(mysqli $mysqli, int $organizationId, int $userId): bool
    {
        $stmt = $mysqli->prepare(
            "SELECT id FROM `" . DB::ORGANIZATION_MEMBERSHIPS . "` WHERE organization_id = ? AND user_id = ? AND membership_status = 'active' LIMIT 1"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ii', $organizationId, $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (bool)$row;
    }

    private static function findPendingInviteByEmail(mysqli $mysqli, int $organizationId, string $email): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id FROM `" . DB::ORGANIZATION_INVITES . "` WHERE organization_id = ? AND email = ? AND invite_status = 'pending' LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('is', $organizationId, $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private static function findInviteByToken(mysqli $mysqli, string $token): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT * FROM `" . DB::ORGANIZATION_INVITES . "` WHERE invite_token = ? LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private static function findPendingInviteById(mysqli $mysqli, int $organizationId, int $inviteId): ?array
    {
        $stmt = $mysqli->prepare(
            "SELECT id, email, role_id FROM `" . DB::ORGANIZATION_INVITES . "` WHERE id = ? AND organization_id = ? AND invite_status = 'pending' LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('ii', $inviteId, $organizationId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private static function findUserIdByEmail(mysqli $mysqli, string $email): int
    {
        $stmt = $mysqli->prepare("SELECT id FROM `" . DB::USERS . "` WHERE email = ? LIMIT 1");
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['id'] ?? 0);
    }

    private static function findUserEmailById(mysqli $mysqli, int $userId): string
    {
        $stmt = $mysqli->prepare("SELECT email FROM `" . DB::USERS . "` WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return '';
        }

        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (string)($row['email'] ?? '');
    }

    private static function markInviteAccepted(mysqli $mysqli, int $inviteId): void
    {
        $stmt = $mysqli->prepare(
            "UPDATE `" . DB::ORGANIZATION_INVITES . "` SET invite_status = 'accepted', accepted_at = NOW() WHERE id = ?"
        );
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $inviteId);
        $stmt->execute();
        $stmt->close();
    }

    private static function markInviteExpired(mysqli $mysqli, int $inviteId): void
    {
        $stmt = $mysqli->prepare(
            "UPDATE `" . DB::ORGANIZATION_INVITES . "` SET invite_status = 'expired' WHERE id = ?"
        );
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('i', $inviteId);
        $stmt->execute();
        $stmt->close();
    }
}