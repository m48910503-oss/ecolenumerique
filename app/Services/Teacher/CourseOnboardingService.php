<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\CourseCreateModel;

final class CourseOnboardingService extends Service
{
    private \PDO $pdo;
    private CourseCreateModel $courses;

    public function __construct(\PDO $pdo, CourseCreateModel $courses)
    {
        $this->pdo = $pdo;
        $this->courses = $courses;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, string $method, array $post): array
    {
        $categories = $this->courses->findActiveCategories();
        $errors = [];

        $title = trim((string) ($post['title'] ?? ''));
        $description = trim((string) ($post['description'] ?? ''));
        $categoryIdRaw = trim((string) ($post['category_id'] ?? ''));
        $pace = trim((string) ($post['pace'] ?? 'regular'));
        $initialStep = 'welcome';

        $paceOptions = [
            'ready' => [
                'title' => 'Mon cours est deja pret',
                'subtitle' => 'Vous pourrez l organiser et le mettre en ligne rapidement.',
            ],
            'busy' => [
                'title' => 'Je suis tres occupe en ce moment',
                'subtitle' => 'Vous avancerez progressivement avec un brouillon simple.',
            ],
            'regular' => [
                'title' => 'Je travaille dessus regulierement',
                'subtitle' => 'Un rythme stable est ideal pour structurer le contenu.',
            ],
            'intensive' => [
                'title' => 'Je veux avancer vite',
                'subtitle' => 'Vous pourrez remplir le parcours complet sans attendre.',
            ],
        ];
        if (!isset($paceOptions[$pace])) {
            $pace = 'regular';
        }

        if ($method === 'POST') {
            $initialStep = 'availability';

            if ($title === '') {
                $errors[] = 'Le titre du cours est obligatoire.';
                $initialStep = 'title';
            } elseif ((function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title)) < 4) {
                $errors[] = 'Le titre doit contenir au moins 4 caracteres.';
                $initialStep = 'title';
            }

            if ($description === '' && $initialStep === 'availability') {
                $errors[] = 'La description du cours est obligatoire.';
                $initialStep = 'description';
            } elseif ($description !== '') {
                $descriptionLength = function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description);
                if ($descriptionLength < 40 && $initialStep === 'availability') {
                    $errors[] = 'La description doit contenir au moins 40 caracteres.';
                    $initialStep = 'description';
                }
            }

            $categoryId = null;
            if ($categoryIdRaw !== '') {
                $categoryId = (int) $categoryIdRaw;
                if (!$this->courses->categoryExists($categoryId) && $initialStep === 'availability') {
                    $errors[] = 'Selectionnez une categorie valide.';
                    $initialStep = 'category';
                }
            } elseif ($initialStep === 'availability') {
                $errors[] = 'Selectionnez une categorie pour continuer.';
                $initialStep = 'category';
            }

            if ($errors === []) {
                try {
                    $courseId = $this->courses->createCourse([
                        'teacher_id' => (int) ($user['id'] ?? 0),
                        'category_id' => $categoryId,
                        'title' => $title,
                        'slug' => $this->courses->generateCourseSlug($title),
                        'level' => 'beginner',
                        'description' => $description,
                        'objectives' => '[]',
                        'prerequisites' => '[]',
                        'equipment' => '[]',
                        'duration_hours' => null,
                        'price_xof' => 0,
                        'referral_override' => 0,
                        'referral_l1_rate' => null,
                        'referral_l2_rate' => null,
                        'referral_l2_enabled' => 0,
                        'cover_path' => null,
                        'status' => 'draft',
                        'submitted_at' => null,
                        'enterprise_price_xof' => null,
                        'enterprise_discount_percent' => null,
                    ]);

                    $this->flashSet('success', 'Brouillon cree. Continuez maintenant le parcours complet du cours.');

                    return [
                        'redirectTo' => $this->siteUrl('teacher/course_content?id=' . $courseId . '&step=audience'),
                        'view' => [],
                    ];
                } catch (\Throwable $e) {
                    $errors[] = 'Impossible de creer le brouillon pour le moment.';
                    $initialStep = 'availability';
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'title' => $title,
                'description' => $description,
                'categoryId' => $categoryIdRaw,
                'pace' => $pace,
                'paceOptions' => $paceOptions,
                'categories' => $categories,
                'initialStep' => $initialStep,
                'coursesIndexUrl' => $this->siteUrl('teacher/courses'),
            ],
        ];
    }
}
