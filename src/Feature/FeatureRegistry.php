<?php

declare(strict_types=1);

namespace AIProductStudio\Feature;

use AIProductStudio\Exceptions\WorkflowException;

/**
 * Holds every Feature. The Core resolves Features by id only.
 */
final class FeatureRegistry
{
    /** @var array<string, FeatureInterface> */
    private array $features = [];

    public function register(FeatureInterface $feature): void
    {
        $this->features[$feature->id()] = $feature;
    }

    public function has(string $id): bool
    {
        return isset($this->features[$id]);
    }

    public function get(string $id): FeatureInterface
    {
        if (! isset($this->features[$id])) {
            throw new WorkflowException(
                sprintf(
                    /* translators: %s: feature id. */
                    __('Aucune fonctionnalité enregistrée pour l\'identifiant « %s ».', 'ai-product-studio'),
                    $id
                ),
                'feature-registry'
            );
        }

        return $this->features[$id];
    }

    /**
     * @return array<string, FeatureInterface>
     */
    public function all(): array
    {
        $features = $this->features;
        uasort(
            $features,
            static fn (FeatureInterface $a, FeatureInterface $b): int => $a->menuPosition() <=> $b->menuPosition()
        );

        return $features;
    }
}
