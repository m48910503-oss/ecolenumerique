<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;
use App\Models\CompanyDashboardModel;
use App\Models\SharedCompanySchemaModel;
use App\Services\Company\DashboardService;
use App\Services\Shared\CompanySchemaService;

final class OnboardingController extends Controller
{
    public function __invoke(): void
    {
        $this->requireAuth(true, $this->siteUrl('company/dashboard'));
        header('Location: ' . $this->siteUrl('company/dashboard'));
        exit;
    }
}
