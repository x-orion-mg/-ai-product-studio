<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

use AIProductStudio\API\ApiKeyRepository;
use AIProductStudio\History\HistoryRepository;
use AIProductStudio\Prompt\PromptRepository;
use AIProductStudio\Services\Settings;

/**
 * Runs once when the plugin is activated: creates the custom tables, seeds the
 * default prompts and stores default settings.
 */
final class Activator
{
    public static function activate(): void
    {
        self::createTables();
        self::ensureStorage();
        self::seedDefaults();

        add_option('aips_version', AIPS_VERSION);
        flush_rewrite_rules();
    }

    /**
     * Create the custom database tables via dbDelta.
     */
    public static function createTables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $prompts = PromptRepository::tableName();
        $keys    = ApiKeyRepository::tableName();
        $history = HistoryRepository::tableName();

        $queries = [];

        $queries[] = "CREATE TABLE {$prompts} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL DEFAULT '',
            description TEXT NULL,
            content LONGTEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY is_active (is_active)
        ) {$charset};";

        $queries[] = "CREATE TABLE {$keys} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(60) NOT NULL DEFAULT '',
            label VARCHAR(191) NOT NULL DEFAULT '',
            api_key TEXT NULL,
            model VARCHAR(191) NOT NULL DEFAULT '',
            endpoint VARCHAR(255) NOT NULL DEFAULT '',
            priority INT NOT NULL DEFAULT 10,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            error_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY provider (provider),
            KEY is_active (is_active),
            KEY priority (priority)
        ) {$charset};";

        $queries[] = "CREATE TABLE {$history} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            provider VARCHAR(60) NOT NULL DEFAULT '',
            prompt_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT '',
            duration FLOAT NOT NULL DEFAULT 0,
            message TEXT NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY status (status),
            KEY product_id (product_id),
            KEY created_at (created_at)
        ) {$charset};";

        foreach ($queries as $query) {
            dbDelta($query);
        }
    }

    /**
     * Make sure the storage directories exist and are protected.
     */
    public static function ensureStorage(): void
    {
        $dirs = [
            AIPS_STORAGE_DIR,
            AIPS_STORAGE_DIR . 'logs/',
            AIPS_STORAGE_DIR . 'sessions/',
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                wp_mkdir_p($dir);
            }

            $htaccess = $dir . '.htaccess';
            if (! file_exists($htaccess)) {
                file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
            }

            $index = $dir . 'index.php';
            if (! file_exists($index)) {
                file_put_contents($index, "<?php\n// Silence is golden.\n");
            }
        }
    }

    /**
     * Seed default settings and the default prompt catalogue.
     */
    private static function seedDefaults(): void
    {
        if (get_option(Settings::OPTION_KEY) === false) {
            add_option(Settings::OPTION_KEY, Settings::defaults());
        }

        $repository = new PromptRepository();

        if ($repository->count() === 0) {
            $defaults = require AIPS_PLUGIN_DIR . 'config/default-prompts.php';

            foreach ($defaults as $prompt) {
                $repository->create($prompt);
            }
        }
    }
}
