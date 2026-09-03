<?php

declare(strict_types=1);

namespace AIProductStudio\AI;

use AIProductStudio\AI\Personas\ProductPersona;
use AIProductStudio\API\ApiKeyRotator;
use AIProductStudio\Exceptions\ProviderException;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\AiResponse;
use MyAILib\Agent\AbstractAgent as LibAgent;
use MyAILib\AI\AIManager;
use MyAILib\Exception\ProviderException as LibProviderException;
use MyAILib\Session\FileSessionStore;
use Throwable;

/**
 * AI facade used by agent steps. Builds a My AI Lib session, rotates API keys,
 * and fails over to the next key when a request fails. Steps never depend on
 * a concrete provider (OpenAI, Gemini, …).
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
     * Generate content via an LLM persona, failing over across the key pool.
     *
     * @param array<int, array{mime: string, data: string}> $images
     * @param array<string, mixed>                          $options
     *
     * @throws ProviderException when every candidate key fails.
     */
    public function generate(string $providerSlug, string $prompt, array $images, array $options = []): AiResponse
    {
        $provider     = $this->factory->make($providerSlug);
        $requestedModel = sanitize_text_field((string) ($options['model'] ?? ''));
        $candidates   = $this->rotator->candidates($providerSlug, $requestedModel);
        $personaClass = $this->resolvePersona($options['persona'] ?? ProductPersona::class);

        $lastError = null;
        $sessionId = (string) ($options['session_id'] ?? uniqid('aips-', true));

        foreach ($candidates as $key) {
            try {
                $keyToUse = $requestedModel !== '' ? $key->withModel($requestedModel) : $key;
                $adapter  = new PluginProviderAdapter($provider, $keyToUse, $images);
                $manager = new AIManager(
                    $adapter,
                    new FileSessionStore($this->sessionDirectory())
                );

                $manager->startSession($sessionId);

                $persona = new $personaClass($manager);
                $manager->setSystemPrompt($persona->instructions());

                $text = $persona->run($prompt);

                $this->rotator->reportSuccess($key);

                $this->logger->info('Agent IA : génération réussie.', [
                    'provider' => $providerSlug,
                    'model'    => $keyToUse->model,
                    'key_id'   => $key->id,
                    'persona'  => $persona->name(),
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

    /**
     * @param mixed $persona
     *
     * @return class-string<LibAgent>
     */
    private function resolvePersona(mixed $persona): string
    {
        if (! is_string($persona) || ! is_subclass_of($persona, LibAgent::class)) {
            return ProductPersona::class;
        }

        return $persona;
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
