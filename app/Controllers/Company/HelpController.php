<?php
declare(strict_types=1);

namespace App\Controllers\Company;

use App\Core\Controller;

final class HelpController extends Controller
{
    public function __invoke(): void
    {
        $this->requireAuth(true, $this->siteUrl('company/dashboard'));
        header('Location: ' . $this->siteUrl('company/dashboard'));
        exit;
    }
}
