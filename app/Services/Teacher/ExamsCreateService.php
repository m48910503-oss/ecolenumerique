<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;
use App\Models\TeacherExamsCreateModel;
use App\Services\Drive\DriveGateway;

final class ExamsCreateService extends Service 
{
    private const DRIVE_ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf', 'jpg', 'jpeg', 'png', 'webp', 'zip', 'rar', 'csv'];
    private const DRIVE_ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-rar-compressed',
    ];

    private \PDO $pdo;
    private TeacherExamsCreateModel $model;

    public function __construct(\PDO $pdo, TeacherExamsCreateModel $model)
    {
        $this->pdo = $pdo;
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{redirectTo:?string,httpStatus:?int,abortMessage:?string,view:array<string,mixed>}
     */
    public function handle(array $user, bool $isAdmin, string $method, array $get, array $post, array $files): array
    {
        $drive = new DriveGateway();
        $courseId = (int) ($get['course_id'] ?? 0);
        if ($courseId < 1) {
            return $this->abort(404, 'Cours introuvable.');
        }

        $course = $this->model->findCourseById($courseId);
        if (!$course) {
            return $this->abort(404, 'Cours introuvable.');
        }
        if (!$isAdmin && (int) ($course['teacher_id'] ?? 0) !== (int) $user['id']) {
            return $this->abort(403, "Acc\u{00e8}s refus\u{00e9}.");
        }

        $return = (string) ($get['return'] ?? 'content');
        $backStep = trim((string) ($get['back_step'] ?? ''));
        if ($backStep !== '' && preg_match('/^[a-z0-9_-]+$/i', $backStep) !== 1) {
            $backStep = '';
        }
        $returnUrl = $return === 'edit'
            ? $this->siteUrl('teacher/course_edit?id=' . $courseId)
            : $this->siteUrl('teacher/course_content?id=' . $courseId . ($backStep !== '' ? '&step=' . rawurlencode($backStep) : ''));

        $isLocked = in_array((string) ($course['status'] ?? ''), ['submitted'], true);
        $existingExam = $this->model->findLatestExamByCourse($courseId);
        $isEdit = $existingExam !== null;

        $driveUpload = $drive->uploadUiState();
        $driveReady = !empty($driveUpload['ready']);
        $driveNeedsConnect = !empty($driveUpload['needsConnect']);
        $driveRemoteReady = !empty($driveUpload['remoteReady']);
        $driveUploadNote = (string) ($driveUpload['note'] ?? '');
        $driveMaxMb = APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25;
        $hasDriveSubjectColumns = $this->schema()->ensureExamSessionsDriveSubjectColumns();
        $hasSettingsColumn = $this->schema()->ensureExamSessionsSettingsJsonColumn();

        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $warning = '';

        $defaultSettings = $this->defaultExamSettings();
        $examSettings = $this->decodeExamSettings((string) ($existingExam['settings_json'] ?? ''), $defaultSettings);
        if ($method === 'POST') {
            $examSettings = $this->parsePostedSettings((string) ($post['settings_json'] ?? ''), $defaultSettings, $errors);
        }

        $title = trim((string) ($post['title'] ?? ($existingExam['title'] ?? '')));
        $type = (string) ($post['type'] ?? ($existingExam['type'] ?? 'qcm'));
        $description = trim((string) ($post['description'] ?? ($existingExam['description'] ?? '')));
        $duration = (int) ($post['duration_min'] ?? ($existingExam['duration_min'] ?? 0));
        $maxAttempts = max(1, (int) ($post['max_attempts'] ?? ($existingExam['max_attempts'] ?? 1)));
        $minScore = max(0, (int) ($post['min_score'] ?? ($existingExam['min_score'] ?? 0)));
        $pointsPerQuestion = (int) ($post['points_per_question'] ?? 0);
        $meetingMode = (string) ($post['meeting_mode'] ?? ($existingExam['meeting_mode'] ?? 'online'));
        $meetingLink = trim((string) ($post['meeting_link'] ?? ($existingExam['meeting_link'] ?? '')));
        $meetingPlace = trim((string) ($post['meeting_place'] ?? ($existingExam['meeting_place'] ?? '')));
        $driveSubjectLink = (string) ($existingExam['drive_subject_link'] ?? '');
        $driveSubjectName = (string) ($existingExam['drive_subject_name'] ?? '');
        if ($type === 'qcm') {
            $maxAttempts = 3;
        }

        $questionsByVariant = $this->parseQuestionsByVariant((string) ($post['questions_json'] ?? ''));
        $attemptCount = 0;
        if ($existingExam) {
            try {
                $attemptCount = $this->model->countAttemptsByExamId((int) $existingExam['id']);
            } catch (\Throwable $e) {
                $attemptCount = 0;
            }
        }

        $initialQuestionsByVariant = [1 => [], 2 => [], 3 => []];
        if ($method === 'POST') {
            $initialQuestionsByVariant = $questionsByVariant;
        } elseif ($existingExam && (string) ($existingExam['type'] ?? '') === 'qcm') {
            $initialQuestionsByVariant = $this->loadQuestionsForInitialState((int) $existingExam['id']);
        }

        if ($pointsPerQuestion < 1) {
            foreach ([1, 2, 3] as $variant) {
                if (!empty($initialQuestionsByVariant[$variant][0]['points'])) {
                    $pointsPerQuestion = (int) $initialQuestionsByVariant[$variant][0]['points'];
                    break;
                }
            }
        }
        $pointsPerQuestion = max(1, $pointsPerQuestion);

        if ($method === 'POST') {
            if ($type === 'drive' || $type === 'meeting') {
                $maxAttempts = 1;
            } elseif ($type === 'qcm') {
                $maxAttempts = 3;
            }
            $this->handlePostValidation(
                $errors,
                $warning,
                $isLocked,
                $type,
                $title,
                $meetingMode,
                $meetingLink,
                $meetingPlace,
                $hasDriveSubjectColumns,
                $driveReady,
                $driveNeedsConnect,
                $files,
                $driveMaxMb,
                $driveSubjectLink,
                $driveSubjectName,
                $hasSettingsColumn,
                $examSettings,
                $isEdit,
                $attemptCount,
                $existingExam,
                $questionsByVariant,
                $pointsPerQuestion,
                $courseId,
                $description,
                $duration,
                $maxAttempts,
                $minScore,
                $success
            );
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            $returnParam = $return !== '' ? '&return=' . urlencode($return) : '';
            if ($backStep !== '') {
                $returnParam .= '&back_step=' . urlencode($backStep);
            }
            return [
                'redirectTo' => $this->siteUrl('teacher/exams_create?course_id=' . $courseId . $returnParam),
                'httpStatus' => null,
                'abortMessage' => null,
                'view' => [],
            ];
        }

        $pageTitle = $isEdit ? 'Modifier un examen' : 'Creer un examen';
        return [
            'redirectTo' => null,
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [
                'course' => $course,
                'returnUrl' => $returnUrl,
                'isLocked' => $isLocked,
                'isEdit' => $isEdit,
                'driveReady' => $driveReady,
                'driveNeedsConnect' => $driveNeedsConnect,
                'driveConnectRecommended' => $driveNeedsConnect && !$driveRemoteReady,
                'driveUploadNote' => $driveUploadNote,
                'driveMaxMb' => $driveMaxMb,
                'errors' => $errors,
                'success' => $success,
                'warning' => $warning,
                'title' => $title,
                'type' => $type,
                'description' => $description,
                'duration' => $duration,
                'maxAttempts' => $maxAttempts,
                'minScore' => $minScore,
                'pointsPerQuestion' => $pointsPerQuestion,
                'meetingMode' => $meetingMode,
                'meetingLink' => $meetingLink,
                'meetingPlace' => $meetingPlace,
                'driveSubjectLink' => $driveSubjectLink,
                'driveSubjectName' => $driveSubjectName,
                'attemptCount' => $attemptCount,
                'initialQuestionsByVariant' => $initialQuestionsByVariant,
                'hasSettingsColumn' => $hasSettingsColumn,
                'examSettings' => $examSettings,
                'examTemplates' => $this->buildExamTemplates($courseId, $hasSettingsColumn, $defaultSettings),
                'pageTitle' => $pageTitle,
            ],
        ];
    }

    /**
     * @param array<int,string> $errors
     * @param array<string,mixed>|null $existingExam
     * @param array{1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:array<int,array<string,mixed>>} $questionsByVariant
     * @param array<string,mixed> $examSettings
     */
    private function handlePostValidation(
        array &$errors,
        string &$warning,
        bool $isLocked,
        string &$type,
        string $title,
        string $meetingMode,
        string $meetingLink,
        string $meetingPlace,
        bool $hasDriveSubjectColumns,
        bool $driveReady,
        bool $driveNeedsConnect,
        array $files,
        int $driveMaxMb,
        string &$driveSubjectLink,
        string &$driveSubjectName,
        bool $hasSettingsColumn,
        array $examSettings,
        bool $isEdit,
        int $attemptCount,
        ?array $existingExam,
        array $questionsByVariant,
        int $pointsPerQuestion,
        int $courseId,
        string $description,
        int $duration,
        int $maxAttempts,
        int $minScore,
        string &$success
    ): void {
        if ($isLocked) {
            $errors[] = "Cours verrouill\u{00e9} (soumis) : modification de l'examen non autoris\u{00e9}e.";
        }
        if (!in_array($type, ['qcm', 'drive', 'meeting'], true)) {
            $errors[] = 'Type invalide.';
        }
        if ($title === '') {
            $errors[] = 'Le titre est requis.';
        }

        $errors = array_merge($errors, $this->validateExamSettings($examSettings));

        if ($type === 'meeting') {
            if (!in_array($meetingMode, ['online', 'onsite'], true)) {
                $errors[] = 'Mode de rendez-vous invalide.';
            }
        }

        if ($type === 'drive') {
            if (!$hasDriveSubjectColumns) {
                $errors[] = "Mise \u{00e0} jour de la base requise pour le sujet Drive.";
            }

            $subjectFile = is_array($files['drive_subject_file'] ?? null) ? $files['drive_subject_file'] : null;
            $subjectError = (int) ($subjectFile['error'] ?? UPLOAD_ERR_NO_FILE);
            $hasSubjectFile = $subjectError !== UPLOAD_ERR_NO_FILE;

            if (!$hasSubjectFile && $driveSubjectLink === '') {
                $errors[] = 'Le fichier sujet est obligatoire.';
            }
            if ($hasSubjectFile && $subjectError !== UPLOAD_ERR_OK) {
                $errors[] = "Erreur d'upload du sujet (code " . (string) $subjectError . ').';
            }

            if ($hasSubjectFile && $errors === []) {
                $subjectTmp = (string) ($subjectFile['tmp_name'] ?? '');
                $subjectSize = (int) ($subjectFile['size'] ?? 0);
                if ($subjectTmp === '' || !is_uploaded_file($subjectTmp)) {
                    $errors[] = 'Fichier sujet invalide.';
                }
                $maxBytes = (int) $driveMaxMb * 1024 * 1024;
                if ($maxBytes > 0 && $subjectSize > $maxBytes) {
                    $errors[] = "Le sujet d\u{00e9}passe " . (string) $driveMaxMb . ' Mo.';
                }

                if ($errors === []) {
                    $drive = new DriveGateway();
                    $subjectOriginal = $drive->safeFilename((string) ($subjectFile['name'] ?? 'sujet'));
                    $subjectMime = $this->detectMimeType($subjectTmp, (string) ($subjectFile['type'] ?? ''));
                    if (!$this->isAllowedDriveUpload($subjectOriginal, $subjectMime)) {
                        $errors[] = "Type de fichier non autoris\u{00e9} pour le sujet Drive.";
                    }
                    if ($errors === []) {
                        $prefix = 'exam_sujet_' . $courseId . '_' . date('Ymd_His');
                        $driveName = $prefix . '_' . $subjectOriginal;
                        if (strlen($driveName) > 220) {
                            $driveName = substr($driveName, 0, 220);
                        }
                        $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
                        $driveResult = $drive->uploadFile($subjectTmp, $driveName, $subjectMime, $parentId, APP_DRIVE_PUBLIC_LINK);
                        if (empty($driveResult['ok'])) {
                            $driveError = $drive->errorMessage($driveResult);
                            $errors[] = 'Upload Drive impossible pour le sujet.' . ($driveError !== '' ? ' ' . $driveError : '');
                        } else {
                            $driveSubjectLink = (string) ($driveResult['webViewLink'] ?? '');
                            if ($driveSubjectLink === '' && !empty($driveResult['webContentLink'])) {
                                $driveSubjectLink = (string) $driveResult['webContentLink'];
                            }
                            if ($driveSubjectLink === '' && !empty($driveResult['id'])) {
                                $driveSubjectLink = 'https://drive.google.com/file/d/' . (string) $driveResult['id'] . '/view';
                            }
                            $driveSubjectName = $subjectOriginal;
                        }
                    }
                }
            }
        }

        if ($isEdit && $attemptCount > 0 && $type !== (string) ($existingExam['type'] ?? $type)) {
            $errors[] = "Impossible de changer le type : des tentatives existent d\u{00e9}j\u{00e0}.";
        }
        if ($type === 'qcm') {
            $errors = array_merge($errors, $this->validateQcmPayload($questionsByVariant, $pointsPerQuestion));
        }

        if ($errors !== []) {
            return;
        }

        try {
            $this->pdo->beginTransaction();
            $wasEdit = $isEdit;
            $examId = $this->saveExamSession(
                $courseId,
                $existingExam,
                $type,
                $title,
                $description,
                $duration,
                $maxAttempts,
                $minScore,
                $meetingMode,
                $meetingLink,
                $meetingPlace,
                $hasDriveSubjectColumns,
                $hasSettingsColumn,
                $driveSubjectLink,
                $driveSubjectName,
                $examSettings
            );
            if ($type === 'qcm') {
                if ($wasEdit && $attemptCount > 0) {
                    $warning = "Des tentatives existent d\u{00e9}j\u{00e0} : les questions du QCM n'ont pas \u{00e9}t\u{00e9} modifi\u{00e9}es.";
                } else {
                    $this->saveQcmQuestions($examId, $questionsByVariant, $pointsPerQuestion, $wasEdit);
                }
            }
            if (!$hasSettingsColumn) {
                $warning = "Les r\u{00e9}glages avanc\u{00e9}s ne sont pas sauvegard\u{00e9}s tant que la migration v39 n'est pas appliqu\u{00e9}e.";
            }
            $this->pdo->commit();
            $success = $wasEdit ? "Examen modifi\u{00e9} avec succ\u{00e8}s." : "Examen cr\u{00e9}\u{00e9} avec succ\u{00e8}s.";
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $errors[] = "Erreur lors de l'enregistrement de l'examen : " . $e->getMessage();
        }
    }

    /**
     * @return array{1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:array<int,array<string,mixed>>}
     */
    private function parseQuestionsByVariant(string $questionsJson): array
    {
        $by = [1 => [], 2 => [], 3 => []];
        if ($questionsJson === '') {
            return $by;
        }
        $decoded = json_decode($questionsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $by;
        }
        if (isset($decoded['variants']) && is_array($decoded['variants'])) {
            foreach ([1, 2, 3] as $variant) {
                $list = $decoded['variants'][(string) $variant] ?? $decoded['variants'][$variant] ?? [];
                $by[$variant] = is_array($list) ? $list : [];
            }
            return $by;
        }
        $by[1] = $decoded;
        return $by;
    }

    /**
     * @return array{1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:array<int,array<string,mixed>>}
     */
    private function loadQuestionsForInitialState(int $examId): array
    {
        $initial = [1 => [], 2 => [], 3 => []];
        $hasVariant = $this->schema()->ensureExamQuestionsVariantColumn();
        $questions = $this->model->fetchQuestionsByExamId($examId, $hasVariant);
        if ($questions === []) {
            return $initial;
        }

        $questionIds = array_map(static fn(array $r): int => (int) ($r['id'] ?? 0), $questions);
        $optsByQ = [];
        foreach ($this->model->fetchOptionsByQuestionIds($questionIds) as $opt) {
            $qid = (int) ($opt['question_id'] ?? 0);
            if ($qid < 1) {
                continue;
            }
            $optsByQ[$qid][] = ['text' => (string) ($opt['option_text'] ?? ''), 'correct' => !empty($opt['is_correct'])];
        }

        foreach ($questions as $q) {
            $variant = $hasVariant ? (int) ($q['variant'] ?? 1) : 1;
            if ($variant < 1 || $variant > 3) {
                $variant = 1;
            }
            $qid = (int) ($q['id'] ?? 0);
            $initial[$variant][] = [
                'text' => (string) ($q['question_text'] ?? ''),
                'type' => (string) ($q['question_type'] ?? 'single'),
                'points' => (int) ($q['points'] ?? 1),
                'options' => $optsByQ[$qid] ?? [],
            ];
        }

        return $initial;
    }

    /** @param array{1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:array<int,array<string,mixed>>} $by */
    private function validateQcmPayload(array $by, int $pointsPerQuestion): array
    {
        $errors = [];
        if ($pointsPerQuestion < 1) {
            $errors[] = 'Points par question : au moins 1 point.';
        }
        $labels = [1 => 'Version A', 2 => 'Version B', 3 => 'Version C'];
        foreach ([1, 2, 3] as $variant) {
            $list = $by[$variant] ?? [];
            if ($list === []) {
                $errors[] = $labels[$variant] . ' : ajoutez au moins une question.';
                continue;
            }
            foreach ($list as $idx => $q) {
                $qText = trim((string) ($q['text'] ?? ''));
                $qType = (string) ($q['type'] ?? 'single');
                $options = is_array($q['options'] ?? null) ? $q['options'] : [];
                if ($qText === '') {
                    $errors[] = $labels[$variant] . ' - Question ' . ($idx + 1) . ' : le texte est requis.';
                }
                if (in_array($qType, ['single', 'multiple'], true)) {
                    $filled = array_values(array_filter($options, static fn($opt): bool => trim((string) ($opt['text'] ?? '')) !== ''));
                    if (count($filled) < 2) {
                        $errors[] = $labels[$variant] . ' - Question ' . ($idx + 1) . ' : au moins 2 options.';
                    }
                    $correct = 0;
                    foreach ($filled as $opt) {
                        if (!empty($opt['correct'])) {
                            $correct++;
                        }
                    }
                    if ($correct === 0) {
                        $errors[] = $labels[$variant] . ' - Question ' . ($idx + 1) . ' : marquez au moins une option correcte.';
                    }
                    if ($qType === 'single' && $correct > 1) {
                        $errors[] = $labels[$variant] . ' - Question ' . ($idx + 1) . ' : une seule option correcte en mode choix unique.';
                    }
                }
            }
        }
        return $errors;
    }

    /**
     * @param array<string,mixed>|null $existingExam
     * @param array<string,mixed> $examSettings
     */
    private function saveExamSession(
        int $courseId,
        ?array $existingExam,
        string $type,
        string $title,
        string $description,
        int $duration,
        int $maxAttempts,
        int $minScore,
        string $meetingMode,
        string $meetingLink,
        string $meetingPlace,
        bool $withDriveSubject,
        bool $withSettings,
        string $driveSubjectLink,
        string $driveSubjectName,
        array $examSettings
    ): int {
        $payload = [
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'type' => $type,
            'duration_min' => $duration > 0 ? $duration : null,
            'max_attempts' => $maxAttempts,
            'min_score' => $minScore,
            'meeting_mode' => $type === 'meeting' ? $meetingMode : null,
            'meeting_link' => $type === 'meeting' ? ($meetingMode === 'online' ? ($meetingLink !== '' ? $meetingLink : null) : null) : null,
            'meeting_place' => $type === 'meeting' ? ($meetingMode === 'onsite' ? ($meetingPlace !== '' ? $meetingPlace : null) : null) : null,
            'drive_expected' => $type === 'drive' ? 1 : 0,
            'drive_subject_link' => $type === 'drive' ? ($driveSubjectLink !== '' ? $driveSubjectLink : null) : null,
            'drive_subject_name' => $type === 'drive' ? ($driveSubjectName !== '' ? $driveSubjectName : null) : null,
        ];
        if ($withSettings) {
            $json = json_encode($examSettings, JSON_UNESCAPED_UNICODE);
            $payload['settings_json'] = $json !== false ? $json : null;
        }

        if ($existingExam) {
            $examId = (int) ($existingExam['id'] ?? 0);
            if ($withDriveSubject && $withSettings) {
                $this->model->updateExamWithDriveSubjectAndSettings($examId, $courseId, $payload);
            } elseif ($withDriveSubject) {
                $this->model->updateExamWithDriveSubject($examId, $courseId, $payload);
            } elseif ($withSettings) {
                $this->model->updateExamWithoutDriveSubjectAndSettings($examId, $courseId, $payload);
            } else {
                $this->model->updateExamWithoutDriveSubject($examId, $courseId, $payload);
            }
            return $examId;
        }
        if ($withDriveSubject && $withSettings) {
            return $this->model->insertExamWithDriveSubjectAndSettings($courseId, $payload);
        }
        if ($withDriveSubject) {
            return $this->model->insertExamWithDriveSubject($courseId, $payload);
        }
        if ($withSettings) {
            return $this->model->insertExamWithoutDriveSubjectAndSettings($courseId, $payload);
        }
        return $this->model->insertExamWithoutDriveSubject($courseId, $payload);
    }

    /** @param array{1:array<int,array<string,mixed>>,2:array<int,array<string,mixed>>,3:array<int,array<string,mixed>>} $by */
    private function saveQcmQuestions(int $examId, array $by, int $pointsPerQuestion, bool $wasEdit): void
    {
        $hasVariant = $this->schema()->ensureExamQuestionsVariantColumn();
        if ($wasEdit) {
            $this->model->deleteQuestionsByExamId($examId);
        }
        foreach ([1, 2, 3] as $variant) {
            $order = 1;
            foreach ($by[$variant] ?? [] as $q) {
                $qText = trim((string) ($q['text'] ?? ''));
                if ($qText === '') {
                    continue;
                }
                $qType = (string) ($q['type'] ?? 'single');
                if (!in_array($qType, ['single', 'multiple', 'text'], true)) {
                    $qType = 'single';
                }
                $qid = $this->model->insertQuestion($examId, $variant, $qText, $qType, $order++, $pointsPerQuestion, $hasVariant);
                $options = is_array($q['options'] ?? null) ? $q['options'] : [];
                foreach ($options as $opt) {
                    $text = trim((string) ($opt['text'] ?? ''));
                    if ($text === '') {
                        continue;
                    }
                    $this->model->insertOption($qid, $text, !empty($opt['correct']));
                }
            }
        }
    }

    /** @param array<string,mixed> $defaults @return array<string,mixed> */
    private function parsePostedSettings(string $rawJson, array $defaults, array &$errors): array
    {
        if (trim($rawJson) === '') {
            return $defaults;
        }
        $decoded = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $errors[] = "R\u{00e9}glages avanc\u{00e9}s invalides.";
            return $defaults;
        }
        return $this->sanitizeExamSettings($decoded, $defaults);
    }

    /** @param array<string,mixed> $defaults @return array<string,mixed> */
    private function decodeExamSettings(string $rawJson, array $defaults): array
    {
        if (trim($rawJson) === '') {
            return $defaults;
        }
        $decoded = json_decode($rawJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $defaults;
        }
        return $this->sanitizeExamSettings($decoded, $defaults);
    }

    /** @param array<string,mixed> $settings */
    private function validateExamSettings(array $settings): array
    {
        $errors = [];
        $availability = is_array($settings['availability'] ?? null) ? $settings['availability'] : [];
        $enabled = !empty($availability['enabled']);
        $startAt = trim((string) ($availability['start_at'] ?? ''));
        $endAt = trim((string) ($availability['end_at'] ?? ''));
        if ($enabled && $startAt === '' && $endAt === '') {
            $errors[] = "R\u{00e9}glez au moins une borne de disponibilit\u{00e9} (d\u{00e9}but ou fin).";
        }
        if ($startAt !== '' && $endAt !== '') {
            $startTs = strtotime(str_replace('T', ' ', $startAt));
            $endTs = strtotime(str_replace('T', ' ', $endAt));
            if ($startTs !== false && $endTs !== false && $startTs >= $endTs) {
                $errors[] = "Fen\u{00ea}tre de disponibilit\u{00e9} invalide (d\u{00e9}but >= fin).";
            }
        }
        return $errors;
    }

    /** @param array<string,mixed> $raw @param array<string,mixed> $defaults @return array<string,mixed> */
    private function sanitizeExamSettings(array $raw, array $defaults): array
    {
        $settings = $defaults;

        $rnd = is_array($raw['randomization'] ?? null) ? $raw['randomization'] : [];
        $strategy = (string) ($rnd['variant_strategy'] ?? $defaults['randomization']['variant_strategy']);
        if (!in_array($strategy, ['sequential', 'seeded_random', 'fixed_a', 'fixed_b', 'fixed_c'], true)) {
            $strategy = 'sequential';
        }
        $settings['randomization']['question_shuffle'] = !empty($rnd['question_shuffle']);
        $settings['randomization']['option_shuffle'] = !empty($rnd['option_shuffle']);
        $settings['randomization']['variant_strategy'] = $strategy;

        $scoring = is_array($raw['scoring'] ?? null) ? $raw['scoring'] : [];
        $penalty = (float) ($scoring['wrong_penalty'] ?? 0.0);
        $penalty = max(0.0, min(10.0, $penalty));
        $settings['scoring']['multiple_partial_credit'] = !empty($scoring['multiple_partial_credit']);
        $settings['scoring']['wrong_penalty'] = $penalty;

        $availability = is_array($raw['availability'] ?? null) ? $raw['availability'] : [];
        $settings['availability']['enabled'] = !empty($availability['enabled']);
        $settings['availability']['start_at'] = $this->normalizeDateTimeInput((string) ($availability['start_at'] ?? ''));
        $settings['availability']['end_at'] = $this->normalizeDateTimeInput((string) ($availability['end_at'] ?? ''));

        $anti = is_array($raw['anti_cheat'] ?? null) ? $raw['anti_cheat'] : [];
        $settings['anti_cheat']['fullscreen_required'] = !empty($anti['fullscreen_required']);
        $settings['anti_cheat']['copy_paste_block'] = !empty($anti['copy_paste_block']);
        $settings['anti_cheat']['tab_switch_warning'] = !empty($anti['tab_switch_warning']);
        $settings['anti_cheat']['camera_required'] = !empty($anti['camera_required']);

        return $settings;
    }

    /** @return array<string,mixed> */
    private function defaultExamSettings(): array
    {
        return [
            'randomization' => ['question_shuffle' => true, 'option_shuffle' => true, 'variant_strategy' => 'sequential'],
            'scoring' => ['multiple_partial_credit' => false, 'wrong_penalty' => 0.0],
            'availability' => ['enabled' => false, 'start_at' => '', 'end_at' => ''],
            'anti_cheat' => ['fullscreen_required' => false, 'copy_paste_block' => false, 'tab_switch_warning' => false, 'camera_required' => false],
        ];
    }

    /** @param array<string,mixed> $defaults @return array<int,array<string,mixed>> */
    private function buildExamTemplates(int $courseId, bool $hasSettingsColumn, array $defaults): array
    {
        $rows = $this->model->fetchExamSummariesByCourse($courseId, 12);
        $out = [];
        foreach ($rows as $row) {
            $examId = (int) ($row['id'] ?? 0);
            if ($examId < 1) {
                continue;
            }
            $type = (string) ($row['type'] ?? 'qcm');
            $questionsByVariant = [1 => [], 2 => [], 3 => []];
            if ($type === 'qcm') {
                $questionsByVariant = $this->loadQuestionsForInitialState($examId);
            }
            $points = 1;
            foreach ([1, 2, 3] as $variant) {
                if (!empty($questionsByVariant[$variant][0]['points'])) {
                    $points = max(1, (int) $questionsByVariant[$variant][0]['points']);
                    break;
                }
            }
            $settings = $defaults;
            if ($hasSettingsColumn) {
                $settings = $this->decodeExamSettings((string) ($row['settings_json'] ?? ''), $defaults);
            }
            $out[] = [
                'id' => $examId,
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'type' => $type,
                'duration_min' => (int) ($row['duration_min'] ?? 0),
                'max_attempts' => max(1, (int) ($row['max_attempts'] ?? 1)),
                'min_score' => max(0, (int) ($row['min_score'] ?? 0)),
                'meeting_mode' => (string) ($row['meeting_mode'] ?? 'online'),
                'meeting_link' => (string) ($row['meeting_link'] ?? ''),
                'meeting_place' => (string) ($row['meeting_place'] ?? ''),
                'points_per_question' => $points,
                'questions_by_variant' => $questionsByVariant,
                'settings' => $settings,
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }
        return $out;
    }

    private function normalizeDateTimeInput(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $normalized = str_replace('T', ' ', $value);
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}(:\\d{2})?$/', $normalized) !== 1) {
            return '';
        }
        $ts = strtotime($normalized);
        if ($ts === false) {
            return '';
        }
        return date('Y-m-d\TH:i', $ts);
    }

    private function isAllowedDriveUpload(string $filename, string $mimeType): bool
    {
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, self::DRIVE_ALLOWED_EXTENSIONS, true)) {
            return false;
        }
        $mime = trim(strtolower($mimeType));
        if ($mime === '' || $mime === 'application/octet-stream') {
            return true;
        }
        return in_array($mime, self::DRIVE_ALLOWED_MIMES, true);
    }

    private function detectMimeType(string $tmpName, string $fallbackMime): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $tmpName);
                if (is_string($detected) && $detected !== '') {
                    finfo_close($finfo);
                    return $detected;
                }
                finfo_close($finfo);
            }
        }
        if ($fallbackMime !== '') {
            return $fallbackMime;
        }
        return 'application/octet-stream';
    }

    /** @return array{redirectTo:?string,httpStatus:?int,abortMessage:string,view:array<string,mixed>} */
    private function abort(int $status, string $message): array
    {
        return ['redirectTo' => null, 'httpStatus' => $status, 'abortMessage' => $message, 'view' => []];
    }
}
