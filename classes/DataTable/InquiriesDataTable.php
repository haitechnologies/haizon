<?php
/**
 * InquiriesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';
require_once __DIR__ . '/../ActionButtonHelper.php';

class InquiriesDataTable extends BaseDataTable {
    protected $table = DB::INQUIRIES;
    protected $searchFields = ['full_name', 'email', 'subject', 'message'];
    protected $sortableColumns = [
        1 => 'created_at',
        2 => 'full_name',
        3 => 'subject',
        4 => 'status'
    ];

    protected function buildBaseQuery($requestData) {
        $spamFilter = (int)($requestData['filter_spam'] ?? 0);
        $archiveFilter = (int)($requestData['filter_archive'] ?? 0);

        if ($archiveFilter === 1) {
            return "SELECT * FROM `" . $this->table . "` WHERE id > 0 AND is_active = 1 AND status = 4";
        }

        if ($spamFilter === 1) {
            return "SELECT * FROM `" . $this->table . "` WHERE id > 0 AND is_active = 1 AND is_spam = 1 AND status <> 4";
        }
        return "SELECT * FROM `" . $this->table . "` WHERE id > 0 AND is_active = 1 AND is_spam = 0 AND status <> 4";
    }

    protected function buildSearchClause($requestData) {
        $searchValue = $requestData['search']['value'] ?? '';
        if (empty($searchValue)) {
            return '';
        }

        $searchValue = $this->mysqli->real_escape_string($searchValue);
        return "AND (full_name LIKE '%{$searchValue}%' OR email LIKE '%{$searchValue}%' OR subject LIKE '%{$searchValue}%' OR message LIKE '%{$searchValue}%')";
    }

    protected function buildOrderClause($requestData) {
        $orderColumn = isset($requestData['order'][0]['column']) ? (int)$requestData['order'][0]['column'] : 1;
        $orderDir = isset($requestData['order'][0]['dir']) ? strtoupper($requestData['order'][0]['dir']) : 'DESC';

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'created_at';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    /** Reply counts keyed by inquiry id, populated in prepareRelatedData(). */
    private array $replyCounts = [];

    protected function prepareRelatedData(array $rows, array $requestData = []): void {
        if (empty($rows)) return;

        $ids = array_map(fn($r) => (int)$r['id'], $rows);
        $placeholders = implode(',', $ids);

        $result = $this->mysqli->query(
            "SELECT inquiry_id, COUNT(*) AS cnt
             FROM `" . DB::INQUIRY_REPLIES . "`
             WHERE inquiry_id IN ($placeholders)
             GROUP BY inquiry_id"
        );

        if ($result) {
            while ($r = $result->fetch_assoc()) {
                $this->replyCounts[(int)$r['inquiry_id']] = (int)$r['cnt'];
            }
            $result->free();
        }
    }

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $fullName = s__($row['full_name'] ?? '');
        $email = s__($row['email'] ?? '');
        $mobile = s__($row['mobile'] ?? '');
        $subject = s__($row['subject'] ?? '');
        $message = s__($row['message'] ?? '');
        $status = (int)($row['status'] ?? 0);
        $isSpam = (int)($row['is_spam'] ?? 0);
        $isActive = (int)($row['is_active'] ?? 0);
        $createdAt = $row['created_at'] ?? '';
        $ipAddress = s__($row['ip_address'] ?? '');
        $userAgent = s__($row['user_agent'] ?? '');

        $messagePreview = $this->buildMessagePreview($message);

        $claimContext = $this->extractClaimContext($subject, $message);
        $subjectDisplay = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $subjectPlain = $subject !== '' ? $subject : 'General inquiry';
        $subjectLine = '<div class="inquiry-subject-line">' . htmlspecialchars($subjectPlain, ENT_QUOTES, 'UTF-8') . '</div>';
        if ($claimContext['is_claim']) {
            $subjectDisplay = '<span class="badge bg-warning text-dark me-2">Claim</span>Claim this business listing';
            $subjectLine = '<div class="inquiry-subject-line"><span class="badge bg-warning text-dark me-2">Claim</span>Claim this business listing</div>';
        }

        // Status badge
        if ($status == 4) {
            $statusBadge = '<span class="badge bg-dark">Archived</span>';
        } elseif ($isSpam) {
            $statusBadge = '<span class="badge bg-danger">Spam</span>';
        } elseif ($status == 0) {
            $statusBadge = BadgeHelper::primary('New');
        } elseif ($status == 2) {
            $statusBadge = BadgeHelper::success('Replied');
        } elseif ($status == 3) {
            $statusBadge = BadgeHelper::secondary('Closed');
        } else {
            $statusBadge = '<span class="badge bg-light text-body">Read</span>';
        }

        // Reply count badge
        $replyCount = $this->replyCounts[$id] ?? 0;
        if ($replyCount > 0) {
            $statusBadge .= ' <span class="badge bg-secondary ms-1" title="' . $replyCount . ' reply(ies)"><i class="ph-arrow-bend-up-right me-1"></i>' . $replyCount . '</span>';
        }

        // Hidden view button with all data attributes for modal
        $viewButton = '<button type="button" class="view-inquiry-btn d-none" data-id="' . $id . '" data-date="' . htmlspecialchars($createdAt) . '" data-name="' . htmlspecialchars($fullName) . '" data-email="' . htmlspecialchars($email) . '" data-phone="' . htmlspecialchars($mobile) . '" data-status="' . $status . '" data-is-active="' . $isActive . '" data-ip="' . htmlspecialchars($ipAddress) . '" data-ua="' . htmlspecialchars($userAgent) . '" data-subject="' . htmlspecialchars($subject) . '" data-subject-display="' . htmlspecialchars($subjectDisplay, ENT_QUOTES, 'UTF-8') . '" data-message="' . htmlspecialchars($message) . '" data-claim-request="' . ($claimContext['is_claim'] ? '1' : '0') . '" data-claim-company-id="' . (int)$claimContext['company_id'] . '" data-claim-company-slug="' . htmlspecialchars((string)$claimContext['company_slug'], ENT_QUOTES, 'UTF-8') . '"></button>';

        $senderMeta = [];
        if ($email !== '') {
            $senderMeta[] = '<a href="mailto:' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '" class="inquiry-sender-link">' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</a>';
        }
        if ($mobile !== '') {
            $senderMeta[] = '<span>' . htmlspecialchars($mobile, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        $senderCell = '<div class="inquiry-sender-cell">'
            . '<div class="inquiry-sender-name">' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</div>'
            . (!empty($senderMeta) ? '<div class="inquiry-sender-meta">' . implode('<span class="inquiry-meta-sep">â€¢</span>', $senderMeta) . '</div>' : '')
            . '</div>';

        $inquiryCell = '<div class="inquiry-preview-cell">'
            . $subjectLine
            . '<div class="inquiry-message-snippet">' . nl2br(htmlspecialchars($messagePreview, ENT_QUOTES, 'UTF-8')) . '</div>'
            . '</div>';

        // Status column: badge only
        $statusCell = '<div class="inquiry-status-cell">' . $statusBadge . '</div>';

        // Actions column: spam toggle + delete
        $editPerm = (int)($requestData['edit_permission'] ?? 0);
        $spamBtn = '';
        if ($editPerm) {
            if ($isSpam) {
                $spamBtn = '<button type="button" class="btn btn-sm btn-outline-secondary mark-spam-btn me-1" title="Not spam" data-id="' . $id . '" data-spam="0">'
                    . '<i class="ph-check-circle me-1"></i>Not Spam</button>';
            } else {
                $spamBtn = '<button type="button" class="btn btn-sm btn-outline-danger mark-spam-btn me-1" title="Mark as spam" data-id="' . $id . '" data-spam="1">'
                    . '<i class="ph-prohibit me-1"></i>Spam</button>';
            }
        }

        $archiveBtn = '';
        if ($editPerm) {
            if ($status == 4) {
                $archiveBtn = '<button type="button" class="btn btn-sm btn-outline-secondary mark-archive-btn me-1" title="Move to inbox" data-id="' . $id . '" data-archive="0">'
                    . '<i class="ph-arrow-counter-clockwise me-1"></i>Unarchive</button>';
            } else {
                $archiveBtn = '<button type="button" class="btn btn-sm btn-outline-dark mark-archive-btn me-1" title="Move to archive" data-id="' . $id . '" data-archive="1">'
                    . '<i class="ph-archive me-1"></i>Archive</button>';
            }
        }

        $actionsCell = '<div class="inquiry-actions-cell d-flex flex-wrap gap-1 align-items-center">'
            . $spamBtn
            . $archiveBtn
            . $this->getActionButtons($id, 'inquiries')
            . '</div>';

        return [
            '<input type="checkbox" class="inquiry-checkbox" data-id="' . $id . '">' . $viewButton,
            timeAgo($createdAt),
            $senderCell,
            $inquiryCell,
            $statusCell,
            $actionsCell
        ];
    }

    private function buildMessagePreview(string $message): string {
        $clean = preg_replace('/\R+--- Claim Request Metadata ---.*$/si', '', $message) ?? $message;
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? $clean);

        if (mb_strlen($clean, 'UTF-8') <= 320) {
            return $clean;
        }

        return rtrim(mb_substr($clean, 0, 320, 'UTF-8')) . '...';
    }

    private function extractClaimContext($subject, $message) {
        $subjectLower = strtolower(trim((string)$subject));
        $isClaim = ($subjectLower === 'business-claim');
        $companyId = 0;
        $companySlug = '';

        if (preg_match('/Claim Company ID:\s*(\d+)/i', (string)$message, $matches)) {
            $companyId = (int)$matches[1];
            $isClaim = true;
        }

        if (preg_match('/Claim Company Slug:\s*([a-z0-9\-_]+)/i', (string)$message, $matches)) {
            $companySlug = (string)$matches[1];
            $isClaim = true;
        }

        return [
            'is_claim' => $isClaim,
            'company_id' => $companyId,
            'company_slug' => $companySlug
        ];
    }
    
    protected function getActionButtons($id, $module) {
        $actions = '';
        // Only show delete button if permitted, no edit/close
        if (granted_('delete', $module)) {
            $actions = ActionButtonHelper::deleteButton($id, $module);
        }
        return $actions;
    }
}
