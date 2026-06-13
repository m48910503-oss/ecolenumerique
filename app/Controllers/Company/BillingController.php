<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\SharedCompanySchemaModel;
use App\Services\Company\DashboardService;
use App\Services\Shared\CompanySchemaService;

final class BillingController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $user = $this->requireAuth(true, $this->siteUrl('company/billing'));
        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        if (!in_array('company_admin', $roles, true)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new DashboardService(
            new CompanyDashboardModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $state = $service->buildBillingState($user);

        $this->render('company/billing', [
            'siteLogoUrl' => $siteLogoUrl,
            'user' => $user,
            'errors' => $state['errors'] ?? [],
            'company' => $state['company'] ?? null,
            'billingRows' => $state['billingRows'] ?? [],
            'billingSummary' => $state['billingSummary'] ?? [],
            'seatSummary' => $state['seatSummary'] ?? [],
            'unreadCount' => (int) ($state['unreadCount'] ?? 0),
        ]);
    }
}
