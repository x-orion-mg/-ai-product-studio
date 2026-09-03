<?php

declare(strict_types=1);

namespace AIProductStudio\Admin;

use AIProductStudio\Agent\AgentRegistry;
use AIProductStudio\Agent\ProgressStore;
use AIProductStudio\AI\ModelCatalog;
use AIProductStudio\AI\ProviderFactory;
use AIProductStudio\Core\Container;
use AIProductStudio\Feature\FeatureInterface;
use AIProductStudio\Form\Fields\HiddenField;
use AIProductStudio\Form\Fields\SelectField;
use AIProductStudio\Form\Form;
use AIProductStudio\Form\FormRenderer;
use AIProductStudio\Services\Settings;

/**
 * Generic admin page for a Feature that uses the shared form + JS runner.
 */
final class FeaturePage extends AbstractPage
{
    public function __construct(
        Container $container,
        private readonly FeatureInterface $feature
    ) {
        parent::__construct($container);
    }

    public function slug(): string
    {
        return $this->feature->slug();
    }

    public function title(): string
    {
        return $this->feature->title();
    }

    public function menuTitle(): string
    {
        return $this->feature->menuTitle();
    }

    public function render(): void
    {
        if (! $this->feature->usesGenericUi()) {
            $this->feature->render($this->container);

            return;
        }

        /** @var AgentRegistry $agents */
        $agents = $this->container->get(AgentRegistry::class);
        /** @var ProviderFactory $providers */
        $providers = $this->container->get(ProviderFactory::class);
        /** @var ModelCatalog $models */
        $models = $this->container->get(ModelCatalog::class);
        /** @var Settings $settings */
        $settings = $this->container->get(Settings::class);
        /** @var ProgressStore $progress */
        $progress = $this->container->get(ProgressStore::class);

        $defaultProvider = (string) $settings->get('default_provider', 'openai');
        $available       = $providers->available();
        $modelMap        = $models->modelsByProvider();

        if (! isset($available[$defaultProvider])) {
            $defaultProvider = (string) array_key_first($available);
        }

        $defaultModels = $modelMap[$defaultProvider] ?? [];
        $defaultModel  = (string) array_key_first($defaultModels);

        $agentChoices = [];
        foreach ($this->feature->agentIds() as $agentId) {
            if (! $agents->has($agentId)) {
                continue;
            }

            $agentChoices[$agentId] = $agents->get($agentId)->name();
        }

        $form = $this->withRuntimeFields(
            $this->feature->form(),
            $agentChoices,
            $available,
            $defaultModels,
            $this->feature->defaultAgentId(),
            $defaultProvider,
            $defaultModel
        );

        $agent = $agents->has($this->feature->defaultAgentId())
            ? $agents->get($this->feature->defaultAgentId())
            : null;

        $this->view('feature', [
            'feature'   => $this->feature,
            'form'      => $form,
            'renderer'  => new FormRenderer(),
            'steps'     => $agent !== null ? $progress->describe($agent) : [],
            'modelMap'  => $modelMap,
        ]);
    }

    /**
     * @param array<string, string> $agentChoices
     * @param array<string, string> $providers
     * @param array<string, string> $models
     */
    private function withRuntimeFields(
        \AIProductStudio\Form\FormInterface $base,
        array $agentChoices,
        array $providers,
        array $models,
        string $defaultAgent,
        string $defaultProvider,
        string $defaultModel
    ): Form {
        $fields = $base->fields();

        if (count($agentChoices) > 1) {
            $fields[] = new SelectField('agent', __('Agent IA', 'ai-product-studio'), true, [
                'options' => $agentChoices,
                'value'   => $defaultAgent,
                'class'   => 'aips-runtime-agent',
            ]);
        } else {
            $fields[] = new HiddenField('agent', __('Agent IA', 'ai-product-studio'), true, [
                'value' => $defaultAgent,
            ]);
        }

        $fields[] = new SelectField('provider', __('Fournisseur IA', 'ai-product-studio'), true, [
            'options' => $providers,
            'value'   => $defaultProvider,
            'class'   => 'aips-runtime-provider',
        ]);

        $fields[] = new SelectField('model', __('Modèle', 'ai-product-studio'), true, [
            'options' => $models !== [] ? $models : [ '' => __('Aucun modèle configuré', 'ai-product-studio') ],
            'value'   => $defaultModel,
            'class'   => 'aips-runtime-model',
            'strict'  => false,
            'help'    => __('Liste issue des clés API actives (onglet API).', 'ai-product-studio'),
        ]);

        return new Form($fields);
    }
}
