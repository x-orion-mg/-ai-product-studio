<?php
/**
 * Dashboard view.
 *
 * @var int                                                   $historyCount
 * @var int                                                   $promptCount
 * @var int                                                   $keyCount
 * @var array<int, \AIProductStudio\History\HistoryEntry>     $recent
 * @var bool                                                  $wooActive
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><span class="dashicons dashicons-superhero"></span> <?php esc_html_e('AI Product Studio', 'ai-product-studio'); ?></h1>
    <p class="aips-subtitle"><?php esc_html_e('Automatisez la création de vos produits WooCommerce à partir d\'une image grâce à l\'IA.', 'ai-product-studio'); ?></p>

    <?php if (! $wooActive) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e('WooCommerce n\'est pas actif. La création de produits sera indisponible tant que WooCommerce n\'est pas installé.', 'ai-product-studio'); ?></p></div>
    <?php endif; ?>

    <div class="aips-cards">
        <div class="aips-card">
            <span class="aips-card__value"><?php echo esc_html((string) $historyCount); ?></span>
            <span class="aips-card__label"><?php esc_html_e('Générations', 'ai-product-studio'); ?></span>
        </div>
        <div class="aips-card">
            <span class="aips-card__value"><?php echo esc_html((string) $promptCount); ?></span>
            <span class="aips-card__label"><?php esc_html_e('Prompts', 'ai-product-studio'); ?></span>
        </div>
        <div class="aips-card">
            <span class="aips-card__value"><?php echo esc_html((string) $keyCount); ?></span>
            <span class="aips-card__label"><?php esc_html_e('Clés API', 'ai-product-studio'); ?></span>
        </div>
    </div>

    <p>
        <a class="button button-primary button-hero" href="<?php echo esc_url(admin_url('admin.php?page=ai-product-studio-generate')); ?>">
            <?php esc_html_e('➜ Générer un produit', 'ai-product-studio'); ?>
        </a>
    </p>

    <h2><?php esc_html_e('Dernières générations', 'ai-product-studio'); ?></h2>
    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Date', 'ai-product-studio'); ?></th>
                <th><?php esc_html_e('Statut', 'ai-product-studio'); ?></th>
                <th><?php esc_html_e('Fournisseur', 'ai-product-studio'); ?></th>
                <th><?php esc_html_e('Durée', 'ai-product-studio'); ?></th>
                <th><?php esc_html_e('Produit', 'ai-product-studio'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($recent === []) : ?>
            <tr><td colspan="5"><?php esc_html_e('Aucune génération pour le moment.', 'ai-product-studio'); ?></td></tr>
        <?php else : ?>
            <?php foreach ($recent as $entry) : ?>
                <tr>
                    <td><?php echo esc_html($entry->createdAt); ?></td>
                    <td><span class="aips-badge aips-badge--<?php echo esc_attr($entry->status); ?>"><?php echo esc_html($entry->status); ?></span></td>
                    <td><?php echo esc_html($entry->provider); ?></td>
                    <td><?php echo esc_html((string) $entry->duration); ?>s</td>
                    <td>
                        <?php if ($entry->productId > 0) : ?>
                            <a href="<?php echo esc_url((string) get_edit_post_link($entry->productId)); ?>">#<?php echo esc_html((string) $entry->productId); ?></a>
                        <?php else : ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
