<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class CompanyInviteAcceptModel extends Model
{
    public function findInviteByTokenHash(string $tokenHash): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT ci.*, c.name AS company_name, c.status AS company_status
                 FROM company_invites ci
                 JOIN companies c ON c.id = ci.company_id
                 WHERE ci.token_hash = :hash
                 LIMIT 1'
            );
            $stmt->execute([':hash' => $tokenHash]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function markInviteStatus(int $inviteId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE company_invites
             SET status = :status, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':status' => $status,
            ':id' => $inviteId,
        ]);
    }

    public function markInviteAccepted(int $inviteId): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE company_invites
                 SET status = 'accepted',
                     approval_status = CASE WHEN approval_status = 'pending' THEN 'approved' ELSE approval_status END,
                     updated_at = NOW()
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $inviteId]);
            return;
        } catch (\Throwable $e) {
            // Fallback for old schema.
        }

        $this->markInviteStatus($inviteId, 'accepted');
    }

    public function markInviteAwaitingApproval(int $inviteId, int $requestedUserId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE company_invites
             SET status = 'awaiting_approval',
                 approval_status = 'pending',
                 approval_required = 1,
                 requested_user_id = :requested_uid,
                 approved_by = NULL,
                 approved_at = NULL,
                 updated_at = NOW()
             WHERE id = :id
               AND status = 'pending'"
        );
        $stmt->execute([
            ':requested_uid' => $requestedUserId,
            ':id' => $inviteId,
        ]);
    }

    public function attachActiveMember(int $companyId, int $userId, string $role = 'member'): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT IGNORE INTO company_members (company_id, user_id, role, status, created_at, updated_at)
             VALUES (:cid, :uid, :role, :status, NOW(), NOW())'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
            ':role' => $role,
            ':status' => 'active',
        ]);
    }

    public function memberExistsInCompany(int $companyId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM company_members
             WHERE company_id = :cid
               AND user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createActiveVerifiedUser(
        string $firstName,
        string $lastName,
        string $email,
        string $passwordHash,
        string $rolesJson
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (first_name, last_name, email, password_hash, status, email_verified_at, roles_json)
             VALUES (:first, :last, :email, :password, :status, NOW(), :roles)'
        );
        $stmt->execute([
            ':first' => $firstName,
            ':last' => $lastName,
            ':email' => $email,
            ':password' => $passwordHash,
            ':status' => 'active',
            ':roles' => $rolesJson,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateUserRoles(int $userId, string $rolesJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':roles' => $rolesJson,
            ':id' => $userId,
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findActiveAdminUsersByCompanyId(int $companyId): array
    {
        if ($companyId < 1) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
             FROM company_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.company_id = :cid
               AND cm.status = 'active'
               AND cm.role IN ('owner','admin')
             ORDER BY u.first_name ASC, u.last_name ASC, u.email ASC"
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url = null): void
    {
        if ($userId < 1) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, body, url, created_at)
             VALUES (:uid, :title, :body, :url, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }
}
