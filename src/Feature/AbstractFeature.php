<?php

declare(strict_types=1);

namespace AIProductStudio\Feature;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Core\Container;
use AIProductStudio\Form\Form;
use AIProductStudio\Form\FormInterface;

abstract class AbstractFeature implements FeatureInterface
{
    public function slug(): string
    {
        return $this->id();
    }

    public function description(): string
    {
        return '';
    }

    public function menuPosition(): int
    {
        return 20;
    }

    public function capability(): string
    {
        return 'manage_woocommerce';
    }

    /**
     * {@inheritDoc}
     */
    public function agentIds(): array
    {
        return [ $this->defaultAgentId() ];
    }

    public function usesGenericUi(): bool
    {
        return true;
    }

    public function render(Container $container): void
    {
    }

    public function form(): FormInterface
    {
        return new Form([]);
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateContext(AgentContext $context, array $fields): void
    {
        $context->set('fields', $fields);

        foreach ($fields as $key => $value) {
            $context->set((string) $key, $value);
        }
    }

    public function entityId(AgentContext $context): int
    {
        return (int) $context->get('entity_id', $context->get('product_id', $context->get('post_id', 0)));
    }

    public function historyMessage(AgentContext $context): string
    {
        return (string) $context->get('history_message', $this->title());
    }

    /**
     * {@inheritDoc}
     */
    public function present(AgentContext $context): array
    {
        return [
            'title'     => $this->title(),
            'message'   => $this->historyMessage($context),
            'html'      => '',
            'edit_link' => '',
            'view_link' => '',
            'entity_id' => $this->entityId($context),
        ];
    }
}
