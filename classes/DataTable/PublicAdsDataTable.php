<?php
/**
 * PublicAdsDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';

class PublicAdsDataTable extends BaseDataTable {
    protected $table = DB::PUBLIC_ADS;
    protected $searchFields = ['campaign_name', 'placement_key', 'ad_format', 'title', 'target_url'];
    protected $sortableColumns = [
        0 => 'id',
        2 => 'campaign_name',
        3 => 'placement_key',
        4 => 'ad_format',
        5 => 'is_active',
        6 => 'impression_count',
        7 => 'click_count',
        8 => 'priority',
        9 => 'updated_at'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)($row['id'] ?? 0);
        $imagePath = trim((string)($row['image_path'] ?? ''));
        $imageAlt = trim((string)($row['image_alt'] ?? $row['title'] ?? 'Ad image'));
        $campaign = (string)($row['campaign_name'] ?? '');
        $title = (string)($row['title'] ?? '');
        $placement = (string)($row['placement_key'] ?? '');
        $format = (string)($row['ad_format'] ?? '');
        $isActive = (int)($row['is_active'] ?? 0) === 1;
        $impressions = (int)($row['impression_count'] ?? 0);
        $clicks = (int)($row['click_count'] ?? 0);
        $priority = (int)($row['priority'] ?? 0);
        $weight = (int)($row['weight'] ?? 0);
        $updatedAt = (string)($row['updated_at'] ?? $row['created_at'] ?? '');

        $csrfToken = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        $statusBadge = $isActive
            ? '<span class="badge bg-success js-toggle-ad-status" data-ad-id="' . $id . '" data-csrf="' . $csrfToken . '" title="Click to deactivate" style="cursor:pointer;">Active</span>'
            : '<span class="badge bg-secondary js-toggle-ad-status" data-ad-id="' . $id . '" data-csrf="' . $csrfToken . '" title="Click to activate" style="cursor:pointer;">Inactive</span>';

        $campaignCol = '<strong>' . htmlspecialchars($campaign, ENT_QUOTES, 'UTF-8') . '</strong>';
        if ($title !== '') {
            $campaignCol .= '<br><small class="text-muted">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</small>';
        }

        $thumbnailCol = $this->buildThumbnailHtml($imagePath, $imageAlt);

        return [
            $id,
            $thumbnailCol,
            $campaignCol,
            htmlspecialchars($placement, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($format, ENT_QUOTES, 'UTF-8'),
            $statusBadge,
            number_format($impressions),
            number_format($clicks),
            $priority . '/' . $weight,
            $updatedAt !== '' ? dd_($updatedAt, 'd M Y g:ia') : '-',
            $this->getActionButtons($id)
        ];
    }

    private function buildThumbnailHtml(string $imagePath, string $imageAlt): string
    {
        if ($imagePath === '') {
            return '<span class="badge bg-light text-muted border">No image</span>';
        }

        $src = $imagePath;
        if (!preg_match('#^(https?:)?//#i', $imagePath) && strpos($imagePath, 'data:') !== 0) {
            $src = '../' . ltrim($imagePath, '/');
        }

        $escapedSrc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
        $escapedAlt = htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8');

        return '<a href="#" class="js-ad-thumb" data-image="' . $escapedSrc . '" data-alt="' . $escapedAlt . '" title="Preview image">'
            . '<img src="' . $escapedSrc . '" alt="' . $escapedAlt . '" class="rounded border" style="width:48px;height:48px;object-fit:cover;">'
            . '</a>';
    }

    protected function buildOrderClause($requestData)
    {
        $orderColumn = (int)($requestData['order'][0]['column'] ?? 0);
        $orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'DESC');

        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $column = $this->sortableColumns[$orderColumn] ?? 'id';
        return 'ORDER BY ' . $column . ' ' . $orderDir;
    }

    protected function getActionButtons(int $id): string
    {
        $buttons = [];
        $csrfToken = urlencode(csrf_token());

        if (granted_('delete', 'public_ads')) {
            $buttons[] = '<a class="btn btn-sm btn-outline-danger" href="listing_public_ads.php?action=delete_public_ads&id=' . $id . '&csrf_token=' . $csrfToken . '" onclick="return confirm(\'Delete this ad?\');">Delete</a>';
        }

        return implode(' ', $buttons);
    }
}

