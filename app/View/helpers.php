<?php

/**
 * ÉcoleNumérique â€” helpers.php  v2.0
 *
 * Fonctions utilitaires globales pour les vues PHP.
 * Toutes les fonctions sont protégées par function_exists().
 *
 * Catégories :
 *  - Sécurité      : view_csrf_token()
 *  - Rôles         : dashboard_roles_from_user(), dashboard_available_spaces(), â€¦
 *  - Navigation    : dashboard_menu_config()
 *  - Icônes        : dashboard_icon_svg()
 *  - Badges statut : app_status_badge()
 *  - URLs          : dashboard_dashboard_url_for_roles(), dashboard_dashboard_url_for_user()
 */

use App\Core\Csrf;
use App\Core\Session;

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   1. SÉCURITÉ â€” TOKEN CSRF
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('view_csrf_token')) {
    /**
     * Renvoie le jeton CSRF courant (crée la session si nécessaire).
     * Instance réutilisée via static pour éviter des appels répétés.
     */
    function view_csrf_token(): string
    {
        static $csrf = null;
        if (!$csrf instanceof Csrf) {
            $csrf = new Csrf(new Session());
        }
        return $csrf->token();
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   1bis. FORMATTAGE SIMPLE POUR LES VUES
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('view_format_money_xof')) {
    /**
     * Formatte un montant en FCFA.
     */
    function view_format_money_xof($amount, string $zeroLabel = 'Gratuit'): string
    {
        $value = (int) ($amount ?? 0);
        if ($value <= 0) {
            return $zeroLabel;
        }
        return number_format($value, 0, ',', ' ') . ' FCFA';
    }
}

if (!function_exists('view_format_date_label')) {
    /**
     * Formatte une date (ou renvoie un fallback).
     */
    function view_format_date_label($value, bool $withTime = false, string $fallback = 'Non défini'): string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return $fallback;
        }
        $ts = strtotime($raw);
        if ($ts === false) {
            return $fallback;
        }
        return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
    }
}

if (!function_exists('view_pluralize')) {
    /**
     * Pluralise un libellé simple.
     */
    function view_pluralize(int $count, string $singular, ?string $plural = null): string
    {
        return $count . ' ' . ($count === 1 ? $singular : ($plural ?? ($singular . 's')));
    }
}

if (!function_exists('format_announcement_body')) {
    /**
     * Formatte un texte d'annonce avec une mise en forme simple (gras, italique, listes).
     */
    function format_announcement_body(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $lines = preg_split('/\\r\\n|\\n|\\r/', $text) ?: [];
        $output = '';
        $inList = false;

        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $linkify = static function (string $value): string {
            return preg_replace_callback(
                '/((?:https?:\/\/|www\.)[^\s<]+)/i',
                static function (array $matches): string {
                    $rawUrl = (string) ($matches[1] ?? '');
                    if ($rawUrl === '') {
                        return '';
                    }

                    $href = preg_match('/^https?:\/\//i', $rawUrl) ? $rawUrl : ('https://' . $rawUrl);

                    return '<span class="notif-inline-link">'
                        . '<a class="notif-body-link" href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $rawUrl . '</a>'
                        . '<button type="button" class="notif-copy-link-btn" data-copy-url="' . $href . '">Copier</button>'
                        . '</span>';
                },
                $value
            ) ?? $value;
        };
        $inline = static function (string $value) use ($escape, $linkify): string {
            $value = $escape($value);
            $value = preg_replace('/\\*\\*(.+?)\\*\\*/', '<strong>$1</strong>', $value) ?? $value;
            $value = preg_replace('/\\*(.+?)\\*/', '<em>$1</em>', $value) ?? $value;
            return $linkify($value);
        };

        foreach ($lines as $line) {
            $trimmed = rtrim($line);
            if (preg_match('/^\\s*[-*]\\s+(.+)$/', $trimmed, $matches)) {
                if (!$inList) {
                    $output .= '<ul class=\"list-disc pl-5 space-y-1\">';
                    $inList = true;
                }
                $output .= '<li>' . $inline($matches[1]) . '</li>';
                continue;
            }

            if ($inList) {
                $output .= '</ul>';
                $inList = false;
            }

            if (trim($trimmed) === '') {
                $output .= '<div class=\"h-2\"></div>';
                continue;
            }

            $output .= '<p>' . $inline($trimmed) . '</p>';
        }

        if ($inList) {
            $output .= '</ul>';
        }

        return $output;
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   2. RÃ”LES UTILISATEUR
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('dashboard_roles_from_user')) {
    /**
     * Extrait et normalise la liste des rôles d'un utilisateur.
     *
     * @param  array<string,mixed>|null $user  Tableau utilisateur issu de la base de données.
     * @return array<int,string>               Rôles en minuscules, dédupliqués.
     */
    function dashboard_roles_from_user(?array $user): array
    {
        return \App\Core\DashboardContextResolver::rolesFromUser($user);
    }
}

if (!function_exists('dashboard_available_spaces')) {
    /**
     * Renvoie les espaces tableau de bord disponibles pour un utilisateur.
     * Seuls les espaces Â« teacher Â» et Â« student Â» sont concernés.
     *
     * @param  array<string,mixed>|null $user
     * @return array<int,string>
     */
    function dashboard_available_spaces(?array $user): array
    {
        return \App\Core\DashboardContextResolver::availableSpacesForUser($user);
    }
}

if (!function_exists('dashboard_resolve_space')) {
    /**
     * Résout l'espace actif pour un utilisateur.
     *
     * Priorité :
     *  1. L'espace explicitement demandé (s'il est disponible).
     *  2. L'espace Â« teacher Â» si disponible.
     *  3. Le premier espace disponible.
     *
     * @param  array<string,mixed>|null $user
     * @param  string|null              $requested  Espace demandé (ex. GET ?space=teacher).
     * @return string|null                          Espace résolu, ou null si aucun rôle.
     */
    function dashboard_resolve_space(?array $user, ?string $requested = null): ?string
    {
        return \App\Core\DashboardContextResolver::resolveSpaceForUser($user, $requested);
    }
}

if (!function_exists('dashboard_space_label')) {
    /**
     * Renvoie le libellé affiché pour un espace donné.
     *
     * @param  string $space  'teacher' | 'student'
     * @return string
     */
    function dashboard_space_label(string $space): string
    {
        return \App\Core\DashboardContextResolver::spaceLabel($space);
    }
}

if (!function_exists('dashboard_uses_shell_space')) {
    function dashboard_uses_shell_space(?string $space): bool
    {
        return \App\Core\DashboardContextResolver::usesDashboardShell($space);
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   3. ICÃ”NES SVG
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('dashboard_user_display_name')) {
    /**
     * Resolves a display name for dashboard shells.
     *
     * @param array<string,mixed>|null $user
     */
    function dashboard_user_display_name(?array $user, string $fallback = 'Utilisateur'): string
    {
        if (!is_array($user)) {
            return $fallback;
        }

        $fullName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email !== '') {
            return $email;
        }

        return $fallback;
    }
}

if (!function_exists('dashboard_user_initials')) {
    /**
     * Builds initials from a dashboard user.
     *
     * @param array<string,mixed>|null $user
     */
    function dashboard_user_initials(?array $user, string $fallback = 'US'): string
    {
        $label = dashboard_user_display_name($user, $fallback);
        $parts = preg_split('/\s+/', trim($label)) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $initials .= strtoupper(function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        if ($initials !== '') {
            return $initials;
        }

        $seed = $fallback !== '' ? $fallback : 'US';
        return strtoupper(function_exists('mb_substr') ? mb_substr($seed, 0, 2, 'UTF-8') : substr($seed, 0, 2));
    }
}

if (!function_exists('dashboard_user_avatar_url')) {
    /**
     * Resolves the public avatar URL for a dashboard user.
     *
     * @param array<string,mixed>|null $user
     */
    function dashboard_user_avatar_url(?array $user): string
    {
        if (!is_array($user)) {
            return '';
        }

        $avatar = trim((string) ($user['avatar_url'] ?? ''));
        if ($avatar === '') {
            return '';
        }

        if (
            preg_match('#^https?://#i', $avatar) === 1
            || str_starts_with($avatar, '//')
            || str_starts_with($avatar, 'data:')
        ) {
            return $avatar;
        }

        $relative = ltrim($avatar, '/');
        $basePath = defined('BASE_PATH') ? trim((string) BASE_PATH, '/') : '';
        if ($basePath !== '' && str_starts_with($relative, $basePath . '/')) {
            $relative = substr($relative, strlen($basePath) + 1);
        }

        $projectRoot = dirname(__DIR__, 2);
        $absolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($absolute)) {
            return '';
        }

        return site_url($relative);
    }
}

if (!function_exists('dashboard_icon_svg')) {
    /**
     * Renvoie une icône SVG inline à partir d'une clé.
     * Les SVG sont au format Lucide (viewBox 24Ã—24, stroke, fill="none").
     *
     * Usage dans une vue :
     *   <span class="app-icon"><?= dashboard_icon_svg('book-open') ?></span>
     *
     * @param  string $icon  Clé de l'icône (voir liste statique).
     * @param  string $attrs Attributs SVG additionnels, injectés dans la balise <svg>.
     * @return string        SVG HTML ou chaîne vide si clé inconnue.
     */
    function dashboard_icon_svg(string $icon, string $attrs = ''): string
    {
        static $icons = [
            /* â€” Navigation â€” */
            'layout'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>',
            'layout-grid'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="5" rx="2"/><rect x="13" y="10" width="8" height="11" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/></svg>',
            'menu'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></svg>',
            'panel-left-close' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="m15 9-3 3 3 3"/></svg>',
            'home'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h14v-9.5"/></svg>',
            'globe'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 0 20"/><path d="M12 2a15 15 0 0 0 0 20"/></svg>',
            'log-out'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/></svg>',
            'repeat'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
            'chevron-left'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>',
            'moon'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.9 13.2A8.5 8.5 0 1 1 10.8 3a7 7 0 0 0 10.1 10.2z"/></svg>',
            'sun'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2.5M12 19.5V22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M2 12h2.5M19.5 12H22M4.9 19.1l1.8-1.8M17.3 6.7l1.8-1.8"/></svg>',
            'theme-moon'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15.2 3.6a7.9 7.9 0 1 0 5.2 13.4A8.8 8.8 0 1 1 15.2 3.6Z"/><path d="M16.7 5.2v2.1"/><path d="M15.65 6.25h2.1"/><path d="m19.55 8.15.55.55"/><path d="m20.1 8.15-.55.55"/></svg>',
            'theme-sun'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4.15"/><path d="M12 2.75v2.1"/><path d="M12 19.15v2.1"/><path d="m5.46 5.46 1.48 1.48"/><path d="m17.06 17.06 1.48 1.48"/><path d="M2.75 12h2.1"/><path d="M19.15 12h2.1"/><path d="m5.46 18.54 1.48-1.48"/><path d="m17.06 6.94 1.48-1.48"/></svg>',
            'gift'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><rect x="4" y="12" width="16" height="9" rx="2"/><path d="M12 8v13"/><path d="M12 8H8.5a2.5 2.5 0 1 1 0-5C10.5 3 12 5 12 8z"/><path d="M12 8H15.5a2.5 2.5 0 1 0 0-5C13.5 3 12 5 12 8z"/></svg>',
            'chalkboard-user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M7 20h10"/><circle cx="12" cy="10" r="2"/><path d="M9 15a3 3 0 0 1 6 0"/></svg>',

            /* â€” Cours / Contenu â€” */
            'book-open'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 5.5a2.5 2.5 0 0 1 2.5-2.5h5.5A3.5 3.5 0 0 1 14 6.5V21a3.5 3.5 0 0 0-3.5-3.5H5a2.5 2.5 0 0 0-2.5 2.5V5.5z"/><path d="M21.5 5.5A2.5 2.5 0 0 0 19 3h-5.5A3.5 3.5 0 0 0 10 6.5V21a3.5 3.5 0 0 1 3.5-3.5H19a2.5 2.5 0 0 1 2.5 2.5V5.5z"/></svg>',
            'graduation-cap'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 11 9-5 9 5-9 5-9-5z"/><path d="M7 13.5V17c0 1.9 2.2 3.5 5 3.5s5-1.6 5-3.5v-3.5"/><path d="M21 11v5"/></svg>',
            'user-graduate' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/><path d="m4 7 8-4 8 4-8 4-8-4z"/><path d="M20 7v3"/></svg>',
            'video'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="12" height="12" rx="2"/><path d="m15 10 6-4v12l-6-4z"/></svg>',
            'plus-square'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M12 8v8M8 12h8"/></svg>',
            'play-circle'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>',
            'clipboard-list'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4.5h6a1.5 1.5 0 0 0-1.5-1.5h-3A1.5 1.5 0 0 0 9 4.5z"/><path d="M9 10h6M9 14h6M9 18h4"/><path d="m7.8 10.2.6.6 1.2-1.2"/><path d="m7.8 14.2.6.6 1.2-1.2"/></svg>',
            'file-text'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
            'upload'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>',
            'folder'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
            'layers'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 3 9 4.5-9 4.5-9-4.5L12 3z"/><path d="m3 12 9 4.5 9-4.5"/><path d="m3 16.5 9 4.5 9-4.5"/></svg>',

            /* â€” Commerce â€” */
            'ticket'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 8a2 2 0 0 0 0 8v2h18v-2a2 2 0 0 0 0-8V6H3v2z"/><path d="M9 6v12M15 6v12"/></svg>',
            'wallet'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H20v14H5.5A2.5 2.5 0 0 1 3 16.5v-9z"/><path d="M20 10h-4a2 2 0 0 0 0 4h4"/></svg>',
            'receipt'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3h12v18l-2-1.5L14 21l-2-1.5L10 21l-2-1.5L6 21V3z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>',
            'credit-card'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',

            /* â€” Marketing â€” */
            'megaphone'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11v2a2 2 0 0 0 2 2h2l5 4V5L7 9H5a2 2 0 0 0-2 2z"/><path d="M16 8.5a4.5 4.5 0 0 1 0 7"/><path d="M18.5 6a8 8 0 0 1 0 12"/></svg>',
            'trending-up'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',

            /* â€” Planification â€” */
            'calendar'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>',
            'clock'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',

            /* â€” Personnes â€” */
            'users'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'user'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
            'user-plus'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>',
            'building'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h.01M12 7h.01M16 7h.01M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01"/><path d="M10 21v-4h4v4"/></svg>',

            /* â€” Notifications / Alertes â€” */
            'bell'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6.26 15.33A1 1 0 0 0 7 17h10a1 1 0 0 0 .74-1.67C16.63 14.09 16 12.45 16 10.75V9a4 4 0 1 0-8 0v1.75c0 1.7-.63 3.34-1.74 4.58"/><path d="M10.5 20a1.8 1.8 0 0 0 3 0"/><path d="M12 3.25v1"/></svg>',
            'alert-triangle'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.8 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>',
            'alert-circle'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
            'check-circle'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
            'help-circle'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.6 1.5c-.7.9-1.7 1.4-2.2 2.3-.2.3-.3.6-.3 1.2"/><path d="M12 17h.01"/></svg>',

            /* â€” Analytique â€” */
            'bar-chart'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6" rx="1"/><rect x="12" y="9" width="3" height="9" rx="1"/><rect x="17" y="6" width="3" height="12" rx="1"/></svg>',
            'pie-chart'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>',

            /* â€” Réalisations / Gamification â€” */
            'award'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6"/><path d="m8.2 14.8-2.2 6.2L12 18l6 3-2.2-6.2"/></svg>',
            'coins'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="9" cy="6.5" rx="5.5" ry="3.25"/><path d="M3.5 6.5v4.5c0 1.8 2.5 3.25 5.5 3.25s5.5-1.45 5.5-3.25V6.5"/><ellipse cx="15" cy="13" rx="5.5" ry="3.25"/><path d="M9.5 13v4.5c0 1.8 2.5 3.25 5.5 3.25s5.5-1.45 5.5-3.25V13"/></svg>',
            'star'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'heart'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 5.7a5 5 0 0 0-7.1 0L12 7.4l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 21.6l8.8-8.8a5 5 0 0 0 0-7.1z"/></svg>',

            /* â€” Support â€” */
            'comment-dots'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13.2A8 8 0 0 1 12 20H7l-4 3V7a3 3 0 0 1 3-3h6a8 8 0 0 1 8 8.2z"/><circle cx="9" cy="12" r="1.1"/><circle cx="12" cy="12" r="1.1"/><circle cx="15" cy="12" r="1.1"/></svg>',
            'headset'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><path d="M4 13a2 2 0 0 1 2-2h1v6H6a2 2 0 0 1-2-2v-2z"/><path d="M20 13a2 2 0 0 0-2-2h-1v6h1a2 2 0 0 0 2-2v-2z"/><path d="M17 17v1a3 3 0 0 1-3 3h-2"/><path d="M12 21h-1.5"/></svg>',
            'lifebuoy'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-1a8 8 0 0 1 16 0v1"/><path d="M4 13a2 2 0 0 1 2-2h1v6H6a2 2 0 0 1-2-2v-2z"/><path d="M20 13a2 2 0 0 0-2-2h-1v6h1a2 2 0 0 0 2-2v-2z"/><path d="M17 17v1a3 3 0 0 1-3 3h-2"/><path d="M12 21h-1.5"/></svg>',
            'message-circle'=> '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 13.2A8 8 0 0 1 12 20H7l-4 3V7a3 3 0 0 1 3-3h6a8 8 0 0 1 8 8.2z"/><circle cx="9" cy="12" r="1.1"/><circle cx="12" cy="12" r="1.1"/><circle cx="15" cy="12" r="1.1"/></svg>',
            'mail'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',

            /* â€” Sécurité / Paramètres â€” */
            'shield'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
            'settings'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
            'lock'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
            'key'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',

            /* â€” Divers â€” */
            'search'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
            'filter'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
            'download'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
            'printer'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V4h12v5"/><path d="M6 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-1"/><rect x="6" y="13" width="12" height="7" rx="2"/><path d="M8 18h8"/></svg>',
            'trash-2'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M10 11v6M14 11v6"/><path d="M6 6l1 15a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-15"/></svg>',
            'save'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h12l4 4v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4z"/><path d="M8 4v6h8V4"/><path d="M9 18h6"/></svg>',
            'external-link' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>',
            'more-vertical' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>',
            'x'             => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
            'check'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>',
            'chevron-right' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>',
            'chevron-down'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>',
        ];

        $resolvedIcon = isset($icons[$icon]) ? $icon : 'help-circle';
        $svg = $icons[$resolvedIcon];
        $attrs = trim($attrs);
        $iconAttrs = preg_match('/\bdata-app-icon\s*=/', $attrs) === 1
            ? ''
            : 'data-app-icon="' . htmlspecialchars($resolvedIcon, ENT_QUOTES, 'UTF-8') . '"';
        if ($attrs !== '') {
            $iconAttrs = trim($iconAttrs . ' ' . $attrs);
        }
        if ($iconAttrs !== '') {
            $svg = preg_replace('/^<svg\\b/', '<svg ' . $iconAttrs, $svg, 1) ?? $svg;
        }

        return $svg;
    }
}

if (!function_exists('dashboard_icon_fa_class')) {
    /**
     * Renvoie la classe Font Awesome correspondant aux clés d'icônes du tableau de bord.
     */
    function dashboard_icon_fa_class(string $icon): string
    {
        static $icons = [
            'layout'           => 'fas fa-th-large',
            'layout-grid'      => 'fas fa-th-large',
            'menu'             => 'fas fa-bars',
            'panel-left-close' => 'fas fa-bars',
            'home'             => 'fas fa-home',
            'log-out'          => 'fas fa-sign-out-alt',
            'repeat'           => 'fas fa-redo-alt',
            'chevron-left'     => 'fas fa-chevron-left',
            'chevron-right'    => 'fas fa-chevron-right',
            'chevron-down'     => 'fas fa-chevron-down',
            'moon'             => 'fas fa-moon',
            'sun'              => 'fas fa-sun',
            'gift'             => 'fas fa-gift',
            'chalkboard-user'  => 'fas fa-chalkboard-teacher',
            'book-open'        => 'fas fa-book-open',
            'graduation-cap'   => 'fas fa-graduation-cap',
            'user-graduate'    => 'fas fa-user-graduate',
            'video'            => 'fas fa-video',
            'plus-square'      => 'fas fa-plus-square',
            'play-circle'      => 'fas fa-play-circle',
            'clipboard-list'   => 'fas fa-clipboard-list',
            'file-text'        => 'fas fa-file-alt',
            'upload'           => 'fas fa-upload',
            'folder'           => 'fas fa-folder',
            'layers'           => 'fas fa-layer-group',
            'ticket'           => 'fas fa-ticket',
            'wallet'           => 'fas fa-wallet',
            'receipt'          => 'fas fa-receipt',
            'credit-card'      => 'fas fa-credit-card',
            'megaphone'        => 'fas fa-bullhorn',
            'trending-up'      => 'fas fa-chart-line',
            'calendar'         => 'fas fa-calendar-alt',
            'clock'            => 'fas fa-clock',
            'users'            => 'fas fa-users',
            'user'             => 'fas fa-user',
            'user-plus'        => 'fas fa-user-plus',
            'building'         => 'fas fa-building',
            'bell'             => 'fas fa-bell',
            'alert-triangle'   => 'fas fa-exclamation-triangle',
            'alert-circle'     => 'fas fa-exclamation-circle',
            'check-circle'     => 'fas fa-check-circle',
            'help-circle'      => 'fas fa-question-circle',
            'bar-chart'        => 'fas fa-chart-bar',
            'pie-chart'        => 'fas fa-chart-pie',
            'award'            => 'fas fa-award',
            'star'             => 'fas fa-star',
            'heart'            => 'fas fa-heart',
            'comment-dots'     => 'fas fa-comments',
            'lifebuoy'         => 'fas fa-headset',
            'message-circle'   => 'fas fa-comments',
            'headset'          => 'fas fa-headset',
            'mail'             => 'fas fa-envelope',
            'shield'           => 'fas fa-shield-alt',
            'settings'         => 'fas fa-cog',
            'lock'             => 'fas fa-lock',
            'key'              => 'fas fa-key',
            'search'           => 'fas fa-search',
            'share'            => 'fas fa-share-alt',
            'filter'           => 'fas fa-filter',
            'download'         => 'fas fa-download',
            'printer'          => 'fas fa-print',
            'trash-2'          => 'fas fa-trash',
            'save'             => 'fas fa-save',
            'external-link'    => 'fas fa-external-link-alt',
            'arrow-left'       => 'fas fa-arrow-left',
            'more-vertical'    => 'fas fa-ellipsis-v',
            'x'                => 'fas fa-times',
            'check'            => 'fas fa-check',
        ];

        return $icons[$icon] ?? 'fas fa-circle';
    }
}

if (!function_exists('dashboard_icon_for_label')) {
    /**
     * Resolve a semantic dashboard icon from a human-readable label.
     */
    function dashboard_icon_for_label(string $label, string $fallback = 'help-circle'): string
    {
        $value = trim($label);
        if ($value === '') {
            return $fallback;
        }

        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($ascii) && $ascii !== '') {
                $value = strtolower($ascii);
            }
        }

        $rules = [
            'bell' => ['notification', 'boite', 'alerte', 'annonce'],
            'filter' => ['filtre', 'affiner', 'tri', 'recherche'],
            'book-open' => ['cours', 'formation', 'apprentissage', 'lecon', 'module', 'programme', 'contenu', 'bibliotheque'],
            'clipboard-list' => ['examen', 'evaluation', 'question', 'copie', 'tentative', 'quiz'],
            'award' => ['certificat', 'reussite', 'badge', 'avis', 'note'],
            'wallet' => ['revenu', 'retrait', 'gain', 'commission', 'finance', 'paiement', 'achat', 'commande', 'facture', 'portefeuille', 'transaction'],
            'calendar' => ['rendez-vous', 'creneau', 'session', 'date', 'calendrier', 'agenda', 'reservation'],
            'building' => ['entreprise', 'societe', 'organisation'],
            'users' => ['utilisateur', 'apprenant', 'collaborateur', 'membre', 'enseignant', 'filleul', 'parrain', 'equipe'],
            'user-plus' => ['invitation', 'inviter', 'candidature', 'candidater', 'inscription'],
            'headset' => ['support', 'assistance', 'ticket', 'conversation', 'message'],
            'shield' => ['securite', 'regle', 'controle', 'validation', 'moderation', 'permission'],
            'settings' => ['parametre', 'configuration', 'reglage', 'preference'],
            'upload' => ['fichier', 'ressource', 'document', 'drive', 'depot', 'avatar', 'signature', 'import'],
            'gift' => ['promotion', 'coupon', 'offre', 'parrainage'],
            'trending-up' => ['statistique', 'performance', 'croissance', 'activite', 'rapport', 'analyse'],
            'home' => ['tableau de bord', 'dashboard', 'accueil'],
            'help-circle' => ['aide', 'faq', 'question'],
            'user' => ['profil', 'compte'],
        ];

        foreach ($rules as $icon => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) {
                    return $icon;
                }
            }
        }

        return $fallback;
    }
}

if (!function_exists('dashboard_title_icon')) {
    /**
     * Render a compact icon well for headings.
     */
    function dashboard_title_icon(string $icon, string $class = 'app-title-icon app-title-icon--sm'): string
    {
        $icon = trim($icon);
        if ($icon === '') {
            return '';
        }

        $class = trim($class);
        if ($class === '') {
            $class = 'app-title-icon app-title-icon--sm';
        }

        return '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true">'
            . dashboard_icon_svg($icon)
            . '</span>';
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   4. BADGE DE STATUT HTML
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('app_status_badge')) {
    /**
     * Génère un badge de statut HTML prêt à l'emploi.
     *
     * @param  string $status   'active' | 'pending' | 'urgent' | 'done'
     * @param  string $label    Texte affiché dans le badge.
     * @return string           Élément <span> HTML échappé.
     */
    function app_status_badge(string $status, string $label): string
    {
        $allowed = ['active', 'pending', 'urgent', 'done'];
        $variant = in_array($status, $allowed, true) ? $status : 'done';
        $safe    = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<span class="app-status-badge app-status-badge--%s">%s</span>',
            $variant,
            $safe
        );
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   5. RÉSOLUTION D'URL TABLEAU DE BORD
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */


if (!function_exists('app_status_pill_class')) {
    /**
     * Resolve a shared status pill class from a semantic tone.
     */
    function app_status_pill_class(string $tone): string
    {
        $tone = strtolower(trim($tone));
        $variant = match ($tone) {
            'warning' => 'pending',
            'danger', 'error' => 'urgent',
            'success' => 'active',
            default => $tone,
        };

        $allowed = ['teacher', 'info', 'active', 'pending', 'urgent', 'done', 'sky'];
        if (!in_array($variant, $allowed, true)) {
            $variant = 'done';
        }

        return 'app-status-pill app-status-pill--' . $variant;
    }
}
if (!function_exists('dashboard_dashboard_url_for_roles')) {
    /**
     * Renvoie l'URL du tableau de bord approprié selon les rôles.
     * Priorité : admin > admin_secondary > teacher > company_admin > student.
     *
     * @param  array<int,string> $roles
     * @return string
     */
    function dashboard_dashboard_url_for_roles(array $roles, ?string $preferredSpace = null): string
    {
        return \App\Core\DashboardContextResolver::dashboardUrlForRoles($roles, $preferredSpace);
    }
}

if (!function_exists('dashboard_dashboard_url_for_user')) {
    /**
     * Renvoie l'URL du tableau de bord pour un utilisateur.
     *
     * @param  array<string,mixed>|null $user
     * @return string
     */
    function dashboard_dashboard_url_for_user(?array $user, ?string $preferredSpace = null): string
    {
        return \App\Core\DashboardContextResolver::dashboardUrlForUser($user, $preferredSpace);
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   6. OPTIONS DE CHANGEMENT DE CONTEXTE
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('dashboard_context_switch_options')) {
    /**
     * Génère les options pour le sélecteur de contexte enseignant/étudiant.
     * Renvoie un tableau vide si l'utilisateur n'a qu'un seul espace.
     *
     * @param  array<string,mixed>|null $user
     * @param  string                   $path         Chemin de la page courante.
     * @param  array<string,mixed>      $query        Paramètres GET actuels.
     * @param  string|null              $activeSpace  Espace actif courant.
     * @return array<int,array<string,mixed>>
     */
    function dashboard_context_switch_options(
        ?array $user,
        string $path,
        array $query = [],
        ?string $activeSpace = null
    ): array {
        $spaces = dashboard_available_spaces($user);
        if (count($spaces) < 2) {
            return [];
        }

        $active    = dashboard_resolve_space($user, $activeSpace);
        $baseQuery = $query;
        unset(
            $baseQuery['space'],
            $baseQuery['_csrf'],
            $baseQuery['action'],
            $baseQuery['notif_id']
        );

        $options = [];
        foreach ($spaces as $space) {
            $params      = $baseQuery;
            $params['space'] = $space;
            $queryString = http_build_query($params);
            $url         = site_url($path) . ($queryString !== '' ? '?' . $queryString : '');
            $options[]   = [
                'space'  => $space,
                'label'  => dashboard_space_label($space),
                'url'    => $url,
                'active' => $space === $active,
            ];
        }

        return $options;
    }
}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
   7. CONFIGURATION DES MENUS DE NAVIGATION
   â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */

if (!function_exists('dashboard_menu_config')) {
    /**
     * Renvoie la configuration du menu de navigation pour un espace donné.
     *
     * Structure de chaque entrée :
     *   - key      : identifiant unique de l'élément
     *   - label    : texte affiché
     *   - url      : lien href
     *   - icon     : clé dashboard_icon_svg()
     *   - variant  : (optionnel) 'primary' pour les CTA principaux
     *   - badge    : (optionnel) clé pour un compteur dynamique
     *
     * @param  string $space  'teacher' | 'student'
     * @return array<int,array<string,mixed>>
     */
    function dashboard_menu_config(string $space): array
    {
        if ($space === 'teacher') {
            return [
                [
                    'section' => 'Actions',
                    'items'   => [
                        [
                            'key'     => 'course_create',
                            'label'   => 'Nouveau cours',
                            'url'     => site_url('teacher/course_create'),
                            'variant' => 'primary',
                            'icon'    => 'plus-square',
                        ],
                    ],
                ],
                [
                    'section' => 'Tableau de bord',
                    'items'   => [
                        [
                            'key'   => 'dashboard',
                            'label' => 'Tableau de bord',
                            'url'   => site_url('teacher/dashboard'),
                            'icon'  => 'layout',
                        ],
                    ],
                ],
                [
                    'section' => 'Contenu',
                    'items'   => [
                        [
                            'key'   => 'courses',
                            'label' => 'Mes cours',
                            'url'   => site_url('teacher/courses'),
                            'icon'  => 'book-open',
                        ],
                        [
                            'key'   => 'reviews',
                            'label' => 'Avis & Notes',
                            'url'   => site_url('teacher/reviews'),
                            'icon'  => 'star',
                        ],
                        [
                            'key'   => 'exams_manage',
                            'label' => 'Examens',
                            'url'   => site_url('teacher/exams_manage'),
                            'badge' => 'pending_exams',
                            'icon'  => 'clipboard-list',
                        ],
                        [
                            'key'   => 'meetings',
                            'label' => 'Mes rendez-vous',
                            'url'   => site_url('teacher/meetings?status=pending'),
                            'badge' => 'pending_meetings',
                            'icon'  => 'calendar',
                        ],
                    ],
                ],
                [
                    'section' => 'Monétisation',
                    'items'   => [
                        [
                            'key'   => 'withdrawals',
                            'label' => 'Revenus et retraits',
                            'url'   => site_url('teacher/withdrawals'),
                            'badge' => 'pending_withdrawals',
                            'icon'  => 'wallet',
                        ],
                        [
                            'key'   => 'promotions',
                            'label' => 'Promotions et coupons',
                            'url'   => site_url('teacher/promotions'),
                            'icon'  => 'ticket',
                        ],
                        [
                            'key'   => 'referrals',
                            'label' => 'Parrainage',
                            'url'   => site_url('teacher/affiliation'),
                            'icon'  => 'users',
                        ],
                    ],
                ],
                [
                    'section' => 'Communication',
                    'items'   => [
                        [
                            'key'   => 'notifications',
                            'label' => 'Notifications',
                            'url'   => site_url('notifications?space=teacher'),
                            'badge' => 'notifications',
                            'icon'  => 'bell',
                        ],
                        [
                            'key'   => 'support',
                            'label' => 'Assistance',
                            'url'   => site_url('support?space=teacher'),
                            'badge' => 'support',
                            'icon'  => 'headset',
                        ],
                    ],
                ],
                [
                    'section' => 'Compte',
                    'items'   => [
                        [
                            'key'   => 'profile',
                            'label' => 'Mon profil',
                            'url'   => site_url('profile?space=teacher'),
                            'icon'  => 'user',
                        ],
                        [
                            'key'   => 'student_space',
                            'label' => 'Espace étudiant',
                            'url'   => site_url('student/dashboard'),
                            'icon'  => 'graduation-cap',
                        ],
                        [
                            'key'   => 'home',
                            'label' => 'Retour au site',
                            'url'   => site_url(''),
                            'icon'  => 'home',
                        ],
                        [
                            'key'   => 'logout',
                            'label' => 'Déconnexion',
                            'url'   => site_url('logout'),
                            'icon'  => 'log-out',
                        ],
                    ],
                ],
            ];
        }

        /* â€” Menu étudiant â€” */
        return [
            [
                'section' => 'Tableau de bord',
                'items'   => [
                    [
                        'key'   => 'dashboard',
                        'label' => 'Tableau de bord',
                        'url'   => site_url('student/dashboard'),
                        'icon'  => 'layout',
                    ],
                ],
            ],
            [
                'section' => 'Apprentissage',
                'items'   => [
                    [
                        'key'   => 'my_courses',
                        'label' => 'Mes cours',
                        'url'   => site_url('student/my_courses'),
                        'badge' => 'courses',
                        'icon'  => 'book-open',
                    ],
                    [
                        'key'   => 'certificates',
                        'label' => 'Mes certificats',
                        'url'   => site_url('student/certificates'),
                        'icon'  => 'award',
                    ],
                    [
                        'key'   => 'meetings',
                        'label' => 'Mes rendez-vous',
                        'url'   => site_url('student/meetings'),
                        'icon'  => 'calendar',
                    ],
                ],
            ],
            [
                'section' => 'Bibliothèque',
                'items'   => [
                    [
                        'key'   => 'favorites',
                        'label' => 'Mes favoris',
                        'url'   => site_url('student/favorites'),
                        'icon'  => 'heart',
                    ],
                    [
                        'key'   => 'orders',
                        'label' => 'Mes achats',
                        'url'   => site_url('student/orders'),
                        'icon'  => 'receipt',
                    ],
                ],
            ],
            [
                'section' => 'Communication',
                'items'   => [
                    [
                        'key'   => 'notifications',
                        'label' => 'Notifications',
                        'url'   => site_url('notifications?space=student'),
                        'badge' => 'notifications',
                        'icon'  => 'bell',
                    ],
                    [
                        'key'   => 'referrals',
                        'label' => 'Parrainage',
                        'url'   => site_url('student/referrals'),
                        'icon'  => 'users',
                    ],
                ],
            ],
            [
                'section' => 'Compte',
                'items'   => [
                    [
                        'key'   => 'profile',
                        'label' => 'Mon profil',
                        'url'   => site_url('profile?space=student'),
                        'icon'  => 'user',
                    ],
                    [
                        'key'   => 'support',
                        'label' => 'Assistance',
                        'url'   => site_url('support?space=student'),
                        'badge' => 'support',
                        'icon'  => 'headset',
                    ],
                    [
                        'key'   => 'home',
                        'label' => 'Retour au site',
                        'url'   => site_url(''),
                        'icon'  => 'home',
                    ],
                    [
                        'key'   => 'logout',
                        'label' => 'Déconnexion',
                        'url'   => site_url('logout'),
                        'icon'  => 'log-out',
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('dashboard_menu_item_meta')) {
    /**
     * Resolve section and item metadata for an active dashboard entry.
     *
     * @return array{section:string,item:array<string,mixed>}
     */
    function dashboard_menu_item_meta(string $space, string $key): array
    {
        $space = strtolower(trim($space));
        $key = trim($key);

        $aliases = [
            'teacher' => [
                'course_actions' => 'courses',
                'course_content' => 'courses',
                'course_edit' => 'courses',
                'course_preview' => 'courses',
                'course_updates' => 'courses',
                'exams_create' => 'courses',
                'exams_manage' => 'exams_manage',
                'exam_preview' => 'exams_manage',
                'messages' => 'support',
                'affiliation' => 'referrals',
                'coupons' => 'promotions',
            ],
            'student' => [
                'exam' => 'my_courses',
            ],
        ];

        if (isset($aliases[$space][$key])) {
            $key = $aliases[$space][$key];
        }

        $sections = dashboard_menu_config($space === 'teacher' ? 'teacher' : 'student');
        foreach ($sections as $section) {
            $sectionLabel = (string) ($section['section'] ?? '');
            foreach ((array) ($section['items'] ?? []) as $item) {
                if ((string) ($item['key'] ?? '') !== $key) {
                    continue;
                }
                return [
                    'section' => $sectionLabel,
                    'item' => is_array($item) ? $item : [],
                ];
            }
        }

        return ['section' => '', 'item' => []];
    }
}

if (!function_exists('admin_sidebar_badge_counts')) {
    /**
     * @param  array<string,mixed>|null $user
     * @return array{notifications:int,pending_withdrawals:int,pending_payments:int,failed_payments:int,pending_teacher_applications:int,pending_courses:int,support_waiting:int}
     */
    function admin_sidebar_badge_counts(?array $user): array
    {
        static $cache = [];

        $default = [
            'notifications' => 0,
            'pending_withdrawals' => 0,
            'pending_payments' => 0,
            'failed_payments' => 0,
            'pending_teacher_applications' => 0,
            'pending_courses' => 0,
            'support_waiting' => 0,
        ];

        if (!is_array($user)) {
            return $default;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return $default;
        }

        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        try {
            $access = new \App\Services\Admin\AdminAccessService();
            if (!$access->hasAdminAccess($user)) {
                $cache[$userId] = $default;
                return $default;
            }

            $model = new \App\Models\AdminDashboardModel(\App\Core\PdoRegistry::get());
            $cache[$userId] = [
                'notifications' => max(0, $model->countUnreadNotifications($userId)),
                'pending_withdrawals' => max(0, $model->countRequestedWithdrawals()),
                'pending_payments' => max(0, $model->countPendingPayments()),
                'failed_payments' => max(0, $model->countFailedPayments()),
                'pending_teacher_applications' => max(0, $model->countPendingTeacherApplications()),
                'pending_courses' => max(0, $model->countPendingCourses()),
                'support_waiting' => max(0, $model->countWaitingSupportTickets()),
            ];
        } catch (\Throwable $e) {
            $cache[$userId] = $default;
        }

        return $cache[$userId];
    }
}

if (!function_exists('teacher_sidebar_badge_counts')) {
    /**
     * @param  array<string,mixed>|null $user
     * @return array{notifications:int,support:int,pending_meetings:int,pending_withdrawals:int,pending_exams:int}
     */
    function teacher_sidebar_badge_counts(?array $user): array
    {
        static $cache = [];

        $default = [
            'notifications' => 0,
            'support' => 0,
            'pending_meetings' => 0,
            'pending_withdrawals' => 0,
            'pending_exams' => 0,
        ];

        if (!is_array($user)) {
            return $default;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return $default;
        }

        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $roles = dashboard_roles_from_user($user);
        if (!in_array('teacher', $roles, true) && !in_array('admin', $roles, true)) {
            $cache[$userId] = $default;
            return $default;
        }

        try {
            $pdo = \App\Core\PdoRegistry::get();
            $badgeData = (new \App\Services\Shared\DashboardBadgeService($pdo))->build($user, 'teacher');
            $withdrawals = new \App\Models\TeacherWithdrawalsModel($pdo);
            $cache[$userId] = [
                'notifications' => max(0, (int) ($badgeData['unreadCount'] ?? 0)),
                'support' => max(0, (int) ($badgeData['supportUnreadCount'] ?? 0)),
                'pending_meetings' => max(0, (int) ($badgeData['pendingMeetingsCount'] ?? 0)),
                'pending_withdrawals' => max(0, $withdrawals->countPendingWithdrawalRequests($userId, 'teacher_sales')),
                'pending_exams' => max(0, (int) ($badgeData['pendingExamCount'] ?? 0)),
            ];
        } catch (\Throwable $e) {
            $cache[$userId] = $default;
        }

        return $cache[$userId];
    }
}

