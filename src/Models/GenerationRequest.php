<?php

declare(strict_types=1);

namespace AIProductStudio\Models;

/**
 * Immutable value object describing everything the user submitted through the
 * product generation forms.
 */
final class GenerationRequest
{
    public const SOURCE_IMAGE       = 'image';
    public const SOURCE_DESCRIPTION = 'description';
    public const SOURCE_IMPORT      = 'import';

    /**
     * @param array<int, int> $galleryImageIds
     * @param array<int, int> $relatedProductIds
     */
    public function __construct(
        public readonly string $source,
        public readonly int $mainImageId,
        public readonly array $galleryImageIds,
        public readonly float $price,
        public readonly ?float $salePrice,
        public readonly string $userDescription,
        public readonly array $relatedProductIds,
        public readonly int $promptId,
        public readonly string $provider
    ) {
    }

    public function hasImage(): bool
    {
        return $this->mainImageId > 0;
    }

    public function isImageSource(): bool
    {
        return $this->source === self::SOURCE_IMAGE;
    }
}
