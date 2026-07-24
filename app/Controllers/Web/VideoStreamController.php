<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\WebVideoStreamModel;
use App\Services\Web\VideoStreamService;

final class VideoStreamController extends Controller
{
    public function __invoke(): void
    {
        $pdo = $this->pdo();

        $lessonId = (int) ($_GET['lesson_id'] ?? 0);
        $token = (string) ($_GET['token'] ?? '');

        $service = new VideoStreamService(new WebVideoStreamModel($pdo));
        $result = $service->handle($this->currentUser(), $lessonId, $token);

        if (!empty($result['error'])) {
            http_response_code((int) ($result['httpStatus'] ?? 404));
            echo (string) $result['error'];
            exit;
        }

        header('Content-Type: ' . (string) $result['mime']);
        header('Content-Length: ' . (string) ((int) ($result['size'] ?? 0)));
        header('Accept-Ranges: bytes');
        header('Content-Disposition: inline; filename="video.mp4"');
        readfile((string) $result['path']);
        exit;
    }
}
