<?php
/**
 * SearchesDataTable Handler
 */

require_once __DIR__ . '/BaseDataTable.php';
require_once __DIR__ . '/../BadgeHelper.php';

class SearchesDataTable extends BaseDataTable {
    protected $table = DB::SEARCHES;
    protected $searchFields = ['search_query', 'ip_address'];
    protected $sortableColumns = [
        0 => 'created_at', 1 => 'search_query', 2 => 'ip_address', 3 => 'id', 4 => 'result_count'
    ];

    protected function formatRow($row, $requestData = []) {
        $id = (int)$row['id'];
        $searchQuery = s__($row['search_query'] ?? '');
        // Prefer unified result_count; fallback to legacy results_found when needed.
        if (array_key_exists('result_count', $row) && $row['result_count'] !== null) {
            $resultsFound = (int)$row['result_count'];
        } elseif (array_key_exists('results_found', $row) && $row['results_found'] !== null) {
            $resultsFound = (int)$row['results_found'];
        } else {
            $resultsFound = 0;
        }
        $ipAddress = s__($row['ip_address'] ?? '');
        $createdAt = $row['created_at'] ?? '';

        // Lookup country from IP address
        $countryName = 'N/A';
        if (!empty($ipAddress)) {
            $ipLong = ip2long($ipAddress);
            if ($ipLong !== false) {
                $sqlCountry = "SELECT country_name FROM " . DB::IP_COUNTRIES . " WHERE {$ipLong} BETWEEN ip_start AND ip_end LIMIT 1";
                $resultCountry = $this->mysqli->query($sqlCountry);
                if ($resultCountry && $resultCountry->num_rows > 0) {
                    $rowCountry = $resultCountry->fetch_assoc();
                    $countryName = $rowCountry['country_name'] ?? 'N/A';
                }
            }
        }
        $countryName = s__($countryName);

        // New order: Date, Search Query, IP, Country, Results
        return [
            '<span style="font-size: 0.675rem;" title="' . htmlspecialchars($createdAt) . '">' . dd__($createdAt) . '</span>',
            '<a href="/trade/hs-codes?search=' . rawurlencode($searchQuery) . '" target="_blank" rel="noopener" class="fw-semibold" title="Search: ' . htmlspecialchars($searchQuery) . '">' . htmlspecialchars($searchQuery) . '</a>',
            htmlspecialchars($ipAddress),
            BadgeHelper::info($countryName),
            BadgeHelper::secondary(number_format($resultsFound))
        ];
    }
}
