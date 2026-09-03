<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

use AIProductStudio\Exceptions\AIProductStudioException;
use AIProductStudio\Exceptions\WorkflowException;
use AIProductStudio\Logger\Logger;
use Throwable;

/**
 * Generic step runner. It does not know any business agent: it executes the
 * ordered step list, reports progress, retries when asked, and stops cleanly.
 */
final class WorkflowEngine
{
    public function __construct(
        private readonly Logger $logger,
        private readonly ProgressStore $progress
    ) {
    }

    public function run(AgentInterface $agent, AgentContext $context): AgentContext
    {
        foreach ($agent->steps() as $step) {
            if ($this->progress->isCancelled($context->jobId())) {
                $this->progress->setStatus(
                    $context->jobId(),
                    'cancelled',
                    __('Génération annulée.', 'ai-product-studio')
                );

                throw new WorkflowException(
                    __('Génération annulée.', 'ai-product-studio'),
                    $step->id()
                );
            }

            $this->progress->mark($context->jobId(), $step->id(), $step->label(), 'running');
            $this->logger->debug('Étape démarrée.', [
                'agent' => $agent->id(),
                'step'  => $step->id(),
            ]);

            $result = $this->executeWithRetry($step, $context);

            if ($result->isHalt()) {
                $this->progress->setStatus($context->jobId(), 'cancelled', $result->message());
                $this->progress->mark($context->jobId(), $step->id(), $step->label(), 'cancelled');

                throw new WorkflowException(
                    $result->message() !== '' ? $result->message() : __('Arrêt du workflow.', 'ai-product-studio'),
                    $step->id()
                );
            }

            if ($result->isFailure()) {
                $this->progress->mark($context->jobId(), $step->id(), $step->label(), 'error');

                throw new WorkflowException(
                    $result->message() !== '' ? $result->message() : __('Échec de l\'étape.', 'ai-product-studio'),
                    $step->id()
                );
            }

            $this->logger->debug('Étape terminée.', [
                'agent'  => $agent->id(),
                'step'   => $step->id(),
                'status' => $result->status(),
            ]);
            $this->progress->mark($context->jobId(), $step->id(), $step->label(), 'done');
        }

        return $context;
    }

    private function executeWithRetry(StepInterface $step, AgentContext $context): StepResult
    {
        $attempts = 0;
        $max      = max(0, $step->maxRetries());
        $last     = null;

        do {
            try {
                $result = $step->execute($context);

                if ($result->shouldRetry() && $attempts < $max) {
                    ++$attempts;
                    $this->logger->warning('Étape en retry.', [
                        'step'    => $step->id(),
                        'attempt' => $attempts,
                        'error'   => $result->message(),
                    ]);
                    continue;
                }

                if ($result->shouldRetry()) {
                    return StepResult::failure(
                        $result->message() !== '' ? $result->message() : __('Retry épuisé.', 'ai-product-studio')
                    );
                }

                return $result;
            } catch (AIProductStudioException $e) {
                $last = $e;

                if ($attempts < $max) {
                    ++$attempts;
                    $this->logger->warning('Étape en retry après exception.', [
                        'step'    => $step->id(),
                        'attempt' => $attempts,
                        'error'   => $e->getMessage(),
                    ]);
                    continue;
                }

                throw $e;
            } catch (Throwable $e) {
                $last = $e;

                if ($attempts < $max) {
                    ++$attempts;
                    $this->logger->warning('Étape en retry après erreur inattendue.', [
                        'step'    => $step->id(),
                        'attempt' => $attempts,
                        'error'   => $e->getMessage(),
                    ]);
                    continue;
                }

                throw new WorkflowException($e->getMessage(), $step->id());
            }
        } while ($attempts <= $max);

        return StepResult::failure(
            $last instanceof Throwable ? $last->getMessage() : __('Échec de l\'étape.', 'ai-product-studio')
        );
    }
}
