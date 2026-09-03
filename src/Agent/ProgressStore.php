<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

/**
 * Job progress and cancellation flags stored in WordPress transients so the
 * admin UI can poll independently of the running workflow.
 */
final class ProgressStore
{
    public const PREFIX        = 'aips_progress_';
    public const CANCEL_PREFIX = 'aips_cancel_';

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function describe(AgentInterface $agent): array
    {
        return array_map(
            static fn (StepInterface $step): array => [
                'key'   => $step->id(),
                'label' => $step->label(),
            ],
            $agent->steps()
        );
    }

    public function mark(string $jobId, string $key, string $label, string $state): void
    {
        $progress                   = $this->read($jobId);
        $progress['steps'][ $key ]  = [ 'label' => $label, 'state' => $state ];
        $progress['current']        = $key;
        $this->write($jobId, $progress);
    }

    public function setStatus(string $jobId, string $status, string $message = ''): void
    {
        $progress            = $this->read($jobId);
        $progress['status']  = $status;
        $progress['message'] = $message;
        $this->write($jobId, $progress);
    }

    /**
     * @return array<string, mixed>
     */
    public function read(string $jobId): array
    {
        $progress = get_transient(self::PREFIX . $jobId);

        if (! is_array($progress)) {
            $progress = [
                'steps'   => [],
                'status'  => 'running',
                'current' => '',
                'message' => '',
            ];
        }

        return $progress;
    }

    public function cancel(string $jobId): void
    {
        if ($jobId === '') {
            return;
        }

        set_transient(self::CANCEL_PREFIX . $jobId, 1, 15 * MINUTE_IN_SECONDS);
    }

    public function isCancelled(string $jobId): bool
    {
        return $jobId !== '' && (bool) get_transient(self::CANCEL_PREFIX . $jobId);
    }

    /**
     * @param array<string, mixed> $progress
     */
    private function write(string $jobId, array $progress): void
    {
        set_transient(self::PREFIX . $jobId, $progress, 15 * MINUTE_IN_SECONDS);
    }
}
