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
        array $input = [],
        private readonly string $featureId = '',
        private readonly string $provider = '',
        private readonly string $model = ''
    ) {
        $this->data      = $input;
        $this->startedAt = microtime(true);

        if ($this->provider !== '') {
            $this->data['provider'] = $this->provider;
        }

        if ($this->model !== '') {
            $this->data['model'] = $this->model;
        }

        if ($this->featureId !== '') {
            $this->data['feature_id'] = $this->featureId;
        }
    }

    public function agentId(): string
    {
        return $this->agentId;
    }

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function featureId(): string
    {
        return $this->featureId;
    }

    public function provider(): string
    {
        return $this->provider !== '' ? $this->provider : (string) ($this->data['provider'] ?? '');
    }

    public function model(): string
    {
        return $this->model !== '' ? $this->model : (string) ($this->data['model'] ?? '');
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
