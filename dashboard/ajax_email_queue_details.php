<?php

use App\Core\DB;
/**
 * AJAX Handler: Email Queue Details
 * Returns detailed information for a queue item in HTML format.
 */

require_once __DIR__ . '/bootstrap.php';
// Removed legacy require for autoloader compatibility: require_once __DIR__ . '/../classes/DB.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($session_user_id) || empty($session_user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid queue ID']);
    exit;
}

$stmt = $mysqli->prepare(
    "SELECT q.*, p.provider_name
     FROM `" . DB::EMAIL_QUEUE . "` q
     LEFT JOIN `" . DB::EMAIL_PROVIDERS . "` p ON p.id = q.provider_id
     WHERE q.id = ?
     LIMIT 1"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare query']);
    exit;
}

$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Queue record not found']);
    exit;
}

$h = static function ($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$headersRaw = (string)($row['headers'] ?? '');
$payloadRaw = (string)($row['payload_json'] ?? '');
$headersPretty = $headersRaw !== '' ? json_encode(json_decode($headersRaw, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
$payloadPretty = $payloadRaw !== '' ? json_encode(json_decode($payloadRaw, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '{}';
if ($headersPretty === false || $headersPretty === null) {
    $headersPretty = $headersRaw;
}
if ($payloadPretty === false || $payloadPretty === null) {
    $payloadPretty = $payloadRaw;
}

$status = $h($row['status'] ?? '-');
$body = (string)($row['body'] ?? '');
$bodyPreview = mb_substr($body, 0, 5000);

$html = '';
$html .= '<div class="table-responsive mb-3">';
$html .= '<table class="table table-bordered table-sm align-middle mb-0">';
$html .= '<tbody>';
$html .= '<tr><th width="220">Queue ID</th><td>' . (int)$row['id'] . '</td></tr>';
$html .= '<tr><th>Status</th><td><strong>' . $status . '</strong></td></tr>';
$html .= '<tr><th>Recipient Email</th><td>' . $h($row['recipient_email'] ?? $row['recipient'] ?? '-') . '</td></tr>';
$html .= '<tr><th>Subject</th><td>' . $h($row['subject'] ?? '-') . '</td></tr>';
$html .= '<tr><th>Provider</th><td>' . $h($row['provider_name'] ?? ('#' . ($row['provider_id'] ?? '-'))) . '</td></tr>';
$html .= '<tr><th>Priority</th><td>' . (int)($row['priority'] ?? 0) . '</td></tr>';
$html .= '<tr><th>Retries</th><td>' . (int)($row['retries'] ?? 0) . ' / ' . (int)($row['max_retries'] ?? 0) . '</td></tr>';
$html .= '<tr><th>Attempts</th><td>' . (int)($row['attempts'] ?? 0) . '</td></tr>';
$html .= '<tr><th>Next Retry At</th><td>' . dd_($row['next_retry_at'] ?? null) . '</td></tr>';
$html .= '<tr><th>Created At</th><td>' . dd_($row['created_at'] ?? null) . '</td></tr>';
$html .= '<tr><th>Updated At</th><td>' . dd_($row['updated_at'] ?? null) . '</td></tr>';
$html .= '<tr><th>Sent At</th><td>' . dd_($row['sent_at'] ?? null) . '</td></tr>';
$html .= '<tr><th>Failed Reason</th><td>' . $h($row['failed_reason'] ?? '-') . '</td></tr>';
$html .= '</tbody>';
$html .= '</table>';
$html .= '</div>';

$html .= '<h6 class="mb-2">Email Body Preview</h6>';
$html .= '<div class="border rounded p-2 bg-light mb-3" style="max-height:260px; overflow:auto;">' . nl2br($h($bodyPreview)) . '</div>';

$html .= '<h6 class="mb-2">Headers (JSON)</h6>';
$html .= '<pre class="border rounded p-2 bg-light" style="max-height:240px; overflow:auto;">' . $h($headersPretty) . '</pre>';

$html .= '<h6 class="mb-2">Payload (JSON)</h6>';
$html .= '<pre class="border rounded p-2 bg-light mb-0" style="max-height:240px; overflow:auto;">' . $h($payloadPretty) . '</pre>';

echo json_encode([
    'success' => true,
    'html' => $html
]);
