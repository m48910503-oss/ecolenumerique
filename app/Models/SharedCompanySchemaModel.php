<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class SharedCompanySchemaModel extends Model
{
    public function ensureCompaniesTable(): bool
    {
        if (!$this->tableExists('companies')) {
            return false;
        }
        return $this->ensureCompanyProfileColumns();
    }

    public function ensureCompanyMembersTable(): bool
    {
        return $this->tableExists('company_members');
    }

    public function ensureCompanyInvitesTable(): bool
    {
        return $this->tableExists('company_invites');
    }

    public function ensureCompanyTeamsTable(): bool
    {
        return $this->tableExists('company_teams');
    }

    public function ensureCompanyTeamMembersTable(): bool
    {
        return $this->tableExists('company_team_members');
    }

    public function ensureCompanyOnboardingTasksTable(): bool
    {
        return $this->tableExists('company_onboarding_tasks');
    }

    public function ensureCompanySuccessReviewsTable(): bool
    {
        return $this->tableExists('company_success_reviews');
    }

    public function ensureCompanyActivityEventsTable(): bool
    {
        return $this->tableExists('company_activity_events');
    }

    public function ensureCompanyMetricsDailyTable(): bool
    {
        return $this->tableExists('company_metrics_daily');
    }

    public function ensureCompanySsoConfigsTable(): bool
    {
        return $this->tableExists('company_sso_configs');
    }

    public function ensureCompanyProfileColumns(): bool
    {
        if (!$this->tableExists('companies')) {
            return false;
        }

        return $this->columnExists('companies', 'company_email')
            && $this->columnExists('companies', 'company_phone')
            && $this->columnExists('companies', 'country')
            && $this->columnExists('companies', 'city')
            && $this->columnExists('companies', 'address_line')
            && $this->columnExists('companies', 'logo_path');
    }

    public function ensureUsersMfaColumns(): bool
    {
        return $this->columnExists('users', 'mfa_enabled')
            && $this->columnExists('users', 'mfa_enabled_at');
    }

    public function findActiveCompanyIdByUser(int $userId): ?int
    {
        $stmt = $this->pdo->prepare(
            'SELECT cm.company_id
             FROM company_members cm
             JOIN companies c ON c.id = cm.company_id
             WHERE cm.user_id = :uid
               AND cm.status = "active"
               AND c.status = "active"
             ORDER BY
               CASE cm.role
                 WHEN "owner" THEN 0
                 WHEN "admin" THEN 1
                 WHEN "manager" THEN 2
                 WHEN "member" THEN 3
                 WHEN "viewer" THEN 4
                 ELSE 5
               END ASC,
               cm.id DESC
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $companyId = (int) $stmt->fetchColumn();
        return $companyId > 0 ? $companyId : null;
    }
}
