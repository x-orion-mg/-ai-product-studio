<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

/**
 * Outcome of a single step execution. The workflow engine decides whether to
 * continue, retry, or stop based on this value — steps never call each other.
 */
final class StepResult
{
    public const SUCCESS = 'success';
    public const FAILURE = 'failure';
    public const RETRY   = 'retry';
    public const SKIPPED = 'skipped';
    public const HALT    = 'halt';

    private function __construct(
        private readonly string $status,
        private readonly string $message = '',
        private readonly mixed $data = null
    ) {
    }

    public static function success(string $message = '', mixed $data = null): self
    {
        return new self(self::SUCCESS, $message, $data);
    }

    public static function failure(string $message, mixed $data = null): self
    {
        return new self(self::FAILURE, $message, $data);
    }

    public static function retry(string $message = '', mixed $data = null): self
    {
        return new self(self::RETRY, $message, $data);
    }

    public static function skipped(string $message = ''): self
    {
        return new self(self::SKIPPED, $message);
    }

    /**
     * Clean stop (e.g. cancellation). Not treated as an unexpected crash.
     */
    public static function halt(string $message = ''): self
    {
        return new self(self::HALT, $message);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function data(): mixed
    {
        return $this->data;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS || $this->status === self::SKIPPED;
    }

    public function shouldRetry(): bool
    {
        return $this->status === self::RETRY;
    }

    public function isFailure(): bool
    {
        return $this->status === self::FAILURE;
    }

    public function isHalt(): bool
    {
        return $this->status === self::HALT;
    }
}
