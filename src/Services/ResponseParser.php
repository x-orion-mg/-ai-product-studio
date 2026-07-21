<?php

declare(strict_types=1);

namespace AIProductStudio\Services;

use AIProductStudio\Exceptions\ValidationException;

/**
 * Turns a raw AI text response into a decoded, associative array.
 *
 * LLMs frequently wrap JSON in Markdown fences or add prose around it; this
 * parser is defensive and extracts the first valid JSON object it can find.
 */
final class ResponseParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $raw): array
    {
        $json = $this->extractJson($raw);

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            throw new ValidationException(
                __('La réponse de l\'IA n\'est pas un JSON valide.', 'ai-product-studio'),
                [json_last_error_msg()]
            );
        }

        return $decoded;
    }

    /**
     * Extract the JSON payload from a possibly noisy response.
     */
    private function extractJson(string $raw): string
    {
        $raw = trim($raw);

        // Strip Markdown code fences (```json ... ``` or ``` ... ```).
        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $raw, $matches) === 1) {
            return trim($matches[1]);
        }

        // Fall back to the substring between the first "{" and the last "}".
        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($raw, $start, $end - $start + 1);
        }

        return $raw;
    }
}
