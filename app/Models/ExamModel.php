<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class ExamModel extends Model
{
    public function findExamById(int $examId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT es.*, c.id AS course_id, c.title AS course_title, c.slug AS course_slug, c.teacher_id
            FROM exam_sessions es
            JOIN courses c ON c.id = es.course_id
            WHERE es.id = :id
            LIMIT 1');
        $stmt->execute([':id' => $examId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function isUserEnrolledInCourse(int $userId, int $courseId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM enrollments WHERE user_id = :uid AND course_id = :cid AND status = "active" LIMIT 1');
        $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
        return (bool) $stmt->fetchColumn();
    }

    public function countAttempts(int $examId, int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM exam_attempts WHERE exam_id = :eid AND user_id = :uid');
        $stmt->execute([':eid' => $examId, ':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findLastAttempt(int $examId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_attempts WHERE exam_id = :eid AND user_id = :uid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':eid' => $examId, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countPassedAttempts(int $examId, int $userId, int $minScore): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM exam_attempts
            WHERE exam_id = :eid AND user_id = :uid AND status = "graded" AND score IS NOT NULL AND score >= :min');
        $stmt->execute([':eid' => $examId, ':uid' => $userId, ':min' => $minScore]);
        return (int) $stmt->fetchColumn();
    }

    public function findCertificateCode(int $userId, int $courseId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT code FROM certificates WHERE user_id = :uid AND course_id = :cid AND status = "issued" LIMIT 1');
        $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
        $code = $stmt->fetchColumn();
        if (!is_string($code) || $code === '') {
            return null;
        }
        return $code;
    }

    public function fetchQuestions(int $examId, bool $withVariant): array
    {
        $selectVariant = $withVariant ? ', variant' : '';
        $orderPrefix = $withVariant ? 'variant ASC, ' : '';
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

    public function countCourseLessons(int $courseId): int
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*)
            FROM course_lessons l
            JOIN course_chapters ch ON ch.id = l.chapter_id
            WHERE ch.course_id = :cid
        ');
        $stmt->execute([':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
    }

    public function countCompletedLessons(int $userId, int $courseId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM lesson_completions WHERE user_id = :uid AND course_id = :cid');
        $stmt->execute([':uid' => $userId, ':cid' => $courseId]);
        return (int) $stmt->fetchColumn();
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

    public function insertQcmAttempt(int $examId, int $userId, string $status, ?int $score, int $autoScore, bool $withVariantIndex, int $variantIndex): int
    {
        $sql = 'INSERT INTO exam_attempts (exam_id, user_id, status, started_at, submitted_at, score, auto_score, manual_score, grader_user_id';
        $vals = ' VALUES (:eid, :uid, :status, NOW(), NOW(), :score, :auto, NULL, NULL';
        $params = [
            ':eid' => $examId,
            ':uid' => $userId,
            ':status' => $status,
            ':score' => $score,
            ':auto' => $autoScore,
        ];
        if ($withVariantIndex) {
            $sql .= ', variant_index';
            $vals .= ', :variant';
            $params[':variant'] = $variantIndex;
        }
        $sql .= ')' . $vals . ')';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    public function insertExamAnswer(int $attemptId, int $questionId, ?int $optionId, ?string $answerText): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_answers (attempt_id, question_id, option_id, answer_text) VALUES (:aid, :qid, :oid, :txt)');
        $stmt->execute([
            ':aid' => $attemptId,
            ':qid' => $questionId,
            ':oid' => $optionId,
            ':txt' => $answerText,
        ]);
    }

    public function insertDriveAttempt(int $examId, int $userId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_attempts (exam_id, user_id, status, started_at, submitted_at, score, auto_score, manual_score, grader_user_id)
            VALUES (:eid, :uid, "submitted", NOW(), NOW(), NULL, NULL, NULL, NULL)');
        $stmt->execute([':eid' => $examId, ':uid' => $userId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function insertExamFileWithDriveMeta(
        int $attemptId,
        ?string $driveLink,
        ?string $driveFileId,
        ?string $driveViewLink,
        ?string $originalName,
        ?string $mimeType,
        ?int $sizeBytes,
        ?string $comment
    ): void {
        $stmt = $this->pdo->prepare('INSERT INTO exam_files (attempt_id, drive_link, drive_file_id, drive_view_link, original_name, mime_type, size_bytes, comment, created_at)
            VALUES (:aid, :link, :fid, :vlink, :oname, :mime, :size, :comment, NOW())');
        $stmt->execute([
            ':aid' => $attemptId,
            ':link' => $driveLink,
            ':fid' => $driveFileId,
            ':vlink' => $driveViewLink,
            ':oname' => $originalName,
            ':mime' => $mimeType,
            ':size' => $sizeBytes,
            ':comment' => $comment,
        ]);
    }

    public function insertExamFileLegacy(int $attemptId, ?string $driveLink, ?string $comment): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_files (attempt_id, drive_link, comment, created_at) VALUES (:aid, :link, :comment, NOW())');
        $stmt->execute([
            ':aid' => $attemptId,
            ':link' => $driveLink,
            ':comment' => $comment,
        ]);
    }

    public function hasBookedMeeting(int $examId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM exam_meetings WHERE exam_id = :eid AND booked_by = :uid AND status = "booked" LIMIT 1');
        $stmt->execute([':eid' => $examId, ':uid' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function findMeetingById(int $meetingId, int $examId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_meetings WHERE id = :id AND exam_id = :eid LIMIT 1');
        $stmt->execute([':id' => $meetingId, ':eid' => $examId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function reserveMeeting(int $meetingId, int $examId, int $userId): int
    {
        $stmt = $this->pdo->prepare('UPDATE exam_meetings
            SET status = "booked", booked_by = :uid, updated_at = NOW()
            WHERE id = :id AND exam_id = :eid AND status = "available" AND booked_by IS NULL');
        $stmt->execute([':uid' => $userId, ':id' => $meetingId, ':eid' => $examId]);
        return $stmt->rowCount();
    }

    public function createMeetingAttempt(int $examId, int $userId, int $meetingId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_attempts (exam_id, user_id, meeting_id, status, started_at, submitted_at, score, auto_score, manual_score, grader_user_id)
            VALUES (:eid, :uid, :mid, "submitted", NOW(), NOW(), NULL, NULL, NULL, NULL)');
        $stmt->execute([':eid' => $examId, ':uid' => $userId, ':mid' => $meetingId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function releaseMeeting(int $meetingId, int $examId, int $userId): int
    {
        $stmt = $this->pdo->prepare('UPDATE exam_meetings
            SET status = "available", booked_by = NULL, updated_at = NOW()
            WHERE id = :id AND exam_id = :eid AND booked_by = :uid AND status = "booked"');
        $stmt->execute([':id' => $meetingId, ':eid' => $examId, ':uid' => $userId]);
        return $stmt->rowCount();
    }

    public function deleteUngradedMeetingAttempts(int $examId, int $userId, int $meetingId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_attempts WHERE exam_id = :eid AND user_id = :uid AND meeting_id = :mid AND status <> "graded"');
        $stmt->execute([':eid' => $examId, ':uid' => $userId, ':mid' => $meetingId]);
    }

    public function findMyMeeting(int $examId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_meetings
            WHERE exam_id = :eid AND booked_by = :uid AND status IN ("booked","done")
            ORDER BY (start_at < NOW()), start_at ASC, id ASC
            LIMIT 1');
        $stmt->execute([':eid' => $examId, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findMeetingAttempt(int $examId, int $userId, int $meetingId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_attempts WHERE exam_id = :eid AND user_id = :uid AND meeting_id = :mid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':eid' => $examId, ':uid' => $userId, ':mid' => $meetingId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAvailableMeetingSlots(int $examId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_meetings
            WHERE exam_id = :eid AND status = "available" AND start_at >= NOW()
            ORDER BY start_at ASC, id ASC
            LIMIT 200');
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchAttemptHistory(int $examId, int $userId, bool $withMeetingData): array
    {
        $sql = 'SELECT ea.*';
        if ($withMeetingData) {
            $sql .= ', em.start_at AS meeting_start, em.end_at AS meeting_end, em.status AS meeting_status, em.mode AS meeting_mode, em.link AS meeting_link, em.place AS meeting_place';
        }
        $sql .= ' FROM exam_attempts ea';
        if ($withMeetingData) {
            $sql .= ' LEFT JOIN exam_meetings em ON em.id = ea.meeting_id';
        }
        $sql .= ' WHERE ea.exam_id = :eid AND ea.user_id = :uid
            ORDER BY ea.created_at DESC, ea.id DESC
            LIMIT 50';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':eid' => $examId, ':uid' => $userId]);
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
}
