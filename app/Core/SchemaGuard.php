<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Centralizes legacy schema guard helpers so services/controllers do not call
 * global ensure_* helpers directly.
 */
final class SchemaGuard
{
    public function ensureCourseMeetingsDurationColumn(): bool
    {
        return function_exists('ensure_course_meetings_duration_column')
            ? (bool) \ensure_course_meetings_duration_column()
            : false;
    }

    public function ensureCoursesUpdateFlagColumn(): bool
    {
        return function_exists('ensure_courses_update_flag_column')
            ? (bool) \ensure_courses_update_flag_column()
            : false;
    }

    public function courseMeetingsHasDuration(): bool
    {
        return function_exists('course_meetings_has_duration')
            ? (bool) \course_meetings_has_duration()
            : false;
    }

    public function examAttemptsHasMeetingId(): bool
    {
        return function_exists('exam_attempts_has_meeting_id')
            ? (bool) \exam_attempts_has_meeting_id()
            : false;
    }

    public function ensureExamAttemptsMeetingIdColumn(): bool
    {
        return function_exists('ensure_exam_attempts_meeting_id_column')
            ? (bool) \ensure_exam_attempts_meeting_id_column()
            : false;
    }

    public function examQuestionsHasVariant(): bool
    {
        return function_exists('exam_questions_has_variant')
            ? (bool) \exam_questions_has_variant()
            : false;
    }

    public function ensureExamQuestionsVariantColumn(): bool
    {
        return function_exists('ensure_exam_questions_variant_column')
            ? (bool) \ensure_exam_questions_variant_column()
            : false;
    }

    public function ensureExamAttemptsVariantIndexColumn(): bool
    {
        return function_exists('ensure_exam_attempts_variant_index_column')
            ? (bool) \ensure_exam_attempts_variant_index_column()
            : false;
    }

    public function ensureExamFilesDriveMetaColumns(): bool
    {
        return function_exists('ensure_exam_files_drive_meta_columns')
            ? (bool) \ensure_exam_files_drive_meta_columns()
            : false;
    }

    public function ensureExamSessionsDriveSubjectColumns(): bool
    {
        return function_exists('ensure_exam_sessions_drive_subject_columns')
            ? (bool) \ensure_exam_sessions_drive_subject_columns()
            : false;
    }

    public function ensureExamSessionsSettingsJsonColumn(): bool
    {
        return function_exists('ensure_exam_sessions_settings_json_column')
            ? (bool) \ensure_exam_sessions_settings_json_column()
            : false;
    }
}
