<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;
use App\Models\WebCourseModel;

final class CoursePreviewOpenService extends Service
{
    private WebCourseModel $model;
    private PreviewTokenService $tokens;

    public function __construct(WebCourseModel $model, ?PreviewTokenService $tokens = null)
    {
        $this->model = $model;
        $this->tokens = $tokens ?? new PreviewTokenService();
    }

    /**
     * @param array<string,mixed>|null $currentUser
     * @return array{httpStatus:?int,errorTitle:?string,error:?string,redirectTo:?string}
     */
    public function handle(?array $currentUser, int $courseId, string $token): array
    {
        if ($courseId < 1 || trim($token) === '') {
            return $this->abort(
                400,
                'Lien d\'apercu invalide',
                "Les informations necessaires pour ouvrir cet apercu sont incompletes."
            );
        }

        $viewerKey = $this->buildViewerKey($currentUser);
        if ($viewerKey === '') {
            return $this->abort(
                403,
                'Acces refuse',
                "Vous ne pouvez pas ouvrir cet apercu depuis cette session."
            );
        }

        $verified = $this->tokens->verifyCoursePreviewToken($courseId, $viewerKey, $token);
        if (!$verified) {
            return $this->abort(
                403,
                'Lien d\'apercu expire',
                "Ce lien d'apercu n'est plus valide. Revenez sur la page du cours pour en generer un nouveau."
            );
        }

        $course = $this->model->findApprovedCourseById($courseId);
        if (!$course) {
            return $this->abort(404, 'Cours indisponible', "Le cours associe a cet apercu n'est plus accessible.");
        }

        $firstVideo = $this->model->findFirstVideoLessonForCourse($courseId);
        if (!$firstVideo) {
            return $this->abort(404, 'Apercu indisponible', "Aucune video d'apercu n'est disponible pour ce cours.");
        }

        $expectedLessonId = (int) ($firstVideo['lesson_id'] ?? 0);
        if ((int) ($verified['lesson_id'] ?? 0) !== $expectedLessonId || $expectedLessonId < 1) {
            return $this->abort(403, 'Lien d\'apercu invalide', "Le jeton d'acces de cet apercu n'est plus valide.");
        }

        $embedUrl = $this->tokens->buildYoutubePreviewEmbedUrl(
            (string) ($firstVideo['youtube_url'] ?? ''),
            $this->absUrl('')
        );
        if (!$embedUrl) {
            return $this->abort(404, 'Apercu indisponible', "La video d'apercu ne peut pas etre ouverte pour le moment.");
        }

        return [
            'httpStatus' => null,
            'errorTitle' => null,
            'error' => null,
            'redirectTo' => $embedUrl,
        ];
    }

    /**
     * @param array<string,mixed>|null $currentUser
     */
    private function buildViewerKey(?array $currentUser): string
    {
        if ($currentUser && !empty($currentUser['id'])) {
            return 'u:' . (int) $currentUser['id'];
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $token = trim((string) ($_SESSION['course_viewer_token'] ?? ''));
        if ($token === '' || strlen($token) < 16) {
            try {
                $token = bin2hex(random_bytes(24));
            } catch (\Throwable $e) {
                $token = session_id() !== '' ? session_id() : uniqid('course_viewer_', true);
            }
            $_SESSION['course_viewer_token'] = $token;
        }
        return 'g:' . hash('sha256', $token);
    }

    /**
     * @return array{httpStatus:int,errorTitle:string,error:string,redirectTo:null}
     */
    private function abort(int $status, string $title, string $error): array
    {
        return [
            'httpStatus' => $status,
            'errorTitle' => $title,
            'error' => $error,
            'redirectTo' => null,
        ];
    }
}
