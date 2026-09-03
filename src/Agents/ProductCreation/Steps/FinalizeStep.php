<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Models\AiResponse;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Models\ProductData;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Prompt\Prompt;

/**
 * Final housekeeping hook so third-party code can react without touching Core.
 */
final class FinalizeStep implements StepInterface
{
    public function id(): string
    {
        return 'finalize';
    }

    public function label(): string
    {
        return __('Finalisation', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $productId = (int) $context->get('product_id', 0);

        /**
         * Fires once a product has been fully generated.
         *
         * @param int               $productId
         * @param GenerationContext $legacyContext Kept for existing listeners.
         */
        do_action('aips_product_generated', $productId, $this->legacyContext($context));

        return StepResult::success();
    }

    private function legacyContext(AgentContext $context): GenerationContext
    {
        $request = $context->get('request');
        $legacy  = new GenerationContext(
            $request instanceof GenerationRequest ? $request : new GenerationRequest(
                source: GenerationRequest::SOURCE_DESCRIPTION,
                mainImageId: 0,
                galleryImageIds: [],
                price: 0,
                salePrice: null,
                userDescription: '',
                relatedProductIds: [],
                promptId: 0,
                provider: ''
            ),
            $context->jobId()
        );

        $images = $context->get('images', []);
        $legacy->images = is_array($images) ? $images : [];

        $analysis = $context->get('analysis', []);
        $legacy->analysis = is_array($analysis) ? $analysis : [];

        $legacy->compiledPrompt = (string) $context->get('compiled_prompt', '');

        $prompt = $context->get('prompt');
        $legacy->prompt = $prompt instanceof Prompt ? $prompt : null;

        $ai = $context->get('ai_response');
        $legacy->aiResponse = $ai instanceof AiResponse ? $ai : null;

        $data = $context->get('product_data');
        $legacy->productData = $data instanceof ProductData ? $data : null;

        $legacy->productId = (int) $context->get('product_id', 0);

        return $legacy;
    }
}
