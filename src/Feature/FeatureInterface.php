<?php

declare(strict_types=1);

namespace AIProductStudio\Feature;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Core\Container;
use AIProductStudio\Form\FormInterface;

/**
 * A Feature is the unit a developer adds: menu, form, default agent, result.
 * Core never imports a concrete Feature class.
 */
interface FeatureInterface
{
    public function id(): string;

    public function slug(): string;

    public function title(): string;

    public function menuTitle(): string;

    public function description(): string;

    public function menuPosition(): int;

    public function capability(): string;

    public function defaultAgentId(): string;

    /**
     * @return array<int, string>
     */
    public function agentIds(): array;

    public function form(): FormInterface;

    /**
     * When false, the Feature renders its own admin page (legacy product UI).
     */
    public function usesGenericUi(): bool;

    public function render(Container $container): void;

    /**
     * Copy sanitized form fields into the shared agent context.
     *
     * @param array<string, mixed> $fields
     */
    public function hydrateContext(AgentContext $context, array $fields): void;

    /**
     * JSON payload returned to the generic admin script after a successful run.
     *
     * @return array<string, mixed>
     */
    public function present(AgentContext $context): array;

    public function entityId(AgentContext $context): int;

    public function historyMessage(AgentContext $context): string;
}
