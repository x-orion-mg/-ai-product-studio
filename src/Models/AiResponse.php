<?php

declare(strict_types=1);

namespace AIProductStudio\Models;

/**
 * Wraps a raw response from an AI provider along with useful metadata for the
 * logger and the history repository.
 */
final class AiResponse {

	/**
	 * @param array<string, mixed> $raw
	 */
	public function __construct(
		public readonly string $content,
		public readonly string $provider,
		public readonly string $model,
		public readonly array $raw = array()
	) {
	}
}
