<?php
/**
 * Plugin Name:       AI Product Studio
 * Plugin URI:        https://github.com/mahery-rak/ai-product-studio
 * Description:       Automatise la création complète de produits WooCommerce à partir d'une image grâce à l'IA. Architecture modulaire, orientée objet et extensible (providers IA multiples, pipeline, prompts, historique, logs).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Mahery RAKOTOARISON
 * Author URI:        https://github.com/mahery-rak
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-product-studio
 * Domain Path:       /languages
 * WC requires at least: 6.0
 * WC tested up to:   9.0
 *
 * @package AIProductStudio
 */

declare(strict_types=1);

namespace AIProductStudio;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin constants.
 */
define('AIPS_VERSION', '1.0.0');
define('AIPS_PLUGIN_FILE', __FILE__);
define('AIPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AIPS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AIPS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AIPS_MIN_PHP', '8.0');
define('AIPS_STORAGE_DIR', AIPS_PLUGIN_DIR . 'storage/');

/**
 * Load the autoloader (Composer if available, otherwise the PSR-4 fallback).
 */
require_once AIPS_PLUGIN_DIR . 'src/Core/Autoloader.php';

if (is_readable(AIPS_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once AIPS_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    (new Core\Autoloader('AIProductStudio\\', AIPS_PLUGIN_DIR . 'src/'))->register();
}

/**
 * Guard against unsupported PHP versions.
 */
if (version_compare(PHP_VERSION, AIPS_MIN_PHP, '<')) {
    add_action('admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: 1: required PHP version, 2: current PHP version. */
                    __('AI Product Studio requiert PHP %1$s ou supérieur. Vous utilisez %2$s.', 'ai-product-studio'),
                    AIPS_MIN_PHP,
                    PHP_VERSION
                )
            )
        );
    });

    return;
}

/**
 * Activation / deactivation hooks.
 */
register_activation_hook(__FILE__, [Core\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [Core\Deactivator::class, 'deactivate']);

/**
 * Declare compatibility with WooCommerce HPOS (High-Performance Order Storage).
 */
add_action('before_woocommerce_init', static function (): void {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

/**
 * Boot the plugin once all plugins are loaded.
 */
add_action('plugins_loaded', static function (): void {
    Core\Plugin::instance()->boot();
}, 20);
