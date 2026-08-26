<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Models\AiResponse;

/**
 * Common contract every AI provider must implement. Adding a new provider is a
 * matter of implementing this interface and registering it in the factory — no
 * change to the core pipeline is required.
 */
interface ProviderInterface
{
    /**
     * Machine slug, e.g. "openai", "gemini". Must be unique.
     */
    public function slug(): string;

    /**
     * Human-readable label shown in the admin UI.
     */
    public function label(): string;

    /**
     * Whether this provider needs an API key. Local providers (e.g. Ollama) may
     * return false.
     */
    public function requiresApiKey(): bool;

    /**
     * Send the multimodal request (prompt + images) and return the raw response.
     *
     * @param array<int, array{mime: string, data: string}> $images Base64-encoded images.
     * @param array<string, mixed>                          $options
     */
    public function generate(ApiKey $key, string $prompt, array $images, array $options = []): AiResponse;
}
