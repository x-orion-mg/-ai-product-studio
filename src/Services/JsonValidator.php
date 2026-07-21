<?php

declare(strict_types=1);

namespace AIProductStudio\Services;

use AIProductStudio\Exceptions\ValidationException;
use AIProductStudio\Models\ProductData;

/**
 * Validates the decoded AI payload against the expected product schema and
 * normalises it into a strongly-typed {@see ProductData} value object.
 */
final class JsonValidator {

	/**
	 * @param array<string, mixed> $data
	 *
	 * @throws ValidationException
	 */
	public function validate( array $data ): ProductData {
		$errors = array();

		$title = isset( $data['title'] ) ? trim( (string) $data['title'] ) : '';
		if ( $title === '' ) {
			$errors[] = __( 'Le champ « title » est obligatoire.', 'ai-product-studio' );
		}

		$longDescription = isset( $data['long_description'] ) ? (string) $data['long_description'] : '';
		if ( trim( $longDescription ) === '' ) {
			$errors[] = __( 'Le champ « long_description » est obligatoire.', 'ai-product-studio' );
		}

		if ( $errors !== array() ) {
			throw new ValidationException(
				__( 'Le JSON produit renvoyé par l\'IA est incomplet.', 'ai-product-studio' ),
				$errors
			);
		}

		$seo   = is_array( $data['seo'] ?? null ) ? $data['seo'] : array();
		$image = is_array( $data['image'] ?? null ) ? $data['image'] : array();

		return new ProductData(
			title: $title,
			slug: isset( $data['slug'] ) ? sanitize_title( (string) $data['slug'] ) : sanitize_title( $title ),
			shortDescription: (string) ( $data['short_description'] ?? '' ),
			longDescription: $longDescription,
			metaTitle: (string) ( $seo['meta_title'] ?? $title ),
			metaDescription: (string) ( $seo['meta_description'] ?? '' ),
			imageAlt: (string) ( $image['alt'] ?? $title ),
			imageCaption: (string) ( $image['caption'] ?? '' ),
			imageDescription: (string) ( $image['description'] ?? '' ),
			categories: $this->stringList( $data['categories'] ?? array() ),
			tags: $this->stringList( $data['tags'] ?? array() ),
			attributes: is_array( $data['attributes'] ?? null ) ? $data['attributes'] : array()
		);
	}

	/**
	 * @param mixed $value
	 *
	 * @return array<int, string>
	 */
	private function stringList( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $item ) {
			$item = trim( (string) $item );
			if ( $item !== '' ) {
				$out[] = $item;
			}
		}

		return array_values( array_unique( $out ) );
	}
}
