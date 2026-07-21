<?php

declare(strict_types=1);

namespace AIProductStudio\Exceptions;

/**
 * Thrown when the AI response (or user input) fails validation.
 */
final class ValidationException extends AIProductStudioException
{
    /** @var array<int, string> */
    private array $errors;

    /**
     * @param array<int, string> $errors
     */
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
