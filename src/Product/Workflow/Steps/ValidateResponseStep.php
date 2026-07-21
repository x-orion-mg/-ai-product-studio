<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Exceptions\WorkflowException;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;
use AIProductStudio\Services\JsonValidator;
use AIProductStudio\Services\ResponseParser;

/**
 * Parses and validates the AI's JSON response into a typed ProductData object.
 */
final class ValidateResponseStep implements StepInterface
{
    private ResponseParser $parser;

    private JsonValidator $validator;

    public function __construct(ResponseParser $parser, JsonValidator $validator)
    {
        $this->parser    = $parser;
        $this->validator = $validator;
    }

    public function key(): string
    {
        return 'validate_json';
    }

    public function label(): string
    {
        return __('Validation du JSON', 'ai-product-studio');
    }

    public function handle(GenerationContext $context): void
    {
        if ($context->aiResponse === null) {
            throw new WorkflowException(
                __('Aucune réponse IA à valider.', 'ai-product-studio'),
                'validate_json'
            );
        }

        $decoded             = $this->parser->parse($context->aiResponse->content);
        $context->productData = $this->validator->validate($decoded);
    }
}
