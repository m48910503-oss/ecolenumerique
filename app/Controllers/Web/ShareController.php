<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Models\WebShareModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService;
use App\Services\Web\ShareService;

final class ShareController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $service = new ShareService(
            new WebShareModel($pdo),
            new PromotionsService(
                new SharedPromotionModel($pdo),
                new CompanySchemaService(new SharedCompanySchemaModel($pdo))
            )
        );
        $result = $service->handle(
            trim((string) ($_GET['token'] ?? '')),
            trim((string) ($_GET['ref'] ?? '')),
            trim((string) ($_GET['course_ref'] ?? ''))
        );

        if (!empty($result['abortMessage'])) {
            http_response_code((int) ($result['httpStatus'] ?? 404));
            $this->render('web/public_error', $this->publicErrorViewData(
                (string) ($result['abortTitle'] ?? 'Lien indisponible'),
                (string) $result['abortMessage']
            ));
            return;
        }

        header('Location: ' . (string) ($result['redirect'] ?? $this->siteUrl('')));
        exit;
    }
}
