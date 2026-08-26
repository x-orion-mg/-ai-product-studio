# AI Product Studio

Plugin WordPress professionnel qui automatise la **création complète de produits WooCommerce à partir d'une image** grâce à l'IA.

L'IA n'est qu'une étape d'un pipeline découplé :

```
Upload image → Compression → Analyse → Construction du prompt → Appel IA
→ Validation JSON → Création produit WooCommerce → SEO → Finalisation
```

## Points clés

- Architecture **orientée objet**, **PSR-4** (`AIProductStudio\`), principes SOLID, aucune logique dans les vues.
- Providers IA interchangeables derrière une interface commune : **OpenAI, Gemini, Claude, OpenRouter, Ollama**.
- Gestion **multi-clés API** par fournisseur : rotation automatique, priorité, comptage d'erreurs, désactivation auto.
- **Prompts** paramétrables avec variables (`{{description_utilisateur}}`, `{{prix}}`, `{{langue}}`…).
- Contrat **JSON strict** renvoyé par l'IA (pas de parsing de texte libre).
- Génération **100 % AJAX** avec barre de progression temps réel et annulation.
- **Historique** des générations + **journalisation** technique avec rotation.
- Sécurité : nonces, capability checks, sanitization, escaping.

## Installation

1. Copier le dossier `ai-product-studio` dans `wp-content/plugins/`.
2. (Optionnel) `composer install` pour l'autoload optimisé — un autoloader PSR-4 de secours est inclus.
3. Activer le plugin, renseigner une clé API dans l'onglet **API**, puis ouvrir **Générer un produit**.

## Développement

```bash
composer install
composer lint      # PHPCS (WordPress Coding Standards)
```

Requiert PHP ≥ 8.0, WordPress ≥ 6.0, WooCommerce ≥ 6.0.

## Licence

GPL-2.0-or-later.
