<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

class OrganizationMembershipManager
{
    private static function getDatabase(mixed $conn = null): Database
    {
        if ($conn instanceof Database) {
            return $conn;
        }

        try {
            $container = Container::getInstance();
            if ($container->has(Database::class)) {
                $resolved = $container->get(Database::class);
                if ($resolved instanceof Database) {
                    return $resolved;
                }
            }
        } catch (Throwable $e) {
            // Ignore container resolution errors
        }

        return new Database();
    }

    public static function hasActiveMembership(mixed $mysqli, int $organizationId, int $userId): bool
    {
        return self::userBelongsToOrganization($mysqli, $organizationId, $userId);
    }

    public static function countActiveMembers(mixed $mysqli, int $organizationId): int
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT COUNT(*) AS cnt FROM `" . DB::ORGANIZATION_MEMBERSHIPS . "` WHERE organization_id = ? AND membership_status = 'active'";
        try {
            $row = $db->fetchOne($sql, [$organizationId]);
            return (int)($row['cnt'] ?? 0);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::countActiveMembers() failed: ' . $e->getMessage());
            return 0;
        }
    }

    public static function createInvite(
        mixed $mysqli,
        int $organizationId,
        int $inviterUserId,
        string $email,
        ?int $roleId = null,
        int $expiresInDays = 7
    ): array {
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
        $db = self::getDatabase($mysqli);
        if ($existingInvite) {
            $inviteId = (int)$existingInvite['id'];
            $sql = "UPDATE `" . DB::ORGANIZATION_INVITES . "`
                    SET invite_token = ?, invited_by = ?, role_id = ?, invite_status = 'pending', expires_at = ?, accepted_at = NULL
                    WHERE id = ?";
            try {
                $db->execute($sql, [$token, $inviterUserId, $roleId, $expiresAt, $inviteId]);
                return [
                    'success' => true,
                    'message' => 'Organization invite refreshed successfully.',
                    'invite_token' => $token,
                    'invite_id' => $inviteId,
                    'email' => $email,
                ];
            } catch (Throwable $e) {
                error_log('OrganizationMembershipManager::createInvite() update failed: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Failed to refresh organization invite.'];
            }
        }

        $sql = "INSERT INTO `" . DB::ORGANIZATION_INVITES . "`
                (organization_id, email, invite_token, invited_by, role_id, invite_status, expires_at)
                VALUES (?, ?, ?, ?, ?, 'pending', ?)";
        try {
            $inviteId = (int)$db->insert($sql, [$organizationId, $email, $token, $inviterUserId, $roleId, $expiresAt]);
            return [
                'success' => true,
                'message' => 'Organization invite created successfully.',
                'invite_token' => $token,
                'invite_id' => $inviteId,
                'email' => $email,
            ];
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::createInvite() insert failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create organization invite.'];
        }
    }

    public static function acceptInviteByToken(mixed $mysqli, string $token, int $userId): array
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

        $db = self::getDatabase($mysqli);
        $sql = "INSERT INTO `" . DB::ORGANIZATION_MEMBERSHIPS . "` (organization_id, user_id, membership_status, is_owner, invited_by, joined_at) VALUES (?, ?, 'active', 0, ?, NOW())";
        try {
            $invitedBy = (int)($invite['invited_by'] ?? 0);
            $membershipId = (int)$db->insert($sql, [$organizationId, $userId, $invitedBy]);

            $roleId = (int)($invite['role_id'] ?? 0);
            if ($roleId > 0) {
                $roleSql = "INSERT IGNORE INTO `" . DB::ORGANIZATION_MEMBER_ROLES . "` (membership_id, role_id) VALUES (?, ?)";
                try {
                    $db->execute($roleSql, [$membershipId, $roleId]);
                } catch (Throwable $e) {
                    error_log('OrganizationMembershipManager::acceptInviteByToken() role assignment failed: ' . $e->getMessage());
                }
            }

            self::markInviteAccepted($mysqli, (int)$invite['id']);
            return ['success' => true, 'message' => 'Organization invite accepted successfully.', 'organization_id' => $organizationId];
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::acceptInviteByToken() membership insert failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create organization membership.'];
        }
    }

    public static function resendInvite(
        mixed $mysqli,
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

        $db = self::getDatabase($mysqli);
        $sql = "UPDATE `" . DB::ORGANIZATION_INVITES . "`
                SET invite_token = ?, invited_by = ?, role_id = ?, invite_status = 'pending', expires_at = ?, accepted_at = NULL
                WHERE id = ? AND organization_id = ?";
        try {
            $db->execute($sql, [$token, $requesterUserId, $resolvedRoleId, $expiresAt, $inviteId, $organizationId]);
            return [
                'success' => true,
                'message' => 'Organization invite resent successfully.',
                'invite_id' => $inviteId,
                'invite_token' => $token,
                'email' => (string)($invite['email'] ?? ''),
            ];
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::resendInvite() failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to resend organization invite.'];
        }
    }

    public static function revokeInvite(
        mixed $mysqli,
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

        $db = self::getDatabase($mysqli);
        $sql = "UPDATE `" . DB::ORGANIZATION_INVITES . "`
                SET invite_status = 'revoked', accepted_at = NULL
                WHERE id = ? AND organization_id = ? AND invite_status = 'pending'";
        try {
            $db->execute($sql, [$inviteId, $organizationId]);
            return ['success' => true, 'message' => 'Organization invite revoked successfully.', 'invite_id' => $inviteId];
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::revokeInvite() failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to revoke organization invite.'];
        }
    }

    private static function userBelongsToOrganization(mixed $mysqli, int $organizationId, int $userId): bool
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT id FROM `" . DB::ORGANIZATION_MEMBERSHIPS . "` WHERE organization_id = ? AND user_id = ? AND membership_status = 'active' LIMIT 1";
        try {
            $row = $db->fetchOne($sql, [$organizationId, $userId]);
            return !empty($row);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::userBelongsToOrganization() failed: ' . $e->getMessage());
            return false;
        }
    }

    private static function findPendingInviteByEmail(mixed $mysqli, int $organizationId, string $email): ?array
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT id FROM `" . DB::ORGANIZATION_INVITES . "` WHERE organization_id = ? AND email = ? AND invite_status = 'pending' LIMIT 1";
        try {
            return $db->fetchOne($sql, [$organizationId, $email]);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::findPendingInviteByEmail() failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function findInviteByToken(mixed $mysqli, string $token): ?array
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT * FROM `" . DB::ORGANIZATION_INVITES . "` WHERE invite_token = ? LIMIT 1";
        try {
            return $db->fetchOne($sql, [$token]);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::findInviteByToken() failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function findPendingInviteById(mixed $mysqli, int $organizationId, int $inviteId): ?array
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT id, email, role_id FROM `" . DB::ORGANIZATION_INVITES . "` WHERE id = ? AND organization_id = ? AND invite_status = 'pending' LIMIT 1";
        try {
            return $db->fetchOne($sql, [$inviteId, $organizationId]);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::findPendingInviteById() failed: ' . $e->getMessage());
            return null;
        }
    }

    private static function findUserIdByEmail(mixed $mysqli, string $email): int
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT id FROM `" . DB::USERS . "` WHERE email = ? LIMIT 1";
        try {
            $row = $db->fetchOne($sql, [$email]);
            return (int)($row['id'] ?? 0);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::findUserIdByEmail() failed: ' . $e->getMessage());
            return 0;
        }
    }

    private static function findUserEmailById(mixed $mysqli, int $userId): string
    {
        $db = self::getDatabase($mysqli);
        $sql = "SELECT email FROM `" . DB::USERS . "` WHERE id = ? LIMIT 1";
        try {
            $row = $db->fetchOne($sql, [$userId]);
            return (string)($row['email'] ?? '');
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::findUserEmailById() failed: ' . $e->getMessage());
            return '';
        }
    }

    private static function markInviteAccepted(mixed $mysqli, int $inviteId): void
    {
        $db = self::getDatabase($mysqli);
        $sql = "UPDATE `" . DB::ORGANIZATION_INVITES . "` SET invite_status = 'accepted', accepted_at = NOW() WHERE id = ?";
        try {
            $db->execute($sql, [$inviteId]);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::markInviteAccepted() failed: ' . $e->getMessage());
        }
    }

    private static function markInviteExpired(mixed $mysqli, int $inviteId): void
    {
        $db = self::getDatabase($mysqli);
        $sql = "UPDATE `" . DB::ORGANIZATION_INVITES . "` SET invite_status = 'expired' WHERE id = ?";
        try {
            $db->execute($sql, [$inviteId]);
        } catch (Throwable $e) {
            error_log('OrganizationMembershipManager::markInviteExpired() failed: ' . $e->getMessage());
        }
    }
}
