<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\API\ApiKeyRotator;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\AiResponse;

/**
 * High-level AI facade used by the pipeline. Selects the right provider,
 * iterates the rotated pool of API keys and transparently fails over to the
 * next key when a request fails.
 */
final class AiClient {

	private ProviderFactory $factory;

	private ApiKeyRotator $rotator;

	private Logger $logger;

	public function __construct( ProviderFactory $factory, ApiKeyRotator $rotator, Logger $logger ) {
		$this->factory = $factory;
		$this->rotator = $rotator;
		$this->logger  = $logger;
	}

	/**
	 * Generate content, failing over across the provider's key pool.
	 *
	 * @param array<int, array{mime: string, data: string}> $images
	 * @param array<string, mixed>                          $options
	 *
	 * @throws ProviderException when every candidate key fails.
	 */
	public function generate( string $providerSlug, string $prompt, array $images, array $options = array() ): AiResponse {
		$provider   = $this->factory->make( $providerSlug );
		$candidates = $this->rotator->candidates( $providerSlug );

		$lastError = null;

		foreach ( $candidates as $key ) {
			try {
				$response = $provider->generate( $key, $prompt, $images, $options );
				$this->rotator->reportSuccess( $key );

				$this->logger->info(
					'Génération IA réussie.',
					array(
						'provider' => $providerSlug,
						'model'    => $response->model,
						'key_id'   => $key->id,
					)
				);

				return $response;
			} catch ( ProviderException $e ) {
				$lastError = $e;
				$this->rotator->reportFailure( $key );
				$this->logger->warning(
					'Échec d\'une clé API, rotation vers la suivante.',
					array(
						'provider' => $providerSlug,
						'key_id'   => $key->id,
						'error'    => $e->getMessage(),
					)
				);
			}
		}

		throw new ProviderException(
			sprintf(
				/* translators: 1: provider, 2: last error message. */
				__( 'Toutes les clés du fournisseur « %1$s » ont échoué. Dernière erreur : %2$s', 'ai-product-studio' ),
				$providerSlug,
				$lastError !== null ? $lastError->getMessage() : __( 'inconnue', 'ai-product-studio' )
			)
		);
	}
}
