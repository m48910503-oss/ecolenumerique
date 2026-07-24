<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class EnrollmentModel extends Model
{
    public function countWithCourseProgressByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             WHERE e.user_id = :uid'
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findWithCourseProgressByUserIdPage(int $userId, int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        $stmt = $this->pdo->prepare(
            'SELECT
                c.id,
                c.teacher_id,
                c.title,
                c.level,
                c.slug,
                c.cover_path,
                c.cover_alt,
                c.status AS course_status,
                u.first_name AS teacher_first_name,
                u.last_name AS teacher_last_name,
                u.email AS teacher_email,
                e.status AS enroll_status,
                e.created_at AS enrolled_at,
                cr.rating AS user_rating,
                cr.comment AS user_rating_comment,
                (SELECT COUNT(DISTINCT cl.id)
                 FROM course_lessons cl
                 JOIN course_chapters ch ON ch.id = cl.chapter_id
                 WHERE ch.course_id = c.id) AS total_lessons,
                (SELECT COUNT(DISTINCT lc.lesson_id)
                 FROM lesson_completions lc
                 JOIN course_lessons cl_done ON cl_done.id = lc.lesson_id
                 JOIN course_chapters ch_done ON ch_done.id = cl_done.chapter_id
                 WHERE ch_done.course_id = c.id AND lc.user_id = :uid_done) AS done_lessons
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             LEFT JOIN users u ON u.id = c.teacher_id
             LEFT JOIN course_ratings cr ON cr.course_id = c.id AND cr.user_id = :uid_rating
             WHERE e.user_id = :uid_main
             ORDER BY e.created_at DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute([
            ':uid_done' => $userId,
            ':uid_rating' => $userId,
            ':uid_main' => $userId,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    public function findWithCourseProgressByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                c.id,
                c.teacher_id,
                c.title,
                c.level,
                c.slug,
                c.cover_path,
                c.cover_alt,
                c.status AS course_status,
                u.first_name AS teacher_first_name,
                u.last_name AS teacher_last_name,
                u.email AS teacher_email,
                e.status AS enroll_status,
                e.created_at AS enrolled_at,
                cr.rating AS user_rating,
                cr.comment AS user_rating_comment,
                (SELECT COUNT(DISTINCT cl.id)
                 FROM course_lessons cl
                 JOIN course_chapters ch ON ch.id = cl.chapter_id
                 WHERE ch.course_id = c.id) AS total_lessons,
                (SELECT COUNT(DISTINCT lc.lesson_id)
                 FROM lesson_completions lc
                 JOIN course_lessons cl_done ON cl_done.id = lc.lesson_id
                 JOIN course_chapters ch_done ON ch_done.id = cl_done.chapter_id
                 WHERE ch_done.course_id = c.id AND lc.user_id = :uid_done) AS done_lessons
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             LEFT JOIN users u ON u.id = c.teacher_id
             LEFT JOIN course_ratings cr ON cr.course_id = c.id AND cr.user_id = :uid_rating
             WHERE e.user_id = :uid_main
             ORDER BY e.created_at DESC'
        );
        $stmt->execute([
            ':uid_done' => $userId,
            ':uid_rating' => $userId,
            ':uid_main' => $userId,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Retourne la derniere tentative par examen (examens deja composes).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAttemptedExamsByUserId(int $userId, int $limit = 100): array
    {
        return $this->findAttemptedExamsByUserIdPage($userId, 0, $limit);
    }

    public function countAttemptedExamsByUserId(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM exam_attempts ea
             JOIN exam_sessions es ON es.id = ea.exam_id
             JOIN courses c ON c.id = es.course_id
             JOIN enrollments e ON e.course_id = c.id
                AND e.user_id = :uid_enroll
                AND e.status = "active"
             WHERE ea.user_id = :uid_main
               AND ea.status IN ("submitted", "graded")
               AND ea.id = (
                   SELECT ea3.id
                   FROM exam_attempts ea3
                   WHERE ea3.exam_id = ea.exam_id
                     AND ea3.user_id = :uid_last
                     AND ea3.status IN ("submitted", "graded")
                   ORDER BY ea3.id DESC
                   LIMIT 1
               )'
        );
        $stmt->execute([
            ':uid_enroll' => $userId,
            ':uid_main' => $userId,
            ':uid_last' => $userId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findAttemptedExamsByUserIdPage(int $userId, int $offset, int $limit = 100): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            'SELECT
                es.id AS exam_id,
                es.title AS exam_title,
                es.type AS exam_type,
                es.min_score,
                c.id AS course_id,
                c.title AS course_title,
                c.slug AS course_slug,
                ea.id AS attempt_id,
                ea.status AS attempt_status,
                ea.score AS attempt_score,
                ea.submitted_at AS attempt_submitted_at,
                ea.created_at AS attempt_created_at,
                (SELECT COUNT(*)
                 FROM exam_attempts ea2
                 WHERE ea2.exam_id = es.id AND ea2.user_id = :uid_count) AS attempts_count
             FROM exam_attempts ea
             JOIN exam_sessions es ON es.id = ea.exam_id
             JOIN courses c ON c.id = es.course_id
             JOIN enrollments e ON e.course_id = c.id
                AND e.user_id = :uid_enroll
                AND e.status = "active"
             WHERE ea.user_id = :uid_main
               AND ea.status IN ("submitted", "graded")
               AND ea.id = (
                   SELECT ea3.id
                   FROM exam_attempts ea3
                   WHERE ea3.exam_id = ea.exam_id
                     AND ea3.user_id = :uid_last
                     AND ea3.status IN ("submitted", "graded")
                   ORDER BY ea3.id DESC
                   LIMIT 1
               )
             ORDER BY COALESCE(ea.submitted_at, ea.created_at) DESC, ea.id DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute([
            ':uid_count' => $userId,
            ':uid_enroll' => $userId,
            ':uid_main' => $userId,
            ':uid_last' => $userId,
        ]);
        return $stmt->fetchAll() ?: [];
    }
}
