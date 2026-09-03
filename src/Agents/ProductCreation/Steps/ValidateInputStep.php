<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Models\GenerationRequest;

/**
 * Validates the generation request before any IO or AI call.
 */
final class ValidateInputStep implements StepInterface
{
    public function id(): string
    {
        return 'validate_input';
    }

    public function label(): string
    {
        return __('Validation des données', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $request = $context->get('request');

        if (! $request instanceof GenerationRequest) {
            return StepResult::failure(__('Requête de génération manquante.', 'ai-product-studio'));
        }

        if ($request->source === GenerationRequest::SOURCE_IMAGE && $request->mainImageId <= 0) {
            return StepResult::failure(__('Veuillez sélectionner une image principale.', 'ai-product-studio'));
        }

        if (
            in_array($request->source, [GenerationRequest::SOURCE_DESCRIPTION, GenerationRequest::SOURCE_IMPORT], true)
            && $request->userDescription === ''
        ) {
            return StepResult::failure(__('Veuillez saisir une description produit.', 'ai-product-studio'));
        }

        return StepResult::success();
    }
}
