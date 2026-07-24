<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\WebShareModel;
use App\Services\Shared\PromotionsService;

final class ShareService extends Service
{
    private WebShareModel $model;
    private PromotionsService $promotions;

    public function __construct(WebShareModel $model, PromotionsService $promotions)
    {
        $this->model = $model;
        $this->promotions = $promotions;
    }

    /**
     * @return array{httpStatus:?int,abortTitle:?string,abortMessage:?string,redirect:?string}
     */
    public function handle(string $token, string $refCode, string $courseRef): array
    {
        if ($token === '' || strlen($token) > 140 || preg_match('/^[A-Za-z0-9@_-]+$/', $token) !== 1) {
            return [
                'httpStatus' => 404,
                'abortTitle' => 'Lien de partage invalide',
                'abortMessage' => "Ce lien de partage est incomplet, invalide ou n'est plus reconnu.",
                'redirect' => null,
            ];
        }

        $this->promotions->ensureCoursesShareTokenColumn();
        $course = $this->model->findApprovedCourseByToken($token);
        if (!$course) {
            return [
                'httpStatus' => 404,
                'abortTitle' => 'Lien de partage indisponible',
                'abortMessage' => "Le cours associe a ce lien n'est plus accessible publiquement.",
                'redirect' => null,
            ];
        }

        $query = [];
        if ($refCode !== '') {
            $query['ref'] = $refCode;
        }
        if ($courseRef !== '') {
            $query['course_ref'] = $courseRef;
        }

        $destination = !empty($course['slug'])
            ? 'course?slug=' . (string) $course['slug']
            : 'course?id=' . (int) $course['id'];
        if ($query !== []) {
            $destination .= (str_contains($destination, '?') ? '&' : '?') . http_build_query($query);
        }

        return [
            'httpStatus' => null,
            'abortTitle' => null,
            'abortMessage' => null,
            'redirect' => $this->siteUrl($destination),
        ];
    }
}

