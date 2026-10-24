=== AI Product Studio ===
Contributors: maheryrak
Tags: woocommerce, ai, product generator, openai, gemini, claude
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
WC requires at least: 6.0
WC tested up to: 9.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatise la création complète de produits WooCommerce à partir d'une image grâce à l'IA.

== Description ==

AI Product Studio transforme une simple image en une fiche produit WooCommerce complète :
titre, descriptions, SEO, texte alternatif d'image, catégories et tags — le tout généré par
l'IA et validé via un contrat JSON strict.

Fonctionnalités principales :

* Tableau de bord et sous-menus dédiés (Générer, Configuration, Prompts, API, Historique, Logs).
* Gestion de prompts avec variables ({{description_utilisateur}}, {{prix}}, {{langue}}, etc.).
* Gestion multi-clés API par fournisseur avec rotation automatique, priorité et suivi des erreurs.
* Architecture Provider extensible : OpenAI, Gemini, Claude, OpenRouter, Ollama.
* Pipeline de génération découplé (préparation image → analyse → prompt → IA → validation JSON →
  création WooCommerce → SEO → finalisation).
* Génération 100 % AJAX avec barre de progression temps réel et annulation.
* Historique complet et journalisation technique avec rotation automatique.
* Sécurité : nonces, vérification de capacités, sanitization, escaping.

== Architecture ==

Le plugin est entièrement orienté objet, respecte le PSR-4 (namespace `AIProductStudio\`),
et sépare strictement les responsabilités (aucune logique métier dans les vues). Les évolutions
futures (nouveaux providers, file d'attente, génération en lot, variantes, traduction, API REST)
s'ajoutent sous forme de modules sans modifier le cœur.

== Installation ==

1. Copiez le dossier `ai-product-studio` dans `wp-content/plugins/`.
2. (Optionnel) Lancez `composer install` pour l'autoload optimisé.
3. Activez le plugin depuis l'écran « Extensions ».
4. Renseignez au moins une clé API dans l'onglet « API ».
5. Rendez-vous dans « Générer un produit ».

== Changelog ==

= 1.0.0 =
* Version initiale.
