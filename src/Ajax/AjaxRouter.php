<?php

declare(strict_types=1);

namespace AIProductStudio\Ajax;

use AIProductStudio\Core\Container;

/**
 * Maps AJAX actions to controller methods and registers them with WordPress.
 * Controllers are resolved lazily from the container.
 */
final class AjaxRouter
{
    private Container $container;

    /**
     * action => [controller-class, method]
     *
     * @var array<string, array{0: class-string, 1: string}>
     */
    private array $routes;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->routes = [
            'aips_generate_product'    => [GenerateController::class, 'generate'],
            'aips_generation_progress' => [GenerateController::class, 'progress'],
            'aips_cancel_generation'   => [GenerateController::class, 'cancel'],
            'aips_save_prompt'         => [PromptController::class, 'save'],
            'aips_delete_prompt'       => [PromptController::class, 'delete'],
            'aips_toggle_prompt'       => [PromptController::class, 'toggle'],
            'aips_save_api_key'        => [ApiKeyController::class, 'save'],
            'aips_delete_api_key'      => [ApiKeyController::class, 'delete'],
            'aips_toggle_api_key'      => [ApiKeyController::class, 'toggle'],
        ];
    }

    public function register(): void
    {
        foreach ($this->routes as $action => [$class, $method]) {
            add_action('wp_ajax_' . $action, function () use ($class, $method): void {
                $controller = $this->container->get($class);
                $controller->{$method}();
            });
        }
    }
}
