<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Image\ImageCompressor;
use AIProductStudio\Models\GenerationRequest;

/**
 * Compresses the main image into a base64 payload for the AI provider.
 */
final class ProcessImageStep implements StepInterface
{
    public function __construct(private readonly ImageCompressor $compressor)
    {
    }

    public function id(): string
    {
        return 'prepare_images';
    }

    public function label(): string
    {
        return __('Préparation des images', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $request = $context->get('request');

        if (! $request instanceof GenerationRequest || ! $request->hasImage()) {
            $context->set('images', []);

            return StepResult::skipped();
        }

        $context->set('images', [ $this->compressor->toBase64($request->mainImageId) ]);

        return StepResult::success();
    }
}
