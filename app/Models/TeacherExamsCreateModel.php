<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherExamsCreateModel extends Model
{
    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findLatestExamByCourse(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_sessions WHERE course_id = :cid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':cid' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchExamSummariesByCourse(int $courseId, int $limit = 12): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->pdo->prepare('SELECT * FROM exam_sessions WHERE course_id = :cid ORDER BY id DESC LIMIT ' . $limit);
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    public function countAttemptsByExamId(int $examId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM exam_attempts WHERE exam_id = :eid');
        $stmt->execute([':eid' => $examId]);
        return (int) $stmt->fetchColumn();
    }

    public function fetchQuestionsByExamId(int $examId, bool $withVariant): array
    {
        $selectVariant = $withVariant ? ', variant' : '';
        $orderPrefix = $withVariant ? 'variant ASC, ' : '';
        $stmt = $this->pdo->prepare('SELECT id, question_text, question_type, order_index, points' . $selectVariant . ' FROM exam_questions WHERE exam_id = :eid ORDER BY ' . $orderPrefix . 'order_index ASC, id ASC');
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $questionIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchOptionsByQuestionIds(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = $this->pdo->prepare("SELECT question_id, option_text, is_correct FROM exam_options WHERE question_id IN ($placeholders) ORDER BY id ASC");
        $stmt->execute($questionIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function updateExamWithDriveSubject(int $examId, int $courseId, array $payload): void
    {
        $this->updateExam($examId, $courseId, $payload, true, false);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function updateExamWithDriveSubjectAndSettings(int $examId, int $courseId, array $payload): void
    {
        $this->updateExam($examId, $courseId, $payload, true, true);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function updateExamWithoutDriveSubject(int $examId, int $courseId, array $payload): void
    {
        $this->updateExam($examId, $courseId, $payload, false, false);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function updateExamWithoutDriveSubjectAndSettings(int $examId, int $courseId, array $payload): void
    {
        $this->updateExam($examId, $courseId, $payload, false, true);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function insertExamWithDriveSubject(int $courseId, array $payload): int
    {
        return $this->insertExam($courseId, $payload, true, false);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function insertExamWithDriveSubjectAndSettings(int $courseId, array $payload): int
    {
        return $this->insertExam($courseId, $payload, true, true);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function insertExamWithoutDriveSubject(int $courseId, array $payload): int
    {
        return $this->insertExam($courseId, $payload, false, false);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function insertExamWithoutDriveSubjectAndSettings(int $courseId, array $payload): int
    {
        return $this->insertExam($courseId, $payload, false, true);
    }

    public function deleteQuestionsByExamId(int $examId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM exam_questions WHERE exam_id = :eid');
        $stmt->execute([':eid' => $examId]);
    }

    public function insertQuestion(
        int $examId,
        int $variant,
        string $questionText,
        string $questionType,
        int $orderIndex,
        int $points,
        bool $withVariant
    ): int {
        if ($withVariant) {
            $stmt = $this->pdo->prepare('INSERT INTO exam_questions (exam_id, variant, question_text, question_type, order_index, points)
                VALUES (:eid, :variant, :qt, :qt_type, :ord, :pts)');
            $stmt->execute([
                ':eid' => $examId,
                ':variant' => $variant,
                ':qt' => $questionText,
                ':qt_type' => $questionType,
                ':ord' => $orderIndex,
                ':pts' => $points,
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO exam_questions (exam_id, question_text, question_type, order_index, points)
                VALUES (:eid, :qt, :qt_type, :ord, :pts)');
            $stmt->execute([
                ':eid' => $examId,
                ':qt' => $questionText,
                ':qt_type' => $questionType,
                ':ord' => $orderIndex,
                ':pts' => $points,
            ]);
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function insertOption(int $questionId, string $optionText, bool $isCorrect): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_options (question_id, option_text, is_correct) VALUES (:qid, :opt, :isc)');
        $stmt->execute([
            ':qid' => $questionId,
            ':opt' => $optionText,
            ':isc' => $isCorrect ? 1 : 0,
        ]);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function updateExam(int $examId, int $courseId, array $payload, bool $withDriveSubject, bool $withSettings): void
    {
        $setParts = [
            'title = :title',
            'description = :descr',
            'type = :type',
            'duration_min = :dur',
            'max_attempts = :maxa',
            'min_score = :mins',
            'meeting_mode = :mmode',
            'meeting_link = :mlink',
            'meeting_place = :mplace',
            'drive_expected = :drive',
        ];
        $params = [
            ':title' => $payload['title'] ?? null,
            ':descr' => $payload['description'] ?? null,
            ':type' => $payload['type'] ?? null,
            ':dur' => $payload['duration_min'] ?? null,
            ':maxa' => $payload['max_attempts'] ?? null,
            ':mins' => $payload['min_score'] ?? null,
            ':mmode' => $payload['meeting_mode'] ?? null,
            ':mlink' => $payload['meeting_link'] ?? null,
            ':mplace' => $payload['meeting_place'] ?? null,
            ':drive' => $payload['drive_expected'] ?? 0,
            ':id' => $examId,
            ':cid' => $courseId,
        ];

        if ($withDriveSubject) {
            $setParts[] = 'drive_subject_link = :drive_subject_link';
            $setParts[] = 'drive_subject_name = :drive_subject_name';
            $params[':drive_subject_link'] = $payload['drive_subject_link'] ?? null;
            $params[':drive_subject_name'] = $payload['drive_subject_name'] ?? null;
        }

        if ($withSettings) {
            $setParts[] = 'settings_json = :settings_json';
            $params[':settings_json'] = $payload['settings_json'] ?? null;
        }

        $setParts[] = 'updated_at = NOW()';

        $sql = 'UPDATE exam_sessions SET ' . implode(', ', $setParts) . ' WHERE id = :id AND course_id = :cid';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function insertExam(int $courseId, array $payload, bool $withDriveSubject, bool $withSettings): int
    {
        $columns = [
            'course_id',
            'title',
            'description',
            'type',
            'duration_min',
            'max_attempts',
            'min_score',
            'meeting_mode',
            'meeting_link',
            'meeting_place',
            'drive_expected',
        ];
        $values = [
            ':cid',
            ':title',
            ':descr',
            ':type',
            ':dur',
            ':maxa',
            ':mins',
            ':mmode',
            ':mlink',
            ':mplace',
            ':drive',
        ];
        $params = [
            ':cid' => $courseId,
            ':title' => $payload['title'] ?? null,
            ':descr' => $payload['description'] ?? null,
            ':type' => $payload['type'] ?? null,
            ':dur' => $payload['duration_min'] ?? null,
            ':maxa' => $payload['max_attempts'] ?? null,
            ':mins' => $payload['min_score'] ?? null,
            ':mmode' => $payload['meeting_mode'] ?? null,
            ':mlink' => $payload['meeting_link'] ?? null,
            ':mplace' => $payload['meeting_place'] ?? null,
            ':drive' => $payload['drive_expected'] ?? 0,
        ];

        if ($withDriveSubject) {
            $columns[] = 'drive_subject_link';
            $columns[] = 'drive_subject_name';
            $values[] = ':drive_subject_link';
            $values[] = ':drive_subject_name';
            $params[':drive_subject_link'] = $payload['drive_subject_link'] ?? null;
            $params[':drive_subject_name'] = $payload['drive_subject_name'] ?? null;
        }

        if ($withSettings) {
            $columns[] = 'settings_json';
            $values[] = ':settings_json';
            $params[':settings_json'] = $payload['settings_json'] ?? null;
        }

        $sql = 'INSERT INTO exam_sessions (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }
}
