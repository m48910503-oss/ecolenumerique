<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\WebSupportDownloadModel;
use App\Services\Web\SupportDownloadService;

final class SupportDownloadController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $user = $this->currentUser();
        $attachmentId = (int) ($_GET['id'] ?? 0);

        $service = new SupportDownloadService(new WebSupportDownloadModel($pdo));
        $result = $service->handle($user, $attachmentId);

        if (!empty($result['error'])) {
            http_response_code((int) ($result['httpStatus'] ?? 404));
            echo (string) $result['error'];
            exit;
        }

        $mime = (string) $result['mime'];
        $inlineRequested = (string) ($_GET['inline'] ?? '') === '1';
        $disposition = ($inlineRequested && str_starts_with($mime, 'image/')) ? 'inline' : 'attachment';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) ((int) ($result['size'] ?? 0)));
        header('Content-Disposition: ' . $disposition . '; filename="' . (string) $result['filename'] . '"');
        header('X-Content-Type-Options: nosniff');
        readfile((string) $result['path']);
        exit;
    }
}
