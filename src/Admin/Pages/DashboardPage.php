<?php

declare(strict_types=1);

namespace AIProductStudio\Admin\Pages;

use AIProductStudio\Admin\AbstractPage;
use AIProductStudio\API\ApiKeyRepository;
use AIProductStudio\History\HistoryRepository;
use AIProductStudio\Prompt\PromptRepository;

final class DashboardPage extends AbstractPage
{
    public function slug(): string
    {
        return 'dashboard';
    }

    public function title(): string
    {
        return __('Tableau de bord — My AI Agent', 'ai-product-studio');
    }

    public function menuTitle(): string
    {
        return __('Tableau de bord', 'ai-product-studio');
    }

    public function render(): void
    {
        /** @var HistoryRepository $history */
        $history = $this->container->get(HistoryRepository::class);
        /** @var PromptRepository $prompts */
        $prompts = $this->container->get(PromptRepository::class);
        /** @var ApiKeyRepository $keys */
        $keys = $this->container->get(ApiKeyRepository::class);

        $this->view('dashboard', [
            'historyCount' => $history->count(),
            'promptCount'  => $prompts->count(),
            'keyCount'     => count($keys->all()),
            'recent'       => $history->paginate(1, 5),
            'wooActive'    => class_exists('WooCommerce'),
        ]);
    }
}
