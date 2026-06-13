<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;

abstract class ApiController extends Controller
{
    protected function json(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    protected function hydrateCsrfFromHeaderOrBody(?array $data = null): array
    {
        $requestData = $data ?? [];
        if ($requestData === []) {
            $raw = (string) file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $requestData = $decoded;
            }
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_SERVER['HTTP_X_CSRF'] ?? null;
        if ($headerToken && empty($_POST['_csrf'])) {
            $_POST['_csrf'] = (string) $headerToken;
        }
        if (!empty($requestData['_csrf']) && empty($_POST['_csrf'])) {
            $_POST['_csrf'] = (string) $requestData['_csrf'];
        }

        return $requestData;
    }
}
