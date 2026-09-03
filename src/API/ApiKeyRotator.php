<?php

declare(strict_types=1);

namespace AIProductStudio\API;

use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Services\Settings;

/**
 * Provides the ordered list of usable API keys for a provider and records the
 * outcome (success/failure) so the pool self-heals over time.
 *
 * Rotation strategy: keys are tried by ascending priority, then by fewest
 * accumulated errors, then by least-recently used. A key that reaches the
 * configured error threshold is automatically deactivated.
 */
final class ApiKeyRotator
{
    private ApiKeyRepository $repository;

    private Settings $settings;

    public function __construct(ApiKeyRepository $repository, Settings $settings)
    {
        $this->repository = $repository;
        $this->settings   = $settings;
    }

    /**
     * Return the ordered candidate keys for a provider.
     *
     * @return array<int, ApiKey>
     *
     * @throws ProviderException when no active key exists.
     */
    /**
     * @return array<int, ApiKey>
     */
    public function candidates(string $provider, string $model = ''): array
    {
        $keys = $this->repository->activeForProvider($provider);

        if ($keys === []) {
            throw new ProviderException(
                sprintf(
                    /* translators: %s: provider slug. */
                    __('Aucune clé API active pour le fournisseur « %s ». Ajoutez-en une dans l\'onglet API.', 'ai-product-studio'),
                    $provider
                )
            );
        }

        if ($model !== '') {
            $matched = array_values(
                array_filter($keys, static fn (ApiKey $key): bool => $key->model === $model)
            );

            if ($matched === []) {
                throw new ProviderException(
                    sprintf(
                        /* translators: 1: model, 2: provider. */
                        __('Aucun modèle « %1$s » configuré pour « %2$s ». Ajoutez une clé API avec ce modèle.', 'ai-product-studio'),
                        $model,
                        $provider
                    )
                );
            }

            return $matched;
        }

        return $keys;
    }

    public function reportSuccess(ApiKey $key): void
    {
        $this->repository->markUsed($key->id);
        $this->repository->resetErrors($key->id);
    }

    public function reportFailure(ApiKey $key): void
    {
        $threshold = (int) $this->settings->get('max_error_before_disable', 5);
        $this->repository->markUsed($key->id);
        $this->repository->incrementError($key->id, $threshold);
    }
}
