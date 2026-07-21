<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Exceptions\WorkflowException;
use AIProductStudio\Prompt\PromptBuilder;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;
use AIProductStudio\Services\Settings;

/**
 * Resolves the prompt template and compiles it with the request variables.
 */
final class BuildPromptStep implements StepInterface
{
    private PromptRepository $prompts;

    private PromptBuilder $builder;

    private Settings $settings;

    public function __construct(PromptRepository $prompts, PromptBuilder $builder, Settings $settings)
    {
        $this->prompts  = $prompts;
        $this->builder  = $builder;
        $this->settings = $settings;
    }

    public function key(): string
    {
        return 'build_prompt';
    }

    public function label(): string
    {
        return __('Construction du prompt', 'ai-product-studio');
    }

    public function handle(GenerationContext $context): void
    {
        $prompt = $context->request->promptId > 0
            ? $this->prompts->find($context->request->promptId)
            : $this->prompts->firstActive();

        if ($prompt === null) {
            throw new WorkflowException(
                __('Aucun prompt disponible. Créez-en un dans l\'onglet Prompts.', 'ai-product-studio'),
                'build_prompt'
            );
        }

        $context->prompt = $prompt;

        $relatedTitles = array_filter(array_map(
            static fn (int $id): string => (string) get_the_title($id),
            $context->request->relatedProductIds
        ));

        $variables = [
            'description_utilisateur' => $context->request->userDescription,
            'image'                   => (string) ($context->analysis['filename'] ?? ''),
            'categorie'               => '',
            'prix'                    => (string) $context->request->price,
            'promotion'               => $context->request->salePrice !== null ? (string) $context->request->salePrice : '',
            'produits_associes'       => implode(', ', $relatedTitles),
            'langue'                  => (string) $this->settings->get('language', 'fr'),
            'orientation'             => (string) ($context->analysis['orientation'] ?? ''),
        ];

        $compiled = $this->builder->build($prompt->content, $variables);

        $context->compiledPrompt = $compiled . "\n\n" . $this->builder->jsonSchemaInstruction();
    }
}
