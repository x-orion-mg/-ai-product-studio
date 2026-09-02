<?php

declare(strict_types=1);

namespace AIProductStudio\Agent;

use MyAILib\Agent\AbstractAgent;

/**
 * Agent copywriting (articles de blog). Non branché à l'UI pour l'instant :
 * le même schéma agent + validations JSON sera réutilisé.
 */
final class CopywriterAgent extends AbstractAgent
{
    public function name(): string
    {
        return 'copywriter-agent';
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
Tu es un agent copywriter spécialisé dans les articles de blog e-commerce.

Tu dois :
- rédiger un article structuré, original et utile ;
- proposer un titre, un extrait, le corps HTML, des catégories et des tags ;
- répondre UNIQUEMENT avec un objet JSON valide, sans markdown.

Schéma JSON obligatoire :
{
  "title": "",
  "slug": "",
  "excerpt": "",
  "content": "",
  "seo": { "meta_title": "", "meta_description": "" },
  "categories": [],
  "tags": []
}
PROMPT;
    }

    public function run(string $input): string
    {
        return $this->ask($input);
    }
}
