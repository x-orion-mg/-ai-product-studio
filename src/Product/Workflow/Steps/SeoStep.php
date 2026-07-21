<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;
use AIProductStudio\SEO\SeoGenerator;

/**
 * Persists the AI-generated SEO metadata to the active SEO plugin.
 */
final class SeoStep implements StepInterface {

	private SeoGenerator $seo;

	public function __construct( SeoGenerator $seo ) {
		$this->seo = $seo;
	}

	public function key(): string {
		return 'seo';
	}

	public function label(): string {
		return __( 'Génération SEO', 'ai-product-studio' );
	}

	public function handle( GenerationContext $context ): void {
		if ( $context->productId <= 0 || $context->productData === null ) {
			return;
		}

		$this->seo->apply( $context->productId, $context->productData );
	}
}
