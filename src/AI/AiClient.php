<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\Agent\ProductAgent;
use AIProductStudio\API\ApiKeyRotator;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\AiResponse;
use MyAILib\AI\AIManager;
use MyAILib\Exception\ProviderException as LibProviderException;
use MyAILib\Session\FileSessionStore;
use Throwable;

/**
 * AI facade used by the product pipeline. Builds a My AI Lib agent session,
 * rotates API keys, and fails over to the next key when a request fails.
 */
final class AiClient
{
    private ProviderFactory $factory;

    private ApiKeyRotator $rotator;

    private Logger $logger;

    public function __construct(ProviderFactory $factory, ApiKeyRotator $rotator, Logger $logger)
    {
        $this->factory = $factory;
        $this->rotator = $rotator;
        $this->logger  = $logger;
    }

    /**
     * Generate content via the product agent, failing over across the key pool.
     *
     * @param array<int, array{mime: string, data: string}> $images
     * @param array<string, mixed>                          $options
     *
     * @throws ProviderException when every candidate key fails.
     */
    public function generate(string $providerSlug, string $prompt, array $images, array $options = []): AiResponse
    {
        $provider   = $this->factory->make($providerSlug);
        $candidates = $this->rotator->candidates($providerSlug);

        $lastError = null;
        $sessionId = (string) ($options['session_id'] ?? uniqid('aips-', true));

        foreach ($candidates as $key) {
            try {
                $adapter = new PluginProviderAdapter($provider, $key, $images);
                $manager = new AIManager(
                    $adapter,
                    new FileSessionStore($this->sessionDirectory())
                );

                $manager->startSession($sessionId);

                $agent = new ProductAgent($manager);
                $manager->setSystemPrompt($agent->instructions());

                $text = $agent->run($prompt);

                $this->rotator->reportSuccess($key);

                $this->logger->info('Agent produit : génération réussie.', [
                    'provider' => $providerSlug,
                    'model'    => $key->model,
                    'key_id'   => $key->id,
                    'agent'    => $agent->name(),
                ]);

                return new AiResponse($text, $providerSlug, $key->model !== '' ? $key->model : $providerSlug);
            } catch (LibProviderException $e) {
                $lastError = new ProviderException($e->getMessage(), 0, $e);
            } catch (Throwable $e) {
                $lastError = new ProviderException($e->getMessage(), 0, $e);
            }

            if ($lastError !== null) {
                $this->rotator->reportFailure($key);
                $this->logger->warning('Échec d\'une clé API, rotation vers la suivante.', [
                    'provider' => $providerSlug,
                    'key_id'   => $key->id,
                    'error'    => $lastError->getMessage(),
                ]);
            }
        }

        throw new ProviderException(
            sprintf(
                /* translators: 1: provider, 2: last error message. */
                __('Toutes les clés du fournisseur « %1$s » ont échoué. Dernière erreur : %2$s', 'ai-product-studio'),
                $providerSlug,
                $lastError !== null ? $lastError->getMessage() : __('inconnue', 'ai-product-studio')
            )
        );
    }

    private function sessionDirectory(): string
    {
        $dir = AIPS_STORAGE_DIR . 'sessions';

        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        return $dir;
    }
}
