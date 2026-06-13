<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherExamPreviewModel extends Model
{
    public function findExamByIdWithCourse(int $examId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT es.*, c.id AS course_id, c.title AS course_title, c.teacher_id
             FROM exam_sessions es
             JOIN courses c ON c.id = es.course_id
             WHERE es.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $examId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function fetchQuestions(int $examId, bool $hasVariant): array
    {
        $selectVariant = $hasVariant ? ', variant' : '';
        $orderPrefix = $hasVariant ? 'variant ASC, ' : '';
        $stmt = $this->pdo->prepare(
            'SELECT id, question_text, question_type, order_index, points' . $selectVariant . '
             FROM exam_questions
             WHERE exam_id = :eid
             ORDER BY ' . $orderPrefix . 'order_index ASC, id ASC'
        );
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param list<int> $questionIds
     */
    public function fetchOptionsByQuestionIds(array $questionIds): array
    {
        if ($questionIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id, question_id, option_text, is_correct
             FROM exam_options
             WHERE question_id IN ($placeholders)
             ORDER BY question_id ASC, id ASC"
        );
        $stmt->execute($questionIds);
        return $stmt->fetchAll() ?: [];
    }

    public function fetchMeetingSlots(int $examId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM exam_meetings WHERE exam_id = :eid ORDER BY start_at ASC');
        $stmt->execute([':eid' => $examId]);
        return $stmt->fetchAll() ?: [];
    }
}
