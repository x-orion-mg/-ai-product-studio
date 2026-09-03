<?php

declare(strict_types=1);

use AIProductStudio\Agent\AgentRegistry;
use AIProductStudio\Agents\Copywriter\CopywriterAgent;
use AIProductStudio\Agents\Copywriter\Steps\GenerateDescriptionStep;
use AIProductStudio\Agents\Copywriter\Steps\GenerateTitleStep;
use AIProductStudio\Agents\Copywriter\Steps\LoadProductStep;
use AIProductStudio\Agents\Copywriter\Steps\ValidateCopyStep;
use AIProductStudio\AI\AiClient;
use AIProductStudio\Core\Container;

/**
 * Registers the copywriter agent without touching Core.
 */
return static function (AgentRegistry $registry, Container $container): void {
    $registry->register(
        new CopywriterAgent(
            [
                new LoadProductStep(),
                new GenerateTitleStep($container->get(AiClient::class)),
                new GenerateDescriptionStep($container->get(AiClient::class)),
                new ValidateCopyStep(),
            ]
        )
    );
};
