<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\SharedCompanySchemaModel;
use App\Services\Company\DashboardService;
use App\Services\Shared\CompanySchemaService;

final class MembersController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('company/members'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        if (!in_array('company_admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new DashboardService(
            new CompanyDashboardModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $state = $service->buildMembersState($user);
        $state['successMessage'] = (string) $this->flashGet('success', '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $state = $service->handlePost($state, $user, $_POST, $_FILES, 'members');
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($state['errors'])
            && (string) ($state['successMessage'] ?? '') !== ''
        ) {
            $this->flashSet('success', (string) $state['successMessage']);
            $this->redirectSelf('company/members');
        }

        $this->render('company/members', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'errors' => $state['errors'] ?? [],
            'successMessage' => (string) ($state['successMessage'] ?? ''),
            'company' => $state['company'] ?? null,
            'memberRows' => $state['memberRows'] ?? [],
            'memberSummary' => $state['memberSummary'] ?? [],
            'teams' => $state['teams'] ?? [],
            'unreadCount' => (int) ($state['unreadCount'] ?? 0),
        ]);
    }
}
