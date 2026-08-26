<?php
/**
 * Generate a product view.
 *
 * @var array<int, \AIProductStudio\Prompt\Prompt> $prompts
 * @var array<string, string>                      $providers
 * @var array<int, array{key: string, label: string}> $steps
 * @var string                                     $defaultProvider
 * @var bool                                       $wooActive
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aips-wrap">
    <h1><?php esc_html_e('Générer un produit', 'ai-product-studio'); ?></h1>

    <?php if (! $wooActive) : ?>
        <div class="notice notice-error"><p><?php esc_html_e('WooCommerce est requis pour générer des produits.', 'ai-product-studio'); ?></p></div>
    <?php endif; ?>

    <div class="aips-generate">
        <form id="aips-generate-form" class="aips-generate__form">
            <div class="aips-field">
                <label><?php esc_html_e('Image principale', 'ai-product-studio'); ?> <span class="aips-required">*</span></label>
                <div id="aips-main-image" class="aips-image-picker"></div>
                <input type="hidden" name="main_image_id" id="aips-main-image-id" value="">
                <button type="button" class="button aips-pick-main"><?php esc_html_e('Choisir l\'image', 'ai-product-studio'); ?></button>
            </div>

            <div class="aips-field">
                <label><?php esc_html_e('Galerie', 'ai-product-studio'); ?></label>
                <div id="aips-gallery" class="aips-image-picker aips-image-picker--multi"></div>
                <input type="hidden" name="gallery_image_ids" id="aips-gallery-ids" value="">
                <button type="button" class="button aips-pick-gallery"><?php esc_html_e('Ajouter à la galerie', 'ai-product-studio'); ?></button>
            </div>

            <div class="aips-field aips-field--inline">
                <div>
                    <label for="aips-price"><?php esc_html_e('Prix', 'ai-product-studio'); ?></label>
                    <input type="number" step="0.01" min="0" id="aips-price" name="price" value="">
                </div>
                <div>
                    <label for="aips-sale-price"><?php esc_html_e('Prix promotionnel', 'ai-product-studio'); ?></label>
                    <input type="number" step="0.01" min="0" id="aips-sale-price" name="sale_price" value="">
                </div>
            </div>

            <div class="aips-field">
                <label for="aips-user-description"><?php esc_html_e('Description (facultatif)', 'ai-product-studio'); ?></label>
                <textarea id="aips-user-description" name="user_description" rows="3"></textarea>
            </div>

            <div class="aips-field">
                <label for="aips-related"><?php esc_html_e('Produits associés (IDs séparés par des virgules, facultatif)', 'ai-product-studio'); ?></label>
                <input type="text" id="aips-related" name="related_product_ids" value="" placeholder="e.g. 12, 34">
            </div>

            <div class="aips-field aips-field--inline">
                <div>
                    <label for="aips-provider"><?php esc_html_e('Fournisseur IA', 'ai-product-studio'); ?></label>
                    <select id="aips-provider" name="provider">
                        <?php foreach ($providers as $slug => $label) : ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($slug, $defaultProvider); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="aips-prompt"><?php esc_html_e('Prompt', 'ai-product-studio'); ?></label>
                    <select id="aips-prompt" name="prompt_id">
                        <option value="0"><?php esc_html_e('— Prompt actif par défaut —', 'ai-product-studio'); ?></option>
                        <?php foreach ($prompts as $prompt) : ?>
                            <option value="<?php echo esc_attr((string) $prompt->id); ?>"><?php echo esc_html($prompt->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="aips-actions">
                <button type="submit" class="button button-primary button-hero" id="aips-generate-btn" <?php disabled(! $wooActive); ?>>
                    <?php esc_html_e('Générer', 'ai-product-studio'); ?>
                </button>
                <button type="button" class="button" id="aips-cancel-btn" style="display:none;">
                    <?php esc_html_e('Annuler', 'ai-product-studio'); ?>
                </button>
            </div>
        </form>

        <aside class="aips-progress" id="aips-progress" style="display:none;">
            <h2><?php esc_html_e('Progression', 'ai-product-studio'); ?></h2>
            <ul class="aips-steps">
                <?php foreach ($steps as $step) : ?>
                    <li class="aips-step" data-step="<?php echo esc_attr($step['key']); ?>">
                        <span class="aips-step__icon">○</span>
                        <span class="aips-step__label"><?php echo esc_html($step['label']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="aips-progress__bar"><span id="aips-progress-fill"></span></div>
            <div id="aips-result" class="aips-result"></div>
        </aside>
    </div>
</div>
