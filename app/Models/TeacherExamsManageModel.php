<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherExamsManageModel extends Model
{
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function findExamWithCourse(int $examId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT es.*, c.id AS course_id, c.title AS course_title, c.slug AS course_slug, c.teacher_id, c.status AS course_status, c.deleted_at AS course_deleted_at
            FROM exam_sessions es
            JOIN courses c ON c.id = es.course_id
            WHERE es.id = :id
            LIMIT 1');
        $stmt->execute([':id' => $examId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCorrectionOverview(bool $isAdmin, int $teacherId, string $status): array
    {
        if (!$this->tableExists('exam_sessions') || !$this->tableExists('exam_attempts')) {
            return [];
        }

        $params = [];
        $where = ['c.deleted_at IS NULL', 'c.status = "approved"'];
        if (!$isAdmin) {
            $where[] = 'c.teacher_id = :tid';
            $params[':tid'] = $teacherId;
        }

        $meetingSlotsSelect = $this->tableExists('exam_meetings')
            ? '(SELECT COUNT(*) FROM exam_meetings em WHERE em.exam_id = es.id) AS meeting_slots'
            : '0 AS meeting_slots';

        $urgentCutoff = date('Y-m-d H:i:s', time() - 72 * 3600);
        $having = match ($status) {
            'submitted' => 'submitted_count > 0',
            'graded' => 'graded_count > 0',
            'urgent' => 'oldest_pending_at IS NOT NULL AND oldest_pending_at <= :urgent_cutoff',
            default => '1 = 1',
        };
        if ($status === 'urgent') {
            $params[':urgent_cutoff'] = $urgentCutoff;
        }

        $sql = 'SELECT
                    es.id AS exam_id,
                    es.title AS exam_title,
                    es.type,
                    es.duration_min,
                    es.min_score,
                    es.created_at AS exam_created_at,
                    c.id AS course_id,
                    c.title AS course_title,
                    c.status AS course_status,
                    COUNT(ea.id) AS attempt_count,
                    SUM(CASE WHEN ea.status = "submitted" THEN 1 ELSE 0 END) AS submitted_count,
                    SUM(CASE WHEN ea.status = "graded" THEN 1 ELSE 0 END) AS graded_count,
                    MAX(COALESCE(ea.submitted_at, ea.created_at)) AS latest_activity_at,
                    MIN(CASE WHEN ea.status = "submitted" THEN COALESCE(ea.submitted_at, ea.created_at) ELSE NULL END) AS oldest_pending_at,
                    ' . $meetingSlotsSelect . '
                FROM exam_sessions es
                JOIN courses c ON c.id = es.course_id
                LEFT JOIN exam_attempts ea ON ea.exam_id = es.id
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY es.id, es.title, es.type, es.duration_min, es.min_score, es.created_at, c.id, c.title, c.status
                HAVING ' . $having . '
                ORDER BY submitted_count DESC, latest_activity_at DESC, es.created_at DESC
                LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchQuestions(int $examId, bool $hasVariant): array
    {
        $selectVariant = $hasVariant ? ', variant' : '';
        $orderPrefix = $hasVariant ? 'variant ASC, ' : '';
        $stmt = $this->pdo->prepare('SELECT id, question_text, question_type, order_index, points' . $selectVariant . ' FROM exam_questions WHERE exam_id = :eid ORDER BY ' . $orderPrefix . 'order_index ASC, id ASC');
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchOptionsByQuestionIds(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = $this->pdo->prepare("SELECT id, question_id, option_text, is_correct FROM exam_options WHERE question_id IN ($placeholders) ORDER BY question_id ASC, id ASC");
        $stmt->execute($questionIds);
        return $stmt->fetchAll() ?: [];
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function insertMeetingSlot(int $examId, string $startAt, string $endAt, string $mode, ?string $link, ?string $place): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_meetings (exam_id, start_at, end_at, mode, link, place, status, booked_by)
            VALUES (:eid, :start_at, :end_at, :mode, :link, :place, "available", NULL)');
        $stmt->execute([
            ':eid' => $examId,
            ':start_at' => $startAt,
            ':end_at' => $endAt,
            ':mode' => $mode,
            ':link' => $link,
            ':place' => $place,
        ]);
    }

    public function findMeetingSlotById(int $meetingId, int $examId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT em.*, u.first_name, u.last_name, u.email
            FROM exam_meetings em
            LEFT JOIN users u ON u.id = em.booked_by
            WHERE em.id = :id AND em.exam_id = :eid
            LIMIT 1');
        $stmt->execute([':id' => $meetingId, ':eid' => $examId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markMeetingCancelled(int $meetingId, int $examId): void
    {
        $stmt = $this->pdo->prepare('UPDATE exam_meetings
            SET status = "cancelled", booked_by = NULL, updated_at = NOW()
            WHERE id = :id AND exam_id = :eid');
        $stmt->execute([
            ':id' => $meetingId,
            ':eid' => $examId,
        ]);
    }

    public function deleteUngradedAttemptsByMeeting(int $examId, int $meetingId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_attempts WHERE exam_id = :eid AND meeting_id = :mid AND status <> "graded"');
        $stmt->execute([
            ':eid' => $examId,
            ':mid' => $meetingId,
        ]);
    }

    public function markMeetingDone(int $meetingId, int $examId): void
    {
        $stmt = $this->pdo->prepare('UPDATE exam_meetings SET status = "done", updated_at = NOW() WHERE id = :id AND exam_id = :eid');
        $stmt->execute([
            ':id' => $meetingId,
            ':eid' => $examId,
        ]);
    }

    public function findAttemptById(int $attemptId, int $examId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_attempts WHERE id = :id AND exam_id = :eid LIMIT 1');
        $stmt->execute([
            ':id' => $attemptId,
            ':eid' => $examId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function gradeAttempt(int $attemptId, int $graderUserId, ?int $score, ?int $manualScore): void
    {
        $stmt = $this->pdo->prepare('UPDATE exam_attempts
            SET status = "graded",
                score = :score,
                manual_score = :manual,
                grader_user_id = :gid,
                updated_at = NOW()
            WHERE id = :id');
        $stmt->execute([
            ':score' => $score,
            ':manual' => $manualScore,
            ':gid' => $graderUserId,
            ':id' => $attemptId,
        ]);
    }

    public function fetchAttempts(int $examId, string $status, bool $withMeetingJoin): array
    {
        $sql = 'SELECT ea.*, u.first_name, u.last_name, u.email';
        if ($withMeetingJoin) {
            $sql .= ', em.start_at AS meeting_start, em.end_at AS meeting_end, em.status AS meeting_status, em.mode AS meeting_mode, em.link AS meeting_link, em.place AS meeting_place';
        }
        $sql .= ' FROM exam_attempts ea
            JOIN users u ON u.id = ea.user_id';
        if ($withMeetingJoin) {
            $sql .= ' LEFT JOIN exam_meetings em ON em.id = ea.meeting_id';
        }
        $sql .= ' WHERE ea.exam_id = :eid';
        $params = [':eid' => $examId];
        if ($status === 'urgent') {
            $sql .= ' AND ea.status = "submitted" AND COALESCE(ea.submitted_at, ea.created_at) <= :urgent_cutoff';
            $params[':urgent_cutoff'] = date('Y-m-d H:i:s', time() - 72 * 3600);
        } elseif ($status !== 'all') {
            $sql .= ' AND ea.status = :status';
            $params[':status'] = $status;
        }
        $sql .= ' ORDER BY ea.created_at DESC, ea.id DESC LIMIT 200';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchFilesByAttemptIds(array $attemptIds): array
    {
        if ($attemptIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($attemptIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM exam_files WHERE attempt_id IN ($placeholders) ORDER BY id DESC");
        $stmt->execute($attemptIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchAnswersByAttemptIds(array $attemptIds): array
    {
        if ($attemptIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($attemptIds), '?'));
        $stmt = $this->pdo->prepare("SELECT attempt_id, question_id, option_id, answer_text FROM exam_answers WHERE attempt_id IN ($placeholders) ORDER BY id ASC");
        $stmt->execute($attemptIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchMeetingSlots(int $examId): array
    {
        $stmt = $this->pdo->prepare('SELECT em.*, u.first_name, u.last_name, u.email
            FROM exam_meetings em
            LEFT JOIN users u ON u.id = em.booked_by
            WHERE em.exam_id = :eid
            ORDER BY em.start_at ASC, em.id ASC');
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll() ?: [];
    }
}
