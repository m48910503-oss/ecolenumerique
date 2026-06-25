<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WebProfileModel extends Model
{
    public function ensureExtendedProfileSchema(): void
    {
        $this->ensureUsersNotificationPreferencesColumn();
        $this->ensureStudentProfilesTable();
        $this->ensureTeacherProfilesTable();
    }

    public function emailExistsForOtherUser(string $email, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $stmt->execute([
            ':email' => $email,
            ':id' => $userId,
        ]);
        return (bool) $stmt->fetch();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function findUserById(int $userId): ?array
    {
        $this->ensureExtendedProfileSchema();
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findStudentProfileByUserId(int $userId): array
    {
        $this->ensureStudentProfilesTable();
        $defaults = [
            'certificate_display_name' => '',
        ];

        if (!$this->tableExists('student_profiles')) {
            return $defaults;
        }

        $stmt = $this->pdo->prepare(
            'SELECT certificate_display_name
             FROM student_profiles
             WHERE user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return $defaults;
        }

        return array_merge($defaults, $row);
    }

    public function findTeacherProfileByUserId(int $userId): array
    {
        $this->ensureTeacherProfilesTable();
        $defaults = [
            'professional_title' => '',
            'short_bio' => '',
            'portfolio_url' => '',
            'linkedin_url' => '',
            'cv_path' => '',
        ];

        $profile = $defaults;
        if ($this->tableExists('teacher_profiles')) {
            $stmt = $this->pdo->prepare(
                'SELECT professional_title, short_bio, portfolio_url, linkedin_url, cv_path
                 FROM teacher_profiles
                 WHERE user_id = :uid
                 LIMIT 1'
            );
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch();
            if ($row) {
                $profile = array_merge($profile, $row);
            }
        }

        if (
            $profile['professional_title'] === ''
            && $profile['short_bio'] === ''
            && $profile['cv_path'] === ''
            && $this->tableExists('teacher_applications')
        ) {
            $stmt = $this->pdo->prepare(
                'SELECT title, bio, cv_path
                 FROM teacher_applications
                 WHERE user_id = :uid
                 ORDER BY id DESC
                 LIMIT 1'
            );
            $stmt->execute([':uid' => $userId]);
            $application = $stmt->fetch();
            if ($application) {
                $profile['professional_title'] = trim((string) ($application['title'] ?? ''));
                $profile['short_bio'] = trim((string) ($application['bio'] ?? ''));
                $profile['cv_path'] = trim((string) ($application['cv_path'] ?? ''));
            }
        }

        return $profile;
    }

    public function updateProfile(int $userId, array $payload, bool $hasSignatureColumn): void
    {
        $this->ensureExtendedProfileSchema();

        $setParts = 'first_name = :first, last_name = :last, email = :email, phone = :phone, country = :country, avatar_url = :avatar';
        if ($hasSignatureColumn) {
            $setParts .= ', signature_path = :signature';
        }
        if ($this->columnExists('users', 'notification_preferences_json')) {
            $setParts .= ', notification_preferences_json = :notification_preferences_json';
        }
        $setParts .= ', status = :status, email_verified_at = :verified_at, updated_at = NOW()';

        $stmt = $this->pdo->prepare('UPDATE users SET ' . $setParts . ' WHERE id = :id');
        $params = [
            ':first' => (string) ($payload['first_name'] ?? ''),
            ':last' => (string) ($payload['last_name'] ?? ''),
            ':email' => (string) ($payload['email'] ?? ''),
            ':phone' => $payload['phone'] ?? null,
            ':country' => $payload['country'] ?? null,
            ':avatar' => $payload['avatar_url'] ?? null,
            ':status' => (string) ($payload['status'] ?? 'active'),
            ':verified_at' => $payload['email_verified_at'] ?? null,
            ':id' => $userId,
        ];
        if ($hasSignatureColumn) {
            $params[':signature'] = $payload['signature_path'] ?? null;
        }
        if ($this->columnExists('users', 'notification_preferences_json')) {
            $prefsJson = $payload['notification_preferences_json'] ?? null;
            if (is_array($prefsJson)) {
                $prefsJson = json_encode($prefsJson);
            }
            $params[':notification_preferences_json'] = $prefsJson;
        }
        $stmt->execute($params);
    }

    public function upsertStudentProfile(int $userId, ?string $certificateDisplayName): void
    {
        $this->ensureStudentProfilesTable();
        if (!$this->tableExists('student_profiles')) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO student_profiles (user_id, certificate_display_name, created_at, updated_at)
             VALUES (:uid, :certificate_display_name, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                certificate_display_name = VALUES(certificate_display_name),
                updated_at = NOW()'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':certificate_display_name' => $certificateDisplayName !== null && $certificateDisplayName !== '' ? $certificateDisplayName : null,
        ]);
    }

    public function upsertTeacherProfile(int $userId, array $payload): void
    {
        $this->ensureTeacherProfilesTable();
        if (!$this->tableExists('teacher_profiles')) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO teacher_profiles (
                user_id, professional_title, short_bio, portfolio_url, linkedin_url, cv_path, created_at, updated_at
             ) VALUES (
                :uid, :professional_title, :short_bio, :portfolio_url, :linkedin_url, :cv_path, NOW(), NOW()
             )
             ON DUPLICATE KEY UPDATE
                professional_title = VALUES(professional_title),
                short_bio = VALUES(short_bio),
                portfolio_url = VALUES(portfolio_url),
                linkedin_url = VALUES(linkedin_url),
                cv_path = COALESCE(VALUES(cv_path), teacher_profiles.cv_path),
                updated_at = NOW()'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':professional_title' => $this->nullableText($payload['professional_title'] ?? null),
            ':short_bio' => $this->nullableText($payload['short_bio'] ?? null),
            ':portfolio_url' => $this->nullableText($payload['portfolio_url'] ?? null),
            ':linkedin_url' => $this->nullableText($payload['linkedin_url'] ?? null),
            ':cv_path' => $this->nullableText($payload['cv_path'] ?? null),
        ]);
    }

    public function invalidateEmailVerifications(int $userId): void
    {
        $stmt = $this->pdo->prepare('UPDATE email_verifications SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL');
        $stmt->execute([':uid' => $userId]);
    }

    public function insertEmailVerification(int $userId, string $tokenHash, string $expiresAt): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $tokenHash,
            ':expires_at' => $expiresAt,
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET password_hash = :pwd, remember_selector = NULL, remember_validator_hash = NULL, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':pwd' => $passwordHash,
            ':id' => $userId,
        ]);
    }

    public function countOwnedCompanies(int $userId): int
    {
        if (!$this->tableExists('companies') || !$this->columnExists('companies', 'admin_user_id')) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM companies WHERE admin_user_id = :uid';
        if ($this->columnExists('companies', 'status')) {
            $sql .= ' AND status = :status';
        }

        $stmt = $this->pdo->prepare($sql);
        $params = [':uid' => $userId];
        if ($this->columnExists('companies', 'status')) {
            $params[':status'] = 'active';
        }
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function countCompanyAdminMemberships(int $userId): int
    {
        if (
            !$this->tableExists('company_members')
            || !$this->columnExists('company_members', 'user_id')
            || !$this->columnExists('company_members', 'role')
        ) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) FROM company_members WHERE user_id = :uid AND role IN (\'owner\', \'admin\')';
        if ($this->columnExists('company_members', 'status')) {
            $sql .= ' AND status = :status';
        }

        $stmt = $this->pdo->prepare($sql);
        $params = [':uid' => $userId];
        if ($this->columnExists('company_members', 'status')) {
            $params[':status'] = 'active';
        }
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int,string>
     */
    public function collectAccountFilePaths(int $userId): array
    {
        $paths = [];
        $user = $this->findUserById($userId);
        if (is_array($user)) {
            foreach (['avatar_url', 'signature_path'] as $column) {
                $value = trim((string) ($user[$column] ?? ''));
                if ($value !== '') {
                    $paths[] = $value;
                }
            }
        }

        if ($this->tableExists('teacher_profiles') && $this->columnExists('teacher_profiles', 'cv_path')) {
            $stmt = $this->pdo->prepare('SELECT cv_path FROM teacher_profiles WHERE user_id = :uid AND cv_path IS NOT NULL');
            $stmt->execute([':uid' => $userId]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $value = trim((string) ($row['cv_path'] ?? ''));
                if ($value !== '') {
                    $paths[] = $value;
                }
            }
        }

        if ($this->tableExists('teacher_applications') && $this->columnExists('teacher_applications', 'cv_path')) {
            $stmt = $this->pdo->prepare('SELECT cv_path FROM teacher_applications WHERE user_id = :uid AND cv_path IS NOT NULL');
            $stmt->execute([':uid' => $userId]);
            foreach ($stmt->fetchAll() ?: [] as $row) {
                $value = trim((string) ($row['cv_path'] ?? ''));
                if ($value !== '') {
                    $paths[] = $value;
                }
            }
        }

        $paths = array_values(array_unique(array_filter(
            $paths,
            static fn (mixed $path): bool => is_string($path) && trim($path) !== ''
        )));

        return $paths;
    }

    public function purgeAccountAncillaryData(int $userId): void
    {
        $this->deleteRowsByColumn('email_verifications', 'user_id', $userId);
        $this->deleteRowsByColumn('password_resets', 'user_id', $userId);
        $this->deleteRowsByColumn('notifications', 'user_id', $userId);
        $this->deleteRowsByColumn('push_subscriptions', 'user_id', $userId);
        $this->deleteRowsByColumn('push_notification_queue', 'user_id', $userId);
        $this->deleteRowsByColumn('download_tokens', 'user_id', $userId);
        $this->deleteRowsByColumn('student_profiles', 'user_id', $userId);
        $this->deleteRowsByColumn('teacher_profiles', 'user_id', $userId);
        $this->deleteRowsByColumn('teacher_applications', 'user_id', $userId);
        $this->deleteRowsByColumn('lesson_bookmarks', 'user_id', $userId);
        $this->deleteRowsByColumn('course_favorites', 'user_id', $userId);
        $this->deleteRowsByColumn('exam_attempts', 'user_id', $userId);
        $this->deleteRowsByColumn('certificates', 'user_id', $userId);
        $this->deleteRowsByColumn('course_ratings', 'user_id', $userId);
        $this->deleteRowsByColumn('company_course_seat_assignments', 'member_user_id', $userId);
        $this->deleteRowsByColumn('company_members', 'user_id', $userId);
        $this->deleteRowsByColumn('support_conversation_reads', 'user_id', $userId);
        $this->deleteRowsByColumn('support_messages', 'sender_id', $userId);
        $this->deleteRowsByColumn('support_conversations', 'created_by', $userId);
    }

    public function anonymizeAccount(int $userId, string $replacementEmail): void
    {
        $setParts = [];
        $params = [
            ':id' => $userId,
            ':email' => $replacementEmail,
            ':first_name' => 'Compte',
            ':last_name' => 'supprime',
            ':status' => 'suspended',
            ':password_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            ':auth_provider' => 'local',
            ':updated_at' => date('Y-m-d H:i:s'),
            ':mfa_enabled' => 0,
        ];

        $columnMap = [
            'first_name' => ':first_name',
            'last_name' => ':last_name',
            'email' => ':email',
            'status' => ':status',
            'password_hash' => ':password_hash',
            'auth_provider' => ':auth_provider',
            'mfa_enabled' => ':mfa_enabled',
        ];
        foreach ($columnMap as $column => $param) {
            if ($this->columnExists('users', $column)) {
                $setParts[] = $column . ' = ' . $param;
            }
        }

        foreach ([
            'email_verified_at',
            'phone',
            'country',
            'avatar_url',
            'signature_path',
            'remember_selector',
            'remember_validator_hash',
            'notification_preferences_json',
            'mfa_enabled_at',
        ] as $nullableColumn) {
            if ($this->columnExists('users', $nullableColumn)) {
                $setParts[] = $nullableColumn . ' = NULL';
            }
        }

        if ($this->columnExists('users', 'updated_at')) {
            $setParts[] = 'updated_at = :updated_at';
        }

        if ($setParts === []) {
            return;
        }

        $stmt = $this->pdo->prepare('UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    private function ensureUsersNotificationPreferencesColumn(): void
    {
        $this->columnExists('users', 'notification_preferences_json');
    }

    private function ensureStudentProfilesTable(): void
    {
        $this->tableExists('student_profiles');
    }

    private function ensureTeacherProfilesTable(): void
    {
        $this->tableExists('teacher_profiles');
    }

    private function deleteRowsByColumn(string $table, string $column, int $userId): void
    {
        if (!$this->tableExists($table) || !$this->columnExists($table, $column)) {
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM ' . $table . ' WHERE ' . $column . ' = :uid');
        $stmt->execute([':uid' => $userId]);
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) $value);
        return $text !== '' ? $text : null;
    }
}
