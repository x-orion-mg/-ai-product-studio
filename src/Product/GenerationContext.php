<?php

declare(strict_types=1);

namespace AIProductStudio\Product;

use AIProductStudio\Models\AiResponse;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Models\ProductData;
use AIProductStudio\Prompt\Prompt;

/**
 * Mutable carrier passed through every pipeline step. Each step reads what it
 * needs and enriches the context for the next one, keeping steps decoupled.
 */
final class GenerationContext {

	public GenerationRequest $request;

	public ?Prompt $prompt = null;

	/** @var array<int, array{mime: string, data: string}> */
	public array $images = array();

	/** @var array<string, mixed> */
	public array $analysis = array();

	public string $compiledPrompt = '';

	public ?AiResponse $aiResponse = null;

	public ?ProductData $productData = null;

	public int $productId = 0;

	public float $startedAt;

	public function __construct( GenerationRequest $request ) {
		$this->request   = $request;
		$this->startedAt = microtime( true );
	}

	public function elapsed(): float {
		return round( microtime( true ) - $this->startedAt, 2 );
	}
}
