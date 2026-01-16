# Lemur Escalade

Thème WordPress pour l'association d'escalade FSGT Le Mur.

## Installation

```bash
composer install
npm install
npm run dev
```

## Stack

- PHP 8.2+ (architecture modulaire PSR-4)
- Vite + PostCSS
- Alpine.js 3.x
- Carbon Fields

## Structure

```
modules/          → Modules PHP (Core, CPT, Fields, Security, SEO...)
includes/         → Helpers
template-parts/   → Composants
page-templates/   → Templates de pages
src/              → CSS, JS, fonts
```

## Scripts

```bash
npm run dev       # Dev server avec HMR
npm run build     # Build production
npm run preview   # Preview du build
```

## Conventions

- Namespace PHP : `Lemur\`
- Préfixe fonctions : `lemur_`
- CSS : BEM, mobile-first
- Commits : conventionnels (feat, fix, docs...)

