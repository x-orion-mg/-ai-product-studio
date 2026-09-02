<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Image\ImageAnalyzer;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;

/**
 * Collects lightweight metadata about the main image to enrich the prompt.
 */
final class AnalyzeImageStep implements StepInterface
{
    private ImageAnalyzer $analyzer;

    public function __construct(ImageAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function key(): string
    {
        return 'analyze_image';
    }

    public function label(): string
    {
        return __('Analyse de l\'image', 'ai-product-studio');
    }

    public function handle(GenerationContext $context): void
    {
        if (! $context->request->hasImage()) {
            $context->analysis = [];

            return;
        }

        $context->analysis = $this->analyzer->analyze($context->request->mainImageId);
    }
}
