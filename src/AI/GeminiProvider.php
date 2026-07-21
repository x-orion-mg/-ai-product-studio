<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKey;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Models\AiResponse;

/**
 * Google Gemini provider (generateContent endpoint with inline image data).
 */
final class GeminiProvider extends AbstractProvider {

	private const DEFAULT_BASE  = 'https://generativelanguage.googleapis.com/v1beta/models';
	private const DEFAULT_MODEL = 'gemini-1.5-flash';

	public function slug(): string {
		return 'gemini';
	}

	public function label(): string {
		return 'Google Gemini';
	}

	public function generate( ApiKey $key, string $prompt, array $images, array $options = array() ): AiResponse {
		$model = $key->model !== '' ? $key->model : self::DEFAULT_MODEL;
		$base  = $key->endpoint !== '' ? rtrim( $key->endpoint, '/' ) : self::DEFAULT_BASE;

		$endpoint = sprintf( '%s/%s:generateContent?key=%s', $base, rawurlencode( $model ), rawurlencode( $key->apiKey ) );

		$parts = array( array( 'text' => $prompt ) );
		foreach ( $images as $image ) {
			$parts[] = array(
				'inline_data' => array(
					'mime_type' => $image['mime'],
					'data'      => $image['data'],
				),
			);
		}

		$body = array(
			'contents'         => array( array( 'parts' => $parts ) ),
			'generationConfig' => array(
				'temperature'      => (float) ( $options['temperature'] ?? 0.4 ),
				'responseMimeType' => 'application/json',
			),
		);

		$decoded = $this->post( $endpoint, array(), $body );

		$text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

		if ( ! is_string( $text ) || $text === '' ) {
			throw new ProviderException( __( 'Gemini n\'a renvoyé aucun contenu.', 'ai-product-studio' ) );
		}

		return new AiResponse( $text, $this->slug(), $model, $decoded );
	}
}
