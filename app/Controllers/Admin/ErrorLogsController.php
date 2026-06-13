<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\Admin\AdminAccessService;
use App\Services\Admin\ErrorLogsService;

final class ErrorLogsController extends Controller
{
    public function __invoke(): void
    {
        $user = $this->requireAuth(true, $this->siteUrl('admin/error_logs'));
        $access = new AdminAccessService();
        if (!$access->canAccess($user, AdminAccessService::PERMISSION_ERROR_LOGS)) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        $service = new ErrorLogsService();
        $viewData = $service->buildViewData($_GET);

        if (strtolower(trim((string) ($_GET['export'] ?? ''))) === 'json') {
            $activeLog = preg_replace('/[^a-z0-9_-]+/i', '_', (string) ($viewData['activeLog'] ?? 'logs'));
            $filename = 'logs_' . ($activeLog !== '' ? $activeLog : 'systeme') . '_' . date('Ymd_His') . '.json';
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $payload = [
                'source' => $viewData['activeLog'] ?? '',
                'meta' => $viewData['logMeta'] ?? [],
                'filters' => [
                    'filter' => $viewData['quickFilter'] ?? 'all',
                    'search' => $viewData['searchQuery'] ?? '',
                    'limit' => $viewData['limit'] ?? 0,
                ],
                'summary' => $viewData['severitySummary'] ?? [],
                'entries' => $viewData['logEntries'] ?? [],
                'exported_at' => date('c'),
            ];
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            echo $json !== false ? $json : '{}';
            return;
        }

        $this->render('admin/error_logs', array_merge(
            [
                'siteLogoUrl' => $this->siteLogoUrl(),
                'user' => $user,
                'adminPermissions' => $access->allowedPermissions($user),
                'hasAdminAccess' => $access->hasAdminAccess($user),
                'isGeneralAdmin' => $access->isGeneralAdmin($user),
            ],
            $viewData
        ));
    }
}
