<?php
declare(strict_types=1);

namespace App\Core;

final class NotificationRouting
{
    /**
     * @return array<int,string>
     */
    public static function normalizeRoles(mixed $rolesInput): array
    {
        if (is_string($rolesInput)) {
            $decoded = json_decode($rolesInput, true);
            $rolesInput = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rolesInput)) {
            return [];
        }

        $roles = [];
        foreach ($rolesInput as $role) {
            if (!is_string($role)) {
                continue;
            }

            $normalized = strtolower(trim($role));
            if ($normalized === '') {
                continue;
            }

            $roles[$normalized] = $normalized;
        }

        return array_values($roles);
    }

    /**
     * @param array<int,string> $roles
     */
    public static function isAdminLike(array $roles): bool
    {
        return in_array('admin', $roles, true)
            || in_array('admin_secondary', $roles, true)
            || in_array('support', $roles, true);
    }

    /**
     * @param array<int,string> $roles
     */
    public static function shouldExcludeAnnouncements(array $roles): bool
    {
        return self::isAdminLike($roles);
    }

    /**
     * @param array<string,mixed> $notification
     * @param array<int,string> $roles
     */
    public static function shouldHideNotification(array $notification, array $roles): bool
    {
        if (!self::shouldExcludeAnnouncements($roles)) {
            return false;
        }

        return (int) ($notification['announcement_id'] ?? 0) > 0;
    }

    public static function normalizeStoredPath(string $path): string
    {
        $normalized = ltrim(trim($path), '/');
        $basePath = ltrim(rtrim((string) (defined('BASE_PATH') ? BASE_PATH : ''), '/'), '/');
        if ($basePath !== '' && ($normalized === $basePath || str_starts_with($normalized, $basePath . '/'))) {
            $normalized = ltrim(substr($normalized, strlen($basePath)), '/');
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $notification
     * @param array<int,string> $roles
     * @param callable(string):string $internalUrlBuilder
     */
    public static function resolveActionUrl(array $notification, array $roles, callable $internalUrlBuilder, ?string $preferredSpace = null): string
    {
        $raw = trim((string) ($notification['url'] ?? ''));
        if ($raw !== '') {
            if (preg_match('#^https?://#i', $raw) === 1) {
                return $raw;
            }

            $lower = strtolower($raw);
            if (str_starts_with($raw, '//') || str_starts_with($lower, 'javascript:')) {
                return '';
            }

            return (string) $internalUrlBuilder(self::normalizeStoredPath($raw));
        }

        $path = self::inferNotificationPath(
            (string) ($notification['title'] ?? ''),
            (string) ($notification['body'] ?? ''),
            $roles,
            $preferredSpace
        );

        return $path !== '' ? (string) $internalUrlBuilder($path) : '';
    }

    public static function hasMeaningfulTarget(string $url, string $notificationsUrl): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return true;
        }

        $notificationsHost = strtolower((string) parse_url($notificationsUrl, PHP_URL_HOST));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host !== '' && $notificationsHost !== '' && $host !== $notificationsHost) {
            return true;
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path === '') {
            return false;
        }

        return !self::isNotificationsPath($path);
    }

    /**
     * @param array<int,string> $roles
     */
    public static function inferNotificationPath(string $title, string $body, array $roles, ?string $preferredSpace = null): string
    {
        $text = strtolower(trim($title . ' ' . $body));
        if ($text === '') {
            return '';
        }

        $preferredSpace = strtolower(trim((string) ($preferredSpace ?? '')));
        if (!in_array($preferredSpace, ['teacher', 'student', 'company', 'admin'], true)) {
            $preferredSpace = '';
        }
        $isAdminLike = self::isAdminLike($roles);
        $isTeacher = in_array('teacher', $roles, true);
        $isStudent = in_array('student', $roles, true) || $isTeacher;
        $isCompanyAdmin = in_array('company_admin', $roles, true);
        $isCompanyMember = in_array('company_member', $roles, true);
        $useStudentSpace = $preferredSpace === 'student' && $isStudent;
        $useTeacherSpace = $preferredSpace === 'teacher' && $isTeacher;

        if (
            str_contains($text, 'invitation entreprise')
            || str_contains($text, 'compte entreprise')
            || str_contains($text, 'entreprise')
            || str_contains($text, 'company')
        ) {
            if ($isCompanyAdmin) {
                if (str_contains($text, 'validation') || str_contains($text, 'approbation') || str_contains($text, 'approval')) {
                    return 'company/dashboard#invites';
                }
                if (str_contains($text, 'siege') || str_contains($text, 'seat') || str_contains($text, 'place')) {
                    return 'company/dashboard#course-seats';
                }
                return 'company/dashboard';
            }

            if ($isCompanyMember) {
                if (
                    str_contains($text, 'cours')
                    || str_contains($text, 'attribu')
                    || str_contains($text, 'siege')
                    || str_contains($text, 'seat')
                    || str_contains($text, 'place')
                ) {
                    return 'company/dashboard#assigned-courses';
                }
                return 'company/dashboard';
            }

            if (str_contains($text, 'approuv') || str_contains($text, 'cours') || str_contains($text, 'attribu')) {
                return 'student/my_courses';
            }
        }

        if (
            str_contains($text, 'support')
            || str_contains($text, 'ticket')
            || str_contains($text, 'conversation')
        ) {
            return $isAdminLike ? 'admin/support' : 'support';
        }

        if (
            str_contains($text, 'affiliation')
            || str_contains($text, 'filleul')
            || str_contains($text, 'commission')
            || str_contains($text, 'parrainage')
        ) {
            if ($useStudentSpace) {
                return 'student/affiliation';
            }
            if ($isTeacher) {
                return 'teacher/affiliation';
            }
            if ($isStudent || !$isAdminLike) {
                return 'student/affiliation';
            }
        }

        if (str_contains($text, 'cours soumis') || str_contains($text, 'cours a valider') || str_contains($text, 'validation cours')) {
            return 'admin/courses';
        }

        if (str_contains($text, 'demande enseignant') || str_contains($text, 'candidature enseignant') || str_contains($text, 'kyc')) {
            if ($isAdminLike) {
                return 'admin/teacher_applications';
            }
            if ($isTeacher) {
                if (str_contains($text, 'approuv')) {
                    return 'teacher/dashboard';
                }
                return 'teacher/apply';
            }
        }

        if (str_contains($text, 'retrait')) {
            if ($isAdminLike) {
                return 'admin/sales';
            }
            if ($useStudentSpace) {
                return 'student/affiliation';
            }
            if ($isTeacher && !str_contains($text, 'parrainage') && !str_contains($text, 'affiliation')) {
                return 'teacher/withdrawals';
            }
            return 'student/affiliation';
        }

        if (str_contains($text, 'rendez-vous') || str_contains($text, 'meeting') || str_contains($text, 'creneau')) {
            if ($useStudentSpace) {
                return 'student/meetings';
            }
            return $isTeacher ? 'teacher/meetings' : 'student/meetings';
        }

        if (str_contains($text, 'certificat')) {
            return 'student/certificates';
        }

        if (
            str_contains($text, 'commande')
            || str_contains($text, 'paiement')
            || str_contains($text, 'achat')
            || str_contains($text, 'facture')
        ) {
            if ($isCompanyAdmin && (str_contains($text, 'entreprise') || str_contains($text, 'siege') || str_contains($text, 'seat') || str_contains($text, 'place'))) {
                return 'company/dashboard#course-seats';
            }
            if ($isCompanyMember && (str_contains($text, 'entreprise') || str_contains($text, 'siege') || str_contains($text, 'seat') || str_contains($text, 'place'))) {
                return 'company/dashboard#assigned-courses';
            }
            return 'student/orders';
        }

        if (str_contains($text, 'examen') || str_contains($text, 'correction') || str_contains($text, 'note')) {
            if ($useStudentSpace) {
                return 'student/dashboard#exams';
            }
            return $isTeacher ? 'teacher/exams_manage' : 'student/dashboard#exams';
        }

        if (str_contains($text, 'promotion') || str_contains($text, 'coupon')) {
            if ($useStudentSpace) {
                return 'student/my_courses';
            }
            return 'teacher/promotions';
        }

        if (str_contains($text, 'avis') || str_contains($text, 'review')) {
            if ($useStudentSpace) {
                return 'student/my_courses';
            }
            return 'teacher/reviews';
        }

        if (str_contains($text, 'cours')) {
            if ($useStudentSpace) {
                return 'student/my_courses';
            }
            if ($isTeacher || $useTeacherSpace) {
                return 'teacher/courses';
            }
            if ($isStudent || $isCompanyMember) {
                return 'student/my_courses';
            }
        }

        return '';
    }

    private static function isNotificationsPath(string $path): bool
    {
        $normalized = trim(strtolower(self::normalizeStoredPath($path)), '/');
        return $normalized === 'notifications'
            || $normalized === 'admin/notifications';
    }
}
