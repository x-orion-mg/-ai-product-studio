<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\AI\AiClient;
use AIProductStudio\AI\Personas\ProductPersona;
use AIProductStudio\Models\GenerationRequest;

/**
 * Calls the AI layer through AiClient (provider-agnostic) to produce product JSON.
 */
final class GenerateProductDataStep implements StepInterface
{
    public function __construct(private readonly AiClient $client)
    {
    }

    public function id(): string
    {
        return 'call_ai';
    }

    public function label(): string
    {
        return __('Agent IA', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 2;
    }

    public function execute(AgentContext $context): StepResult
    {
        $request = $context->get('request');
        $prompt  = (string) $context->get('compiled_prompt', '');

        if (! $request instanceof GenerationRequest || $prompt === '') {
            return StepResult::failure(__('Prompt compilé manquant.', 'ai-product-studio'));
        }

        $images = $context->get('images', []);
        if (! is_array($images)) {
            $images = [];
        }

        $response = $this->client->generate(
            $request->provider,
            $prompt,
            $images,
            [
                'session_id' => $context->jobId() !== '' ? $context->jobId() : uniqid('aips-', true),
                'persona'    => ProductPersona::class,
            ]
        );

        $context->set('ai_response', $response);

        return StepResult::success();
    }
}
