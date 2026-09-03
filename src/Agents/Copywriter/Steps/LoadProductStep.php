<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\Copywriter\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;

/**
 * Loads an existing WooCommerce product into the shared context.
 */
final class LoadProductStep implements StepInterface
{
    public function id(): string
    {
        return 'load_product';
    }

    public function label(): string
    {
        return __('Chargement du produit', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $productId = (int) $context->get('product_id', 0);

        if ($productId <= 0 || ! function_exists('wc_get_product')) {
            return StepResult::failure(__('Identifiant produit invalide.', 'ai-product-studio'));
        }

        $product = wc_get_product($productId);

        if (! $product) {
            return StepResult::failure(__('Produit introuvable.', 'ai-product-studio'));
        }

        $context->set('product', $product);
        $context->set('source_title', $product->get_name());
        $context->set('source_description', wp_strip_all_tags((string) $product->get_description()));

        return StepResult::success();
    }
}
