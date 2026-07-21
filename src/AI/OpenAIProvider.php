<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Models\AiResponse;

/**
 * OpenAI Chat Completions provider with vision support.
 */
final class OpenAIProvider extends AbstractProvider {

	private const DEFAULT_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
	private const DEFAULT_MODEL    = 'gpt-4o-mini';

	public function slug(): string {
		return 'openai';
	}

	public function label(): string {
		return 'OpenAI';
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
			'model'           => $model,
			'messages'        => array(
				array(
					'role'    => 'user',
					'content' => $content,
				),
			),
			'temperature'     => (float) ( $options['temperature'] ?? 0.4 ),
			'response_format' => array( 'type' => 'json_object' ),
		);

		$decoded = $this->post(
			$endpoint,
			array(
				'Authorization' => 'Bearer ' . $key->apiKey,
			),
			$body
		);

		$text = $decoded['choices'][0]['message']['content'] ?? null;

		if ( ! is_string( $text ) || $text === '' ) {
			throw new ProviderException( __( 'OpenAI n\'a renvoyé aucun contenu.', 'ai-product-studio' ) );
		}

		return new AiResponse( $text, $this->slug(), $model, $decoded );
	}
}
