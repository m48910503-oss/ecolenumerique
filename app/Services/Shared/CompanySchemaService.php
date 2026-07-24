<?php
declare(strict_types=1);

namespace App\Services\Shared;

use App\Core\Service;
use App\Models\SharedCompanySchemaModel;
use Throwable;

final class CompanySchemaService extends Service
{
    private SharedCompanySchemaModel $model;

    public function __construct(SharedCompanySchemaModel $model)
    {
        $this->model = $model;
    }

    public function ensureCompaniesTable(): bool
    {
        try {
            return $this->model->ensureCompaniesTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyMembersTable(): bool
    {
        try {
            return $this->model->ensureCompanyMembersTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyInvitesTable(): bool
    {
        try {
            return $this->model->ensureCompanyInvitesTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyTeamsTable(): bool
    {
        try {
            return $this->model->ensureCompanyTeamsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyTeamMembersTable(): bool
    {
        try {
            return $this->model->ensureCompanyTeamMembersTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyOnboardingTasksTable(): bool
    {
        try {
            return $this->model->ensureCompanyOnboardingTasksTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanySuccessReviewsTable(): bool
    {
        try {
            return $this->model->ensureCompanySuccessReviewsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyActivityEventsTable(): bool
    {
        try {
            return $this->model->ensureCompanyActivityEventsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanyMetricsDailyTable(): bool
    {
        try {
            return $this->model->ensureCompanyMetricsDailyTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCompanySsoConfigsTable(): bool
    {
        try {
            return $this->model->ensureCompanySsoConfigsTable();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureUsersMfaColumns(): bool
    {
        try {
            return $this->model->ensureUsersMfaColumns();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function ensureCoreSchema(): bool
    {
        return $this->ensureCompaniesTable() && $this->ensureCompanyMembersTable();
    }

    public function ensureFullSchema(): bool
    {
        return $this->ensureCoreSchema() && $this->ensureCompanyInvitesTable();
    }

    public function ensureDashboardSchema(): bool
    {
        return $this->ensureFullSchema()
            && $this->ensureCompanyTeamsTable()
            && $this->ensureCompanyTeamMembersTable()
            && $this->ensureCompanyOnboardingTasksTable()
            && $this->ensureCompanySuccessReviewsTable()
            && $this->ensureCompanyActivityEventsTable()
            && $this->ensureCompanyMetricsDailyTable()
            && $this->ensureCompanySsoConfigsTable()
            && $this->ensureUsersMfaColumns();
    }

    public function getUserCompanyId(int $userId): ?int
    {
        if ($userId < 1 || !$this->ensureCoreSchema()) {
            return null;
        }
        try {
            return $this->model->findActiveCompanyIdByUser($userId);
        } catch (Throwable $e) {
            return null;
        }
    }
}
