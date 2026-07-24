<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class TeacherApplicationModel extends Model
{
    public function findLatestByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, status
             FROM teacher_applications
             WHERE user_id = :uid
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createPending(
        int $userId,
        string $title,
        string $bio,
        ?string $cvPath,
        string $teachingExperience = ''
    ): void
    {
        $teachingExperience = trim($teachingExperience);
        if ($teachingExperience !== '' && !$this->columnExists('teacher_applications', 'teaching_experience')) {
            $bio = rtrim($bio) . "\n\nExpérience pédagogique : " . $teachingExperience;
        }

        $hasTeachingExperienceColumn = $this->columnExists('teacher_applications', 'teaching_experience');

        if ($hasTeachingExperienceColumn) {
            $stmt = $this->pdo->prepare(
                'INSERT INTO teacher_applications (user_id, title, bio, teaching_experience, cv_path, status, created_at)
                 VALUES (:uid, :title, :bio, :teaching_experience, :cv, :status, NOW())'
            );
            $stmt->execute([
                ':uid' => $userId,
                ':title' => $title,
                ':bio' => $bio,
                ':teaching_experience' => $teachingExperience !== '' ? $teachingExperience : null,
                ':cv' => $cvPath,
                ':status' => 'pending',
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO teacher_applications (user_id, title, bio, cv_path, status, created_at)
             VALUES (:uid, :title, :bio, :cv, :status, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':bio' => $bio,
            ':cv' => $cvPath,
            ':status' => 'pending',
        ]);
    }
}
