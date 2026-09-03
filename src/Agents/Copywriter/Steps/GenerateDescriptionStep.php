<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\Copywriter\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\AI\AiClient;
use AIProductStudio\AI\Personas\CopywriterPersona;

/**
 * Asks the AI layer for an improved product description.
 */
final class GenerateDescriptionStep implements StepInterface
{
    public function __construct(private readonly AiClient $client)
    {
    }

    public function id(): string
    {
        return 'generate_description';
    }

    public function label(): string
    {
        return __('Génération de la description', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 1;
    }

    public function execute(AgentContext $context): StepResult
    {
        $provider = (string) $context->get('provider', 'openai');
        $title    = (string) $context->get('copy_title', $context->get('source_title', ''));
        $desc     = (string) $context->get('source_description', '');

        $prompt = sprintf(
            "Rédige une description produit HTML convaincante.\nTitre : %s\nDescription actuelle : %s\nRéponds uniquement avec JSON {\"title\":\"\",\"description\":\"\"} — title peut reprendre le titre fourni.",
            $title,
            $desc
        );

        $response = $this->client->generate(
            $provider,
            $prompt,
            [],
            [
                'session_id' => $context->jobId() !== '' ? $context->jobId() . '-desc' : uniqid('aips-copy-', true),
                'persona'    => CopywriterPersona::class,
            ]
        );

        $decoded     = json_decode($response->content, true);
        $description = is_array($decoded) ? trim((string) ($decoded['description'] ?? '')) : '';

        if ($description === '') {
            $description = trim($response->content);
        }

        $context->set('copy_description', $description);
        $context->set('ai_description_response', $response);

        return $description !== ''
            ? StepResult::success()
            : StepResult::retry(__('Description générée vide.', 'ai-product-studio'));
    }
}
