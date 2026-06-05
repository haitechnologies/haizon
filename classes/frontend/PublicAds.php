<?php

class PublicAds
{
    /** @var mysqli */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAdsForSlot($placementKey, array $context = [], $limit = 1)
    {
        $placementKey = trim((string)$placementKey);
        $limit = max(1, (int)$limit);
        if ($placementKey === '') {
            return [];
        }

        // Master switch: if ads are globally disabled, return nothing
        if (function_exists('getSystemSetting') && getSystemSetting('ads_master_enabled', '1') !== '1') {
            return [];
        }

        $sql = "
            SELECT
                id,
                campaign_name,
                placement_key,
                ad_format,
                title,
                description,
                cta_text,
                target_url,
                image_path,
                image_alt,
                badge_text,
                product_category,
                page_scope,
                keyword_tags,
                priority,
                weight,
                is_active,
                starts_at,
                ends_at,
                impression_count,
                click_count
            FROM `" . DB::PUBLIC_ADS . "`
            WHERE is_active = 1
              AND (placement_key = ? OR placement_key = 'global')
              AND (starts_at IS NULL OR starts_at <= NOW())
              AND (ends_at IS NULL OR ends_at >= NOW())
            ORDER BY priority DESC, weight DESC, id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('s', $placementKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $ads = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (empty($ads)) {
            return [];
        }

        $scoredAds = [];
        foreach ($ads as $ad) {
            $score = $this->calculateScore($ad, $placementKey, $context);
            $ad['smart_score'] = $score;
            $scoredAds[] = $ad;
        }

        usort($scoredAds, function ($left, $right) {
            if ($left['smart_score'] === $right['smart_score']) {
                if ((int)$left['priority'] === (int)$right['priority']) {
                    return (int)$right['weight'] <=> (int)$left['weight'];
                }
                return (int)$right['priority'] <=> (int)$left['priority'];
            }
            return (int)$right['smart_score'] <=> (int)$left['smart_score'];
        });

        $selectedAds = $this->selectRotatedAds($scoredAds, $placementKey, $context, $limit);
        $this->trackImpressions($selectedAds);

        return $selectedAds;
    }

    /**
     * Rotate selected ads within a relevance-preserving pool.
     * Keeps top scoring ads eligible while avoiding the same ad every request.
     */
    private function selectRotatedAds(array $scoredAds, string $placementKey, array $context, int $limit): array
    {
        if (count($scoredAds) <= $limit) {
            return array_slice($scoredAds, 0, $limit);
        }

        // Keep a small high-quality pool for rotation instead of the full list.
        $poolSize = min(count($scoredAds), max($limit * 4, 4));
        $pool = array_slice($scoredAds, 0, $poolSize);

        // Stable hourly rotation seed by slot + normalized context.
        $normalizedContext = $this->normalizeContextForSeed($context);
        $seedInput = $placementKey . '|' . json_encode($normalizedContext) . '|' . date('YmdH');
        $seed = abs(crc32($seedInput));
        $start = $poolSize > 0 ? ($seed % $poolSize) : 0;

        $selected = [];
        for ($i = 0; $i < $limit; $i++) {
            $index = ($start + $i) % $poolSize;
            $selected[] = $pool[$index];
        }

        return $selected;
    }

    private function normalizeContextForSeed(array $context): array
    {
        $normalized = $context;
        ksort($normalized);

        foreach ($normalized as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = array_values($value);
                sort($normalized[$key]);
            } else {
                $normalized[$key] = (string)$value;
            }
        }

        return $normalized;
    }

    public function getAdById($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT * FROM `" . DB::PUBLIC_ADS . "` WHERE id = ? LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    public function incrementClick($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE `" . DB::PUBLIC_ADS . "` SET click_count = click_count + 1 WHERE id = ?"
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    private function calculateScore(array $ad, $placementKey, array $context)
    {
        $score = 0;
        $score += ((int)($ad['priority'] ?? 0)) * 10;
        $score += ((int)($ad['weight'] ?? 0)) * 3;

        if (($ad['placement_key'] ?? '') === $placementKey) {
            $score += 40;
        }

        $pageType = strtolower(trim((string)($context['page_type'] ?? '')));
        $pageScopeTokens = $this->tokenize((string)($ad['page_scope'] ?? ''));
        if ($pageType !== '' && in_array($pageType, $pageScopeTokens, true)) {
            $score += 30;
        }

        $contextTokens = [];
        foreach (['keyword', 'category', 'city', 'topic', 'tags'] as $field) {
            if (!isset($context[$field])) {
                continue;
            }
            if (is_array($context[$field])) {
                foreach ($context[$field] as $value) {
                    $contextTokens = array_merge($contextTokens, $this->tokenize((string)$value));
                }
            } else {
                $contextTokens = array_merge($contextTokens, $this->tokenize((string)$context[$field]));
            }
        }
        $contextTokens = array_values(array_unique($contextTokens));

        $keywordTokens = $this->tokenize((string)($ad['keyword_tags'] ?? ''));
        $matchedTags = array_intersect($keywordTokens, $contextTokens);
        $score += count($matchedTags) * 12;

        $productCategory = strtolower(trim((string)($ad['product_category'] ?? '')));
        if ($productCategory === 'software') {
            $score += 8;
        }

        if (!empty($ad['image_path']) && in_array($placementKey, ['home_hero', 'trade_feature', 'global_footer'], true)) {
            $score += 6;
        }

        return $score;
    }

    private function tokenize($value)
    {
        $value = strtolower(trim((string)$value));
        if ($value === '') {
            return [];
        }

        $value = preg_replace('/[^a-z0-9\s,-]+/', ' ', $value);
        $parts = preg_split('/[\s,|-]+/', $value);
        $parts = array_filter(array_map('trim', $parts));

        return array_values(array_unique($parts));
    }

    private function trackImpressions(array $ads)
    {
        $ids = [];
        foreach ($ads as $ad) {
            $id = (int)($ad['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if (empty($ids)) {
            return;
        }

        $ids = array_values(array_unique($ids));
        $idList = implode(',', $ids);
        $this->conn->query("UPDATE `" . DB::PUBLIC_ADS . "` SET impression_count = impression_count + 1 WHERE id IN ($idList)");
    }
}
