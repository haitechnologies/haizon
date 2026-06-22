<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\Database;
use App\Core\DB;

class GlobalSettingsService
{
    private Database $db;

    private const COLOR_SLUGS = [
        'admin_header_bg_color', 'admin_header_text_color', 'admin_header_accent_color',
        'sidebar_bg_color', 'sidebar_text_color', 'sidebar_active_bg_color',
        'sidebar_active_text_color', 'sidebar_hover_bg_color',
        'login_header_bg_color', 'login_header_text_color', 'login_form_bg_color',
        'login_button_bg_color', 'login_button_text_color', 'login_button_hover_color',
    ];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function updateSettings(array $data): int
    {
        $updatedCount = 0;

        $textSlugs = [
            'software_name', 'company_name', 'phone', 'email', 'website', 'trn',
            'street1', 'street2', 'city', 'pobox', 'country',
            'social_fb', 'social_x', 'social_insta', 'social_gmb',
            'global_settings', 'sitemap_root', 'seo_ai_policy_mode',
            'seo_meta_title', 'seo_meta_description', 'seo_meta_keywords',
            'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_og_type', 'seo_og_url',
            'seo_twitter_card', 'seo_twitter_site', 'seo_twitter_creator',
            'seo_google_analytics', 'seo_google_tag_manager', 'seo_google_site_verification',
            'seo_bing_verification', 'seo_robots_meta', 'seo_canonical_url', 'seo_schema_organization',
            'bank_name', 'beneficiary', 'account_number', 'iban',
        ];

        $intSlugs = [
            'login_captcha_threshold', 'sitemap_enabled', 'ai_sitemap_enabled',
            'sitemap_companies', 'sitemap_blogs', 'sitemap_categories',
            'sitemap_hs_codes', 'sitemap_amp', 'seo_hsts_required',
        ];

        foreach ($textSlugs as $slug) {
            if (isset($data[$slug])) {
                $this->upsertSetting($slug, (string)$data[$slug]);
                $updatedCount++;
            }
        }

        foreach ($intSlugs as $slug) {
            if (isset($data[$slug])) {
                $this->upsertSetting($slug, (string)((int)$data[$slug]));
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    public function updateColors(array $data): int
    {
        $updatedCount = 0;
        foreach (self::COLOR_SLUGS as $slug) {
            if (!isset($data[$slug])) {
                continue;
            }
            $color = $this->sanitizeColorInput((string)$data[$slug]);
            if ($this->isValidHexColor($color)) {
                $settingName = ucwords(str_replace('_', ' ', $slug));
                $hint = 'Auto-created from Global Settings (UI color setting).';

                $existing = $this->db->fetchOne(
                    "SELECT id FROM `{DB::SYSTEM_SETTINGS}` WHERE setting_slug = :slug LIMIT 1",
                    ['slug' => $slug]
                );

                if ($existing) {
                    $this->db->execute(
                        "UPDATE `{DB::SYSTEM_SETTINGS}` SET setting_value = :value WHERE setting_slug = :slug",
                        ['value' => $color, 'slug' => $slug]
                    );
                } else {
                    $this->db->execute(
                        "INSERT INTO `{DB::SYSTEM_SETTINGS}` (setting_slug, setting_name, setting_value, hint, is_active, created_by, updated_by, created_at, updated_at)
                         VALUES (:slug, :name, :value, :hint, 1, 1, 1, NOW(), NOW())",
                        ['slug' => $slug, 'name' => $settingName, 'value' => $color, 'hint' => $hint]
                    );
                }
                $updatedCount++;
            }
        }
        return $updatedCount;
    }

    public function deleteSettingValue(string $settingKey): bool
    {
        $sql = "UPDATE `{DB::SYSTEM_SETTINGS}` SET setting_value = '' WHERE setting_slug = :slug";
        $stmt = $this->db->execute($sql, ['slug' => $settingKey]);
        return $stmt->rowCount() > 0;
    }

    public function getSettingValue(string $settingKey): ?string
    {
        $row = $this->db->fetchOne(
            "SELECT setting_value FROM `{DB::SYSTEM_SETTINGS}` WHERE setting_slug = :slug LIMIT 1",
            ['slug' => $settingKey]
        );
        return $row !== null ? (string)$row['setting_value'] : null;
    }

    private function upsertSetting(string $slug, string $value): void
    {
        $settingName = ucwords(str_replace('_', ' ', $slug));
        $hint = 'Auto-created from Global Settings.';

        $existing = $this->db->fetchOne(
            "SELECT id FROM `{DB::SYSTEM_SETTINGS}` WHERE setting_slug = :slug LIMIT 1",
            ['slug' => $slug]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE `{DB::SYSTEM_SETTINGS}` SET setting_value = :value WHERE setting_slug = :slug",
                ['value' => $value, 'slug' => $slug]
            );
        } else {
            $this->db->execute(
                "INSERT INTO `{DB::SYSTEM_SETTINGS}` (setting_slug, setting_name, setting_value, hint, is_active, created_by, updated_by, created_at, updated_at)
                 VALUES (:slug, :name, :value, :hint, 1, 1, 1, NOW(), NOW())",
                ['slug' => $slug, 'name' => $settingName, 'value' => $value, 'hint' => $hint]
            );
        }
    }

    private function sanitizeColorInput(string $color): string
    {
        $color = trim($color);
        if ($color !== '' && $color[0] === '#') {
            $color = substr($color, 1);
        }
        return '#' . strtoupper($color);
    }

    private function isValidHexColor(string $color): bool
    {
        return preg_match('/^#[0-9A-F]{6}$/i', $color) === 1;
    }
}
