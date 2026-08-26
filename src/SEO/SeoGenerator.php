<?php

declare(strict_types=1);

namespace AIProductStudio\SEO;

use AIProductStudio\Models\ProductData;
use AIProductStudio\Services\Settings;

/**
 * Writes the AI-generated SEO metadata to the active SEO plugin (Yoast or Rank
 * Math), auto-detecting which one is installed. Falls back to storing the data
 * as plugin meta so nothing is lost when no SEO plugin is present.
 */
final class SeoGenerator
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function apply(int $productId, ProductData $data): void
    {
        if (! $this->settings->get('generate_seo', true)) {
            return;
        }

        $target = (string) $this->settings->get('seo_plugin', 'auto');

        if ($target === 'auto') {
            $target = $this->detect();
        }

        // Always keep a copy on our own meta keys.
        update_post_meta($productId, '_aips_meta_title', $data->metaTitle);
        update_post_meta($productId, '_aips_meta_description', $data->metaDescription);

        switch ($target) {
            case 'yoast':
                update_post_meta($productId, '_yoast_wpseo_title', $data->metaTitle);
                update_post_meta($productId, '_yoast_wpseo_metadesc', $data->metaDescription);
                break;

            case 'rankmath':
                update_post_meta($productId, 'rank_math_title', $data->metaTitle);
                update_post_meta($productId, 'rank_math_description', $data->metaDescription);
                break;

            default:
                // No SEO plugin: our own meta already stored above.
                break;
        }
    }

    private function detect(): string
    {
        if (defined('WPSEO_VERSION') || class_exists('WPSEO_Meta')) {
            return 'yoast';
        }

        if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) {
            return 'rankmath';
        }

        return 'none';
    }
}
