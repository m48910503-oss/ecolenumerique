<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Core\DashboardContextResolver;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPaymentModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Models\ReferralWalletModel;
use App\Models\StudentDashboardModel;
use App\Models\TeacherWithdrawalsModel;
use App\Models\WebCheckoutModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\FedapayService;
use App\Services\Shared\PaymentOpsService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Web\CheckoutService;

final class CheckoutController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();
        $requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $isAjax = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest';

        $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : '';
        $returnPath = $this->siteUrl('checkout' . $queryString);
        $user = $this->requireAuth(true, $returnPath);

        if ($requestMethod === 'POST') {
            $this->requireCsrf();
        }

        $promotions = new PromotionsService(
            new SharedPromotionModel($pdo),
            new CompanySchemaService(new SharedCompanySchemaModel($pdo))
        );
        $paymentOps = new PaymentOpsService(
            new SharedPaymentModel($pdo),
            new ReferralService(new SharedReferralModel($pdo)),
            $promotions,
            new ReferralWalletModel($pdo)
        );
        $fedapay = new FedapayService();
        $service = new CheckoutService(
            new WebCheckoutModel($pdo),
            $paymentOps,
            $fedapay,
            $promotions,
            new StudentDashboardModel($pdo),
            new TeacherWithdrawalsModel($pdo),
            new ReferralWalletModel($pdo)
        );
        $data = $service->handle(
            $user,
            $requestMethod,
            (string) ($_SERVER['REQUEST_URI'] ?? ''),
            $_GET,
            $_POST
        );

        if (!empty($data['redirect'])) {
            if ($isAjax && $requestMethod === 'POST') {
                header('Content-Type: application/json; charset=utf-8');
                echo (string) json_encode([
                    'ok' => true,
                    'redirectTo' => (string) $data['redirect'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            header('Location: ' . (string) $data['redirect']);
            exit;
        }

        if ($isAjax && $requestMethod === 'POST') {
            $errors = is_array($data['errors'] ?? null)
                ? array_values(array_map(static fn($error): string => (string) $error, $data['errors']))
                : [];
            $warnings = is_array($data['warnings'] ?? null)
                ? array_values(array_map(static fn($warning): string => (string) $warning, $data['warnings']))
                : [];
            $fedapayCheckout = is_array($data['fedapayCheckout'] ?? null) ? $data['fedapayCheckout'] : null;

            if (is_array($fedapayCheckout)) {
                $fedapayCheckout['public_key'] = $fedapay->publicKey();
                $fedapayCheckout['return_url'] = $this->siteUrl('payment_return');
            }

            if ($errors !== []) {
                http_response_code(422);
            }

            header('Content-Type: application/json; charset=utf-8');
            echo (string) json_encode([
                'ok' => $errors === [],
                'errors' => $errors,
                'warnings' => $warnings,
                'success' => (string) ($data['success'] ?? ''),
                'fedapayCheckout' => $fedapayCheckout,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $data['user'] = $user;
        $data['isLoggedIn'] = true;
        $data['dashboardUrl'] = DashboardContextResolver::dashboardUrlForUser($user);
        $data['headerNotificationCount'] = max(0, (int) ($data['headerNotificationCount'] ?? 0));

        $this->render('web/checkout', $data);
    }
}
