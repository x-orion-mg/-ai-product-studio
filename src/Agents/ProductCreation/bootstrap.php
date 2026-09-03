<?php

declare(strict_types=1);

use AIProductStudio\Agent\AgentRegistry;
use AIProductStudio\Agents\ProductCreation\ProductCreationAgent;
use AIProductStudio\Agents\ProductCreation\Steps\AnalyzeProductStep;
use AIProductStudio\Agents\ProductCreation\Steps\ApplySeoStep;
use AIProductStudio\Agents\ProductCreation\Steps\BuildPromptStep;
use AIProductStudio\Agents\ProductCreation\Steps\CreateWooCommerceProductStep;
use AIProductStudio\Agents\ProductCreation\Steps\FinalizeStep;
use AIProductStudio\Agents\ProductCreation\Steps\GenerateProductDataStep;
use AIProductStudio\Agents\ProductCreation\Steps\ProcessImageStep;
use AIProductStudio\Agents\ProductCreation\Steps\ValidateInputStep;
use AIProductStudio\Agents\ProductCreation\Steps\ValidateProductDataStep;
use AIProductStudio\AI\AiClient;
use AIProductStudio\Core\Container;
use AIProductStudio\Image\ImageAnalyzer;
use AIProductStudio\Image\ImageCompressor;
use AIProductStudio\Prompt\PromptBuilder;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\SEO\SeoGenerator;
use AIProductStudio\Services\JsonValidator;
use AIProductStudio\Services\ResponseParser;
use AIProductStudio\Services\Settings;
use AIProductStudio\WooCommerce\ProductCreator;

/**
 * Registers the product-creation agent. Adding this file is enough: Core
 * discovers every agent bootstrap file automatically.
 */
return static function (AgentRegistry $registry, Container $container): void {
    $registry->register(
        new ProductCreationAgent(
            [
                new ValidateInputStep(),
                new ProcessImageStep($container->get(ImageCompressor::class)),
                new AnalyzeProductStep($container->get(ImageAnalyzer::class)),
                new BuildPromptStep(
                    $container->get(PromptRepository::class),
                    $container->get(PromptBuilder::class),
                    $container->get(Settings::class)
                ),
                new GenerateProductDataStep($container->get(AiClient::class)),
                new ValidateProductDataStep(
                    $container->get(ResponseParser::class),
                    $container->get(JsonValidator::class)
                ),
                new CreateWooCommerceProductStep(
                    $container->get(ProductCreator::class),
                    $container->get(Settings::class)
                ),
                new ApplySeoStep($container->get(SeoGenerator::class)),
                new FinalizeStep(),
            ]
        )
    );
};
