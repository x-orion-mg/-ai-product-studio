<?php

declare(strict_types=1);

namespace AIProductStudio\Admin\Pages;

use AIProductStudio\Admin\AbstractPage;
use AIProductStudio\Logger\Logger;

final class LogsPage extends AbstractPage
{
    public function slug(): string
    {
        return 'logs';
    }

    public function title(): string
    {
        return __('Logs — AI Product Studio', 'ai-product-studio');
    }

    public function menuTitle(): string
    {
        return __('Logs', 'ai-product-studio');
    }

    public function render(): void
    {
        /** @var Logger $logger */
        $logger = $this->container->get(Logger::class);

        // Handle "clear logs" action.
        if (isset($_POST['aips_clear_logs']) && check_admin_referer('aips_clear_logs', 'aips_logs_nonce')) {
            $logger->clear();
            echo '<div class="notice notice-success is-dismissible"><p>' .
                esc_html__('Logs vidés.', 'ai-product-studio') . '</p></div>';
        }

        $this->view('logs', [
            'lines' => $logger->tail(300),
        ]);
    }
}
