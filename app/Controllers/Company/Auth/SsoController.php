<?php
declare(strict_types=1);

namespace App\Controllers\Company\Auth;

use App\Core\Controller;

final class SsoController extends Controller
{
    public function __invoke(): void
    {
        $this->flashSet('error', 'Le SSO entreprise n’est pas activé dans la version simple.');
        header('Location: ' . $this->siteUrl('login'));
        exit;
    }
}
