<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use Throwable;

final class CompanyDashboardModel extends Model
{
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function countUnreadNotifications(int $userId): int
    {
        if ($userId < 1) {
            return 0;
        }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS unread FROM notifications WHERE user_id = :uid AND is_read = 0');
        $stmt->execute([':uid' => $userId]);
        return (int) ($stmt->fetch()['unread'] ?? 0);
    }

    public function findActiveAdminCompanyByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*
             FROM companies c
             JOIN company_members cm ON cm.company_id = c.id
             WHERE cm.user_id = :uid
               AND cm.role IN ('owner','admin')
               AND cm.status = 'active'
               AND c.status = 'active'
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActiveCompanyByUserId(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                c.*,
                cm.id AS member_id,
                cm.role AS member_role,
                cm.status AS member_status,
                cm.created_at AS member_joined_at
             FROM companies c
             JOIN company_members cm ON cm.company_id = c.id
             WHERE cm.user_id = :uid
               AND cm.status = 'active'
               AND c.status = 'active'
             ORDER BY CASE cm.role
                WHEN 'owner' THEN 0
                WHEN 'admin' THEN 1
                WHEN 'manager' THEN 2
                WHEN 'member' THEN 3
                WHEN 'viewer' THEN 4
                ELSE 5
             END ASC, c.id DESC
             LIMIT 1"
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCompanyMemberByUserId(int $companyId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, company_id, user_id, role, status
             FROM company_members
             WHERE company_id = :cid
               AND user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function memberExistsByEmail(int $companyId, string $email): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1
             FROM company_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.company_id = :cid
               AND u.email = :email
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':email' => $email,
        ]);
        return (bool) $stmt->fetchColumn();
    }

    public function findUserByEmail(string $email): ?array
    {
        $value = strtolower(trim($email));
        if ($value === '') {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, first_name, last_name, email
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCompanyById(int $companyId): ?array
    {
        if ($companyId < 1) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, name, status
             FROM companies
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $companyId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateCompanyProfile(int $companyId, array $payload): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE companies
             SET company_email = :company_email,
                 company_phone = :company_phone,
                 country = :country,
                 city = :city,
                 address_line = :address_line,
                 logo_path = :logo_path,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $stmt->execute([
            ':company_email' => $payload['company_email'] ?? null,
            ':company_phone' => $payload['company_phone'] ?? null,
            ':country' => $payload['country'] ?? null,
            ':city' => $payload['city'] ?? null,
            ':address_line' => $payload['address_line'] ?? null,
            ':logo_path' => $payload['logo_path'] ?? null,
            ':id' => $companyId,
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

    public function pendingInviteExists(int $companyId, string $email, string $inviteMode = 'email'): bool
    {
        $mode = strtolower(trim($inviteMode));
        if (!in_array($mode, ['email', 'link'], true)) {
            $mode = 'email';
        }

        if ($email === '' && $mode === 'email') {
            return false;
        }

        try {
            if ($mode === 'email') {
                $stmt = $this->pdo->prepare(
                    "SELECT 1
                     FROM company_invites
                     WHERE company_id = :cid
                       AND email = :email
                       AND invite_mode = 'email'
                       AND status IN ('pending', 'awaiting_approval')
                       AND expires_at > CURRENT_TIMESTAMP
                     LIMIT 1"
                );
                $stmt->execute([
                    ':cid' => $companyId,
                    ':email' => $email,
                ]);
            } else {
                $stmt = $this->pdo->prepare(
                    "SELECT 1
                     FROM company_invites
                     WHERE company_id = :cid
                       AND invite_mode = 'link'
                       AND status IN ('pending', 'awaiting_approval')
                       AND expires_at > CURRENT_TIMESTAMP
                       AND (
                            (:email = '' AND (email IS NULL OR email = ''))
                            OR email = :email
                       )
                     LIMIT 1"
                );
                $stmt->execute([
                    ':cid' => $companyId,
                    ':email' => $email,
                ]);
            }
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            // Fallback for old schema where invite_mode/status extensions are missing.
            if ($email === '') {
                return false;
            }
            $stmt = $this->pdo->prepare(
                "SELECT 1
                 FROM company_invites
                 WHERE company_id = :cid
                   AND email = :email
                   AND status = 'pending'
                   AND expires_at > CURRENT_TIMESTAMP
                 LIMIT 1"
            );
            $stmt->execute([
                ':cid' => $companyId,
                ':email' => $email,
            ]);
            return (bool) $stmt->fetchColumn();
        }
    }

    public function createInvite(
        int $companyId,
        ?string $email,
        string $tokenHash,
        string $expiresAt,
        int $invitedBy,
        string $inviteMode = 'email',
        bool $approvalRequired = false
    ): void {
        $mode = strtolower(trim($inviteMode));
        if (!in_array($mode, ['email', 'link'], true)) {
            $mode = 'email';
        }
        $status = $mode === 'link' ? 'pending' : 'pending';
        $approvalStatus = $approvalRequired ? 'pending' : 'none';
        $emailValue = trim((string) ($email ?? ''));
        if ($emailValue === '') {
            $emailValue = null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO company_invites (
                    company_id, email, token_hash, invite_mode, status, approval_status, approval_required, expires_at, invited_by, created_at, updated_at
                 ) VALUES (
                    :cid, :email, :token, :mode, :status, :approval_status, :approval_required, :exp, :by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                 )'
            );
            $stmt->execute([
                ':cid' => $companyId,
                ':email' => $emailValue,
                ':token' => $tokenHash,
                ':mode' => $mode,
                ':status' => $status,
                ':approval_status' => $approvalStatus,
                ':approval_required' => $approvalRequired ? 1 : 0,
                ':exp' => $expiresAt,
                ':by' => $invitedBy,
            ]);
            return;
        } catch (Throwable $e) {
            // Fallback for old schema.
        }

        $legacyEmail = $emailValue ?? ('link-' . substr($tokenHash, 0, 12) . '@invite.local');
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_invites (company_id, email, token_hash, status, expires_at, invited_by, created_at, updated_at)
             VALUES (:cid, :email, :token, :status, :exp, :by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':email' => $legacyEmail,
            ':token' => $tokenHash,
            ':status' => 'pending',
            ':exp' => $expiresAt,
            ':by' => $invitedBy,
        ]);
    }

    public function revokePendingInvite(int $inviteId, int $companyId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE company_invites
             SET status = 'revoked', updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND company_id = :cid
               AND status IN ('pending', 'awaiting_approval')"
        );
        $stmt->execute([
            ':id' => $inviteId,
            ':cid' => $companyId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function findInviteByIdForCompany(int $companyId, int $inviteId): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT
                    ci.id,
                    ci.company_id,
                    ci.email,
                    ci.invite_mode,
                    ci.status,
                    ci.approval_status,
                    ci.approval_required,
                    ci.requested_user_id,
                    ru.email AS requested_user_email,
                    ru.first_name AS requested_user_first_name,
                    ru.last_name AS requested_user_last_name,
                    ci.expires_at,
                    ci.created_at
                 FROM company_invites ci
                 LEFT JOIN users ru ON ru.id = ci.requested_user_id
                 WHERE ci.company_id = :cid
                   AND ci.id = :id
                 LIMIT 1'
            );
            $stmt->execute([
                ':cid' => $companyId,
                ':id' => $inviteId,
            ]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            $stmt = $this->pdo->prepare(
                'SELECT id, company_id, email, status, expires_at, created_at
                 FROM company_invites
                 WHERE company_id = :cid
                   AND id = :id
                 LIMIT 1'
            );
            $stmt->execute([
                ':cid' => $companyId,
                ':id' => $inviteId,
            ]);
            $row = $stmt->fetch();
            return $row ?: null;
        }
    }

    public function refreshPendingInvite(
        int $companyId,
        int $inviteId,
        string $tokenHash,
        string $expiresAt,
        string $inviteMode = 'email',
        bool $approvalRequired = false
    ): bool
    {
        $mode = strtolower(trim($inviteMode));
        if (!in_array($mode, ['email', 'link'], true)) {
            $mode = 'email';
        }

        try {
            $stmt = $this->pdo->prepare(
                "UPDATE company_invites
                 SET token_hash = :token,
                     invite_mode = :mode,
                     status = 'pending',
                     approval_status = :approval_status,
                     approval_required = :approval_required,
                     requested_user_id = NULL,
                     approved_by = NULL,
                     approved_at = NULL,
                     expires_at = :exp,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE company_id = :cid
                   AND id = :id
                   AND status IN ('pending', 'awaiting_approval')"
            );
            $stmt->execute([
                ':token' => $tokenHash,
                ':mode' => $mode,
                ':approval_status' => $approvalRequired ? 'pending' : 'none',
                ':approval_required' => $approvalRequired ? 1 : 0,
                ':exp' => $expiresAt,
                ':cid' => $companyId,
                ':id' => $inviteId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            $stmt = $this->pdo->prepare(
                "UPDATE company_invites
                 SET token_hash = :token, status = 'pending', expires_at = :exp, updated_at = CURRENT_TIMESTAMP
                 WHERE company_id = :cid
                   AND id = :id
                   AND status = 'pending'"
            );
            $stmt->execute([
                ':token' => $tokenHash,
                ':exp' => $expiresAt,
                ':cid' => $companyId,
                ':id' => $inviteId,
            ]);
            return $stmt->rowCount() > 0;
        }
    }

    public function findMembersByCompanyId(int $companyId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT cm.id, cm.user_id, cm.role, cm.status, cm.created_at, u.first_name, u.last_name, u.email
             FROM company_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.company_id = :cid
             ORDER BY CASE cm.role
                WHEN 'owner' THEN 0
                WHEN 'admin' THEN 1
                WHEN 'manager' THEN 2
                WHEN 'member' THEN 3
                WHEN 'viewer' THEN 4
                ELSE 5
             END ASC, u.first_name ASC"
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findMemberByIdForCompany(int $companyId, int $memberId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, company_id, user_id, role, status
             FROM company_members
             WHERE company_id = :cid
               AND id = :mid
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':mid' => $memberId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countActiveAdminsByCompanyId(int $companyId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM company_members
             WHERE company_id = :cid
               AND role IN ('owner','admin')
               AND status = 'active'"
        );
        $stmt->execute([':cid' => $companyId]);
        return (int) $stmt->fetchColumn();
    }

    public function updateMemberRole(int $companyId, int $memberId, string $role): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE company_members
             SET role = :role, updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND id = :mid'
        );
        $stmt->execute([
            ':role' => $role,
            ':cid' => $companyId,
            ':mid' => $memberId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function updateMemberStatus(int $companyId, int $memberId, string $status): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE company_members
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND id = :mid'
        );
        $stmt->execute([
            ':status' => $status,
            ':cid' => $companyId,
            ':mid' => $memberId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function removeMember(int $companyId, int $memberId): bool
    {
        $memberUserId = 0;
        $member = $this->findMemberByIdForCompany($companyId, $memberId);
        if (is_array($member)) {
            $memberUserId = (int) ($member['user_id'] ?? 0);
        }

        if ($memberUserId > 0 && $this->tableExistsSafe('company_course_seat_assignments')) {
            if ($this->tableExistsSafe('enrollments')) {
                $stmtRevokeEnroll = $this->pdo->prepare(
                    "UPDATE enrollments
                     SET status = 'revoked', updated_at = CURRENT_TIMESTAMP
                     WHERE user_id = :uid
                       AND status = 'active'
                       AND course_id IN (
                           SELECT course_id
                           FROM company_course_seat_assignments
                           WHERE company_id = :cid
                             AND member_user_id = :uid
                             AND status = 'active'
                       )"
                );
                $stmtRevokeEnroll->execute([
                    ':cid' => $companyId,
                    ':uid' => $memberUserId,
                ]);
            }

            $stmtRevokeSeats = $this->pdo->prepare(
                "UPDATE company_course_seat_assignments
                 SET status = 'revoked',
                     revoked_at = CURRENT_TIMESTAMP,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE company_id = :cid
                   AND member_user_id = :uid
                   AND status = 'active'"
            );
            $stmtRevokeSeats->execute([
                ':cid' => $companyId,
                ':uid' => $memberUserId,
            ]);
        }

        if ($this->tableExistsSafe('company_team_members')) {
            $stmtDetach = $this->pdo->prepare(
                'DELETE FROM company_team_members WHERE company_id = :cid AND member_id = :mid'
            );
            $stmtDetach->execute([
                ':cid' => $companyId,
                ':mid' => $memberId,
            ]);
        }

        $stmt = $this->pdo->prepare(
            'DELETE FROM company_members
             WHERE company_id = :cid
               AND id = :mid'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':mid' => $memberId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function findInvitesByCompanyId(int $companyId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT
                    ci.id,
                    ci.email,
                    ci.invite_mode,
                    ci.status,
                    ci.approval_status,
                    ci.approval_required,
                    ci.requested_user_id,
                    ru.email AS requested_user_email,
                    ru.first_name AS requested_user_first_name,
                    ru.last_name AS requested_user_last_name,
                    ci.expires_at,
                    ci.created_at
                 FROM company_invites ci
                 LEFT JOIN users ru ON ru.id = ci.requested_user_id
                 WHERE ci.company_id = :cid
                 ORDER BY ci.created_at DESC
                 LIMIT 50'
            );
            $stmt->execute([':cid' => $companyId]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            $stmt = $this->pdo->prepare(
                'SELECT id, email, status, expires_at, created_at
                 FROM company_invites
                 WHERE company_id = :cid
                 ORDER BY created_at DESC
                 LIMIT 50'
            );
            $stmt->execute([':cid' => $companyId]);
            return $stmt->fetchAll() ?: [];
        }
    }

    public function approveInviteRequestedUser(int $companyId, int $inviteId, int $approvedBy): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, company_id, email, requested_user_id, status
             FROM company_invites
             WHERE company_id = :cid
               AND id = :id
             LIMIT 1"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':id' => $inviteId,
        ]);
        $invite = $stmt->fetch();
        if (!$invite) {
            return null;
        }
        if ((int) ($invite['requested_user_id'] ?? 0) < 1) {
            return null;
        }
        if ((string) ($invite['status'] ?? '') !== 'awaiting_approval') {
            return null;
        }

        $update = $this->pdo->prepare(
            "UPDATE company_invites
             SET status = 'accepted',
                 approval_status = 'approved',
                 approved_by = :approved_by,
                 approved_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND id = :id
               AND status = 'awaiting_approval'"
        );
        $update->execute([
            ':approved_by' => $approvedBy > 0 ? $approvedBy : null,
            ':cid' => $companyId,
            ':id' => $inviteId,
        ]);
        if ($update->rowCount() < 1) {
            return null;
        }

        return $invite;
    }

    public function rejectInviteRequestedUser(int $companyId, int $inviteId, int $approvedBy): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, company_id, email, requested_user_id, status
             FROM company_invites
             WHERE company_id = :cid
               AND id = :id
             LIMIT 1"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':id' => $inviteId,
        ]);
        $invite = $stmt->fetch();
        if (!$invite) {
            return null;
        }
        if ((int) ($invite['requested_user_id'] ?? 0) < 1) {
            return null;
        }
        if ((string) ($invite['status'] ?? '') !== 'awaiting_approval') {
            return null;
        }

        $update = $this->pdo->prepare(
            "UPDATE company_invites
             SET status = 'revoked',
                 approval_status = 'rejected',
                 approved_by = :approved_by,
                 approved_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND id = :id
               AND status = 'awaiting_approval'"
        );
        $update->execute([
            ':approved_by' => $approvedBy > 0 ? $approvedBy : null,
            ':cid' => $companyId,
            ':id' => $inviteId,
        ]);
        if ($update->rowCount() < 1) {
            return null;
        }

        return $invite;
    }

    public function attachActiveMember(int $companyId, int $userId, string $role = 'member'): void
    {
        try {
            $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (Throwable $e) {
            $driver = '';
        }

        $insert = $driver === 'sqlite'
            ? 'INSERT OR IGNORE INTO company_members (company_id, user_id, role, status, created_at, updated_at)
               VALUES (:cid, :uid, :role, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
            : 'INSERT IGNORE INTO company_members (company_id, user_id, role, status, created_at, updated_at)
               VALUES (:cid, :uid, :role, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';

        $stmt = $this->pdo->prepare($insert);
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
            ':role' => $role,
            ':status' => 'active',
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCompanySeatPurchaseRows(int $companyId): array
    {
        if (
            !$this->tableExistsSafe('company_course_seat_purchases')
            || !$this->tableExistsSafe('courses')
        ) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                p.course_id,
                c.title AS course_title,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN p.seats_total ELSE 0 END), 0) AS seats_total,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN p.total_xof ELSE 0 END), 0) AS total_xof,
                COALESCE(MAX(CASE WHEN p.status = 'active' THEN p.unit_price_xof ELSE 0 END), 0) AS unit_price_xof,
                COALESCE(SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END), 0) AS purchase_count
             FROM company_course_seat_purchases p
             JOIN courses c ON c.id = p.course_id
             WHERE p.company_id = :cid
             GROUP BY p.course_id, c.title
             ORDER BY c.title ASC"
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCompanyBillingRows(int $companyId): array
    {
        if (
            $companyId < 1
            || !$this->tableExistsSafe('orders')
            || !$this->tableExistsSafe('courses')
            || !$this->tableExistsSafe('users')
        ) {
            return [];
        }

        if ($this->tableExistsSafe('order_items') && $this->columnExistsSafe('order_items', 'company_id')) {
            return $this->findCompanyBillingRowsFromOrderItems($companyId);
        }

        if ($this->tableExistsSafe('company_course_seat_purchases')) {
            return $this->findCompanyBillingRowsFromSeatPurchases($companyId);
        }

        return [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCompanySeatAssignmentRows(int $companyId): array
    {
        if (
            !$this->tableExistsSafe('company_course_seat_assignments')
            || !$this->tableExistsSafe('users')
            || !$this->tableExistsSafe('courses')
        ) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                a.id,
                a.course_id,
                a.member_user_id,
                a.status,
                a.assigned_at,
                c.title AS course_title,
                u.first_name,
                u.last_name,
                u.email
             FROM company_course_seat_assignments a
             JOIN users u ON u.id = a.member_user_id
             JOIN courses c ON c.id = a.course_id
             WHERE a.company_id = :cid
               AND a.status = 'active'
             ORDER BY c.title ASC, a.assigned_at DESC"
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCompanySeatAssignmentsByUser(int $companyId, int $userId): array
    {
        if (
            $companyId < 1
            || $userId < 1
            || !$this->tableExistsSafe('company_course_seat_assignments')
            || !$this->tableExistsSafe('courses')
        ) {
            return [];
        }

        $stmt = $this->pdo->prepare(
            "SELECT
                a.id,
                a.course_id,
                a.member_user_id,
                a.status,
                a.assigned_at,
                c.title AS course_title
             FROM company_course_seat_assignments a
             JOIN courses c ON c.id = a.course_id
             WHERE a.company_id = :cid
               AND a.member_user_id = :uid
               AND a.status = 'active'
             ORDER BY a.assigned_at DESC, a.id DESC"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':uid' => $userId,
        ]);
        return $stmt->fetchAll() ?: [];
    }

    public function sumCompanyPurchasedSeatsForCourse(int $companyId, int $courseId): int
    {
        if (!$this->tableExistsSafe('company_course_seat_purchases')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(SUM(seats_total), 0)
             FROM company_course_seat_purchases
             WHERE company_id = :cid
               AND course_id = :course_id
               AND status = 'active'"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':course_id' => $courseId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function countCompanyActiveSeatAssignmentsForCourse(int $companyId, int $courseId): int
    {
        if (!$this->tableExistsSafe('company_course_seat_assignments')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM company_course_seat_assignments
             WHERE company_id = :cid
               AND course_id = :course_id
               AND status = 'active'"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':course_id' => $courseId,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function findActiveSeatAssignment(int $companyId, int $courseId, int $memberUserId): ?array
    {
        if (!$this->tableExistsSafe('company_course_seat_assignments')) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT id, company_id, course_id, member_user_id, status
             FROM company_course_seat_assignments
             WHERE company_id = :cid
               AND course_id = :course_id
               AND member_user_id = :member_uid
             LIMIT 1"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':course_id' => $courseId,
            ':member_uid' => $memberUserId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function upsertSeatAssignmentActive(int $companyId, int $courseId, int $memberUserId, int $assignedBy): bool
    {
        if (!$this->tableExistsSafe('company_course_seat_assignments')) {
            return false;
        }
        try {
            $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (Throwable $e) {
            $driver = '';
        }
        if ($driver === 'sqlite') {
            $existing = $this->findActiveSeatAssignment($companyId, $courseId, $memberUserId);
            if (is_array($existing)) {
                $stmt = $this->pdo->prepare(
                    "UPDATE company_course_seat_assignments
                     SET status = 'active',
                         assigned_by = :assigned_by,
                         assigned_at = CURRENT_TIMESTAMP,
                         revoked_at = NULL,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE company_id = :cid
                       AND course_id = :course_id
                       AND member_user_id = :member_uid"
                );
                $stmt->execute([
                    ':cid' => $companyId,
                    ':course_id' => $courseId,
                    ':member_uid' => $memberUserId,
                    ':assigned_by' => $assignedBy > 0 ? $assignedBy : null,
                ]);
                return true;
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO company_course_seat_assignments
                    (company_id, course_id, member_user_id, assigned_by, status, assigned_at, revoked_at, created_at, updated_at)
                 VALUES
                    (:cid, :course_id, :member_uid, :assigned_by, 'active', CURRENT_TIMESTAMP, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
            );
            $stmt->execute([
                ':cid' => $companyId,
                ':course_id' => $courseId,
                ':member_uid' => $memberUserId,
                ':assigned_by' => $assignedBy > 0 ? $assignedBy : null,
            ]);
            return true;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO company_course_seat_assignments
                (company_id, course_id, member_user_id, assigned_by, status, assigned_at, revoked_at, created_at, updated_at)
             VALUES
                (:cid, :course_id, :member_uid, :assigned_by, 'active', CURRENT_TIMESTAMP, NULL, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                status = 'active',
                assigned_by = VALUES(assigned_by),
                assigned_at = CURRENT_TIMESTAMP,
                revoked_at = NULL,
                updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':course_id' => $courseId,
            ':member_uid' => $memberUserId,
            ':assigned_by' => $assignedBy > 0 ? $assignedBy : null,
        ]);
        return true;
    }

    public function revokeSeatAssignment(int $companyId, int $courseId, int $memberUserId): bool
    {
        if (!$this->tableExistsSafe('company_course_seat_assignments')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE company_course_seat_assignments
             SET status = 'revoked',
                 revoked_at = CURRENT_TIMESTAMP,
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND course_id = :course_id
               AND member_user_id = :member_uid
               AND status = 'active'"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':course_id' => $courseId,
            ':member_uid' => $memberUserId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function ensureEnrollmentActive(int $userId, int $courseId): void
    {
        try {
            $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (Throwable $e) {
            $driver = '';
        }
        if ($driver === 'sqlite') {
            $update = $this->pdo->prepare(
                "UPDATE enrollments
                 SET status = 'active', updated_at = CURRENT_TIMESTAMP
                 WHERE user_id = :uid
                   AND course_id = :cid"
            );
            $update->execute([
                ':uid' => $userId,
                ':cid' => $courseId,
            ]);
            if ($update->rowCount() > 0) {
                return;
            }

            $insert = $this->pdo->prepare(
                "INSERT INTO enrollments (user_id, course_id, status, created_at, updated_at)
                 VALUES (:uid, :cid, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
            );
            $insert->execute([
                ':uid' => $userId,
                ':cid' => $courseId,
            ]);
            return;
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO enrollments (user_id, course_id, status, created_at, updated_at)
             VALUES (:uid, :cid, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                status = 'active',
                updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
    }

    public function revokeEnrollment(int $userId, int $courseId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE enrollments
             SET status = 'revoked', updated_at = CURRENT_TIMESTAMP
             WHERE user_id = :uid
               AND course_id = :cid
               AND status = 'active'"
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $courseId,
        ]);
    }

    public function findCourseById(int $courseId): ?array
    {
        if ($courseId < 1 || !$this->tableExistsSafe('courses')) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT id, title FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $courseId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function insertNotification(int $userId, string $title, string $body, ?string $url = null): void
    {
        if ($userId < 1 || !$this->tableExistsSafe('notifications')) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO notifications (user_id, title, body, url, created_at)
             VALUES (:uid, :title, :body, :url, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':title' => $title,
            ':body' => $body,
            ':url' => $url,
        ]);
    }

    public function findUserById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, roles_json FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateUserRoles(int $userId, string $rolesJson): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET roles_json = :roles, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            ':roles' => $rolesJson,
            ':id' => $userId,
        ]);
    }

    public function countActiveAdminMembershipsForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM company_members cm
             JOIN companies c ON c.id = cm.company_id
             WHERE cm.user_id = :uid
               AND cm.role IN ('owner','admin')
               AND cm.status = 'active'
               AND c.status = 'active'"
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function countActiveMembershipsForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM company_members cm
             JOIN companies c ON c.id = cm.company_id
             WHERE cm.user_id = :uid
               AND cm.status = 'active'
               AND c.status = 'active'"
        );
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function findTeamsByCompanyId(int $companyId): array
    {
        if (!$this->tableExistsSafe('company_teams')) {
            return [];
        }
        $hasTeamMembers = $this->tableExistsSafe('company_team_members');
        if ($hasTeamMembers) {
            $stmt = $this->pdo->prepare(
                "SELECT t.id, t.name, t.status, t.created_at, COUNT(ctm.id) AS member_count
                 FROM company_teams t
                 LEFT JOIN company_team_members ctm ON ctm.team_id = t.id
                 WHERE t.company_id = :cid
                 GROUP BY t.id, t.name, t.status, t.created_at
                 ORDER BY t.status = 'archived', t.name ASC"
            );
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT t.id, t.name, t.status, t.created_at, 0 AS member_count
                 FROM company_teams t
                 WHERE t.company_id = :cid
                 ORDER BY t.status = 'archived', t.name ASC"
            );
        }
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    public function findTeamByIdForCompany(int $companyId, int $teamId): ?array
    {
        if (!$this->tableExistsSafe('company_teams')) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, company_id, name, status
             FROM company_teams
             WHERE company_id = :cid AND id = :tid
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':tid' => $teamId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createTeam(int $companyId, string $name, int $createdBy): bool
    {
        if (!$this->tableExistsSafe('company_teams')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_teams (company_id, name, status, created_by, created_at, updated_at)
             VALUES (:cid, :name, :status, :by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':name' => $name,
            ':status' => 'active',
            ':by' => $createdBy > 0 ? $createdBy : null,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function archiveTeam(int $companyId, int $teamId): bool
    {
        if (!$this->tableExistsSafe('company_teams')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE company_teams
             SET status = 'archived', updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND id = :tid
               AND status = 'active'"
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':tid' => $teamId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function clearTeamAssignmentsForMember(int $companyId, int $memberId): void
    {
        if (!$this->tableExistsSafe('company_team_members')) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'DELETE FROM company_team_members
             WHERE company_id = :cid
               AND member_id = :mid'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':mid' => $memberId,
        ]);
    }

    public function assignTeamToMember(int $companyId, int $teamId, int $memberId, int $assignedBy): bool
    {
        if (!$this->tableExistsSafe('company_team_members')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_team_members (company_id, team_id, member_id, assigned_by, created_at, updated_at)
             VALUES (:cid, :tid, :mid, :by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':tid' => $teamId,
            ':mid' => $memberId,
            ':by' => $assignedBy > 0 ? $assignedBy : null,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function findTeamAssignmentsByCompanyId(int $companyId): array
    {
        if (!$this->tableExistsSafe('company_teams') || !$this->tableExistsSafe('company_team_members')) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            "SELECT ctm.member_id, ctm.team_id, t.name AS team_name
             FROM company_team_members ctm
             JOIN company_teams t ON t.id = ctm.team_id
             WHERE ctm.company_id = :cid
               AND t.status = 'active'
             ORDER BY t.name ASC"
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    public function ensureDefaultOnboardingTasks(int $companyId, int $ownerUserId): void
    {
        if (!$this->tableExistsSafe('company_onboarding_tasks')) {
            return;
        }

        $stmtCount = $this->pdo->prepare(
            'SELECT COUNT(*) FROM company_onboarding_tasks WHERE company_id = :cid'
        );
        $stmtCount->execute([':cid' => $companyId]);
        $count = (int) $stmtCount->fetchColumn();
        if ($count > 0) {
            return;
        }

        $rows = [
            ['profile', 'Configurer l organisation', 'Nom, referents, objectifs de depart.', '+0 day', 10],
            ['invites', 'Inviter les membres cles', 'Invitez au moins 3 membres de l equipe.', '+7 day', 20],
            ['first_review', 'Programmer la revue de pilotage', 'Planifiez une revue de suivi a 30 jours.', '+30 day', 30],
        ];

        $stmtInsert = $this->pdo->prepare(
            'INSERT INTO company_onboarding_tasks
             (company_id, task_key, title, description, due_at, sort_order, status, owner_user_id, created_at, updated_at)
             VALUES (:cid, :task_key, :title, :description, :due_at, :sort_order, :status, :owner_uid, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );

        foreach ($rows as [$taskKey, $title, $description, $dueDelta, $sortOrder]) {
            $dueAt = date('Y-m-d H:i:s', strtotime($dueDelta));
            $stmtInsert->execute([
                ':cid' => $companyId,
                ':task_key' => $taskKey,
                ':title' => $title,
                ':description' => $description,
                ':due_at' => $dueAt,
                ':sort_order' => $sortOrder,
                ':status' => 'pending',
                ':owner_uid' => $ownerUserId > 0 ? $ownerUserId : null,
            ]);
        }
    }

    public function findOnboardingTasksByCompanyId(int $companyId): array
    {
        if (!$this->tableExistsSafe('company_onboarding_tasks')) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, task_key, title, description, due_at, sort_order, status, owner_user_id, completed_at
             FROM company_onboarding_tasks
             WHERE company_id = :cid
             ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    public function updateOnboardingTaskStatus(int $companyId, int $taskId, string $status, int $actorUserId): bool
    {
        if (!$this->tableExistsSafe('company_onboarding_tasks')) {
            return false;
        }
        $completedAt = $status === 'done' ? date('Y-m-d H:i:s') : null;
        $stmt = $this->pdo->prepare(
            'UPDATE company_onboarding_tasks
             SET status = :status,
                 completed_at = :completed_at,
                 owner_user_id = COALESCE(owner_user_id, :owner_uid),
                 updated_at = CURRENT_TIMESTAMP
             WHERE company_id = :cid
               AND id = :tid'
        );
        $stmt->execute([
            ':status' => $status,
            ':completed_at' => $completedAt,
            ':owner_uid' => $actorUserId > 0 ? $actorUserId : null,
            ':cid' => $companyId,
            ':tid' => $taskId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function findSuccessReviewsByCompanyId(int $companyId, int $limit = 12): array
    {
        if (!$this->tableExistsSafe('company_success_reviews')) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, review_date, cadence, risk_level, status, notes, next_actions, created_at
             FROM company_success_reviews
             WHERE company_id = :cid
             ORDER BY review_date DESC, id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function createSuccessReview(
        int $companyId,
        int $ownerUserId,
        string $reviewDate,
        string $cadence,
        string $riskLevel,
        string $status,
        string $notes,
        string $nextActions
    ): bool {
        if (!$this->tableExistsSafe('company_success_reviews')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_success_reviews
             (company_id, owner_user_id, review_date, cadence, risk_level, status, notes, next_actions, created_at, updated_at)
             VALUES (:cid, :owner_uid, :review_date, :cadence, :risk, :status, :notes, :next_actions, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':owner_uid' => $ownerUserId > 0 ? $ownerUserId : null,
            ':review_date' => $reviewDate,
            ':cadence' => $cadence,
            ':risk' => $riskLevel,
            ':status' => $status,
            ':notes' => $notes,
            ':next_actions' => $nextActions,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function addCompanyActivityEvent(int $companyId, int $actorUserId, string $eventType, string $title, array $meta = []): void
    {
        if (!$this->tableExistsSafe('company_activity_events')) {
            return;
        }
        $metaJson = json_encode($meta);
        if (!is_string($metaJson) || $metaJson === '') {
            $metaJson = '{}';
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_activity_events (company_id, actor_user_id, event_type, title, meta_json, created_at)
             VALUES (:cid, :actor_uid, :event_type, :title, :meta_json, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':actor_uid' => $actorUserId > 0 ? $actorUserId : null,
            ':event_type' => $eventType,
            ':title' => $title,
            ':meta_json' => $metaJson,
        ]);
    }

    public function findRecentCompanyActivity(int $companyId, int $limit = 20): array
    {
        if (!$this->tableExistsSafe('company_activity_events')) {
            return [];
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, actor_user_id, event_type, title, meta_json, created_at
             FROM company_activity_events
             WHERE company_id = :cid
             ORDER BY created_at DESC, id DESC
             LIMIT :lim'
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', max(1, $limit), \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countDistinctActiveActorsByDays(int $companyId, int $days): int
    {
        if (!$this->tableExistsSafe('company_activity_events')) {
            return 0;
        }
        $days = max(1, $days);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT actor_user_id)
             FROM company_activity_events
             WHERE company_id = :cid
               AND actor_user_id IS NOT NULL
               AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :days DAY)"
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function upsertCompanyMetricsDaily(int $companyId, string $metricDate, array $metrics): void
    {
        if (!$this->tableExistsSafe('company_metrics_daily')) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO company_metrics_daily
             (company_id, metric_date, members_total, members_active, invites_pending, activation_30d, wau, mau, wau_mau_ratio, retention_90d, expansion_30d, churn_30d, created_at, updated_at)
             VALUES
             (:cid, :metric_date, :members_total, :members_active, :invites_pending, :activation_30d, :wau, :mau, :wau_mau_ratio, :retention_90d, :expansion_30d, :churn_30d, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE
                members_total = VALUES(members_total),
                members_active = VALUES(members_active),
                invites_pending = VALUES(invites_pending),
                activation_30d = VALUES(activation_30d),
                wau = VALUES(wau),
                mau = VALUES(mau),
                wau_mau_ratio = VALUES(wau_mau_ratio),
                retention_90d = VALUES(retention_90d),
                expansion_30d = VALUES(expansion_30d),
                churn_30d = VALUES(churn_30d),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':metric_date' => $metricDate,
            ':members_total' => (int) ($metrics['members_total'] ?? 0),
            ':members_active' => (int) ($metrics['members_active'] ?? 0),
            ':invites_pending' => (int) ($metrics['invites_pending'] ?? 0),
            ':activation_30d' => (float) ($metrics['activation_30d'] ?? 0),
            ':wau' => (int) ($metrics['wau'] ?? 0),
            ':mau' => (int) ($metrics['mau'] ?? 0),
            ':wau_mau_ratio' => (float) ($metrics['wau_mau_ratio'] ?? 0),
            ':retention_90d' => (float) ($metrics['retention_90d'] ?? 0),
            ':expansion_30d' => (int) ($metrics['expansion_30d'] ?? 0),
            ':churn_30d' => (float) ($metrics['churn_30d'] ?? 0),
        ]);
    }

    public function findCompanyMetricsByCompanyId(int $companyId, int $days = 90): array
    {
        if (!$this->tableExistsSafe('company_metrics_daily')) {
            return [];
        }
        $days = max(1, $days);
        $stmt = $this->pdo->prepare(
            "SELECT metric_date, members_total, members_active, invites_pending, activation_30d, wau, mau, wau_mau_ratio, retention_90d, expansion_30d, churn_30d
             FROM company_metrics_daily
             WHERE company_id = :cid
               AND metric_date >= DATE_SUB(CURRENT_DATE, INTERVAL :days DAY)
             ORDER BY metric_date ASC"
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function findMetricSnapshotBeforeDate(int $companyId, string $metricDate): ?array
    {
        if (!$this->tableExistsSafe('company_metrics_daily')) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT metric_date, members_active
             FROM company_metrics_daily
             WHERE company_id = :cid
               AND metric_date <= :metric_date
             ORDER BY metric_date DESC
             LIMIT 1'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':metric_date' => $metricDate,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function countMfaEnabledMembersByCompany(int $companyId): int
    {
        if (!$this->tableExistsSafe('company_members') || !$this->columnExistsSafe('users', 'mfa_enabled')) {
            return 0;
        }
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM company_members cm
             JOIN users u ON u.id = cm.user_id
             WHERE cm.company_id = :cid
               AND cm.status = 'active'
               AND u.mfa_enabled = 1"
        );
        $stmt->execute([':cid' => $companyId]);
        return (int) $stmt->fetchColumn();
    }

    public function isCompanySsoEnabled(int $companyId): bool
    {
        if (!$this->tableExistsSafe('company_sso_configs')) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM company_sso_configs WHERE company_id = :cid AND is_enabled = 1 LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId]);
        return (bool) $stmt->fetchColumn();
    }

    public function findCompanySsoConfig(int $companyId): ?array
    {
        if (!$this->tableExistsSafe('company_sso_configs')) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT id, company_id, provider, domain, issuer, entry_point, cert_pem, client_id, client_secret_enc, is_enabled, created_at, updated_at
             FROM company_sso_configs
             WHERE company_id = :cid
             LIMIT 1'
        );
        $stmt->execute([':cid' => $companyId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findCompanySsoConfigByDomain(string $domain): ?array
    {
        if (!$this->tableExistsSafe('company_sso_configs') || !$this->tableExistsSafe('companies')) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT csc.id, csc.company_id, csc.provider, csc.domain, csc.issuer, csc.entry_point,
                    csc.cert_pem, csc.client_id, csc.client_secret_enc, csc.is_enabled,
                    c.name AS company_name, c.status AS company_status
             FROM company_sso_configs csc
             JOIN companies c ON c.id = csc.company_id
             WHERE csc.domain = :domain
               AND csc.is_enabled = 1
               AND c.status = "active"
             LIMIT 1'
        );
        $stmt->execute([':domain' => $domain]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function upsertCompanySsoConfig(int $companyId, array $data, ?int $actorUserId): void
    {
        if (!$this->tableExistsSafe('company_sso_configs')) {
            return;
        }
        $payload = [
            ':cid' => $companyId,
            ':provider' => (string) ($data['provider'] ?? 'oidc'),
            ':domain' => $data['domain'] ?? null,
            ':issuer' => $data['issuer'] ?? null,
            ':entry_point' => $data['entry_point'] ?? null,
            ':cert_pem' => $data['cert_pem'] ?? null,
            ':client_id' => $data['client_id'] ?? null,
            ':client_secret_enc' => $data['client_secret_enc'] ?? null,
            ':is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
            ':created_by' => $actorUserId,
        ];
        $payloadUpdate = $payload;
        unset($payloadUpdate[':created_by']);

        try {
            $driver = (string) $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } catch (Throwable $e) {
            $driver = '';
        }

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM company_sso_configs WHERE company_id = :cid LIMIT 1'
            );
            $stmt->execute([':cid' => $companyId]);
            $exists = (int) $stmt->fetchColumn() > 0;

            if ($exists) {
                $stmt = $this->pdo->prepare(
                    'UPDATE company_sso_configs
                     SET provider = :provider,
                         domain = :domain,
                         issuer = :issuer,
                         entry_point = :entry_point,
                         cert_pem = :cert_pem,
                         client_id = :client_id,
                         client_secret_enc = :client_secret_enc,
                         is_enabled = :is_enabled,
                         updated_at = CURRENT_TIMESTAMP
                     WHERE company_id = :cid'
                );
                $stmt->execute($payloadUpdate);
                return;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO company_sso_configs (
                    company_id, provider, domain, issuer, entry_point, cert_pem, client_id, client_secret_enc, is_enabled, created_by, created_at, updated_at
                ) VALUES (
                    :cid, :provider, :domain, :issuer, :entry_point, :cert_pem, :client_id, :client_secret_enc, :is_enabled, :created_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )'
            );
            $stmt->execute($payload);
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO company_sso_configs (
                company_id, provider, domain, issuer, entry_point, cert_pem, client_id, client_secret_enc, is_enabled, created_by
            ) VALUES (
                :cid, :provider, :domain, :issuer, :entry_point, :cert_pem, :client_id, :client_secret_enc, :is_enabled, :created_by
            ) ON DUPLICATE KEY UPDATE
                provider = VALUES(provider),
                domain = VALUES(domain),
                issuer = VALUES(issuer),
                entry_point = VALUES(entry_point),
                cert_pem = VALUES(cert_pem),
                client_id = VALUES(client_id),
                client_secret_enc = VALUES(client_secret_enc),
                is_enabled = VALUES(is_enabled),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute($payload);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function findCompanyAuditEvents(int $companyId, int $limit = 15): array
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return [];
        }
        $limit = max(1, $limit);
        $stmt = $this->pdo->prepare(
            'SELECT al.id, al.action, al.created_at, al.user_id, u.first_name, u.last_name, u.email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.target_type = "company"
               AND al.target_id = :cid
             ORDER BY al.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function countCompanyAuditEvents(int $companyId, int $days = 30): int
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return 0;
        }
        $days = max(1, $days);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM audit_logs
             WHERE target_type = 'company'
               AND target_id = :cid
               AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :days DAY)"
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function addAuditEvent(?int $userId, string $action, string $targetType, ?int $targetId, array $meta = []): void
    {
        if (!$this->tableExistsSafe('audit_logs')) {
            return;
        }
        $metaJson = json_encode($meta);
        if (!is_string($metaJson) || $metaJson === '') {
            $metaJson = '{}';
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, meta_json, created_at)
             VALUES (:uid, :action, :target_type, :target_id, :meta_json, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':action' => $action,
            ':target_type' => $targetType,
            ':target_id' => $targetId,
            ':meta_json' => $metaJson,
        ]);
    }

    public function hasTableForFeature(string $tableName): bool
    {
        return $this->tableExistsSafe($tableName);
    }

    private function tableExistsSafe(string $table): bool
    {
        try {
            return $this->tableExists($table);
        } catch (Throwable $e) {
            try {
                $stmt = $this->pdo->prepare(
                    "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table LIMIT 1"
                );
                $stmt->execute([':table' => $table]);
                return (bool) $stmt->fetchColumn();
            } catch (Throwable $_ignored) {
                return false;
            }
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function findCompanyBillingRowsFromOrderItems(int $companyId): array
    {
        $quantityExpr = $this->columnExistsSafe('order_items', 'quantity')
            ? 'COALESCE(oi.quantity, 1)'
            : '1';
        $unitPriceExpr = $this->columnExistsSafe('order_items', 'unit_price_xof')
            ? 'COALESCE(oi.unit_price_xof, 0)'
            : '0';

        $paymentSelect = ', NULL AS payment_reference, NULL AS payment_status';
        $paymentJoin = '';
        if ($this->tableExistsSafe('payments')) {
            $paymentSelect = ', pay.reference AS payment_reference, pay.status AS payment_status';
            $paymentJoin = 'LEFT JOIN payments pay ON pay.id = (
                SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.id DESC LIMIT 1
            )';
        }

        $purchaseSelect = ', NULL AS seat_purchase_id, NULL AS seat_purchase_status, NULL AS purchase_created_at';
        $purchaseJoin = '';
        if ($this->tableExistsSafe('company_course_seat_purchases')) {
            $purchaseSelect = ', cp.id AS seat_purchase_id, cp.status AS seat_purchase_status, cp.created_at AS purchase_created_at';
            $purchaseJoin = 'LEFT JOIN company_course_seat_purchases cp
                ON cp.company_id = :purchase_cid
               AND cp.order_item_id = oi.id';
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                oi.id AS order_item_id,
                o.id AS order_id,
                o.reference AS order_reference,
                o.status AS order_status,
                o.provider AS order_provider,
                o.created_at AS order_created_at,
                oi.course_id,
                c.title AS course_title,
                ' . $quantityExpr . ' AS seats_total,
                ' . $unitPriceExpr . ' AS unit_price_xof,
                (' . $quantityExpr . ' * ' . $unitPriceExpr . ') AS total_xof,
                u.id AS purchased_by,
                u.first_name AS purchased_by_first_name,
                u.last_name AS purchased_by_last_name,
                u.email AS purchased_by_email'
                . $paymentSelect
                . $purchaseSelect . '
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             JOIN courses c ON c.id = oi.course_id
             LEFT JOIN users u ON u.id = o.user_id
             ' . $paymentJoin . '
             ' . $purchaseJoin . '
             WHERE oi.company_id = :cid
             ORDER BY o.created_at DESC, oi.id DESC'
        );
        $params = [':cid' => $companyId];
        if ($purchaseJoin !== '') {
            $params[':purchase_cid'] = $companyId;
        }
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function findCompanyBillingRowsFromSeatPurchases(int $companyId): array
    {
        $paymentSelect = ', NULL AS payment_reference, NULL AS payment_status';
        $paymentJoin = '';
        if ($this->tableExistsSafe('payments')) {
            $paymentSelect = ', pay.reference AS payment_reference, pay.status AS payment_status';
            $paymentJoin = 'LEFT JOIN payments pay ON pay.id = (
                SELECT p2.id FROM payments p2 WHERE p2.order_id = o.id ORDER BY p2.id DESC LIMIT 1
            )';
        }

        $stmt = $this->pdo->prepare(
            'SELECT
                p.id AS seat_purchase_id,
                p.status AS seat_purchase_status,
                p.created_at AS purchase_created_at,
                p.order_item_id,
                p.order_id,
                o.reference AS order_reference,
                o.status AS order_status,
                o.provider AS order_provider,
                o.created_at AS order_created_at,
                p.course_id,
                c.title AS course_title,
                p.seats_total,
                p.unit_price_xof,
                p.total_xof,
                u.id AS purchased_by,
                u.first_name AS purchased_by_first_name,
                u.last_name AS purchased_by_last_name,
                u.email AS purchased_by_email'
                . $paymentSelect . '
             FROM company_course_seat_purchases p
             LEFT JOIN orders o ON o.id = p.order_id
             JOIN courses c ON c.id = p.course_id
             LEFT JOIN users u ON u.id = p.purchased_by
             ' . $paymentJoin . '
             WHERE p.company_id = :cid
             ORDER BY COALESCE(o.created_at, p.created_at) DESC, p.id DESC'
        );
        $stmt->execute([':cid' => $companyId]);
        return $stmt->fetchAll() ?: [];
    }

    private function columnExistsSafe(string $table, string $column): bool
    {
        try {
            return $this->columnExists($table, $column);
        } catch (Throwable $e) {
            try {
                $stmt = $this->pdo->query('PRAGMA table_info(' . $table . ')');
                if (!$stmt) {
                    return false;
                }
                foreach (($stmt->fetchAll() ?: []) as $row) {
                    if ((string) ($row['name'] ?? '') === $column) {
                        return true;
                    }
                }
            } catch (Throwable $_ignored) {
                return false;
            }
            return false;
        }
    }
}
