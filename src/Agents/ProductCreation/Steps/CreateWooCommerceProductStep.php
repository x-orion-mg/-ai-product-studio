<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Models\ProductData;
use AIProductStudio\Services\Settings;
use AIProductStudio\WooCommerce\ProductCreator;

/**
 * Creates the WooCommerce product from the validated AI data.
 */
final class CreateWooCommerceProductStep implements StepInterface
{
    public function __construct(
        private readonly ProductCreator $creator,
        private readonly Settings $settings
    ) {
    }

    public function id(): string
    {
        return 'create_product';
    }

    public function label(): string
    {
        return __('Création du produit WooCommerce', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $productData = $context->get('product_data');
        $request     = $context->get('request');

        if (! $productData instanceof ProductData || ! $request instanceof GenerationRequest) {
            return StepResult::failure(__('Données produit manquantes.', 'ai-product-studio'));
        }

        $status = (string) $this->settings->get('default_status', 'draft');

        $context->set('product_id', $this->creator->create($productData, $request, $status));

        return StepResult::success();
    }
}
