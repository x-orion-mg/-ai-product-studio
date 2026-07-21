<?php

declare(strict_types=1);

namespace AIProductStudio\Ajax;

use AIProductStudio\Core\Container;

/**
 * Maps AJAX actions to controller methods and registers them with WordPress.
 * Controllers are resolved lazily from the container.
 */
final class AjaxRouter {

	private Container $container;

	/**
	 * action => [controller-class, method]
	 *
	 * @var array<string, array{0: class-string, 1: string}>
	 */
	private array $routes;

	public function __construct( Container $container ) {
		$this->container = $container;

		$this->routes = array(
			'aips_generate_product'    => array( GenerateController::class, 'generate' ),
			'aips_generation_progress' => array( GenerateController::class, 'progress' ),
			'aips_cancel_generation'   => array( GenerateController::class, 'cancel' ),
			'aips_save_prompt'         => array( PromptController::class, 'save' ),
			'aips_delete_prompt'       => array( PromptController::class, 'delete' ),
			'aips_toggle_prompt'       => array( PromptController::class, 'toggle' ),
			'aips_save_api_key'        => array( ApiKeyController::class, 'save' ),
			'aips_delete_api_key'      => array( ApiKeyController::class, 'delete' ),
			'aips_toggle_api_key'      => array( ApiKeyController::class, 'toggle' ),
		);
	}

	public function register(): void {
		foreach ( $this->routes as $action => [$class, $method] ) {
			add_action(
				'wp_ajax_' . $action,
				function () use ( $class, $method ): void {
					$controller = $this->container->get( $class );
					$controller->{$method}();
				}
			);
		}
	}
}
