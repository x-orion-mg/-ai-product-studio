<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Models\AiResponse;
use AIProductStudio\Services\JsonValidator;
use AIProductStudio\Services\ResponseParser;

/**
 * Parses and validates the AI JSON response into a typed ProductData object.
 */
final class ValidateProductDataStep implements StepInterface
{
    public function __construct(
        private readonly ResponseParser $parser,
        private readonly JsonValidator $validator
    ) {
    }

    public function id(): string
    {
        return 'validate_json';
    }

    public function label(): string
    {
        return __('Validation du JSON', 'ai-product-studio');
    }

    public function maxRetries(): int
    {
        return 0;
    }

    public function execute(AgentContext $context): StepResult
    {
        $response = $context->get('ai_response');

        if (! $response instanceof AiResponse) {
            return StepResult::failure(__('Aucune réponse IA à valider.', 'ai-product-studio'));
        }

        $decoded = $this->parser->parse($response->content);
        $context->set('product_data', $this->validator->validate($decoded));

        return StepResult::success();
    }
}
