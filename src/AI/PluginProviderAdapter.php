<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use MyAILib\Exception\ProviderException as LibProviderException;
use MyAILib\Model\AIModel;
use MyAILib\Provider\ProviderCapability;
use MyAILib\Provider\ProviderInterface as LibProviderInterface;
use MyAILib\Request\AIRequest;
use MyAILib\Response\AIResponse as LibAiResponse;
use Throwable;

/**
 * Bridges the plugin's multimodal providers into My AI Lib's ProviderInterface
 * so agents can run through AIManager while still sending images.
 */
final class PluginProviderAdapter implements LibProviderInterface
{
    /**
     * @param array<int, array{mime: string, data: string}> $images
     */
    public function __construct(
        private readonly ProviderInterface $inner,
        private readonly ApiKey $key,
        private array $images = []
    ) {
    }

    /**
     * @param array<int, array{mime: string, data: string}> $images
     */
    public function setImages(array $images): void
    {
        $this->images = $images;
    }

    public function configure(array $options): void
    {
    }

    public function ask(AIRequest $request): LibAiResponse
    {
        $prompt = $this->flattenPrompt($request);

        try {
            $response = $this->inner->generate($this->key, $prompt, $this->images);
        } catch (Throwable $e) {
            throw new LibProviderException(
                $e->getMessage(),
                $this->getSlug()
            );
        }

        return new LibAiResponse(
            text: $response->content,
            provider: $response->provider,
            model: $response->model,
            metadata: $response->raw
        );
    }

    public function getName(): string
    {
        return $this->inner->label();
    }

    public function getSlug(): string
    {
        return $this->inner->slug();
    }

    public function supports(ProviderCapability $capability): bool
    {
        return true;
    }

    /**
     * @return AIModel[]
     */
    public function getModels(): array
    {
        $model = $this->key->model !== '' ? $this->key->model : $this->inner->slug();

        return [new AIModel(id: $model, name: $model)];
    }

    private function flattenPrompt(AIRequest $request): string
    {
        $chunks = [];

        foreach ($request->toArray() as $message) {
            if (! is_array($message)) {
                continue;
            }

            $role    = (string) ($message['role'] ?? '');
            $content = trim((string) ($message['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            if ($role === 'system') {
                $chunks[] = $content;
            } elseif ($role === 'user') {
                $chunks[] = $content;
            }
        }

        if ($chunks === []) {
            return $request->getPrompt();
        }

        return implode("\n\n", $chunks);
    }
}
