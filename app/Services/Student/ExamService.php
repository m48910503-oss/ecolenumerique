<?php
declare(strict_types=1);

namespace App\Services\Student;

use App\Core\Service;
 
use App\Models\ExamModel;
use App\Services\Drive\DriveGateway;
use App\Services\Shared\CertificateOpsService;

final class ExamService extends Service
{
    private \PDO $pdo;
    private ExamModel $exams;
    private CertificateOpsService $certificates;
    private DriveGateway $drive;

    public function __construct(
        \PDO $pdo,
        ExamModel $exams,
        CertificateOpsService $certificates,
        ?DriveGateway $drive = null
    )
    {
        $this->pdo = $pdo;
        $this->exams = $exams;
        $this->certificates = $certificates;
        $this->drive = $drive ?? new DriveGateway();
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @param array<string,mixed> $files
     * @return array{
     *   redirectTo:?string,
     *   httpStatus:?int,
     *   abortMessage:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(int $examId, array $user, string $method, array $post, array $files): array
    {
        if ($examId < 1) {
            return $this->abort(404, 'Examen introuvable.');
        }

        $exam = $this->exams->findExamById($examId);
        if (!$exam) {
            return $this->abort(404, 'Examen introuvable.');
        }

        $roles = json_decode((string) ($user['roles_json'] ?? '[]'), true) ?: [];
        $isAdmin = in_array('admin', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);
        $isCourseOwner = $isTeacher && (int) ($exam['teacher_id'] ?? 0) === (int) $user['id'];

        if (!$isAdmin && !$isCourseOwner) {
            if (!$this->exams->isUserEnrolledInCourse((int) $user['id'], (int) $exam['course_id'])) {
                return $this->abort(403, "Acc\u{00e8}s refus\u{00e9} (inscription requise).");
            }
        }

        $errors = [];
        $success = (string) $this->flashGet('success', '');
        $examType = (string) ($exam['type'] ?? 'qcm');
        $maxAttempts = max(1, (int) ($exam['max_attempts'] ?? 1));
        $minScore = max(0, (int) ($exam['min_score'] ?? 0));
        $examSettings = $this->parseExamSettings((string) ($exam['settings_json'] ?? ''));
        $randomizationSettings = is_array($examSettings['randomization'] ?? null) ? $examSettings['randomization'] : [];
        $scoringSettings = is_array($examSettings['scoring'] ?? null) ? $examSettings['scoring'] : [];
        $availabilitySettings = is_array($examSettings['availability'] ?? null) ? $examSettings['availability'] : [];
        $antiCheatSettings = is_array($examSettings['anti_cheat'] ?? null) ? $examSettings['anti_cheat'] : [];
        $availabilityBlocked = false;
        $availabilityBlockedReason = '';
        $driveUpload = $examType === 'drive'
            ? $this->drive->uploadUiState()
            : ['ready' => false, 'remoteReady' => false, 'localReady' => false, 'needsConnect' => false, 'note' => ''];
        $driveReady = !empty($driveUpload['ready']);
        $driveUploadNote = (string) ($driveUpload['note'] ?? '');
        $driveMaxMb = APP_DRIVE_MAX_UPLOAD_MB > 0 ? APP_DRIVE_MAX_UPLOAD_MB : 25;

        $attemptsHasMeetingId = true;
        $attemptsHasVariantIndex = false;
        if ($examType === 'qcm') {
            $attemptsHasVariantIndex = $this->schema()->ensureExamAttemptsVariantIndexColumn();
        }
        if ($examType === 'meeting') {
            $attemptsHasMeetingId = $this->schema()->ensureExamAttemptsMeetingIdColumn();
        }

        $attemptCount = 0;
        $lastAttempt = null;
        $hasPassedAttempt = false;
        try {
            $attemptCount = $this->exams->countAttempts($examId, (int) $user['id']);
            $lastAttempt = $this->exams->findLastAttempt($examId, (int) $user['id']);
            $hasPassedAttempt = $this->exams->countPassedAttempts($examId, (int) $user['id'], $minScore) > 0;
        } catch (\Throwable $e) {
            $attemptCount = 0;
            $lastAttempt = null;
            $hasPassedAttempt = false;
        }
        $lastAttemptStatus = is_array($lastAttempt) ? (string) ($lastAttempt['status'] ?? '') : '';

        $certificateCode = null;
        if ($this->certificates->hasCertificatesTable()) {
            try {
                $certificateCode = $this->exams->findCertificateCode((int) $user['id'], (int) $exam['course_id']);
            } catch (\Throwable $e) {
                $certificateCode = null;
            }
        }

        $questions = [];
        $questionsByVariant = [1 => [], 2 => [], 3 => []];
        $maxScore = 0;
        $maxScoreByVariant = [1 => 0, 2 => 0, 3 => 0];
        $hasTextQuestions = false;
        $hasTextByVariant = [1 => false, 2 => false, 3 => false];
        $optionsByQuestion = [];
        $correctByQuestion = [];
        $optionTextById = [];
        $activeVariant = 1;
        $seedBase = '';

        if ($examType === 'qcm') {
            $hasVariant = $this->schema()->ensureExamQuestionsVariantColumn();
            $questionsRaw = $this->exams->fetchQuestions($examId, $hasVariant);

            if ($questionsRaw) {
                $questionIds = [];
                foreach ($questionsRaw as $q) {
                    $variant = $hasVariant ? (int) ($q['variant'] ?? 1) : 1;
                    if ($variant < 1 || $variant > 3) {
                        $variant = 1;
                    }
                    $questionsByVariant[$variant][] = $q;
                    $questionIds[] = (int) $q['id'];
                    $maxScoreByVariant[$variant] += max(0, (int) ($q['points'] ?? 0));
                    if (($q['question_type'] ?? '') === 'text') {
                        $hasTextByVariant[$variant] = true;
                    }
                }
                foreach ($this->exams->fetchOptionsByQuestionIds($questionIds) as $opt) {
                    $qid = (int) $opt['question_id'];
                    $optionsByQuestion[$qid][] = $opt;
                    $optionTextById[(int) $opt['id']] = (string) ($opt['option_text'] ?? '');
                    if (!empty($opt['is_correct'])) {
                        $correctByQuestion[$qid][] = (int) $opt['id'];
                    }
                }
            }

            $attemptIndex = $attemptCount + 1;
            $variantStrategy = (string) ($randomizationSettings['variant_strategy'] ?? 'sequential');
            if ($variantStrategy === 'fixed_a') {
                $activeVariant = 1;
            } elseif ($variantStrategy === 'fixed_b') {
                $activeVariant = 2;
            } elseif ($variantStrategy === 'fixed_c') {
                $activeVariant = 3;
            } elseif ($variantStrategy === 'seeded_random') {
                $variantSeed = (string) ((int) $user['id']) . '|' . (string) $examId . '|attempt:' . (string) $attemptIndex;
                $variantHash = hash('sha256', $variantSeed);
                $activeVariant = (hexdec(substr($variantHash, 0, 8)) % 3) + 1;
            } else {
                $activeVariant = (($attemptIndex - 1) % 3) + 1;
            }
            if (empty($questionsByVariant[$activeVariant])) {
                $activeVariant = 1;
            }
            $questions = $questionsByVariant[$activeVariant] ?? [];
            $maxScore = $maxScoreByVariant[$activeVariant] ?? 0;
            $hasTextQuestions = $hasTextByVariant[$activeVariant] ?? false;
            $seedBase = (string) ((int) $user['id']) . '|' . (string) $examId . '|' . (string) $activeVariant . '|' . (string) $attemptIndex;
            if ($questions && !empty($randomizationSettings['question_shuffle'])) {
                $questions = array_values($questions);
                $questions = self::seededShuffle($questions, $seedBase . '|questions', static function ($q) {
                    return (string) ($q['id'] ?? '');
                });
            }
        }

        $variantLabels = [1 => 'Version A', 2 => 'Version B', 3 => 'Version C'];

        $progressLocked = false;
        $progressTotal = 0;
        $progressDone = 0;
        $progressPercent = 0;
        if (!$isAdmin && !$isCourseOwner) {
            try {
                $progressTotal = $this->exams->countCourseLessons((int) $exam['course_id']);
                if ($progressTotal > 0) {
                    $progressDone = $this->exams->countCompletedLessons((int) $user['id'], (int) $exam['course_id']);
                    $progressPercent = (int) floor(($progressDone / $progressTotal) * 100);
                    if ($progressDone < $progressTotal) {
                        $progressLocked = true;
                    }
                }
            } catch (\Throwable $e) {
                $progressLocked = false;
                $progressTotal = 0;
                $progressDone = 0;
                $progressPercent = 0;
            }
        }

        if (!$isAdmin && !$isCourseOwner && !empty($availabilitySettings['enabled'])) {
            $nowTs = time();
            $startTs = $this->parseDateTimeToTimestamp((string) ($availabilitySettings['start_at'] ?? ''));
            $endTs = $this->parseDateTimeToTimestamp((string) ($availabilitySettings['end_at'] ?? ''));
            if ($startTs !== null && $nowTs < $startTs) {
                $availabilityBlocked = true;
                $availabilityBlockedReason = "Examen disponible \u{00e0} partir du " . self::fmtDtShort(date('Y-m-d H:i:s', $startTs)) . '.';
            } elseif ($endTs !== null && $nowTs > $endTs) {
                $availabilityBlocked = true;
                $availabilityBlockedReason = "La fen\u{00ea}tre de passage est termin\u{00e9}e depuis le " . self::fmtDtShort(date('Y-m-d H:i:s', $endTs)) . '.';
            }
        }

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($progressLocked && !$isCourseOwner && !$isAdmin) {
                $errors[] = 'Vous devez terminer 100% du cours avant de passer l\'examen.';
            }
            if ($availabilityBlocked && !$isCourseOwner && !$isAdmin && !($examType === 'meeting' && $action === 'cancel_meeting_booking')) {
                $errors[] = $availabilityBlockedReason !== '' ? $availabilityBlockedReason : 'Examen indisponible pour le moment.';
            }
            if ($hasPassedAttempt && !$isCourseOwner && !$isAdmin) {
                $errors[] = "Examen d\u{00e9}j\u{00e0} r\u{00e9}ussi.";
            }
            if ($examType === 'qcm' && $lastAttemptStatus === 'submitted' && !$isCourseOwner && !$isAdmin) {
                $errors[] = 'Votre dernière tentative est en cours de correction.';
            }
            if ($attemptCount >= $maxAttempts && !$isCourseOwner && !$isAdmin) {
                $errors[] = 'Nombre de tentatives atteint.';
            }

            if ($errors === []) {
                if ($examType === 'qcm' && $action === 'submit_qcm') {
                    $submit = $this->handleQcmSubmit(
                        $examId,
                        $user,
                        $exam,
                        $questions,
                        $optionsByQuestion,
                        $correctByQuestion,
                        $attemptsHasVariantIndex,
                        $activeVariant,
                        $minScore,
                        $maxScore,
                        $hasTextQuestions,
                        $scoringSettings,
                        $post
                    );
                    if (!empty($submit['redirectTo'])) {
                        return ['redirectTo' => $submit['redirectTo'], 'httpStatus' => null, 'abortMessage' => null, 'view' => []];
                    }
                    $errors = array_merge($errors, $submit['errors']);
                } elseif ($examType === 'drive' && $action === 'submit_drive') {
                    $submit = $this->handleDriveSubmit($examId, $user, $exam, $driveReady, $driveMaxMb, $files, $post);
                    if (!empty($submit['redirectTo'])) {
                        return ['redirectTo' => $submit['redirectTo'], 'httpStatus' => null, 'abortMessage' => null, 'view' => []];
                    }
                    $errors = array_merge($errors, $submit['errors']);
                } elseif ($examType === 'meeting' && $action === 'book_meeting_slot') {
                    $submit = $this->handleMeetingBooking($examId, $user, $exam, $attemptsHasMeetingId, $post);
                    if (!empty($submit['redirectTo'])) {
                        return ['redirectTo' => $submit['redirectTo'], 'httpStatus' => null, 'abortMessage' => null, 'view' => []];
                    }
                    $errors = array_merge($errors, $submit['errors']);
                } elseif ($examType === 'meeting' && $action === 'cancel_meeting_booking') {
                    $submit = $this->handleMeetingCancel($examId, $user, $exam, $attemptsHasMeetingId, $post);
                    if (!empty($submit['redirectTo'])) {
                        return ['redirectTo' => $submit['redirectTo'], 'httpStatus' => null, 'abortMessage' => null, 'view' => []];
                    }
                    $errors = array_merge($errors, $submit['errors']);
                } else {
                    $errors[] = 'Action invalide.';
                }
            }
        }

        $meetingSlots = [];
        $myMeeting = null;
        $myMeetingAttempt = null;
        if ($examType === 'meeting') {
            try {
                $myMeeting = $this->exams->findMyMeeting($examId, (int) $user['id']);
                if ($myMeeting && $attemptsHasMeetingId) {
                    $myMeetingAttempt = $this->exams->findMeetingAttempt($examId, (int) $user['id'], (int) $myMeeting['id']);
                }
            } catch (\Throwable $e) {
                $myMeeting = null;
                $myMeetingAttempt = null;
            }
            try {
                $meetingSlots = $this->exams->findAvailableMeetingSlots($examId);
            } catch (\Throwable $e) {
                $meetingSlots = [];
            }
        }

        $attemptHistory = [];
        $filesByAttempt = [];
        $answersByAttempt = [];
        try {
            $attemptHistory = $this->exams->fetchAttemptHistory($examId, (int) $user['id'], $examType === 'meeting' && $attemptsHasMeetingId);
        } catch (\Throwable $e) {
            $attemptHistory = [];
        }
        if ($attemptHistory) {
            $attemptIds = array_values(array_map(static fn(array $a): int => (int) ($a['id'] ?? 0), $attemptHistory));
            try {
                foreach ($this->exams->fetchFilesByAttemptIds($attemptIds) as $f) {
                    $aid = (int) ($f['attempt_id'] ?? 0);
                    if ($aid > 0 && !isset($filesByAttempt[$aid])) {
                        $filesByAttempt[$aid] = $f;
                    }
                }
            } catch (\Throwable $e) {
                $filesByAttempt = [];
            }
            if ($examType === 'qcm') {
                try {
                    foreach ($this->exams->fetchAnswersByAttemptIds($attemptIds) as $row) {
                        $aid = (int) ($row['attempt_id'] ?? 0);
                        $qid = (int) ($row['question_id'] ?? 0);
                        if ($aid < 1 || $qid < 1) {
                            continue;
                        }
                        if (!isset($answersByAttempt[$aid])) {
                            $answersByAttempt[$aid] = [];
                        }
                        if (!isset($answersByAttempt[$aid][$qid])) {
                            $answersByAttempt[$aid][$qid] = ['options' => [], 'text' => null];
                        }
                        if (!empty($row['option_id'])) {
                            $answersByAttempt[$aid][$qid]['options'][] = (int) $row['option_id'];
                        }
                        if (!empty($row['answer_text'])) {
                            $answersByAttempt[$aid][$qid]['text'] = (string) $row['answer_text'];
                        }
                    }
                } catch (\Throwable $e) {
                    $answersByAttempt = [];
                }
            }
        }

        $courseUrl = !empty($exam['course_slug'])
            ? $this->siteUrl('course/watch?slug=' . (string) $exam['course_slug'])
            : $this->siteUrl('course/watch?id=' . (int) $exam['course_id']);

        $attemptPolicy = $this->buildAttemptPolicy(
            $examType,
            $attemptCount,
            $maxAttempts,
            $hasPassedAttempt,
            $progressLocked,
            $availabilityBlocked,
            ($isCourseOwner || $isAdmin),
            $lastAttemptStatus
        );
        $canAttempt = (bool) ($attemptPolicy['canAttempt'] ?? false);
        $remainingAttempts = (int) ($attemptPolicy['remainingAttempts'] ?? 0);

        $typeLabel = $examType === 'qcm' ? 'QCM' : ($examType === 'drive' ? 'Remise Drive' : 'Rendez-vous');
        $durationLabel = !empty($exam['duration_min']) ? \format_duration_minutes_hm((int) $exam['duration_min']) : 'Duree n/c';

        $driveSubjectLink = '';
        $driveSubjectText = '';
        if ($examType === 'drive') {
            $driveSubjectLink = (string) ($exam['drive_subject_link'] ?? '');
            if ($driveSubjectLink === '' && !empty($exam['drive_expected'])) {
                $legacy = (string) $exam['drive_expected'];
                if (str_starts_with($legacy, 'http')) {
                    $driveSubjectLink = $legacy;
                }
            }
            $subjectName = (string) ($exam['drive_subject_name'] ?? '');
            $driveSubjectText = $subjectName !== '' ? $subjectName : $driveSubjectLink;
        }

        $showRetry = (bool) ($attemptPolicy['showRetry'] ?? false);

        $lastAttemptUi = null;
        if (is_array($lastAttempt) && $lastAttempt !== []) {
            $lastStatus = (string) ($lastAttempt['status'] ?? '');
            $lastStatusLabel = $this->attemptStatusLabel($lastStatus);
            $lastVariant = (int) ($lastAttempt['variant_index'] ?? $activeVariant);
            if ($lastVariant < 1 || $lastVariant > 3) {
                $lastVariant = 1;
            }
            $lastMaxScore = $maxScoreByVariant[$lastVariant] ?? $maxScore;
            $lastIsPassed = $lastStatus === 'graded'
                && $lastAttempt['score'] !== null
                && (int) $lastAttempt['score'] >= $minScore;
            $lastAttemptUi = [
                'status' => $lastStatus,
                'status_label' => $lastStatusLabel,
                'variant_label' => $variantLabels[$lastVariant] ?? 'Version A',
                'submitted_at_label' => !empty($lastAttempt['submitted_at'])
                    ? self::fmtDtShort((string) $lastAttempt['submitted_at'])
                    : '',
                'score' => $lastAttempt['score'] !== null ? (int) $lastAttempt['score'] : null,
                'max_score' => (int) $lastMaxScore,
                'is_passed' => $lastIsPassed,
            ];
        }

        $questionsUi = [];
        if ($examType === 'qcm') {
            foreach ($questions as $idx => $q) {
                $qid = (int) ($q['id'] ?? 0);
                if ($qid < 1) {
                    continue;
                }
                $qType = (string) ($q['question_type'] ?? 'single');
                $points = max(0, (int) ($q['points'] ?? 0));
                $isText = $qType === 'text';
                $optionsUi = [];
                if (!$isText) {
                    $opts = $optionsByQuestion[$qid] ?? [];
                    if ($opts && !empty($randomizationSettings['option_shuffle'])) {
                        $opts = array_values($opts);
                        $opts = self::seededShuffle($opts, $seedBase . '|q:' . (string) $qid . '|opts', static function ($opt) {
                            return (string) ($opt['id'] ?? '');
                        });
                    }
                    foreach ($opts as $oIdx => $opt) {
                        $oid = (int) ($opt['id'] ?? 0);
                        if ($oid < 1) {
                            continue;
                        }
                        $isMultiple = $qType === 'multiple';
                        $inputType = $isMultiple ? 'checkbox' : 'radio';
                        $inputName = $isMultiple ? ('answers[' . $qid . '][]') : ('answers[' . $qid . ']');
                        $optionsUi[] = [
                            'id' => $oid,
                            'text' => (string) ($opt['option_text'] ?? ''),
                            'input_type' => $inputType,
                            'input_name' => $inputName,
                            'required' => !$isMultiple && $oIdx === 0,
                        ];
                    }
                }
                $questionsUi[] = [
                    'id' => $qid,
                    'index' => (int) $idx + 1,
                    'text' => (string) ($q['question_text'] ?? ''),
                    'points' => $points,
                    'is_text' => $isText,
                    'text_input_name' => 'texts[' . $qid . ']',
                    'options' => $optionsUi,
                ];
            }
        }

        $meetingMyUi = null;
        $meetingCanBook = (($attemptCount < $maxAttempts) || $isCourseOwner || $isAdmin) && $attemptsHasMeetingId;
        $meetingSlotsUi = [];
        if ($examType === 'meeting') {
            if (is_array($myMeeting) && $myMeeting !== []) {
                $meetingStatus = (string) ($myMeeting['status'] ?? '');
                $meetingStartTs = !empty($myMeeting['start_at']) ? strtotime((string) $myMeeting['start_at']) : false;
                $meetingScore = (is_array($myMeetingAttempt) && isset($myMeetingAttempt['score']) && $myMeetingAttempt['score'] !== null)
                    ? (int) $myMeetingAttempt['score']
                    : null;
                $meetingAttemptStatus = is_array($myMeetingAttempt) ? (string) ($myMeetingAttempt['status'] ?? '') : '';
                $meetingMyUi = [
                    'id' => (int) ($myMeeting['id'] ?? 0),
                    'mode' => (string) ($myMeeting['mode'] ?? 'online'),
                    'mode_label' => $this->meetingModeLabel((string) ($myMeeting['mode'] ?? 'online')),
                    'start_label' => self::fmtDtShort(isset($myMeeting['start_at']) ? (string) $myMeeting['start_at'] : null),
                    'end_label' => self::fmtDtShort(isset($myMeeting['end_at']) ? (string) $myMeeting['end_at'] : null),
                    'duration_min' => $this->meetingDurationMinutes(
                        isset($myMeeting['start_at']) ? (string) $myMeeting['start_at'] : null,
                        isset($myMeeting['end_at']) ? (string) $myMeeting['end_at'] : null
                    ),
                    'link' => (string) ($myMeeting['link'] ?? ''),
                    'place' => (string) ($myMeeting['place'] ?? ''),
                    'attempt_status' => $meetingAttemptStatus,
                    'attempt_score' => $meetingScore,
                    'attempt_passed' => $meetingScore !== null && $meetingScore >= $minScore,
                    'can_cancel' => $meetingStatus === 'booked'
                        && ($meetingStartTs === false || $meetingStartTs > time())
                        && $meetingAttemptStatus !== 'graded',
                ];
            }

            foreach ($meetingSlots as $idx => $slot) {
                $meetingSlotsUi[] = [
                    'id' => (int) ($slot['id'] ?? 0),
                    'mode' => (string) ($slot['mode'] ?? 'online'),
                    'mode_label' => $this->meetingModeLabel((string) ($slot['mode'] ?? 'online')),
                    'start_label' => self::fmtDtShort(isset($slot['start_at']) ? (string) $slot['start_at'] : null),
                    'end_label' => self::fmtDtShort(isset($slot['end_at']) ? (string) $slot['end_at'] : null),
                    'duration_min' => $this->meetingDurationMinutes(
                        isset($slot['start_at']) ? (string) $slot['start_at'] : null,
                        isset($slot['end_at']) ? (string) $slot['end_at'] : null
                    ),
                    'link' => (string) ($slot['link'] ?? ''),
                    'place' => (string) ($slot['place'] ?? ''),
                    'required' => $idx === 0,
                ];
            }
        }

        $attemptHistoryUi = [];
        foreach ($attemptHistory as $idx => $attemptRow) {
            $attemptId = (int) ($attemptRow['id'] ?? 0);
            $statusValue = (string) ($attemptRow['status'] ?? '');
            $attemptVariant = (int) ($attemptRow['variant_index'] ?? 1);
            if ($attemptVariant < 1 || $attemptVariant > 3) {
                $attemptVariant = 1;
            }
            $attemptQuestions = $questionsByVariant[$attemptVariant] ?? $questionsByVariant[1] ?? [];
            $attemptMaxScore = $maxScoreByVariant[$attemptVariant] ?? 0;
            $attemptFile = $filesByAttempt[$attemptId] ?? null;

            $historyItem = [
                'id' => $attemptId,
                'index' => (int) $idx + 1,
                'status' => $statusValue,
                'status_label' => $this->attemptStatusLabel($statusValue),
                'badge_class' => $this->attemptBadgeClass($statusValue),
                'score' => $attemptRow['score'] !== null ? (int) $attemptRow['score'] : null,
                'auto_score' => $attemptRow['auto_score'] !== null ? (int) $attemptRow['auto_score'] : null,
                'manual_score' => $attemptRow['manual_score'] !== null ? (int) $attemptRow['manual_score'] : null,
                'when_label' => self::fmtDtShort(isset($attemptRow['submitted_at']) && $attemptRow['submitted_at'] !== null
                    ? (string) $attemptRow['submitted_at']
                    : (isset($attemptRow['created_at']) ? (string) $attemptRow['created_at'] : null)),
                'variant_label' => $variantLabels[$attemptVariant] ?? 'Version A',
                'max_score' => (int) $attemptMaxScore,
                'qcm_questions' => [],
                'drive_file' => null,
                'meeting_info' => null,
            ];

            if ($examType === 'qcm') {
                $qcmQuestions = [];
                foreach ($attemptQuestions as $qIdx => $question) {
                    $questionId = (int) ($question['id'] ?? 0);
                    if ($questionId < 1) {
                        continue;
                    }
                    $questionType = (string) ($question['question_type'] ?? 'single');
                    $answer = $answersByAttempt[$attemptId][$questionId] ?? ['options' => [], 'text' => null];
                    if ($questionType === 'text') {
                        $valueLabel = (string) ($answer['text'] ?? '');
                    } else {
                        $selectedTexts = [];
                        foreach (($answer['options'] ?? []) as $optionId) {
                            $optionId = (int) $optionId;
                            if (isset($optionTextById[$optionId])) {
                                $selectedTexts[] = (string) $optionTextById[$optionId];
                            }
                        }
                        $valueLabel = $selectedTexts ? implode(' | ', $selectedTexts) : 'Aucune';
                    }
                    $qcmQuestions[] = [
                        'index' => (int) $qIdx + 1,
                        'text' => (string) ($question['question_text'] ?? ''),
                        'is_text' => $questionType === 'text',
                        'value' => $valueLabel,
                    ];
                }
                $historyItem['qcm_questions'] = $qcmQuestions;
            } elseif ($examType === 'drive') {
                $driveLink = '';
                if (is_array($attemptFile)) {
                    if (!empty($attemptFile['drive_view_link'])) {
                        $driveLink = (string) $attemptFile['drive_view_link'];
                    } elseif (!empty($attemptFile['drive_link'])) {
                        $driveLink = (string) $attemptFile['drive_link'];
                    }
                }
                $historyItem['drive_file'] = [
                    'link' => $driveLink,
                    'name' => is_array($attemptFile) && !empty($attemptFile['original_name']) ? (string) $attemptFile['original_name'] : '',
                    'comment' => is_array($attemptFile) && !empty($attemptFile['comment']) ? (string) $attemptFile['comment'] : '',
                ];
            } elseif ($examType === 'meeting') {
                $mMode = (string) ($attemptRow['meeting_mode'] ?? ($exam['meeting_mode'] ?? 'online'));
                $mStatus = (string) ($attemptRow['meeting_status'] ?? '');
                $historyItem['meeting_info'] = [
                    'start_label' => self::fmtDtShort(isset($attemptRow['meeting_start']) ? (string) $attemptRow['meeting_start'] : null),
                    'end_label' => self::fmtDtShort(isset($attemptRow['meeting_end']) ? (string) $attemptRow['meeting_end'] : null),
                    'status_label' => $this->meetingSlotStatusLabel($mStatus),
                    'mode' => $mMode,
                    'mode_label' => $this->meetingModeLabel($mMode),
                    'link' => (string) ($attemptRow['meeting_link'] ?? ($exam['meeting_link'] ?? '')),
                    'place' => (string) ($attemptRow['meeting_place'] ?? ($exam['meeting_place'] ?? '')),
                    'comment' => is_array($attemptFile) && !empty($attemptFile['comment']) ? (string) $attemptFile['comment'] : '',
                ];
            }

            $attemptHistoryUi[] = $historyItem;
        }

        return [
            'redirectTo' => null,
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [
                'exam' => $exam,
                'errors' => $errors,
                'success' => $success,
                'examType' => $examType,
                'maxAttempts' => $maxAttempts,
                'minScore' => $minScore,
                'driveReady' => $driveReady,
                'driveUploadNote' => $driveUploadNote,
                'driveMaxMb' => $driveMaxMb,
                'attemptsHasMeetingId' => $attemptsHasMeetingId,
                'attemptCount' => $attemptCount,
                'hasPassedAttempt' => $hasPassedAttempt,
                'certificateCode' => $certificateCode,
                'maxScore' => $maxScore,
                'activeVariant' => $activeVariant,
                'variantLabels' => $variantLabels,
                'progressLocked' => $progressLocked,
                'progressTotal' => $progressTotal,
                'progressDone' => $progressDone,
                'progressPercent' => $progressPercent,
                'isAdmin' => $isAdmin,
                'isCourseOwner' => $isCourseOwner,
                'courseUrl' => $courseUrl,
                'canAttempt' => $canAttempt,
                'remainingAttempts' => $remainingAttempts,
                'typeLabel' => $typeLabel,
                'durationLabel' => $durationLabel,
                'driveSubjectLink' => $driveSubjectLink,
                'driveSubjectText' => $driveSubjectText,
                'showRetry' => $showRetry,
                'lastAttemptUi' => $lastAttemptUi,
                'questionsUi' => $questionsUi,
                'examSettings' => $examSettings,
                'antiCheatSettings' => $antiCheatSettings,
                'availabilityBlocked' => $availabilityBlocked,
                'availabilityBlockedReason' => $availabilityBlockedReason,
                'meetingMyUi' => $meetingMyUi,
                'meetingCanBook' => $meetingCanBook,
                'meetingSlotsUi' => $meetingSlotsUi,
                'attemptHistoryUi' => $attemptHistoryUi,
            ],
        ];
    }

    /**
     * @return array{canAttempt:bool,remainingAttempts:int,showRetry:bool}
     */
    private function buildAttemptPolicy(
        string $examType,
        int $attemptCount,
        int $maxAttempts,
        bool $hasPassedAttempt,
        bool $progressLocked,
        bool $availabilityBlocked,
        bool $isPrivileged,
        string $lastAttemptStatus = ''
    ): array {
        $withinAttemptLimit = $attemptCount < $maxAttempts;
        $canAttempt = $examType === 'meeting' ? true : $withinAttemptLimit;

        if ($isPrivileged) {
            $canAttempt = true;
        } elseif ($hasPassedAttempt || $progressLocked || $availabilityBlocked) {
            $canAttempt = false;
        } elseif ($examType === 'qcm' && $lastAttemptStatus === 'submitted') {
            $canAttempt = false;
        }

        $remainingAttempts = max(0, $maxAttempts - $attemptCount);
        $showRetry = $examType === 'qcm'
            ? (!$hasPassedAttempt && $attemptCount > 0 && $withinAttemptLimit && $lastAttemptStatus !== 'submitted')
            : ($examType !== 'meeting' && !$hasPassedAttempt && $remainingAttempts > 0 && $attemptCount > 0);

        if ($availabilityBlocked && !$isPrivileged) {
            $showRetry = false;
        }

        return [
            'canAttempt' => $canAttempt,
            'remainingAttempts' => $remainingAttempts,
            'showRetry' => $showRetry,
        ];
    }

    /**
     * @param array<string,mixed> $exam
     * @param array<string,mixed> $user
     * @param array<int,array<string,mixed>> $questions
     * @param array<int,array<int,array<string,mixed>>> $optionsByQuestion
     * @param array<int,array<int,int>> $correctByQuestion
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,errors:array<int,string>}
     */
    private function handleQcmSubmit(
        int $examId,
        array $user,
        array $exam,
        array $questions,
        array $optionsByQuestion,
        array $correctByQuestion,
        bool $attemptsHasVariantIndex,
        int $activeVariant,
        int $minScore,
        int $maxScore,
        bool $hasTextQuestions,
        array $scoringSettings,
        array $post
    ): array {
        $errors = [];
        if ($questions === []) {
            return ['redirectTo' => null, 'errors' => ['Aucune question configuree.']];
        }

        $answers = $post['answers'] ?? [];
        $texts = $post['texts'] ?? [];
        if (!is_array($answers)) {
            $answers = [];
        }
        if (!is_array($texts)) {
            $texts = [];
        }

        $normalized = [];
        foreach ($questions as $q) {
            $qid = (int) ($q['id'] ?? 0);
            if ($qid < 1) {
                continue;
            }
            $qType = (string) ($q['question_type'] ?? 'single');
            if ($qType === 'text') {
                $textVal = trim((string) ($texts[$qid] ?? ''));
                if ($textVal === '') {
                    $errors[] = 'Reponse texte requise.';
                    break;
                }
                $normalized[$qid] = ['type' => 'text', 'text' => $textVal];
                continue;
            }

            $optList = $optionsByQuestion[$qid] ?? [];
            $validOptionIds = [];
            foreach ($optList as $o) {
                $validOptionIds[(int) ($o['id'] ?? 0)] = true;
            }

            $val = $answers[$qid] ?? null;
            $selected = [];
            if ($qType === 'multiple') {
                if (is_array($val)) {
                    foreach ($val as $raw) {
                        $oid = (int) $raw;
                        if ($oid > 0 && isset($validOptionIds[$oid])) {
                            $selected[] = $oid;
                        }
                    }
                }
                $selected = array_values(array_unique($selected));
                if ($selected === []) {
                    $errors[] = 'Selection requise (QCM multiple).';
                    break;
                }
                $normalized[$qid] = ['type' => 'multiple', 'options' => $selected];
            } else {
                $oid = (int) $val;
                if ($oid < 1 || !isset($validOptionIds[$oid])) {
                    $errors[] = 'Selection requise (QCM).';
                    break;
                }
                $normalized[$qid] = ['type' => 'single', 'options' => [$oid]];
            }
        }

        if ($errors !== []) {
            return ['redirectTo' => null, 'errors' => $errors];
        }

        $wrongPenalty = max(0.0, min(10.0, (float) ($scoringSettings['wrong_penalty'] ?? 0.0)));
        $partialCredit = !empty($scoringSettings['multiple_partial_credit']);
        $scoreFloat = 0.0;
        foreach ($questions as $q) {
            $qid = (int) ($q['id'] ?? 0);
            if ($qid < 1) {
                continue;
            }
            $qType = (string) ($q['question_type'] ?? 'single');
            if ($qType === 'text') {
                continue;
            }
            $points = max(0, (int) ($q['points'] ?? 0));
            $selected = $normalized[$qid]['options'] ?? [];
            $correct = $correctByQuestion[$qid] ?? [];
            sort($selected);
            sort($correct);
            if ($qType === 'multiple') {
                if ($partialCredit && $correct) {
                    $good = 0;
                    foreach ($selected as $picked) {
                        if (in_array((int) $picked, $correct, true)) {
                            $good++;
                        }
                    }
                    $wrong = max(0, count($selected) - $good);
                    $perGood = $points > 0 ? ($points / max(1, count($correct))) : 0.0;
                    $scoreFloat += ($good * $perGood) - ($wrong * $wrongPenalty);
                } elseif ($selected && $correct && $selected === $correct) {
                    $scoreFloat += $points;
                } elseif ($selected && $wrongPenalty > 0) {
                    $scoreFloat -= $wrongPenalty;
                }
            } else {
                if ($correct && count($correct) === 1 && isset($selected[0]) && (int) $selected[0] === (int) $correct[0]) {
                    $scoreFloat += $points;
                } elseif ($selected && $wrongPenalty > 0) {
                    $scoreFloat -= $wrongPenalty;
                }
            }
        }

        $autoScore = (int) max(0, round($scoreFloat));

        $status = $hasTextQuestions ? 'submitted' : 'graded';
        $score = $hasTextQuestions ? null : $autoScore;

        try {
            $this->pdo->beginTransaction();
            $attemptId = $this->exams->insertQcmAttempt(
                $examId,
                (int) $user['id'],
                $status,
                $score,
                $autoScore,
                $attemptsHasVariantIndex,
                $activeVariant
            );

            foreach ($questions as $q) {
                $qid = (int) ($q['id'] ?? 0);
                if ($qid < 1) {
                    continue;
                }
                $qType = (string) ($q['question_type'] ?? 'single');
                if ($qType === 'text') {
                    $this->exams->insertExamAnswer($attemptId, $qid, null, (string) ($normalized[$qid]['text'] ?? ''));
                    continue;
                }
                foreach (($normalized[$qid]['options'] ?? []) as $oid) {
                    $this->exams->insertExamAnswer($attemptId, $qid, (int) $oid, null);
                }
            }
            $this->pdo->commit();

            if ($status === 'graded' && $autoScore >= $minScore) {
                $certCode = $this->certificates->issueCertificate(
                    (int) $user['id'],
                    (int) $exam['course_id'],
                    $examId,
                    $attemptId,
                    $autoScore,
                    $minScore
                );
                if ($certCode) {
                    try {
                        $this->exams->insertNotification(
                            (int) $user['id'],
                            'Certificat disponible',
                            (string) ($exam['course_title'] ?? 'Cours') . ' : votre certificat est disponible.',
                            $this->siteUrl('certificate?code=' . urlencode($certCode))
                        );
                    } catch (\Throwable $e) {
                        // no-op
                    }
                }
            }

            if ($status === 'submitted') {
                try {
                    $studentName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
                $this->exams->insertNotification(
                    (int) ($exam['teacher_id'] ?? 0),
                    "Nouvelle soumission d'examen",
                    ($studentName !== '' ? $studentName . ' - ' : '') . (string) ($exam['course_title'] ?? 'Cours') . " : examen \u{00e0} corriger.",
                    $this->siteUrl('teacher/exams_manage?exam_id=' . $examId)
                );
            } catch (\Throwable $e) {
                // no-op
            }
            }

            $msg = $status === 'graded'
                ? ("Examen corrig\u{00e9}. Votre note : " . (string) $autoScore . ' / ' . (string) $maxScore . '.')
                : "Examen envoy\u{00e9}. En attente de correction.";
            $this->flashSet('success', $msg);

            return ['redirectTo' => $this->siteUrl('student/exam?exam_id=' . $examId), 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['redirectTo' => null, 'errors' => ["Erreur lors de l'envoi."]];
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $exam
     * @param array<string,mixed> $files
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,errors:array<int,string>}
     */
    private function handleDriveSubmit(
        int $examId,
        array $user,
        array $exam,
        bool $driveReady,
        int $driveMaxMb,
        array $files,
        array $post
    ): array {
        $errors = [];
        $comment = trim((string) ($post['comment'] ?? ''));

        $file = $files['drive_file'] ?? null;
        if (!$file || !isset($file['error'])) {
            $errors[] = 'Fichier requis.';
        } else {
            $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError === UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Fichier requis.';
            } elseif ($uploadError !== UPLOAD_ERR_OK) {
                $errors[] = 'Erreur upload (code ' . (string) $uploadError . ').';
            }
        }

        $tmpPath = '';
        $sizeBytes = 0;
        $safeOriginal = '';
        $mimeType = 'application/octet-stream';
        $driveResult = null;

        if ($errors === []) {
            $tmpPath = (string) ($file['tmp_name'] ?? '');
            $sizeBytes = (int) ($file['size'] ?? 0);
            if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
                $errors[] = 'Fichier invalide.';
            }
            $maxBytes = $driveMaxMb * 1024 * 1024;
            if ($maxBytes > 0 && $sizeBytes > $maxBytes) {
                $errors[] = 'Fichier trop volumineux (max ' . (string) $driveMaxMb . ' Mo).';
            }
        }

        if ($errors === []) {
            $safeOriginal = $this->drive->safeFilename((string) ($file['name'] ?? 'fichier'));
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $tmpPath);
                    if (is_string($detected) && $detected !== '') {
                        $mimeType = $detected;
                    }
                    finfo_close($finfo);
                }
            } elseif (!empty($file['type'])) {
                $mimeType = (string) $file['type'];
            }
            $prefix = 'cours-' . (int) $exam['course_id'] . '_exam-' . $examId . '_etudiant-' . (int) $user['id'] . '_' . date('Ymd_His');
            $driveName = $prefix . '_' . $safeOriginal;
            if (strlen($driveName) > 220) {
                $driveName = substr($driveName, 0, 220);
            }
            $parentId = APP_DRIVE_ROOT_FOLDER_ID !== '' ? APP_DRIVE_ROOT_FOLDER_ID : null;
            $driveResult = $this->drive->uploadFile($tmpPath, $driveName, $mimeType, $parentId, APP_DRIVE_PUBLIC_LINK);
            if (empty($driveResult['ok'])) {
                $driveError = $this->drive->errorMessage($driveResult);
                $errors[] = "Upload Drive impossible." . ($driveError !== '' ? ' ' . $driveError : " Merci de r\u{00e9}essayer.");
            }
        }

        if ($errors !== []) {
            return ['redirectTo' => null, 'errors' => $errors];
        }

        $driveFileId = (string) ($driveResult['id'] ?? '');
        $driveLink = (string) ($driveResult['webViewLink'] ?? '');
        if ($driveLink === '' && !empty($driveResult['webContentLink'])) {
            $driveLink = (string) $driveResult['webContentLink'];
        }
        if ($driveLink === '' && $driveFileId !== '') {
            $driveLink = 'https://drive.google.com/file/d/' . $driveFileId . '/view';
        }

        try {
            $this->pdo->beginTransaction();
            $attemptId = $this->exams->insertDriveAttempt($examId, (int) $user['id']);
            if ($this->schema()->ensureExamFilesDriveMetaColumns()) {
                $this->exams->insertExamFileWithDriveMeta(
                    $attemptId,
                    $driveLink !== '' ? $driveLink : null,
                    $driveFileId !== '' ? $driveFileId : null,
                    $driveLink !== '' ? $driveLink : null,
                    $safeOriginal !== '' ? $safeOriginal : null,
                    $mimeType !== '' ? $mimeType : null,
                    $sizeBytes > 0 ? $sizeBytes : null,
                    $comment !== '' ? $comment : null
                );
            } else {
                $this->exams->insertExamFileLegacy(
                    $attemptId,
                    $driveLink !== '' ? $driveLink : null,
                    $comment !== '' ? $comment : null
                );
            }
            $this->pdo->commit();

            try {
                $studentName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
                $this->exams->insertNotification(
                    (int) ($exam['teacher_id'] ?? 0),
                    "Nouvelle remise Drive (examen)",
                    ($studentName !== '' ? $studentName . ' - ' : '') . (string) ($exam['course_title'] ?? 'Cours') . " : fichier Drive \u{00e0} corriger.",
                    $this->siteUrl('teacher/exams_manage?exam_id=' . $examId)
                );
            } catch (\Throwable $e) {
                // no-op
            }

            $this->flashSet('success', "Remise envoy\u{00e9}e. En attente de correction.");
            return ['redirectTo' => $this->siteUrl('student/exam?exam_id=' . $examId), 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['redirectTo' => null, 'errors' => ["Erreur lors de l'envoi."]];
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $exam
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,errors:array<int,string>}
     */
    private function handleMeetingBooking(
        int $examId,
        array $user,
        array $exam,
        bool $attemptsHasMeetingId,
        array $post
    ): array {
        $errors = [];
        $meetingId = (int) ($post['meeting_id'] ?? 0);
        $comment = trim((string) ($post['comment'] ?? ''));

        if (!$attemptsHasMeetingId) {
            $errors[] = "Fonction indisponible (mise \u{00e0} jour de la base de donn\u{00e9}es requise).";
        } elseif ($meetingId < 1) {
            $errors[] = "Cr\u{00e9}neau invalide.";
        }

        if ($errors === [] && $this->exams->hasBookedMeeting($examId, (int) $user['id'])) {
            $errors[] = "Vous avez d\u{00e9}j\u{00e0} un cr\u{00e9}neau r\u{00e9}serv\u{00e9}.";
        }

        $slot = null;
        if ($errors === []) {
            $slot = $this->exams->findMeetingById($meetingId, $examId);
            if (!$slot) {
                $errors[] = "Cr\u{00e9}neau introuvable.";
            } elseif ((string) ($slot['status'] ?? '') !== 'available' || !empty($slot['booked_by'])) {
                $errors[] = "Ce cr\u{00e9}neau n'est plus disponible.";
            } elseif (!empty($slot['start_at']) && strtotime((string) $slot['start_at']) < time()) {
                $errors[] = "Ce cr\u{00e9}neau est d\u{00e9}j\u{00e0} pass\u{00e9}.";
            }
        }

        if ($errors !== []) {
            return ['redirectTo' => null, 'errors' => $errors];
        }

        try {
            $this->pdo->beginTransaction();
            $updated = $this->exams->reserveMeeting($meetingId, $examId, (int) $user['id']);
            if ($updated !== 1) {
                $this->pdo->rollBack();
                return ['redirectTo' => null, 'errors' => ["Ce cr\u{00e9}neau n'est plus disponible."]];
            }

            $attemptId = $this->exams->createMeetingAttempt($examId, (int) $user['id'], $meetingId);
            if ($comment !== '') {
                $this->exams->insertExamFileLegacy($attemptId, null, $comment);
            }
            $this->pdo->commit();

            try {
                $studentName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
                $slotLabel = self::fmtDtShort((string) ($slot['start_at'] ?? null));
                $this->exams->insertNotification(
                    (int) ($exam['teacher_id'] ?? 0),
                    "Cr\u{00e9}neau r\u{00e9}serv\u{00e9} (examen)",
                    ($studentName !== '' ? $studentName . ' - ' : '') . (string) ($exam['course_title'] ?? 'Cours') . " : cr\u{00e9}neau " . $slotLabel . '.',
                    $this->siteUrl('teacher/exams_manage?exam_id=' . $examId)
                );
            } catch (\Throwable $e) {
                // no-op
            }

            $this->flashSet('success', "Cr\u{00e9}neau r\u{00e9}serv\u{00e9}. Vous recevrez la note apr\u{00e8}s la s\u{00e9}ance.");
            return ['redirectTo' => $this->siteUrl('student/exam?exam_id=' . $examId), 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['redirectTo' => null, 'errors' => ["Erreur lors de la r\u{00e9}servation."]];
        }
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $exam
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,errors:array<int,string>}
     */
    private function handleMeetingCancel(
        int $examId,
        array $user,
        array $exam,
        bool $attemptsHasMeetingId,
        array $post
    ): array {
        $errors = [];
        $meetingId = (int) ($post['meeting_id'] ?? 0);
        if (!$attemptsHasMeetingId) {
            $errors[] = "Fonction indisponible (mise \u{00e0} jour de la base de donn\u{00e9}es requise).";
        } elseif ($meetingId < 1) {
            $errors[] = "Cr\u{00e9}neau invalide.";
        }

        $slot = null;
        if ($errors === []) {
            $slot = $this->exams->findMeetingById($meetingId, $examId);
            if (!$slot || (int) ($slot['booked_by'] ?? 0) !== (int) $user['id']) {
                $errors[] = "Cr\u{00e9}neau introuvable.";
            } elseif ((string) ($slot['status'] ?? '') !== 'booked') {
                $errors[] = "Ce cr\u{00e9}neau ne peut pas \u{00ea}tre annul\u{00e9}.";
            }
        }

        if ($errors !== []) {
            return ['redirectTo' => null, 'errors' => $errors];
        }

        try {
            $this->pdo->beginTransaction();
            $updated = $this->exams->releaseMeeting($meetingId, $examId, (int) $user['id']);
            if ($updated !== 1) {
                $this->pdo->rollBack();
                return ['redirectTo' => null, 'errors' => ['Annulation impossible.']];
            }
            $this->exams->deleteUngradedMeetingAttempts($examId, (int) $user['id'], $meetingId);
            $this->pdo->commit();

            try {
                $studentName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
                $slotLabel = self::fmtDtShort((string) ($slot['start_at'] ?? null));
                $this->exams->insertNotification(
                    (int) ($exam['teacher_id'] ?? 0),
                    "Cr\u{00e9}neau annul\u{00e9} (examen)",
                    ($studentName !== '' ? $studentName . ' - ' : '') . (string) ($exam['course_title'] ?? 'Cours') . " : annulation du cr\u{00e9}neau " . $slotLabel . '.',
                    $this->siteUrl('teacher/exams_manage?exam_id=' . $examId)
                );
            } catch (\Throwable $e) {
                // no-op
            }

            $this->flashSet('success', "Cr\u{00e9}neau annul\u{00e9}.");
            return ['redirectTo' => $this->siteUrl('student/exam?exam_id=' . $examId), 'errors' => []];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['redirectTo' => null, 'errors' => ["Erreur lors de l'annulation."]];
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function parseExamSettings(string $raw): array
    {
        $defaults = [
            'randomization' => [
                'question_shuffle' => true,
                'option_shuffle' => true,
                'variant_strategy' => 'sequential',
            ],
            'scoring' => [
                'multiple_partial_credit' => false,
                'wrong_penalty' => 0.0,
            ],
            'availability' => [
                'enabled' => false,
                'start_at' => '',
                'end_at' => '',
            ],
            'anti_cheat' => [
                'fullscreen_required' => false,
                'copy_paste_block' => false,
                'tab_switch_warning' => false,
                'camera_required' => false,
            ],
        ];

        if (trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return $defaults;
        }

        $rnd = is_array($decoded['randomization'] ?? null) ? $decoded['randomization'] : [];
        $strategy = (string) ($rnd['variant_strategy'] ?? 'sequential');
        if (!in_array($strategy, ['sequential', 'seeded_random', 'fixed_a', 'fixed_b', 'fixed_c'], true)) {
            $strategy = 'sequential';
        }
        $defaults['randomization']['question_shuffle'] = !empty($rnd['question_shuffle']);
        $defaults['randomization']['option_shuffle'] = !empty($rnd['option_shuffle']);
        $defaults['randomization']['variant_strategy'] = $strategy;

        $scoring = is_array($decoded['scoring'] ?? null) ? $decoded['scoring'] : [];
        $defaults['scoring']['multiple_partial_credit'] = !empty($scoring['multiple_partial_credit']);
        $defaults['scoring']['wrong_penalty'] = max(0.0, min(10.0, (float) ($scoring['wrong_penalty'] ?? 0.0)));

        $availability = is_array($decoded['availability'] ?? null) ? $decoded['availability'] : [];
        $defaults['availability']['enabled'] = !empty($availability['enabled']);
        $defaults['availability']['start_at'] = (string) ($availability['start_at'] ?? '');
        $defaults['availability']['end_at'] = (string) ($availability['end_at'] ?? '');

        $anti = is_array($decoded['anti_cheat'] ?? null) ? $decoded['anti_cheat'] : [];
        $defaults['anti_cheat']['fullscreen_required'] = !empty($anti['fullscreen_required']);
        $defaults['anti_cheat']['copy_paste_block'] = !empty($anti['copy_paste_block']);
        $defaults['anti_cheat']['tab_switch_warning'] = !empty($anti['tab_switch_warning']);
        $defaults['anti_cheat']['camera_required'] = !empty($anti['camera_required']);

        return $defaults;
    }

    private function parseDateTimeToTimestamp(string $value): ?int
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $normalized = str_replace('T', ' ', $value);
        $ts = strtotime($normalized);
        return $ts === false ? null : (int) $ts;
    }

    private function abort(int $httpStatus, string $message): array
    {
        return [
            'redirectTo' => null,
            'httpStatus' => $httpStatus,
            'abortMessage' => $message,
            'view' => [],
        ];
    }

    /**
     * @param array<int,mixed> $items
     * @return array<int,mixed>
     */
    private static function seededShuffle(array $items, string $seed, ?callable $idFn = null): array
    {
        $decorated = [];
        $idx = 0;
        foreach ($items as $item) {
            $id = $idFn ? (string) $idFn($item) : (string) $idx;
            $decorated[] = ['k' => hash('sha256', $seed . '|' . $id), 'v' => $item];
            $idx++;
        }
        usort($decorated, static function ($a, $b) {
            return $a['k'] <=> $b['k'];
        });
        return array_map(static function ($row) {
            return $row['v'];
        }, $decorated);
    }

    private static function fmtDtShort(?string $dbValue): string
    {
        if (!$dbValue) {
            return '';
        }
        $ts = strtotime($dbValue);
        if (!$ts) {
            return (string) $dbValue;
        }
        return date('d/m/Y H:i', $ts);
    }

    private function attemptStatusLabel(string $status): string
    {
        return $status === 'graded' ? 'Corrigé' : ($status === 'submitted' ? 'Envoyé' : 'En cours');
    }

    private function attemptBadgeClass(string $status): string
    {
        if ($status === 'graded') {
            return 'border-emerald-500 text-emerald-700 dark:text-emerald-200';
        }
        if ($status === 'submitted') {
            return 'border-amber-500 text-amber-700 dark:text-amber-200';
        }
        return 'border-slate-500 text-slate-700 dark:text-slate-200';
    }

    private function meetingModeLabel(string $mode): string
    {
        return $mode === 'onsite' ? 'Presentiel' : 'En ligne';
    }

    private function meetingDurationMinutes(?string $startAt, ?string $endAt): int
    {
        if (!$startAt || !$endAt) {
            return 0;
        }
        $startTs = strtotime($startAt);
        $endTs = strtotime($endAt);
        if (!$startTs || !$endTs || $endTs <= $startTs) {
            return 0;
        }
        return (int) round(($endTs - $startTs) / 60);
    }

    private function meetingSlotStatusLabel(string $status): string
    {
        if ($status === 'booked') {
            return 'Reserve';
        }
        if ($status === 'done') {
            return 'Termine';
        }
        if ($status === 'cancelled') {
            return 'Annule';
        }
        if ($status === 'available') {
            return 'Disponible';
        }
        return '';
    }
}

