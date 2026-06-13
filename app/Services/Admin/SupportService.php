<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;

use App\Models\AdminSupportModel;

final class SupportService extends Service
{
    private AdminSupportModel $model;

    public function __construct(AdminSupportModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @return array{basePath:string,initialConv:int,userId:int}
     */
    public function buildViewData(array $user, array $get): array
    {
        $basePath = rtrim((string) BASE_PATH, '/');
        $initialConv = (int) ($get['conv'] ?? 0);
        if ($initialConv > 0 && !$this->model->conversationExists($initialConv)) {
            $initialConv = 0;
        }

        return [
            'basePath' => $basePath,
            'initialConv' => $initialConv,
            'userId' => (int) ($user['id'] ?? 0),
        ];
    }
}

