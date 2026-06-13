<?php
declare(strict_types=1);

namespace App\Core;

final class DashboardContextResolver
{
    /**
     * @param array<int,string> $roles
     * @return array<int,string>
     */
    public static function normalizeRoles(array $roles): array
    {
        $normalized = [];
        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }

            $clean = strtolower(trim($role));
            if ($clean !== '') {
                $normalized[$clean] = $clean;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param array<string,mixed>|null $user
     * @return array<int,string>
     */
    public static function rolesFromUser(?array $user): array
    {
        if (!is_array($user)) {
            return [];
        }

        $raw = $user['roles_json'] ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $roles = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $roles = $raw;
        } else {
            $roles = [];
        }

        return self::normalizeRoles($roles);
    }

    /**
     * @param array<int,string> $roles
     * @return array<int,string>
     */
    public static function availableSpacesForRoles(array $roles): array
    {
        $roles = self::normalizeRoles($roles);
        $spaces = [];

        if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
            $spaces[] = 'admin';
        }
        $hasTeacher = in_array('teacher', $roles, true);
        $hasStudent = in_array('student', $roles, true);

        if ($hasTeacher) {
            $spaces[] = 'teacher';
        }
        if (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
            $spaces[] = 'company';
        }
        if ($hasStudent || $hasTeacher) {
            $spaces[] = 'student';
        }

        return $spaces;
    }

    /**
     * @param array<string,mixed>|null $user
     * @return array<int,string>
     */
    public static function availableSpacesForUser(?array $user): array
    {
        return self::availableSpacesForRoles(self::rolesFromUser($user));
    }

    /**
     * @param array<int,string> $roles
     */
    public static function resolveSpaceForRoles(array $roles, ?string $requested = null): ?string
    {
        $spaces = self::availableSpacesForRoles($roles);
        if ($spaces === []) {
            return null;
        }

        $requested = strtolower(trim((string) ($requested ?? '')));
        if ($requested !== '' && in_array($requested, $spaces, true)) {
            return $requested;
        }

        return $spaces[0] ?? null;
    }

    /**
     * @param array<string,mixed>|null $user
     */
    public static function resolveSpaceForUser(?array $user, ?string $requested = null): ?string
    {
        return self::resolveSpaceForRoles(self::rolesFromUser($user), $requested);
    }

    public static function spaceLabel(string $space): string
    {
        return match (strtolower(trim($space))) {
            'admin' => 'Administration',
            'teacher' => 'Espace enseignant',
            'company' => 'Espace entreprise',
            'student' => 'Espace etudiant',
            default => ucfirst(trim($space)),
        };
    }

    public static function usesDashboardShell(?string $space): bool
    {
        return in_array(strtolower(trim((string) ($space ?? ''))), ['teacher', 'student'], true);
    }

    /**
     * @param array<int,string> $roles
     */
    public static function dashboardUrlForRoles(array $roles, ?string $requested = null): string
    {
        $space = self::resolveSpaceForRoles($roles, $requested);
        if ($space === null) {
            return self::siteUrl('');
        }

        return self::dashboardUrlForSpace($space);
    }

    /**
     * @param array<string,mixed>|null $user
     */
    public static function dashboardUrlForUser(?array $user, ?string $requested = null): string
    {
        return self::dashboardUrlForRoles(self::rolesFromUser($user), $requested);
    }

    public static function dashboardUrlForSpace(string $space): string
    {
        return match (strtolower(trim($space))) {
            'admin' => self::siteUrl('admin/dashboard'),
            'teacher' => self::siteUrl('teacher/dashboard'),
            'company' => self::siteUrl('company/dashboard'),
            'student' => self::siteUrl('student/dashboard'),
            default => self::siteUrl(''),
        };
    }

    private static function siteUrl(string $path = ''): string
    {
        if (function_exists('site_url')) {
            return \site_url($path);
        }

        $basePath = defined('BASE_PATH') ? (string) BASE_PATH : '/';
        $base = rtrim($basePath, '/');

        return $base . '/' . ltrim($path, '/');
    }
}
