<?php

declare(strict_types=1);

namespace AIProductStudio\Image;

/**
 * Extracts lightweight metadata from an attachment to enrich the AI prompt
 * (dimensions, orientation, original filename). The heavy visual analysis is
 * performed by the multimodal model itself.
 */
final class ImageAnalyzer
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(int $attachmentId): array
    {
        $meta = wp_get_attachment_metadata($attachmentId);
        $file = get_attached_file($attachmentId);

        $width  = is_array($meta) ? (int) ($meta['width'] ?? 0) : 0;
        $height = is_array($meta) ? (int) ($meta['height'] ?? 0) : 0;

        $orientation = 'square';
        if ($width > 0 && $height > 0) {
            if ($width > $height) {
                $orientation = 'landscape';
            } elseif ($height > $width) {
                $orientation = 'portrait';
            }
        }

        return [
            'attachment_id' => $attachmentId,
            'filename'      => $file !== false ? basename($file) : '',
            'width'         => $width,
            'height'        => $height,
            'orientation'   => $orientation,
            'mime'          => (string) get_post_mime_type($attachmentId),
        ];
    }
}
