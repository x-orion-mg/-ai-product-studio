<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\Copywriter;

use AIProductStudio\Agent\AbstractAgent;

/**
 * Business agent: rewrite title and description of an existing product.
 * Registered independently of Core via bootstrap.php.
 */
final class CopywriterAgent extends AbstractAgent
{
    public function id(): string
    {
        return 'copywriter';
    }

    public function name(): string
    {
        return __('Copywriter', 'ai-product-studio');
    }

    public function description(): string
    {
        return __('Réécrit le titre et la description d\'un produit WooCommerce existant.', 'ai-product-studio');
    }

    /**
     * {@inheritDoc}
     */
    public function inputs(): array
    {
        return [
            'product_id' => 'int',
            'provider'   => 'string',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function outputs(): array
    {
        return [
            'copy_title'       => 'string',
            'copy_description' => 'string',
        ];
    }
}
