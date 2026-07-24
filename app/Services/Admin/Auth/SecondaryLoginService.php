<?php
declare(strict_types=1);

namespace App\Services\Admin\Auth;

use App\Core\Service;
use App\Models\AdminDashboardModel;
use App\Models\AdminLoginModel;
use App\Security\LoginThrottle;
use App\Services\Admin\AdminAccessService;

final class SecondaryLoginService extends Service
{
    private AdminLoginModel $model;

    public function __construct(AdminLoginModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(string $method, array $post): array
    {
        $errors = [];
        $oldEmail = '';
        $throttle = new LoginThrottle();

        if ($method === 'POST') {
            $email = strtolower(trim((string) ($post['email'] ?? '')));
            $password = (string) ($post['password'] ?? '');
            $oldEmail = $email;
            $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Adresse e-mail invalide.';
            } elseif ($throttle->isBlocked('admin_secondary', $email, $ip)) {
                $errors[] = 'Trop de tentatives de connexion. Réessayez plus tard.';
            } else {
                $user = $this->model->findUserByEmail($email);
                $roles = [];

                if (is_array($user) && !empty($user['roles_json'])) {
                    $decoded = json_decode((string) $user['roles_json'], true);
                    if (is_array($decoded)) {
                        $roles = $decoded;
                    }
                }

                $isSecondary = in_array(AdminAccessService::ROLE_SECONDARY, $roles, true);

                if (!$user || !password_verify($password, (string) ($user['password_hash'] ?? '')) || !$isSecondary) {
                    $throttle->recordFailure('admin_secondary', $email, $ip);
                    $errors[] = 'Identifiants invalides ou droits insuffisants.';
                } elseif ((string) ($user['status'] ?? '') !== 'active' || empty($user['email_verified_at'])) {
                    $throttle->recordFailure('admin_secondary', $email, $ip);
                    $errors[] = 'Compte non actif ou adresse e-mail non vérifiée.';
                } else {
                    $access = new AdminAccessService();
                    $redirectTo = $this->resolveSecondaryRedirect($access, $user);

                    if ($redirectTo === null) {
                        $throttle->recordFailure('admin_secondary', $email, $ip);
                        $errors[] = 'Accès administrateur secondaire inactif. Contactez un administrateur général.';
                    } else {
                        $throttle->clear('admin_secondary', $email, $ip);
                        $this->loginUser($user, false);
                        (new AdminDashboardModel($this->pdo()))->insertAuditLog(
                            (int) ($user['id'] ?? 0),
                            'secondary_admin_login',
                            'auth',
                            null,
                            [
                                'source' => 'admin_secondary_login',
                                'redirect_to' => $redirectTo,
                            ]
                        );

                        return [
                            'redirectTo' => $redirectTo,
                            'view' => [],
                        ];
                    }
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'oldEmail' => $oldEmail,
                'adminLoginUrl' => $this->siteUrl('admin/login'),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     */
    private function resolveSecondaryRedirect(AdminAccessService $access, array $user): ?string
    {
        $permissionToRoute = [
            AdminAccessService::PERMISSION_DASHBOARD => 'admin/dashboard',
            AdminAccessService::PERMISSION_SUPPORT => 'admin/support',
            AdminAccessService::PERMISSION_SUPPORT_AGENTS => 'admin/support_agents',
            AdminAccessService::PERMISSION_COURSES => 'admin/courses',
            AdminAccessService::PERMISSION_ANNOUNCEMENTS => 'admin/announcements',
            AdminAccessService::PERMISSION_TEACHER_APPLICATIONS => 'admin/teacher_applications',
            AdminAccessService::PERMISSION_SALES => 'admin/sales',
            AdminAccessService::PERMISSION_CATEGORIES => 'admin/categories',
            AdminAccessService::PERMISSION_SITE_SETTINGS => 'admin/site_settings',
            AdminAccessService::PERMISSION_ERROR_LOGS => 'admin/error_logs',
        ];

        foreach ($permissionToRoute as $permission => $route) {
            if ($access->canAccess($user, $permission)) {
                return $this->siteUrl($route);
            }
        }

        return null;
    }
}
