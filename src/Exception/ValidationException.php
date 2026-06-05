<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Validation Exception
 *
 * Thrown when data validation constraints are violated.
 */
class ValidationException extends DomainException
{
    private array $errors;

    public function __construct(array $errors, string $message = "Validation constraints failed", int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * Retrieve the list of validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
