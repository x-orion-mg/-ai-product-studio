<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Exceptions\WorkflowException;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;
use AIProductStudio\Services\Settings;
use AIProductStudio\WooCommerce\ProductCreator;

/**
 * Creates the WooCommerce product (with main image, gallery and taxonomies)
 * from the validated AI data.
 */
final class CreateProductStep implements StepInterface
{
    private ProductCreator $creator;

    private Settings $settings;

    public function __construct(ProductCreator $creator, Settings $settings)
    {
        $this->creator  = $creator;
        $this->settings = $settings;
    }

    public function key(): string
    {
        return 'create_product';
    }

    public function label(): string
    {
        return __('Création du produit WooCommerce', 'ai-product-studio');
    }

    public function handle(GenerationContext $context): void
    {
        if ($context->productData === null) {
            throw new WorkflowException(
                __('Données produit manquantes.', 'ai-product-studio'),
                'create_product'
            );
        }

        $status = (string) $this->settings->get('default_status', 'draft');

        $context->productId = $this->creator->create(
            $context->productData,
            $context->request,
            $status
        );
    }
}
