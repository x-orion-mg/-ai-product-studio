<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Models\ProductData;
use AIProductStudio\SEO\SeoGenerator;

/**
 * Persists the AI-generated SEO metadata to the active SEO plugin.
 */
final class ApplySeoStep implements StepInterface
{
    public function __construct(private readonly SeoGenerator $seo)
    {
    }

    public function id(): string
    {
        return 'seo';
    }

    public function label(): string
    {
        return __('Génération SEO', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $productId   = (int) $context->get('product_id', 0);
        $productData = $context->get('product_data');

        if ($productId <= 0 || ! $productData instanceof ProductData) {
            return StepResult::skipped();
        }

        $this->seo->apply($productId, $productData);

        return StepResult::success();
    }
}
