<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\WebCourseModel;
use App\Services\Web\CoursePreviewOpenService;
use App\Services\Web\PreviewTokenService;

final class CoursePreviewOpenController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $courseId = (int) ($_GET['course_id'] ?? 0);
        $token = (string) ($_GET['token'] ?? '');

        $service = new CoursePreviewOpenService(
            new WebCourseModel($pdo),
            new PreviewTokenService()
        );
        $result = $service->handle($this->currentUser(), $courseId, $token);

        if (!empty($result['error'])) {
            http_response_code((int) ($result['httpStatus'] ?? 403));
            $this->render('web/public_error', $this->publicErrorViewData(
                (string) ($result['errorTitle'] ?? 'Apercu indisponible'),
                (string) $result['error']
            ));
            return;
        }

        $redirectTo = (string) ($result['redirectTo'] ?? '');
        if ($redirectTo === '') {
            http_response_code(404);
            $this->render('web/public_error', $this->publicErrorViewData(
                'Apercu indisponible',
                "Le contenu d'apercu n'est pas accessible pour le moment."
            ));
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Referrer-Policy: no-referrer');
        header('Location: ' . $redirectTo);
        exit;
    }
}
