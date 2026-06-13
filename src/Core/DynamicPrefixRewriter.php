<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Dynamic Database Table Prefix Rewriter
 *
 * Rewrites table names in SQL queries from the base prefix ('erp_')
 * to the configured runtime prefix, ignoring quoted literals.
 */
class DynamicPrefixRewriter
{
    /** @var array<string>|null */
    private static ?array $tables = null;

    private static ?string $tablesPattern = null;

    /**
     * Rewrite table prefix in SQL queries from 'erp_' to configured prefix
     */
    public static function rewrite(string $sql): string
    {
        // Resolve DB::CONST and {DB::CONST} references in query string
        $sql = preg_replace_callback('/(?:\{?DB::([A-Z0-9_]+)\}?)/', function (array $matches): string {
            $constantName = $matches[1];
            $ref = 'App\Core\DB::' . $constantName;
            if (defined($ref)) {
                return (string)constant($ref);
            }
            return $matches[0];
        }, $sql);

        // Translate utf8_general_ci to utf8mb4_general_ci to match connection encoding
        $sql = str_ireplace('utf8_general_ci', 'utf8mb4_general_ci', $sql);

        $prefix = class_exists(DB::class) ? DB::getPrefix() : 'erp_';

        if (self::$tables === null) {
            self::initTables();
        }

        if (self::$tablesPattern === '') {
            return $sql;
        }

        // Pattern matches double quoted strings, single quoted strings, or the table names prefixed with erp_ or fls_
        $pattern = '/"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'|\b(?:erp_|fls_)(' . self::$tablesPattern . ')\b/';

        $result = preg_replace_callback($pattern, function (array $matches) use ($prefix): string {
            $match = $matches[0];
            // If it matched a quoted string, return it unchanged (starts with " or ')
            if ($match[0] === '"' || $match[0] === "'") {
                return $match;
            }
            // Otherwise, it's a table name. Replace prefix 'erp_' or 'fls_' with the configured prefix
            return preg_replace('/^(?:erp_|fls_)/', $prefix, $match);
        }, $sql);

        return $result ?? $sql;
    }

    /**
     * Rewrite table names inside parameter values if they are database table constants
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public static function rewriteParams(array $params): array
    {
        $prefix = class_exists(DB::class) ? DB::getPrefix() : 'erp_';

        if (self::$tables === null) {
            self::initTables();
        }

        foreach ($params as $key => $value) {
            if (is_string($value)) {
                if (strpos($value, 'erp_') === 0) {
                    if (in_array($value, self::$tables, true)) {
                        $params[$key] = str_replace('erp_', $prefix, $value);
                    }
                } elseif (strpos($value, 'fls_') === 0) {
                    $baseVal = str_replace('fls_', 'erp_', $value);
                    if (in_array($baseVal, self::$tables, true)) {
                        $params[$key] = str_replace('fls_', $prefix, $value);
                    }
                }
            }
        }

        return $params;
    }

    /**
     * Initialize registered tables list from DB registry
     */
    private static function initTables(): void
    {
        if (class_exists(DB::class)) {
            $tables = DB::getAllTables();
            // Sort tables by length descending to prevent partial match issues (e.g. erp_leave_requests before erp_leave)
            usort($tables, fn(string $a, string $b): int => strlen($b) - strlen($a));
            self::$tables = $tables;
            
            // Extract base names without prefix erp_
            $baseTables = array_map(fn(string $t): string => preg_replace('/^erp_/', '', $t), $tables);
            usort($baseTables, fn(string $a, string $b): int => strlen($b) - strlen($a));
            self::$tablesPattern = implode('|', array_map('preg_quote', $baseTables));
        } else {
            self::$tables = [];
            self::$tablesPattern = '';
        }
    }
}
