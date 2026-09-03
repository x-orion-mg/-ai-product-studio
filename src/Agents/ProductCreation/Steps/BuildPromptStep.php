<?php

declare(strict_types=1);

namespace AIProductStudio\Agents\ProductCreation\Steps;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\StepInterface;
use AIProductStudio\Agent\StepResult;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Prompt\PromptBuilder;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\Services\Settings;

/**
 * Resolves the prompt template and compiles it with the request variables.
 */
final class BuildPromptStep implements StepInterface
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PromptBuilder $builder,
        private readonly Settings $settings
    ) {
    }

    public function id(): string
    {
        return 'build_prompt';
    }

    public function label(): string
    {
        return __('Construction du prompt', 'ai-product-studio');
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

        $prompt = $request->promptId > 0
            ? $this->prompts->find($request->promptId)
            : $this->prompts->firstActive();

        if ($prompt === null) {
            return StepResult::failure(
                __('Aucun prompt disponible. Créez-en un dans l\'onglet Prompts.', 'ai-product-studio')
            );
        }

        $context->set('prompt', $prompt);

        $relatedTitles = array_filter(array_map(
            static fn (int $id): string => (string) get_the_title($id),
            $request->relatedProductIds
        ));

        $sourceLabels = [
            GenerationRequest::SOURCE_IMAGE       => __('image principale', 'ai-product-studio'),
            GenerationRequest::SOURCE_DESCRIPTION => __('description texte', 'ai-product-studio'),
            GenerationRequest::SOURCE_IMPORT      => __('ligne importée (CSV/Excel)', 'ai-product-studio'),
        ];

        $analysis = is_array($context->get('analysis')) ? $context->get('analysis') : [];

        $variables = [
            'source'                  => $sourceLabels[ $request->source ] ?? $request->source,
            'description_utilisateur' => $request->userDescription,
            'image'                   => (string) ($analysis['filename'] ?? ''),
            'categorie'               => '',
            'prix'                    => (string) $request->price,
            'promotion'               => $request->salePrice !== null ? (string) $request->salePrice : '',
            'produits_associes'       => implode(', ', $relatedTitles),
            'langue'                  => (string) $this->settings->get('language', 'fr'),
            'orientation'             => (string) ($analysis['orientation'] ?? ''),
        ];

        $compiled = $this->builder->build($prompt->content, $variables);

        $context->set('compiled_prompt', $compiled . "\n\n" . $this->builder->jsonSchemaInstruction());

        return StepResult::success();
    }
}
