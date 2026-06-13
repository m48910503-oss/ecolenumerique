<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Models\TeacherPromotionsModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService as SharedPromotionsService;
use App\Services\Teacher\PromotionsService;

final class CouponsController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->requireAuth(true, $this->siteUrl('teacher/coupons'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isTeacher = in_array('teacher', $roles, true);
        if (!$isTeacher) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $query = [];
            if (!empty($_GET['period'])) {
                $query['period'] = (string) $_GET['period'];
            }
            if (!empty($_GET['from'])) {
                $query['from'] = (string) $_GET['from'];
            }
            if (!empty($_GET['to'])) {
                $query['to'] = (string) $_GET['to'];
            }
            $queryString = http_build_query($query);
            $redirectTo = $this->siteUrl('teacher/promotions')
                . ($queryString !== '' ? ('?' . $queryString) : '')
                . '#section-coupons';
            header('Location: ' . $redirectTo);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCsrf();
        }

        $siteLogoUrl = $this->siteLogoUrl();

        $service = new PromotionsService(
            new TeacherPromotionsModel($pdo),
            new SharedPromotionsService(
                new SharedPromotionModel($pdo),
                new CompanySchemaService(new SharedCompanySchemaModel($pdo))
            )
        );
        if (!isset($_POST['promo_mode'])) {
            $_POST['promo_mode'] = 'coupon';
        }
        $result = $service->handle(
            $user,
            true,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            'teacher/promotions',
            'all',
            $_GET
        );

        if (!empty($result['redirectTo'])) {
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        $this->render('teacher/promotions', array_merge(
            ['siteLogoUrl' => $siteLogoUrl],
            is_array($result['view'] ?? null) ? $result['view'] : []
        ));
    }
}
