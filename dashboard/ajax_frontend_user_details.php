<?php
/**
 * AJAX Frontend User Details Endpoint
 *
 * Returns full frontend user details for modal display.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
    exit;
}

if (!granted_('view', 'frontend_users')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid frontend user ID']);
    exit;
}

try {
    $sql = "SELECT 
                id,
                full_name,
                email,
                mobile,
                email_verified,
                email_verification_token,
                is_active,
                publish,
                last_login,
                created_at,
                updated_at
            FROM `" . DB::FRONTEND_USERS . "`
            WHERE id = ?
            LIMIT 1";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    // Get user statistics
    // Favorites table is decommissioned, keep metric as 0 for compatibility.
    $favoritesCount = 0;
    $searchesCount = 0;

    // Count searches
    $searchSql = "SELECT COUNT(*) as cnt FROM `" . DB::SEARCHES . "` WHERE user_id = ?";
    $searchStmt = $mysqli->prepare($searchSql);
    if ($searchStmt) {
        $searchStmt->bind_param('i', $id);
        $searchStmt->execute();
        $searchResult = $searchStmt->get_result();
        if ($searchResult && $searchRow = $searchResult->fetch_assoc()) {
            $searchesCount = (int)$searchRow['cnt'];
        }
        $searchStmt->close();
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$user['id'],
            'full_name' => (string)($user['full_name'] ?? ''),
            'email' => (string)($user['email'] ?? ''),
            'mobile' => (string)($user['mobile'] ?? ''),
            'email_verified' => (int)($user['email_verified'] ?? 0),
            'is_active' => (int)($user['is_active'] ?? 0),
            'publish' => (int)($user['publish'] ?? 0),
            'last_login' => (string)($user['last_login'] ?? ''),
            'created_at' => (string)($user['created_at'] ?? ''),
            'updated_at' => (string)($user['updated_at'] ?? ''),
            'favorites_count' => $favoritesCount,
            'searches_count' => $searchesCount
        ]
    ]);

} catch (Exception $e) {
    error_log('Frontend User Details Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
