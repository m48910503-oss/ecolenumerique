<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;

final class LogoutController extends Controller
{
    public function __invoke(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            $this->render('web/public_error', $this->publicErrorViewData(
                'Action interdite',
                'La deconnexion doit etre effectuee via un formulaire securise.'
            ));
            return;
        }
        $this->requireCsrf();
        $this->logout();
        header('Location: ' . $this->siteUrl(''));
        exit;
    }
}
