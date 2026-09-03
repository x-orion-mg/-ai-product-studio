<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKeyRepository;

/**
 * Models offered in Feature forms: the distinct model names configured on
 * active API keys, grouped by provider.
 */
final class ModelCatalog
{
    public function __construct(private readonly ApiKeyRepository $keys)
    {
    }

    /**
     * @return array<string, array<string, string>> provider => [model => model]
     */
    public function modelsByProvider(): array
    {
        $map = [];

        foreach ($this->keys->all() as $key) {
            if (! $key->isActive || $key->model === '') {
                continue;
            }

            $map[$key->provider][$key->model] = $key->model;
        }

        return $map;
    }
}
