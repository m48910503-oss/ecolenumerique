<?php
declare(strict_types=1);

namespace App\Services\Admin;

final class TeacherRoleBackfillService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return array{
     *   summary:array<string,int>,
     *   changes:array<int,array<string,mixed>>,
     *   warnings:array<int,array<string,mixed>>
     * }
     */
    public function run(bool $apply = false): array
    {
        $users = $this->fetchScopedUsers();
        $applicationMap = $this->fetchApplicationSummaryMap();
        $changes = [];
        $warnings = [];
        $summary = [
            'scanned_user_count' => count($users),
            'changed_user_count' => 0,
            'warning_count' => 0,
            'grant_teacher_count' => 0,
            'restore_teacher_candidate_count' => 0,
            'remove_teacher_candidate_count' => 0,
            'normalize_roles_count' => 0,
            'legacy_teacher_warning_count' => 0,
            'pending_teacher_conflict_count' => 0,
            'orphan_candidate_warning_count' => 0,
            'mixed_history_warning_count' => 0,
        ];

        $ownsTransaction = false;
        if ($apply && !$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $ownsTransaction = true;
        }

        try {
            foreach ($users as $user) {
                $userId = (int) ($user['id'] ?? 0);
                $email = trim((string) ($user['email'] ?? ''));
                $application = $applicationMap[$userId] ?? [
                    'application_count' => 0,
                    'has_approved' => false,
                    'has_pending' => false,
                    'has_rejected' => false,
                ];

                $decodedRoles = json_decode((string) ($user['roles_json'] ?? '[]'), true);
                $beforeRoles = is_array($decodedRoles) ? $this->filterRoles($decodedRoles) : [];
                $roles = $this->normalizeRoles($beforeRoles);
                $afterRoles = $roles;
                $actions = [];
                $userWarnings = [];

                if (count($beforeRoles) !== count($roles)) {
                    $actions[] = 'normalize_roles';
                    $summary['normalize_roles_count']++;
                }

                $hasTeacher = in_array('teacher', $afterRoles, true);
                $hasCandidate = in_array('teacher_candidate', $afterRoles, true);
                $hasApproved = !empty($application['has_approved']);
                $hasPending = !empty($application['has_pending']);
                $hasRejected = !empty($application['has_rejected']);

                if ($hasApproved) {
                    if (!$hasTeacher) {
                        $afterRoles[] = 'teacher';
                        $actions[] = 'grant_teacher_from_approved_application';
                        $summary['grant_teacher_count']++;
                        $hasTeacher = true;
                    }
                    if ($hasCandidate) {
                        $afterRoles = $this->removeRole($afterRoles, 'teacher_candidate');
                        $actions[] = 'remove_teacher_candidate_after_approval';
                        $summary['remove_teacher_candidate_count']++;
                    }
                } elseif ($hasPending) {
                    if ($hasTeacher) {
                        $userWarnings[] = 'teacher_role_with_pending_application_without_approval';
                        $summary['pending_teacher_conflict_count']++;
                    } elseif (!$hasCandidate) {
                        $afterRoles[] = 'teacher_candidate';
                        $actions[] = 'restore_teacher_candidate_from_pending_application';
                        $summary['restore_teacher_candidate_count']++;
                    }
                } elseif ($hasRejected && $hasCandidate) {
                    $afterRoles = $this->removeRole($afterRoles, 'teacher_candidate');
                    $actions[] = 'remove_teacher_candidate_after_rejection';
                    $summary['remove_teacher_candidate_count']++;
                }

                if (!$hasApproved && !$hasPending && !$hasRejected && $hasTeacher) {
                    $userWarnings[] = 'legacy_teacher_without_application';
                    $summary['legacy_teacher_warning_count']++;
                }
                if (!$hasApproved && !$hasPending && !$hasRejected && $hasCandidate) {
                    $userWarnings[] = 'teacher_candidate_without_application';
                    $summary['orphan_candidate_warning_count']++;
                }
                if (($hasApproved && ($hasPending || $hasRejected)) || (!$hasApproved && $hasPending && $hasRejected)) {
                    $userWarnings[] = 'mixed_application_history_detected';
                    $summary['mixed_history_warning_count']++;
                }

                $afterRoles = $this->normalizeRoles($afterRoles);
                if ($afterRoles !== $beforeRoles) {
                    if ($apply) {
                        $this->updateUserRoles($userId, $afterRoles);
                    }
                    $changes[] = [
                        'user_id' => $userId,
                        'email' => $email,
                        'before_roles' => $beforeRoles,
                        'after_roles' => $afterRoles,
                        'actions' => $actions,
                        'application_count' => (int) ($application['application_count'] ?? 0),
                    ];
                    $summary['changed_user_count']++;
                }

                if ($userWarnings !== []) {
                    $warnings[] = [
                        'user_id' => $userId,
                        'email' => $email,
                        'roles' => $beforeRoles,
                        'warnings' => $userWarnings,
                        'application_count' => (int) ($application['application_count'] ?? 0),
                    ];
                    $summary['warning_count'] += count($userWarnings);
                }
            }

            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        return [
            'summary' => $summary,
            'changes' => $changes,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array<int,array{id:int,email:string,roles_json:string}>
     */
    private function fetchScopedUsers(): array
    {
        $usersById = [];

        $stmt = $this->pdo->prepare(
            'SELECT id, email, roles_json
             FROM users
             WHERE roles_json LIKE :teacher_pattern
                OR roles_json LIKE :candidate_pattern
             ORDER BY id ASC'
        );
        $stmt->execute([
            ':teacher_pattern' => '%"teacher"%',
            ':candidate_pattern' => '%"teacher_candidate"%',
        ]);

        foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
            $userId = (int) ($row['id'] ?? 0);
            if ($userId > 0) {
                $usersById[$userId] = [
                    'id' => $userId,
                    'email' => (string) ($row['email'] ?? ''),
                    'roles_json' => (string) ($row['roles_json'] ?? '[]'),
                ];
            }
        }

        if ($this->tableExists('teacher_applications')) {
            $idsStmt = $this->pdo->query('SELECT DISTINCT user_id FROM teacher_applications WHERE user_id IS NOT NULL');
            $missingIds = [];
            foreach (($idsStmt ? $idsStmt->fetchAll(\PDO::FETCH_ASSOC) : []) as $row) {
                $userId = (int) ($row['user_id'] ?? 0);
                if ($userId > 0 && !isset($usersById[$userId])) {
                    $missingIds[] = $userId;
                }
            }

            foreach ($this->fetchUsersByIds($missingIds) as $row) {
                $userId = (int) ($row['id'] ?? 0);
                if ($userId > 0) {
                    $usersById[$userId] = $row;
                }
            }
        }

        ksort($usersById);
        return array_values($usersById);
    }

    /**
     * @param array<int,int> $ids
     * @return array<int,array{id:int,email:string,roles_json:string}>
     */
    private function fetchUsersByIds(array $ids): array
    {
        $ids = array_values(array_filter($ids, static fn (int $value): bool => $value > 0));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            'SELECT id, email, roles_json
             FROM users
             WHERE id IN (' . $placeholders . ')
             ORDER BY id ASC'
        );
        foreach ($ids as $index => $userId) {
            $stmt->bindValue($index + 1, $userId, \PDO::PARAM_INT);
        }
        $stmt->execute();

        $rows = [];
        foreach (($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) as $row) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'email' => (string) ($row['email'] ?? ''),
                'roles_json' => (string) ($row['roles_json'] ?? '[]'),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int,array{application_count:int,has_approved:bool,has_pending:bool,has_rejected:bool}>
     */
    private function fetchApplicationSummaryMap(): array
    {
        if (!$this->tableExists('teacher_applications')) {
            return [];
        }

        $stmt = $this->pdo->query(
            "SELECT user_id,
                    COUNT(*) AS application_count,
                    MAX(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS has_approved,
                    MAX(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS has_pending,
                    MAX(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS has_rejected
             FROM teacher_applications
             GROUP BY user_id"
        );

        $map = [];
        foreach (($stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : []) as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId < 1) {
                continue;
            }
            $map[$userId] = [
                'application_count' => (int) ($row['application_count'] ?? 0),
                'has_approved' => (int) ($row['has_approved'] ?? 0) === 1,
                'has_pending' => (int) ($row['has_pending'] ?? 0) === 1,
                'has_rejected' => (int) ($row['has_rejected'] ?? 0) === 1,
            ];
        }

        return $map;
    }

    /**
     * @param array<int,mixed> $roles
     * @return array<int,string>
     */
    private function normalizeRoles(array $roles): array
    {
        $normalized = [];
        foreach ($this->filterRoles($roles) as $value) {
            if (in_array($value, $normalized, true)) {
                continue;
            }
            $normalized[] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<int,mixed> $roles
     * @return array<int,string>
     */
    private function filterRoles(array $roles): array
    {
        $filtered = [];
        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }
            $value = trim($role);
            if ($value === '') {
                continue;
            }
            $filtered[] = $value;
        }

        return $filtered;
    }

    /**
     * @param array<int,string> $roles
     * @return array<int,string>
     */
    private function removeRole(array $roles, string $roleToRemove): array
    {
        return array_values(array_filter(
            $roles,
            static fn (string $role): bool => $role !== $roleToRemove
        ));
    }

    /**
     * @param array<int,string> $roles
     */
    private function updateUserRoles(int $userId, array $roles): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles WHERE id = :id');
        $stmt->execute([
            ':roles' => (string) json_encode($roles),
            ':id' => $userId,
        ]);
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table LIMIT 1'
            );
            $stmt->execute([':table' => $table]);
            if ((bool) $stmt->fetchColumn()) {
                return true;
            }
        } catch (\Throwable) {
        }

        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table LIMIT 1");
            $stmt->execute([':table' => $table]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            return false;
        }
    }
}
