<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

use AIProductStudio\Admin\AdminMenu;
use AIProductStudio\Agent\AgentRegistry;
use AIProductStudio\Agent\ProgressStore;
use AIProductStudio\Agent\WorkflowEngine;
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
use AIProductStudio\Prompt\PromptBuilder;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\SEO\SeoGenerator;
use AIProductStudio\Services\JsonValidator;
use AIProductStudio\Services\ResponseParser;
use AIProductStudio\Services\Settings;
use AIProductStudio\WooCommerce\ProductCreator;

/**
 * Central bootstrap and service container of the plugin. Wires generic services
 * only: business agents register themselves via each Agents subfolder bootstrap.
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
        $this->loadAgentBootstraps();
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

    /**
     * Discover agent bootstraps. Core never imports a concrete agent class.
     */
    private function loadAgentBootstraps(): void
    {
        $files = glob(AIPS_PLUGIN_DIR . 'src/Agents/*/bootstrap.php') ?: [];

        foreach ($files as $file) {
            $register = require $file;

            if (is_callable($register)) {
                add_action('aips_register_agents', $register, 10, 2);
            }
        }
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

        $c->set(ProgressStore::class, static fn (): ProgressStore => new ProgressStore());

        $c->set(WorkflowEngine::class, static fn (Container $c): WorkflowEngine => new WorkflowEngine(
            $c->get(Logger::class),
            $c->get(ProgressStore::class)
        ));

        $c->set(AgentRegistry::class, static function (Container $c): AgentRegistry {
            $registry = new AgentRegistry();

            /**
             * Register business agents. Each Agents/<Name>/bootstrap.php hooks here.
             *
             * @param AgentRegistry $registry
             * @param Container     $c
             */
            do_action('aips_register_agents', $registry, $c);

            return $registry;
        });

        $c->set(ProductGenerator::class, static fn (Container $c): ProductGenerator => new ProductGenerator(
            $c->get(AgentRegistry::class),
            $c->get(WorkflowEngine::class),
            $c->get(ProgressStore::class),
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
