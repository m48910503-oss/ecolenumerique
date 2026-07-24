<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\WebHomeModel;
use App\Services\Web\HomeService;

final class HomeController extends Controller
{
    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $service = new HomeService(new WebHomeModel($pdo));
        $this->render('web/home', $service->buildViewData());
    }
}

