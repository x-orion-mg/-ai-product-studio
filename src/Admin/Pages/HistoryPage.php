<?php

declare(strict_types=1);

namespace AIProductStudio\Admin\Pages;

use AIProductStudio\Admin\AbstractPage;
use AIProductStudio\History\HistoryRepository;

final class HistoryPage extends AbstractPage
{
    private const PER_PAGE = 20;

    public function slug(): string
    {
        return 'history';
    }

    public function title(): string
    {
        return __('Historique — AI Product Studio', 'ai-product-studio');
    }

    public function menuTitle(): string
    {
        return __('Historique', 'ai-product-studio');
    }

    public function render(): void
    {
        /** @var HistoryRepository $repository */
        $repository = $this->container->get(HistoryRepository::class);

        $page  = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $total = $repository->count();

        $this->view('history', [
            'entries'  => $repository->paginate($page, self::PER_PAGE),
            'page'     => $page,
            'perPage'  => self::PER_PAGE,
            'total'    => $total,
            'pages'    => (int) ceil($total / self::PER_PAGE),
        ]);
    }
}
