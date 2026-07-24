<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\SharedCompanySchemaModel;
use App\Services\Company\DashboardService;
use App\Services\Shared\CompanySchemaService;

final class InvitesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('company/invites'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        if (!in_array('company_admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new DashboardService(
            new CompanyDashboardModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $state = $service->buildInvitesState($user);
        $state['successMessage'] = (string) $this->flashGet('success', '');
        $state['inviteLink'] = (string) $this->flashGet('invite_link', '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $state = $service->handlePost($state, $user, $_POST, $_FILES, 'invites');
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($state['errors'])
            && (string) ($state['successMessage'] ?? '') !== ''
        ) {
            $this->flashSet('success', (string) $state['successMessage']);
            if ((string) ($state['inviteLink'] ?? '') !== '') {
                $this->flashSet('invite_link', (string) $state['inviteLink']);
            }
            $this->redirectSelf('company/invites');
        }

        $this->render('company/invites', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'errors' => $state['errors'] ?? [],
            'successMessage' => (string) ($state['successMessage'] ?? ''),
            'inviteLink' => (string) ($state['inviteLink'] ?? ''),
            'company' => $state['company'] ?? null,
            'inviteRows' => $state['inviteRows'] ?? [],
            'inviteSummary' => $state['inviteSummary'] ?? [],
            'unreadCount' => (int) ($state['unreadCount'] ?? 0),
        ]);
    }
}
