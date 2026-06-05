<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Domain Exception
 * 
 * Base exception class for all business logic/domain errors.
 * Helps prevent type coercion and returning false/null on failure.
 */
class DomainException extends RuntimeException
{
}
