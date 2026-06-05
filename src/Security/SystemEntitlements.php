<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Database;
use App\Core\Container;
use App\Core\DB;
use Throwable;

class SystemEntitlements
{
    private const SYSTEMS = ['crm', 'accounting', 'hr', 'shipping'];
    private const DEFAULT_FEATURES = [
        'crm_enabled' => '1',
        'accounting_enabled' => '1',
        'hr_enabled' => '1',
        'shipping_enabled' => '1',
        'can_create_organizations' => '1',
        'can_invite_members' => '1',
        'max_organizations' => '999',
        'max_team_members' => '999',
    ];

    /** @var array<string, bool> */
    private static array $tableExistsCache = [];

    /** @var array<string, array<string, bool>> */
    private static array $columnCache = [];

    public static function defaultEntitlements(): array
    {
        return [
            'crm' => true,
            'accounting' => true,
            'hr' => true,
            'shipping' => true,
        ];
    }

    private static function getDatabase(mixed $conn = null): Database
    {
        if ($conn instanceof Database) {
            return $conn;
        }

        try {
            $container = Container::getInstance();
            if ($container->has(Database::class)) {
                $resolved = $container->get(Database::class);
                if ($resolved instanceof Database) {
                    return $resolved;
                }
            }
        } catch (Throwable $e) {
            // Ignore container resolution errors
        }

        return new Database();
    }

    public static function resolveForDashboardUser(mixed $mysqli, array $dashboardSession = []): array
    {
        $snapshot = self::resolveFeatureSnapshotForDashboardUser($mysqli, $dashboardSession);
        return self::extractEntitlements($snapshot);
    }

    public static function resolveFeatureSnapshotForDashboardUser(mixed $mysqli, array $dashboardSession = []): array
    {
        $features = self::defaultFeatures();

        $roleId = (int)($dashboardSession['role_id'] ?? 0);
        if ($roleId === Roles::SYSTEM_ADMIN || $roleId === Roles::SUPER_ADMIN) {
            return $features;
        }

        $userId = (int)($dashboardSession['user_id'] ?? 0);
        if ($userId <= 0) {
            return $features;
        }

        $organizationId = (int)($dashboardSession['organization_id'] ?? 0);
        $resolvedFromData = false;

        $planId = self::findActivePlanId($mysqli, $userId);
        if ($planId > 0) {
            $planFeatures = self::loadPlanFeatures($mysqli, $planId);
            if (!empty($planFeatures)) {
                $features = array_replace($features, $planFeatures);
                $resolvedFromData = true;
            }
        }

        $overrideFeatures = self::loadOverrideFeatures($mysqli, $userId, $organizationId);
        if (!empty($overrideFeatures)) {
            $features = array_replace($features, $overrideFeatures);
            $resolvedFromData = true;
        }

        if (!$resolvedFromData) {
            return $features;
        }

        return self::normalizeFeatures($features);
    }

    public static function defaultFeatures(): array
    {
        return self::DEFAULT_FEATURES;
    }

    private static function findActivePlanId(mixed $mysqli, int $userId): int
    {
        $subscriptionsTable = self::tableName('subscriptions');
        if (!self::tableExists($mysqli, $subscriptionsTable)) {
            return 0;
        }

        $columns = self::getColumns($mysqli, $subscriptionsTable);
        $userColumn = self::pickFirstExistingColumn($columns, ['main_user_id', 'user_id', 'owner_user_id', 'account_user_id']);
        if ($userColumn === null || !isset($columns['plan_id'])) {
            return 0;
        }

        $statusColumn = isset($columns['status']) ? 'status' : null;
        $idOrderColumn = isset($columns['id']) ? 'id' : 'plan_id';

        if ($statusColumn !== null) {
            $sql = "SELECT plan_id FROM `{$subscriptionsTable}` WHERE `{$userColumn}` = ? AND `{$statusColumn}` IN ('active','trial') ORDER BY `{$idOrderColumn}` DESC LIMIT 1";
        } else {
            $sql = "SELECT plan_id FROM `{$subscriptionsTable}` WHERE `{$userColumn}` = ? ORDER BY `{$idOrderColumn}` DESC LIMIT 1";
        }

        $db = self::getDatabase($mysqli);
        try {
            $row = $db->fetchOne($sql, [$userId]);
            return (int)($row['plan_id'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    private static function loadPlanFeatures(mixed $mysqli, int $planId): array
    {
        $featuresTable = self::tableName('subscription_plan_features');
        if (!self::tableExists($mysqli, $featuresTable)) {
            return [];
        }

        $columns = self::getColumns($mysqli, $featuresTable);
        if (!isset($columns['plan_id']) || !isset($columns['feature_key'])) {
            return [];
        }

        $valueColumn = self::pickFirstExistingColumn($columns, ['feature_value', 'value', 'is_enabled', 'enabled']);
        if ($valueColumn === null) {
            return [];
        }

        $flatKeys = array_keys(self::defaultFeatures());
        $flatKeys = array_merge($flatKeys, ['system_crm_enabled', 'system_accounting_enabled', 'system_hr_enabled', 'system_shipping_enabled']);

        $placeholders = implode(',', array_fill(0, count($flatKeys), '?'));
        $sql = "SELECT feature_key, `{$valueColumn}` AS feature_value FROM `{$featuresTable}` WHERE plan_id = ? AND feature_key IN ({$placeholders})";

        $db = self::getDatabase($mysqli);
        try {
            $params = array_merge([$planId], $flatKeys);
            $rows = $db->fetchAll($sql, $params);

            $resolved = [];
            foreach ($rows as $row) {
                $key = (string)($row['feature_key'] ?? '');
                $value = $row['feature_value'] ?? null;

                if ($key === 'system_crm_enabled') {
                    $resolved['crm_enabled'] = self::toBool($value) ? '1' : '0';
                    continue;
                }
                if ($key === 'system_accounting_enabled') {
                    $resolved['accounting_enabled'] = self::toBool($value) ? '1' : '0';
                    continue;
                }
                if ($key === 'system_hr_enabled') {
                    $resolved['hr_enabled'] = self::toBool($value) ? '1' : '0';
                    continue;
                }
                if ($key === 'system_shipping_enabled') {
                    $resolved['shipping_enabled'] = self::toBool($value) ? '1' : '0';
                    continue;
                }

                if (array_key_exists($key, self::defaultFeatures())) {
                    $resolved[$key] = (string)$value;
                }
            }
            return $resolved;
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function loadOverrideFeatures(mixed $mysqli, int $userId, int $organizationId): array
    {
        $overridesTable = self::tableName('subscription_overrides');
        if (!self::tableExists($mysqli, $overridesTable)) {
            return [];
        }

        $columns = self::getColumns($mysqli, $overridesTable);

        $systemColumn = self::pickFirstExistingColumn($columns, ['system_key', 'feature_key', 'key_name']);
        $valueColumn = self::pickFirstExistingColumn($columns, ['override_value', 'feature_value', 'value', 'enabled']);
        $userColumn = self::pickFirstExistingColumn($columns, ['main_user_id', 'user_id', 'account_user_id']);
        if ($systemColumn === null || $valueColumn === null || $userColumn === null) {
            return [];
        }

        $where = ["`{$userColumn}` = ?"];
        $params = [$userId];

        if (isset($columns['organization_id']) && $organizationId > 0) {
            $where[] = "(`organization_id` IS NULL OR `organization_id` = ?)";
            $params[] = $organizationId;
        }

        if (isset($columns['expires_at'])) {
            $where[] = "(`expires_at` IS NULL OR `expires_at` > NOW())";
        }

        if (isset($columns['is_active'])) {
            $where[] = "`is_active` = 1";
        }

        $featureKeys = array_keys(self::defaultFeatures());
        $where[] = "`{$systemColumn}` IN (" . implode(',', array_fill(0, count($featureKeys), '?')) . ")";
        foreach ($featureKeys as $featureKey) {
            $params[] = $featureKey;
        }

        $sql = "SELECT `{$systemColumn}` AS system_key, `{$valueColumn}` AS system_value FROM `{$overridesTable}` WHERE " . implode(' AND ', $where);

        $db = self::getDatabase($mysqli);
        try {
            $rows = $db->fetchAll($sql, $params);
            $resolved = [];
            foreach ($rows as $row) {
                $featureKey = strtolower((string)($row['system_key'] ?? ''));
                if (array_key_exists($featureKey, self::defaultFeatures())) {
                    $resolved[$featureKey] = (string)($row['system_value'] ?? '');
                }
            }
            return $resolved;
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function normalizeFeatures(array $features): array
    {
        $normalized = self::defaultFeatures();
        foreach ($normalized as $featureKey => $defaultValue) {
            if (!array_key_exists($featureKey, $features)) {
                continue;
            }

            if (in_array($featureKey, ['crm_enabled', 'accounting_enabled', 'hr_enabled', 'shipping_enabled', 'can_create_organizations', 'can_invite_members'], true)) {
                $normalized[$featureKey] = self::toBool($features[$featureKey]) ? '1' : '0';
                continue;
            }

            $normalized[$featureKey] = (string)$features[$featureKey];
        }

        return $normalized;
    }

    private static function extractEntitlements(array $features): array
    {
        $normalized = self::defaultEntitlements();
        foreach (self::SYSTEMS as $system) {
            $featureKey = $system . '_enabled';
            $normalized[$system] = self::toBool($features[$featureKey] ?? false);
        }
        return $normalized;
    }

    private static function tableName(string $suffix): string
    {
        if (class_exists('DB')) {
            return DB::table($suffix);
        }
        return 'erp_' . $suffix;
    }

    private static function tableExists(mixed $mysqli, string $table): bool
    {
        if (isset(self::$tableExistsCache[$table])) {
            return self::$tableExistsCache[$table];
        }

        $db = self::getDatabase($mysqli);
        try {
            $sql = 'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1';
            $row = $db->fetchOne($sql, [$table]);
            $exists = !empty($row);
        } catch (Throwable $e) {
            $exists = false;
        }

        self::$tableExistsCache[$table] = $exists;
        return $exists;
    }

    private static function getColumns(mixed $mysqli, string $table): array
    {
        if (isset(self::$columnCache[$table])) {
            return self::$columnCache[$table];
        }

        $columns = [];
        $db = self::getDatabase($mysqli);
        try {
            $rows = $db->fetchAll("SHOW COLUMNS FROM `{$table}`");
            foreach ($rows as $row) {
                $name = strtolower((string)($row['Field'] ?? $row['field'] ?? ''));
                if ($name !== '') {
                    $columns[$name] = true;
                }
            }
        } catch (Throwable $e) {
            // ignore
        }

        self::$columnCache[$table] = $columns;
        return $columns;
    }

    private static function pickFirstExistingColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (isset($columns[strtolower($candidate)])) {
                return strtolower($candidate);
            }
        }
        return null;
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 'yes', 'on', 'enabled', 'active'], true);
    }
}
