# Design System Global

Ce document fixe les standards UI/UX et sécurité pour tout le projet.

## 1) Typographie

Polices recommandées:
- Titres: `Space Grotesk`
- Texte UI: `Inter`
- Code/chiffres: `JetBrains Mono`

Le projet utilise déjà ces familles dans:
- `assets/css/global-theme.css`
- `assets/js/theme.js` (config Tailwind runtime)

Tu peux déposer les fichiers dans `assets/fonts/` si tu veux un hébergement local.

## 2) Couleurs (light + dark)

Tokens globaux (définis dans `assets/css/global-theme.css`):
- `--app-bg`, `--app-surface`, `--app-text`, `--app-muted`
- `--app-primary`, `--app-success`, `--app-warning`, `--app-danger`, `--app-info`
- `--app-border`, `--app-border-strong`, `--app-ring`

Règle d'état standard:
- `Actif` => vert
- `En attente` => orange/ambre
- `Urgent` => rouge/rose
- `Terminé` => neutre

## 3) Styles communs

Classes globales:
- `app-view`, `app-role-*`, `data-view` (appliqués automatiquement par `theme.js`)
- `app-nav-link` pour les entrées de menu
- `app-icon` pour les icônes de navigation
- `app-status-badge` (+ variantes `--active|--pending|--urgent|--done`)

## 4) Icônes

Les sidebars teacher/student utilisent maintenant un helper unique:
- `dashboard_icon_svg()` dans `app/View/helpers.php`

Cela uniformise l'iconographie sur les menus.

## 5) Animations

Standards:
- Animation courte: 140ms
- Animation standard: 220ms
- Courbe: `cubic-bezier(0.2, 0.8, 0.2, 1)`

Classes utilitaires:
- `app-motion-enter`
- `app-motion-soft-hover`

Accessibilité:
- `prefers-reduced-motion` est respecté dans `global-theme.css`.

## 6) Sécurité front + HTTP

Renforcements globaux dans `includes/bootstrap.php`:
- Session hardening: `use_strict_mode`, `use_only_cookies`, `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS
- Headers: `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `COOP`, `CORP`, `HSTS` (HTTPS)
- CSP renforcée: `object-src 'none'`, `base-uri 'self'`, `form-action 'self'`

## 7) Intégration globale

`assets/js/theme.js` charge automatiquement `assets/css/global-theme.css`.
Donc toutes les pages qui importent `theme.js` reçoivent automatiquement le design system.

## 8) Shell Admin partagé

Composants factorisés pour le dashboard admin:
- `app/Views/admin/_sidebar.php`
- `app/Views/admin/_topbar.php`
- `assets/css/global-theme.css`

Vues déjà migrées:
- `app/Views/admin/dashboard.php`
- `app/Views/admin/courses.php`
- `app/Views/admin/reports.php`
- `app/Views/admin/sales.php`
- `app/Views/admin/support.php`

Convention d'usage dans une vue admin:
1. Définir `adminNavActive`, `adminTopTitle`, `adminTopSubtitle`, `adminTopActions` (et badges si besoin).
2. Inclure `theme.js`, `sidebar_toggle.js`, `global-theme.css`.
3. Utiliser le shell:
   - `require __DIR__ . '/_sidebar.php';`
   - `require __DIR__ . '/_topbar.php';`
