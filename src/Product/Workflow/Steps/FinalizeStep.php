<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow\Steps;

use AIProductStudio\Product\GenerationContext;
use AIProductStudio\Product\Workflow\StepInterface;

/**
 * Final housekeeping hook. Fires an action so third-party code can react to a
 * freshly generated product without touching the pipeline.
 */
final class FinalizeStep implements StepInterface {

	public function key(): string {
		return 'finalize';
	}

	public function label(): string {
		return __( 'Finalisation', 'ai-product-studio' );
	}

	public function handle( GenerationContext $context ): void {
		/**
		 * Fires once a product has been fully generated.
		 *
		 * @param int               $productId
		 * @param GenerationContext $context
		 */
		do_action( 'aips_product_generated', $context->productId, $context );
	}
}
