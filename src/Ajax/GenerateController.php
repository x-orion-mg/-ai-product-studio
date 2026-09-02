<?php

declare(strict_types=1);

namespace AIProductStudio\Ajax;

use AIProductStudio\Exceptions\AIProductStudioException;
use AIProductStudio\Exceptions\ValidationException;
use AIProductStudio\Import\SpreadsheetParser;
use AIProductStudio\Logger\Logger;
use AIProductStudio\Models\GenerationRequest;
use AIProductStudio\Product\ProductGenerator;
use Throwable;

/**
 * Handles the product-generation AJAX flow: kick-off, progress polling,
 * cancellation and spreadsheet parsing.
 */
final class GenerateController extends AbstractController
{
    private ProductGenerator $generator;

    private Logger $logger;

    private SpreadsheetParser $spreadsheet;

    public function __construct(ProductGenerator $generator, Logger $logger, SpreadsheetParser $spreadsheet)
    {
        $this->generator   = $generator;
        $this->logger      = $logger;
        $this->spreadsheet = $spreadsheet;
    }

    public function generate(): void
    {
        $this->guard();

        $source = $this->post('source', GenerationRequest::SOURCE_IMAGE);
        $allowed = [
            GenerationRequest::SOURCE_IMAGE,
            GenerationRequest::SOURCE_DESCRIPTION,
            GenerationRequest::SOURCE_IMPORT,
        ];

        if (! in_array($source, $allowed, true)) {
            $this->fail(__('Type de saisie invalide.', 'ai-product-studio'));
        }

        $mainImageId     = $this->postInt('main_image_id');
        $userDescription = $this->postTextarea('user_description');

        if ($source === GenerationRequest::SOURCE_IMAGE && $mainImageId <= 0) {
            $this->fail(__('Veuillez sélectionner une image principale.', 'ai-product-studio'));
        }

        if (
            in_array($source, [GenerationRequest::SOURCE_DESCRIPTION, GenerationRequest::SOURCE_IMPORT], true)
            && $userDescription === ''
        ) {
            $this->fail(__('Veuillez saisir une description produit.', 'ai-product-studio'));
        }

        if ($source !== GenerationRequest::SOURCE_IMAGE) {
            $mainImageId = 0;
        }

        $jobId = $this->post('job_id');
        if ($jobId === '') {
            $jobId = wp_generate_uuid4();
        }

        if ($this->isCancelled($jobId)) {
            $this->fail(__('Génération annulée.', 'ai-product-studio'));
        }

        $request = new GenerationRequest(
            source: $source,
            mainImageId: $mainImageId,
            galleryImageIds: $this->postIntList('gallery_image_ids'),
            price: $this->postFloat('price'),
            salePrice: $this->postFloat('sale_price') > 0 ? $this->postFloat('sale_price') : null,
            userDescription: $userDescription,
            relatedProductIds: $this->postIntList('related_product_ids'),
            promptId: $this->postInt('prompt_id'),
            provider: $this->post('provider', 'openai')
        );

        try {
            $result = $this->generator->generate($request, $jobId);

            $product = get_post($result['product_id']);

            $this->success([
                'job_id'     => $jobId,
                'product_id' => $result['product_id'],
                'duration'   => $result['duration'],
                'edit_link'  => get_edit_post_link($result['product_id'], 'raw'),
                'view_link'  => get_permalink($result['product_id']),
                'title'      => $product instanceof \WP_Post ? $product->post_title : '',
            ]);
        } catch (ValidationException $e) {
            $this->fail($e->getMessage(), 422, ['errors' => $e->getErrors()]);
        } catch (AIProductStudioException $e) {
            $this->fail($e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Erreur inattendue lors de la génération.', ['error' => $e->getMessage()]);
            $this->fail(__('Une erreur inattendue est survenue.', 'ai-product-studio'), 500);
        }
    }

    public function parseImport(): void
    {
        $this->guard();

        if (empty($_FILES['import_file']) || ! is_array($_FILES['import_file'])) {
            $this->fail(__('Veuillez choisir un fichier CSV ou Excel.', 'ai-product-studio'));
        }

        $file = $_FILES['import_file'];

        if (! empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            $this->fail(__('Échec de l\'upload du fichier.', 'ai-product-studio'));
        }

        $tmp  = (string) ($file['tmp_name'] ?? '');
        $name = sanitize_file_name((string) ($file['name'] ?? 'import.csv'));

        if ($tmp === '' || ! is_uploaded_file($tmp)) {
            $this->fail(__('Fichier d\'import introuvable.', 'ai-product-studio'));
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size > 5 * MB_IN_BYTES) {
            $this->fail(__('Le fichier dépasse 5 Mo.', 'ai-product-studio'));
        }

        try {
            $rows = $this->spreadsheet->parse($tmp, $name);
        } catch (ValidationException $e) {
            $this->fail($e->getMessage(), 422, ['errors' => $e->getErrors()]);
        }

        $this->success([
            'count' => count($rows),
            'rows'  => $rows,
        ]);
    }

    public function progress(): void
    {
        $this->guard();

        $jobId    = $this->post('job_id');
        $progress = get_transient(ProductGenerator::PROGRESS_PREFIX . $jobId);

        if (! is_array($progress)) {
            $progress = ['steps' => [], 'status' => 'pending', 'current' => '', 'message' => ''];
        }

        $this->success(['progress' => $progress]);
    }

    public function cancel(): void
    {
        $this->guard();

        $jobId = $this->post('job_id');
        if ($jobId !== '') {
            set_transient('aips_cancel_' . $jobId, 1, 15 * MINUTE_IN_SECONDS);
        }

        $this->success(['cancelled' => true]);
    }

    private function isCancelled(string $jobId): bool
    {
        return (bool) get_transient('aips_cancel_' . $jobId);
    }
}
