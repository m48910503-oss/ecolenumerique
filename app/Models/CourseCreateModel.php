<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CourseCreateModel extends Model
{
    public function findCourseById(int $courseId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM courses WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findTeacherDraftCourses(int $teacherId, int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $sql = 'SELECT c.id, c.title, c.description, c.level, c.status, c.cover_path, c.updated_at, c.created_at,
                       cat.name AS category_name,
                       (SELECT COUNT(*) FROM course_chapters cc WHERE cc.course_id = c.id) AS chapters_cnt,
                       (SELECT COUNT(*)
                          FROM course_lessons cl
                          JOIN course_chapters cc2 ON cc2.id = cl.chapter_id
                         WHERE cc2.course_id = c.id) AS lessons_cnt,
                       (SELECT COUNT(*) FROM course_meetings cm WHERE cm.course_id = c.id AND cm.student_id IS NULL) AS meetings_cnt
                FROM courses c
                LEFT JOIN categories cat ON cat.id = c.category_id
                WHERE c.teacher_id = :teacher_id
                  AND c.deleted_at IS NULL
                  AND c.status IN ("draft", "rejected")
                ORDER BY c.updated_at DESC, c.id DESC
                LIMIT ' . $limit;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':teacher_id' => $teacherId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findActiveCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, name FROM categories WHERE is_deleted = 0 ORDER BY name');
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateCourseFromBuilder(int $courseId, array $data): void
    {
        $set = [
            'category_id = :category_id',
            'title = :title',
            'level = :level',
            'description = :description',
            'objectives = :objectives',
            'prerequisites = :prerequisites',
            'equipment = :equipment',
            'duration_hours = :duration_hours',
            'price_xof = :price_xof',
            'referral_override = :referral_override',
            'referral_l1_rate = :referral_l1_rate',
            'referral_l2_rate = :referral_l2_rate',
            'referral_l2_enabled = :referral_l2_enabled',
            'cover_path = :cover_path',
            'status = :status',
            'submitted_at = :submitted_at',
            'updated_at = NOW()',
        ];

        if ($this->columnExists('courses', 'enterprise_price_xof')) {
            $set[] = 'enterprise_price_xof = :enterprise_price_xof';
        }
        if ($this->columnExists('courses', 'enterprise_discount_percent')) {
            $set[] = 'enterprise_discount_percent = :enterprise_discount_percent';
        }

        $stmt = $this->pdo->prepare('UPDATE courses SET ' . implode(', ', $set) . ' WHERE id = :id');
        $params = [
            ':category_id' => $data['category_id'],
            ':title' => $data['title'],
            ':level' => $data['level'],
            ':description' => $data['description'],
            ':objectives' => $data['objectives'],
            ':prerequisites' => $data['prerequisites'],
            ':equipment' => $data['equipment'],
            ':duration_hours' => $data['duration_hours'],
            ':price_xof' => $data['price_xof'],
            ':referral_override' => $data['referral_override'],
            ':referral_l1_rate' => $data['referral_l1_rate'],
            ':referral_l2_rate' => $data['referral_l2_rate'],
            ':referral_l2_enabled' => $data['referral_l2_enabled'],
            ':cover_path' => $data['cover_path'],
            ':status' => $data['status'],
            ':submitted_at' => $data['submitted_at'],
            ':id' => $courseId,
        ];
        if (in_array('enterprise_price_xof = :enterprise_price_xof', $set, true)) {
            $params[':enterprise_price_xof'] = $data['enterprise_price_xof'] ?? null;
        }
        if (in_array('enterprise_discount_percent = :enterprise_discount_percent', $set, true)) {
            $params[':enterprise_discount_percent'] = $data['enterprise_discount_percent'] ?? null;
        }
        $stmt->execute($params);
    }

    public function categoryExists(int $categoryId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE id = :id AND is_deleted = 0');
        $stmt->execute([':id' => $categoryId]);
        return (bool) $stmt->fetch();
    }

    public function generateCourseSlug(string $title): string
    {
        $base = strtolower(trim((string) preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title) ?: $title), '-'));
        if ($base === '') {
            $base = 'cours';
        }
        $slug = $base;
        $i = 2;
        $check = $this->pdo->prepare('SELECT id FROM courses WHERE slug = :slug LIMIT 1');
        while (true) {
            $check->execute([':slug' => $slug]);
            if (!$check->fetch()) {
                return $slug;
            }
            $slug = $base . '-' . $i;
            $i++;
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public function createCourse(array $data): int
    {
        $columns = [
            'teacher_id', 'category_id', 'title', 'slug', 'level', 'description', 'objectives', 'prerequisites', 'equipment',
            'duration_hours', 'price_xof',
            'referral_override', 'referral_l1_rate', 'referral_l2_rate', 'referral_l2_enabled',
            'general_file_path', 'general_file_mime', 'general_file_size', 'cover_path', 'cover_alt',
            'status', 'submitted_at',
            'created_at', 'updated_at',
        ];
        $values = [
            ':teacher_id', ':category_id', ':title', ':slug', ':level', ':description', ':objectives', ':prerequisites', ':equipment',
            ':duration_hours', ':price_xof',
            ':referral_override', ':referral_l1_rate', ':referral_l2_rate', ':referral_l2_enabled',
            ':general_file_path', ':general_file_mime', ':general_file_size', ':cover_path', ':cover_alt',
            ':status', ':submitted_at',
            'NOW()', 'NOW()',
        ];

        try {
            if ($this->columnExists('courses', 'enterprise_price_xof')) {
                $columns[] = 'enterprise_price_xof';
                $values[] = ':enterprise_price_xof';
            }
            if ($this->columnExists('courses', 'enterprise_discount_percent')) {
                $columns[] = 'enterprise_discount_percent';
                $values[] = ':enterprise_discount_percent';
            }
        } catch (\Throwable $e) {
            // Legacy schema: ignore enterprise pricing columns.
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO courses (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $values) . ')'
        );

        $params = [
            ':teacher_id' => $data['teacher_id'],
            ':category_id' => $data['category_id'],
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':level' => $data['level'],
            ':description' => $data['description'],
            ':objectives' => $data['objectives'],
            ':prerequisites' => $data['prerequisites'],
            ':equipment' => $data['equipment'],
            ':duration_hours' => $data['duration_hours'],
            ':price_xof' => $data['price_xof'],
            ':referral_override' => $data['referral_override'],
            ':referral_l1_rate' => $data['referral_l1_rate'],
            ':referral_l2_rate' => $data['referral_l2_rate'],
            ':referral_l2_enabled' => $data['referral_l2_enabled'],
            ':general_file_path' => null,
            ':general_file_mime' => null,
            ':general_file_size' => null,
            ':cover_path' => $data['cover_path'],
            ':cover_alt' => null,
            ':status' => $data['status'],
            ':submitted_at' => $data['submitted_at'],
        ];
        if (in_array('enterprise_price_xof', $columns, true)) {
            $params[':enterprise_price_xof'] = $data['enterprise_price_xof'] ?? null;
        }
        if (in_array('enterprise_discount_percent', $columns, true)) {
            $params[':enterprise_discount_percent'] = $data['enterprise_discount_percent'] ?? null;
        }
        $manualId = 0;
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if ($this->isDuplicateZeroPrimary($e)) {
                $manualId = $this->nextTableId('courses');
                if ($manualId < 1) {
                    throw $e;
                }
                $columnsWithId = array_merge(['id'], $columns);
                $valuesWithId = array_merge([':id'], $values);
                $stmtWithId = $this->pdo->prepare(
                    'INSERT INTO courses (' . implode(', ', $columnsWithId) . ')
                     VALUES (' . implode(', ', $valuesWithId) . ')'
                );
                $paramsWithId = $params;
                $paramsWithId[':id'] = $manualId;
                $stmtWithId->execute($paramsWithId);
            } else {
                throw $e;
            }
        }

        $courseId = (int) $this->pdo->lastInsertId();
        if ($courseId > 0) {
            return $courseId;
        }
        if ($manualId > 0) {
            return $manualId;
        }

        $fallbackCourseId = $this->findCourseIdBySlug((string) ($data['slug'] ?? ''));
        if ($fallbackCourseId > 0) {
            return $fallbackCourseId;
        }

        throw new \RuntimeException('Impossible de recuperer l identifiant du cours apres creation.');
    }

    public function updateCourseGeneralFile(int $courseId, ?string $path, ?string $mime, ?int $size): void
    {
        $stmt = $this->pdo->prepare('UPDATE courses SET general_file_path = :path, general_file_mime = :mime, general_file_size = :size WHERE id = :id');
        $stmt->execute([
            ':path' => $path,
            ':mime' => $mime,
            ':size' => $size,
            ':id' => $courseId,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderChapters(int $courseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM course_chapters WHERE course_id = :cid ORDER BY position ASC, id ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderLessonsByChapterIds(array $chapterIds): array
    {
        $chapterIds = array_values(array_filter(array_map('intval', $chapterIds), static fn(int $id): bool => $id > 0));
        if ($chapterIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM course_lessons WHERE chapter_id IN ({$placeholders}) ORDER BY chapter_id ASC, position ASC, id ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $chapterIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderDocsByChapterIds(array $chapterIds): array
    {
        $chapterIds = array_values(array_filter(array_map('intval', $chapterIds), static fn(int $id): bool => $id > 0));
        if ($chapterIds === [] || !$this->tableExists('chapter_documents')) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM chapter_documents WHERE chapter_id IN ({$placeholders}) ORDER BY chapter_id ASC, position ASC, id ASC");
        $stmt->execute($chapterIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderMeetings(int $courseId): array
    {
        if (!$this->tableExists('course_meetings')) {
            return [];
        }
        $studentFilter = $this->safeColumnExists('course_meetings', 'student_id') ? ' AND student_id IS NULL' : '';
        $order = $this->safeColumnExists('course_meetings', 'chapter_id') ? 'chapter_id ASC, id ASC' : 'id ASC';
        $stmt = $this->pdo->prepare('SELECT * FROM course_meetings WHERE course_id = :cid' . $studentFilter . ' ORDER BY ' . $order);
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderExams(int $courseId): array
    {
        if (!$this->tableExists('exam_sessions')) {
            return [];
        }
        $stmt = $this->pdo->prepare('SELECT * FROM exam_sessions WHERE course_id = :cid ORDER BY id ASC');
        $stmt->execute([':cid' => $courseId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $examIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderQuestionsByExamIds(array $examIds): array
    {
        $examIds = array_values(array_filter(array_map('intval', $examIds), static fn(int $id): bool => $id > 0));
        if ($examIds === [] || !$this->tableExists('exam_questions')) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($examIds), '?'));
        $withVariant = $this->safeColumnExists('exam_questions', 'variant');
        $order = $withVariant ? 'exam_id ASC, variant ASC, order_index ASC, id ASC' : 'exam_id ASC, order_index ASC, id ASC';
        $stmt = $this->pdo->prepare("SELECT * FROM exam_questions WHERE exam_id IN ({$placeholders}) ORDER BY {$order}");
        $stmt->execute($examIds);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param array<int,int> $questionIds
     * @return array<int,array<string,mixed>>
     */
    public function fetchBuilderOptionsByQuestionIds(array $questionIds): array
    {
        $questionIds = array_values(array_filter(array_map('intval', $questionIds), static fn(int $id): bool => $id > 0));
        if ($questionIds === [] || !$this->tableExists('exam_options')) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM exam_options WHERE question_id IN ({$placeholders}) ORDER BY question_id ASC, id ASC");
        $stmt->execute($questionIds);
        return $stmt->fetchAll() ?: [];
    }

    public function deleteBuilderContentForCourse(int $courseId): void
    {
        $chapterIdsStmt = $this->pdo->prepare('SELECT id FROM course_chapters WHERE course_id = :cid');
        $chapterIdsStmt->execute([':cid' => $courseId]);
        $chapterIds = array_values(array_filter(array_map('intval', $chapterIdsStmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));

        if ($chapterIds !== []) {
            $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
            if ($this->tableExists('chapter_documents')) {
                $stmt = $this->pdo->prepare("DELETE FROM chapter_documents WHERE chapter_id IN ({$placeholders})");
                $stmt->execute($chapterIds);
            }
            $stmt = $this->pdo->prepare("DELETE FROM course_lessons WHERE chapter_id IN ({$placeholders})");
            $stmt->execute($chapterIds);
        }

        if ($this->tableExists('course_meetings')) {
            $studentFilter = $this->safeColumnExists('course_meetings', 'student_id') ? ' AND student_id IS NULL' : '';
            $stmt = $this->pdo->prepare('DELETE FROM course_meetings WHERE course_id = :cid' . $studentFilter);
            $stmt->execute([':cid' => $courseId]);
        }

        if ($chapterIds !== []) {
            $placeholders = implode(',', array_fill(0, count($chapterIds), '?'));
            $stmt = $this->pdo->prepare("DELETE FROM course_chapters WHERE id IN ({$placeholders})");
            $stmt->execute($chapterIds);
        }
    }

    public function deleteBuilderExamsForCourse(int $courseId): void
    {
        if (!$this->tableExists('exam_sessions')) {
            return;
        }

        $examIdsStmt = $this->pdo->prepare('SELECT id FROM exam_sessions WHERE course_id = :cid');
        $examIdsStmt->execute([':cid' => $courseId]);
        $examIds = array_values(array_filter(array_map('intval', $examIdsStmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
        if ($examIds !== []) {
            $examPlaceholders = implode(',', array_fill(0, count($examIds), '?'));
            if ($this->tableExists('exam_questions')) {
                $questionStmt = $this->pdo->prepare("SELECT id FROM exam_questions WHERE exam_id IN ({$examPlaceholders})");
                $questionStmt->execute($examIds);
                $questionIds = array_values(array_filter(array_map('intval', $questionStmt->fetchAll(\PDO::FETCH_COLUMN) ?: [])));
                if ($questionIds !== [] && $this->tableExists('exam_options')) {
                    $questionPlaceholders = implode(',', array_fill(0, count($questionIds), '?'));
                    $stmt = $this->pdo->prepare("DELETE FROM exam_options WHERE question_id IN ({$questionPlaceholders})");
                    $stmt->execute($questionIds);
                }
                $stmt = $this->pdo->prepare("DELETE FROM exam_questions WHERE exam_id IN ({$examPlaceholders})");
                $stmt->execute($examIds);
            }
        }
        $stmt = $this->pdo->prepare('DELETE FROM exam_sessions WHERE course_id = :cid');
        $stmt->execute([':cid' => $courseId]);
    }

    public function deleteBuilderStructureForCourse(int $courseId): void
    {
        $this->deleteBuilderContentForCourse($courseId);
        $this->deleteBuilderExamsForCourse($courseId);
    }

    public function countExamAttemptsForCourse(int $courseId): int
    {
        if (!$this->tableExists('exam_attempts') || !$this->tableExists('exam_sessions')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
               FROM exam_attempts ea
               JOIN exam_sessions es ON es.id = ea.exam_id
              WHERE es.course_id = :cid'
        );
        $stmt->execute([':cid' => $courseId]);
        return (int) ($stmt->fetchColumn() ?: 0);
    }

    public function setUpdateInProgress(int $courseId): void
    {
        if (!$this->safeColumnExists('courses', 'update_in_progress')) {
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE courses SET update_in_progress = 1, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $courseId]);
    }

    public function insertAuditLog(int $userId, string $action, string $targetType, int $targetId): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, created_at) VALUES (:uid, :action, :type, :tid, NOW())');
        $params = [
            ':uid' => $userId,
            ':action' => $action,
            ':type' => $targetType,
            ':tid' => $targetId,
        ];
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if (!$this->isDuplicateZeroPrimary($e)) {
                throw $e;
            }
            $manualId = $this->nextTableId('audit_logs');
            if ($manualId < 1) {
                throw $e;
            }
            $stmtWithId = $this->pdo->prepare(
                'INSERT INTO audit_logs (id, user_id, action, target_type, target_id, created_at)
                 VALUES (:id, :uid, :action, :type, :tid, NOW())'
            );
            $params[':id'] = $manualId;
            $stmtWithId->execute($params);
        }
    }

    public function insertBuilderChapter(int $courseId, string $title, ?string $description, int $position): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO course_chapters (course_id, title, description, position, created_at)
            VALUES (:course_id, :title, :description, :position, NOW())');
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => $title,
            ':description' => $description,
            ':position' => $position,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function insertBuilderLesson(
        int $chapterId,
        string $type,
        string $title,
        ?string $description,
        ?string $youtubeUrl,
        ?string $documentPath,
        int $durationMin,
        int $position
    ): int {
        $lessonType = $type === 'document' ? 'document' : 'video';
        $documentMime = $lessonType === 'document' && $documentPath !== null && $documentPath !== ''
            ? (preg_match('~^https?://~i', $documentPath) ? 'text/uri-list' : 'text/plain')
            : null;

        $stmt = $this->pdo->prepare('INSERT INTO course_lessons
            (chapter_id, lesson_type, youtube_url, document_path, document_mime, document_size, title, description, position, duration_estimate_min, created_at)
            VALUES
            (:chapter_id, :lesson_type, :youtube_url, :document_path, :document_mime, :document_size, :title, :description, :position, :duration_min, NOW())');
        $stmt->execute([
            ':chapter_id' => $chapterId,
            ':lesson_type' => $lessonType,
            ':youtube_url' => $lessonType === 'video' ? $youtubeUrl : null,
            ':document_path' => $lessonType === 'document' ? $documentPath : null,
            ':document_mime' => $documentMime,
            ':document_size' => null,
            ':title' => $title,
            ':description' => $description,
            ':position' => $position,
            ':duration_min' => $durationMin > 0 ? $durationMin : null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function insertBuilderMeeting(
        int $courseId,
        int $chapterId,
        string $title,
        string $mode,
        int $durationMin,
        ?string $meetingLink = null,
        ?string $meetingPlace = null
    ): int {
        $columns = ['course_id', 'title', 'mode', 'start_at', 'end_at', 'meeting_link', 'meeting_place', 'created_at'];
        $values = [':course_id', ':title', ':mode', ':start_at', ':end_at', ':meeting_link', ':meeting_place', 'NOW()'];
        $params = [
            ':course_id' => $courseId,
            ':title' => $title,
            ':mode' => $mode === 'onsite' ? 'onsite' : 'online',
            ':start_at' => null,
            ':end_at' => null,
            ':meeting_link' => $mode === 'onsite' ? null : $meetingLink,
            ':meeting_place' => $mode === 'onsite' ? $meetingPlace : null,
        ];

        if ($this->safeColumnExists('course_meetings', 'chapter_id')) {
            $columns[] = 'chapter_id';
            $values[] = ':chapter_id';
            $params[':chapter_id'] = $chapterId > 0 ? $chapterId : null;
        }
        if ($this->safeColumnExists('course_meetings', 'duration_min')) {
            $columns[] = 'duration_min';
            $values[] = ':duration_min';
            $params[':duration_min'] = $durationMin > 0 ? $durationMin : null;
        }
        if ($this->safeColumnExists('course_meetings', 'status')) {
            $columns[] = 'status';
            $values[] = ':status';
            $params[':status'] = 'pending';
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO course_meetings (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $values) . ')'
        );
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function insertBuilderExam(int $courseId, array $payload): int
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
            ':course_id',
            ':title',
            ':description',
            ':type',
            ':duration_min',
            ':max_attempts',
            ':min_score',
            ':meeting_mode',
            ':meeting_link',
            ':meeting_place',
            ':drive_expected',
        ];
        $params = [
            ':course_id' => $courseId,
            ':title' => $payload['title'] ?? null,
            ':description' => $payload['description'] ?? null,
            ':type' => $payload['type'] ?? 'qcm',
            ':duration_min' => $payload['duration_min'] ?? null,
            ':max_attempts' => $payload['max_attempts'] ?? 1,
            ':min_score' => $payload['min_score'] ?? 0,
            ':meeting_mode' => $payload['meeting_mode'] ?? null,
            ':meeting_link' => $payload['meeting_link'] ?? null,
            ':meeting_place' => $payload['meeting_place'] ?? null,
            ':drive_expected' => $payload['drive_expected'] ?? 0,
        ];

        if ($this->safeColumnExists('exam_sessions', 'drive_subject_link')) {
            $columns[] = 'drive_subject_link';
            $values[] = ':drive_subject_link';
            $params[':drive_subject_link'] = $payload['drive_subject_link'] ?? null;
        }
        if ($this->safeColumnExists('exam_sessions', 'drive_subject_name')) {
            $columns[] = 'drive_subject_name';
            $values[] = ':drive_subject_name';
            $params[':drive_subject_name'] = $payload['drive_subject_name'] ?? null;
        }
        if ($this->safeColumnExists('exam_sessions', 'settings_json')) {
            $columns[] = 'settings_json';
            $values[] = ':settings_json';
            $params[':settings_json'] = $payload['settings_json'] ?? null;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO exam_sessions (' . implode(', ', $columns) . ')
             VALUES (' . implode(', ', $values) . ')'
        );
        $stmt->execute($params);

        return (int) $this->pdo->lastInsertId();
    }

    public function insertBuilderExamQuestion(
        int $examId,
        int $variant,
        string $questionText,
        string $questionType,
        int $orderIndex,
        int $points,
        bool $withVariant
    ): int {
        if ($withVariant) {
            $stmt = $this->pdo->prepare('INSERT INTO exam_questions
                (exam_id, variant, question_text, question_type, order_index, points)
                VALUES (:exam_id, :variant, :question_text, :question_type, :order_index, :points)');
            $stmt->execute([
                ':exam_id' => $examId,
                ':variant' => $variant,
                ':question_text' => $questionText,
                ':question_type' => $questionType,
                ':order_index' => $orderIndex,
                ':points' => $points,
            ]);
        } else {
            $stmt = $this->pdo->prepare('INSERT INTO exam_questions
                (exam_id, question_text, question_type, order_index, points)
                VALUES (:exam_id, :question_text, :question_type, :order_index, :points)');
            $stmt->execute([
                ':exam_id' => $examId,
                ':question_text' => $questionText,
                ':question_type' => $questionType,
                ':order_index' => $orderIndex,
                ':points' => $points,
            ]);
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function insertBuilderExamOption(int $questionId, string $optionText, bool $isCorrect): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO exam_options (question_id, option_text, is_correct)
            VALUES (:question_id, :option_text, :is_correct)');
        $stmt->execute([
            ':question_id' => $questionId,
            ':option_text' => $optionText,
            ':is_correct' => $isCorrect ? 1 : 0,
        ]);
    }

    private function safeColumnExists(string $table, string $column): bool
    {
        try {
            return $this->columnExists($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function findCourseIdBySlug(string $slug): int
    {
        $slug = trim($slug);
        if ($slug === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare('SELECT id FROM courses WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);

        return (int) ($stmt->fetchColumn() ?: 0);
    }

    private function isDuplicateZeroPrimary(\PDOException $e): bool
    {
        $msg = $e->getMessage();
        if ($msg === '') {
            return false;
        }
        return strpos($msg, "Duplicate entry '0'") !== false && stripos($msg, 'PRIMARY') !== false;
    }

    private function nextTableId(string $table): int
    {
        $table = trim($table);
        if ($table === '') {
            return 0;
        }
        $sql = $this->pdo->inTransaction()
            ? 'SELECT id FROM ' . $table . ' ORDER BY id DESC LIMIT 1 FOR UPDATE'
            : 'SELECT id FROM ' . $table . ' ORDER BY id DESC LIMIT 1';
        $stmt = $this->pdo->query($sql);
        $last = (int) ($stmt->fetchColumn() ?: 0);
        return $last + 1;
    }
}
