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

final class CourseWatchController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $slug = isset($_GET['slug']) ? (string) $_GET['slug'] : null;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $returnPath = $this->siteUrl('course/watch' . (!empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : ''));
        $this->requireAuth(true, $returnPath);

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
                (string) ($result['abortMessage'] ?? "Le cours demandé n'est pas accessible pour le moment.")
            ));
            return;
        }

        $view = is_array($result['view'] ?? null) ? $result['view'] : [];
        if (!empty($view['isEnrolled'])) {
            $this->render('web/course_watch', $view);
            return;
        }

        $course = is_array($view['course'] ?? null) ? $view['course'] : [];
        $fallback = !empty($course['slug'])
            ? $this->siteUrl('course?slug=' . rawurlencode((string) $course['slug']))
            : ($id > 0
                ? $this->siteUrl('course?id=' . $id)
                : $this->siteUrl('course'));

        header('Location: ' . $fallback);
        exit;
    }
}
