<?php

declare(strict_types=1);

namespace AIProductStudio\Admin\Pages;

use AIProductStudio\Admin\AbstractPage;
use AIProductStudio\AI\ProviderFactory;
use AIProductStudio\Product\ProductGenerator;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\Services\Settings;

final class GeneratePage extends AbstractPage
{
    public function slug(): string
    {
        return 'generate';
    }

    public function title(): string
    {
        return __('Générer un produit — My AI Agent', 'ai-product-studio');
    }

    public function menuTitle(): string
    {
        return __('Générer un produit', 'ai-product-studio');
    }

    public function render(): void
    {
        /** @var PromptRepository $prompts */
        $prompts = $this->container->get(PromptRepository::class);
        /** @var ProviderFactory $factory */
        $factory = $this->container->get(ProviderFactory::class);
        /** @var ProductGenerator $generator */
        $generator = $this->container->get(ProductGenerator::class);
        /** @var Settings $settings */
        $settings = $this->container->get(Settings::class);

        $this->view('generate', [
            'prompts'        => $prompts->all(true),
            'providers'      => $factory->available(),
            'steps'          => $generator->steps(),
            'defaultProvider'=> (string) $settings->get('default_provider', 'openai'),
            'wooActive'      => class_exists('WooCommerce'),
        ]);
    }
}
