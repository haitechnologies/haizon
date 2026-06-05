<?php
require_once __DIR__ . '/../DB.php';
require_once __DIR__ . '/../../config/database.php';

class UserSettings {
    private $mysqli;
    private $usersTable = DB::FRONTEND_USERS;

    public function __construct($mysqli = null) {
        global $conn;
        $this->mysqli = $mysqli ?? $conn;
    }

    public function getUserInfo($userId) {
        $sql = "SELECT id, full_name, email, mobile, email_verified, created_at
                FROM `{$this->usersTable}` WHERE id = ? LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user ?: null;
    }

    public function updateProfile($userId, array $data) {
        $fullName = trim((string)($data['full_name'] ?? ''));
        $mobile = trim((string)($data['mobile'] ?? ''));

        $sql = "UPDATE `{$this->usersTable}` SET full_name = ?, mobile = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ssi', $fullName, $mobile, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function verifyPassword($userId, $password) {
        $sql = "SELECT password FROM `{$this->usersTable}` WHERE id = ? LIMIT 1";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['password'])) {
            return false;
        }

        return password_verify($password, $row['password']);
    }

    public function changePassword($userId, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE `{$this->usersTable}` SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $hash, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteAccount($userId) {
        $sql = "DELETE FROM `{$this->usersTable}` WHERE id = ?";
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
