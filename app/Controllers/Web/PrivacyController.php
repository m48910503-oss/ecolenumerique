<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Services\Web\LegalPageService;

final class PrivacyController extends Controller
{
    public function __invoke(): void
    {
        $service = new LegalPageService();
        $data = $service->build('2026-01-15');
        $this->render('web/privacy', $data);
    }
}

