<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Models\AiResponse;

/**
 * Anthropic Claude provider (Messages API with base64 images).
 */
final class ClaudeProvider extends AbstractProvider
{
    private const DEFAULT_ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const DEFAULT_MODEL    = 'claude-3-5-sonnet-latest';
    private const API_VERSION      = '2023-06-01';

    public function slug(): string
    {
        return 'claude';
    }

    public function label(): string
    {
        return 'Anthropic Claude';
    }

    public function generate(ApiKey $key, string $prompt, array $images, array $options = []): AiResponse
    {
        $endpoint = $key->endpoint !== '' ? $key->endpoint : self::DEFAULT_ENDPOINT;
        $model    = $key->model !== '' ? $key->model : self::DEFAULT_MODEL;

        $content = [];
        foreach ($images as $image) {
            $content[] = [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $image['mime'],
                    'data'       => $image['data'],
                ],
            ];
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $body = [
            'model'      => $model,
            'max_tokens' => (int) ($options['max_tokens'] ?? 2048),
            'messages'   => [['role' => 'user', 'content' => $content]],
        ];

        $decoded = $this->post($endpoint, [
            'x-api-key'         => $key->apiKey,
            'anthropic-version' => self::API_VERSION,
        ], $body);

        $text = $decoded['content'][0]['text'] ?? null;

        if (! is_string($text) || $text === '') {
            throw new ProviderException(__('Claude n\'a renvoyé aucun contenu.', 'ai-product-studio'));
        }

        return new AiResponse($text, $this->slug(), $model, $decoded);
    }
}
