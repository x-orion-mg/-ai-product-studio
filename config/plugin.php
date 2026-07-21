<?php
/**
 * Static plugin configuration.
 *
 * @package AIProductStudio
 */

if (! defined('ABSPATH')) {
    exit;
}

return [
    'text_domain' => 'ai-product-studio',
    'capability'  => 'manage_woocommerce',
    'menu_slug'   => 'ai-product-studio',
    'nonce'       => 'aips_nonce',
    'supported_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],
];
