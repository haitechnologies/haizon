<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;
use mysqli_result;
use mysqli_stmt;

/**
 * Dynamic Table Prefix MySQLi Subclass
 *
 * Intercepts MySQLi query execution and statement preparation to rewrite table prefixes at runtime.
 */
class DynamicPrefixMysqli extends mysqli
{
    /**
     * @param string $query
     * @param int $resultMode
     * @return mysqli_result|bool
     */
    #[\ReturnTypeWillChange]
    public function query(string $query, int $resultMode = MYSQLI_STORE_RESULT)
    {
        $query = DynamicPrefixRewriter::rewrite($query);
        return parent::query($query, $resultMode);
    }

    /**
     * @param string $query
     * @return mysqli_stmt|false
     */
    #[\ReturnTypeWillChange]
    public function prepare(string $query)
    {
        $query = DynamicPrefixRewriter::rewrite($query);
        return parent::prepare($query);
    }
}
