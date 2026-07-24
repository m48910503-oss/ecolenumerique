<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\SharedCompanySchemaModel;
use App\Services\Company\DashboardService;
use App\Services\Shared\CompanySchemaService;

final class SeatsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('company/seats'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        if (!in_array('company_admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new DashboardService(
            new CompanyDashboardModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $state = $service->buildSeatsState($user);
        $state['successMessage'] = (string) $this->flashGet('success', '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
            $state = $service->handlePost($state, $user, $_POST, $_FILES, 'seats');
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && empty($state['errors'])
            && (string) ($state['successMessage'] ?? '') !== ''
        ) {
            $this->flashSet('success', (string) $state['successMessage']);
            $this->redirectSelf('company/seats');
        }

        $this->render('company/seats', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'errors' => $state['errors'] ?? [],
            'successMessage' => (string) ($state['successMessage'] ?? ''),
            'company' => $state['company'] ?? null,
            'seatCourseRows' => $state['seatCourseRows'] ?? [],
            'seatSummary' => $state['seatSummary'] ?? [],
            'seatMemberOptions' => $state['seatMemberOptions'] ?? [],
            'unreadCount' => (int) ($state['unreadCount'] ?? 0),
        ]);
    }
}
