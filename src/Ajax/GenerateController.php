<?php

declare(strict_types=1);

namespace AIProductStudio\Ajax;

use AIProductStudio\Exceptions\AIProductStudioException;
use AIProductStudio\Exceptions\ValidationException;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Product\ProductGenerator;
use Throwable;

/**
 * Handles the product-generation AJAX flow: kick-off, progress polling and
 * cancellation.
 */
final class GenerateController extends AbstractController {

	private ProductGenerator $generator;

	private Logger $logger;

	public function __construct( ProductGenerator $generator, Logger $logger ) {
		$this->generator = $generator;
		$this->logger    = $logger;
	}

	public function generate(): void {
		$this->guard();

		$mainImageId = $this->postInt( 'main_image_id' );
		if ( $mainImageId <= 0 ) {
			$this->fail( __( 'Veuillez sélectionner une image principale.', 'ai-product-studio' ) );
		}

		$jobId = $this->post( 'job_id' );
		if ( $jobId === '' ) {
			$jobId = wp_generate_uuid4();
		}

		// Abort early if the user cancelled before we started heavy work.
		if ( $this->isCancelled( $jobId ) ) {
			$this->fail( __( 'Génération annulée.', 'ai-product-studio' ) );
		}

		$request = new GenerationRequest(
			mainImageId: $mainImageId,
			galleryImageIds: $this->postIntList( 'gallery_image_ids' ),
			price: $this->postFloat( 'price' ),
			salePrice: $this->postFloat( 'sale_price' ) > 0 ? $this->postFloat( 'sale_price' ) : null,
			userDescription: $this->postTextarea( 'user_description' ),
			relatedProductIds: $this->postIntList( 'related_product_ids' ),
			promptId: $this->postInt( 'prompt_id' ),
			provider: $this->post( 'provider', 'openai' )
		);

		try {
			$result = $this->generator->generate( $request, $jobId );

			$product = get_post( $result['product_id'] );

			$this->success(
				array(
					'job_id'     => $jobId,
					'product_id' => $result['product_id'],
					'duration'   => $result['duration'],
					'edit_link'  => get_edit_post_link( $result['product_id'], 'raw' ),
					'view_link'  => get_permalink( $result['product_id'] ),
					'title'      => $product instanceof \WP_Post ? $product->post_title : '',
				)
			);
		} catch ( ValidationException $e ) {
			$this->fail( $e->getMessage(), 422, array( 'errors' => $e->getErrors() ) );
		} catch ( AIProductStudioException $e ) {
			$this->fail( $e->getMessage(), 400 );
		} catch ( Throwable $e ) {
			$this->logger->error( 'Erreur inattendue lors de la génération.', array( 'error' => $e->getMessage() ) );
			$this->fail( __( 'Une erreur inattendue est survenue.', 'ai-product-studio' ), 500 );
		}
	}

	public function progress(): void {
		$this->guard();

		$jobId    = $this->post( 'job_id' );
		$progress = get_transient( ProductGenerator::PROGRESS_PREFIX . $jobId );

		if ( ! is_array( $progress ) ) {
			$progress = array(
				'steps'   => array(),
				'status'  => 'pending',
				'current' => '',
				'message' => '',
			);
		}

		$this->success( array( 'progress' => $progress ) );
	}

	public function cancel(): void {
		$this->guard();

		$jobId = $this->post( 'job_id' );
		if ( $jobId !== '' ) {
			set_transient( 'aips_cancel_' . $jobId, 1, 15 * MINUTE_IN_SECONDS );
		}

		$this->success( array( 'cancelled' => true ) );
	}

	private function isCancelled( string $jobId ): bool {
		return (bool) get_transient( 'aips_cancel_' . $jobId );
	}
}
