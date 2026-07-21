<?php

declare(strict_types=1);

namespace AIProductStudio\Prompt;

/**
 * Compiles a prompt template by replacing its {{variables}} with runtime values.
 *
 * Supported variables include (but are not limited to):
 *   {{description_utilisateur}}, {{image}}, {{categorie}}, {{prix}},
 *   {{promotion}}, {{langue}}, {{produits_associes}}, {{format_json}}
 */
final class PromptBuilder
{
    /**
     * @param array<string, string> $variables
     */
    public function build(string $template, array $variables): string
    {
        $replacements = [];

        foreach ($variables as $key => $value) {
            $replacements['{{' . $key . '}}'] = $value;
        }

        $compiled = strtr($template, $replacements);

        // Remove any variables that were left unresolved so they never leak to the AI.
        return (string) preg_replace('/\{\{\s*[a-z0-9_]+\s*\}\}/i', '', $compiled);
    }

    /**
     * The canonical JSON contract we ask every provider to respect.
     */
    public function jsonSchemaInstruction(): string
    {
        $schema = <<<'JSON'
{
  "title": "",
  "slug": "",
  "short_description": "",
  "long_description": "",
  "seo": { "meta_title": "", "meta_description": "" },
  "image": { "alt": "", "caption": "", "description": "" },
  "categories": [],
  "tags": [],
  "attributes": {}
}
JSON;

        return sprintf(
            "%s\n\n%s\n%s",
            __('Réponds UNIQUEMENT avec un objet JSON valide respectant exactement ce schéma, sans texte additionnel ni bloc de code Markdown :', 'ai-product-studio'),
            $schema,
            __('Toutes les valeurs textuelles doivent être rédigées dans la langue demandée.', 'ai-product-studio')
        );
    }
}
