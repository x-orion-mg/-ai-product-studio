<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow;

use AIProductStudio\Product\GenerationContext;

/**
 * A single unit of work in the product-generation pipeline. Steps are ordered,
 * self-contained and communicate only through the {@see GenerationContext}.
 */
interface StepInterface {

	/**
	 * Stable machine key used for progress reporting.
	 */
	public function key(): string;

	/**
	 * Human-readable label shown in the progress bar.
	 */
	public function label(): string;

	/**
	 * Execute the step, mutating the context.
	 */
	public function handle( GenerationContext $context ): void;
}
