<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

/**
 * Shared bag passed through every step of an agent run. Steps read and write
 * keys here instead of calling each other.
 */
final class AgentContext
{
    /** @var array<string, mixed> */
    private array $data = [];

    private float $startedAt;

    /**
     * @param array<string, mixed> $input
     */
    public function __construct(
        private readonly string $agentId,
        private readonly string $jobId,
        array $input = []
    ) {
        $this->data      = $input;
        $this->startedAt = microtime(true);
    }

    public function agentId(): string
    {
        return $this->agentId;
    }

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function elapsed(): float
    {
        return round(microtime(true) - $this->startedAt, 2);
    }
}
