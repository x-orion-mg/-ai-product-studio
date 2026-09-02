<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Image\ImageCompressor;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;

/**
 * Compresses the main image (and optionally the first gallery images) into
 * base64 payloads ready to be sent to the AI.
 */
final class PrepareImagesStep implements StepInterface
{
    private ImageCompressor $compressor;

    public function __construct(ImageCompressor $compressor)
    {
        $this->compressor = $compressor;
    }

    public function key(): string
    {
        return 'prepare_images';
    }

    public function label(): string
    {
        return __('Préparation des images', 'ai-product-studio');
    }

    public function handle(GenerationContext $context): void
    {
        if (! $context->request->hasImage()) {
            $context->images = [];

            return;
        }

        $context->images[] = $this->compressor->toBase64($context->request->mainImageId);
    }
}
