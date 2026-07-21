<?php

declare(strict_types=1);

namespace AIProductStudio\Product;

use AIProductStudio\Exceptions\AIProductStudioException;
use AIProductStudio\History\HistoryRepository;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Product\Workflow\Pipeline;
use Throwable;

/**
 * Orchestrates a full product generation: runs the pipeline, streams progress
 * into a transient (polled by the browser) and records the outcome in history.
 */
final class ProductGenerator
{
    public const PROGRESS_PREFIX = 'aips_progress_';

    private Pipeline $pipeline;

    private HistoryRepository $history;

    private Logger $logger;

    public function __construct(Pipeline $pipeline, HistoryRepository $history, Logger $logger)
    {
        $this->pipeline = $pipeline;
        $this->history  = $history;
        $this->logger   = $logger;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function steps(): array
    {
        return $this->pipeline->describe();
    }

    /**
     * @return array{product_id: int, duration: float, product_data: array<string, mixed>}
     */
    public function generate(GenerationRequest $request, string $jobId): array
    {
        $context = new GenerationContext($request);

        $this->pipeline->onProgress(function (string $key, string $label, string $state) use ($jobId): void {
            $this->updateProgress($jobId, $key, $label, $state);
        });

        try {
            $this->pipeline->run($context);

            $duration = $context->elapsed();

            $this->history->record(
                $context->productId,
                $request->provider,
                $context->prompt?->id ?? 0,
                HistoryRepository::STATUS_SUCCESS,
                $duration,
                sprintf(__('Produit #%d généré.', 'ai-product-studio'), $context->productId),
                ['product_data' => $context->productData?->toArray()]
            );

            $this->setProgressState($jobId, 'completed');

            return [
                'product_id'   => $context->productId,
                'duration'     => $duration,
                'product_data' => $context->productData?->toArray() ?? [],
            ];
        } catch (Throwable $e) {
            $duration = $context->elapsed();

            $this->logger->error('Échec de génération.', [
                'error'    => $e->getMessage(),
                'provider' => $request->provider,
            ]);

            $this->history->record(
                0,
                $request->provider,
                $context->prompt?->id ?? $request->promptId,
                HistoryRepository::STATUS_ERROR,
                $duration,
                $e->getMessage()
            );

            $this->setProgressState($jobId, 'error', $e->getMessage());

            if ($e instanceof AIProductStudioException) {
                throw $e;
            }

            throw new AIProductStudioException($e->getMessage(), 0, $e);
        }
    }

    private function updateProgress(string $jobId, string $key, string $label, string $state): void
    {
        $progress = $this->readProgress($jobId);
        $progress['steps'][$key] = ['label' => $label, 'state' => $state];
        $progress['current']     = $key;
        set_transient(self::PROGRESS_PREFIX . $jobId, $progress, 15 * MINUTE_IN_SECONDS);
    }

    private function setProgressState(string $jobId, string $status, string $message = ''): void
    {
        $progress            = $this->readProgress($jobId);
        $progress['status']  = $status;
        $progress['message'] = $message;
        set_transient(self::PROGRESS_PREFIX . $jobId, $progress, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * @return array<string, mixed>
     */
    private function readProgress(string $jobId): array
    {
        $progress = get_transient(self::PROGRESS_PREFIX . $jobId);

        if (! is_array($progress)) {
            $progress = ['steps' => [], 'status' => 'running', 'current' => '', 'message' => ''];
        }

        return $progress;
    }
}
