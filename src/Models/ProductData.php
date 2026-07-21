<?php

declare(strict_types=1);

namespace AIProductStudio\Models;

/**
 * Immutable value object describing the product content produced by the AI,
 * after validation. Passed to the WooCommerce creator.
 */
final class ProductData {

	/**
	 * @param array<int, string>   $categories
	 * @param array<int, string>   $tags
	 * @param array<string, mixed> $attributes
	 */
	public function __construct(
		public readonly string $title,
		public readonly string $slug,
		public readonly string $shortDescription,
		public readonly string $longDescription,
		public readonly string $metaTitle,
		public readonly string $metaDescription,
		public readonly string $imageAlt,
		public readonly string $imageCaption,
		public readonly string $imageDescription,
		public readonly array $categories = array(),
		public readonly array $tags = array(),
		public readonly array $attributes = array()
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'title'             => $this->title,
			'slug'              => $this->slug,
			'short_description' => $this->shortDescription,
			'long_description'  => $this->longDescription,
			'meta_title'        => $this->metaTitle,
			'meta_description'  => $this->metaDescription,
			'image_alt'         => $this->imageAlt,
			'image_caption'     => $this->imageCaption,
			'image_description' => $this->imageDescription,
			'categories'        => $this->categories,
			'tags'              => $this->tags,
			'attributes'        => $this->attributes,
		);
	}
}
