<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\Copywriter\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\AI\AiClient;
use AIProductStudio\AI\Personas\CopywriterPersona;

/**
 * Asks the AI layer for an improved product title.
 */
final class GenerateTitleStep implements StepInterface
{
    public function __construct(private readonly AiClient $client)
    {
    }

    public function id(): string
    {
        return 'generate_title';
    }

    public function label(): string
    {
        return __('Génération du titre', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 1;
    }

    public function execute(AgentContext $context): StepResult
    {
        $provider = (string) $context->get('provider', 'openai');
        $source   = (string) $context->get('source_title', '');
        $desc     = (string) $context->get('source_description', '');

        $prompt = sprintf(
            "Génère un titre produit e-commerce percutant.\nTitre actuel : %s\nDescription : %s\nRéponds uniquement avec JSON {\"title\":\"\",\"description\":\"\"} — description peut rester vide.",
            $source,
            $desc
        );

        $response = $this->client->generate(
            $provider,
            $prompt,
            [],
            [
                'session_id' => $context->jobId() !== '' ? $context->jobId() . '-title' : uniqid('aips-copy-', true),
                'persona'    => CopywriterPersona::class,
            ]
        );

        $decoded = json_decode($response->content, true);
        $title   = is_array($decoded) ? trim((string) ($decoded['title'] ?? '')) : '';

        if ($title === '') {
            $title = trim($response->content);
        }

        $context->set('copy_title', $title);
        $context->set('ai_title_response', $response);

        return $title !== ''
            ? StepResult::success()
            : StepResult::retry(__('Titre généré vide.', 'ai-product-studio'));
    }
}
