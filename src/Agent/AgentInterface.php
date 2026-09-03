<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

/**
 * A business agent owns one task and the ordered list of steps that fulfil it.
 * The Core never depends on a concrete agent class.
 */
interface AgentInterface
{
    public function id(): string;

    public function name(): string;

    public function description(): string;

    /**
     * Declared input keys (documentation / validation hints).
     *
     * @return array<string, string>
     */
    public function inputs(): array;

    /**
     * Declared output keys.
     *
     * @return array<string, string>
     */
    public function outputs(): array;

    /**
     * Ordered workflow.
     *
     * @return array<int, StepInterface>
     */
    public function steps(): array;
}
