<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\TeacherCourseEditModel;
use App\Models\TeacherCourseContentModel;
use App\Services\Drive\DriveGateway;

final class CourseContentService extends Service
{
    private \PDO $pdo;
    private TeacherCourseContentModel $model;

    public function __construct(\PDO $pdo, TeacherCourseContentModel $model)
    {
        $this->pdo = $pdo;
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{
     *   redirectTo:?string,
     *   httpStatus:?int,
     *   abortMessage:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, bool $isAdmin, string $method, array $get, array $post, array $files): array
    {
        $drive = new DriveGateway();
        $courseEditModel = new TeacherCourseEditModel($this->pdo);
        $courseId = (int) ($get['id'] ?? 0);
        $fallbackUrl = $isAdmin ? $this->siteUrl('admin/courses') : $this->siteUrl('teacher/courses');
        if ($courseId < 1) {
            return $this->redirectWithError($fallbackUrl, 'Selectionnez un cours valide pour ouvrir son contenu.');
        }

        $this->schema()->ensureCoursesUpdateFlagColumn();
        $course = $this->model->findCourseById($courseId);
        if (!$course) {
            return $this->redirectWithError($fallbackUrl, "Le cours recherche est introuvable ou n'est plus disponible.");
        }
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) $user['id']) {
            return $this->redirectWithError($fallbackUrl, "Vous n'avez pas acces a ce cours.");
        }

        $wasUpdating = !empty($course['update_in_progress']);
        $isLocked = in_array((string) ($course['status'] ?? ''), ['submitted'], true);
        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'approved' => 'Approuve',
            'rejected' => 'Rejete',
        ];

        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $meetingsHasDuration = $this->schema()->ensureCourseMeetingsDurationColumn();
        $driveUpload = $drive->uploadUiState();
        $driveReady = !empty($driveUpload['ready']);
        $driveNeedsConnect = !empty($driveUpload['needsConnect']);
        $driveRemoteReady = !empty($driveUpload['remoteReady']);
        $driveUploadNote = (string) ($driveUpload['note'] ?? '');
        $driveMaxMb = APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25;

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            $didUpdate = false;
            $updateLabel = '';
            $redirectStep = '';

            if ($isLocked) {
                $errors[] = 'Cours verrouille (soumis) : modifications non autorisees.';
            } else {
                // Each action mutates one content domain and leaves other domains untouched.
                if ($action === 'update_pedagogy') {
                    $objectives = $this->normalizeListField($post['objectives'] ?? []);
                    $prerequisites = $this->normalizeListField($post['prerequisites'] ?? []);
                    $equipment = $this->normalizeListField($post['equipment'] ?? []);

                    if ($objectives === []) {
                        $errors[] = 'Ajoutez au moins un objectif pédagogique.';
                    }

                    if ($errors === []) {
                        try {
                            $objectivesJson = json_encode($objectives, JSON_UNESCAPED_UNICODE);
                            $prerequisitesJson = json_encode($prerequisites, JSON_UNESCAPED_UNICODE);
                            $equipmentJson = json_encode($equipment, JSON_UNESCAPED_UNICODE);
                            $this->model->updateCoursePedagogy(
                                $courseId,
                                is_string($objectivesJson) ? $objectivesJson : '[]',
                                is_string($prerequisitesJson) ? $prerequisitesJson : '[]',
                                is_string($equipmentJson) ? $equipmentJson : '[]'
                            );
                            $course['objectives'] = is_string($objectivesJson) ? $objectivesJson : '[]';
                            $course['prerequisites'] = is_string($prerequisitesJson) ? $prerequisitesJson : '[]';
                            $course['equipment'] = is_string($equipmentJson) ? $equipmentJson : '[]';
                            $success = "Pédagogie enregistrée.";
                            $didUpdate = true;
                            $updateLabel = "Pédagogie modifiée";
                            $redirectStep = 'audience';
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la sauvegarde de la pédagogie.';
                        }
                    }
                }

                if ($action === 'update_course_landing') {
                    $title = trim((string) ($post['course_title'] ?? ''));
                    $description = trim((string) ($post['course_description'] ?? ''));
                    $level = trim((string) ($post['course_level'] ?? 'beginner'));
                    $categoryId = (int) ($post['course_category_id'] ?? 0);
                    $coverAlt = trim((string) ($post['course_cover_alt'] ?? ''));
                    $seoTitle = trim((string) ($post['course_seo_title'] ?? ''));
                    $seoDescription = trim((string) ($post['course_seo_description'] ?? ''));
                    $existingCoverPath = $this->normalizeCoverPath((string) ($course['cover_path'] ?? ''));
                    $coverPath = $existingCoverPath !== '' ? $existingCoverPath : null;
                    $previousCoverPath = $coverPath;

                    $course['title'] = $title;
                    $course['description'] = $description;
                    $course['level'] = $level;
                    $course['category_id'] = $categoryId;
                    $course['cover_alt'] = $coverAlt;
                    $course['seo_title'] = $seoTitle;
                    $course['seo_description'] = $seoDescription;

                    if ($title === '') {
                        $errors[] = 'Le titre du cours est obligatoire.';
                    } elseif ((function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title)) < 4) {
                        $errors[] = 'Le titre doit contenir au moins 4 caracteres.';
                    }

                    if ($description === '') {
                        $errors[] = 'La description du cours est obligatoire.';
                    } else {
                        $descriptionLength = function_exists('mb_strlen') ? mb_strlen($description, 'UTF-8') : strlen($description);
                        if ($descriptionLength < 40) {
                            $errors[] = 'La description doit contenir au moins 40 caracteres.';
                        }
                    }

                    if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
                        $errors[] = 'Le niveau selectionne est invalide.';
                    }

                    if ($categoryId < 1 || !$courseEditModel->categoryExists($categoryId)) {
                        $errors[] = 'Selectionnez une categorie valide.';
                    }

                    if ($coverAlt !== '') {
                        $coverAltLength = function_exists('mb_strlen') ? mb_strlen($coverAlt, 'UTF-8') : strlen($coverAlt);
                        if ($coverAltLength > 180) {
                            $errors[] = 'Le texte alternatif de la miniature ne doit pas depasser 180 caracteres.';
                        }
                    }

                    if ($seoTitle !== '') {
                        $seoTitleLength = function_exists('mb_strlen') ? mb_strlen($seoTitle, 'UTF-8') : strlen($seoTitle);
                        if ($seoTitleLength > 160) {
                            $errors[] = 'Le titre SEO ne doit pas depasser 160 caracteres.';
                        }
                    }

                    if ($seoDescription !== '') {
                        $seoDescriptionLength = function_exists('mb_strlen') ? mb_strlen($seoDescription, 'UTF-8') : strlen($seoDescription);
                        if ($seoDescriptionLength > 255) {
                            $errors[] = 'La meta description ne doit pas depasser 255 caracteres.';
                        }
                    }

                    $coverFile = $files['course_cover_file'] ?? null;
                    if (is_array($coverFile) && (int) ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        if ((int) ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                            $errors[] = 'Erreur lors du televersement de la miniature.';
                        } else {
                            $maxSize = 5 * 1024 * 1024;
                            if ((int) ($coverFile['size'] ?? 0) > $maxSize) {
                                $errors[] = 'La miniature depasse 5 Mo.';
                            } else {
                                $ext = strtolower(pathinfo((string) ($coverFile['name'] ?? ''), PATHINFO_EXTENSION));
                                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                                    $errors[] = 'Le format de miniature doit etre JPG, PNG ou WebP.';
                                } else {
                                    $uploadDir = $this->projectRoot() . '/storage/uploads/covers';
                                    if (!is_dir($uploadDir)) {
                                        mkdir($uploadDir, 0755, true);
                                    }

                                    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                                    $destination = $uploadDir . '/' . $filename;
                                    if (!move_uploaded_file((string) ($coverFile['tmp_name'] ?? ''), $destination)) {
                                        $errors[] = 'Impossible d enregistrer la nouvelle miniature.';
                                    } else {
                                        $coverPath = 'storage/uploads/covers/' . $filename;
                                        $course['cover_path'] = $coverPath;
                                    }
                                }
                            }
                        }
                    }

                    if ($errors === []) {
                        try {
                            $course['cover_path'] = $coverPath;
                            $courseEditModel->updateCourse($courseId, $this->buildCourseUpdatePayload($course));

                            if ($previousCoverPath && $previousCoverPath !== $coverPath) {
                                $this->safeUnlink($previousCoverPath);
                            }

                            $course['cover_path'] = $coverPath;
                            $success = 'Page d accueil mise a jour.';
                            $didUpdate = true;
                            $updateLabel = 'Page d accueil mise a jour';
                            $redirectStep = 'landing';
                        } catch (\Throwable $e) {
                            if ($coverPath && $coverPath !== $previousCoverPath) {
                                $this->safeUnlink($coverPath);
                            }
                            $course['cover_path'] = $previousCoverPath;
                            $errors[] = 'Erreur lors de la mise a jour de la page d accueil.';
                        }
                    } elseif ($coverPath && $coverPath !== $previousCoverPath) {
                        $this->safeUnlink($coverPath);
                        $course['cover_path'] = $previousCoverPath;
                    }
                }

                if ($action === 'update_course_pricing') {
                    $publicPriceRaw = trim((string) ($post['price_xof'] ?? '0'));
                    $enterpriseMode = trim((string) ($post['enterprise_pricing_mode'] ?? 'none'));
                    $enterprisePriceRaw = trim((string) ($post['enterprise_price_xof'] ?? ''));
                    $enterpriseDiscountRaw = str_replace(',', '.', trim((string) ($post['enterprise_discount_percent'] ?? '')));

                    $priceValue = 0;
                    if ($publicPriceRaw !== '') {
                        if (preg_match('/^\d+$/', $publicPriceRaw) !== 1) {
                            $errors[] = 'Le prix public doit etre un entier positif ou nul.';
                        } else {
                            $priceValue = (int) $publicPriceRaw;
                        }
                    }

                    if (!in_array($enterpriseMode, ['none', 'price', 'discount'], true)) {
                        $enterpriseMode = 'none';
                    }

                    $enterprisePriceValue = null;
                    $enterpriseDiscountValue = null;

                    if ($enterpriseMode === 'price') {
                        if ($enterprisePriceRaw === '' || preg_match('/^\d+$/', $enterprisePriceRaw) !== 1) {
                            $errors[] = 'Le prix entreprise doit etre un entier positif.';
                        } else {
                            $enterprisePriceValue = (int) $enterprisePriceRaw;
                            if ($enterprisePriceValue < 1) {
                                $errors[] = 'Le prix entreprise doit etre superieur a 0.';
                            }
                        }
                    } elseif ($enterpriseMode === 'discount') {
                        if ($enterpriseDiscountRaw === '' || !is_numeric($enterpriseDiscountRaw)) {
                            $errors[] = 'La reduction entreprise doit etre numerique.';
                        } else {
                            $enterpriseDiscountValue = round((float) $enterpriseDiscountRaw, 2);
                            if ($enterpriseDiscountValue <= 0 || $enterpriseDiscountValue > 100) {
                                $errors[] = 'La reduction entreprise doit etre comprise entre 0 et 100.';
                            }
                        }
                    }

                    $course['price_xof'] = $priceValue;
                    $course['enterprise_price_xof'] = $enterprisePriceValue;
                    $course['enterprise_discount_percent'] = $enterpriseDiscountValue;

                    if ($errors === []) {
                        try {
                            $courseEditModel->updateCourse($courseId, $this->buildCourseUpdatePayload($course));

                            $success = 'Tarification mise a jour.';
                            $didUpdate = true;
                            $updateLabel = 'Tarification mise a jour';
                            $redirectStep = 'pricing';
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la mise a jour de la tarification.';
                        }
                    }
                }

                if ($action === 'clear_update_notice') {
                    try {
                        $this->model->setCourseUpdateInProgress($courseId, 0);
                        $course['update_in_progress'] = 0;
                        $success = "Mise \u{00e0} jour termin\u{00e9}e.";

                        $courseUrl = !empty($course['slug'])
                            ? $this->absUrl('course?slug=' . (string) $course['slug'])
                            : $this->absUrl('course?id=' . $courseId);
                        notify_course_paid_students(
                            $courseId,
                            "Mise \u{00e0} jour termin\u{00e9}e",
                            "Le cours \"" . ((string) ($course['title'] ?? 'Cours')) . "\" a \u{00e9}t\u{00e9} mis \u{00e0} jour. Vous pouvez consulter les nouveaut\u{00e9}s.",
                            $courseUrl
                        );
                        log_course_audit((int) ($user['id'] ?? 0), $courseId, 'course_update_finished', [
                            'label' => "Mise \u{00e0} jour termin\u{00e9}e",
                            'source' => 'course_content',
                        ]);
                    } catch (\Throwable $e) {
                        $errors[] = "Erreur lors de la mise \u{00e0} jour du statut.";
                    }
                }

                if ($action === 'edit_chapter') {
                    $chapterId = (int) ($post['edit_chapter_id'] ?? 0);
                    $title = trim((string) ($post['edit_chapter_title'] ?? ''));
                    $description = trim((string) ($post['edit_chapter_description'] ?? ''));
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre introuvable.';
                    }
                    if ($title === '') {
                        $errors[] = 'Le titre du chapitre est obligatoire.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->updateChapter($chapterId, $courseId, $title, $description !== '' ? $description : null);
                            $success = "Chapitre modifi\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Chapitre modifi\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la modification du chapitre.';
                        }
                    }
                }

                if ($action === 'delete_chapter') {
                    $chapterId = (int) ($post['delete_chapter_id'] ?? 0);
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre introuvable.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->deleteChapter($chapterId, $courseId);
                            $this->updateCourseDurationFromLessons($courseId);
                            $success = "Chapitre supprim\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Chapitre supprim\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la suppression du chapitre.';
                        }
                    }
                }

                if ($action === 'duplicate_chapter') {
                    $chapterId = (int) ($post['duplicate_chapter_id'] ?? 0);
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre introuvable.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->duplicateChapterWithContent($chapterId, $courseId);
                            $this->updateCourseDurationFromLessons($courseId);
                            $success = "Chapitre dupliqu\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Chapitre dupliqu\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la duplication du chapitre.';
                        }
                    }
                }

                if ($action === 'reorder_chapters') {
                    $orderRaw = trim((string) ($post['chapter_order'] ?? ''));
                    $ordered = array_values(array_filter(array_map('intval', explode(',', $orderRaw)), static fn(int $id): bool => $id > 0));
                    if ($ordered === []) {
                        $errors[] = 'Ordre des chapitres invalide.';
                    } else {
                        try {
                            $this->model->reorderChapters($courseId, $ordered);
                            $success = "Ordre des chapitres mis \u{00e0} jour.";
                            $didUpdate = true;
                            $updateLabel = "Ordre chapitres mis \u{00e0} jour";
                        } catch (\Throwable $e) {
                            $errors[] = "Impossible de r\u{00e9}ordonner les chapitres.";
                        }
                    }
                }

                if ($action === 'add_chapter') {
                    $title = trim((string) ($post['chapter_title'] ?? ''));
                    $description = trim((string) ($post['chapter_description'] ?? ''));
                    if ($title === '') {
                        $errors[] = 'Le titre du chapitre est obligatoire.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->insertChapter(
                                $courseId,
                                $title,
                                $description !== '' ? $description : null,
                                $this->model->nextChapterPosition($courseId)
                            );
                            $success = "Chapitre ajout\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Chapitre ajout\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de l'ajout du chapitre.";
                        }
                    }
                }

                if ($action === 'add_lesson') {
                    $chapterId = (int) ($post['lesson_chapter_id'] ?? 0);
                    if ($chapterId < 1) {
                        $chapterId = $this->resolveDefaultContentChapterId($courseId);
                    }
                    if ($chapterId > 0 && !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $chapterId = $this->resolveDefaultContentChapterId($courseId);
                    }
                    $title = trim((string) ($post['lesson_title'] ?? ''));
                    $description = trim((string) ($post['lesson_description'] ?? ''));
                    $positionInput = (int) ($post['lesson_position'] ?? 0);
                    $duration = (int) ($post['lesson_duration'] ?? 0);
                    $youtubeUrl = trim((string) ($post['youtube_url'] ?? ''));

                    if (!$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide.';
                    }
                    if ($title === '') {
                        $errors[] = "Le titre de la le\u{00e7}on est obligatoire.";
                    }
                    if (!$this->isYoutubeUrl($youtubeUrl)) {
                        $errors[] = 'Le lien YouTube est invalide.';
                    }
                    if ($duration < 1) {
                        $errors[] = "La dur\u{00e9}e de la le\u{00e7}on est obligatoire.";
                    }

                    if ($errors === []) {
                        $position = $positionInput > 0 ? $positionInput : $this->model->nextLessonPosition($chapterId);
                        try {
                            $this->model->insertLesson(
                                $chapterId,
                                $title,
                                $description !== '' ? $description : null,
                                $position,
                                $duration,
                                $youtubeUrl
                            );
                            $this->updateCourseDurationFromLessons($courseId);
                            $success = "Le\u{00e7}on ajout\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Le\u{00e7}on ajout\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de l'ajout de la le\u{00e7}on.";
                        }
                    }
                }

                if ($action === 'edit_lesson') {
                    $lessonId = (int) ($post['edit_lesson_id'] ?? 0);
                    $chapterId = (int) ($post['edit_lesson_chapter_id'] ?? 0);
                    $title = trim((string) ($post['edit_lesson_title'] ?? ''));
                    $description = trim((string) ($post['edit_lesson_description'] ?? ''));
                    $duration = (int) ($post['edit_lesson_duration'] ?? 0);
                    $youtubeUrl = trim((string) ($post['edit_lesson_youtube_url'] ?? ''));

                    if ($lessonId < 1 || !$this->model->findLessonForCourse($lessonId, $courseId)) {
                        $errors[] = "Le\u{00e7}on introuvable.";
                    }
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide.';
                    }
                    if ($title === '') {
                        $errors[] = "Le titre de la le\u{00e7}on est obligatoire.";
                    }
                    if (!$this->isYoutubeUrl($youtubeUrl)) {
                        $errors[] = 'Le lien YouTube est invalide.';
                    }
                    if ($duration < 1) {
                        $errors[] = "La dur\u{00e9}e de la le\u{00e7}on est obligatoire.";
                    }

                    if ($errors === []) {
                        try {
                            $this->model->updateLesson(
                                $lessonId,
                                $courseId,
                                $chapterId,
                                $title,
                                $description !== '' ? $description : null,
                                $duration,
                                $youtubeUrl
                            );
                            $this->updateCourseDurationFromLessons($courseId);
                            $success = "Le\u{00e7}on modifi\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Le\u{00e7}on modifi\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de la modification de la le\u{00e7}on.";
                        }
                    }
                }

                if ($action === 'delete_lesson') {
                    $lessonId = (int) ($post['delete_lesson_id'] ?? 0);
                    if ($lessonId < 1 || !$this->model->findLessonForCourse($lessonId, $courseId)) {
                        $errors[] = "Le\u{00e7}on introuvable.";
                    }
                    if ($errors === []) {
                        try {
                            $this->model->deleteLessonWithLinkedDocuments($lessonId, $courseId);
                            $this->updateCourseDurationFromLessons($courseId);
                            $success = "Le\u{00e7}on supprim\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Le\u{00e7}on supprim\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de la suppression de la le\u{00e7}on.";
                        }
                    }
                }

                if ($action === 'duplicate_lesson') {
                    $lessonId = (int) ($post['duplicate_lesson_id'] ?? 0);
                    if ($lessonId < 1 || !$this->model->findLessonForCourse($lessonId, $courseId)) {
                        $errors[] = "Le\u{00e7}on introuvable.";
                    }
                    if ($errors === []) {
                        try {
                            $this->model->duplicateLessonWithLinkedDocuments($lessonId, $courseId);
                            $this->updateCourseDurationFromLessons($courseId);
                            $success = "Le\u{00e7}on dupliqu\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Le\u{00e7}on dupliqu\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de la duplication de la le\u{00e7}on.";
                        }
                    }
                }

                if ($action === 'reorder_lessons') {
                    $chapterId = (int) ($post['chapter_id'] ?? 0);
                    $orderRaw = trim((string) ($post['lesson_order'] ?? ''));
                    $ordered = array_values(array_filter(array_map('intval', explode(',', $orderRaw)), static fn(int $id): bool => $id > 0));
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide pour le réordonnancement.';
                    } elseif ($ordered === []) {
                        $errors[] = "Ordre des le\u{00e7}ons invalide.";
                    } else {
                        try {
                            $this->model->reorderLessons($courseId, $chapterId, $ordered);
                            $success = "Ordre des le\u{00e7}ons mis \u{00e0} jour.";
                            $didUpdate = true;
                            $updateLabel = "Ordre le\u{00e7}ons mis \u{00e0} jour";
                        } catch (\Throwable $e) {
                            $errors[] = "Impossible de r\u{00e9}ordonner les le\u{00e7}ons.";
                        }
                    }
                }

                if ($action === 'add_doc') {
                    $docType = (string) ($post['doc_type'] ?? 'document');
                    $title = trim((string) ($post['doc_title'] ?? ''));
                    $scope = (string) ($post['doc_scope'] ?? 'chapter');
                    $chapterId = (int) ($post['doc_chapter_id'] ?? 0);
                    if ($chapterId < 1) {
                        $chapterId = $this->findFirstContentChapterId($courseId);
                    }
                    $docLessonId = (int) ($post['doc_lesson_id'] ?? 0);

                    $dbType = 'resource';
                    $docTag = '';
                    if ($docType === 'exercise') {
                        $dbType = 'exercise';
                    } elseif ($docType === 'document') {
                        $dbType = 'resource';
                        $docTag = 'doc:document';
                    } elseif ($docType === 'file') {
                        $dbType = 'resource';
                        $docTag = 'doc:file';
                    } else {
                        $errors[] = 'Type de ressource invalide.';
                    }

                    if ($docType !== 'file') {
                        $scope = 'chapter';
                        $docLessonId = 0;
                    }

                    if ($title === '') {
                        $errors[] = 'Le titre est obligatoire.';
                    }

                    $docFile = is_array($files['doc_file'] ?? null) ? $files['doc_file'] : null;
                    if (!$docFile || (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        $errors[] = 'Le fichier est obligatoire.';
                    }

                    $docFileTmp = '';
                    $docFileName = '';
                    $docFileSize = 0;
                    $docFileMime = 'application/octet-stream';
                    $docShouldUpload = false;

                    if ($docFile && (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $uploadError = (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE);
                        if ($uploadError !== UPLOAD_ERR_OK) {
                            $errors[] = "Erreur d'upload de la ressource (code " . (string) $uploadError . ').';
                        } else {
                            $docFileTmp = (string) ($docFile['tmp_name'] ?? '');
                            $docFileSize = (int) ($docFile['size'] ?? 0);
                            if ($docFileTmp === '' || !is_uploaded_file($docFileTmp)) {
                                $errors[] = 'Le fichier de la ressource est invalide.';
                            }
                            $maxBytes = (int) $driveMaxMb * 1024 * 1024;
                            if ($maxBytes > 0 && $docFileSize > $maxBytes) {
                                $errors[] = "La ressource d\u{00e9}passe " . (string) $driveMaxMb . ' Mo.';
                            }
                            if ($errors === []) {
                                $docFileName = $drive->safeFilename((string) ($docFile['name'] ?? 'ressource'));
                                $docFileMime = $this->detectMimeType($docFileTmp, (string) ($docFile['type'] ?? ''));
                                $docShouldUpload = true;
                            }
                        }
                    }

                    if (!in_array($scope, ['chapter', 'lesson'], true)) {
                        $errors[] = "Port\u{00e9}e invalide.";
                    }

                    if ($chapterId < 1) {
                        $errors[] = "Un chapitre est requis pour l'association.";
                    } elseif (!$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide.';
                    }

                    if ($scope === 'lesson') {
                        if ($docLessonId < 1) {
                            $errors[] = "Une le\u{00e7}on est requise pour l'association.";
                        } elseif ($chapterId > 0 && !$this->model->lessonBelongsToChapterAndCourse($docLessonId, $chapterId, $courseId)) {
                            $errors[] = "Le\u{00e7}on invalide pour ce chapitre.";
                        }
                    }

                    $driveLink = '';
                    if ($errors === [] && $docShouldUpload) {
                        $prefix = 'ressource_' . $courseId . '_' . date('Ymd_His');
                        $driveName = $prefix . '_' . $docFileName;
                        if (strlen($driveName) > 220) {
                            $driveName = substr($driveName, 0, 220);
                        }

                        $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
                        $driveResult = $drive->uploadFile(
                            $docFileTmp,
                            $driveName,
                            $docFileMime !== '' ? $docFileMime : 'application/octet-stream',
                            $parentId,
                            APP_DRIVE_PUBLIC_LINK
                        );
                        if (empty($driveResult['ok'])) {
                            $driveError = $drive->errorMessage($driveResult);
                            $errors[] = 'Upload Drive impossible pour la ressource.' . ($driveError !== '' ? ' ' . $driveError : '');
                        } else {
                            $driveLink = (string) ($driveResult['webViewLink'] ?? '');
                            if ($driveLink === '' && !empty($driveResult['webContentLink'])) {
                                $driveLink = (string) $driveResult['webContentLink'];
                            }
                            if ($driveLink === '' && !empty($driveResult['id'])) {
                                $driveLink = 'https://drive.google.com/file/d/' . (string) $driveResult['id'] . '/view';
                            }
                        }
                    }

                    if ($errors === []) {
                        $mime = $docFileMime !== '' ? $docFileMime : 'application/octet-stream';
                        if ($docTag !== '') {
                            $mime .= '|' . $docTag;
                        }
                        if ($scope === 'lesson' && $docLessonId > 0) {
                            $mime .= '|lesson:' . $docLessonId;
                        }
                        try {
                            $this->model->insertDocument(
                                $chapterId,
                                $title,
                                $dbType,
                                $driveLink,
                                $mime,
                                $docFileSize,
                                $this->model->nextDocumentPosition($chapterId)
                            );
                            $success = "Ressource ajout\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Ressource ajout\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de l'ajout de la ressource.";
                        }
                    }
                }

                if ($action === 'edit_doc') {
                    $docId = (int) ($post['edit_doc_id'] ?? 0);
                    $title = trim((string) ($post['edit_doc_title'] ?? ''));
                    $docType = (string) ($post['edit_doc_type'] ?? 'document');
                    $scope = (string) ($post['edit_doc_scope'] ?? 'chapter');
                    $chapterId = (int) ($post['edit_doc_chapter_id'] ?? 0);
                    $docLessonId = (int) ($post['edit_doc_lesson_id'] ?? 0);

                    $doc = $this->model->findDocumentForCourse($docId, $courseId);
                    if (!$doc) {
                        $errors[] = 'Ressource introuvable.';
                    }
                    if ($title === '') {
                        $errors[] = 'Le titre de la ressource est obligatoire.';
                    }

                    $dbType = 'resource';
                    $docTag = '';
                    if ($docType === 'exercise') {
                        $dbType = 'exercise';
                    } elseif ($docType === 'document') {
                        $dbType = 'resource';
                        $docTag = 'doc:document';
                    } elseif ($docType === 'file') {
                        $dbType = 'resource';
                        $docTag = 'doc:file';
                    } else {
                        $errors[] = 'Type de ressource invalide.';
                    }

                    if ($docType !== 'file') {
                        $scope = 'chapter';
                        $docLessonId = 0;
                    }
                    if (!in_array($scope, ['chapter', 'lesson'], true)) {
                        $errors[] = "Port\u{00e9}e invalide.";
                    }
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide.';
                    }
                    if ($scope === 'lesson') {
                        if ($docLessonId < 1) {
                            $errors[] = "Une le\u{00e7}on est requise pour cette association.";
                        } elseif (!$this->model->lessonBelongsToChapterAndCourse($docLessonId, $chapterId, $courseId)) {
                            $errors[] = "Le\u{00e7}on invalide pour ce chapitre.";
                        }
                    }

                    if ($errors === [] && is_array($doc)) {
                        try {
                            $baseMime = $this->stripDocMetadataTags((string) ($doc['mime'] ?? 'application/octet-stream'));
                            $newMime = $baseMime;
                            if ($docTag !== '') {
                                $newMime .= '|'.$docTag;
                            }
                            if ($scope === 'lesson' && $docLessonId > 0) {
                                $newMime .= '|lesson:' . $docLessonId;
                            }
                            $this->model->updateDocumentRecord($docId, $courseId, $chapterId, $title, $dbType, $newMime);
                            $success = "Ressource modifi\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Ressource modifi\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la modification de la ressource.';
                        }
                    }
                }

                if ($action === 'delete_doc') {
                    $docId = (int) ($post['delete_doc_id'] ?? 0);
                    if ($docId < 1 || !$this->model->findDocumentForCourse($docId, $courseId)) {
                        $errors[] = 'Ressource introuvable.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->deleteDocument($docId, $courseId);
                            $success = "Ressource supprim\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Ressource supprim\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la suppression de la ressource.';
                        }
                    }
                }

                if ($action === 'duplicate_doc') {
                    $docId = (int) ($post['duplicate_doc_id'] ?? 0);
                    if ($docId < 1 || !$this->model->findDocumentForCourse($docId, $courseId)) {
                        $errors[] = 'Ressource introuvable.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->duplicateDocument($docId, $courseId);
                            $success = "Ressource dupliqu\u{00e9}e.";
                            $didUpdate = true;
                            $updateLabel = "Ressource dupliqu\u{00e9}e";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la duplication de la ressource.';
                        }
                    }
                }

                if ($action === 'add_meeting') {
                    $title = trim((string) ($post['meeting_title'] ?? ''));
                    $mode = (string) ($post['meeting_mode'] ?? 'online');
                    $chapterId = (int) ($post['meeting_chapter_id'] ?? 0);
                    $durationMin = (int) ($post['meeting_duration_min'] ?? 0);

                    if ($chapterId < 1) {
                        $chapterId = $this->resolveDefaultContentChapterId($courseId);
                    }
                    if ($chapterId > 0 && !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $chapterId = $this->resolveDefaultContentChapterId($courseId);
                    }

                    if ($title === '') {
                        $errors[] = 'Le titre du rendez-vous est obligatoire.';
                    }
                    if (!in_array($mode, ['online', 'onsite'], true)) {
                        $errors[] = 'Mode de rendez-vous invalide.';
                    }
                    if ($chapterId < 1) {
                        $errors[] = "S\u{00e9}lectionnez un chapitre associ\u{00e9}.";
                    } elseif (!$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide.';
                    }

                    if ($errors === []) {
                        try {
                            if ($meetingsHasDuration) {
                                $this->model->insertMeetingWithDuration($courseId, $chapterId, $title, $mode, $durationMin);
                            } else {
                                $this->model->insertMeetingWithoutDuration($courseId, $chapterId, $title, $mode);
                            }
                            $success = "Rendez-vous ajout\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Rendez-vous ajout\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = "Erreur lors de l'ajout du rendez-vous.";
                        }
                    }
                }

                if ($action === 'edit_meeting') {
                    $meetingId = (int) ($post['edit_meeting_id'] ?? 0);
                    $title = trim((string) ($post['edit_meeting_title'] ?? ''));
                    $mode = (string) ($post['edit_meeting_mode'] ?? 'online');
                    $chapterId = (int) ($post['edit_meeting_chapter_id'] ?? 0);
                    $durationMin = (int) ($post['edit_meeting_duration_min'] ?? 0);

                    if ($meetingId < 1 || !$this->model->findMeetingForCourse($meetingId, $courseId)) {
                        $errors[] = 'Rendez-vous introuvable.';
                    }
                    if ($title === '') {
                        $errors[] = 'Le titre du rendez-vous est obligatoire.';
                    }
                    if (!in_array($mode, ['online', 'onsite'], true)) {
                        $errors[] = 'Mode de rendez-vous invalide.';
                    }
                    if ($chapterId < 1 || !$this->model->chapterBelongsToCourse($chapterId, $courseId)) {
                        $errors[] = 'Chapitre invalide.';
                    }
                    if ($durationMin < 1) {
                        $errors[] = "La dur\u{00e9}e du rendez-vous est obligatoire.";
                    }

                    if ($errors === []) {
                        try {
                            $this->model->updateMeeting($meetingId, $courseId, $chapterId, $title, $mode, $durationMin);
                            $success = "Rendez-vous modifi\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Rendez-vous modifi\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la modification du rendez-vous.';
                        }
                    }
                }

                if ($action === 'delete_meeting') {
                    $meetingId = (int) ($post['delete_meeting_id'] ?? 0);
                    if ($meetingId < 1 || !$this->model->findMeetingForCourse($meetingId, $courseId)) {
                        $errors[] = 'Rendez-vous introuvable.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->deleteMeeting($meetingId, $courseId);
                            $success = "Rendez-vous supprim\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Rendez-vous supprim\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la suppression du rendez-vous.';
                        }
                    }
                }

                if ($action === 'duplicate_meeting') {
                    $meetingId = (int) ($post['duplicate_meeting_id'] ?? 0);
                    if ($meetingId < 1 || !$this->model->findMeetingForCourse($meetingId, $courseId)) {
                        $errors[] = 'Rendez-vous introuvable.';
                    }
                    if ($errors === []) {
                        try {
                            $this->model->duplicateMeeting($meetingId, $courseId);
                            $success = "Rendez-vous dupliqu\u{00e9}.";
                            $didUpdate = true;
                            $updateLabel = "Rendez-vous dupliqu\u{00e9}";
                        } catch (\Throwable $e) {
                            $errors[] = 'Erreur lors de la duplication du rendez-vous.';
                        }
                    }
                }

                if ($action === 'save_draft') {
                    if (!in_array((string) ($course['status'] ?? ''), ['draft', 'rejected'], true)) {
                        $errors[] = 'Ce cours ne peut plus passer en brouillon.';
                    } else {
                        $this->model->updateCourseDraftStatus($courseId);
                        $course['status'] = 'draft';
                        $success = "Cours enregistr\u{00e9} en brouillon.";
                    }
                }

                if ($action === 'submit_course') {
                    if (!in_array((string) ($course['status'] ?? ''), ['draft', 'rejected'], true)) {
                        $errors[] = "Ce cours ne peut plus \u{00ea}tre soumis.";
                    } else {
                        if (count($this->decodeListField($course['objectives'] ?? null)) < 1) {
                            $errors[] = 'Ajoutez au moins un objectif pédagogique avant de soumettre.';
                        }
                        if (trim((string) ($course['description'] ?? '')) === '' || trim((string) ($course['cover_path'] ?? '')) === '') {
                            $errors[] = 'Completez la page d accueil du cours avec une description et une miniature avant de soumettre.';
                        }
                        $counts = $this->model->fetchSubmissionCounts($courseId);
                        if (((int) ($counts['chapters_cnt'] ?? 0)) < 1 || ((int) ($counts['lessons_cnt'] ?? 0)) < 1) {
                            $errors[] = "Ajoutez au moins un chapitre et une le\u{00e7}on avant de soumettre.";
                        }
                        if (((int) ($counts['meetings_cnt'] ?? 0)) < 1) {
                            $errors[] = 'Ajoutez au moins un rendez-vous avant de soumettre.';
                        }
                    }

                    if ($errors === []) {
                        try {
                            $this->pdo->beginTransaction();
                            $this->model->updateCourseSubmittedStatus($courseId);

                            foreach ($this->model->findAdminIds() as $admin) {
                                $this->model->insertNotification(
                                    (int) ($admin['id'] ?? 0),
                                    'Cours soumis',
                                    (string) ($course['title'] ?? '') . " a \u{00e9}t\u{00e9} soumis pour validation.",
                                    $this->absUrl('admin/courses?course_id=' . $courseId)
                                );
                            }

                            $this->pdo->commit();
                            $course['status'] = 'submitted';
                            $success = "Cours soumis, l'admin a \u{00e9}t\u{00e9} notifi\u{00e9}.";
                        } catch (\Throwable $e) {
                            if ($this->pdo->inTransaction()) {
                                $this->pdo->rollBack();
                            }
                            $errors[] = 'Erreur lors de la soumission.';
                        }
                    }
                }
            }

            if ($didUpdate) {
                if ($updateLabel === '') {
                    $updateLabel = $success !== '' ? $success : "Mise \u{00e0} jour du cours";
                }
                if ($redirectStep === '') {
                    $redirectStep = match ($action) {
                        'update_pedagogy' => 'audience',
                        'add_chapter',
                        'edit_chapter',
                        'delete_chapter',
                        'duplicate_chapter',
                        'reorder_chapters',
                        'add_lesson',
                        'edit_lesson',
                        'delete_lesson',
                        'duplicate_lesson',
                        'reorder_lessons' => 'curriculum',
                        'add_doc',
                        'edit_doc',
                        'delete_doc',
                        'duplicate_doc',
                        'add_meeting',
                        'edit_meeting',
                        'delete_meeting',
                        'duplicate_meeting' => 'production',
                        'save_draft',
                        'submit_course',
                        'clear_update_notice' => 'submit',
                        default => $redirectStep,
                    };
                }
                log_course_audit((int) ($user['id'] ?? 0), $courseId, 'course_update', [
                    'label' => $updateLabel,
                    'source' => 'course_content',
                ]);
            }

            // For approved courses, first content mutation starts the "update in progress" banner.
            if ($didUpdate && ((string) ($course['status'] ?? '')) === 'approved' && !$wasUpdating) {
                if ($this->schema()->ensureCoursesUpdateFlagColumn()) {
                    $this->model->setCourseUpdateInProgress($courseId, 1);
                    $course['update_in_progress'] = 1;

                    $courseUrl = !empty($course['slug'])
                        ? $this->absUrl('course?slug=' . (string) $course['slug'])
                        : $this->absUrl('course?id=' . $courseId);
                    notify_course_paid_students(
                        $courseId,
                        "Cours en mise \u{00e0} jour",
                        "Le cours \"" . ((string) ($course['title'] ?? 'Cours')) . "\" est en cours de mise \u{00e0} jour. Certaines le\u{00e7}ons ou ressources peuvent changer.",
                        $courseUrl
                    );
                    log_course_audit((int) ($user['id'] ?? 0), $courseId, 'course_update_started', [
                        'label' => "Mise \u{00e0} jour d\u{00e9}marr\u{00e9}e",
                        'source' => 'course_content',
                    ]);
                }
            }

            // PRG: avoid duplicate form re-submission after browser refresh.
            if ($errors === [] && $success !== '') {
                $this->flashSet('success', $success);
                $redirectUrl = 'teacher/course_content?id=' . $courseId . ($redirectStep !== '' ? '&step=' . rawurlencode($redirectStep) : '');
                return [
                    'redirectTo' => $this->siteUrl($redirectUrl),
                    'httpStatus' => null,
                    'abortMessage' => null,
                    'view' => [],
                ];
            }
        }

        $chapters = $this->model->fetchChaptersByCourse($courseId);
        $lessonsByChapter = [];
        $docsByChapter = [];
        $docsByLesson = [];

        if ($chapters !== []) {
            $chapterIds = array_values(array_map(static fn(array $ch): int => (int) ($ch['id'] ?? 0), $chapters));
            foreach ($this->model->fetchLessonsByChapterIds($chapterIds) as $lesson) {
                $chapterId = (int) ($lesson['chapter_id'] ?? 0);
                if (!isset($lessonsByChapter[$chapterId])) {
                    $lessonsByChapter[$chapterId] = [];
                }
                $lessonsByChapter[$chapterId][] = $lesson;
            }

            foreach ($this->model->fetchDocumentsByChapterIds($chapterIds) as $doc) {
                $lessonId = $this->docLessonIdFromMime((string) ($doc['mime'] ?? ''));
                if ($lessonId > 0) {
                    if (!isset($docsByLesson[$lessonId])) {
                        $docsByLesson[$lessonId] = [];
                    }
                    $docsByLesson[$lessonId][] = $doc;
                } else {
                    $chapterId = (int) ($doc['chapter_id'] ?? 0);
                    if (!isset($docsByChapter[$chapterId])) {
                        $docsByChapter[$chapterId] = [];
                    }
                    $docsByChapter[$chapterId][] = $doc;
                }
            }
        }

        $meetings = $this->model->fetchMeetingsForCourse($courseId);
        $exams = $this->model->fetchExamsForCourse($courseId);
        $lessonsList = $this->model->fetchLessonsListForCourse($courseId);
        $contentStats = $this->model->fetchContentStats($courseId);
        $submissionSummary = $this->buildSubmissionSummary($contentStats, $course);
        $categories = $courseEditModel->findCategories();
        $auditTimeline = $this->model->fetchAuditLogsForCourse($courseId, 24);
        $auditTimeline = array_map(function (array $row): array {
            $meta = [];
            $metaRaw = (string) ($row['meta_json'] ?? '');
            if ($metaRaw !== '') {
                $decoded = json_decode($metaRaw, true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }
            $who = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
            if ($who === '') {
                $who = "Syst\u{00e8}me";
            }
            return [
                'action' => (string) ($row['action'] ?? 'action'),
                'label' => trim((string) ($meta['label'] ?? '')) !== '' ? (string) $meta['label'] : (string) ($row['action'] ?? 'Action'),
                'source' => (string) ($meta['source'] ?? ''),
                'who' => $who,
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $auditTimeline);

        return [
            'redirectTo' => null,
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [
                'courseId' => $courseId,
                'course' => $course,
                'courseStepDefault' => $this->resolveCourseStepDefault((string) ($get['step'] ?? '')),
                'statusLabels' => $statusLabels,
                'errors' => $errors,
                'success' => $success,
                'isLocked' => $isLocked,
                'meetingsHasDuration' => $meetingsHasDuration,
                'driveReady' => $driveReady,
                'driveNeedsConnect' => $driveNeedsConnect,
                'driveConnectRecommended' => $driveNeedsConnect && !$driveRemoteReady,
                'driveUploadNote' => $driveUploadNote,
                'driveMaxMb' => $driveMaxMb,
                'chapters' => $chapters,
                'lessonsByChapter' => $lessonsByChapter,
                'docsByChapter' => $docsByChapter,
                'docsByLesson' => $docsByLesson,
                'meetings' => $meetings,
                'exams' => $exams,
                'lessonsList' => $lessonsList,
                'contentStats' => $contentStats,
                'submissionSummary' => $submissionSummary,
                'categories' => $categories,
                'auditTimeline' => $auditTimeline,
                'docLabel' => static function (?string $type, ?string $mime): string {
                    return self::docLabel($type, $mime);
                },
            ],
        ];
    }

    private function findFirstContentChapterId(int $courseId): int
    {
        foreach ($this->model->fetchChaptersByCourse($courseId) as $chapter) {
            $title = strtolower(trim((string) ($chapter['title'] ?? '')));
            if (in_array($title, ['examens', 'examen'], true)) {
                continue;
            }
            $chapterId = (int) ($chapter['id'] ?? 0);
            if ($chapterId > 0) {
                return $chapterId;
            }
        }

        return 0;
    }

    private function updateCourseDurationFromLessons(int $courseId): void
    {
        $totalMin = $this->model->sumCourseLessonsDurationMin($courseId);
        if ($totalMin < 1) {
            return;
        }
        $this->model->updateCourseDurationHours($courseId, round($totalMin / 60, 2));
    }

    private function isYoutubeUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $url);
    }

    private function docLessonIdFromMime(?string $mime): int
    {
        if (!$mime) {
            return 0;
        }
        if (preg_match('/\|lesson:(\d+)/', $mime, $match)) {
            return (int) $match[1];
        }
        return 0;
    }

    private static function docLabel(?string $type, ?string $mime): string
    {
        if ($type === 'exercise') {
            return 'Exercice';
        }
        if ($type === 'exam') {
            return 'Examen';
        }
        if ($type === 'resource') {
            if (is_string($mime) && (stripos($mime, '|doc:document') !== false || stripos($mime, 'url_document') !== false)) {
                return "Document d'accompagnement";
            }
            if (is_string($mime) && (stripos($mime, '|doc:file') !== false || stripos($mime, 'url_file') !== false)) {
                return 'Fichier';
            }
            return 'Ressource';
        }
        return 'Ressource';
    }

    private function resolveCourseStepDefault(string $step): string
    {
        $step = trim($step);
        $aliases = [
            'pedagogy' => 'audience',
            'content' => 'curriculum',
            'settings' => 'production',
            'publish' => 'submit',
        ];
        if (isset($aliases[$step])) {
            $step = $aliases[$step];
        }

        $validSteps = [
            'audience',
            'config',
            'production',
            'curriculum',
            'quizzes',
            'subtitles',
            'dubbing',
            'landing',
            'pricing',
            'promotions',
            'messages',
            'submit',
        ];

        return in_array($step, $validSteps, true) ? $step : 'audience';
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function decodeListField(mixed $value): array
    {
        if (is_array($value)) {
            return $this->normalizeListField($value);
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $this->normalizeListField($decoded) : [];
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function normalizeListField(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item === '') {
                continue;
            }
            $items[] = function_exists('mb_substr') ? mb_substr($item, 0, 180, 'UTF-8') : substr($item, 0, 180);
        }

        return array_values(array_slice(array_unique($items), 0, 6));
    }

    private function stripDocMetadataTags(string $mime): string
    {
        $normalized = preg_replace('/\|doc:(document|file)/i', '', $mime) ?? $mime;
        $normalized = preg_replace('/\|lesson:\d+/i', '', $normalized) ?? $normalized;
        $normalized = trim((string) $normalized);
        return $normalized !== '' ? $normalized : 'application/octet-stream';
    }

    /**
     * @param array<string,mixed> $stats
     * @return array{
     *   progress_pct:int,
     *   required_ok:bool,
     *   missing_required:array<int,string>,
     *   checklist:array<int,array{key:string,label:string,done:bool,required:bool,count:int}>
     * }
     */
    private function buildSubmissionSummary(array $stats, array $course): array
    {
        $chaptersCnt = (int) ($stats['chapters_cnt'] ?? 0);
        $lessonsCnt = (int) ($stats['lessons_cnt'] ?? 0);
        $resourcesCnt = (int) ($stats['resources_cnt'] ?? 0);
        $meetingsCnt = (int) ($stats['meetings_cnt'] ?? 0);
        $examDocsCnt = (int) ($stats['exam_docs_cnt'] ?? 0);
        $advExamsCnt = (int) ($stats['adv_exams_cnt'] ?? 0);
        $examsTotal = $examDocsCnt + $advExamsCnt;
        $objectivesCnt = count($this->decodeListField($course['objectives'] ?? null));
        $hasCover = trim((string) ($course['cover_path'] ?? '')) !== '';
        $hasDescription = trim((string) ($course['description'] ?? '')) !== '';

        $checklist = [
            ['key' => 'pedagogy', 'label' => 'Au moins 1 objectif pédagogique', 'done' => $objectivesCnt > 0, 'required' => true, 'count' => $objectivesCnt],
            ['key' => 'landing', 'label' => 'Titre, description et miniature', 'done' => $hasCover && $hasDescription, 'required' => true, 'count' => ($hasCover ? 1 : 0) + ($hasDescription ? 1 : 0)],
            ['key' => 'chapters', 'label' => 'Au moins 1 chapitre', 'done' => $chaptersCnt > 0, 'required' => true, 'count' => $chaptersCnt],
            ['key' => 'lessons', 'label' => "Au moins 1 le\u{00e7}on", 'done' => $lessonsCnt > 0, 'required' => true, 'count' => $lessonsCnt],
            ['key' => 'meetings', 'label' => 'Au moins 1 rendez-vous', 'done' => $meetingsCnt > 0, 'required' => true, 'count' => $meetingsCnt],
            ['key' => 'resources', 'label' => 'Ressources du cours', 'done' => $resourcesCnt > 0, 'required' => false, 'count' => $resourcesCnt],
            ['key' => 'exams', 'label' => 'Evaluation (examen conseille)', 'done' => $examsTotal > 0, 'required' => false, 'count' => $examsTotal],
        ];

        $completed = 0;
        foreach ($checklist as $item) {
            if (!empty($item['done'])) {
                $completed++;
            }
        }
        $total = count($checklist);
        $progressPct = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        $missingRequired = [];
        foreach ($checklist as $item) {
            if (!empty($item['required']) && empty($item['done'])) {
                $missingRequired[] = (string) $item['label'];
            }
        }

        return [
            'progress_pct' => $progressPct,
            'required_ok' => $missingRequired === [],
            'missing_required' => $missingRequired,
            'checklist' => $checklist,
        ];
    }

    /**
     * @param array<string,mixed> $course
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function buildCourseUpdatePayload(array $course, array $overrides = []): array
    {
        $payload = [
            ':category_id' => (int) ($course['category_id'] ?? 0),
            ':title' => (string) ($course['title'] ?? ''),
            ':level' => (string) ($course['level'] ?? 'beginner'),
            ':description' => (string) ($course['description'] ?? ''),
            ':objectives' => (string) ($course['objectives'] ?? '[]'),
            ':prerequisites' => (string) ($course['prerequisites'] ?? '[]'),
            ':equipment' => (string) ($course['equipment'] ?? '[]'),
            ':duration_hours' => ($course['duration_hours'] ?? null) !== null ? (float) $course['duration_hours'] : null,
            ':price_xof' => (int) ($course['price_xof'] ?? 0),
            ':enterprise_price_xof' => isset($course['enterprise_price_xof']) && $course['enterprise_price_xof'] !== null ? (int) $course['enterprise_price_xof'] : null,
            ':enterprise_discount_percent' => isset($course['enterprise_discount_percent']) && $course['enterprise_discount_percent'] !== null ? (float) $course['enterprise_discount_percent'] : null,
            ':referral_override' => (int) ($course['referral_override'] ?? 0),
            ':referral_l1_rate' => isset($course['referral_l1_rate']) && $course['referral_l1_rate'] !== null ? (float) $course['referral_l1_rate'] : null,
            ':referral_l2_rate' => isset($course['referral_l2_rate']) && $course['referral_l2_rate'] !== null ? (float) $course['referral_l2_rate'] : null,
            ':referral_l2_enabled' => (int) ($course['referral_l2_enabled'] ?? 0),
            ':general_file_path' => ($course['general_file_path'] ?? null) !== null ? (string) $course['general_file_path'] : null,
            ':general_file_mime' => ($course['general_file_mime'] ?? null) !== null ? (string) $course['general_file_mime'] : null,
            ':general_file_size' => ($course['general_file_size'] ?? null) !== null ? (int) $course['general_file_size'] : null,
            ':cover_path' => ($course['cover_path'] ?? null) !== null ? (string) $course['cover_path'] : null,
            ':cover_alt' => trim((string) ($course['cover_alt'] ?? '')) !== '' ? trim((string) $course['cover_alt']) : null,
            ':seo_title' => trim((string) ($course['seo_title'] ?? '')) !== '' ? trim((string) $course['seo_title']) : null,
            ':seo_description' => trim((string) ($course['seo_description'] ?? '')) !== '' ? trim((string) $course['seo_description']) : null,
        ];

        return array_replace($payload, $overrides);
    }

    private function detectMimeType(string $tmpName, string $fallbackMime): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    return $detected;
                }
            }
        }
        if ($fallbackMime !== '') {
            return $fallbackMime;
        }
        return 'application/octet-stream';
    }

    private function normalizeCoverPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || strpos($path, '..') !== false) {
            return '';
        }
        if (strpos($path, 'storage/uploads/covers/') !== 0) {
            return '';
        }

        $full = $this->projectRoot() . '/' . $path;
        return is_file($full) ? $path : '';
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private function safeUnlink(string $relativePath): void
    {
        $full = $this->projectRoot() . '/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function resolveDefaultContentChapterId(int $courseId): int
    {
        foreach ($this->model->fetchChaptersByCourse($courseId) as $chapter) {
            $title = strtolower(trim((string) ($chapter['title'] ?? '')));
            if (in_array($title, ['examen', 'examens'], true)) {
                continue;
            }
            $chapterId = (int) ($chapter['id'] ?? 0);
            if ($chapterId > 0) {
                return $chapterId;
            }
        }

        return 0;
    }

    /**
     * @return array{
     *   redirectTo:?string,
     *   httpStatus:int,
     *   abortMessage:string,
     *   view:array<string,mixed>
     * }
     */
    private function abort(int $status, string $message): array
    {
        return [
            'redirectTo' => null,
            'httpStatus' => $status,
            'abortMessage' => $message,
            'view' => [],
        ];
    }

    /**
     * @return array{
     *   redirectTo:string,
     *   httpStatus:null,
     *   abortMessage:null,
     *   view:array<string,mixed>
     * }
     */
    private function redirectWithError(string $redirectTo, string $message): array
    {
        $existingErrors = $this->flashGet('errors', []);
        $errors = is_array($existingErrors) ? $existingErrors : [];
        $errors[] = $message;
        $errors = array_values(array_unique(array_filter(array_map(
            static fn($error): string => trim((string) $error),
            $errors
        ))));

        $this->flashSet('errors', $errors);

        return [
            'redirectTo' => $redirectTo,
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [],
        ];
    }
}
