<?php

declare(strict_types=1);

namespace AIProductStudio\History;

/**
 * Value object mirroring a row of the history table.
 */
final class HistoryEntry
{
    public function __construct(
        public readonly int $id,
        public readonly int $productId,
        public readonly string $provider,
        public readonly int $promptId,
        public readonly string $status,
        public readonly float $duration,
        public readonly string $message,
        public readonly string $createdAt,
        public readonly string $feature = '',
        public readonly int $entityId = 0
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $productId = (int) ($row['product_id'] ?? 0);
        $entityId  = (int) ($row['entity_id'] ?? 0);

        return new self(
            id: (int) ($row['id'] ?? 0),
            productId: $productId,
            provider: (string) ($row['provider'] ?? ''),
            promptId: (int) ($row['prompt_id'] ?? 0),
            status: (string) ($row['status'] ?? ''),
            duration: (float) ($row['duration'] ?? 0),
            message: (string) ($row['message'] ?? ''),
            createdAt: (string) ($row['created_at'] ?? ''),
            feature: (string) ($row['feature'] ?? ''),
            entityId: $entityId > 0 ? $entityId : $productId
        );
    }

    public function contentId(): int
    {
        return $this->entityId > 0 ? $this->entityId : $this->productId;
    }
}
