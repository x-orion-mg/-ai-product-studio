<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Models\AiResponse;

/**
 * OpenRouter provider. Uses the OpenAI-compatible schema, giving access to many
 * models through a single endpoint.
 */
final class OpenRouterProvider extends AbstractProvider {

	private const DEFAULT_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';
	private const DEFAULT_MODEL    = 'openai/gpt-4o-mini';

	public function slug(): string {
		return 'openrouter';
	}

	public function label(): string {
		return 'OpenRouter';
	}

	public function generate( ApiKey $key, string $prompt, array $images, array $options = array() ): AiResponse {
		$endpoint = $key->endpoint !== '' ? $key->endpoint : self::DEFAULT_ENDPOINT;
		$model    = $key->model !== '' ? $key->model : self::DEFAULT_MODEL;

		$content = array(
			array(
				'type' => 'text',
				'text' => $prompt,
			),
		);
		foreach ( $images as $image ) {
			$content[] = array(
				'type'      => 'image_url',
				'image_url' => array( 'url' => $this->dataUri( $image ) ),
			);
		}

		$body = array(
			'model'       => $model,
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $content,
				),
			),
			'temperature' => (float) ( $options['temperature'] ?? 0.4 ),
		);

		$decoded = $this->post(
			$endpoint,
			array(
				'Authorization' => 'Bearer ' . $key->apiKey,
				'HTTP-Referer'  => home_url(),
				'X-Title'       => 'AI Product Studio',
			),
			$body
		);

		$text = $decoded['choices'][0]['message']['content'] ?? null;

		if ( ! is_string( $text ) || $text === '' ) {
			throw new ProviderException( __( 'OpenRouter n\'a renvoyé aucun contenu.', 'ai-product-studio' ) );
		}

		return new AiResponse( $text, $this->slug(), $model, $decoded );
	}
}
