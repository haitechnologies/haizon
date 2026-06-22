<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/globals.php';

use App\Core\Database;

/**
 * Shared test bootstrap.
 * Provides a fresh Database instance for each test file.
 * Define the $db variable globally so tests can use it directly.
 */
global $db;
$db = new Database();
