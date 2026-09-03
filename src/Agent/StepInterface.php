<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

/**
 * A single unit of work in an agent workflow. Steps communicate only through
 * {@see AgentContext} and must not depend on sibling steps.
 */
interface StepInterface
{
    /**
     * Stable machine id used for progress reporting and logs.
     */
    public function id(): string;

    /**
     * Human-readable label shown in the progress UI.
     */
    public function label(): string;

    /**
     * How many extra attempts the engine may make after a retryable failure.
     */
    public function maxRetries(): int;

    /**
     * Execute the step, reading and writing shared context.
     */
    public function execute(AgentContext $context): StepResult;
}
