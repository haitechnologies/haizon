<?php
/**
 * EmailHistoryDataTable Handler
 * 
 * Email send history tracking with open/click monitoring
 * Complex: Campaign join, status badges, tracking icons
 */

require_once __DIR__ . '/BaseDataTable.php';

class EmailHistoryDataTable extends BaseDataTable {
    protected $table = DB::EMAIL_HISTORY;
    protected $searchFields = ['recipient_email', 'status', 'from_name', 'from_email', 'subject'];
    protected $sortableColumns = [
        0 => 'id', 1 => 'campaign_id', 2 => 'source_label', 3 => 'recipient_email', 4 => 'status', 
        5 => 'sent_at', 6 => 'opened_at', 7 => 'clicked_at', 8 => 'created_at'
    ];

    protected function buildBaseQuery($requestData)
    {
        return "SELECT 
                    eh.*,
                    CASE
                        WHEN du.id IS NOT NULL OR (eh.campaign_id IS NOT NULL AND eh.campaign_id > 0) THEN 'Dashboard'
                        WHEN fu.id IS NOT NULL THEN 'Website'
                        ELSE 'Website'
                    END AS source_label
                FROM `" . $this->table . "` eh
                LEFT JOIN `" . DB::USERS . "` du ON du.id = eh.user_id
                LEFT JOIN `" . DB::FRONTEND_USERS . "` fu ON fu.id = eh.user_id
                WHERE eh.id > 0";
    }

    /**
     * OPTIMIZATION: Pre-fetch campaign names to prevent N+1 queries
     */
    protected function prepareRelatedData(array $rows, array $requestData = []): void
    {
        $campaignIds = array_filter(array_map(fn($r) => (int)($r['campaign_id'] ?? 0), $rows));
        
        if (empty($campaignIds)) {
            return;
        }

        $idList = implode(',', array_unique($campaignIds));

        // OPTIMIZATION: Fetch all campaign names in ONE query
        $campaignQuery = "
            SELECT id, name 
            FROM " . DB::EMAIL_CAMPAIGNS . " 
            WHERE id IN ({$idList})
        ";
        
        $this->relatedDataCache['campaigns'] = [];
        $result = $this->mysqli->query($campaignQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->relatedDataCache['campaigns'][(int)$row['id']] = $row['name'] ?? '-';
            }
        }
    }

    protected function formatRow($row, $requestData = []) {
        global $mysqli;
        
        $id = (int)$row['id'];
        $campaignId = (int)$row['campaign_id'];
        $recipientEmail = s__($row['recipient_email'] ?? '');
        $status = s__($row['status'] ?? '');
        $sourceLabel = (string)($row['source_label'] ?? 'Website');
        $sentAt = $row['sent_at'] ?? '';
        $openedAt = $row['opened_at'] ?? '';
        $clickedAt = $row['clicked_at'] ?? '';
        $createdAt = $row['created_at'] ?? '';
        
        // OPTIMIZATION: Use pre-fetched campaign name instead of per-row query
        $campaignName = $campaignId > 0 && isset($this->relatedDataCache['campaigns'][$campaignId])
            ? $this->relatedDataCache['campaigns'][$campaignId]
            : '-';
        
        $statusBadge = match($status) {
            'queued' => '<span class="badge bg-secondary bg-opacity-20 text-secondary">Queued</span>',
            'sent' => '<span class="badge bg-success bg-opacity-20 text-success">Sent</span>',
            'failed' => '<span class="badge bg-danger bg-opacity-20 text-danger">Failed</span>',
            'bounced' => '<span class="badge bg-warning bg-opacity-20 text-warning">Bounced</span>',
            'unsubscribed' => '<span class="badge bg-info bg-opacity-20 text-info">Unsubscribed</span>',
            default => '<span class="badge bg-secondary bg-opacity-20 text-secondary">' . ucfirst($status) . '</span>'
        };
        
        $openedIcon = !empty($openedAt) ? '<i class="ph-check text-success"></i>' : '-';
        $clickedIcon = !empty($clickedAt) ? '<i class="ph-check text-success"></i>' : '-';

        $sourceBadge = $sourceLabel === 'Dashboard'
            ? '<span class="badge bg-primary bg-opacity-20 text-primary">Dashboard</span>'
            : '<span class="badge bg-info bg-opacity-20 text-info">Website</span>';
        
        return [
            $id,
            htmlspecialchars($campaignName),
            $sourceBadge,
            '<a href="mailto:' . htmlspecialchars($recipientEmail) . '">' . htmlspecialchars($recipientEmail) . '</a>',
            $statusBadge,
            !empty($sentAt) ? timeAgo($sentAt) : '-',
            $openedIcon,
            $clickedIcon,
            timeAgo($createdAt)
        ];
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? count($this->sortableColumns) - 2);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }
}
