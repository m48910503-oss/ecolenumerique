<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service;

use App\Models\TeacherExamsManageModel;
use App\Services\Shared\CertificateOpsService;

final class ExamsManageService extends Service
{
    private TeacherExamsManageModel $model;
    private CertificateOpsService $certificates;

    public function __construct(TeacherExamsManageModel $model, CertificateOpsService $certificates)
    {
        $this->model = $model;
        $this->certificates = $certificates;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @param array<string,mixed> $post
     * @return array{
     *   redirectTo:?string,
     *   httpStatus:?int,
     *   abortMessage:?string,
     *   view:array<string,mixed>,
     *   download?:array{filename:string,content:string,contentType:string}
     * }
     */
    public function handle(int $examId, array $user, bool $isAdmin, string $method, array $get, array $post): array
    {
        $status = (string) ($get['status'] ?? ($examId < 1 ? 'all' : 'submitted'));
        if (!in_array($status, ['submitted', 'graded', 'all', 'urgent'], true)) {
            $status = $examId < 1 ? 'all' : 'submitted';
        }

        if ($examId < 1) {
            return $this->buildOverview($user, $isAdmin, $status);
        }

        $exam = $this->model->findExamWithCourse($examId);
        if (!$exam) {
            return $this->abort(404, 'Examen introuvable.');
        }
        if (!$isAdmin && (int) ($exam['teacher_id'] ?? 0) !== (int) $user['id']) {
            return $this->abort(403, "Acc\u{00e8}s refus\u{00e9}.");
        }

        $errors = [];
        $success = (string) $this->flashGet('success', '');

        $examType = (string) ($exam['type'] ?? 'qcm');
        $minScore = max(0, (int) ($exam['min_score'] ?? 0));
        $attemptsHasMeetingId = $this->schema()->examAttemptsHasMeetingId();
        if ($examType === 'meeting') {
            $attemptsHasMeetingId = $this->schema()->ensureExamAttemptsMeetingIdColumn();
        }

        $questions = [];
        $questionsByVariant = [1 => [], 2 => [], 3 => []];
        $optionsByQuestion = [];
        $optionTextById = [];
        $correctTextByQuestion = [];
        $maxScore = 0;
        $maxScoreByVariant = [1 => 0, 2 => 0, 3 => 0];
        $manualMax = 0;
        $manualMaxByVariant = [1 => 0, 2 => 0, 3 => 0];

        if ($examType === 'qcm') {
            $hasVariant = $this->schema()->ensureExamQuestionsVariantColumn();
            $questions = $this->model->fetchQuestions($examId, $hasVariant);
            foreach ($questions as $q) {
                $variant = $hasVariant ? (int) ($q['variant'] ?? 1) : 1;
                if ($variant < 1 || $variant > 3) {
                    $variant = 1;
                }
                $questionsByVariant[$variant][] = $q;
                $pts = max(0, (int) ($q['points'] ?? 0));
                $maxScoreByVariant[$variant] += $pts;
                $maxScore += $pts;
                if (($q['question_type'] ?? '') === 'text') {
                    $manualMaxByVariant[$variant] += $pts;
                    $manualMax += $pts;
                }
            }

            if ($questions) {
                $questionIds = array_map(static fn(array $q): int => (int) ($q['id'] ?? 0), $questions);
                foreach ($this->model->fetchOptionsByQuestionIds($questionIds) as $opt) {
                    $qid = (int) ($opt['question_id'] ?? 0);
                    if ($qid < 1) {
                        continue;
                    }
                    $optionsByQuestion[$qid][] = $opt;
                    $optionTextById[(int) ($opt['id'] ?? 0)] = (string) ($opt['option_text'] ?? '');
                    if (!empty($opt['is_correct'])) {
                        $correctTextByQuestion[$qid][] = (string) ($opt['option_text'] ?? '');
                    }
                }
            }
        }

        if ($method === 'POST') {
            $actionResult = $this->handlePostAction(
                $examId,
                $exam,
                $examType,
                $attemptsHasMeetingId,
                $minScore,
                $manualMax,
                $status,
                $user,
                $post
            );
            if (!empty($actionResult['redirectTo'])) {
                return [
                    'redirectTo' => $actionResult['redirectTo'],
                    'httpStatus' => null,
                    'abortMessage' => null,
                    'view' => [],
                ];
            }
            $errors = array_merge($errors, $actionResult['errors']);
            if (($actionResult['success'] ?? '') !== '') {
                $success = (string) $actionResult['success'];
            }
        }

        $attempts = [];
        $filesByAttempt = [];
        $answersByAttempt = [];
        try {
            $attempts = $this->model->fetchAttempts($examId, $status, $examType === 'meeting' && $attemptsHasMeetingId);
        } catch (\Throwable $e) {
            $attempts = [];
        }

        if ($attempts) {
            $attemptIds = array_values(array_map(static fn(array $a): int => (int) ($a['id'] ?? 0), $attempts));
            try {
                foreach ($this->model->fetchFilesByAttemptIds($attemptIds) as $fileRow) {
                    $aid = (int) ($fileRow['attempt_id'] ?? 0);
                    if ($aid > 0 && !isset($filesByAttempt[$aid])) {
                        $filesByAttempt[$aid] = $fileRow;
                    }
                }
            } catch (\Throwable $e) {
                $filesByAttempt = [];
            }

            if ($examType === 'qcm') {
                try {
                    foreach ($this->model->fetchAnswersByAttemptIds($attemptIds) as $row) {
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

        $selectedAttemptId = max(0, (int) ($get['attempt_id'] ?? ($post['attempt_id'] ?? 0)));
        if ($selectedAttemptId < 1 && $attempts !== []) {
            foreach ($attempts as $candidate) {
                if ((string) ($candidate['status'] ?? '') === 'submitted') {
                    $selectedAttemptId = (int) ($candidate['id'] ?? 0);
                    break;
                }
            }
            if ($selectedAttemptId < 1) {
                $selectedAttemptId = (int) ($attempts[0]['id'] ?? 0);
            }
        }
        if ($selectedAttemptId > 0 && $attempts !== []) {
            $validAttemptIds = array_map(static fn(array $a): int => (int) ($a['id'] ?? 0), $attempts);
            if (!in_array($selectedAttemptId, $validAttemptIds, true)) {
                $selectedAttemptId = (int) ($attempts[0]['id'] ?? 0);
            }
        }

        if ($method === 'GET' && strtolower(trim((string) ($get['export'] ?? ''))) === 'csv') {
            return [
                'redirectTo' => null,
                'httpStatus' => null,
                'abortMessage' => null,
                'view' => [],
                'download' => $this->buildGradesCsv($exam, $examType, $minScore, $attempts),
            ];
        }

        $meetingSlots = [];
        $defaultMeetingDuration = (int) ($exam['duration_min'] ?? 0);
        if ($defaultMeetingDuration < 1) {
            $defaultMeetingDuration = 60;
        }
        $defaultMeetingMode = in_array((string) ($exam['meeting_mode'] ?? ''), ['online', 'onsite'], true)
            ? (string) $exam['meeting_mode']
            : 'online';

        if ($examType === 'meeting') {
            try {
                $meetingSlots = $this->model->fetchMeetingSlots($examId);
            } catch (\Throwable $e) {
                $meetingSlots = [];
            }
        }

        $variantLabels = [1 => 'Version A', 2 => 'Version B', 3 => 'Version C'];

        return [
            'redirectTo' => null,
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [
                'examId' => $examId,
                'exam' => $exam,
                'errors' => $errors,
                'success' => $success,
                'examType' => $examType,
                'minScore' => $minScore,
                'attemptsHasMeetingId' => $attemptsHasMeetingId,
                'questionsByVariant' => $questionsByVariant,
                'optionsByQuestion' => $optionsByQuestion,
                'optionTextById' => $optionTextById,
                'correctTextByQuestion' => $correctTextByQuestion,
                'maxScore' => $maxScore,
                'maxScoreByVariant' => $maxScoreByVariant,
                'manualMax' => $manualMax,
                'manualMaxByVariant' => $manualMaxByVariant,
                'variantLabels' => $variantLabels,
                'status' => $status,
                'attempts' => $attempts,
                'selectedAttemptId' => $selectedAttemptId,
                'filesByAttempt' => $filesByAttempt,
                'answersByAttempt' => $answersByAttempt,
                'meetingSlots' => $meetingSlots,
                'defaultMeetingDuration' => $defaultMeetingDuration,
                'defaultMeetingMode' => $defaultMeetingMode,
                'fmtDtShort' => static function (?string $dbValue): string {
                    return self::fmtDtShort($dbValue);
                },
                'dtLocalValue' => static function (?string $dbValue): string {
                    return self::dtLocalValue($dbValue);
                },
            ],
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @return array{redirectTo:?string,httpStatus:?int,abortMessage:?string,view:array<string,mixed>}
     */
    private function buildOverview(array $user, bool $isAdmin, string $status): array
    {
        try {
            $examRows = $this->model->fetchCorrectionOverview($isAdmin, (int) ($user['id'] ?? 0), $status);
        } catch (\Throwable $e) {
            $examRows = [];
        }

        return [
            'redirectTo' => null,
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [
                'isExamIndex' => true,
                'examRows' => $examRows,
                'status' => $status,
                'errors' => [],
                'success' => (string) $this->flashGet('success', ''),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $exam
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @return array{redirectTo:?string,errors:array<int,string>,success:string}
     */
    private function handlePostAction(
        int $examId,
        array $exam,
        string $examType,
        bool $attemptsHasMeetingId,
        int $minScore,
        int $manualMax,
        string $status,
        array $user,
        array $post
    ): array {
        $errors = [];
        $action = (string) ($post['action'] ?? '');

        if ($action === 'add_meeting_slot') {
            if ($examType !== 'meeting') {
                return ['redirectTo' => null, 'errors' => ['Action invalide.'], 'success' => ''];
            }

            $startAt = self::parseDtLocal((string) ($post['start_at'] ?? ''));
            $durationMin = (int) ($post['duration_min'] ?? 0);
            if ($durationMin < 1) {
                $durationMin = (int) ($exam['duration_min'] ?? 0);
            }
            if ($durationMin < 1) {
                $durationMin = 60;
            }

            $mode = (string) ($post['mode'] ?? ($exam['meeting_mode'] ?? 'online'));
            $link = trim((string) ($post['link'] ?? ''));
            $place = trim((string) ($post['place'] ?? ''));

            if (!$startAt) {
                $errors[] = "Date de d\u{00e9}but requise.";
            }
            if (!in_array($mode, ['online', 'onsite'], true)) {
                $errors[] = 'Mode invalide.';
            }

            if ($mode === 'online') {
                if ($link === '') {
                    $link = trim((string) ($exam['meeting_link'] ?? ''));
                }
                if ($link === '') {
                    $errors[] = 'Lien requis (en ligne).';
                }
                $place = '';
            } elseif ($mode === 'onsite') {
                if ($place === '') {
                    $place = trim((string) ($exam['meeting_place'] ?? ''));
                }
                if ($place === '') {
                    $errors[] = 'Lieu requis (presentiel).';
                }
                $link = '';
            }

            $endAt = null;
            if ($errors === []) {
                $endAt = date('Y-m-d H:i:s', strtotime($startAt) + ($durationMin * 60));
                if (!$endAt || strtotime($endAt) <= strtotime($startAt)) {
                    $errors[] = 'Fin invalide.';
                }
            }

            if ($errors === []) {
                try {
                    $this->model->insertMeetingSlot(
                        $examId,
                        $startAt,
                        $endAt,
                        $mode,
                        $link !== '' ? $link : null,
                        $place !== '' ? $place : null
                    );
                    $this->flashSet('success', 'Créneau ajouté.');
                    return ['redirectTo' => $this->siteUrl('teacher/exams_manage?exam_id=' . $examId . '&status=' . urlencode($status)), 'errors' => [], 'success' => ''];
                } catch (\Throwable $e) {
                    $errors[] = "Erreur lors de l'ajout.";
                }
            }
        } elseif ($action === 'cancel_meeting_slot') {
            if ($examType !== 'meeting') {
                return ['redirectTo' => null, 'errors' => ['Action invalide.'], 'success' => ''];
            }
            $meetingId = (int) ($post['meeting_id'] ?? 0);
            if ($meetingId < 1) {
                return ['redirectTo' => null, 'errors' => ['Créneau invalide.'], 'success' => ''];
            }

            $meeting = $this->model->findMeetingSlotById($meetingId, $examId);
            if (!$meeting) {
                return ['redirectTo' => null, 'errors' => ['Créneau introuvable.'], 'success' => ''];
            }
            if (!in_array((string) ($meeting['status'] ?? ''), ['available', 'booked'], true)) {
                return ['redirectTo' => null, 'errors' => ['Ce créneau ne peut pas être annulé.'], 'success' => ''];
            }

            $bookedBy = !empty($meeting['booked_by']) ? (int) $meeting['booked_by'] : null;
            $slotLabel = self::fmtDtShort((string) ($meeting['start_at'] ?? null));

            try {
                $this->model->beginTransaction();
                $this->model->markMeetingCancelled($meetingId, $examId);
                if ($attemptsHasMeetingId) {
                    $this->model->deleteUngradedAttemptsByMeeting($examId, $meetingId);
                }
                $this->model->commit();

                if ($bookedBy) {
                    $this->notifySafe(
                        $bookedBy,
                        'Créneau annulé',
                        ((string) ($exam['course_title'] ?? 'Cours')) . ' - ' . ((string) ($exam['title'] ?? 'Examen')) . ' : créneau ' . $slotLabel . ' annulé. Choisissez un autre créneau.',
                        $this->siteUrl('student/exam?exam_id=' . $examId)
                    );
                }

                $this->flashSet('success', 'Créneau annulé.');
                return ['redirectTo' => $this->siteUrl('teacher/exams_manage?exam_id=' . $examId . '&status=' . urlencode($status)), 'errors' => [], 'success' => ''];
            } catch (\Throwable $e) {
                if ($this->model->inTransaction()) {
                    $this->model->rollBack();
                }
                $errors[] = "Erreur lors de l'annulation.";
            }
        } elseif ($action === 'mark_meeting_done') {
            if ($examType !== 'meeting') {
                return ['redirectTo' => null, 'errors' => ['Action invalide.'], 'success' => ''];
            }
            $meetingId = (int) ($post['meeting_id'] ?? 0);
            if ($meetingId < 1) {
                return ['redirectTo' => null, 'errors' => ['Créneau invalide.'], 'success' => ''];
            }
            $meeting = $this->model->findMeetingSlotById($meetingId, $examId);
            if (!$meeting) {
                return ['redirectTo' => null, 'errors' => ['Créneau introuvable.'], 'success' => ''];
            }
            if ((string) ($meeting['status'] ?? '') !== 'booked') {
                return ['redirectTo' => null, 'errors' => ['Seuls les créneaux réservés peuvent être marqués comme terminés.'], 'success' => ''];
            }

            try {
                $this->model->markMeetingDone($meetingId, $examId);
                if (!empty($meeting['booked_by'])) {
                    $this->notifySafe(
                        (int) $meeting['booked_by'],
                        'Rendez-vous terminé',
                        ((string) ($exam['course_title'] ?? 'Cours')) . ' - ' . ((string) ($exam['title'] ?? 'Examen')) . ' : séance terminée. Attendez la note.',
                        $this->siteUrl('student/exam?exam_id=' . $examId)
                    );
                }

                $this->flashSet('success', 'Créneau marqué comme terminé.');
                return ['redirectTo' => $this->siteUrl('teacher/exams_manage?exam_id=' . $examId . '&status=' . urlencode($status)), 'errors' => [], 'success' => ''];
            } catch (\Throwable $e) {
                $errors[] = 'Erreur lors de la mise à jour.';
            }
        } elseif ($action === 'grade_attempt') {
            $attemptId = (int) ($post['attempt_id'] ?? 0);
            if ($attemptId < 1) {
                return ['redirectTo' => null, 'errors' => ['Tentative invalide.'], 'success' => ''];
            }
            $attempt = $this->model->findAttemptById($attemptId, $examId);
            if (!$attempt) {
                return ['redirectTo' => null, 'errors' => ['Tentative introuvable.'], 'success' => ''];
            }

            $finalScore = null;
            $manualScore = null;
            if ($examType === 'qcm') {
                $manualScore = (int) ($post['manual_score'] ?? 0);
                if ($manualScore < 0 || $manualScore > $manualMax) {
                    return ['redirectTo' => null, 'errors' => ['Note manuelle invalide.'], 'success' => ''];
                }
                $auto = (int) ($attempt['auto_score'] ?? 0);
                $finalScore = $auto + $manualScore;
            } else {
                $finalScore = (int) ($post['score'] ?? 0);
                if ($finalScore < 0) {
                    return ['redirectTo' => null, 'errors' => ['Note invalide.'], 'success' => ''];
                }
                $manualScore = $finalScore;
            }

            try {
                $this->model->gradeAttempt($attemptId, (int) $user['id'], $finalScore, $manualScore);

                $studentUrl = $this->siteUrl('student/exam?exam_id=' . $examId);
                $passed = $finalScore !== null && $finalScore >= $minScore;
                $this->notifySafe(
                    (int) $attempt['user_id'],
                    'Examen corrigé',
                    ((string) ($exam['course_title'] ?? 'Cours')) . ' - ' . ((string) ($exam['title'] ?? 'Examen')) . ' : note ' . (string) $finalScore . ($passed ? ' (réussi)' : ' (non validé)') . '.',
                    $studentUrl
                );

                if ($passed) {
                    $certCode = $this->certificates->issueCertificate(
                        (int) $attempt['user_id'],
                        (int) $exam['course_id'],
                        $examId,
                        $attemptId,
                        (int) $finalScore,
                        $minScore
                    );
                    if ($certCode) {
                        $this->notifySafe(
                            (int) $attempt['user_id'],
                            'Certificat disponible',
                            ((string) ($exam['course_title'] ?? 'Cours')) . ' : votre certificat est disponible.',
                            $this->siteUrl('certificate?code=' . urlencode($certCode))
                        );
                    }
                }

                $this->flashSet('success', 'Note enregistrée et étudiant notifié.');
                return ['redirectTo' => $this->siteUrl('teacher/exams_manage?exam_id=' . $examId . '&status=' . urlencode($status) . '&attempt_id=' . $attemptId), 'errors' => [], 'success' => ''];
            } catch (\Throwable $e) {
                $errors[] = "Erreur lors de l'enregistrement.";
            }
        } else {
            $errors[] = 'Action invalide.';
        }

        return ['redirectTo' => null, 'errors' => $errors, 'success' => ''];
    }

    /**
     * @param array<string,mixed> $exam
     * @param array<int,array<string,mixed>> $attempts
     * @return array{filename:string,content:string,contentType:string}
     */
    private function buildGradesCsv(array $exam, string $examType, int $minScore, array $attempts): array
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [
                'filename' => 'notes-examen.csv',
                'content' => '',
                'contentType' => 'text/csv; charset=utf-8',
            ];
        }

        fputcsv($stream, [
            'Cours',
            'Examen',
            'Type',
            'Etudiant',
            'Email',
            'Statut',
            'Soumis le',
            'Note',
            'Note automatique',
            'Note manuelle',
            'Note minimale',
            'Résultat',
        ], ';');

        foreach ($attempts as $attempt) {
            $score = ($attempt['score'] ?? null) !== null ? (int) $attempt['score'] : null;
            $passed = $score !== null && $score >= $minScore;
            $studentName = trim((string) ($attempt['first_name'] ?? '') . ' ' . (string) ($attempt['last_name'] ?? ''));
            fputcsv($stream, [
                (string) ($exam['course_title'] ?? ''),
                (string) ($exam['title'] ?? ''),
                strtoupper($examType),
                $studentName !== '' ? $studentName : 'Étudiant',
                (string) ($attempt['email'] ?? ''),
                self::attemptStatusLabel((string) ($attempt['status'] ?? '')),
                self::fmtDtShort((string) ($attempt['submitted_at'] ?? ($attempt['created_at'] ?? ''))),
                $score !== null ? (string) $score : '',
                ($attempt['auto_score'] ?? null) !== null ? (string) ((int) $attempt['auto_score']) : '',
                ($attempt['manual_score'] ?? null) !== null ? (string) ((int) $attempt['manual_score']) : '',
                (string) $minScore,
                $score !== null ? ($passed ? 'Réussi' : 'Non validé') : 'En attente',
            ], ';');
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $filenameBase = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($exam['title'] ?? 'examen')));
        $filenameBase = trim($filenameBase, '-_') ?: 'examen';

        return [
            'filename' => 'notes-' . $filenameBase . '.csv',
            'content' => (string) $content,
            'contentType' => 'text/csv; charset=utf-8',
        ];
    }

    private static function attemptStatusLabel(string $state): string
    {
        return match ($state) {
            'submitted' => 'À corriger',
            'graded' => 'Corrigé',
            'available' => 'Disponible',
            'booked' => 'Réservé',
            'done' => 'Terminé',
            'cancelled' => 'Annulé',
            default => 'En cours',
        };
    }

    private function notifySafe(int $userId, string $title, string $body, ?string $url = null): void
    {
        try {
            $this->model->insertNotification($userId, $title, $body, $url);
        } catch (\Throwable $e) {
            // ignore
        }
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

    private static function dtLocalValue(?string $dbValue): string
    {
        if (!$dbValue) {
            return '';
        }
        $ts = strtotime($dbValue);
        if (!$ts) {
            return '';
        }
        return date('Y-m-d\TH:i', $ts);
    }

    private static function parseDtLocal(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if (!$dt) {
            $ts = strtotime($value);
            if (!$ts) {
                return null;
            }
            return date('Y-m-d H:i:s', $ts);
        }
        return $dt->format('Y-m-d H:i:s');
    }
}

