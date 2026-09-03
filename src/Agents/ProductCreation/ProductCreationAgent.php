<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation;

use AIProductStudio\Agent\AbstractAgent;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Models\ProductData;

/**
 * Business agent: create a WooCommerce product from an image, a description
 * or an imported row. The ordered step list is the workflow.
 */
final class ProductCreationAgent extends AbstractAgent
{
    public function id(): string
    {
        return 'product-creation';
    }

    public function name(): string
    {
        return __('Création de produit', 'ai-product-studio');
    }

    public function description(): string
    {
        return __('Génère une fiche produit WooCommerce à partir d\'une image, d\'une description ou d\'un import.', 'ai-product-studio');
    }

    /**
     * {@inheritDoc}
     */
    public function inputs(): array
    {
        return [
            'request' => GenerationRequest::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function outputs(): array
    {
        return [
            'product_id'   => 'int',
            'product_data' => ProductData::class,
        ];
    }
}
