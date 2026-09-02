<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\AI\AiClient;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;

/**
 * Runs the product agent (My AI Lib) with the compiled prompt and optional images.
 */
final class CallAiStep implements StepInterface
{
    private AiClient $client;

    public function __construct(AiClient $client)
    {
        $this->client = $client;
    }

    public function key(): string
    {
        return 'call_ai';
    }

    public function label(): string
    {
        return __('Agent IA', 'ai-product-studio');
    }

    public function handle(GenerationContext $context): void
    {
        $context->aiResponse = $this->client->generate(
            $context->request->provider,
            $context->compiledPrompt,
            $context->images,
            ['session_id' => $context->jobId !== '' ? $context->jobId : uniqid('aips-', true)]
        );
    }
}
