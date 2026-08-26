<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Models\AiResponse;

/**
 * OpenAI Chat Completions provider with vision support.
 */
final class OpenAIProvider extends AbstractProvider
{
    private const DEFAULT_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const DEFAULT_MODEL    = 'gpt-4o-mini';

    public function slug(): string
    {
        return 'openai';
    }

    public function label(): string
    {
        return 'OpenAI';
    }

    public function generate(ApiKey $key, string $prompt, array $images, array $options = []): AiResponse
    {
        $endpoint = $key->endpoint !== '' ? $key->endpoint : self::DEFAULT_ENDPOINT;
        $model    = $key->model !== '' ? $key->model : self::DEFAULT_MODEL;

        $content = [['type' => 'text', 'text' => $prompt]];
        foreach ($images as $image) {
            $content[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => $this->dataUri($image)],
            ];
        }

        $body = [
            'model'           => $model,
            'messages'        => [['role' => 'user', 'content' => $content]],
            'temperature'     => (float) ($options['temperature'] ?? 0.4),
            'response_format' => ['type' => 'json_object'],
        ];

        $decoded = $this->post($endpoint, [
            'Authorization' => 'Bearer ' . $key->apiKey,
        ], $body);

        $text = $decoded['choices'][0]['message']['content'] ?? null;

        if (! is_string($text) || $text === '') {
            throw new ProviderException(__('OpenAI n\'a renvoyé aucun contenu.', 'ai-product-studio'));
        }

        return new AiResponse($text, $this->slug(), $model, $decoded);
    }
}
