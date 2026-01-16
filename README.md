# Lemur Escalade - Thème WordPress

> Thème WordPress custom pour l'association d'escalade FSGT Le Mur
> Site existant : https://www.lemur-escalade.org/ (Drupal)

---

## Stack technique

| Élément | Choix |
|---------|-------|
| **PHP** | 8.2+, architecture modulaire, PSR-4 |
| **Build** | Vite + PostCSS |
| **JavaScript** | Vanilla JS + Alpine.js 3.x |
| **Custom Fields** | Carbon Fields (via Composer) |
| **Accessibilité** | WCAG AA |
| **Gutenberg** | Désactivé |

---

## Installation

### Prérequis

- PHP 8.2+
- Node.js 18+
- Composer 2.x
- WordPress 6.x

### Setup

```bash
# Cloner le repo dans wp-content/themes/
cd wp-content/themes/
git clone [repo-url] lemur
cd lemur

# Installer les dépendances PHP
composer install

# Installer les dépendances Node
npm install

# Lancer le dev server
npm run dev
```

### Scripts disponibles

| Commande | Description |
|----------|-------------|
| `npm run dev` | Serveur de développement avec HMR |
| `npm run build` | Build de production |
| `npm run preview` | Prévisualisation du build |

---

## Structure du projet

```
lemur/
├── docs/                    # Documentation
│   ├── ROADMAP.md          # Roadmap de développement
│   ├── ARCHITECTURE-CARBON-FIELDS.md
│   └── stories/            # User stories détaillées
│
├── modules/                 # Modules PHP (PSR-4)
│   ├── Core/               # Fonctionnalités de base
│   ├── CustomPostTypes/    # CPT (Événements, Membres, FAQ...)
│   ├── Taxonomies/         # Taxonomies custom
│   ├── Fields/             # Champs Carbon Fields
│   ├── Security/           # Sécurité (Headers, CSRF, XSS...)
│   ├── SEO/                # Schema.org, Meta tags
│   └── MemberArea/         # Espace membre (OAuth2 Galette)
│
├── includes/               # Helpers et fonctions
├── template-parts/         # Composants réutilisables
├── page-templates/         # Templates de pages
│
├── src/                    # Sources frontend
│   ├── css/               # Styles (PostCSS)
│   ├── js/                # JavaScript
│   └── fonts/             # Polices
│
├── dist/                   # Build output (gitignored)
│
├── functions.php           # Bootstrap du thème
├── style.css              # Métadonnées WordPress
├── composer.json          # Dépendances PHP
├── package.json           # Dépendances Node
└── vite.config.js         # Configuration Vite
```

---

## Phases de développement

| Phase | Focus | Stories |
|-------|-------|---------|
| **0** | MVP Technique | 1.1, 1.2, 1.3 |
| **1A** | Core Structure | 2.1, 2.2, 2.3, 2.5 |
| **1B** | CPT | 3.1, 3.2, 3.4, 3.5 |
| **1C** | Templates | 4.1, 4.2, 4.3, 4.4, 4.6, 4.9 |
| **1D** | Page Builder | 2.4 |
| **2** | Sécurité/SEO | 7.x, 8.x (core) |
| **3** | Design | 1.4, 6.x |
| **4** | Espace membre | 5.x, 8.x (member) |

Voir la [ROADMAP complète](docs/ROADMAP.md) pour plus de détails.

---

## Epics & Stories

**Total : 53 stories** | [Index détaillé](docs/stories/)

### Epic 1 - Infrastructure & Setup (6 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 1.1 | Setup Vite + PostCSS | High |
| 1.2 | Structure modulaire PHP | High |
| 1.3 | Intégration Composer | High |
| 1.4 | Design System CSS | Medium |
| 1.5 | Setup Storybook | Low |
| 1.6 | Setup Figma Code Connect | Low |

### Epic 2 - Core du thème (5 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 2.1 | Header sticky + sous-menus | High |
| 2.2 | Footer | Medium |
| 2.3 | Page d'options admin | High |
| 2.4 | Page Builder (blocs drag & drop) | High |
| 2.5 | Gestion images optimisée | Medium |

### Epic 3 - Custom Post Types (5 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 3.1 | CPT Événements | High |
| 3.2 | CPT Membres | High |
| 3.3 | CPT Collectifs | Medium |
| 3.4 | CPT FAQ | Medium |
| 3.5 | Taxonomies | Medium |

### Epic 4 - Templates (11 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 4.1 | Template Accueil | High |
| 4.2 | Template Le Club | Medium |
| 4.3 | Template Grimper | Medium |
| 4.4 | Template Sorties/Événements | High |
| 4.5 | Template Planning | Medium |
| 4.6 | Template Tarifs/Adhésion | Medium |
| 4.7 | Template Équipe | Medium |
| 4.8 | Template Collectifs | Low |
| 4.9 | Template FAQ | Medium |
| 4.10 | Template Galerie | Medium |
| 4.11 | Template Contact | Low |

### Epic 5 - Espace membre [OPTIONNEL] (10 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 5.0 | Configuration Galette OAuth2 | High |
| 5.1 | Authentification OAuth2 WordPress | High |
| 5.1b | Stratégie fallback & résilience | High |
| 5.1c | Rôles WordPress & Mapping Galette | High |
| 5.2 | Contenu réservé membres | Medium |
| 5.3 | Bibliothèque de documents | Medium |
| 5.4 | Annuaire des membres | Low |
| 5.5 | Todo list annuelle | Medium |
| 5.6 | Calendrier intégré | Medium |
| 5.7 | Dashboard espace membre | Low |

### Epic 6 - Animations & UX (3 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 6.1 | Système d'animations | Medium |
| 6.2 | Lightbox custom | Medium |
| 6.3 | Composants Alpine.js | Medium |

### Epic 7 - SEO & Performance (3 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 7.1 | Schema.org | Medium |
| 7.2 | Meta tags | Medium |
| 7.3 | Accessibilité WCAG AA | High |

### Epic 8 - Sécurité (10 stories)

| ID | Story | Priorité |
|----|-------|----------|
| 8.1 | Headers de sécurité HTTP | High |
| 8.2 | Protection CSRF & Nonces | High |
| 8.3 | Protection XSS | High |
| 8.4 | Protection SQL Injection | High |
| 8.5 | Sécurisation uploads | High |
| 8.6 | Sécurité sessions OAuth2 | High |
| 8.7 | Rate limiting & Brute force | Medium |
| 8.8 | Permissions & Capabilities | High |
| 8.9 | Audit & Logging | Low |
| 8.10 | Hardening WordPress | Medium |

---

## Conventions

### PHP

- Namespace : `Lemur\`
- Préfixe fonctions : `lemur_`
- PSR-4 autoloading
- WordPress Coding Standards

### CSS

- Méthodologie BEM
- Variables CSS (custom properties)
- Mobile-first

### Git

- Branches : `feature/`, `fix/`, `docs/`
- Commits conventionnels (feat, fix, docs, style, refactor)

---

## Checklists de review

### Code Review

- [ ] WordPress Coding Standards respectés
- [ ] Préfixe `lemur_` sur les fonctions
- [ ] PSR-4 pour les classes
- [ ] Pas de code dupliqué
- [ ] PHPDoc sur les fonctions

### Security Audit

- [ ] Entrées utilisateur sanitizées
- [ ] Sorties échappées
- [ ] Nonces sur les formulaires
- [ ] `$wpdb->prepare()` pour les requêtes SQL
- [ ] `current_user_can()` avant actions sensibles

### Accessibility Audit

- [ ] HTML sémantique
- [ ] ARIA labels appropriés
- [ ] Contrastes suffisants (WCAG AA)
- [ ] Navigation clavier
- [ ] Alt text sur les images

---

## Documentation

| Document | Description |
|----------|-------------|
| [docs/ROADMAP.md](docs/ROADMAP.md) | Planning détaillé par phase |
| [docs/ARCHITECTURE-CARBON-FIELDS.md](docs/ARCHITECTURE-CARBON-FIELDS.md) | Architecture des champs admin |
| [docs/stories/](docs/stories/) | Stories détaillées par epic |

---

## Licence

Thème développé pour l'association Le Mur Escalade (FSGT).

---

*Mis à jour le 2026-01-15*
# LeSite
