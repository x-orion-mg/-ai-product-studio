<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Image\ImageAnalyzer;
use AIProductStudio\Models\GenerationRequest;

/**
 * Collects lightweight metadata about the main image to enrich the prompt.
 */
final class AnalyzeProductStep implements StepInterface
{
    public function __construct(private readonly ImageAnalyzer $analyzer)
    {
    }

    public function id(): string
    {
        return 'analyze_image';
    }

    public function label(): string
    {
        return __('Analyse de l\'image', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $request = $context->get('request');

        if (! $request instanceof GenerationRequest || ! $request->hasImage()) {
            $context->set('analysis', []);

            return StepResult::skipped();
        }

        $context->set('analysis', $this->analyzer->analyze($request->mainImageId));

        return StepResult::success();
    }
}
