<?php

declare(strict_types=1);



namespace App\Services\Admin;



use App\Core\Service;

use App\Models\AdminDashboardModel;



final class DashboardService extends Service

{

    private AdminDashboardModel $model;



    public function __construct(AdminDashboardModel $model)

    {

        $this->model = $model;

    }



    /**

     * @param array<string,mixed> $user

     * @param array<string,mixed> $query

     * @return array<string,mixed>

     */

    public function buildViewData(array $user, array $query = []): array

    {

        $unreadCount = $this->model->countUnreadNotifications((int) ($user['id'] ?? 0));

        $openTickets = $this->model->countOpenSupportTickets();

        $highPriorityTickets = $this->model->countHighPrioritySupportTickets();

        $pendingCourses = $this->model->countPendingCourses();
        $publishedCourses = $this->model->countPublishedCourses();
        $pendingTeacherApplications = $this->model->countPendingTeacherApplications();

        $pendingWithdrawals = $this->model->countPendingWithdrawals();

        $failedPayments = $this->model->countFailedPayments();

        $pendingPayments = $this->model->countPendingPayments();

        $recentNotifications = $this->model->countRecentNotifications(24);

        $totalRevenueXof = $this->model->sumPaidOrdersTotalXof();

        $paidOrders = $this->model->countPaidOrders();

        $commissionPercent = $this->model->commissionPercent(20.0);

        $adminRevenueXof = (int) round($totalRevenueXof * ($commissionPercent / 100));

        $totalRegisteredUsers = $this->model->countRegisteredUsers();

        $totalTeachers = $this->model->countActiveUsersByRole('teacher');

        $totalStudents = $this->model->countActiveUsersByRole('student');

        $totalCompanies = $this->model->countActiveCompanies();

        $newUsersThisMonth = $this->model->countUsersCreatedSince(date('Y-m-01'));

        $conversionRate = $totalRegisteredUsers > 0
            ? round(($paidOrders / $totalRegisteredUsers) * 100, 1)
            : 0.0;

        $kpiUpdatedAt = date('H:i:s');

        $dashboardSummary = [
            'students' => $totalStudents,
            'teachers' => $totalTeachers,
            'registered_users' => $totalRegisteredUsers,
            'companies' => $totalCompanies,
            'published_courses' => $publishedCourses,
            'pending_courses' => $pendingCourses,
            'pending_teacher_applications' => $pendingTeacherApplications,
            'pending_withdrawals' => $pendingWithdrawals,
            'failed_payments' => $failedPayments,
            'pending_payments' => $pendingPayments,
            'open_tickets' => $openTickets,
            'high_priority_tickets' => $highPriorityTickets,
            'revenue_xof' => $totalRevenueXof,
            'admin_revenue_xof' => $adminRevenueXof,
            'commission_percent' => $commissionPercent,
            'paid_orders' => $paidOrders,
            'conversion_rate' => $conversionRate,
            'new_users_this_month' => $newUsersThisMonth,
        ];



        $operationalKpis = [
            [
                'label' => 'Chiffre d’affaires total',
                'value' => number_format($totalRevenueXof, 0, ',', ' ') . ' F CFA',
                'meta' => 'Part admin ' . number_format($commissionPercent, 2, ',', ' ') . '% : '
                    . number_format($adminRevenueXof, 0, ',', ' ') . ' F CFA',
                'tone' => 'info',
                'icon' => 'wallet',
            ],
            [
                'label' => 'Inscrits totaux',
                'value' => $totalRegisteredUsers,
                'meta' => 'Comptes actifs et en attente',
                'tone' => 'info',
                'icon' => 'users',
            ],
            [
                'label' => 'Enseignants actifs',
                'value' => $totalTeachers,
                'meta' => 'Profils avec rôle enseignant',
                'tone' => $totalTeachers > 0 ? 'success' : 'warning',
                'icon' => 'user',
            ],
            [
                'label' => 'Entreprises actives',
                'value' => $totalCompanies,
                'meta' => 'Comptes entreprise opérationnels',
                'tone' => $totalCompanies > 0 ? 'success' : 'warning',
                'icon' => 'layout',
            ],
            [
                'label' => 'Étudiants actifs',
                'value' => $totalStudents,
                'meta' => 'Profils avec rôle étudiant',
                'tone' => $totalStudents > 0 ? 'info' : 'warning',
                'icon' => 'book-open',
            ],
            [
                'label' => 'Conversations ouvertes',
                'value' => $openTickets,
                'meta' => 'Support en cours',
                'tone' => $openTickets > 0 ? 'warning' : 'success',
                'icon' => 'message-circle',
            ],
            [
                'label' => 'Conversations prioritaires',
                'value' => $highPriorityTickets,
                'meta' => 'Support haute priorité',
                'tone' => $highPriorityTickets > 0 ? 'danger' : 'success',
                'icon' => 'alert-triangle',
            ],
            [
                'label' => 'Cours à valider',
                'value' => $pendingCourses,
                'meta' => 'Soumis par les enseignants',
                'tone' => $pendingCourses > 0 ? 'warning' : 'success',
                'icon' => 'check-circle',
            ],
            [
                'label' => 'Retraits en attente',
                'value' => $pendingWithdrawals,
                'meta' => 'Demandes en attente',
                'tone' => $pendingWithdrawals > 0 ? 'warning' : 'success',
                'icon' => 'credit-card',
            ],
            [
                'label' => 'Paiements en attente',
                'value' => $pendingPayments,
                'meta' => 'Transactions à vérifier',
                'tone' => $pendingPayments > 0 ? 'warning' : 'success',
                'icon' => 'receipt',
            ],
        ];

        $actionableAlerts = [

            [

                'scope' => 'urgent',

                'label' => 'Urgent',

                'value' => $highPriorityTickets,

                'meta' => 'Conversations support haute priorité',

                'cta' => 'Ouvrir conversation',

                'url' => $this->siteUrl('admin/support?status=open&priority=high'),

                'tone' => $highPriorityTickets > 0 ? 'danger' : 'success',

                'icon' => 'alert-triangle',

            ],

            [

                'scope' => 'todo',

                'label' => 'À traiter',

                'value' => $pendingCourses,

                'meta' => 'Cours soumis en attente de validation',

                'cta' => 'Valider cours',

                'url' => $this->siteUrl('admin/courses'),

                'tone' => $pendingCourses > 0 ? 'warning' : 'success',

                'icon' => 'check-circle',

            ],

            [

                'scope' => 'blocked',

                'label' => 'Bloqué',

                'value' => $failedPayments,

                'meta' => 'Paiements en échec à reprendre',

                'cta' => 'Relancer paiement',

                'url' => $this->siteUrl('admin/sales'),

                'tone' => $failedPayments > 0 ? 'danger' : 'success',

                'icon' => 'credit-card',

            ],

        ];



        $platformHealth = $this->buildPlatformHealthCards($recentNotifications);

        return [

            'user' => $user,

            'unreadCount' => $unreadCount,

            'dashboardSummary' => $dashboardSummary,

            'dashboardChart' => $this->model->fetchMonthlyRegistrationCounts(6),

            'pendingCourseCards' => $this->model->fetchPendingCourseCards(3),

            'latestUsers' => $this->model->fetchRecentUsers(4),

            'latestTransactions' => $this->model->fetchRecentTransactions(5),

            'operationalKpis' => $operationalKpis,

            'actionableAlerts' => $actionableAlerts,

            'kpiUpdatedAt' => $kpiUpdatedAt,



            'platformHealth' => $platformHealth,


        ];

    }



    /**

     * @return array<int,array<string,mixed>>

     */

    private function buildPriorityQueue(): array

    {

        $queue = [];



        foreach ($this->model->fetchSupportPriorityCandidates(8) as $row) {

            $priority = strtolower((string) ($row['priority'] ?? 'normal'));

            $impact = $priority === 'high' ? 3 : ($priority === 'low' ? 1 : 2);

            $slaHours = $priority === 'high' ? 4 : 12;

            $ageHours = $this->hoursSince((string) ($row['last_message_at'] ?? $row['created_at'] ?? ''));

            $score = $this->computePriorityScore($impact, $ageHours, $slaHours);



            $queue[] = [

                'score' => $score,

                'tone' => $priority === 'high' ? 'danger' : 'warning',

                'label' => 'Ticket support #' . (int) ($row['id'] ?? 0),

                'meta' => trim((string) ($row['subject'] ?? 'Conversation support')),

                'type' => 'Support',

                'age_hours' => $ageHours,

                'sla_hours' => $slaHours,

                'url' => $this->siteUrl('admin/support?conv=' . (int) ($row['id'] ?? 0)),

                'cta' => 'Ouvrir ticket',

            ];

        }



        foreach ($this->model->fetchCoursePriorityCandidates(8) as $row) {

            $ageHours = $this->hoursSince((string) ($row['submitted_at'] ?? $row['updated_at'] ?? ''));

            $score = $this->computePriorityScore(2, $ageHours, 48);

            $queue[] = [

                'score' => $score,

                'tone' => 'warning',

                'label' => 'Validation cours #' . (int) ($row['id'] ?? 0),

                'meta' => trim((string) ($row['title'] ?? 'Cours soumis')),

                'type' => 'Cours',

                'age_hours' => $ageHours,

                'sla_hours' => 48,

                'url' => $this->siteUrl('admin/courses'),

                'cta' => 'Valider cours',

            ];

        }



        foreach ($this->model->fetchWithdrawalPriorityCandidates(8) as $row) {

            $status = strtolower((string) ($row['status'] ?? 'requested'));

            $impact = $status === 'approved' ? 3 : 2;

            $slaHours = $status === 'approved' ? 12 : 24;

            $ageHours = $this->hoursSince((string) ($row['created_at'] ?? ''));

            $score = $this->computePriorityScore($impact, $ageHours, $slaHours);

            $queue[] = [

                'score' => $score,

                'tone' => $status === 'approved' ? 'danger' : 'info',

                'label' => 'Retrait #' . (int) ($row['id'] ?? 0),

                'meta' => (int) ($row['amount_xof'] ?? 0) . ' F CFA · ' . strtoupper($status),

                'type' => 'Paiement',

                'age_hours' => $ageHours,

                'sla_hours' => $slaHours,

                'url' => $this->siteUrl('admin/sales'),

                'cta' => 'Relancer paiement',

            ];

        }



        usort($queue, static function (array $a, array $b): int {

            $scoreCmp = (int) ($b['score'] ?? 0) <=> (int) ($a['score'] ?? 0);

            if ($scoreCmp !== 0) {

                return $scoreCmp;

            }

            return (int) ($b['age_hours'] ?? 0) <=> (int) ($a['age_hours'] ?? 0);

        });



        return array_slice($queue, 0, 10);

    }



    /**

     * @param array<string,mixed> $query

     * @return array{from:string,to:string,admin_id:int,module:string}

     */

    private function normalizeAuditFilters(array $query): array

    {

        $from = trim((string) ($query['audit_from'] ?? ''));

        $to = trim((string) ($query['audit_to'] ?? ''));

        $adminId = max(0, (int) ($query['audit_admin'] ?? 0));

        $module = strtolower(trim((string) ($query['audit_module'] ?? '')));



        if (!$this->isValidDate($from)) {

            $from = '';

        }

        if (!$this->isValidDate($to)) {

            $to = '';

        }

        if ($module !== '' && !preg_match('/^[a-z0-9_]+$/', $module)) {

            $module = '';

        }



        return [

            'from' => $from,

            'to' => $to,

            'admin_id' => $adminId,

            'module' => $module,

        ];

    }



    private function isValidDate(string $value): bool

    {

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {

            return false;

        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $dt instanceof \DateTimeImmutable && $dt->format('Y-m-d') === $value;

    }



    private function countAuditRowsToday(array $rows): int

    {

        $today = date('Y-m-d');

        $count = 0;

        foreach ($rows as $row) {

            $createdAt = (string) ($row['created_at'] ?? '');

            if ($createdAt !== '' && substr($createdAt, 0, 10) === $today) {

                $count++;

            }

        }

        return $count;

    }



    /**

     * @return array<int,array<string,mixed>>

     */

    private function buildPlatformHealthCards(int $recentNotifications): array
    {
        $storageFreeGb = $this->estimateStorageFreeGb();

        $mailTone = $recentNotifications === 0 ? 'warning' : 'success';
        $storageTone = $storageFreeGb < 2.0 ? 'danger' : ($storageFreeGb < 5.0 ? 'warning' : 'success');

        return [
            [
                'service' => 'Mail',
                'status' => $mailTone,
                'value' => (string) $recentNotifications . ' envois / 24h',
                'meta' => 'Flux de notifications email et popup',
                'icon' => 'mail',
            ],
            [
                'service' => 'Stockage',
                'status' => $storageTone,
                'value' => number_format($storageFreeGb, 1, '.', ' ') . ' Go libres',
                'meta' => 'Espace disque serveur',
                'icon' => 'folder',
            ],
        ];
    }

    private function estimateStorageFreeGb(): float

    {

        $root = dirname(__DIR__, 3);

        $bytes = @disk_free_space($root);

        if (!is_numeric($bytes) || (float) $bytes <= 0) {

            return 0.0;

        }

        return (float) $bytes / 1024 / 1024 / 1024;

    }



    private function hoursSince(string $dateTime): int

    {

        $ts = strtotime($dateTime);

        if ($ts === false || $ts <= 0) {

            return 0;

        }

        return max(0, (int) floor((time() - $ts) / 3600));

    }



    private function computePriorityScore(int $impact, int $ageHours, int $slaHours): int

    {

        $safeImpact = max(1, min(3, $impact));

        $safeAge = max(0, $ageHours);

        $safeSla = max(1, $slaHours);



        $impactScore = $safeImpact * 15;

        $ageScore = min(35, (int) round(($safeAge / $safeSla) * 30));

        $slaScore = $safeAge > $safeSla ? 20 : 0;



        return max(0, min(100, $impactScore + $ageScore + $slaScore));

    }

}



