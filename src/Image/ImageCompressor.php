<?php

declare(strict_types=1);

namespace AIProductStudio\Image;

use AIProductStudio\Exceptions\WorkflowException;
use AIProductStudio\Services\Settings;

/**
 * Produces a compressed, base64-encoded copy of an attachment suitable for
 * sending to a multimodal AI (keeps requests small and fast).
 */
final class ImageCompressor {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @return array{mime: string, data: string}
	 *
	 * @throws WorkflowException
	 */
	public function toBase64( int $attachmentId ): array {
		$path = get_attached_file( $attachmentId );

		if ( $path === false || ! file_exists( $path ) ) {
			throw new WorkflowException(
				sprintf(
					/* translators: %d: attachment id. */
					__( 'Fichier image introuvable pour la pièce jointe #%d.', 'ai-product-studio' ),
					$attachmentId
				),
				'compress'
			);
		}

		$maxWidth = (int) $this->settings->get( 'image_max_width', 1600 );
		$quality  = (int) $this->settings->get( 'image_quality', 82 );

		$editor = wp_get_image_editor( $path );

		if ( is_wp_error( $editor ) ) {
			// Fall back to the original bytes if the editor is unavailable.
			$bytes = (string) file_get_contents( $path );

			return array(
				'mime' => (string) ( mime_content_type( $path ) ?: 'image/jpeg' ),
				'data' => base64_encode( $bytes ),
			);
		}

		$editor->resize( $maxWidth, $maxWidth, false );
		$editor->set_quality( $quality );

		$tmp   = wp_tempnam( 'aips-img' );
		$saved = $editor->save( $tmp, 'image/jpeg' );

		if ( is_wp_error( $saved ) || ! isset( $saved['path'] ) ) {
			throw new WorkflowException(
				__( 'Impossible de compresser l\'image.', 'ai-product-studio' ),
				'compress'
			);
		}

		$bytes = (string) file_get_contents( $saved['path'] );
		@unlink( $saved['path'] );

		return array(
			'mime' => 'image/jpeg',
			'data' => base64_encode( $bytes ),
		);
	}
}
