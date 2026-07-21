<?php

declare(strict_types=1);

namespace AIProductStudio\WooCommerce;

use AIProductStudio\Exceptions\WorkflowException;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Models\ProductData;
use WC_Product_Simple;

/**
 * Creates a WooCommerce product from validated AI data and the user's input.
 * All WooCommerce coupling lives here so the rest of the pipeline stays agnostic.
 */
final class ProductCreator {

	/**
	 * @throws WorkflowException
	 */
	public function create( ProductData $data, GenerationRequest $request, string $status = 'draft' ): int {
		if ( ! class_exists( WC_Product_Simple::class ) ) {
			throw new WorkflowException(
				__( 'WooCommerce n\'est pas actif : impossible de créer le produit.', 'ai-product-studio' ),
				'woocommerce'
			);
		}

		$product = new WC_Product_Simple();

		$product->set_name( $data->title );
		$product->set_slug( $data->slug );
		$product->set_description( $data->longDescription );
		$product->set_short_description( $data->shortDescription );
		$product->set_status( $status );

		$product->set_regular_price( (string) $request->price );
		if ( $request->salePrice !== null && $request->salePrice > 0 && $request->salePrice < $request->price ) {
			$product->set_sale_price( (string) $request->salePrice );
		}

		// Main image + gallery.
		$product->set_image_id( $request->mainImageId );
		if ( $request->galleryImageIds !== array() ) {
			$product->set_gallery_image_ids( $request->galleryImageIds );
		}

		// Related / upsell products.
		if ( $request->relatedProductIds !== array() ) {
			$product->set_upsell_ids( $request->relatedProductIds );
		}

		// Taxonomies.
		$categoryIds = $this->resolveTerms( $data->categories, 'product_cat' );
		if ( $categoryIds !== array() ) {
			$product->set_category_ids( $categoryIds );
		}

		$tagIds = $this->resolveTerms( $data->tags, 'product_tag' );
		if ( $tagIds !== array() ) {
			$product->set_tag_ids( $tagIds );
		}

		$productId = $product->save();

		if ( ! $productId ) {
			throw new WorkflowException(
				__( 'WooCommerce a échoué lors de l\'enregistrement du produit.', 'ai-product-studio' ),
				'woocommerce'
			);
		}

		$this->applyImageMeta( $request->mainImageId, $data );

		return (int) $productId;
	}

	/**
	 * Ensure each term exists (creating it when missing) and return the IDs.
	 *
	 * @param array<int, string> $names
	 *
	 * @return array<int, int>
	 */
	private function resolveTerms( array $names, string $taxonomy ): array {
		$ids = array();

		foreach ( $names as $name ) {
			$term = term_exists( $name, $taxonomy );

			if ( $term === null || $term === 0 ) {
				$term = wp_insert_term( $name, $taxonomy );
			}

			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				$ids[] = (int) $term['term_id'];
			}
		}

		return $ids;
	}

	/**
	 * Store AI-generated alt text / caption / description on the main image.
	 */
	private function applyImageMeta( int $attachmentId, ProductData $data ): void {
		if ( $attachmentId <= 0 ) {
			return;
		}

		if ( $data->imageAlt !== '' ) {
			update_post_meta( $attachmentId, '_wp_attachment_image_alt', $data->imageAlt );
		}

		$postFields = array();
		if ( $data->imageCaption !== '' ) {
			$postFields['post_excerpt'] = $data->imageCaption;
		}
		if ( $data->imageDescription !== '' ) {
			$postFields['post_content'] = $data->imageDescription;
		}

		if ( $postFields !== array() ) {
			$postFields['ID'] = $attachmentId;
			wp_update_post( $postFields );
		}
	}
}
