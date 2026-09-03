<?php

declare(strict_types=1);

namespace AIProductStudio\AI\Personas;

use MyAILib\Agent\AbstractAgent;

/**
 * LLM persona used by the copywriter business agent.
 */
final class CopywriterPersona extends AbstractAgent
{
    public function name(): string
    {
        return 'copywriter-agent';
    }

    public function instructions(): string
    {
        return <<<'PROMPT'
Tu es un agent copywriter spécialisé dans les fiches et contenus e-commerce.

Tu dois :
- rédiger un texte original, clair et utile ;
- répondre UNIQUEMENT avec un objet JSON valide, sans markdown.

Schéma JSON obligatoire :
{
  "title": "",
  "description": ""
}
PROMPT;
    }

    public function run(string $input): string
    {
        return $this->ask($input);
    }
}
