<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\WebVideoStreamModel; 

final class VideoStreamService extends Service
{
    private WebVideoStreamModel $model;

    public function __construct(WebVideoStreamModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed>|null $user
     * @return array<string,mixed>
     */
    public function handle(?array $user, int $lessonId, string $token): array
    {
        if (!$user) {
            return $this->abort(401, 'Auth requise');
        }
        if ($lessonId < 1 || $token === '') {
            return $this->abort(400, "Param\u{00e8}tres manquants");
        }

        $tokenParts = explode('.', $token, 2);
        if (count($tokenParts) !== 2) {
            return $this->abort(403, 'Token invalide');
        }

        $payloadRaw = base64_decode(strtr((string) $tokenParts[0], '-_', '+/'));
        if ($payloadRaw === false) {
            return $this->abort(403, 'Token invalide');
        }
        $payloadParts = explode('|', $payloadRaw);
        if (count($payloadParts) !== 4) {
            return $this->abort(403, 'Token invalide');
        }

        [$tokenLesson, $tokenCourse, $tokenUser, $expiresAt] = $payloadParts;
        $tokenLessonId = (int) $tokenLesson;
        $tokenCourseId = (int) $tokenCourse;
        $tokenUserId = (int) $tokenUser;
        $expiry = (int) $expiresAt;
        $userId = (int) ($user['id'] ?? 0);

        $secret = \defined('APP_KEY') ? trim((string) \constant('APP_KEY')) : '';
        if ($secret === '') {
            return $this->abort(500, 'Configuration invalide');
        }
        $expectedSignature = hash_hmac('sha256', implode('|', $payloadParts), $secret);
        $providedSignature = (string) $tokenParts[1];
        if (
            !hash_equals($expectedSignature, $providedSignature)
            || $expiry < time()
            || $tokenLessonId !== $lessonId
            || $tokenUserId !== $userId
        ) {
            return $this->abort(403, "Token expir\u{00e9} ou invalide");
        }

        if (!$this->model->hasActiveEnrollment($userId, $tokenCourseId)) {
            return $this->abort(403, "Acc\u{00e8}s refus\u{00e9}");
        }

        $filePath = $this->model->findVideoPathForLessonAndCourse($lessonId, $tokenCourseId);
        if (!$filePath || !is_file($filePath)) {
            return $this->abort(404, 'Fichier introuvable');
        }

        $mime = mime_content_type($filePath) ?: 'video/mp4';
        $size = filesize($filePath);

        return [
            'httpStatus' => null,
            'error' => null,
            'path' => $filePath,
            'mime' => $mime,
            'size' => (int) $size,
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

