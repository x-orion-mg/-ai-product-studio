<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

use AIProductStudio\Exceptions\WorkflowException;

/**
 * Holds every business agent. The Core resolves agents by id only.
 */
final class AgentRegistry
{
    /** @var array<string, AgentInterface> */
    private array $agents = [];

    public function register(AgentInterface $agent): void
    {
        $this->agents[$agent->id()] = $agent;
    }

    public function has(string $id): bool
    {
        return isset($this->agents[$id]);
    }

    public function get(string $id): AgentInterface
    {
        if (! isset($this->agents[$id])) {
            throw new WorkflowException(
                sprintf(
                    /* translators: %s: agent id. */
                    __('Aucun agent enregistré pour l\'identifiant « %s ».', 'ai-product-studio'),
                    $id
                ),
                'registry'
            );
        }

        return $this->agents[$id];
    }

    /**
     * @return array<string, AgentInterface>
     */
    public function all(): array
    {
        return $this->agents;
    }
}
