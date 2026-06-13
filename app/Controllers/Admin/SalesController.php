<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AdminSalesModel;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPaymentModel;
use App\Models\SharedPlatformSettingsModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Models\ReferralWalletModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PaymentOpsService;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Admin\SalesService;

final class SalesController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();

        $user = $this->requireAuth(true, $this->siteUrl('admin/sales'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_SALES)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $referrals = new ReferralService(new SharedReferralModel($pdo));
        $promotions = new PromotionsService(
            new SharedPromotionModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $service = new SalesService(
            new AdminSalesModel($pdo),
            new PaymentOpsService(new SharedPaymentModel($pdo), $referrals, $promotions, new ReferralWalletModel($pdo)),
            new PlatformSettingsService(new SharedPlatformSettingsModel($pdo)),
            $referrals
        );
        $result = $service->handle($user, (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'), $_POST);

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('admin/sales', array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'user' => $user,
                'adminPermissions' => $access->allowedPermissions($user),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
            ],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}

