<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Dynamic Table Prefix PDO Subclass
 *
 * Intercepts SQL statement preparation and execution to rewrite table prefixes at runtime.
 */
class DynamicPrefixPdo extends PDO
{
    /**
     * @param string $query
     * @param array<mixed> $options
     * @return PDOStatement|false
     */
    #[\ReturnTypeWillChange]
    public function prepare(string $query, array $options = [])
    {
        $query = DynamicPrefixRewriter::rewrite($query);
        return parent::prepare($query, $options);
    }

    /**
     * @param string $query
     * @param int|null $fetchMode
     * @param mixed ...$fetchModeArgs
     * @return PDOStatement|false
     */
    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, ...$fetchModeArgs)
    {
        $query = DynamicPrefixRewriter::rewrite($query);
        // PHP's parent::query expects arguments matching its signatures
        if ($fetchMode === null) {
            return parent::query($query);
        }
        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    /**
     * @param string $statement
     * @return int|false
     */
    #[\ReturnTypeWillChange]
    public function exec(string $statement)
    {
        $statement = DynamicPrefixRewriter::rewrite($statement);
        return parent::exec($statement);
    }
}
