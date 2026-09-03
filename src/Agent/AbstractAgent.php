<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

/**
 * Convenience base for business agents: identity is defined by the subclass,
 * the workflow is the injected step list.
 */
abstract class AbstractAgent implements AgentInterface
{
    /**
     * @param array<int, StepInterface> $steps
     */
    public function __construct(private readonly array $steps)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function steps(): array
    {
        return $this->steps;
    }
}
