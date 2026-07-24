<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\WebNotificationsModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Support\SupportAccessService;
use App\Services\Web\NotificationsService;

final class NotificationsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('admin/notifications'));
        $adminAccess = new AdminAccessService();
        $supportAccess = new SupportAccessService();
        $roles = $supportAccess->rolesFromUser($user);
        $isSupportAgent = in_array('support', $roles, true);
        $hasAdminAccess = $adminAccess->hasAdminAccess($user);

        if (!$hasAdminAccess && !$isSupportAgent) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        $allowedScopes = $this->allowedScopesForRole($hasAdminAccess, $isSupportAgent);
        $defaultScope = $this->defaultScopeForRole($hasAdminAccess, $isSupportAgent);

        $query = $_GET;
        unset($query['space']);
        $query['scope'] = $this->normalizeScopeForRole($query['scope'] ?? null, $allowedScopes, $defaultScope);

        $post = $_POST;
        if (array_key_exists('scope', $post)) {
            $post['scope'] = $this->normalizeScopeForRole($post['scope'] ?? null, $allowedScopes, $defaultScope);
        }
        $requestScope = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
            ? (string) ($post['scope'] ?? $query['scope'])
            : (string) $query['scope'];

        $service = new NotificationsService(new WebNotificationsModel($pdo));
        $result = $service->handle(
            (int) $user['id'],
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $query,
            $post
        );

        if (!empty($result['redirect'])) {
            $redirect = $this->swapNotificationsRoute((string) $result['redirect'], $requestScope);
            header('Location: ' . $redirect);
            exit;
        }

        $unreadCount = (int) ($result['unreadCount'] ?? 0);

        $this->render('admin/notifications', [
            'user' => $user,
            'siteLogoUrl' => $this->siteLogoUrl(),
            'notifications' => $result['notifications'] ?? [],
            'unreadCount' => $unreadCount,
            'scope' => (string) ($result['scope'] ?? $defaultScope),
            'scopeCounts' => is_array($result['scopeCounts'] ?? null) ? $result['scopeCounts'] : [],
            'success' => (string) ($result['success'] ?? ''),
            'page' => (int) ($result['page'] ?? 1),
            'totalPages' => (int) ($result['totalPages'] ?? 1),
            'totalItems' => (int) ($result['totalItems'] ?? 0),
            'perPage' => (int) ($result['perPage'] ?? 10),
            'dashboardUrl' => $this->siteUrl('admin/dashboard'),
            'adminPermissions' => $adminAccess->allowedPermissions($user),
            'hasAdminAccess' => $hasAdminAccess,
            'isGeneralAdmin' => $adminAccess->isGeneralAdmin($user),
            'adminUnreadCount' => $unreadCount,
            'allowedScopes' => $allowedScopes,
            'defaultScope' => $defaultScope,
        ]);
    }

    private function swapNotificationsRoute(string $redirect, string $scope): string
    {
        $webBase = $this->siteUrl('notifications');
        $adminBase = $this->siteUrl('admin/notifications');

        if (str_starts_with($redirect, $webBase)) {
            $redirect = $adminBase . substr($redirect, strlen($webBase));
            return $this->ensureScopeOnAdminNotificationsUrl($redirect, $scope);
        }

        return $redirect;
    }

    private function ensureScopeOnAdminNotificationsUrl(string $url, string $scope): string
    {
        $base = $this->siteUrl('admin/notifications');
        if (!str_starts_with($url, $base)) {
            return $url;
        }

        $parts = parse_url($url);
        $query = [];
        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $query);
        }
        $query['scope'] = $scope;

        $path = isset($parts['path']) ? (string) $parts['path'] : $base;
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        $queryString = http_build_query($query);

        return $path . ($queryString !== '' ? '?' . $queryString : '') . $fragment;
    }

    /**
     * @return array<int,string>
     */
    private function allowedScopesForRole(bool $hasAdminAccess, bool $isSupportAgent): array
    {
        if ($hasAdminAccess) {
            return ['all', 'unread', 'important', 'admin', 'support'];
        }

        if ($isSupportAgent) {
            return ['all', 'unread', 'important', 'support'];
        }

        return ['all', 'unread', 'important'];
    }

    private function defaultScopeForRole(bool $hasAdminAccess, bool $isSupportAgent): string
    {
        return 'all';
    }

    /**
     * @param array<int,string> $allowedScopes
     */
    private function normalizeScopeForRole(mixed $scope, array $allowedScopes, string $defaultScope): string
    {
        $value = strtolower(trim((string) $scope));
        if ($value === '') {
            return $defaultScope;
        }

        return in_array($value, $allowedScopes, true) ? $value : $defaultScope;
    }
}
