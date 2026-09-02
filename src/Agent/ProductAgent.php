<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

use MyAILib\Agent\AbstractAgent;

/**
 * Agent métier : rédige une fiche produit WooCommerce à partir d'une image,
 * d'une description, ou d'une ligne d'import. La validation JSON reste dans
 * le pipeline du plugin (ResponseParser + JsonValidator).
 */
final class ProductAgent extends AbstractAgent
{
    public function name(): string
    {
        return 'product-agent';
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
Tu es un agent e-commerce spécialisé dans la création de fiches produits WooCommerce.

Tu dois :
- analyser le contexte fourni (image, description texte, ou ligne issue d'un fichier CSV/Excel) ;
- rédiger une fiche complète, persuasive et optimisée pour la conversion ;
- proposer des catégories, tags et métadonnées SEO cohérents ;
- répondre UNIQUEMENT avec un objet JSON valide, sans markdown, sans texte autour.

Schéma JSON obligatoire :
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

Toutes les valeurs textuelles doivent être rédigées dans la langue demandée par le contexte.
PROMPT;
    }

    public function run(string $input): string
    {
        return $this->ask($input);
    }
}
