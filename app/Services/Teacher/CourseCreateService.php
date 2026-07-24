<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\CourseCreateModel;
use App\Services\Drive\DriveGateway;
use App\Services\Shared\ReferralService;

final class CourseCreateService extends Service
{
    private \PDO $pdo;
    private CourseCreateModel $courses;
    private ReferralService $referrals;

    public function __construct(
        \PDO $pdo,
        CourseCreateModel $courses,
        ReferralService $referrals
    ) {
        $this->pdo = $pdo;
        $this->courses = $courses;
        $this->referrals = $referrals;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @param array<string,mixed> $session
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $user, array $post, array $files, string $method, array &$session): array
    {
        $drive = new DriveGateway();
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $createdCourseId = (int) $this->flashGet('created_course_id', 0);

        $title = trim((string) ($post['title'] ?? ''));
        $categoryId = (string) ($post['category_id'] ?? '');
        $level = (string) ($post['level'] ?? 'beginner');
        $description = trim((string) ($post['description'] ?? ''));
        $objectives = $post['objectives'] ?? [];
        $prerequisites = $post['prerequisites'] ?? [];
        $equipment = $post['equipment'] ?? [];
        $durationHours = (string) ($post['duration_hours'] ?? '');
        $priceXof = (string) ($post['price_xof'] ?? '');
        $enterprisePriceXof = (string) ($post['enterprise_price_xof'] ?? '');
        $enterpriseDiscountPercent = (string) ($post['enterprise_discount_percent'] ?? '');
        $enterprisePricingMode = (string) ($post['enterprise_pricing_mode'] ?? '');
        if (!in_array($enterprisePricingMode, ['none', 'price', 'discount'], true)) {
            $enterprisePricingMode = trim($enterprisePriceXof) !== ''
                ? 'price'
                : (trim($enterpriseDiscountPercent) !== '' ? 'discount' : 'none');
        }
        if ($enterprisePricingMode === 'price') {
            $enterpriseDiscountPercent = '';
        } elseif ($enterprisePricingMode === 'discount') {
            $enterprisePriceXof = '';
        } else {
            $enterprisePriceXof = '';
            $enterpriseDiscountPercent = '';
        }
        $referralOverride = !empty($post['referral_override']) ? 1 : 0;
        $referralL2Enabled = !empty($post['referral_l2_enabled']) ? 1 : 0;
        $contentBuilderPayloadRaw = trim((string) ($post['content_payload'] ?? ''));
        $examBuilderPayloadRaw = trim((string) ($post['exams_payload'] ?? ''));
        $driveUpload = $drive->uploadUiState();
        $driveReady = !empty($driveUpload['ready']);
        $driveNeedsConnect = !empty($driveUpload['needsConnect']);
        $driveRemoteReady = !empty($driveUpload['remoteReady']);
        $driveUploadNote = (string) ($driveUpload['note'] ?? '');
        $driveMaxMb = APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25;
        $platformReferral = $this->referrals->getPlatformReferralSettings();
        $teacherDraftCourses = $this->courses->findTeacherDraftCourses((int) ($user['id'] ?? 0), 6);

        $generalFileTmp = '';
        $generalFileMime = null;
        $generalFileSize = null;
        $generalFileOriginal = '';
        $generalFileShouldUpload = false;

        $generalFileCachePath = '';
        $generalFileCacheName = '';
        $generalFileCacheMime = null;
        $generalFileCacheSize = null;
        $generalFileCache = $session['course_create_general_file'] ?? null;
        if (is_array($generalFileCache)) {
            $generalFileCachePath = $this->normalizeGeneralTempPath((string) ($generalFileCache['path'] ?? ''));
            if ($generalFileCachePath !== '') {
                $generalFileCacheName = $drive->safeFilename((string) ($generalFileCache['name'] ?? ''));
                $generalFileCacheMime = $generalFileCache['mime'] ?? null;
                if (isset($generalFileCache['size']) && is_numeric((string) $generalFileCache['size'])) {
                    $generalFileCacheSize = (int) $generalFileCache['size'];
                }
            } else {
                unset($session['course_create_general_file']);
            }
        }

        $coverPathExisting = $this->normalizeCoverPath((string) ($post['cover_path_existing'] ?? ''));
        if ($coverPathExisting === '' && !empty($session['course_create_cover_path'])) {
            $coverPathExisting = $this->normalizeCoverPath((string) $session['course_create_cover_path']);
        }
        if ($coverPathExisting === '' && isset($session['course_create_cover_path'])) {
            unset($session['course_create_cover_path']);
        }

        if ($method === 'POST') {
            $submitAction = (string) ($post['submit_action'] ?? 'draft');
            $requiresCategoryForSubmission = $submitAction === 'submit';
            $draftTitle = 'Brouillon sans titre';
            $draftDescription = 'Brouillon en cours.';

            if ($title === '') {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'Le titre est obligatoire.';
                } else {
                    $title = $draftTitle;
                }
            }
            if ($description === '') {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'La description est obligatoire.';
                } else {
                    $description = $draftDescription;
                }
            }

            $allowedLevels = ['beginner', 'intermediate', 'advanced'];
            if (!in_array($level, $allowedLevels, true)) {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'Niveau invalide.';
                } else {
                    $level = 'beginner';
                }
            }

            $categoryIdInt = null;
            if ($categoryId === '') {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'Une categorie valide est obligatoire avant la soumission.';
                }
            } elseif ($categoryId === 'other') {
                $categoryIdInt = null;
            } else {
                $categoryIdInt = (int) $categoryId;
                if (!$this->courses->categoryExists($categoryIdInt)) {
                    if ($requiresCategoryForSubmission) {
                        $errors[] = 'Categorie inconnue.';
                    } else {
                        $categoryIdInt = null;
                    }
                } 
            }

            $durationValue = null;
            if ($durationHours === '') {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'La duree est obligatoire.';
                }
            } else {
                $durationValue = (float) $durationHours;
                if ($durationValue <= 0) {
                    if ($requiresCategoryForSubmission) {
                        $errors[] = 'La duree doit etre positive.';
                    } else {
                        $durationValue = null;
                    }
                }
            }

            $priceValue = null;
            $priceRaw = trim($priceXof);
            if ($priceRaw === '') {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'Le prix est obligatoire.';
                } else {
                    $priceValue = 0;
                }
            } else {
                if (preg_match('/^\d+$/', $priceRaw) !== 1) {
                    if ($requiresCategoryForSubmission) {
                        $errors[] = 'Le prix doit etre un entier positif ou nul.';
                    } else {
                        $priceValue = 0;
                    }
                } else {
                    $priceValue = (int) $priceRaw;
                }
            }

            $enterprisePriceValue = null;
            $enterpriseDiscountValue = null;
            $enterprisePriceRaw = trim($enterprisePriceXof);
            $enterpriseDiscountRaw = str_replace(',', '.', trim($enterpriseDiscountPercent));
            if ($enterprisePriceRaw !== '' && $enterpriseDiscountRaw !== '') {
                $errors[] = 'Choisissez soit un prix entreprise, soit une reduction entreprise.';
            } elseif ($enterprisePriceRaw !== '') {
                if (preg_match('/^\d+$/', $enterprisePriceRaw) !== 1) {
                    $errors[] = 'Le prix entreprise doit etre un entier positif.';
                } else {
                    $enterprisePriceValue = (int) $enterprisePriceRaw;
                    if ($enterprisePriceValue < 1) {
                        $errors[] = 'Le prix entreprise doit etre superieur a 0.';
                    }
                }
            } elseif ($enterpriseDiscountRaw !== '') {
                if (!is_numeric($enterpriseDiscountRaw)) {
                    $errors[] = 'La reduction entreprise doit etre numerique.';
                } else {
                    $enterpriseDiscountValue = (float) $enterpriseDiscountRaw;
                    if ($enterpriseDiscountValue <= 0 || $enterpriseDiscountValue > 100) {
                        $errors[] = 'La reduction entreprise doit etre comprise entre 0 et 100.';
                    } else {
                        $enterpriseDiscountValue = round($enterpriseDiscountValue, 2);
                    }
                }
            }

            $referralOverrideValue = $referralOverride ? 1 : 0;
            $referralL1Value = null;
            $referralL2Value = null;
            $referralL2EnabledValue = ($referralOverrideValue === 1 && $referralL2Enabled) ? 1 : 0;

            $generalFile = $files['general_file'] ?? null;
            $generalFileClear = !empty($post['general_file_clear']);
            $generalFileErrors = [];

            if ($generalFileClear && $generalFileCachePath !== '') {
                $this->safeUnlink($generalFileCachePath);
                $generalFileCachePath = '';
                $generalFileCacheName = '';
                $generalFileCacheMime = null;
                $generalFileCacheSize = null;
                unset($session['course_create_general_file']);
            }

            if (is_array($generalFile) && isset($generalFile['error']) && (int) $generalFile['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadError = (int) ($generalFile['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $generalFileErrors[] = 'Erreur d upload de la ressource generale (code ' . (string) $uploadError . ').';
                } else {
                    $generalFileTmp = (string) ($generalFile['tmp_name'] ?? '');
                    $generalFileSize = (int) ($generalFile['size'] ?? 0);
                    if ($generalFileTmp === '' || !is_uploaded_file($generalFileTmp)) {
                        $generalFileErrors[] = 'Fichier ressource invalide.';
                    }
                    $maxBytes = (int) $driveMaxMb * 1024 * 1024;
                    if ($maxBytes > 0 && $generalFileSize > $maxBytes) {
                        $generalFileErrors[] = 'La ressource generale depasse ' . (string) $driveMaxMb . ' Mo.';
                    }

                    if ($generalFileErrors === []) {
                        $generalFileOriginal = $drive->safeFilename((string) ($generalFile['name'] ?? 'ressource'));
                        $generalFileMime = 'application/octet-stream';
                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            if ($finfo) {
                                $detected = finfo_file($finfo, $generalFileTmp);
                                if (is_string($detected) && $detected !== '') {
                                    $generalFileMime = $detected;
                                }
                                finfo_close($finfo);
                            }
                        } elseif (!empty($generalFile['type'])) {
                            $generalFileMime = (string) $generalFile['type'];
                        }

                        $generalTempDir = $this->projectRoot() . '/storage/uploads/general_tmp';
                        if (!is_dir($generalTempDir)) {
                            mkdir($generalTempDir, 0755, true);
                        }
                        $tempName = bin2hex(random_bytes(8)) . '.tmp';
                        $tempPath = $generalTempDir . '/' . $tempName;
                        if (!move_uploaded_file($generalFileTmp, $tempPath)) {
                            $generalFileErrors[] = 'Echec de la sauvegarde temporaire de la ressource.';
                        } else {
                            if ($generalFileCachePath !== '') {
                                $this->safeUnlink($generalFileCachePath);
                            }
                            $generalFileCachePath = 'storage/uploads/general_tmp/' . $tempName;
                            $generalFileCacheName = $generalFileOriginal;
                            $generalFileCacheMime = $generalFileMime;
                            $generalFileCacheSize = $generalFileSize;
                            $session['course_create_general_file'] = [
                                'path' => $generalFileCachePath,
                                'name' => $generalFileCacheName,
                                'mime' => $generalFileCacheMime,
                                'size' => $generalFileCacheSize,
                            ];
                        }
                    }
                }
            }

            if ($generalFileCachePath !== '' && $generalFileErrors === []) {
                $generalFileTmp = $this->projectRoot() . '/' . $generalFileCachePath;
                $generalFileOriginal = $generalFileCacheName !== '' ? $generalFileCacheName : 'ressource';
                $generalFileMime = $generalFileCacheMime ?: 'application/octet-stream';
                $generalFileSize = (int) ($generalFileCacheSize ?? 0);
                $generalFileShouldUpload = true;
            }
            if ($generalFileErrors !== []) {
                if ($requiresCategoryForSubmission) {
                    $errors = array_merge($errors, $generalFileErrors);
                }
            }

            $coverPath = $coverPathExisting !== '' ? $coverPathExisting : null;
            $previousCoverPath = $coverPath;
            $coverFile = $files['cover_file'] ?? null;
            $coverProvided = is_array($coverFile) && !empty($coverFile['name']);
            if ($coverProvided) {
                if ((int) ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $coverUpload = $this->validateCourseCoverUpload((array) $coverFile, $errors);
                    if ($coverUpload !== null) {
                        $uploadDir = $this->projectRoot() . '/storage/uploads/covers';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $filename = bin2hex(random_bytes(8)) . '.' . $coverUpload['extension'];
                        $dest = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file((string) $coverFile['tmp_name'], $dest)) {
                            $errors[] = 'Echec de l upload de la miniature.';
                        } else {
                            $coverPath = 'storage/uploads/covers/' . $filename;
                            $coverPathExisting = $coverPath;
                        }
                    }
                } elseif ($requiresCategoryForSubmission) {
                    $errors[] = 'Erreur d upload de la miniature.';
                }
            }

            if ($coverPath === null && !$coverProvided && $requiresCategoryForSubmission) {
                $errors[] = 'La miniature est obligatoire.';
            }
            if ($previousCoverPath && $previousCoverPath !== $coverPath) {
                $this->safeUnlink((string) $previousCoverPath);
            }
            if ($coverPathExisting !== '') {
                $session['course_create_cover_path'] = $coverPathExisting;
            } else {
                unset($session['course_create_cover_path']);
            }

            $status = $submitAction === 'submit' ? 'submitted' : 'draft';
            $submittedAt = $status === 'submitted' ? date('Y-m-d H:i:s') : null;

            $objectivesClean = $this->normalizeListField($objectives);
            $prerequisitesClean = $this->normalizeListField($prerequisites);
            $equipmentClean = $this->normalizeListField($equipment);
            $contentBuilder = $this->parseContentBuilderPayload(
                $contentBuilderPayloadRaw,
                $submitAction === 'submit',
                $errors
            );
            $examBuilder = $this->parseExamBuilderPayload(
                $examBuilderPayloadRaw,
                $errors,
                $submitAction === 'submit'
            );

            if ($submitAction === 'submit' && $objectivesClean === []) {
                $errors[] = 'Ajoutez au moins un objectif pédagogique avant de publier.';
            }

            if ($errors === []) {
                try {
                    $withExamQuestionVariant = false;
                    if (($examBuilder['exams'] ?? []) !== []) {
                        $this->schema()->ensureExamSessionsDriveSubjectColumns();
                        $withExamQuestionVariant = $this->schema()->ensureExamQuestionsVariantColumn();
                    }

                    $this->pdo->beginTransaction();

                    $slug = $this->courses->generateCourseSlug($title);
                    $newCourseId = $this->courses->createCourse([
                        'teacher_id' => (int) ($user['id'] ?? 0),
                        'category_id' => $categoryIdInt,
                        'title' => $title,
                        'slug' => $slug,
                        'level' => $level,
                        'description' => $description,
                        'objectives' => json_encode($objectivesClean, JSON_UNESCAPED_UNICODE),
                        'prerequisites' => json_encode($prerequisitesClean, JSON_UNESCAPED_UNICODE),
                        'equipment' => json_encode($equipmentClean, JSON_UNESCAPED_UNICODE),
                        'duration_hours' => $durationValue,
                        'price_xof' => $priceValue,
                        'enterprise_price_xof' => $enterprisePriceValue,
                        'enterprise_discount_percent' => $enterpriseDiscountValue,
                        'referral_override' => $referralOverrideValue,
                        'referral_l1_rate' => $referralL1Value,
                        'referral_l2_rate' => $referralL2Value,
                        'referral_l2_enabled' => $referralL2EnabledValue,
                        'cover_path' => $coverPath,
                        'status' => $status,
                        'submitted_at' => $submittedAt,
                    ]);
                    if ($newCourseId < 1) {
                        throw new \RuntimeException('Identifiant de cours invalide apres creation.');
                    }

                    $this->attachBuilderDocumentUploads(
                        $contentBuilder,
                        $files,
                        $drive,
                        (int) ($user['id'] ?? 0),
                        $driveMaxMb,
                        $submitAction === 'submit',
                        $errors
                    );
                    $this->attachBuilderExamSubjectUpload(
                        $examBuilder,
                        $files,
                        $drive,
                        $newCourseId,
                        $driveMaxMb,
                        $submitAction === 'submit',
                        $errors
                    );

                    if ($errors === []) {
                        $this->persistCompleteBuilder($newCourseId, $contentBuilder, $examBuilder, $withExamQuestionVariant);
                    }

                    if ($errors === [] && $generalFileShouldUpload) {
                        $prefix = 'ressource_cours_' . (int) ($user['id'] ?? 0) . '_' . date('Ymd_His');
                        $driveName = $prefix . '_' . $generalFileOriginal;
                        if (strlen($driveName) > 220) {
                            $driveName = substr($driveName, 0, 220);
                        }
                        $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
                        $driveResult = $drive->uploadFile(
                            $generalFileTmp,
                            $driveName,
                            $generalFileMime ?: 'application/octet-stream',
                            $parentId,
                            APP_DRIVE_PUBLIC_LINK
                        );
                        if (empty($driveResult['ok'])) {
                            $driveError = $drive->errorMessage($driveResult);
                            $errors[] = 'Upload Drive impossible pour la ressource generale.' . ($driveError !== '' ? ' ' . $driveError : '');
                        } else {
                            $driveLink = (string) ($driveResult['webViewLink'] ?? '');
                            if ($driveLink === '' && !empty($driveResult['webContentLink'])) {
                                $driveLink = (string) $driveResult['webContentLink'];
                            }
                            if ($driveLink === '' && !empty($driveResult['id'])) {
                                $driveLink = 'https://drive.google.com/file/d/' . (string) $driveResult['id'] . '/view';
                            }
                            $this->courses->updateCourseGeneralFile(
                                $newCourseId,
                                $driveLink !== '' ? $driveLink : null,
                                $generalFileMime ?: null,
                                $generalFileSize ?: null
                            );
                        }
                    }

                    if ($errors !== []) {
                        $this->pdo->rollBack();
                    } else {
                        $this->courses->insertAuditLog((int) ($user['id'] ?? 0), 'course_create', 'course', $newCourseId);
                        $this->pdo->commit();

                        if ($generalFileCachePath !== '') {
                            $this->safeUnlink($generalFileCachePath);
                        }
                        unset($session['course_create_general_file'], $session['course_create_cover_path']);

                        if ($submitAction === 'content') {
                            return [
                                'redirectTo' => $this->siteUrl('teacher/course_content?id=' . $newCourseId . '&step=pedagogy'),
                                'view' => [],
                            ];
                        }

                        $successMessage = $submitAction === 'submit'
                            ? 'Cours soumis à la validation admin.'
                            : 'Fiche du cours enregistrée.';
                        $this->flashSet('success', $successMessage);
                        $this->flashSet('created_course_id', (int) $newCourseId);

                        return [
                            'redirectTo' => $this->siteUrl('teacher/course_create'),
                            'view' => [],
                        ];
                    }
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $errors[] = 'Erreur lors de la creation du cours.';
                    if ($this->isLocalRequest()) {
                        $errors[] = 'Detail technique: ' . $e->getMessage();
                    }
                    if (function_exists('error_log')) {
                        error_log('[CourseCreate] ' . $e->getMessage());
                    }
                }
            }
        }

        return [
            'redirectTo' => null,
            'view' => [
                'categories' => $this->courses->findActiveCategories(),
                'errors' => $errors,
                'successMessage' => $successMessage,
                'createdCourseId' => $createdCourseId,
                'title' => $title,
                'categoryId' => $categoryId,
                'level' => $level,
                'description' => $description,
                'objectives' => $objectives,
                'prerequisites' => $prerequisites,
                'equipment' => $equipment,
                'durationHours' => $durationHours,
                'priceXof' => $priceXof,
                'enterprisePriceXof' => $enterprisePriceXof,
                'enterpriseDiscountPercent' => $enterpriseDiscountPercent,
                'referralOverride' => $referralOverride,
                'referralL2Enabled' => $referralL2Enabled,
                'contentBuilderPayload' => $contentBuilderPayloadRaw,
                'examBuilderPayload' => $examBuilderPayloadRaw,
                'driveReady' => $driveReady,
                'driveRemoteReady' => $driveRemoteReady,
                'driveLocalReady' => !empty($driveUpload['localReady']),
                'driveNeedsConnect' => $driveNeedsConnect,
                'driveConnectRecommended' => $driveNeedsConnect && !$driveRemoteReady,
                'driveUploadNote' => $driveUploadNote,
                'driveMaxMb' => $driveMaxMb,
                'platformReferral' => $platformReferral,
                'teacherDraftCourses' => $teacherDraftCourses,
                'generalFileCachePath' => $generalFileCachePath,
                'generalFileCacheName' => $generalFileCacheName,
                'generalFileCacheSize' => $generalFileCacheSize,
                'coverPathExisting' => $coverPathExisting,
                'teacherId' => (int) ($user['id'] ?? 0),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @param array<string,mixed> $session
     * @return array{redirectTo:?string,fatal:?string,view:array<string,mixed>}
     */
    public function handleEdit(
        array $user,
        bool $isAdmin,
        int $courseId,
        array $post,
        array $files,
        string $method,
        array &$session
    ): array {
        if ($courseId < 1) {
            return ['redirectTo' => null, 'fatal' => 'Cours introuvable.', 'view' => []];
        }

        $course = $this->courses->findCourseById($courseId);
        if (!$course) {
            return ['redirectTo' => null, 'fatal' => 'Cours introuvable.', 'view' => []];
        }
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            return ['redirectTo' => null, 'fatal' => 'Acces refuse.', 'view' => []];
        }
        $this->schema()->ensureCoursesUpdateFlagColumn();

        $drive = new DriveGateway();
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $isLocked = in_array((string) ($course['status'] ?? ''), ['submitted'], true);
        $wasUpdating = !empty($course['update_in_progress']);
        $driveUpload = $drive->uploadUiState();
        $driveReady = !empty($driveUpload['ready']);
        $driveNeedsConnect = !empty($driveUpload['needsConnect']);
        $driveRemoteReady = !empty($driveUpload['remoteReady']);
        $driveUploadNote = (string) ($driveUpload['note'] ?? '');
        $driveMaxMb = APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25;
        $platformReferral = $this->referrals->getPlatformReferralSettings();
        $teacherDraftCourses = $this->courses->findTeacherDraftCourses((int) ($user['id'] ?? 0), 6);

        $title = trim((string) ($post['title'] ?? ($course['title'] ?? '')));
        $categoryId = (string) ($post['category_id'] ?? ($course['category_id'] ?? ''));
        $level = (string) ($post['level'] ?? ($course['level'] ?? 'beginner'));
        $description = trim((string) ($post['description'] ?? ($course['description'] ?? '')));
        $objectives = $post['objectives'] ?? $this->decodeStoredList($course['objectives'] ?? '[]');
        $prerequisites = $post['prerequisites'] ?? $this->decodeStoredList($course['prerequisites'] ?? '[]');
        $equipment = $post['equipment'] ?? $this->decodeStoredList($course['equipment'] ?? '[]');
        $durationHours = (string) ($post['duration_hours'] ?? ($course['duration_hours'] ?? ''));
        $priceXof = (string) ($post['price_xof'] ?? ($course['price_xof'] ?? ''));
        $enterprisePriceXof = (string) ($post['enterprise_price_xof'] ?? ($course['enterprise_price_xof'] ?? ''));
        $enterpriseDiscountPercent = (string) ($post['enterprise_discount_percent'] ?? ($course['enterprise_discount_percent'] ?? ''));
        $enterprisePricingMode = (string) ($post['enterprise_pricing_mode'] ?? '');
        if (!in_array($enterprisePricingMode, ['none', 'price', 'discount'], true)) {
            $enterprisePricingMode = trim($enterprisePriceXof) !== ''
                ? 'price'
                : (trim($enterpriseDiscountPercent) !== '' ? 'discount' : 'none');
        }
        if ($enterprisePricingMode === 'price') {
            $enterpriseDiscountPercent = '';
        } elseif ($enterprisePricingMode === 'discount') {
            $enterprisePriceXof = '';
        } else {
            $enterprisePriceXof = '';
            $enterpriseDiscountPercent = '';
        }

        $referralOverride = isset($post['referral_override']) ? (!empty($post['referral_override']) ? 1 : 0) : (int) ($course['referral_override'] ?? 0);
        $referralL2Enabled = isset($post['referral_l2_enabled']) ? (!empty($post['referral_l2_enabled']) ? 1 : 0) : (int) ($course['referral_l2_enabled'] ?? 0);
        $coverPathExisting = $this->normalizeCoverPath((string) ($post['cover_path_existing'] ?? ($course['cover_path'] ?? '')));
        if ($coverPathExisting === '' && !empty($course['cover_path'])) {
            $coverPathExisting = $this->normalizeCoverPath((string) $course['cover_path']);
        }
        $storedContentBuilderPayloadRaw = $this->buildContentBuilderPayloadForCourse($courseId);
        $contentBuilderPayloadRaw = trim((string) ($post['content_payload'] ?? ''));
        if ($contentBuilderPayloadRaw === '') {
            $contentBuilderPayloadRaw = $storedContentBuilderPayloadRaw;
        }
        $storedExamBuilderPayloadRaw = $this->buildExamBuilderPayloadForCourse($courseId);
        $examBuilderPayloadRaw = trim((string) ($post['exams_payload'] ?? ''));
        if ($examBuilderPayloadRaw === '') {
            $examBuilderPayloadRaw = $storedExamBuilderPayloadRaw;
        }

        $generalFilePath = trim((string) ($course['general_file_path'] ?? ''));
        $generalFileMime = $course['general_file_mime'] ?? null;
        $generalFileSize = isset($course['general_file_size']) ? (int) $course['general_file_size'] : 0;
        $generalFileCacheName = $this->displayNameFromPath($generalFilePath);
        $generalFileCacheSize = max(0, $generalFileSize);

        if ($method === 'POST') {
            $submitAction = (string) ($post['submit_action'] ?? 'draft');
            $requiresCategoryForSubmission = $submitAction === 'submit';
            $isDraftSave = $submitAction === 'draft';
            $storedTitle = trim((string) ($course['title'] ?? ''));
            $storedDescription = trim((string) ($course['description'] ?? ''));
            $storedLevel = (string) ($course['level'] ?? 'beginner');
            $storedDurationHours = isset($course['duration_hours']) && $course['duration_hours'] !== ''
                ? (float) $course['duration_hours']
                : null;
            $storedPriceXof = isset($course['price_xof']) ? (int) $course['price_xof'] : 0;
            $storedEnterprisePriceXof = isset($course['enterprise_price_xof']) && $course['enterprise_price_xof'] !== null
                ? (string) $course['enterprise_price_xof']
                : '';
            $storedEnterpriseDiscountPercent = isset($course['enterprise_discount_percent']) && $course['enterprise_discount_percent'] !== null
                ? (string) $course['enterprise_discount_percent']
                : '';
            $storedCategoryId = isset($course['category_id']) && $course['category_id'] !== null
                ? (int) $course['category_id']
                : null;

            if ($isLocked) {
                $errors[] = 'Cours verrouille (soumis) : modifications non autorisees.';
            }
            if ($title === '') {
                $errors[] = 'Le titre est obligatoire.';
            }
            if ($description === '') {
                $errors[] = 'La description est obligatoire.';
            }
            if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
                $errors[] = 'Niveau invalide.';
            }

            $categoryIdInt = null;
            if ($categoryId === '') {
                if ($requiresCategoryForSubmission) {
                    $errors[] = 'Une categorie valide est obligatoire avant la soumission.';
                } elseif ($storedCategoryId !== null) {
                    $categoryIdInt = $storedCategoryId;
                }
            } elseif ($categoryId === 'other') {
                $categoryIdInt = null;
            } else {
                $categoryIdInt = (int) $categoryId;
                if (!$this->courses->categoryExists($categoryIdInt)) {
                    $errors[] = 'Categorie inconnue.';
                }
            }

            $durationValue = null;
            if ($durationHours === '') {
                $errors[] = 'La duree est obligatoire.';
            } else {
                $durationValue = (float) $durationHours;
                if ($durationValue <= 0) {
                    $errors[] = 'La duree doit etre positive.';
                }
            }

            $priceValue = null;
            $priceRaw = trim($priceXof);
            if ($priceRaw === '') {
                $errors[] = 'Le prix est obligatoire.';
            } elseif (preg_match('/^\d+$/', $priceRaw) !== 1) {
                $errors[] = 'Le prix doit etre un entier positif ou nul.';
            } else {
                $priceValue = (int) $priceRaw;
            }

            $enterprisePriceValue = null;
            $enterpriseDiscountValue = null;
            $enterprisePriceRaw = trim($enterprisePriceXof);
            $enterpriseDiscountRaw = str_replace(',', '.', trim($enterpriseDiscountPercent));
            if ($enterprisePriceRaw !== '' && $enterpriseDiscountRaw !== '') {
                $errors[] = 'Choisissez soit un prix entreprise, soit une reduction entreprise.';
            } elseif ($enterprisePriceRaw !== '') {
                if (preg_match('/^\d+$/', $enterprisePriceRaw) !== 1) {
                    $errors[] = 'Le prix entreprise doit etre un entier positif.';
                } else {
                    $enterprisePriceValue = (int) $enterprisePriceRaw;
                    if ($enterprisePriceValue < 1) {
                        $errors[] = 'Le prix entreprise doit etre superieur a 0.';
                    }
                }
            } elseif ($enterpriseDiscountRaw !== '') {
                if (!is_numeric($enterpriseDiscountRaw)) {
                    $errors[] = 'La reduction entreprise doit etre numerique.';
                } else {
                    $enterpriseDiscountValue = (float) $enterpriseDiscountRaw;
                    if ($enterpriseDiscountValue <= 0 || $enterpriseDiscountValue > 100) {
                        $errors[] = 'La reduction entreprise doit etre comprise entre 0 et 100.';
                    } else {
                        $enterpriseDiscountValue = round($enterpriseDiscountValue, 2);
                    }
                }
            }

            $coverPath = $coverPathExisting !== '' ? $coverPathExisting : null;
            $coverFile = $files['cover_file'] ?? null;
            $coverProvided = is_array($coverFile) && !empty($coverFile['name']);
            if ($coverProvided) {
                if ((int) ($coverFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $coverUpload = $this->validateCourseCoverUpload((array) $coverFile, $errors);
                    if ($coverUpload !== null) {
                        $uploadDir = $this->projectRoot() . '/storage/uploads/covers';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        $filename = bin2hex(random_bytes(8)) . '.' . $coverUpload['extension'];
                        $dest = $uploadDir . '/' . $filename;
                        if (!move_uploaded_file((string) $coverFile['tmp_name'], $dest)) {
                            $errors[] = 'Echec de l upload de la miniature.';
                        } else {
                            $coverPath = 'storage/uploads/covers/' . $filename;
                            $coverPathExisting = $coverPath;
                        }
                    }
                } else {
                    $errors[] = 'Erreur d upload de la miniature.';
                }
            }
            if ($coverPath === null && !$coverProvided) {
                $errors[] = 'La miniature est obligatoire.';
            }

            if ($isDraftSave) {
                if ($title === '') {
                    $title = $storedTitle !== '' ? $storedTitle : 'Brouillon sans titre';
                }
                if ($description === '') {
                    $description = $storedDescription !== '' ? $storedDescription : 'Brouillon en cours.';
                }
                if (!in_array($level, ['beginner', 'intermediate', 'advanced'], true)) {
                    $level = in_array($storedLevel, ['beginner', 'intermediate', 'advanced'], true) ? $storedLevel : 'beginner';
                }
                if ($categoryId !== '' && $categoryId !== 'other' && $categoryIdInt !== null && !$this->courses->categoryExists($categoryIdInt)) {
                    $categoryIdInt = $storedCategoryId;
                } elseif (($categoryId === '' || $categoryId === 'other') && $storedCategoryId !== null) {
                    $categoryIdInt = $storedCategoryId;
                }
                if ($durationValue === null || $durationValue <= 0) {
                    $durationValue = $storedDurationHours;
                }
                if ($priceValue === null) {
                    $priceValue = $storedPriceXof;
                }
                if (($enterprisePriceValue === null || $enterprisePriceValue < 1) && $storedEnterprisePriceXof !== '') {
                    $enterprisePriceValue = (int) $storedEnterprisePriceXof;
                }
                if (($enterpriseDiscountValue === null || $enterpriseDiscountValue <= 0 || $enterpriseDiscountValue > 100) && $storedEnterpriseDiscountPercent !== '') {
                    $enterpriseDiscountValue = (float) $storedEnterpriseDiscountPercent;
                }
                if ($coverPath === null && $coverPathExisting !== '') {
                    $coverPath = $coverPathExisting;
                }
                if (!$isLocked) {
                    $errors = [];
                }
            }

            $generalFile = $files['general_file'] ?? null;
            $generalFileTmp = '';
            $generalFileOriginal = '';
            $generalFileNewMime = null;
            $generalFileNewSize = null;
            $generalFileShouldUpload = false;
            if (!empty($post['general_file_clear'])) {
                $generalFilePath = '';
                $generalFileMime = null;
                $generalFileSize = 0;
                $generalFileCacheName = '';
                $generalFileCacheSize = 0;
            }
            if (is_array($generalFile) && isset($generalFile['error']) && (int) $generalFile['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadError = (int) ($generalFile['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $errors[] = 'Erreur d upload de la ressource generale (code ' . (string) $uploadError . ').';
                } else {
                    $generalFileTmp = (string) ($generalFile['tmp_name'] ?? '');
                    $generalFileNewSize = (int) ($generalFile['size'] ?? 0);
                    if ($generalFileTmp === '' || !is_uploaded_file($generalFileTmp)) {
                        $errors[] = 'Fichier ressource invalide.';
                    }
                    $maxBytes = (int) $driveMaxMb * 1024 * 1024;
                    if ($maxBytes > 0 && (int) $generalFileNewSize > $maxBytes) {
                        $errors[] = 'La ressource generale depasse ' . (string) $driveMaxMb . ' Mo.';
                    }
                    if ($errors === []) {
                        $generalFileOriginal = $drive->safeFilename((string) ($generalFile['name'] ?? 'ressource'));
                        $generalFileNewMime = $this->detectUploadMime($generalFileTmp, (string) ($generalFile['type'] ?? ''));
                        $generalFileShouldUpload = true;
                    }
                }
            }

            $objectivesClean = $this->normalizeListField($objectives);
            $prerequisitesClean = $this->normalizeListField($prerequisites);
            $equipmentClean = $this->normalizeListField($equipment);
            $contentBuilder = $this->parseContentBuilderPayload($contentBuilderPayloadRaw, $submitAction === 'submit', $errors);
            $examBuilder = $this->parseExamBuilderPayload(
                $examBuilderPayloadRaw,
                $errors,
                $submitAction === 'submit'
            );
            $contentChanged = $this->builderPayloadChanged($storedContentBuilderPayloadRaw, $contentBuilderPayloadRaw);
            $examsChanged = $this->builderPayloadChanged($storedExamBuilderPayloadRaw, $examBuilderPayloadRaw);
            $hasDriveExam = array_filter(
                $examBuilder['exams'] ?? [],
                static fn(array $exam): bool => (string) ($exam['type'] ?? '') === 'drive'
            ) !== [];
            if ($hasDriveExam && $this->hasExamSubjectUpload($files)) {
                $examsChanged = true;
            }
            if ($submitAction === 'submit' && $objectivesClean === []) {
                $errors[] = 'Ajoutez au moins un objectif pedagogique avant de publier.';
            }
            if ($examsChanged && $this->courses->countExamAttemptsForCourse($courseId) > 0) {
                $errors[] = 'Les examens de ce cours ont deja des tentatives. Les modifier depuis ce formulaire supprimerait l historique des apprenants.';
            }

            if ($errors === []) {
                try {
                    $withExamQuestionVariant = false;
                    if ($examsChanged && ($examBuilder['exams'] ?? []) !== []) {
                        $this->schema()->ensureExamSessionsDriveSubjectColumns();
                        $withExamQuestionVariant = $this->schema()->ensureExamQuestionsVariantColumn();
                    }

                    $this->pdo->beginTransaction();
                    if ($contentChanged) {
                        $this->attachBuilderDocumentUploads(
                            $contentBuilder,
                            $files,
                            $drive,
                            (int) ($user['id'] ?? 0),
                            $driveMaxMb,
                            $submitAction === 'submit',
                            $errors
                        );
                    }
                    if ($examsChanged) {
                        $this->attachBuilderExamSubjectUpload(
                            $examBuilder,
                            $files,
                            $drive,
                            $courseId,
                            $driveMaxMb,
                            $submitAction === 'submit',
                            $errors
                        );
                    }

                    if ($errors === [] && $generalFileShouldUpload) {
                        $prefix = 'ressource_cours_' . $courseId . '_' . date('Ymd_His');
                        $driveName = $prefix . '_' . $generalFileOriginal;
                        if (strlen($driveName) > 220) {
                            $driveName = substr($driveName, 0, 220);
                        }
                        $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
                        $driveResult = $drive->uploadFile(
                            $generalFileTmp,
                            $driveName,
                            $generalFileNewMime ?: 'application/octet-stream',
                            $parentId,
                            APP_DRIVE_PUBLIC_LINK
                        );
                        if (empty($driveResult['ok'])) {
                            $driveError = $drive->errorMessage($driveResult);
                            $errors[] = 'Upload Drive impossible pour la ressource generale.' . ($driveError !== '' ? ' ' . $driveError : '');
                        } else {
                            $generalFilePath = $this->driveResultLink($driveResult);
                            $generalFileMime = $generalFileNewMime ?: null;
                            $generalFileSize = (int) ($generalFileNewSize ?? 0);
                        }
                    }

                    if ($errors === []) {
                        $currentStatus = (string) ($course['status'] ?? 'draft');
                        $submitsForValidation = $submitAction === 'submit' && in_array($currentStatus, ['draft', 'rejected'], true);
                        $status = $submitsForValidation
                            ? 'submitted'
                            : (in_array($currentStatus, ['draft', 'rejected'], true) ? 'draft' : $currentStatus);
                        $submittedAt = $status === 'submitted'
                            ? date('Y-m-d H:i:s')
                            : ($status === (string) ($course['status'] ?? '') ? ($course['submitted_at'] ?? null) : null);

                        $this->courses->updateCourseFromBuilder($courseId, [
                            'category_id' => $categoryIdInt,
                            'title' => $title,
                            'level' => $level,
                            'description' => $description,
                            'objectives' => json_encode($objectivesClean, JSON_UNESCAPED_UNICODE),
                            'prerequisites' => json_encode($prerequisitesClean, JSON_UNESCAPED_UNICODE),
                            'equipment' => json_encode($equipmentClean, JSON_UNESCAPED_UNICODE),
                            'duration_hours' => $durationValue,
                            'price_xof' => $priceValue,
                            'enterprise_price_xof' => $enterprisePriceValue,
                            'enterprise_discount_percent' => $enterpriseDiscountValue,
                            'referral_override' => $referralOverride ? 1 : 0,
                            'referral_l1_rate' => null,
                            'referral_l2_rate' => null,
                            'referral_l2_enabled' => ($referralOverride && $referralL2Enabled) ? 1 : 0,
                            'cover_path' => $coverPath,
                            'status' => $status,
                            'submitted_at' => $submittedAt,
                        ]);
                        $this->courses->updateCourseGeneralFile(
                            $courseId,
                            $generalFilePath !== '' ? $generalFilePath : null,
                            $generalFileMime ?: null,
                            $generalFileSize > 0 ? $generalFileSize : null
                        );
                        if ($contentChanged) {
                            $this->courses->deleteBuilderContentForCourse($courseId);
                            $this->persistContentBuilder($courseId, $contentBuilder);
                        }
                        if ($examsChanged) {
                            $this->courses->deleteBuilderExamsForCourse($courseId);
                            $this->persistExamBuilder($courseId, $examBuilder, $withExamQuestionVariant);
                        }
                    }

                    if ($errors !== []) {
                        $this->pdo->rollBack();
                    } else {
                        if ((string) ($course['status'] ?? '') === 'approved' && !$wasUpdating) {
                            $this->courses->setUpdateInProgress($courseId);
                        }
                        $this->courses->insertAuditLog((int) ($user['id'] ?? 0), 'course_update', 'course', $courseId);
                        $this->pdo->commit();
                        if ((string) ($course['status'] ?? '') === 'approved' && !$wasUpdating) {
                            $this->notifyCourseUpdateStarted($courseId, $course, $user);
                        }
                        $didSubmitForValidation = $submitAction === 'submit' && in_array((string) ($course['status'] ?? ''), ['draft', 'rejected'], true);

                        $this->flashSet('success', $didSubmitForValidation ? 'Cours soumis à la validation admin.' : 'Cours mis à jour.');
                        $this->flashSet('updated_course_id', $courseId);
                        return [
                            'redirectTo' => $this->siteUrl('teacher/course_edit?id=' . $courseId),
                            'fatal' => null,
                            'view' => [],
                        ];
                    }
                } catch (\Throwable $e) {
                    if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                    }
                    $errors[] = 'Erreur lors de la mise a jour du cours.';
                    if ($this->isLocalRequest()) {
                        $errors[] = 'Detail technique: ' . $e->getMessage();
                    }
                    if (function_exists('error_log')) {
                        error_log('[CourseEditBuilder] ' . $e->getMessage());
                    }
                }
            }
        }

        return [
            'redirectTo' => null,
            'fatal' => null,
            'view' => [
                'editMode' => true,
                'courseId' => $courseId,
                'course' => $course,
                'isLocked' => $isLocked,
                'categories' => $this->courses->findActiveCategories(),
                'errors' => $errors,
                'successMessage' => $successMessage,
                'createdCourseId' => 0,
                'updatedCourseId' => (int) $this->flashGet('updated_course_id', 0),
                'title' => $title,
                'categoryId' => $categoryId,
                'level' => $level,
                'description' => $description,
                'objectives' => $objectives,
                'prerequisites' => $prerequisites,
                'equipment' => $equipment,
                'durationHours' => $durationHours,
                'priceXof' => $priceXof,
                'enterprisePriceXof' => $enterprisePriceXof,
                'enterpriseDiscountPercent' => $enterpriseDiscountPercent,
                'referralOverride' => $referralOverride,
                'referralL2Enabled' => $referralL2Enabled,
                'contentBuilderPayload' => $contentBuilderPayloadRaw,
                'examBuilderPayload' => $examBuilderPayloadRaw,
                'driveReady' => $driveReady,
                'driveRemoteReady' => $driveRemoteReady,
                'driveLocalReady' => !empty($driveUpload['localReady']),
                'driveNeedsConnect' => $driveNeedsConnect,
                'driveConnectRecommended' => $driveNeedsConnect && !$driveRemoteReady,
                'driveUploadNote' => $driveUploadNote,
                'driveMaxMb' => $driveMaxMb,
                'platformReferral' => $platformReferral,
                'teacherDraftCourses' => $teacherDraftCourses,
                'generalFileCachePath' => $generalFilePath,
                'generalFileCacheName' => $generalFileCacheName,
                'generalFileCacheSize' => $generalFileCacheSize,
                'coverPathExisting' => $coverPathExisting,
                'teacherId' => (int) ($user['id'] ?? 0),
            ],
        ];
    }

    private function buildContentBuilderPayloadForCourse(int $courseId): string
    {
        $chapters = $this->courses->fetchBuilderChapters($courseId);
        $chapterIds = array_values(array_filter(array_map(
            static fn(array $row): int => (int) ($row['id'] ?? 0),
            $chapters
        )));
        $lessonsByChapter = [];
        foreach ($this->courses->fetchBuilderLessonsByChapterIds($chapterIds) as $lesson) {
            $lessonsByChapter[(int) ($lesson['chapter_id'] ?? 0)][] = $lesson;
        }
        $docsByChapter = [];
        foreach ($this->courses->fetchBuilderDocsByChapterIds($chapterIds) as $doc) {
            $docsByChapter[(int) ($doc['chapter_id'] ?? 0)][] = $doc;
        }
        $meetingsByChapter = [];
        foreach ($this->courses->fetchBuilderMeetings($courseId) as $meeting) {
            $meetingsByChapter[(int) ($meeting['chapter_id'] ?? 0)][] = $meeting;
        }

        $modules = [];
        foreach ($chapters as $chapter) {
            $chapterId = (int) ($chapter['id'] ?? 0);
            $items = [];
            foreach ($lessonsByChapter[$chapterId] ?? [] as $lesson) {
                $lessonType = (string) ($lesson['lesson_type'] ?? 'video');
                $resource = $lessonType === 'document'
                    ? trim((string) ($lesson['document_path'] ?? ''))
                    : trim((string) ($lesson['youtube_url'] ?? ''));
                $items[] = [
                    'id' => (int) ($lesson['id'] ?? 0),
                    'type' => $lessonType === 'document' ? 'document' : 'video',
                    'title' => (string) ($lesson['title'] ?? ''),
                    'description' => (string) ($lesson['description'] ?? ''),
                    'resource' => $resource,
                    'duration_min' => (int) ($lesson['duration_estimate_min'] ?? 0),
                    'file_key' => 'lesson_' . (int) ($lesson['id'] ?? 0),
                    'file_name' => $lessonType === 'document' ? $this->displayNameFromPath($resource) : '',
                ];
            }
            foreach ($docsByChapter[$chapterId] ?? [] as $doc) {
                $resource = trim((string) ($doc['file_path'] ?? ''));
                if ($resource === '') {
                    continue;
                }
                $items[] = [
                    'id' => 0,
                    'type' => 'document',
                    'title' => (string) ($doc['title'] ?? 'Document'),
                    'description' => '',
                    'resource' => $resource,
                    'duration_min' => 0,
                    'file_key' => 'doc_' . (int) ($doc['id'] ?? 0),
                    'file_name' => $this->displayNameFromPath($resource),
                ];
            }
            foreach ($meetingsByChapter[$chapterId] ?? [] as $meeting) {
                $items[] = [
                    'id' => (int) ($meeting['id'] ?? 0),
                    'type' => 'meeting',
                    'title' => (string) ($meeting['title'] ?? 'Rendez-vous'),
                    'description' => '',
                    'resource' => '',
                    'duration_min' => (int) ($meeting['duration_min'] ?? 60),
                    'mode' => (string) ($meeting['mode'] ?? 'online') === 'onsite' ? 'onsite' : 'online',
                ];
            }

            $modules[] = [
                'id' => $chapterId,
                'title' => (string) ($chapter['title'] ?? 'Module'),
                'description' => (string) ($chapter['description'] ?? ''),
                'items' => $items,
            ];
        }

        $orphanMeetings = $meetingsByChapter[0] ?? [];
        if ($orphanMeetings !== []) {
            $items = [];
            foreach ($orphanMeetings as $meeting) {
                $items[] = [
                    'id' => (int) ($meeting['id'] ?? 0),
                    'type' => 'meeting',
                    'title' => (string) ($meeting['title'] ?? 'Rendez-vous'),
                    'description' => '',
                    'resource' => '',
                    'duration_min' => (int) ($meeting['duration_min'] ?? 60),
                    'mode' => (string) ($meeting['mode'] ?? 'online') === 'onsite' ? 'onsite' : 'online',
                ];
            }
            $modules[] = [
                'id' => 0,
                'title' => 'Rendez-vous',
                'description' => '',
                'items' => $items,
            ];
        }

        $encoded = json_encode(['modules' => $modules], JSON_UNESCAPED_UNICODE);
        return is_string($encoded) ? $encoded : '{"modules":[]}';
    }

    private function buildExamBuilderPayloadForCourse(int $courseId): string
    {
        $examRows = $this->courses->fetchBuilderExams($courseId);
        $examIds = array_values(array_filter(array_map(
            static fn(array $row): int => (int) ($row['id'] ?? 0),
            $examRows
        )));
        $questionsByExam = [];
        $questionIds = [];
        foreach ($this->courses->fetchBuilderQuestionsByExamIds($examIds) as $question) {
            $questionsByExam[(int) ($question['exam_id'] ?? 0)][] = $question;
            $questionIds[] = (int) ($question['id'] ?? 0);
        }
        $optionsByQuestion = [];
        foreach ($this->courses->fetchBuilderOptionsByQuestionIds($questionIds) as $option) {
            $optionsByQuestion[(int) ($option['question_id'] ?? 0)][] = $option;
        }

        $exams = [];
        foreach ($examRows as $exam) {
            $type = strtolower((string) ($exam['type'] ?? 'qcm'));
            if (!in_array($type, ['qcm', 'drive', 'meeting'], true)) {
                $type = 'qcm';
            }
            $versions = [1 => [], 2 => [], 3 => []];
            $versionCount = 1;
            foreach ($questionsByExam[(int) ($exam['id'] ?? 0)] ?? [] as $question) {
                $variant = max(1, min(3, (int) ($question['variant'] ?? 1)));
                $versionCount = max($versionCount, $variant);
                $options = [];
                foreach ($optionsByQuestion[(int) ($question['id'] ?? 0)] ?? [] as $option) {
                    $options[] = [
                        'text' => (string) ($option['option_text'] ?? ''),
                        'correct' => !empty($option['is_correct']),
                    ];
                }
                $versions[$variant][] = [
                    'text' => (string) ($question['question_text'] ?? ''),
                    'type' => (string) ($question['question_type'] ?? 'single'),
                    'options' => $options,
                ];
            }

            $subjectLink = trim((string) ($exam['drive_subject_link'] ?? ''));
            $subjectName = trim((string) ($exam['drive_subject_name'] ?? ''));
            $exams[] = [
                'id' => (int) ($exam['id'] ?? 0),
                'title' => (string) ($exam['title'] ?? ''),
                'type' => $type,
                'description' => (string) ($exam['description'] ?? ''),
                'duration_min' => (int) ($exam['duration_min'] ?? ($type === 'qcm' ? 45 : 0)),
                'max_attempts' => (int) ($exam['max_attempts'] ?? ($type === 'qcm' ? 3 : 1)),
                'min_score' => (int) ($exam['min_score'] ?? 0),
                'points_per_question' => $this->firstQuestionPoints($questionsByExam[(int) ($exam['id'] ?? 0)] ?? []),
                'version_count' => $type === 'qcm' ? max(1, min(3, $versionCount)) : 0,
                'versions' => $versions,
                'subject' => $subjectLink !== '' ? $subjectLink : $subjectName,
                'subject_file_name' => $subjectName,
                'meeting_mode' => (string) ($exam['meeting_mode'] ?? 'online') === 'onsite' ? 'onsite' : 'online',
                'meeting_link' => '',
                'meeting_place' => '',
            ];
        }

        $encoded = json_encode(['exams' => $exams], JSON_UNESCAPED_UNICODE);
        return is_string($encoded) ? $encoded : '{"exams":[]}';
    }

    /**
     * @param array<int,array<string,mixed>> $questions
     */
    private function firstQuestionPoints(array $questions): int
    {
        foreach ($questions as $question) {
            $points = (int) ($question['points'] ?? 0);
            if ($points > 0) {
                return $points;
            }
        }
        return 1;
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function decodeStoredList($value): array
    {
        $decoded = json_decode((string) $value, true);
        return $this->normalizeListField(is_array($decoded) ? $decoded : []);
    }

    private function displayNameFromPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        $urlPath = parse_url($path, PHP_URL_PATH);
        $name = basename((string) ($urlPath ?: $path));
        $name = trim(urldecode($name));
        if (in_array(strtolower($name), ['view', 'edit', 'preview', 'download'], true)) {
            return 'Document deja associe';
        }
        return $name !== '' ? $name : 'Ressource existante';
    }

    private function builderPayloadChanged(string $storedRaw, string $submittedRaw): bool
    {
        return $this->canonicalBuilderPayload($storedRaw) !== $this->canonicalBuilderPayload($submittedRaw);
    }

    private function canonicalBuilderPayload(string $raw): string
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = [];
        }
        $decoded = $this->normalizeComparableBuilderRoot($decoded);
        $normalized = $this->normalizeComparableBuilderValue($decoded);
        $encoded = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($encoded) ? $encoded : '{}';
    }

    /**
     * @param array<string,mixed> $decoded
     * @return array<string,mixed>
     */
    private function normalizeComparableBuilderRoot(array $decoded): array
    {
        return $decoded;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeComparableBuilderValue($value)
    {
        if (!is_array($value)) {
            return is_string($value) ? trim($value) : $value;
        }

        $isList = array_keys($value) === range(0, count($value) - 1);
        $normalized = [];
        foreach ($value as $key => $child) {
            if (!$isList && in_array((string) $key, ['id', 'max_attempts'], true)) {
                continue;
            }
            $normalized[$key] = $this->normalizeComparableBuilderValue($child);
        }

        if (!$isList) {
            ksort($normalized);
        }

        return $normalized;
    }

    private function notifyCourseUpdateStarted(int $courseId, array $course, array $user): void
    {
        if (function_exists('log_course_audit')) {
            \log_course_audit($user['id'] ?? null, $courseId, 'course_update_started', [
                'label' => 'Mise a jour demarree',
                'source' => 'course_edit_unified',
            ]);
        }

        if (!function_exists('notify_course_paid_students')) {
            return;
        }

        $slug = trim((string) ($course['slug'] ?? ''));
        $courseUrl = $slug !== ''
            ? $this->absUrl('course?slug=' . rawurlencode($slug))
            : $this->absUrl('course?id=' . $courseId);
        \notify_course_paid_students(
            $courseId,
            'Cours en mise a jour',
            'Le cours "' . (string) ($course['title'] ?? 'Cours') . '" est en cours de mise a jour. Certaines lecons ou ressources peuvent changer.',
            $courseUrl
        );
    }

    /**
     * @param array<int,string> $errors
     * @return array{modules:array<int,array{title:string,description:?string,items:array<int,array<string,mixed>>}>,counts:array{modules:int,lessons:int,meetings:int}}
     */
    private function parseContentBuilderPayload(string $raw, bool $requireForSubmit, array &$errors): array
    {
        $fallback = [
            'modules' => [],
            'counts' => ['modules' => 0, 'lessons' => 0, 'meetings' => 0],
        ];
        if ($raw === '') {
            if ($requireForSubmit) {
                $errors[] = 'Ajoutez au moins un module et une leçon avant de publier.';
            }
            return $fallback;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $errors[] = 'Structure du contenu invalide.';
            return $fallback;
        }

        $modulesRaw = is_array($decoded['modules'] ?? null) ? $decoded['modules'] : [];
        $modules = [];
        $lessonCount = 0;
        $meetingCount = 0;

        foreach ($modulesRaw as $moduleIndex => $moduleRaw) {
            if (!is_array($moduleRaw)) {
                continue;
            }
            $title = $this->shortText((string) ($moduleRaw['title'] ?? ''), 200);
            $description = $this->shortText((string) ($moduleRaw['description'] ?? ''), 1000);
            $itemsRaw = is_array($moduleRaw['items'] ?? null) ? $moduleRaw['items'] : [];
            $items = [];

            foreach ($itemsRaw as $itemIndex => $itemRaw) {
                if (!is_array($itemRaw)) {
                    continue;
                }
                $type = strtolower(trim((string) ($itemRaw['type'] ?? 'video')));
                if (!in_array($type, ['video', 'document', 'meeting'], true)) {
                    $errors[] = 'Type de contenu invalide dans le module ' . ((int) $moduleIndex + 1) . '.';
                    continue;
                }

                $itemTitle = $this->shortText((string) ($itemRaw['title'] ?? ''), 200);
                $itemDescription = $this->shortText((string) ($itemRaw['description'] ?? ''), 1000);
                $resource = trim((string) ($itemRaw['resource'] ?? ''));
                $durationMin = max(0, (int) ($itemRaw['duration_min'] ?? 0));
                $mode = strtolower(trim((string) ($itemRaw['mode'] ?? 'online')));
                $fileKey = $this->shortText((string) ($itemRaw['file_key'] ?? ''), 80);
                $fileName = $this->shortText((string) ($itemRaw['file_name'] ?? ''), 255);
                $hasContent = $itemTitle !== '' || $itemDescription !== '' || $resource !== '' || $fileName !== '';

                if (!$hasContent) {
                    continue;
                }

                if ($itemTitle === '') {
                    $errors[] = 'Titre manquant pour un élément du module ' . ((int) $moduleIndex + 1) . '.';
                    continue;
                }

                if ($type === 'video') {
                    if (!$this->isYoutubeUrl($resource)) {
                        $errors[] = 'Lien YouTube invalide pour la leçon "' . $itemTitle . '".';
                        continue;
                    }
                } elseif ($type === 'document') {
                    // Le document est validé plus loin lors de l'envoi du fichier.
                } else {
                    if (!in_array($mode, ['online', 'onsite'], true)) {
                        $mode = 'online';
                    }
                    if ($durationMin < 1) {
                        $durationMin = 60;
                    }
                    $meetingCount++;
                }

                if ($type !== 'meeting' && $durationMin < 1) {
                    $errors[] = 'Durée obligatoire pour la leçon "' . $itemTitle . '".';
                    continue;
                }

                if ($type !== 'meeting') {
                    $lessonCount++;
                }

                $items[] = [
                    'type' => $type,
                    'title' => $itemTitle,
                    'description' => $itemDescription !== '' ? $itemDescription : null,
                    'resource' => $type === 'meeting' ? '' : $resource,
                    'duration_min' => $durationMin,
                    'mode' => $mode,
                    'file_key' => $type === 'document' ? $fileKey : '',
                    'file_name' => $type === 'document' ? $fileName : '',
                ];
            }

            if ($title === '' && $items === []) {
                continue;
            }
            if ($title === '') {
                $title = 'Module ' . ((int) count($modules) + 1);
            }

            $modules[] = [
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'items' => $items,
            ];
        }

        if ($requireForSubmit) {
            if ($modules === []) {
                $errors[] = 'Ajoutez au moins un module avant de publier.';
            }
            if ($lessonCount < 1) {
                $errors[] = 'Ajoutez au moins une leçon avant de publier.';
            }
        }

        return [
            'modules' => $modules,
            'counts' => [
                'modules' => count($modules),
                'lessons' => $lessonCount,
                'meetings' => $meetingCount,
            ],
        ];
    }

    /**
     * @param array{modules:array<int,array{title:string,description:?string,items:array<int,array<string,mixed>>}>,counts:array{modules:int,lessons:int,meetings:int}} $contentBuilder
     * @param array<string,mixed> $files
     * @param array<int,string> $errors
     */
    private function attachBuilderDocumentUploads(
        array &$contentBuilder,
        array $files,
        DriveGateway $drive,
        int $teacherId,
        int $driveMaxMb,
        bool $requireForSubmit,
        array &$errors
    ): void {
        $maxBytes = max(1, $driveMaxMb) * 1024 * 1024;
        $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;

        foreach ($contentBuilder['modules'] as $moduleIndex => &$module) {
            foreach ($module['items'] as $itemIndex => &$item) {
                if ((string) ($item['type'] ?? '') !== 'document') {
                    continue;
                }

                $title = (string) ($item['title'] ?? 'Document');
                $fileKey = trim((string) ($item['file_key'] ?? ''));
                $existingResource = trim((string) ($item['resource'] ?? ''));
                $hasExistingUploadedResource = $existingResource !== '' && (
                    preg_match('~^https?://~i', $existingResource) === 1
                    || str_starts_with($existingResource, 'storage/')
                );
                $upload = $this->lessonUploadByKey($files, $fileKey);

                if ($upload === null || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    if ($hasExistingUploadedResource) {
                        continue;
                    }
                    $item['resource'] = '';
                    if ($requireForSubmit) {
                        $errors[] = 'Ajoutez le fichier document/exercice pour "' . $title . '".';
                    }
                    continue;
                }

                $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError !== UPLOAD_ERR_OK) {
                    $errors[] = 'Erreur d upload du document "' . $title . '" (code ' . (string) $uploadError . ').';
                    continue;
                }

                $tmpName = (string) ($upload['tmp_name'] ?? '');
                $size = max(0, (int) ($upload['size'] ?? 0));
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    $errors[] = 'Fichier document invalide pour "' . $title . '".';
                    continue;
                }
                if ($maxBytes > 0 && $size > $maxBytes) {
                    $errors[] = 'Le document "' . $title . '" depasse ' . (string) $driveMaxMb . ' Mo.';
                    continue;
                }

                $originalName = $drive->safeFilename((string) ($upload['name'] ?? 'document'));
                if ($originalName === '') {
                    $originalName = 'document';
                }
                $mime = $this->detectUploadMime($tmpName, (string) ($upload['type'] ?? ''));
                $driveName = 'contenu_cours_' . $teacherId . '_' . date('Ymd_His')
                    . '_m' . ((int) $moduleIndex + 1)
                    . '_l' . ((int) $itemIndex + 1)
                    . '_' . $originalName;
                if (strlen($driveName) > 220) {
                    $driveName = substr($driveName, 0, 220);
                }

                $driveResult = $drive->uploadFile($tmpName, $driveName, $mime, $parentId, APP_DRIVE_PUBLIC_LINK);
                if (empty($driveResult['ok'])) {
                    $driveError = $drive->errorMessage($driveResult);
                    $errors[] = 'Upload Drive impossible pour le document "' . $title . '".' . ($driveError !== '' ? ' ' . $driveError : '');
                    continue;
                }

                $driveLink = $this->driveResultLink($driveResult);
                if ($driveLink === '') {
                    $errors[] = 'Lien Drive introuvable apres upload du document "' . $title . '".';
                    continue;
                }

                $item['resource'] = $driveLink;
                $item['file_name'] = $originalName;
                $item['document_mime'] = $mime;
                $item['document_size'] = $size;
            }
            unset($item);
        }
        unset($module);
    }

    /**
     * @param array{exams:array<int,array<string,mixed>>} $examBuilder
     * @param array<string,mixed> $files
     * @param array<int,string> $errors
     */
    private function attachBuilderExamSubjectUpload(
        array &$examBuilder,
        array $files,
        DriveGateway $drive,
        int $courseId,
        int $driveMaxMb,
        bool $requireForSubmit,
        array &$errors
    ): void {
        $upload = $this->examSubjectUpload($files);
        $maxBytes = max(1, $driveMaxMb) * 1024 * 1024;
        $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;

        foreach ($examBuilder['exams'] as &$exam) {
            if ((string) ($exam['type'] ?? '') !== 'drive') {
                continue;
            }

            $existingLink = trim((string) ($exam['drive_subject_link'] ?? ''));
            $existingName = trim((string) ($exam['drive_subject_name'] ?? ''));
            $hasExistingUploadedSubject = $existingLink !== '' && (
                preg_match('~^https?://~i', $existingLink) === 1
                || str_starts_with($existingLink, 'storage/')
            );

            if ($upload === null || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                if ($hasExistingUploadedSubject) {
                    continue;
                }
                if ($requireForSubmit) {
                    $errors[] = $existingName !== ''
                        ? 'Sélectionnez à nouveau le fichier sujet de l’évaluation avant de publier.'
                        : 'Ajoutez le fichier sujet de l’évaluation avant de publier.';
                }
                $exam['drive_subject_link'] = null;
                $exam['drive_subject_name'] = $existingName !== '' ? $existingName : null;
                continue;
            }

            $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_OK) {
                $errors[] = 'Erreur d upload du sujet de l’évaluation (code ' . (string) $uploadError . ').';
                continue;
            }

            $tmpName = (string) ($upload['tmp_name'] ?? '');
            $size = max(0, (int) ($upload['size'] ?? 0));
            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                $errors[] = 'Fichier sujet invalide pour l’évaluation.';
                continue;
            }
            if ($maxBytes > 0 && $size > $maxBytes) {
                $errors[] = 'Le sujet de l’évaluation depasse ' . (string) $driveMaxMb . ' Mo.';
                continue;
            }

            $originalName = $drive->safeFilename((string) ($upload['name'] ?? 'sujet'));
            if ($originalName === '') {
                $originalName = 'sujet';
            }
            $mime = $this->detectUploadMime($tmpName, (string) ($upload['type'] ?? ''));
            $driveName = 'exam_sujet_' . $courseId . '_' . date('Ymd_His') . '_' . $originalName;
            if (strlen($driveName) > 220) {
                $driveName = substr($driveName, 0, 220);
            }

            $driveResult = $drive->uploadFile($tmpName, $driveName, $mime, $parentId, APP_DRIVE_PUBLIC_LINK);
            if (empty($driveResult['ok'])) {
                $driveError = $drive->errorMessage($driveResult);
                $errors[] = 'Upload Drive impossible pour le sujet de l’évaluation.' . ($driveError !== '' ? ' ' . $driveError : '');
                continue;
            }

            $driveLink = $this->driveResultLink($driveResult);
            if ($driveLink === '') {
                $errors[] = 'Lien Drive introuvable apres upload du sujet de l’évaluation.';
                continue;
            }

            $exam['drive_subject_link'] = $driveLink;
            $exam['drive_subject_name'] = $originalName;
            $exam['subject_file_name'] = $originalName;
        }
        unset($exam);
    }

    /**
     * @param array<string,mixed> $files
     * @return array{name:string,type:string,tmp_name:string,error:int,size:int}|null
     */
    private function examSubjectUpload(array $files): ?array
    {
        if (!isset($files['exam_subject_file']) || !is_array($files['exam_subject_file'])) {
            return null;
        }

        $file = $files['exam_subject_file'];
        return [
            'name' => (string) ($file['name'] ?? ''),
            'type' => (string) ($file['type'] ?? ''),
            'tmp_name' => (string) ($file['tmp_name'] ?? ''),
            'error' => (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int) ($file['size'] ?? 0),
        ];
    }

    /**
     * @param array<string,mixed> $files
     */
    private function hasExamSubjectUpload(array $files): bool
    {
        $upload = $this->examSubjectUpload($files);
        return $upload !== null && (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * @param array<string,mixed> $files
     * @return array{name:string,type:string,tmp_name:string,error:int,size:int}|null
     */
    private function lessonUploadByKey(array $files, string $fileKey): ?array
    {
        if ($fileKey === '' || !isset($files['lesson_files']) || !is_array($files['lesson_files'])) {
            return null;
        }

        $group = $files['lesson_files'];
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $field) {
            if (!isset($group[$field]) || !is_array($group[$field]) || !array_key_exists($fileKey, $group[$field])) {
                return null;
            }
        }

        return [
            'name' => (string) $group['name'][$fileKey],
            'type' => (string) $group['type'][$fileKey],
            'tmp_name' => (string) $group['tmp_name'][$fileKey],
            'error' => (int) $group['error'][$fileKey],
            'size' => (int) $group['size'][$fileKey],
        ];
    }

    private function detectUploadMime(string $tmpName, string $fallback): string
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

        $fallback = trim($fallback);
        return $fallback !== '' ? $fallback : 'application/octet-stream';
    }

    /**
     * @param array<string,mixed> $driveResult
     */
    private function driveResultLink(array $driveResult): string
    {
        $driveLink = (string) ($driveResult['webViewLink'] ?? '');
        if ($driveLink === '' && !empty($driveResult['webContentLink'])) {
            $driveLink = (string) $driveResult['webContentLink'];
        }
        if ($driveLink === '' && !empty($driveResult['id'])) {
            $id = (string) $driveResult['id'];
            if (str_starts_with($id, 'local:')) {
                return '';
            }
            $driveLink = 'https://drive.google.com/file/d/' . $id . '/view';
        }
        return $driveLink;
    }

    /**
     * @param array<int,string> $errors
     * @return array{exams:array<int,array<string,mixed>>}
     */
    private function parseExamBuilderPayload(string $raw, array &$errors, bool $requireCompleteQcm = false): array
    {
        if ($raw === '') {
            return ['exams' => []];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $errors[] = 'Structure des examens invalide.';
            return ['exams' => []];
        }

        $allExamsRaw = is_array($decoded['exams'] ?? null) ? array_values($decoded['exams']) : [];
        $payloadHasData = static function ($examRaw): bool {
            if (!is_array($examRaw)) {
                return false;
            }

            $type = strtolower(trim((string) ($examRaw['type'] ?? 'qcm')));
            if ($type !== 'qcm') {
                return true;
            }

            $title = trim((string) ($examRaw['title'] ?? ''));
            $titleKey = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
            if ($title !== '' && !in_array($titleKey, ['évaluation finale', 'examen 1'], true)) {
                return true;
            }
            foreach (['description', 'subject', 'subject_file_name', 'meeting_link', 'meeting_place'] as $field) {
                if (trim((string) ($examRaw[$field] ?? '')) !== '') {
                    return true;
                }
            }

            $versionsRaw = is_array($examRaw['versions'] ?? null) ? $examRaw['versions'] : [];
            foreach ([1, 2, 3] as $variant) {
                $questionsRaw = is_array($versionsRaw[(string) $variant] ?? null)
                    ? $versionsRaw[(string) $variant]
                    : (is_array($versionsRaw[$variant] ?? null) ? $versionsRaw[$variant] : []);
                foreach ($questionsRaw as $questionRaw) {
                    if (!is_array($questionRaw)) {
                        continue;
                    }
                    if (trim((string) ($questionRaw['text'] ?? '')) !== '') {
                        return true;
                    }
                    foreach ((is_array($questionRaw['options'] ?? null) ? $questionRaw['options'] : []) as $optionRaw) {
                        if (is_array($optionRaw) && trim((string) ($optionRaw['text'] ?? '')) !== '') {
                            return true;
                        }
                    }
                }
            }

            return false;
        };

        $examsRaw = [];
        foreach ($allExamsRaw as $examRaw) {
            if ($payloadHasData($examRaw)) {
                $examsRaw = [$examRaw];
                break;
            }
        }
        if ($examsRaw === [] && isset($allExamsRaw[0]) && is_array($allExamsRaw[0])) {
            $examsRaw = [$allExamsRaw[0]];
        }
        $exams = [];

        foreach ($examsRaw as $examIndex => $examRaw) {
            if (!is_array($examRaw)) {
                continue;
            }

            $title = $this->shortText((string) ($examRaw['title'] ?? ''), 255);
            $type = strtolower(trim((string) ($examRaw['type'] ?? 'qcm')));
            $description = $this->shortText((string) ($examRaw['description'] ?? ''), 2000);
            $durationMin = max(0, (int) ($examRaw['duration_min'] ?? 0));
            $minScore = max(0, (int) ($examRaw['min_score'] ?? 0));
            $pointsPerQuestion = max(1, (int) ($examRaw['points_per_question'] ?? 1));
            $maxAttempts = max(1, (int) ($examRaw['max_attempts'] ?? ($type === 'qcm' ? 3 : 1)));
            $versionCount = max(1, min(3, (int) ($examRaw['version_count'] ?? 3)));
            $meetingMode = strtolower(trim((string) ($examRaw['meeting_mode'] ?? 'online')));
            $meetingLink = trim((string) ($examRaw['meeting_link'] ?? ''));
            $meetingPlace = trim((string) ($examRaw['meeting_place'] ?? ''));
            $subject = trim((string) ($examRaw['subject'] ?? ''));
            $subjectFileName = $this->shortText((string) ($examRaw['subject_file_name'] ?? ''), 255);
            $titleKey = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
            $hasMeaningfulTitle = $title !== '' && !in_array($titleKey, ['évaluation finale', 'examen 1'], true);
            $hasExamData = $type !== 'qcm' || $hasMeaningfulTitle || $description !== '' || $subject !== '' || $subjectFileName !== '' || $meetingLink !== '' || $meetingPlace !== '';

            if (!in_array($type, ['qcm', 'drive', 'meeting'], true)) {
                $errors[] = 'Type d’évaluation invalide.';
                continue;
            }

            $versions = [1 => [], 2 => [], 3 => []];
            if ($type === 'qcm') {
                $versionsRaw = is_array($examRaw['versions'] ?? null) ? $examRaw['versions'] : [];
                foreach ([1, 2, 3] as $variant) {
                    $questionsRaw = is_array($versionsRaw[(string) $variant] ?? null)
                        ? $versionsRaw[(string) $variant]
                        : (is_array($versionsRaw[$variant] ?? null) ? $versionsRaw[$variant] : []);
                    foreach ($questionsRaw as $questionIndex => $questionRaw) {
                        if (!is_array($questionRaw)) {
                            continue;
                        }
                        $questionText = $this->shortText((string) ($questionRaw['text'] ?? ''), 2000);
                        $questionType = strtolower(trim((string) ($questionRaw['type'] ?? 'single')));
                        if (!in_array($questionType, ['single', 'multiple', 'text'], true)) {
                            $questionType = 'single';
                        }
                        $options = [];
                        $optionsRaw = is_array($questionRaw['options'] ?? null) ? $questionRaw['options'] : [];
                        foreach ($optionsRaw as $optionRaw) {
                            if (!is_array($optionRaw)) {
                                continue;
                            }
                            $optionText = $this->shortText((string) ($optionRaw['text'] ?? ''), 1000);
                            if ($optionText === '') {
                                continue;
                            }
                            $options[] = [
                                'text' => $optionText,
                                'correct' => !empty($optionRaw['correct']),
                            ];
                        }

                        if ($questionText === '' && $options === []) {
                            continue;
                        }
                        $hasExamData = true;

                        if ($questionText === '') {
                            $errors[] = 'Question ' . ((int) $questionIndex + 1) . ' sans intitulé dans la version ' . chr(64 + $variant) . '.';
                            continue;
                        }
                        if (in_array($questionType, ['single', 'multiple'], true)) {
                            if (count($options) < 2) {
                                $errors[] = 'La question "' . $questionText . '" doit avoir au moins deux options.';
                                continue;
                            }
                            $correctCount = count(array_filter($options, static fn(array $option): bool => !empty($option['correct'])));
                            if ($correctCount < 1) {
                                $errors[] = 'Marquez une réponse correcte pour la question "' . $questionText . '".';
                                continue;
                            }
                            if ($questionType === 'single' && $correctCount > 1) {
                                $errors[] = 'Une question à choix unique ne peut pas avoir plusieurs réponses correctes.';
                                continue;
                            }
                        }

                        $versions[$variant][] = [
                            'text' => $questionText,
                            'type' => $questionType,
                            'options' => $options,
                        ];
                    }
                }
            }

            if (!$hasExamData) {
                continue;
            }
            if ($title === '') {
                $title = 'Évaluation finale';
            }

            if ($type === 'qcm') {
                foreach ([1, 2, 3] as $variant) {
                    if ($variant > $versionCount) {
                        continue;
                    }
                    if ($requireCompleteQcm && $versions[$variant] === []) {
                        $errors[] = 'Version ' . chr(64 + $variant) . ' : ajoutez au moins une question.';
                    }
                }
            }

            if ($type === 'meeting' && !in_array($meetingMode, ['online', 'onsite'], true)) {
                $meetingMode = 'online';
            }

            $subjectLooksUploaded = preg_match('~^(https?://|storage/)~i', $subject) === 1;

            $exams[] = [
                'title' => $title,
                'type' => $type,
                'description' => $description !== '' ? $description : null,
                'duration_min' => $durationMin > 0 ? $durationMin : null,
                'max_attempts' => $type === 'qcm' ? 3 : $maxAttempts,
                'min_score' => $minScore,
                'points_per_question' => $pointsPerQuestion,
                'meeting_mode' => $type === 'meeting' ? $meetingMode : null,
                'meeting_link' => null,
                'meeting_place' => null,
                'drive_expected' => $type === 'drive' ? 1 : 0,
                'drive_subject_link' => $type === 'drive' && $subjectLooksUploaded ? $subject : null,
                'drive_subject_name' => $type === 'drive' ? ($subjectFileName !== '' ? $subjectFileName : ($subject !== '' && !$subjectLooksUploaded ? $subject : null)) : null,
                'subject_file_name' => $type === 'drive' ? $subjectFileName : '',
                'version_count' => $type === 'qcm' ? $versionCount : 0,
                'versions' => $versions,
            ];
        }

        return ['exams' => $exams];
    }

    /**
     * @param array{modules:array<int,array{title:string,description:?string,items:array<int,array<string,mixed>>}>,counts:array{modules:int,lessons:int,meetings:int}} $contentBuilder
     * @param array{exams:array<int,array<string,mixed>>} $examBuilder
     */
    private function persistCompleteBuilder(int $courseId, array $contentBuilder, array $examBuilder, bool $withVariant): void
    {
        $this->persistContentBuilder($courseId, $contentBuilder);
        $this->persistExamBuilder($courseId, $examBuilder, $withVariant);
    }

    /**
     * @param array{modules:array<int,array{title:string,description:?string,items:array<int,array<string,mixed>>}>,counts:array{modules:int,lessons:int,meetings:int}} $contentBuilder
     */
    private function persistContentBuilder(int $courseId, array $contentBuilder): void
    {
        $chapterPositions = 1;
        foreach ($contentBuilder['modules'] as $module) {
            $chapterId = $this->courses->insertBuilderChapter(
                $courseId,
                (string) $module['title'],
                $module['description'] ?? null,
                $chapterPositions++
            );

            $lessonPosition = 1;
            foreach ($module['items'] as $item) {
                $type = (string) ($item['type'] ?? 'video');
                if ($type === 'meeting') {
                    $meetingMode = (string) ($item['mode'] ?? 'online');
                    $this->courses->insertBuilderMeeting(
                        $courseId,
                        $chapterId,
                        (string) ($item['title'] ?? 'Rendez-vous'),
                        $meetingMode,
                        (int) ($item['duration_min'] ?? 60),
                        null,
                        null
                    );
                    continue;
                }

                $documentPath = trim((string) ($item['resource'] ?? ''));
                $this->courses->insertBuilderLesson(
                    $chapterId,
                    $type === 'document' ? 'document' : 'video',
                    (string) ($item['title'] ?? 'Leçon'),
                    $item['description'] ?? null,
                    $type === 'video' ? (string) ($item['resource'] ?? '') : null,
                    $type === 'document' && $documentPath !== '' ? $documentPath : null,
                    (int) ($item['duration_min'] ?? 0),
                    $lessonPosition++
                );
            }
        }
    }

    /**
     * @param array{exams:array<int,array<string,mixed>>} $examBuilder
     */
    private function persistExamBuilder(int $courseId, array $examBuilder, bool $withVariant): void
    {
        foreach ($examBuilder['exams'] as $exam) {
            $examId = $this->courses->insertBuilderExam($courseId, [
                'title' => (string) ($exam['title'] ?? 'Évaluation finale'),
                'description' => $exam['description'] ?? null,
                'type' => (string) ($exam['type'] ?? 'qcm'),
                'duration_min' => $exam['duration_min'] ?? null,
                'max_attempts' => $exam['max_attempts'] ?? ((string) ($exam['type'] ?? 'qcm') === 'qcm' ? 3 : 1),
                'min_score' => (int) ($exam['min_score'] ?? 0),
                'meeting_mode' => $exam['meeting_mode'] ?? null,
                'meeting_link' => $exam['meeting_link'] ?? null,
                'meeting_place' => $exam['meeting_place'] ?? null,
                'drive_expected' => $exam['drive_expected'] ?? 0,
                'drive_subject_link' => $exam['drive_subject_link'] ?? null,
                'drive_subject_name' => $exam['drive_subject_name'] ?? null,
            ]);

            if ((string) ($exam['type'] ?? '') !== 'qcm') {
                continue;
            }

            $points = max(1, (int) ($exam['points_per_question'] ?? 1));
            $versions = is_array($exam['versions'] ?? null) ? $exam['versions'] : [];
            $versionCount = max(1, min(3, (int) ($exam['version_count'] ?? 3)));
            for ($variant = 1; $variant <= $versionCount; $variant++) {
                $questions = is_array($versions[$variant] ?? null) ? $versions[$variant] : [];
                $order = 1;
                foreach ($questions as $question) {
                    if (!is_array($question)) {
                        continue;
                    }
                    $questionId = $this->courses->insertBuilderExamQuestion(
                        $examId,
                        $variant,
                        (string) ($question['text'] ?? ''),
                        (string) ($question['type'] ?? 'single'),
                        $order++,
                        $points,
                        $withVariant
                    );
                    $options = is_array($question['options'] ?? null) ? $question['options'] : [];
                    foreach ($options as $option) {
                        if (!is_array($option)) {
                            continue;
                        }
                        $this->courses->insertBuilderExamOption(
                            $questionId,
                            (string) ($option['text'] ?? ''),
                            !empty($option['correct'])
                        );
                    }
                }
            }
        }
    }

    private function shortText(string $value, int $maxLength): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', $value));
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }
        return substr($value, 0, $maxLength);
    }

    private function isYoutubeUrl(string $url): bool
    {
        $parts = parse_url(trim($url));
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) $parts['host']);
        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($host === 'youtu.be') {
            return $path !== '';
        }

        if (!in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            return false;
        }

        if ($path === 'watch') {
            $query = [];
            parse_str((string) ($parts['query'] ?? ''), $query);
            return trim((string) ($query['v'] ?? '')) !== '';
        }

        return (bool) preg_match('~^(embed|shorts|live)/[^/]+~i', $path);
    }

    private function isLocalRequest(): bool
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }
        if ($host !== '' && str_ends_with($host, '.local')) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $val
     * @return array<int,string>
     */
    private function normalizeListField($val): array
    {
        if (!is_array($val)) {
            $val = [$val];
        }
        $val = array_map(static fn($x): string => trim((string) $x), $val);
        $val = array_filter($val, static fn(string $x): bool => $x !== '');
        return array_values($val);
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

    private function normalizeGeneralTempPath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || strpos($path, '..') !== false) {
            return '';
        }
        if (strpos($path, 'storage/uploads/general_tmp/') !== 0) {
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
}
