<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Models\AiResponse;

/**
 * Ollama provider for locally-hosted multimodal models (e.g. llava, llama3.2-vision).
 * No API key required by default; the "endpoint" points at the local server.
 */
final class OllamaProvider extends AbstractProvider {

	private const DEFAULT_ENDPOINT = 'http://localhost:11434/api/generate';
	private const DEFAULT_MODEL    = 'llava';

	public function slug(): string {
		return 'ollama';
	}

	public function label(): string {
		return 'Ollama (local)';
	}

	public function requiresApiKey(): bool {
		return false;
	}

	public function generate( ApiKey $key, string $prompt, array $images, array $options = array() ): AiResponse {
		$endpoint = $key->endpoint !== '' ? $key->endpoint : self::DEFAULT_ENDPOINT;
		$model    = $key->model !== '' ? $key->model : self::DEFAULT_MODEL;

		$body = array(
			'model'   => $model,
			'prompt'  => $prompt,
			'stream'  => false,
			'format'  => 'json',
			'images'  => array_map( static fn ( array $image ): string => $image['data'], $images ),
			'options' => array( 'temperature' => (float) ( $options['temperature'] ?? 0.4 ) ),
		);

		$headers = array();
		if ( $key->apiKey !== '' ) {
			$headers['Authorization'] = 'Bearer ' . $key->apiKey;
		}

		$decoded = $this->post( $endpoint, $headers, $body );

		$text = $decoded['response'] ?? null;

		if ( ! is_string( $text ) || $text === '' ) {
			throw new ProviderException( __( 'Ollama n\'a renvoyé aucun contenu.', 'ai-product-studio' ) );
		}

		return new AiResponse( $text, $this->slug(), $model, $decoded );
	}
}
