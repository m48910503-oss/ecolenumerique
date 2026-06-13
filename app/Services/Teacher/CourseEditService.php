<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\TeacherCourseEditModel;
use App\Services\Drive\DriveGateway;
use App\Services\Shared\PromotionsService;
use App\Services\Shared\ReferralService;

final class CourseEditService extends Service
{
    private \PDO $pdo;
    private TeacherCourseEditModel $model;
    private PromotionsService $promotions;
    private ReferralService $referrals;
    private DriveGateway $drive;

    public function __construct(
        \PDO $pdo,
        TeacherCourseEditModel $model,
        PromotionsService $promotions,
        ReferralService $referrals,
        ?DriveGateway $drive = null
    )
    {
        $this->pdo = $pdo;
        $this->model = $model;
        $this->promotions = $promotions;
        $this->referrals = $referrals;
        $this->drive = $drive ?? new DriveGateway();
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{
     *   redirectTo:?string,
     *   fatal:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, bool $isAdmin, array $get, array $post, array $files, string $method): array
    {
        $courseId = (int) ($get['id'] ?? 0);
        if ($courseId < 1) {
            return ['redirectTo' => null, 'fatal' => 'Cours introuvable.', 'view' => []];
        }

        $this->schema()->ensureCoursesUpdateFlagColumn();
        $this->promotions->ensureCoursesReferralSettingsColumns();

        $course = $this->model->findCourseById($courseId);
        if (!$course) {
            return ['redirectTo' => null, 'fatal' => 'Cours introuvable.', 'view' => []];
        }
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            return ['redirectTo' => null, 'fatal' => "Acc\u{00e8}s refus\u{00e9}.", 'view' => []];
        }

        $statusLabels = [
            'draft' => 'Brouillon',
            'submitted' => 'Soumis',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
        ];
        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $isLocked = in_array((string) ($course['status'] ?? ''), ['submitted'], true);
        $wasUpdating = !empty($course['update_in_progress']);
        $meetingsHasDuration = $this->schema()->ensureCourseMeetingsDurationColumn();
        $driveUpload = $this->drive->uploadUiState();
        $driveReady = !empty($driveUpload['ready']);
        $driveNeedsConnect = !empty($driveUpload['needsConnect']);
        $driveRemoteReady = !empty($driveUpload['remoteReady']);
        $driveUploadNote = (string) ($driveUpload['note'] ?? '');
        $driveMaxMb = APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25;
        $platformReferral = $this->referrals->getPlatformReferralSettings();

        $state = [
            'courseId' => $courseId,
            'course' => $course,
            'errors' => $errors,
            'success' => $success,
            'isLocked' => $isLocked,
            'wasUpdating' => $wasUpdating,
            'driveReady' => $driveReady,
            'driveNeedsConnect' => $driveNeedsConnect,
            'driveConnectRecommended' => $driveNeedsConnect && !$driveRemoteReady,
            'driveUploadNote' => $driveUploadNote,
            'driveMaxMb' => $driveMaxMb,
            'platformReferral' => $platformReferral,
            'meetingsHasDuration' => $meetingsHasDuration,
            'didUpdate' => false,
            'updateLabel' => '',
            'redirectAfterPost' => true,
        ];

        $state['title'] = trim((string) ($post['title'] ?? $course['title']));
        $state['categoryId'] = $post['category_id'] ?? ($course['category_id'] ?? '');
        $state['level'] = $post['level'] ?? $course['level'];
        $state['description'] = trim((string) ($post['description'] ?? ($course['description'] ?? '')));
        // Nettoie les valeurs existantes (évite d'afficher une ligne vide "required"
        // quand le JSON stocké contient des entrées vides).
        $state['objectives'] = self::normalizeListField(
            $post['objectives'] ?? (json_decode((string) ($course['objectives'] ?? '[]'), true) ?: [])
        );
        $state['prerequisites'] = self::normalizeListField(
            $post['prerequisites'] ?? (json_decode((string) ($course['prerequisites'] ?? '[]'), true) ?: [])
        );
        $state['equipment'] = self::normalizeListField(
            $post['equipment'] ?? (json_decode((string) ($course['equipment'] ?? '[]'), true) ?: [])
        );
        $state['durationHours'] = $post['duration_hours'] ?? ($course['duration_hours'] ?? '');
        $state['priceXof'] = $post['price_xof'] ?? ($course['price_xof'] ?? '');
        $state['enterprisePriceXof'] = $post['enterprise_price_xof'] ?? ($course['enterprise_price_xof'] ?? '');
        $state['enterpriseDiscountPercent'] = $post['enterprise_discount_percent'] ?? ($course['enterprise_discount_percent'] ?? '');
        $state['generalFilePath'] = (string) ($course['general_file_path'] ?? '');
        $state['generalFileMime'] = $course['general_file_mime'] ?? null;
        $state['generalFileSize'] = $course['general_file_size'] ?? null;
        $state['referralOverride'] = isset($post['referral_override']) ? 1 : (int) ($course['referral_override'] ?? 0);
        $state['referralL2Enabled'] = isset($post['referral_l2_enabled']) ? 1 : (int) ($course['referral_l2_enabled'] ?? 0);

        // Durée calculée automatiquement à partir des leçons (pas d'édition manuelle).
        $totalMin = $this->model->sumLessonDurationMinutes($courseId);
        $state['durationHours'] = $totalMin > 0 ? round($totalMin / 60, 2) : 0;

        if ($method === 'POST') {
            $this->handlePost($state, $post, $files, $user);
            if (empty($state['errors']) && (string) $state['success'] !== '' && !empty($state['redirectAfterPost'])) {
                $this->flashSet('success', (string) $state['success']);
                return [
                    'redirectTo' => $this->siteUrl('teacher/course_edit?id=' . $courseId),
                    'fatal' => null,
                    'view' => [],
                ];
            }
        }

        $course = $this->model->findCourseById($courseId) ?: $course;
        $state['course'] = $course;
        $state['isLocked'] = in_array((string) ($course['status'] ?? ''), ['submitted'], true);

        $courseHistory = $this->model->fetchCourseHistory($courseId);
        $chapters = $this->model->fetchChapters($courseId);
        $lessonsList = $this->model->fetchLessonsList($courseId);
        $lessonsByChapter = [];
        $docsByChapter = [];
        $docsByLesson = [];

        if ($chapters !== []) {
            $chapterIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $chapters);
            $chapterIds = array_values(array_filter($chapterIds, static fn(int $id): bool => $id > 0));
            foreach ($this->model->fetchLessonsByChapterIds($chapterIds) as $lesson) {
                $lessonsByChapter[(int) ($lesson['chapter_id'] ?? 0)][] = $lesson;
            }
            foreach ($this->model->fetchDocsByChapterIds($chapterIds) as $doc) {
                $lessonId = self::docLessonIdFromMime((string) ($doc['mime'] ?? ''));
                if ($lessonId > 0) {
                    $docsByLesson[$lessonId][] = $doc;
                } else {
                    $docsByChapter[(int) ($doc['chapter_id'] ?? 0)][] = $doc;
                }
            }
        }

        $categories = $this->model->findCategories();
        $meetings = $this->model->fetchMeetingTemplates($courseId);
        $advExam = $this->model->fetchLatestAdvancedExam($courseId);

        return [
            'redirectTo' => null,
            'fatal' => null,
            'view' => [
                'courseId' => $courseId,
                'course' => $course,
                'statusLabels' => $statusLabels,
                'categories' => $categories,
                'errors' => $state['errors'],
                'success' => $state['success'],
                'isLocked' => $state['isLocked'],
                'title' => $state['title'],
                'categoryId' => $state['categoryId'],
                'categoryDraftName' => $state['categoryDraftName'],
                'level' => $state['level'],
                'description' => $state['description'],
                'objectives' => $state['objectives'],
                'prerequisites' => $state['prerequisites'],
                'equipment' => $state['equipment'],
                'durationHours' => $state['durationHours'],
                'priceXof' => $state['priceXof'],
                'enterprisePriceXof' => $state['enterprisePriceXof'],
                'enterpriseDiscountPercent' => $state['enterpriseDiscountPercent'],
                'generalFilePath' => $state['generalFilePath'],
                'generalFileMime' => $state['generalFileMime'],
                'generalFileSize' => $state['generalFileSize'],
                'referralOverride' => $state['referralOverride'],
                'referralL2Enabled' => $state['referralL2Enabled'],
                'platformReferral' => $platformReferral,
                'driveReady' => $driveReady,
                'driveNeedsConnect' => $driveNeedsConnect,
                'driveConnectRecommended' => $driveNeedsConnect && !$driveRemoteReady,
                'driveUploadNote' => $driveUploadNote,
                'driveMaxMb' => $driveMaxMb,
                'courseHistory' => $courseHistory,
                'chapters' => $chapters,
                'lessonsList' => $lessonsList,
                'lessonsByChapter' => $lessonsByChapter,
                'docsByChapter' => $docsByChapter,
                'docsByLesson' => $docsByLesson,
                'meetings' => $meetings,
                'advExam' => $advExam,
                'isSystemChapterTitle' => static function (string $title): bool {
                    return self::isSystemChapterTitle($title);
                },
                'docLessonIdFromMime' => static function (?string $mime): int {
                    return self::docLessonIdFromMime($mime);
                },
                'docLabel' => static function (?string $type, ?string $mime): string {
                    return self::docLabel($type, $mime);
                },
            ],
        ];
    }

    /**
     * @param array<string,mixed> $state
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @param array<string,mixed> $user
     */
    private function handlePost(array &$state, array $post, array $files, array $user): void
    {
        if (!empty($state['isLocked'])) {
            $state['errors'][] = 'Cours verrouillé (soumis) : modifications non autorisées.';
            return;
        }

        $action = (string) ($post['action'] ?? '');
        if ($action === 'clear_update_notice') {
            $this->handleClearUpdateNotice($state, $user);
        } elseif ($action === 'update_course') {
            $this->handleUpdateCourse($state, $post, $files);
        } elseif ($action === 'add_chapter') {
            $this->handleAddChapter($state, $post);
        } elseif ($action === 'add_lesson') {
            $this->handleAddLesson($state, $post);
        } elseif ($action === 'add_doc') {
            $this->handleAddDoc($state, $post, $files);
        } elseif ($action === 'add_meeting') {
            $this->handleAddMeeting($state, $post);
        } elseif ($action === 'update_chapter') {
            $this->handleUpdateChapter($state, $post);
        } elseif ($action === 'delete_chapter') {
            $this->handleDeleteChapter($state, $post);
        } elseif ($action === 'update_lesson') {
            $this->handleUpdateLesson($state, $post);
        } elseif ($action === 'delete_lesson') {
            $this->handleDeleteLesson($state, $post);
        } elseif ($action === 'update_doc') {
            $this->handleUpdateDoc($state, $post, $files);
        } elseif ($action === 'delete_doc') {
            $this->handleDeleteDoc($state, $post);
        } elseif ($action === 'update_meeting') {
            $this->handleUpdateMeeting($state, $post);
        } elseif ($action === 'delete_meeting') {
            $this->handleDeleteMeeting($state, $post);
        } elseif ($action === 'save_draft') {
            $this->handleSaveDraft($state);
        } elseif ($action === 'submit_course') {
            $this->handleSubmitCourse($state);
        }

        if (!empty($state['didUpdate'])) {
            $label = (string) ($state['updateLabel'] ?? '');
            if ($label === '') {
                $label = (string) ($state['success'] ?? "Mise \u{00e0} jour du cours");
            }
            log_course_audit($user['id'] ?? null, (int) $state['courseId'], 'course_update', [
                'label' => $label,
                'source' => 'course_edit',
            ]);
        }

        $course = (array) $state['course'];
        if (!empty($state['didUpdate']) && (string) ($course['status'] ?? '') === 'approved' && empty($state['wasUpdating'])) {
            if ($this->schema()->ensureCoursesUpdateFlagColumn()) {
                $this->model->setUpdateInProgress((int) $state['courseId']);
                $courseUrl = !empty($course['slug'])
                    ? $this->absUrl('course?slug=' . $course['slug'])
                    : $this->absUrl('course?id=' . (int) $state['courseId']);
                notify_course_paid_students(
                    (int) $state['courseId'],
                    "Cours en mise \u{00e0} jour",
                    "Le cours \"" . ($course['title'] ?? 'Cours') . "\" est en cours de mise \u{00e0} jour. Certaines le\u{00e7}ons ou ressources peuvent changer.",
                    $courseUrl
                );
                log_course_audit($user['id'] ?? null, (int) $state['courseId'], 'course_update_started', [
                    'label' => "Mise \u{00e0} jour d\u{00e9}marr\u{00e9}e",
                    'source' => 'course_edit',
                ]);
            }
        }
    }

    // Action handlers are implemented below to keep the controller thin.
    // They directly mutate $state['errors'], $state['success'] and $state['didUpdate'].
    private function handleClearUpdateNotice(array &$state, array $user): void
    {
        try {
            $this->model->clearUpdateNotice((int) $state['courseId']);
            $state['success'] = "Mise \u{00e0} jour termin\u{00e9}e.";
            $course = (array) $state['course'];
            $courseUrl = !empty($course['slug'])
                ? $this->absUrl('course?slug=' . $course['slug'])
                : $this->absUrl('course?id=' . (int) $state['courseId']);
            notify_course_paid_students(
                (int) $state['courseId'],
                "Mise \u{00e0} jour termin\u{00e9}e",
                "Le cours \"" . ($course['title'] ?? 'Cours') . "\" a \u{00e9}t\u{00e9} mis \u{00e0} jour. Vous pouvez consulter les nouveaut\u{00e9}s.",
                $courseUrl
            );
            log_course_audit($user['id'] ?? null, (int) $state['courseId'], 'course_update_finished', [
                'label' => "Mise \u{00e0} jour termin\u{00e9}e",
                'source' => 'course_edit',
            ]);
        } catch (\Throwable $e) {
            $state['errors'][] = "Erreur lors de la mise \u{00e0} jour du statut.";
        }
    }

    private function handleUpdateCourse(array &$state, array $post, array $files): void
    {
        $courseId = (int) $state['courseId'];
        $course = (array) $state['course'];

        $title = (string) $state['title'];
        $description = (string) $state['description'];
        $level = (string) $state['level'];
        $categoryId = $state['categoryId'];
        // Durée: dérivée des leçons (ignore toute saisie manuelle).
        $durationHours = $state['durationHours'];
        $priceXof = $state['priceXof'];
        $enterprisePriceXof = (string) ($state['enterprisePriceXof'] ?? '');
        $enterpriseDiscountPercent = (string) ($state['enterpriseDiscountPercent'] ?? '');

        if ($title === '') {
            $state['errors'][] = 'Le titre est obligatoire.';
        }
        if ($description === '') {
            $state['errors'][] = 'La description est obligatoire.';
        }
        if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
            $state['errors'][] = 'Niveau invalide.';
        }

        $categoryIdInt = null;
        if ($categoryId === '') {
            $categoryIdInt = null;
        } elseif ($categoryId === 'other') {
            $categoryIdInt = null;
        } else {
            $categoryIdInt = (int) $categoryId;
            if (!$this->model->categoryExists($categoryIdInt)) {
                $state['errors'][] = "Cat\u{00e9}gorie inconnue.";
            }
        }

        $durationValue = (float) $durationHours;
        if ($durationValue < 0) {
            $durationValue = 0.0;
        }

        $priceValue = null;
        $priceRaw = trim($priceXof);
        if ($priceRaw === '') {
            $state['errors'][] = 'Le prix est obligatoire.';
        } else {
            if (preg_match('/^\d+$/', $priceRaw) !== 1) {
                $state['errors'][] = "Le prix doit \u{00ea}tre un entier positif ou nul.";
            } else {
                $priceValue = (int) $priceRaw;
            }
        }

        $enterprisePriceValue = null;
        $enterpriseDiscountValue = null;
        $enterprisePriceRaw = trim($enterprisePriceXof);
        $enterpriseDiscountRaw = str_replace(',', '.', trim($enterpriseDiscountPercent));
        if ($enterprisePriceRaw !== '' && $enterpriseDiscountRaw !== '') {
            $state['errors'][] = "Choisissez soit un prix entreprise, soit une r\u{00e9}duction entreprise.";
        } elseif ($enterprisePriceRaw !== '') {
            if (preg_match('/^\d+$/', $enterprisePriceRaw) !== 1) {
                $state['errors'][] = "Le prix entreprise doit \u{00ea}tre un entier positif.";
            } else {
                $enterprisePriceValue = (int) $enterprisePriceRaw;
                if ($enterprisePriceValue < 1) {
                    $state['errors'][] = "Le prix entreprise doit \u{00ea}tre sup\u{00e9}rieur \u{00e0} 0.";
                }
            }
        } elseif ($enterpriseDiscountRaw !== '') {
            if (!is_numeric($enterpriseDiscountRaw)) {
                $state['errors'][] = "La r\u{00e9}duction entreprise doit \u{00ea}tre num\u{00e9}rique.";
            } else {
                $enterpriseDiscountValue = (float) $enterpriseDiscountRaw;
                if ($enterpriseDiscountValue <= 0 || $enterpriseDiscountValue > 100) {
                    $state['errors'][] = "La r\u{00e9}duction entreprise doit \u{00ea}tre comprise entre 0 et 100.";
                } else {
                    $enterpriseDiscountValue = round($enterpriseDiscountValue, 2);
                }
            }
        }

        $referralOverrideValue = !empty($state['referralOverride']) ? 1 : 0;
        $referralL1Value = null;
        $referralL2Value = null;
        $referralL2EnabledValue = ($referralOverrideValue === 1 && !empty($state['referralL2Enabled'])) ? 1 : 0;

        $generalFilePath = !empty($state['generalFilePath']) ? (string) $state['generalFilePath'] : null;
        $generalFileMime = $state['generalFileMime'] ?? null;
        $generalFileSize = $state['generalFileSize'] ?? null;

        $generalFile = $files['general_file'] ?? null;
        $generalFileTmp = '';
        $generalFileOriginal = '';
        $generalFileNewMime = 'application/octet-stream';
        $generalFileNewSize = 0;
        $generalFileShouldUpload = false;
        $generalFileClear = !empty($post['general_file_clear']);

        if (is_array($generalFile) && isset($generalFile['error']) && (int) $generalFile['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = (int) ($generalFile['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_OK) {
                $state['errors'][] = "Erreur d'upload de la ressource g\u{00e9}n\u{00e9}rale (code " . (string) $uploadError . ').';
            } else {
                $generalFileTmp = (string) ($generalFile['tmp_name'] ?? '');
                $generalFileNewSize = (int) ($generalFile['size'] ?? 0);
                if ($generalFileTmp === '' || !is_uploaded_file($generalFileTmp)) {
                    $state['errors'][] = 'Fichier ressource invalide.';
                }
                $maxBytes = (int) $state['driveMaxMb'] * 1024 * 1024;
                if ($maxBytes > 0 && $generalFileNewSize > $maxBytes) {
                    $state['errors'][] = "La ressource g\u{00e9}n\u{00e9}rale d\u{00e9}passe " . (string) $state['driveMaxMb'] . ' Mo.';
                }
                if (empty($state['errors'])) {
                    $generalFileOriginal = $this->drive->safeFilename((string) ($generalFile['name'] ?? 'ressource'));
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $detected = finfo_file($finfo, $generalFileTmp);
                            if (is_string($detected) && $detected !== '') {
                                $generalFileNewMime = $detected;
                            }
                        }
                    } elseif (!empty($generalFile['type'])) {
                        $generalFileNewMime = (string) $generalFile['type'];
                    }
                    $generalFileShouldUpload = true;
                }
            }
        }

        if ($generalFileClear && !$generalFileShouldUpload) {
            $generalFilePath = null;
            $generalFileMime = null;
            $generalFileSize = null;
        }

        $coverPath = $course['cover_path'] ?? null;
        $coverFile = $files['cover_file'] ?? null;
        if (is_array($coverFile) && !empty($coverFile['name'])) {
            if ((int) ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $maxSize = 5 * 1024 * 1024;
                if ((int) ($coverFile['size'] ?? 0) > $maxSize) {
                    $state['errors'][] = "La miniature d\u{00e9}passe 5 Mo.";
                } else {
                    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo((string) ($coverFile['name'] ?? ''), PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedExt, true)) {
                        $state['errors'][] = "Extension de miniature non autoris\u{00e9}e.";
                    } else {
                        $uploadDir = __DIR__ . '/../../../storage/uploads/covers';
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0755, true);
                        }
                        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                        $dest = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file((string) $coverFile['tmp_name'], $dest)) {
                            $state['errors'][] = "\u{00c9}chec de l'upload de la miniature.";
                        } else {
                            $coverPath = 'storage/uploads/covers/' . $filename;
                        }
                    }
                }
            } else {
                $state['errors'][] = "Erreur d'upload de la miniature.";
            }
        }

        $objectivesClean = self::normalizeListField($state['objectives']);
        $prerequisitesClean = self::normalizeListField($state['prerequisites']);
        $equipmentClean = self::normalizeListField($state['equipment']);
        if ($objectivesClean === []) {
            $state['errors'][] = 'Ajoutez au moins un objectif.';
        }
        if ($prerequisitesClean === []) {
            $state['errors'][] = "Ajoutez au moins un pr\u{00e9}requis.";
        }
        if ($equipmentClean === []) {
            $state['errors'][] = "Ajoutez au moins un \u{00e9}quipement.";
        }

        if (empty($state['errors']) && $generalFileShouldUpload) {
            $prefix = 'ressource_cours_' . $courseId . '_' . date('Ymd_His');
            $driveName = $prefix . '_' . $generalFileOriginal;
            if (strlen($driveName) > 220) {
                $driveName = substr($driveName, 0, 220);
            }
            $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
            $driveResult = $this->drive->uploadFile(
                $generalFileTmp,
                $driveName,
                $generalFileNewMime ?: 'application/octet-stream',
                $parentId,
                APP_DRIVE_PUBLIC_LINK
            );
            if (empty($driveResult['ok'])) {
                $driveError = $this->drive->errorMessage($driveResult);
                $state['errors'][] = "Upload Drive impossible pour la ressource g\u{00e9}n\u{00e9}rale." . ($driveError !== '' ? ' ' . $driveError : '');
            } else {
                $generalFilePath = self::buildDriveLink($driveResult);
                $generalFileMime = $generalFileNewMime ?: null;
                $generalFileSize = $generalFileNewSize ?: null;
                $state['generalFilePath'] = (string) ($generalFilePath ?? '');
                $state['generalFileMime'] = $generalFileMime;
                $state['generalFileSize'] = $generalFileSize;
            }
        }

        if (!empty($state['errors'])) {
            return;
        }

        try {
            $this->model->updateCourse($courseId, [
                ':category_id' => $categoryIdInt,
                ':title' => $title,
                ':level' => $level,
                ':description' => $description,
                ':objectives' => json_encode($objectivesClean, JSON_UNESCAPED_UNICODE),
                ':prerequisites' => json_encode($prerequisitesClean, JSON_UNESCAPED_UNICODE),
                ':equipment' => json_encode($equipmentClean, JSON_UNESCAPED_UNICODE),
                ':duration_hours' => $durationValue,
                ':price_xof' => $priceValue,
                ':enterprise_price_xof' => $enterprisePriceValue,
                ':enterprise_discount_percent' => $enterpriseDiscountValue,
                ':referral_override' => $referralOverrideValue,
                ':referral_l1_rate' => $referralL1Value,
                ':referral_l2_rate' => $referralL2Value,
                ':referral_l2_enabled' => $referralL2EnabledValue,
                ':general_file_path' => $generalFilePath ?: null,
                ':general_file_mime' => $generalFileMime ?: null,
                ':general_file_size' => $generalFileSize ?: null,
                ':cover_path' => $coverPath,
            ]);
            $state['success'] = "Cours mis \u{00e0} jour.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = "Erreur lors de la mise \u{00e0} jour du cours.";
        }
    }

    private function handleAddChapter(array &$state, array $post): void
    {
        $title = trim((string) ($post['chapter_title'] ?? ''));
        $description = trim((string) ($post['chapter_description'] ?? ''));
        if ($title === '') {
            $state['errors'][] = 'Le titre du chapitre est obligatoire.';
            return;
        }

        try {
            $pos = $this->nextChapterPosition((int) $state['courseId']);
            $this->model->insertChapter(
                (int) $state['courseId'],
                $title,
                $description !== '' ? $description : null,
                $pos
            );
            $state['success'] = "Chapitre ajout\u{00e9}.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = "Erreur lors de l'ajout du chapitre.";
        }
    }

    private function handleAddLesson(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $chapterId = (int) ($post['lesson_chapter_id'] ?? 0);
        $title = trim((string) ($post['lesson_title'] ?? ''));
        $description = trim((string) ($post['lesson_description'] ?? ''));
        $positionInput = (int) ($post['lesson_position'] ?? 0);
        $duration = (int) ($post['lesson_duration'] ?? 0);
        $youtubeUrl = trim((string) ($post['youtube_url'] ?? ''));

        if (!$this->model->chapterExistsInCourse($chapterId, $courseId)) {
            $state['errors'][] = 'Chapitre invalide.';
        }
        if ($title === '') {
            $state['errors'][] = "Le titre de la le\u{00e7}on est obligatoire.";
        }
        if (!self::isYoutubeUrl($youtubeUrl)) {
            $state['errors'][] = 'Le lien YouTube est invalide.';
        }
        if ($duration < 1) {
            $state['errors'][] = "La dur\u{00e9}e de la le\u{00e7}on est obligatoire.";
        }
        if (!empty($state['errors'])) {
            return;
        }

        try {
            $position = $positionInput > 0 ? $positionInput : $this->nextItemPosition($chapterId, 'course_lessons');
            $this->model->insertLesson(
                $chapterId,
                $title,
                $description !== '' ? $description : null,
                $youtubeUrl,
                $position,
                $duration
            );
            $this->updateCourseDurationFromLessons($courseId);
            $state['success'] = "Le\u{00e7}on ajout\u{00e9}e.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = "Erreur lors de l'ajout de la le\u{00e7}on.";
        }
    }

    private function handleAddDoc(array &$state, array $post, array $files): void
    {
        $courseId = (int) $state['courseId'];
        $docType = (string) ($post['doc_type'] ?? 'document');
        $title = trim((string) ($post['doc_title'] ?? ''));
        $scope = (string) ($post['doc_scope'] ?? 'chapter');
        $chapterId = (int) ($post['doc_chapter_id'] ?? 0);
        $docLessonId = (int) ($post['doc_lesson_id'] ?? 0);
        $docFile = $files['doc_file'] ?? null;

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
            $state['errors'][] = 'Type de ressource invalide.';
        }

        if ($title === '') {
            $state['errors'][] = 'Le titre est obligatoire.';
        }

        $docFileTmp = '';
        $docFileName = '';
        $docFileSize = 0;
        $docFileMime = 'application/octet-stream';
        $docShouldUpload = false;
        if (!is_array($docFile) || (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $state['errors'][] = 'Le fichier est obligatoire.';
        } else {
            $uploadError = (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_OK) {
                $state['errors'][] = 'Erreur upload ressource (code ' . (string) $uploadError . ').';
            } else {
                $docFileTmp = (string) ($docFile['tmp_name'] ?? '');
                $docFileSize = (int) ($docFile['size'] ?? 0);
                if ($docFileTmp === '' || !is_uploaded_file($docFileTmp)) {
                    $state['errors'][] = 'Fichier ressource invalide.';
                }
                $maxBytes = (int) $state['driveMaxMb'] * 1024 * 1024;
                if ($maxBytes > 0 && $docFileSize > $maxBytes) {
                    $state['errors'][] = "La ressource d\u{00e9}passe " . (string) $state['driveMaxMb'] . ' Mo.';
                }
                if (empty($state['errors'])) {
                    $docFileName = $this->drive->safeFilename((string) ($docFile['name'] ?? 'ressource'));
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $detected = finfo_file($finfo, $docFileTmp);
                            if (is_string($detected) && $detected !== '') {
                                $docFileMime = $detected;
                            }
                        }
                    } elseif (!empty($docFile['type'])) {
                        $docFileMime = (string) $docFile['type'];
                    }
                    $docShouldUpload = true;
                }
            }
        }

        if ($dbType === 'exercise' && $scope !== 'chapter') {
            $state['errors'][] = "Un exercice doit \u{00ea}tre rattach\u{00e9} \u{00e0} un chapitre.";
        }
        if (!in_array($scope, ['chapter', 'lesson'], true)) {
            $state['errors'][] = "Port\u{00e9}e invalide.";
        }
        if ($chapterId < 1 || !$this->model->chapterExistsInCourse($chapterId, $courseId)) {
            $state['errors'][] = 'Chapitre invalide.';
        }
        if ($scope === 'lesson') {
            if ($docLessonId < 1) {
                $state['errors'][] = "Une le\u{00e7}on est requise pour l'association.";
            } elseif (!$this->model->lessonExistsInChapterCourse($docLessonId, $chapterId, $courseId)) {
                $state['errors'][] = "Le\u{00e7}on invalide pour ce chapitre.";
            }
        }
        if (!empty($state['errors'])) {
            return;
        }

        $driveLink = '';
        if ($docShouldUpload) {
            $prefix = 'ressource_' . $courseId . '_' . date('Ymd_His');
            $driveName = $prefix . '_' . $docFileName;
            if (strlen($driveName) > 220) {
                $driveName = substr($driveName, 0, 220);
            }
            $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
            $driveResult = $this->drive->uploadFile(
                $docFileTmp,
                $driveName,
                $docFileMime ?: 'application/octet-stream',
                $parentId,
                APP_DRIVE_PUBLIC_LINK
            );
            if (empty($driveResult['ok'])) {
                $driveError = $this->drive->errorMessage($driveResult);
                $state['errors'][] = 'Upload Drive impossible pour la ressource.' . ($driveError !== '' ? ' ' . $driveError : '');
                return;
            }
            $driveLink = self::buildDriveLink($driveResult);
        }

        $mime = $docFileMime ?: 'application/octet-stream';
        if ($docTag !== '') {
            $mime .= '|' . $docTag;
        }
        if ($scope === 'lesson' && $docLessonId > 0) {
            $mime .= '|lesson:' . $docLessonId;
        }

        try {
            $position = $this->nextItemPosition($chapterId, 'chapter_documents');
            $this->model->insertDocument(
                $chapterId,
                $title,
                $dbType,
                $driveLink,
                $mime,
                $docFileSize,
                $position
            );
            $state['success'] = "Ressource ajout\u{00e9}e.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Erreur lors de l ajout du document.';
        }
    }

    private function handleAddMeeting(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $title = trim((string) ($post['meeting_title'] ?? ''));
        $mode = (string) ($post['meeting_mode'] ?? 'online');
        $chapterId = (int) ($post['meeting_chapter_id'] ?? 0);
        $durationMin = (int) ($post['meeting_duration_min'] ?? 0);

        if ($chapterId < 1) {
            $chapterId = $this->resolveDefaultContentChapterId($courseId);
        }

        if ($title === '') {
            $state['errors'][] = 'Le titre du rendez-vous est obligatoire.';
        }
        if (!in_array($mode, ['online', 'onsite'], true)) {
            $state['errors'][] = 'Mode de rendez-vous invalide.';
        }
        if ($durationMin < 1) {
            $state['errors'][] = "La dur\u{00e9}e du rendez-vous est obligatoire.";
        }
        if (!$this->model->chapterExistsInCourse($chapterId, $courseId)) {
            $state['errors'][] = 'Chapitre invalide.';
        }
        if (!empty($state['errors'])) {
            return;
        }

        try {
            $this->model->insertMeetingTemplate(
                (bool) $state['meetingsHasDuration'],
                $courseId,
                $chapterId,
                $title,
                $mode,
                $durationMin
            );
            $state['success'] = "Rendez-vous ajout\u{00e9}.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = "Erreur lors de l'ajout du rendez-vous.";
        }
    }

    private function handleUpdateChapter(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $chapterId = (int) ($post['chapter_id'] ?? 0);
        $title = trim((string) ($post['chapter_title'] ?? ''));
        $description = trim((string) ($post['chapter_description'] ?? ''));
        $positionInput = (int) ($post['chapter_position'] ?? 0);

        if ($chapterId < 1) {
            $state['errors'][] = 'Chapitre invalide.';
            return;
        }
        if ($title === '') {
            $state['errors'][] = 'Le titre du chapitre est obligatoire.';
            return;
        }
        $chapter = $this->model->findChapterByIdInCourse($chapterId, $courseId);
        if (!$chapter) {
            $state['errors'][] = 'Chapitre introuvable.';
            return;
        }
        if (self::isSystemChapterTitle((string) ($chapter['title'] ?? ''))) {
            $state['errors'][] = "Ce chapitre syst\u{00e8}me ne peut pas \u{00ea}tre modifi\u{00e9}.";
            return;
        }

        try {
            $this->model->updateChapter(
                $chapterId,
                $courseId,
                $title,
                $description !== '' ? $description : null
            );
            if ($positionInput > 0) {
                $chapterIds = $this->model->findChapterIdsByCourseOrdered($courseId);
                $chapterIds = array_values(array_filter($chapterIds, static fn(int $id): bool => $id !== $chapterId));
                $positionInput = max(1, min($positionInput, count($chapterIds) + 1));
                array_splice($chapterIds, $positionInput - 1, 0, [$chapterId]);
                $pos = 1;
                foreach ($chapterIds as $id) {
                    $this->model->updateChapterPositionById($id, $pos);
                    $pos++;
                }
            }
            $state['success'] = 'Chapitre modifie.';
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Erreur lors de la modification du chapitre.';
        }
    }

    private function handleDeleteChapter(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $chapterId = (int) ($post['chapter_id'] ?? 0);
        if ($chapterId < 1) {
            $state['errors'][] = 'Chapitre invalide.';
            return;
        }

        $chapter = $this->model->findChapterByIdInCourse($chapterId, $courseId);
        if (!$chapter) {
            $state['errors'][] = 'Chapitre introuvable.';
            return;
        }
        if (self::isSystemChapterTitle((string) ($chapter['title'] ?? ''))) {
            $state['errors'][] = "Ce chapitre syst\u{00e8}me ne peut pas \u{00ea}tre supprim\u{00e9}.";
            return;
        }

        try {
            $this->model->beginTransaction();
            $this->model->deleteMeetingsByCourseChapter($courseId, $chapterId);
            $this->model->deleteChapter($chapterId, $courseId);
            $this->resequenceCoursePositions($courseId);
            $this->model->commit();
            $this->updateCourseDurationFromLessons($courseId);
            $state['success'] = "Chapitre supprim\u{00e9}.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $state['errors'][] = 'Erreur lors de la suppression du chapitre.';
        }
    }

    private function handleUpdateLesson(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $lessonId = (int) ($post['lesson_id'] ?? 0);
        $title = trim((string) ($post['lesson_title'] ?? ''));
        $description = trim((string) ($post['lesson_description'] ?? ''));
        $positionInput = (int) ($post['lesson_position'] ?? 0);
        $duration = (int) ($post['lesson_duration'] ?? 0);
        $youtubeUrl = trim((string) ($post['youtube_url'] ?? ''));

        if ($lessonId < 1) {
            $state['errors'][] = "Le\u{00e7}on invalide.";
            return;
        }
        if ($title === '') {
            $state['errors'][] = "Le titre de la le\u{00e7}on est obligatoire.";
            return;
        }
        if (!self::isYoutubeUrl($youtubeUrl)) {
            $state['errors'][] = 'Le lien YouTube est invalide.';
            return;
        }
        if ($duration < 1) {
            $state['errors'][] = "La dur\u{00e9}e de la le\u{00e7}on est obligatoire.";
            return;
        }
        $lesson = $this->model->findLessonByIdInCourseWithChapter($lessonId, $courseId);
        if (!$lesson) {
            $state['errors'][] = "Le\u{00e7}on introuvable.";
            return;
        }
        if (self::isSystemChapterTitle((string) ($lesson['chapter_title'] ?? ''))) {
            $state['errors'][] = "Cette le\u{00e7}on ne peut pas \u{00ea}tre modifi\u{00e9}e.";
            return;
        }
        $chapterId = (int) ($lesson['chapter_id'] ?? 0);

        try {
            $this->model->updateLesson(
                $lessonId,
                $title,
                $description !== '' ? $description : null,
                $youtubeUrl,
                $duration
            );
            if ($positionInput > 0 && $chapterId > 0) {
                $this->moveItemPosition('course_lessons', $chapterId, $lessonId, $positionInput);
            }
            $this->updateCourseDurationFromLessons($courseId);
            $state['success'] = "Le\u{00e7}on modifi\u{00e9}e.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = "Erreur lors de la modification de la le\u{00e7}on.";
        }
    }

    private function handleDeleteLesson(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $lessonId = (int) ($post['lesson_id'] ?? 0);
        if ($lessonId < 1) {
            $state['errors'][] = "Le\u{00e7}on invalide.";
            return;
        }
        $lesson = $this->model->findLessonByIdInCourseWithChapter($lessonId, $courseId);
        if (!$lesson) {
            $state['errors'][] = "Le\u{00e7}on introuvable.";
            return;
        }
        if (self::isSystemChapterTitle((string) ($lesson['chapter_title'] ?? ''))) {
            $state['errors'][] = "Cette le\u{00e7}on ne peut pas \u{00ea}tre supprim\u{00e9}e.";
            return;
        }

        $chapterId = (int) ($lesson['chapter_id'] ?? 0);
        try {
            $this->model->beginTransaction();
            if ($chapterId > 0) {
                $this->model->deleteLessonScopedDocuments($chapterId, $lessonId);
            }
            $this->model->deleteLessonById($lessonId);
            if ($chapterId > 0) {
                $this->resequencePositions('course_lessons', $chapterId);
            }
            $this->model->commit();
            $this->updateCourseDurationFromLessons($courseId);
            $state['success'] = "Le\u{00e7}on supprim\u{00e9}e.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $state['errors'][] = "Erreur lors de la suppression de la le\u{00e7}on.";
        }
    }

    private function handleUpdateDoc(array &$state, array $post, array $files): void
    {
        $courseId = (int) $state['courseId'];
        $docId = (int) ($post['doc_id'] ?? 0);
        $docType = (string) ($post['doc_type'] ?? 'document');
        $title = trim((string) ($post['doc_title'] ?? ''));
        $scope = (string) ($post['doc_scope'] ?? 'chapter');
        $chapterId = (int) ($post['doc_chapter_id'] ?? 0);
        $docLessonId = (int) ($post['doc_lesson_id'] ?? 0);
        $positionInput = (int) ($post['doc_position'] ?? 0);
        $docFile = $files['doc_file'] ?? null;

        $existing = null;
        $oldChapterId = 0;
        if ($docId < 1) {
            $state['errors'][] = 'Ressource invalide.';
        } else {
            $existing = $this->model->findDocumentByIdInCourse($docId, $courseId);
            if (!$existing) {
                $state['errors'][] = 'Ressource introuvable.';
            } elseif ((string) ($existing['type'] ?? '') === 'exam') {
                $state['errors'][] = "Cette ressource ne peut pas \u{00ea}tre modifi\u{00e9}e ici.";
            }
        }
        if ($existing) {
            $oldChapterId = (int) ($existing['chapter_id'] ?? 0);
        }

        $docPath = $existing ? (string) ($existing['file_path'] ?? '') : '';
        $docSize = $existing ? (int) ($existing['size'] ?? 0) : 0;
        $baseMime = 'application/octet-stream';
        if ($existing) {
            $existingMime = (string) ($existing['mime'] ?? '');
            if ($existingMime !== '') {
                $baseMime = $existingMime;
                if (strpos($baseMime, '|') !== false) {
                    $baseMime = explode('|', $baseMime)[0];
                }
            }
            if (stripos($baseMime, 'url_') === 0) {
                $baseMime = 'application/octet-stream';
            }
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
            $state['errors'][] = 'Type de ressource invalide.';
        }

        if ($title === '') {
            $state['errors'][] = 'Le titre est obligatoire.';
        }

        $docShouldUpload = false;
        $docFileTmp = '';
        $docFileName = '';
        $docFileNewSize = 0;
        $docFileNewMime = 'application/octet-stream';
        if (is_array($docFile) && (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = (int) ($docFile['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_OK) {
                $state['errors'][] = 'Erreur upload ressource (code ' . (string) $uploadError . ').';
            } else {
                $docFileTmp = (string) ($docFile['tmp_name'] ?? '');
                $docFileNewSize = (int) ($docFile['size'] ?? 0);
                if ($docFileTmp === '' || !is_uploaded_file($docFileTmp)) {
                    $state['errors'][] = 'Fichier ressource invalide.';
                }
                $maxBytes = (int) $state['driveMaxMb'] * 1024 * 1024;
                if ($maxBytes > 0 && $docFileNewSize > $maxBytes) {
                    $state['errors'][] = "La ressource d\u{00e9}passe " . (string) $state['driveMaxMb'] . ' Mo.';
                }
                if (empty($state['errors'])) {
                    $docFileName = $this->drive->safeFilename((string) ($docFile['name'] ?? 'ressource'));
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $detected = finfo_file($finfo, $docFileTmp);
                            if (is_string($detected) && $detected !== '') {
                                $docFileNewMime = $detected;
                            }
                        }
                    } elseif (!empty($docFile['type'])) {
                        $docFileNewMime = (string) $docFile['type'];
                    }
                    $docShouldUpload = true;
                }
            }
        }

        if (!in_array($scope, ['chapter', 'lesson'], true)) {
            $state['errors'][] = "Port\u{00e9}e invalide.";
        }
        if ($dbType === 'exercise' && $scope !== 'chapter') {
            $state['errors'][] = "Un exercice doit \u{00ea}tre rattach\u{00e9} \u{00e0} un chapitre.";
        }
        if ($chapterId < 1 || !$this->model->chapterExistsInCourse($chapterId, $courseId)) {
            $state['errors'][] = 'Chapitre invalide.';
        }
        if ($scope === 'lesson') {
            if ($docLessonId < 1) {
                $state['errors'][] = "Une le\u{00e7}on est requise pour l'association.";
            } elseif (!$this->model->lessonExistsInChapterCourse($docLessonId, $chapterId, $courseId)) {
                $state['errors'][] = "Le\u{00e7}on invalide pour ce chapitre.";
            }
        }
        if (!empty($state['errors']) || !$existing) {
            return;
        }

        if ($docShouldUpload) {
            $prefix = 'ressource_' . $courseId . '_' . date('Ymd_His');
            $driveName = $prefix . '_' . $docFileName;
            if (strlen($driveName) > 220) {
                $driveName = substr($driveName, 0, 220);
            }
            $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
            $driveResult = $this->drive->uploadFile(
                $docFileTmp,
                $driveName,
                $docFileNewMime ?: 'application/octet-stream',
                $parentId,
                APP_DRIVE_PUBLIC_LINK
            );
            if (empty($driveResult['ok'])) {
                $driveError = $this->drive->errorMessage($driveResult);
                $state['errors'][] = 'Upload Drive impossible pour la ressource.' . ($driveError !== '' ? ' ' . $driveError : '');
                return;
            }
            $docPath = self::buildDriveLink($driveResult);
            $docSize = $docFileNewSize;
            $baseMime = $docFileNewMime ?: 'application/octet-stream';
        }

        $mime = $baseMime ?: 'application/octet-stream';
        if ($docTag !== '') {
            $mime .= '|' . $docTag;
        }
        if ($scope === 'lesson' && $docLessonId > 0) {
            $mime .= '|lesson:' . $docLessonId;
        }

        $newPosition = (int) ($existing['position'] ?? 0);
        if ($positionInput > 0) {
            $newPosition = $positionInput;
        } elseif ($oldChapterId !== $chapterId) {
            $newPosition = $this->nextItemPosition($chapterId, 'chapter_documents');
        }
        if ($newPosition < 1) {
            $newPosition = $this->nextItemPosition($chapterId, 'chapter_documents');
        }

        try {
            $this->model->updateDocument(
                $docId,
                $chapterId,
                $title,
                $dbType,
                $docPath,
                $mime,
                $docSize,
                $newPosition
            );
            if ($oldChapterId > 0 && $oldChapterId !== $chapterId) {
                $this->resequencePositions('chapter_documents', $oldChapterId);
            }
            if ($positionInput > 0 || ($oldChapterId > 0 && $oldChapterId !== $chapterId)) {
                $targetPos = $positionInput > 0 ? $positionInput : $newPosition;
                $this->moveItemPosition('chapter_documents', $chapterId, $docId, $targetPos);
            }
            $state['success'] = 'Ressource modifiee.';
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Erreur lors de la modification de la ressource.';
        }
    }

    private function handleDeleteDoc(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $docId = (int) ($post['doc_id'] ?? 0);
        if ($docId < 1) {
            $state['errors'][] = 'Ressource invalide.';
            return;
        }

        $doc = $this->model->findDocumentByIdInCourse($docId, $courseId);
        if (!$doc) {
            $state['errors'][] = 'Ressource introuvable.';
            return;
        }

        try {
            $this->model->deleteDocument($docId);
            $chapterId = (int) ($doc['chapter_id'] ?? 0);
            if ($chapterId > 0) {
                $this->resequencePositions('chapter_documents', $chapterId);
            }
            $state['success'] = "Ressource supprim\u{00e9}e.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Erreur lors de la suppression de la ressource.';
        }
    }

    private function handleUpdateMeeting(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $meetingId = (int) ($post['meeting_id'] ?? 0);
        $title = trim((string) ($post['meeting_title'] ?? ''));
        $mode = (string) ($post['meeting_mode'] ?? 'online');
        $chapterId = (int) ($post['meeting_chapter_id'] ?? 0);
        $durationMin = (int) ($post['meeting_duration_min'] ?? 0);

        if ($meetingId < 1) {
            $state['errors'][] = 'Rendez-vous invalide.';
        }
        if ($title === '') {
            $state['errors'][] = 'Le titre du rendez-vous est obligatoire.';
        }
        if (!in_array($mode, ['online', 'onsite'], true)) {
            $state['errors'][] = 'Mode de rendez-vous invalide.';
        }
        if ($durationMin < 1) {
            $state['errors'][] = "La dur\u{00e9}e du rendez-vous est obligatoire.";
        }
        if (!$this->model->chapterExistsInCourse($chapterId, $courseId)) {
            $state['errors'][] = 'Chapitre invalide.';
        }
        if (!empty($state['errors'])) {
            return;
        }

        $meeting = $this->model->findMeetingTemplateByIdInCourse($meetingId, $courseId);
        if (!$meeting) {
            $state['errors'][] = 'Rendez-vous introuvable.';
            return;
        }

        try {
            $this->model->updateMeetingTemplate(
                (bool) $state['meetingsHasDuration'],
                $meetingId,
                $courseId,
                $chapterId,
                $title,
                $mode,
                $durationMin
            );
            $state['success'] = 'Rendez-vous modifie.';
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Erreur lors de la modification du rendez-vous.';
        }
    }

    private function handleDeleteMeeting(array &$state, array $post): void
    {
        $courseId = (int) $state['courseId'];
        $meetingId = (int) ($post['meeting_id'] ?? 0);
        if ($meetingId < 1) {
            $state['errors'][] = 'Rendez-vous invalide.';
            return;
        }

        $meeting = $this->model->findMeetingTemplateByIdInCourse($meetingId, $courseId);
        if (!$meeting) {
            $state['errors'][] = 'Rendez-vous introuvable.';
            return;
        }

        try {
            $this->model->deleteMeetingTemplate($meetingId, $courseId);
            $state['success'] = "Rendez-vous supprim\u{00e9}.";
            $state['didUpdate'] = true;
        } catch (\Throwable $e) {
            $state['errors'][] = 'Erreur lors de la suppression du rendez-vous.';
        }
    }

    private function handleSaveDraft(array &$state): void
    {
        $status = (string) (($state['course']['status'] ?? ''));
        if (!in_array($status, ['draft', 'rejected'], true)) {
            $state['errors'][] = 'Ce cours ne peut plus passer en brouillon.';
            return;
        }

        $this->model->setCourseDraft((int) $state['courseId']);
        $state['success'] = 'Cours enregistré en brouillon.';
        $state['course']['status'] = 'draft';
    }

    private function handleSubmitCourse(array &$state): void
    {
        $status = (string) (($state['course']['status'] ?? ''));
        if (!in_array($status, ['draft', 'rejected'], true)) {
            $state['errors'][] = "Ce cours ne peut plus \u{00ea}tre soumis.";
            return;
        }

        $courseId = (int) $state['courseId'];
        $categoryId = (int) (($state['course']['category_id'] ?? 0));
        if ($categoryId < 1 || !$this->model->categoryExists($categoryId)) {
            $state['errors'][] = 'Une categorie valide est requise avant la soumission.';
        }
        $counts = $this->model->fetchSubmissionCounts($courseId);
        if (((int) ($counts['chapters_cnt'] ?? 0)) < 1 || ((int) ($counts['lessons_cnt'] ?? 0)) < 1) {
            $state['errors'][] = "Ajoutez au moins un chapitre et une le\u{00e7}on avant de soumettre.";
        }
        if (((int) ($counts['meetings_cnt'] ?? 0)) < 1) {
            $state['errors'][] = 'Ajoutez au moins un rendez-vous avant de soumettre.';
        }
        if (!empty($state['errors'])) {
            return;
        }

        try {
            $this->model->beginTransaction();
            $this->model->setCourseSubmitted($courseId);

            $course = (array) $state['course'];
            foreach ($this->model->fetchAdminIds() as $adminId) {
                if ($adminId < 1) {
                    continue;
                }
                $this->model->insertNotification(
                    $adminId,
                    'Cours soumis',
                    (string) ($course['title'] ?? 'Cours') . " a \u{00e9}t\u{00e9} soumis pour validation.",
                    $this->absUrl('admin/courses?course_id=' . $courseId)
                );
            }

            $this->model->commit();
            $state['success'] = "Cours soumis, l'admin a \u{00e9}t\u{00e9} notifi\u{00e9}.";
            $state['course']['status'] = 'submitted';
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $state['errors'][] = 'Erreur lors de la soumission.';
        }
    }

    private function nextChapterPosition(int $courseId): int
    {
        return $this->model->findChapterMaxPosition($courseId) + 1;
    }

    private function nextItemPosition(int $chapterId, string $table): int
    {
        return $this->model->findItemMaxPositionForChapter($table, $chapterId) + 1;
    }

    private function resequencePositions(string $table, int $chapterId): void
    {
        $ids = $this->model->findItemIdsByChapterOrdered($table, $chapterId);
        $pos = 1;
        foreach ($ids as $id) {
            $this->model->updateItemPositionById($table, $id, $pos);
            $pos++;
        }
    }

    private function resequenceCoursePositions(int $courseId): void
    {
        $chapterIds = $this->model->findChapterIdsByCourseOrdered($courseId);
        $pos = 1;
        foreach ($chapterIds as $chapterId) {
            $this->model->updateChapterPositionById($chapterId, $pos);
            $this->resequencePositions('course_lessons', $chapterId);
            $this->resequencePositions('chapter_documents', $chapterId);
            $pos++;
        }
    }

    private function moveItemPosition(string $table, int $chapterId, int $itemId, int $newPosition): void
    {
        $ids = $this->model->findItemIdsByChapterOrdered($table, $chapterId);
        if ($ids === []) {
            return;
        }
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id !== $itemId));
        $newPosition = max(1, min($newPosition, count($ids) + 1));
        array_splice($ids, $newPosition - 1, 0, [$itemId]);
        $pos = 1;
        foreach ($ids as $id) {
            $this->model->updateItemPositionById($table, $id, $pos);
            $pos++;
        }
    }

    private function updateCourseDurationFromLessons(int $courseId): void
    {
        $totalMin = $this->model->sumLessonDurationMinutes($courseId);
        $hours = $totalMin > 0 ? round($totalMin / 60, 2) : 0.0;
        $this->model->updateCourseDurationHours($courseId, $hours);
    }

    private function resolveDefaultContentChapterId(int $courseId): int
    {
        foreach ($this->model->fetchChapters($courseId) as $chapter) {
            if (self::isSystemChapterTitle((string) ($chapter['title'] ?? ''))) {
                continue;
            }
            $chapterId = (int) ($chapter['id'] ?? 0);
            if ($chapterId > 0) {
                return $chapterId;
            }
        }

        return 0;
    }

    private static function normalizeListField(mixed $value): array
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $value = array_map(static fn($v): string => trim((string) $v), $value);
        $value = array_filter($value, static fn(string $v): bool => $v !== '');
        return array_values($value);
    }

    private static function isYoutubeUrl(string $url): bool
    {
        return (bool) preg_match('#^https?://(www\\.)?(youtube\\.com|youtu\\.be)/#i', $url);
    }

    private static function isSystemChapterTitle(string $title): bool
    {
        return in_array($title, ['Examens', 'Ressources generales'], true);
    }

    private static function docLessonIdFromMime(?string $mime): int
    {
        if (!$mime) {
            return 0;
        }
        if (preg_match('/\\|lesson:(\\d+)/', $mime, $matches)) {
            return (int) $matches[1];
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

    private static function buildDriveLink(array $driveResult): string
    {
        $driveLink = (string) ($driveResult['webViewLink'] ?? '');
        if ($driveLink === '' && !empty($driveResult['webContentLink'])) {
            $driveLink = (string) $driveResult['webContentLink'];
        }
        if ($driveLink === '' && !empty($driveResult['id'])) {
            $driveLink = 'https://drive.google.com/file/d/' . (string) $driveResult['id'] . '/view';
        }
        return $driveLink;
    }
}

