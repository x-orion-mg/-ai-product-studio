<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\Copywriter\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;

/**
 * Ensures generated copy is complete enough to be used.
 */
final class ValidateCopyStep implements StepInterface
{
    public function id(): string
    {
        return 'validate_copy';
    }

    public function label(): string
    {
        return __('Validation du copywriting', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $title       = trim((string) $context->get('copy_title', ''));
        $description = trim((string) $context->get('copy_description', ''));

        if ($title === '' || $description === '') {
            return StepResult::failure(__('Le titre et la description générés sont obligatoires.', 'ai-product-studio'));
        }

        $context->set('copy', [
            'title'       => $title,
            'description' => $description,
        ]);

        return StepResult::success();
    }
}
