<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

use AIProductStudio\Admin\AdminMenu;
use AIProductStudio\AI\AiClient;
use AIProductStudio\AI\ProviderFactory;
use AIProductStudio\Ajax\AjaxRouter;
use AIProductStudio\Ajax\GenerateController;
use AIProductStudio\Ajax\ApiKeyController;
use AIProductStudio\Ajax\PromptController;
use AIProductStudio\API\ApiKeyRepository;
use AIProductStudio\API\ApiKeyRotator;
use AIProductStudio\History\HistoryRepository;
use AIProductStudio\Image\ImageAnalyzer;
use AIProductStudio\Image\ImageCompressor;
use AIProductStudio\Import\SpreadsheetParser;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Product\ProductGenerator;
use AIProductStudio\Product\Workflow\Pipeline;
use AIProductStudio\Product\Workflow\Steps\AnalyzeImageStep;
use AIProductStudio\Product\Workflow\Steps\BuildPromptStep;
use AIProductStudio\Product\Workflow\Steps\CallAiStep;
use AIProductStudio\Product\Workflow\Steps\CreateProductStep;
use AIProductStudio\Product\Workflow\Steps\FinalizeStep;
use AIProductStudio\Product\Workflow\Steps\PrepareImagesStep;
use AIProductStudio\Product\Workflow\Steps\SeoStep;
use AIProductStudio\Product\Workflow\Steps\ValidateResponseStep;
use AIProductStudio\Prompt\PromptBuilder;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\SEO\SeoGenerator;
use AIProductStudio\Services\JsonValidator;
use AIProductStudio\Services\ResponseParser;
use AIProductStudio\Services\Settings;
use AIProductStudio\WooCommerce\ProductCreator;

/**
 * Central bootstrap and service container of the plugin. Wires every service
 * as a lazy factory and registers the WordPress hooks.
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    private Container $container;

    private bool $booted = false;

    private function __construct()
    {
        $this->container = new Container();
        $this->registerServices();
    }

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function container(): Container
    {
        return $this->container;
    }

    /**
     * Register hooks. Called once on plugins_loaded.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        load_plugin_textdomain('ai-product-studio', false, dirname(AIPS_PLUGIN_BASENAME) . '/languages');

        // Admin UI.
        if (is_admin()) {
            /** @var AdminMenu $menu */
            $menu = $this->container->get(AdminMenu::class);
            $menu->register();

            /** @var Assets $assets */
            $assets = $this->container->get(Assets::class);
            $assets->register();
        }

        // AJAX endpoints (available in admin context).
        /** @var AjaxRouter $router */
        $router = $this->container->get(AjaxRouter::class);
        $router->register();

        // Missing-dependency notices.
        add_action('admin_notices', [$this, 'maybeWooCommerceNotice']);
    }

    public function maybeWooCommerceNotice(): void
    {
        if (class_exists('WooCommerce')) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s</p></div>',
            esc_html__('My AI Agent nécessite WooCommerce pour créer des produits. Veuillez installer et activer WooCommerce.', 'ai-product-studio')
        );
    }

    private function registerServices(): void
    {
        $c = $this->container;

        $c->set(Settings::class, static fn (): Settings => new Settings());

        $c->set(Logger::class, static fn (Container $c): Logger => new Logger($c->get(Settings::class)));

        $c->set(PromptRepository::class, static fn (): PromptRepository => new PromptRepository());
        $c->set(PromptBuilder::class, static fn (): PromptBuilder => new PromptBuilder());
        $c->set(ApiKeyRepository::class, static fn (): ApiKeyRepository => new ApiKeyRepository());
        $c->set(HistoryRepository::class, static fn (): HistoryRepository => new HistoryRepository());

        $c->set(ApiKeyRotator::class, static fn (Container $c): ApiKeyRotator => new ApiKeyRotator(
            $c->get(ApiKeyRepository::class),
            $c->get(Settings::class)
        ));

        $c->set(ProviderFactory::class, static fn (Container $c): ProviderFactory => new ProviderFactory(
            $c->get(Logger::class),
            $c->get(Settings::class)
        ));

        $c->set(AiClient::class, static fn (Container $c): AiClient => new AiClient(
            $c->get(ProviderFactory::class),
            $c->get(ApiKeyRotator::class),
            $c->get(Logger::class)
        ));

        $c->set(ImageCompressor::class, static fn (Container $c): ImageCompressor => new ImageCompressor(
            $c->get(Settings::class)
        ));
        $c->set(ImageAnalyzer::class, static fn (): ImageAnalyzer => new ImageAnalyzer());

        $c->set(ResponseParser::class, static fn (): ResponseParser => new ResponseParser());
        $c->set(JsonValidator::class, static fn (): JsonValidator => new JsonValidator());
        $c->set(SpreadsheetParser::class, static fn (): SpreadsheetParser => new SpreadsheetParser());

        $c->set(ProductCreator::class, static fn (): ProductCreator => new ProductCreator());
        $c->set(SeoGenerator::class, static fn (Container $c): SeoGenerator => new SeoGenerator(
            $c->get(Settings::class)
        ));

        $c->set(Pipeline::class, static function (Container $c): Pipeline {
            $steps = [
                new PrepareImagesStep($c->get(ImageCompressor::class)),
                new AnalyzeImageStep($c->get(ImageAnalyzer::class)),
                new BuildPromptStep(
                    $c->get(PromptRepository::class),
                    $c->get(PromptBuilder::class),
                    $c->get(Settings::class)
                ),
                new CallAiStep($c->get(AiClient::class)),
                new ValidateResponseStep(
                    $c->get(ResponseParser::class),
                    $c->get(JsonValidator::class)
                ),
                new CreateProductStep(
                    $c->get(ProductCreator::class),
                    $c->get(Settings::class)
                ),
                new SeoStep($c->get(SeoGenerator::class)),
                new FinalizeStep(),
            ];

            return new Pipeline($steps, $c->get(Logger::class));
        });

        $c->set(ProductGenerator::class, static fn (Container $c): ProductGenerator => new ProductGenerator(
            $c->get(Pipeline::class),
            $c->get(HistoryRepository::class),
            $c->get(Logger::class)
        ));

        // Admin.
        $c->set(AdminMenu::class, static fn (Container $c): AdminMenu => new AdminMenu($c));
        $c->set(Assets::class, static fn (Container $c): Assets => new Assets($c->get(ProductGenerator::class)));

        // Ajax controllers + router.
        $c->set(GenerateController::class, static fn (Container $c): GenerateController => new GenerateController(
            $c->get(ProductGenerator::class),
            $c->get(Logger::class),
            $c->get(SpreadsheetParser::class)
        ));
        $c->set(PromptController::class, static fn (Container $c): PromptController => new PromptController(
            $c->get(PromptRepository::class)
        ));
        $c->set(ApiKeyController::class, static fn (Container $c): ApiKeyController => new ApiKeyController(
            $c->get(ApiKeyRepository::class),
            $c->get(ProviderFactory::class)
        ));

        $c->set(AjaxRouter::class, static fn (Container $c): AjaxRouter => new AjaxRouter($c));
    }
}
