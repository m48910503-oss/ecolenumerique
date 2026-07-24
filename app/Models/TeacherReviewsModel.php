<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherReviewsModel extends Model
{
    private ?bool $ratingsModerationReady = null;
    private ?bool $hasHiddenByUserColumn = null;
    private ?bool $hasHiddenAtColumn = null;
    private ?bool $hasUpdatedAtColumn = null;
    private ?bool $hasTeacherReplyTextColumn = null;
    private ?bool $hasTeacherReplyUserColumn = null;
    private ?bool $hasTeacherReplyAtColumn = null;
    private ?bool $hasReviewerAvatarColumn = null;

    public function ratingsModerationReady(): bool
    {
        if (is_bool($this->ratingsModerationReady)) {
            return $this->ratingsModerationReady;
        }
        $this->ratingsModerationReady = $this->columnExists('course_ratings', 'is_hidden_by_teacher');
        return $this->ratingsModerationReady;
    }

    public function hasHiddenByUserColumn(): bool
    {
        if (is_bool($this->hasHiddenByUserColumn)) {
            return $this->hasHiddenByUserColumn;
        }
        $this->hasHiddenByUserColumn = $this->columnExists('course_ratings', 'hidden_by_user_id');
        return $this->hasHiddenByUserColumn;
    }

    public function hasHiddenAtColumn(): bool
    {
        if (is_bool($this->hasHiddenAtColumn)) {
            return $this->hasHiddenAtColumn;
        }
        $this->hasHiddenAtColumn = $this->columnExists('course_ratings', 'hidden_at');
        return $this->hasHiddenAtColumn;
    }

    public function hasUpdatedAtColumn(): bool
    {
        if (is_bool($this->hasUpdatedAtColumn)) {
            return $this->hasUpdatedAtColumn;
        }
        $this->hasUpdatedAtColumn = $this->columnExists('course_ratings', 'updated_at');
        return $this->hasUpdatedAtColumn;
    }

    public function hasTeacherReplyTextColumn(): bool
    {
        if (is_bool($this->hasTeacherReplyTextColumn)) {
            return $this->hasTeacherReplyTextColumn;
        }
        $this->hasTeacherReplyTextColumn = $this->columnExists('course_ratings', 'teacher_reply_text');
        return $this->hasTeacherReplyTextColumn;
    }

    public function hasTeacherReplyUserColumn(): bool
    {
        if (is_bool($this->hasTeacherReplyUserColumn)) {
            return $this->hasTeacherReplyUserColumn;
        }
        $this->hasTeacherReplyUserColumn = $this->columnExists('course_ratings', 'teacher_reply_user_id');
        return $this->hasTeacherReplyUserColumn;
    }

    public function hasTeacherReplyAtColumn(): bool
    {
        if (is_bool($this->hasTeacherReplyAtColumn)) {
            return $this->hasTeacherReplyAtColumn;
        }
        $this->hasTeacherReplyAtColumn = $this->columnExists('course_ratings', 'teacher_reply_at');
        return $this->hasTeacherReplyAtColumn;
    }

    public function hasReviewerAvatarColumn(): bool
    {
        if (is_bool($this->hasReviewerAvatarColumn)) {
            return $this->hasReviewerAvatarColumn;
        }

        $this->hasReviewerAvatarColumn = $this->columnExists('users', 'avatar_url');
        return $this->hasReviewerAvatarColumn;
    }

    public function ratingsReplyReady(): bool
    {
        return $this->hasTeacherReplyTextColumn()
            && $this->hasTeacherReplyUserColumn()
            && $this->hasTeacherReplyAtColumn();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchCourseOptions(int $teacherId, bool $includeArchived = false): array
    {
        $sql = 'SELECT id, title, status, deleted_at
                FROM courses
                WHERE teacher_id = :tid';
        if (!$includeArchived) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' ORDER BY title ASC';

        $stmt = $this->pdo->prepare(
            $sql
        );
        $stmt->execute([':tid' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,int> keyed by course id
     */
    public function fetchCourseStudentCounts(int $teacherId, bool $includeArchived = false): array
    {
        if (!$this->tableExists('enrollments')) {
            return [];
        }

        $courseFilterSql = $includeArchived ? '' : ' AND c.deleted_at IS NULL';

        $stmt = $this->pdo->prepare(
            'SELECT
                c.id AS course_id,
                COALESCE(students.student_count, 0) AS student_count
             FROM courses c
             LEFT JOIN (
                SELECT
                    e.course_id,
                    COUNT(DISTINCT e.user_id) AS student_count
                FROM enrollments e
                WHERE e.status = "active"
                GROUP BY e.course_id
             ) students ON students.course_id = c.id
             WHERE c.teacher_id = :tid
             ' . $courseFilterSql
        );
        $stmt->execute([':tid' => $teacherId]);
        $rows = $stmt->fetchAll() ?: [];

        $counts = [];
        foreach ($rows as $row) {
            $courseId = max(0, (int) ($row['course_id'] ?? 0));
            if ($courseId < 1) {
                continue;
            }
            $counts[$courseId] = max(0, (int) ($row['student_count'] ?? 0));
        }

        return $counts;
    }

    /**
     * @return array{total:int,hidden:int,visible:int,avg:float}
     */
    public function fetchSummary(int $teacherId, bool $includeArchived = false): array
    {
        $courseFilterSql = $includeArchived ? '' : ' AND c.deleted_at IS NULL';
        if ($this->ratingsModerationReady()) {
            $stmt = $this->pdo->prepare(
                'SELECT
                    COUNT(*) AS total_reviews,
                    COALESCE(SUM(CASE WHEN cr.is_hidden_by_teacher = 1 THEN 1 ELSE 0 END), 0) AS hidden_reviews,
                    COALESCE(AVG(cr.rating), 0) AS avg_rating
                 FROM course_ratings cr
                 JOIN courses c ON c.id = cr.course_id
                 WHERE c.teacher_id = :tid
                 ' . $courseFilterSql
            );
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT
                    COUNT(*) AS total_reviews,
                    0 AS hidden_reviews,
                    COALESCE(AVG(cr.rating), 0) AS avg_rating
                 FROM course_ratings cr
                 JOIN courses c ON c.id = cr.course_id
                 WHERE c.teacher_id = :tid
                 ' . $courseFilterSql
            );
        }

        $stmt->execute([':tid' => $teacherId]);
        $row = $stmt->fetch() ?: [];
        $total = max(0, (int) ($row['total_reviews'] ?? 0));
        $hidden = max(0, (int) ($row['hidden_reviews'] ?? 0));

        return [
            'total' => $total,
            'hidden' => min($hidden, $total),
            'visible' => max(0, $total - $hidden),
            'avg' => round((float) ($row['avg_rating'] ?? 0), 2),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchReviewRows(
        int $teacherId,
        int $courseId = 0,
        string $visibility = 'all',
        int $ratingMin = 0,
        int $limit = 150,
        bool $includeArchived = false
    ): array {
        $limit = max(1, min(5000, $limit));
        $params = [':tid' => $teacherId];
        $where = [
            'c.teacher_id = :tid',
        ];
        if (!$includeArchived) {
            $where[] = 'c.deleted_at IS NULL';
        }

        if ($courseId > 0) {
            $where[] = 'cr.course_id = :course_id';
            $params[':course_id'] = $courseId;
        }

        if ($ratingMin > 0) {
            $where[] = 'cr.rating >= :rating_min';
            $params[':rating_min'] = $ratingMin;
        }

        $moderationReady = $this->ratingsModerationReady();
        if ($moderationReady) {
            if ($visibility === 'visible') {
                $where[] = 'COALESCE(cr.is_hidden_by_teacher, 0) = 0';
            } elseif ($visibility === 'hidden') {
                $where[] = 'COALESCE(cr.is_hidden_by_teacher, 0) = 1';
            }
        } elseif ($visibility === 'hidden') {
            return [];
        }

        $hiddenSelect = $moderationReady
            ? 'COALESCE(cr.is_hidden_by_teacher, 0) AS is_hidden_by_teacher'
            : '0 AS is_hidden_by_teacher';
        $hiddenAtSelect = $this->hasHiddenAtColumn() ? 'cr.hidden_at' : 'NULL AS hidden_at';
        $hiddenBySelect = $this->hasHiddenByUserColumn()
            ? 'hu.first_name AS hidden_by_first_name, hu.last_name AS hidden_by_last_name'
            : 'NULL AS hidden_by_first_name, NULL AS hidden_by_last_name';
        $hiddenByJoin = $this->hasHiddenByUserColumn()
            ? 'LEFT JOIN users hu ON hu.id = cr.hidden_by_user_id'
            : '';
        $replyTextSelect = $this->hasTeacherReplyTextColumn()
            ? 'cr.teacher_reply_text'
            : 'NULL AS teacher_reply_text';
        $replyAtSelect = $this->hasTeacherReplyAtColumn()
            ? 'cr.teacher_reply_at'
            : 'NULL AS teacher_reply_at';
        $replyBySelect = $this->hasTeacherReplyUserColumn()
            ? 'tru.first_name AS teacher_reply_first_name, tru.last_name AS teacher_reply_last_name'
            : 'NULL AS teacher_reply_first_name, NULL AS teacher_reply_last_name';
        $replyByJoin = $this->hasTeacherReplyUserColumn()
            ? 'LEFT JOIN users tru ON tru.id = cr.teacher_reply_user_id'
            : '';
        $reviewerAvatarSelect = $this->hasReviewerAvatarColumn()
            ? 'u.avatar_url AS reviewer_avatar_url'
            : 'NULL AS reviewer_avatar_url';

        $sql = 'SELECT
                    cr.id,
                    cr.course_id,
                    cr.rating,
                    cr.comment,
                    cr.created_at,
                    ' . $replyTextSelect . ',
                    ' . $replyAtSelect . ',
                    ' . $hiddenSelect . ',
                    ' . $hiddenAtSelect . ',
                    c.title AS course_title,
                    c.status AS course_status,
                    c.deleted_at AS course_deleted_at,
                    u.first_name AS reviewer_first_name,
                    u.last_name AS reviewer_last_name,
                    ' . $reviewerAvatarSelect . ',
                    ' . $hiddenBySelect . ',
                    ' . $replyBySelect . '
                FROM course_ratings cr
                JOIN courses c ON c.id = cr.course_id
                JOIN users u ON u.id = cr.user_id
                ' . $hiddenByJoin . '
                ' . $replyByJoin . '
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY cr.created_at DESC, cr.id DESC
                LIMIT ' . $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findReviewForTeacher(int $teacherId, int $reviewId): ?array
    {
        if ($reviewId < 1 || $teacherId < 1) {
            return null;
        }

        $hiddenSelect = $this->ratingsModerationReady()
            ? 'COALESCE(cr.is_hidden_by_teacher, 0) AS is_hidden_by_teacher'
            : '0 AS is_hidden_by_teacher';
        $replyTextSelect = $this->hasTeacherReplyTextColumn()
            ? 'cr.teacher_reply_text'
            : 'NULL AS teacher_reply_text';
        $replyAtSelect = $this->hasTeacherReplyAtColumn()
            ? 'cr.teacher_reply_at'
            : 'NULL AS teacher_reply_at';

        $stmt = $this->pdo->prepare(
            'SELECT cr.id, cr.course_id, cr.rating, cr.comment, ' . $hiddenSelect . ', ' . $replyTextSelect . ', ' . $replyAtSelect . '
             FROM course_ratings cr
             JOIN courses c ON c.id = cr.course_id
             WHERE c.teacher_id = :tid
               AND c.deleted_at IS NULL
               AND cr.id = :rid
             LIMIT 1'
        );
        $stmt->execute([
            ':tid' => $teacherId,
            ':rid' => $reviewId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setReviewHiddenState(int $reviewId, bool $hidden, int $actorUserId): void
    {
        if ($reviewId < 1 || !$this->ratingsModerationReady()) {
            return;
        }

        $set = ['is_hidden_by_teacher = :hidden'];
        $params = [
            ':hidden' => $hidden ? 1 : 0,
            ':id' => $reviewId,
        ];

        if ($this->hasHiddenByUserColumn()) {
            $set[] = 'hidden_by_user_id = :hidden_by_user_id';
            $params[':hidden_by_user_id'] = $hidden ? $actorUserId : null;
        }
        if ($this->hasHiddenAtColumn()) {
            $set[] = 'hidden_at = :hidden_at';
            $params[':hidden_at'] = $hidden ? date('Y-m-d H:i:s') : null;
        }
        if ($this->hasUpdatedAtColumn()) {
            $set[] = 'updated_at = NOW()';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE course_ratings
             SET ' . implode(', ', $set) . '
             WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public function saveReviewReply(int $reviewId, string $replyText, int $actorUserId): void
    {
        if ($reviewId < 1 || !$this->ratingsReplyReady()) {
            return;
        }

        $set = [
            'teacher_reply_text = :reply_text',
            'teacher_reply_user_id = :reply_user_id',
            'teacher_reply_at = :reply_at',
        ];
        $params = [
            ':reply_text' => $replyText,
            ':reply_user_id' => $actorUserId,
            ':reply_at' => date('Y-m-d H:i:s'),
            ':id' => $reviewId,
        ];
        if ($this->hasUpdatedAtColumn()) {
            $set[] = 'updated_at = NOW()';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE course_ratings
             SET ' . implode(', ', $set) . '
             WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public function clearReviewReply(int $reviewId): void
    {
        if ($reviewId < 1 || !$this->ratingsReplyReady()) {
            return;
        }

        $set = [
            'teacher_reply_text = NULL',
            'teacher_reply_user_id = NULL',
            'teacher_reply_at = NULL',
        ];
        if ($this->hasUpdatedAtColumn()) {
            $set[] = 'updated_at = NOW()';
        }

        $stmt = $this->pdo->prepare(
            'UPDATE course_ratings
             SET ' . implode(', ', $set) . '
             WHERE id = :id'
        );
        $stmt->execute([':id' => $reviewId]);
    }
}
