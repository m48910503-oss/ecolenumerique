<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\WebSupportDownloadModel;
use App\Services\Support\SupportGuestContextService;
use App\Services\Support\SupportAccessService;
use App\Services\Support\SupportStorageService;

final class SupportDownloadService extends Service
{
    private WebSupportDownloadModel $model;
    private SupportAccessService $access;
    private SupportGuestContextService $guestContext;
    private SupportStorageService $storage;

    public function __construct(
        WebSupportDownloadModel $model,
        ?SupportAccessService $access = null,
        ?SupportGuestContextService $guestContext = null,
        ?SupportStorageService $storage = null
    )
    {
        $this->model = $model;
        $this->access = $access ?? new SupportAccessService();
        $this->guestContext = $guestContext ?? new SupportGuestContextService();
        $this->storage = $storage ?? new SupportStorageService();
    }

    /**
     * @return array<string,mixed>
     */
    public function handle(?array $user, int $attachmentId): array
    {
        $roles = $this->access->rolesFromUser($user);
        $isStaff = $this->access->isStaff($roles);

        if ($attachmentId < 1) {
            return $this->abort(404, 'Fichier introuvable.');
        }

        $row = $this->model->findAttachmentWithConversation($attachmentId);
        if (!$row) {
            return $this->abort(404, 'Fichier introuvable.');
        }

        if (!$isStaff) {
            $guestKey = $this->guestContext->viewerKey();
            $createdBy = (int) ($row['created_by'] ?? 0);
            $viewerKey = trim((string) ($row['viewer_key'] ?? ''));
            $userId = (int) ($user['id'] ?? 0);

            if ($userId > 0 && $createdBy === $userId) {
                // Owner access granted.
            } elseif ($userId < 1 && $viewerKey !== '' && hash_equals($guestKey, $viewerKey)) {
                // Guest session access granted.
            } else {
                return $this->abort(403, "Acc\u{00e8}s refus\u{00e9}.");
            }
        }

        $realPath = $this->storage->resolveStoredPath((string) ($row['file_path'] ?? ''));
        if ($realPath === null) {
            return $this->abort(404, 'Fichier introuvable.');
        }

        $filename = $this->storage->sanitizeFilename((string) ($row['original_name'] ?? 'piece-jointe'));
        $filename = str_replace(["\r", "\n", '"'], '', $filename);
        $mime = (string) ($row['mime'] ?? '');
        if ($mime === '') {
            $mime = 'application/octet-stream';
        }

        return [
            'httpStatus' => null,
            'error' => null,
            'path' => $realPath,
            'filename' => $filename,
            'mime' => $mime,
            'size' => (int) filesize($realPath),
        ];
    }

    /**
     * @return array{httpStatus:int,error:string}
     */
    private function abort(int $httpStatus, string $message): array
    {
        return [
            'httpStatus' => $httpStatus,
            'error' => $message,
        ];
    }
}

