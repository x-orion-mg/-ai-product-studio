<?php

declare(strict_types=1);

namespace AIProductStudio\Product;

use AIProductStudio\Agent\AgentContext;
use AIProductStudio\Agent\AgentRegistry;
use AIProductStudio\Agent\ProgressStore;
use AIProductStudio\Agent\WorkflowEngine;
use AIProductStudio\Exceptions\AIProductStudioException;
use AIProductStudio\History\HistoryRepository;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Models\ProductData;
use AIProductStudio\Prompt\Prompt;
use Throwable;

/**
 * Compatibility facade: runs the product-creation agent, streams progress
 * into transients and records the outcome in history.
 */
final class ProductGenerator
{
    public const AGENT_ID        = 'product-creation';
    public const PROGRESS_PREFIX = ProgressStore::PREFIX;

    public function __construct(
        private readonly AgentRegistry $registry,
        private readonly WorkflowEngine $engine,
        private readonly ProgressStore $progress,
        private readonly HistoryRepository $history,
        private readonly Logger $logger
    ) {
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function steps(): array
    {
        return $this->progress->describe($this->registry->get(self::AGENT_ID));
    }

    /**
     * @return array{product_id: int, duration: float, product_data: array<string, mixed>}
     */
    public function generate(GenerationRequest $request, string $jobId): array
    {
        $agent   = $this->registry->get(self::AGENT_ID);
        $context = new AgentContext($agent->id(), $jobId, [ 'request' => $request ]);

        try {
            $this->engine->run($agent, $context);

            $duration    = $context->elapsed();
            $productId   = (int) $context->get('product_id', 0);
            $productData = $context->get('product_data');
            $prompt      = $context->get('prompt');

            $this->history->record(
                $productId,
                $request->provider,
                $prompt instanceof Prompt ? $prompt->id : 0,
                HistoryRepository::STATUS_SUCCESS,
                $duration,
                sprintf(__('Produit #%d généré.', 'ai-product-studio'), $productId),
                [ 'product_data' => $productData instanceof ProductData ? $productData->toArray() : [] ]
            );

            $this->progress->setStatus($jobId, 'completed');

            return [
                'product_id'   => $productId,
                'duration'     => $duration,
                'product_data' => $productData instanceof ProductData ? $productData->toArray() : [],
            ];
        } catch (Throwable $e) {
            $duration = $context->elapsed();
            $prompt   = $context->get('prompt');

            $this->logger->error('Échec de génération.', [
                'error'    => $e->getMessage(),
                'provider' => $request->provider,
            ]);

            $this->history->record(
                0,
                $request->provider,
                $prompt instanceof Prompt ? $prompt->id : $request->promptId,
                HistoryRepository::STATUS_ERROR,
                $duration,
                $e->getMessage()
            );

            $this->progress->setStatus($jobId, 'error', $e->getMessage());

            if ($e instanceof AIProductStudioException) {
                throw $e;
            }

            throw new AIProductStudioException($e->getMessage(), 0, $e);
        }
    }
}
