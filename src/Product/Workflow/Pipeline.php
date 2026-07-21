<?php

declare(strict_types=1);

namespace AIProductStudio\Product\Workflow;

use AIProductStudio\Logger\Logger;
use AIProductStudio\Product\GenerationContext;

/**
 * Runs an ordered list of steps against a context, notifying a progress
 * callback before and after each one. Adding, removing or reordering steps
 * requires no change to the steps themselves.
 */
final class Pipeline {

	/** @var array<int, StepInterface> */
	private array $steps;

	private Logger $logger;

	/** @var null|callable(string, string, string): void */
	private $progress;

	/**
	 * @param array<int, StepInterface> $steps
	 */
	public function __construct( array $steps, Logger $logger ) {
		$this->steps  = $steps;
		$this->logger = $logger;
	}

	/**
	 * @param callable(string $key, string $label, string $state): void $callback
	 */
	public function onProgress( callable $callback ): void {
		$this->progress = $callback;
	}

	/**
	 * @return array<int, array{key: string, label: string}>
	 */
	public function describe(): array {
		return array_map(
			static fn ( StepInterface $step ): array => array(
				'key'   => $step->key(),
				'label' => $step->label(),
			),
			$this->steps
		);
	}

	public function run( GenerationContext $context ): GenerationContext {
		foreach ( $this->steps as $step ) {
			$this->notify( $step, 'running' );

			$this->logger->debug( 'Étape démarrée.', array( 'step' => $step->key() ) );
			$step->handle( $context );
			$this->logger->debug( 'Étape terminée.', array( 'step' => $step->key() ) );

			$this->notify( $step, 'done' );
		}

		return $context;
	}

	private function notify( StepInterface $step, string $state ): void {
		if ( $this->progress !== null ) {
			( $this->progress )( $step->key(), $step->label(), $state );
		}
	}
}
