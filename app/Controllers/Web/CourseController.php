<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Models\SharedReferralModel;
use App\Models\WebCourseModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;
use App\Services\Web\CourseService;

final class CourseController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $slug = isset($_GET['slug']) ? (string) $_GET['slug'] : null;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        $service = new CourseService(
            new WebCourseModel($pdo),
            new ReferralService(new SharedReferralModel($pdo)),
            new PromotionsService(
                new SharedPromotionModel($pdo),
                new CompanySchemaService(new SharedCompanySchemaModel($pdo))
            )
        );
        $result = $service->handle(
            $slug,
            $id,
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $_POST,
            $_SERVER
        );

        if (!empty($result['redirectTo'])) {
            $status = (int) ($result['redirectStatus'] ?? 302);
            if ($status >= 300 && $status < 400) {
                http_response_code($status);
            }
            header('Location: ' . (string) $result['redirectTo']);
            exit;
        }

        if (!empty($result['httpStatus'])) {
            http_response_code((int) $result['httpStatus']);
            $this->render('web/public_error', $this->publicErrorViewData(
                (string) ($result['abortTitle'] ?? 'Cours indisponible'),
                (string) ($result['abortMessage'] ?? "Le cours demand\u{00e9} n'est pas accessible pour le moment.")
            ));
            return;
        }

        $this->render('web/course', $result['view'] ?? []);
    }
}
