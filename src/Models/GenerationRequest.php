<?php

declare(strict_types=1);

namespace AIProductStudio\Models;

/**
 * Immutable value object describing everything the user submitted through the
 * "Générer un produit" form.
 */
final class GenerationRequest
{
    /**
     * @param array<int, int> $galleryImageIds
     * @param array<int, int> $relatedProductIds
     */
    public function __construct(
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
}
