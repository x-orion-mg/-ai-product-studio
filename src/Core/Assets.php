<?php

declare(strict_types=1);

namespace AIProductStudio\Core;

use AIProductStudio\Product\ProductGenerator;

/**
 * Registers and enqueues admin CSS/JS, and exposes runtime data to the browser
 * (AJAX URL, nonce, pipeline steps).
 */
final class Assets
{
    private ProductGenerator $generator;

    public function __construct(ProductGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        // Only load on our own admin pages.
        if (! str_contains($hook, 'ai-product-studio')) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'aips-admin',
            AIPS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AIPS_VERSION
        );

        wp_enqueue_script(
            'aips-admin',
            AIPS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-i18n'],
            AIPS_VERSION,
            true
        );

        wp_localize_script('aips-admin', 'AIPS', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('aips_nonce'),
            'steps'   => $this->generator->steps(),
            'i18n'    => [
                'generating'  => __('Génération en cours…', 'ai-product-studio'),
                'cancelled'   => __('Génération annulée.', 'ai-product-studio'),
                'done'        => __('Terminé', 'ai-product-studio'),
                'error'       => __('Erreur', 'ai-product-studio'),
                'confirmDelete' => __('Confirmer la suppression ?', 'ai-product-studio'),
                'selectMain'  => __('Choisir l\'image principale', 'ai-product-studio'),
                'selectGallery' => __('Ajouter à la galerie', 'ai-product-studio'),
                'noImage'     => __('Veuillez ajouter une image principale.', 'ai-product-studio'),
                'noDescription' => __('Veuillez saisir une description produit.', 'ai-product-studio'),
                'noFile'      => __('Veuillez choisir un fichier CSV ou Excel.', 'ai-product-studio'),
                'parsing'     => __('Analyse du fichier…', 'ai-product-studio'),
                'rowsFound'   => __('Lignes détectées', 'ai-product-studio'),
                'importDone'  => __('produits créés.', 'ai-product-studio'),
            ],
        ]);
    }
}
