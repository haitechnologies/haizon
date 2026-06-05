<?php
/**
 * AJAX Email Template Preview Endpoint
 * 
 * Securely fetches email template data for display in a modal preview
 * Includes CSRF validation and permission checks
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/bootstrap.php';

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// CSRF validation
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
    exit;
}

// Permission check
if (!granted_('view', 'email_templates')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

try {
    $id = (int)($_POST['id'] ?? 0);
    
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid template ID']);
        exit;
    }
    
    // Fetch template using prepared statement
    $stmt = $mysqli->prepare("SELECT id, name, subject_default, html_body, text_body, is_default, is_system, created_at, updated_at FROM `" . DB::EMAIL_TEMPLATES . "` WHERE id = ? LIMIT 1");
    
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $mysqli->error);
    }
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Template not found']);
        exit;
    }
    
    // Normalize html body for preview: if stored as encoded entities,
    // decode it so the modal iframe renders markup instead of raw tags.
    $htmlBody = (string)($template['html_body'] ?? '');
    if (strpos($htmlBody, '<') === false && preg_match('/&lt;|&gt;|&amp;[a-zA-Z0-9#]+;/', $htmlBody)) {
        $decoded = html_entity_decode($htmlBody, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (is_string($decoded) && $decoded !== '') {
            $htmlBody = $decoded;
        }
    }

    // Return template data
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => (int)$template['id'],
            'name' => htmlspecialchars($template['name'] ?? ''),
            'subject_default' => htmlspecialchars($template['subject_default'] ?? ''),
            'html_body' => $htmlBody,
            'text_body' => htmlspecialchars($template['text_body'] ?? ''),
            'is_default' => (int)($template['is_default'] ?? 0),
            'is_system' => (int)($template['is_system'] ?? 0),
            'created_at' => $template['created_at'] ?? '',
            'updated_at' => $template['updated_at'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Email template preview error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>
