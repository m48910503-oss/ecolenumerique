<?php
declare(strict_types=1);

namespace App\Services\Teacher;

use App\Core\Service; 

use App\Models\TeacherExamPreviewModel;

final class ExamPreviewService extends Service
{
    private TeacherExamPreviewModel $model;

    public function __construct(TeacherExamPreviewModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param list<string> $roles
     * @param array<string,mixed> $get
     * @return array{
     *   httpStatus:?int,
     *   abortMessage:?string,
     *   view:array<string,mixed>
     * }
     */
    public function build(array $user, array $roles, array $get): array
    {
        $examId = (int) ($get['exam_id'] ?? 0);
        if ($examId < 1) {
            return $this->abort(404, 'Examen introuvable.');
        }

        $isAdmin = in_array('admin', $roles, true);
        $exam = $this->model->findExamByIdWithCourse($examId);
        if (!$exam) {
            return $this->abort(404, 'Examen introuvable.');
        }
        if (!$isAdmin && (int) ($exam['teacher_id'] ?? 0) !== (int) ($user['id'] ?? 0)) {
            return $this->abort(403, "Acc\u{00e8}s refus\u{00e9}.");
        }
        $courseId = (int) ($exam['course_id'] ?? 0);
        if ($isAdmin) {
            $backUrl = $courseId > 0
                ? $this->siteUrl('admin/course_preview?id=' . $courseId)
                : $this->siteUrl('admin/courses');
        } else {
            $backUrl = $courseId > 0
                ? $this->siteUrl('teacher/course_content?id=' . $courseId)
                : $this->siteUrl('teacher/courses');
        }
        $examType = (string) ($exam['type'] ?? 'qcm');
        $typeLabels = [
            'qcm' => 'QCM',
            'drive' => 'Remise Drive',
            'meeting' => 'Rendez-vous',
        ];
        $typeLabel = $typeLabels[$examType] ?? strtoupper($examType);

        $questions = [];
        $questionsByVariant = [1 => [], 2 => [], 3 => []];
        $optionsByQuestion = [];
        $pointsByVariant = [1 => 0, 2 => 0, 3 => 0];
        $hasVariant = false;

        if ($examType === 'qcm') {
            $hasVariant = $this->schema()->examQuestionsHasVariant();
            $questions = $this->model->fetchQuestions($examId, $hasVariant);
            foreach ($questions as $q) {
                $variant = $hasVariant ? (int) ($q['variant'] ?? 1) : 1;
                if ($variant < 1 || $variant > 3) {
                    $variant = 1;
                }
                $questionsByVariant[$variant][] = $q;
                $pointsByVariant[$variant] += max(0, (int) ($q['points'] ?? 0));
            }

            $questionIds = array_values(array_map(static fn($q) => (int) ($q['id'] ?? 0), $questions));
            foreach ($this->model->fetchOptionsByQuestionIds($questionIds) as $opt) {
                $questionId = (int) ($opt['question_id'] ?? 0);
                $optionsByQuestion[$questionId][] = $opt;
            }
        }

        $meetingSlots = [];
        if ($examType === 'meeting') {
            $meetingSlots = $this->model->fetchMeetingSlots($examId);
        }

        $examQuestionTypeLabel = static function (string $type): string {
            $labels = [
                'single' => 'Choix unique',
                'multiple' => 'Choix multiple',
                'text' => 'Reponse texte',
            ];
            return $labels[$type] ?? 'Choix unique';
        };

        $variantLabels = [
            1 => $hasVariant ? 'Version A' : 'Questions',
            2 => 'Version B',
            3 => 'Version C',
        ];
        $statusLabels = [
            'available' => 'Disponible',
            'booked' => 'Reserve',
            'done' => 'Termine',
            'cancelled' => 'Annule',
        ];

        return [
            'httpStatus' => null,
            'abortMessage' => null,
            'view' => [
                'examId' => $examId,
                'exam' => $exam,
                'backUrl' => $backUrl,
                'examType' => $examType,
                'typeLabel' => $typeLabel,
                'questions' => $questions,
                'questionsByVariant' => $questionsByVariant,
                'optionsByQuestion' => $optionsByQuestion,
                'pointsByVariant' => $pointsByVariant,
                'hasVariant' => $hasVariant,
                'meetingSlots' => $meetingSlots,
                'variantLabels' => $variantLabels,
                'statusLabels' => $statusLabels,
                'examQuestionTypeLabel' => $examQuestionTypeLabel,
            ],
        ];
    }

    /**
     * @return array{httpStatus:int,abortMessage:string,view:array<string,mixed>}
     */
    private function abort(int $status, string $message): array
    {
        return [
            'httpStatus' => $status,
            'abortMessage' => $message,
            'view' => [],
        ];
    }
}

