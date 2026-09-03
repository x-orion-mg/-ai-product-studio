<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

use AIProductStudio\Exceptions\AIProductStudioException;
use AIProductStudio\Feature\FeatureInterface;
use AIProductStudio\History\HistoryRepository;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Prompt\Prompt;
use Throwable;

/**
 * Generic orchestrator: run an agent workflow, record history, update progress.
 * It has no knowledge of Product, Blog or any other business Feature.
 */
final class AgentRunner
{
    public function __construct(
        private readonly AgentRegistry $registry,
        private readonly WorkflowEngine $engine,
        private readonly ProgressStore $progress,
        private readonly HistoryRepository $history,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public function runFeature(FeatureInterface $feature, string $agentId, AgentContext $context, array $fields): array
    {
        $agent = $this->registry->get($agentId !== '' ? $agentId : $feature->defaultAgentId());

        $allowed = $feature->agentIds();
        if ($allowed !== [] && ! in_array($agent->id(), $allowed, true)) {
            throw new AIProductStudioException(
                __('Cet agent n\'est pas autorisé pour cette fonctionnalité.', 'ai-product-studio')
            );
        }

        $feature->hydrateContext($context, $fields);

        try {
            $this->engine->run($agent, $context);

            $this->history->record(
                $feature->entityId($context),
                $context->provider() !== '' ? $context->provider() : (string) $context->get('provider', ''),
                $this->promptId($context),
                HistoryRepository::STATUS_SUCCESS,
                $context->elapsed(),
                $feature->historyMessage($context),
                [ 'result' => $feature->present($context) ],
                $feature->id(),
                $feature->entityId($context)
            );

            $this->progress->setStatus($context->jobId(), 'completed');

            return $feature->present($context);
        } catch (Throwable $e) {
            $this->logger->error('Échec d\'exécution d\'une Feature.', [
                'feature' => $feature->id(),
                'agent'   => $agent->id(),
                'error'   => $e->getMessage(),
            ]);

            $this->history->record(
                $feature->entityId($context),
                $context->provider() !== '' ? $context->provider() : (string) $context->get('provider', ''),
                $this->promptId($context),
                HistoryRepository::STATUS_ERROR,
                $context->elapsed(),
                $e->getMessage(),
                [],
                $feature->id(),
                $feature->entityId($context)
            );

            $this->progress->setStatus($context->jobId(), 'error', $e->getMessage());

            if ($e instanceof AIProductStudioException) {
                throw $e;
            }

            throw new AIProductStudioException($e->getMessage(), 0, $e);
        }
    }

    private function promptId(AgentContext $context): int
    {
        $prompt = $context->get('prompt');

        if ($prompt instanceof Prompt) {
            return $prompt->id;
        }

        return (int) $context->get('prompt_id', 0);
    }
}
