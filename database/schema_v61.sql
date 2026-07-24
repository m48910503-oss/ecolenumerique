-- Schema v61 : reparation des ids auto-increment du module cours/examens
-- sur certaines bases legacy importees avec des colonnes id degradees.
--
-- Cette migration :
-- 1. remappe les enregistrements historiques en id=0 qui bloquent les ajouts,
-- 2. remet AUTO_INCREMENT sur les tables d'edition de cours/examens,
-- 3. recree la cle primaire id lorsqu'elle a disparu sur quelques tables.

-- Repare un cours legacy en id=0. Aucun lien n'a ete retrouve sur cette base.
SET @course_zero_new_id := (SELECT COALESCE(MAX(id), 0) + 1 FROM courses);
UPDATE courses
SET id = @course_zero_new_id
WHERE id = 0;

-- Repare un chapitre legacy en id=0 puis ses references de contenu.
SET @chapter_zero_new_id := (SELECT COALESCE(MAX(id), 0) + 1 FROM course_chapters);
UPDATE course_chapters
SET id = @chapter_zero_new_id
WHERE id = 0;

UPDATE course_lessons
SET chapter_id = @chapter_zero_new_id
WHERE chapter_id = 0;

UPDATE chapter_documents
SET chapter_id = @chapter_zero_new_id
WHERE chapter_id = 0;

UPDATE course_meetings
SET chapter_id = @chapter_zero_new_id
WHERE chapter_id = 0;

-- Repare une lecon legacy en id=0 puis les pointeurs annexes.
SET @lesson_zero_new_id := (SELECT COALESCE(MAX(id), 0) + 1 FROM course_lessons);
UPDATE course_lessons
SET id = @lesson_zero_new_id
WHERE id = 0;

UPDATE lesson_bookmarks
SET lesson_id = @lesson_zero_new_id
WHERE lesson_id = 0;

UPDATE lesson_completions
SET lesson_id = @lesson_zero_new_id
WHERE lesson_id = 0;

UPDATE lesson_positions
SET lesson_id = @lesson_zero_new_id
WHERE lesson_id = 0;

UPDATE chapter_documents
SET mime = REPLACE(mime, '|lesson:0', CONCAT('|lesson:', @lesson_zero_new_id))
WHERE mime LIKE '%|lesson:0%';

-- Repare un examen legacy en id=0 puis ses references.
SET @exam_zero_new_id := (SELECT COALESCE(MAX(id), 0) + 1 FROM exam_sessions);
UPDATE exam_sessions
SET id = @exam_zero_new_id
WHERE id = 0;

UPDATE exam_attempts
SET exam_id = @exam_zero_new_id
WHERE exam_id = 0;

UPDATE exam_questions
SET exam_id = @exam_zero_new_id
WHERE exam_id = 0;

UPDATE exam_meetings
SET exam_id = @exam_zero_new_id
WHERE exam_id = 0;

UPDATE certificates
SET exam_id = @exam_zero_new_id
WHERE exam_id = 0;

ALTER TABLE courses
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE course_chapters
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE course_lessons
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE chapter_documents
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE course_meetings
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_exam_sessions_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'exam_sessions'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_exam_sessions_pk = 0, 'ALTER TABLE exam_sessions ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE exam_sessions
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_exam_questions_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'exam_questions'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_exam_questions_pk = 0, 'ALTER TABLE exam_questions ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE exam_questions
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
SET @has_exam_questions_variant_idx := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'exam_questions'
      AND INDEX_NAME = 'idx_exam_questions_variant'
);
SET @sql := IF(
    @has_exam_questions_variant_idx = 0,
    'CREATE INDEX idx_exam_questions_variant ON exam_questions (exam_id, variant, order_index)',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_exam_options_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'exam_options'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_exam_options_pk = 0, 'ALTER TABLE exam_options ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE exam_options
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE exam_attempts
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE exam_answers
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE exam_files
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE exam_meetings
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE certificates
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_lesson_bookmarks_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'lesson_bookmarks'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_lesson_bookmarks_pk = 0, 'ALTER TABLE lesson_bookmarks ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE lesson_bookmarks
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

SET @has_lesson_completions_pk := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'lesson_completions'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(@has_lesson_completions_pk = 0, 'ALTER TABLE lesson_completions ADD PRIMARY KEY (id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
ALTER TABLE lesson_completions
    MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;
