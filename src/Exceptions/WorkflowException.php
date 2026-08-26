<?php

declare(strict_types=1);

namespace AIProductStudio\Exceptions;

/**
 * Thrown when a step of the product-generation pipeline fails.
 */
final class WorkflowException extends AIProductStudioException
{
    private string $step;

    public function __construct(string $message, string $step = '')
    {
        parent::__construct($message);
        $this->step = $step;
    }

    public function getStep(): string
    {
        return $this->step;
    }
}
