<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;

use App\Models\AdminSalesModel;
use App\Services\Shared\PaymentOpsService;
use App\Services\Shared\PlatformSettingsService;
use App\Services\Shared\ReferralService;

final class SalesService extends Service
{
    private AdminSalesModel $model;
    private PaymentOpsService $paymentOps;
    private PlatformSettingsService $settings;
    private ReferralService $referrals;

    public function __construct(
        AdminSalesModel $model,
        PaymentOpsService $paymentOps,
        PlatformSettingsService $settings,
        ReferralService $referrals
    )
    {
        $this->model = $model;
        $this->paymentOps = $paymentOps;
        $this->settings = $settings;
        $this->referrals = $referrals;
    }

    /**
     * @param array<string,mixed> $adminUser
     * @param array<string,mixed> $post
     * @return array{
     *   redirectTo:?string,
     *   view:array<string,mixed>
     * }
     */
    public function handle(array $adminUser, string $method, array $post): array
    {
        $errors = [];
        $success = (string) $this->flashGet('success', '');

        $commissionPercent = 20.0;
        $minWithdrawal = 0.0;
        $teacherMinWithdrawal = 0.0;
        $referralMinWithdrawal = 0.0;
        $referralL1Rate = 5.0;
        $referralL2Rate = 2.0;

        $teacherWithdrawalMethodCatalog = $this->settings->teacherWithdrawalMethodCatalog();

        $teacherWithdrawalMethodsEnabled = array_keys($teacherWithdrawalMethodCatalog);

        try {
            if (!$this->model->ensureTeacherPayoutsTable()) {
                $errors[] = 'Table teacher_payouts manquante. Lancez les migrations.';
            }
            $commissionPercent = $this->settings->getFloat('commission_percent', 20.0);
            $legacyMin = $this->settings->getFloat('min_withdrawal_xof', 0.0);
            $teacherMinWithdrawal = $this->settings->getFloat('teacher_min_withdrawal_xof', $legacyMin);
            $referralMinWithdrawal = $this->settings->getFloat('referral_min_withdrawal_xof', $legacyMin);
            $minWithdrawal = $teacherMinWithdrawal;
            $referralL1Rate = $this->settings->getFloat('referral_l1_rate', 5.0);
            $referralL2Rate = $this->settings->getFloat('referral_l2_rate', 2.0);

            $teacherWithdrawalMethodsEnabled = $this->settings->teacherWithdrawalMethodsEnabled();
        } catch (\Throwable $e) {
            $errors[] = 'Erreur d’initialisation des tables.';
        }

        if ($method === 'POST') {
            $action = (string) ($post['action'] ?? '');

            if ($action === 'save_commission') {
                $raw = str_replace(',', '.', trim((string) ($post['commission_percent'] ?? '')));
                $val = is_numeric($raw) ? (float) $raw : -1;
                if ($val < 0 || $val > 100) {
                    $errors[] = 'Pourcentage invalide.';
                } else {
                    $commissionPercent = $val;
                    $this->settings->set('commission_percent', number_format($val, 2, '.', ''));
                    $success = 'Pourcentage mis à jour.';
                }
            } elseif ($action === 'save_withdrawal_minimums' || $action === 'save_min_withdrawal') {
                $rawTeacher = str_replace(',', '.', trim((string) ($post['teacher_min_withdrawal_xof'] ?? ($post['min_withdrawal_xof'] ?? ''))));
                $rawReferral = str_replace(',', '.', trim((string) ($post['referral_min_withdrawal_xof'] ?? (string) $referralMinWithdrawal)));
                $teacherVal = is_numeric($rawTeacher) ? (float) $rawTeacher : -1;
                $referralVal = is_numeric($rawReferral) ? (float) $rawReferral : -1;
                if ($teacherVal < 0) {
                    $errors[] = 'Montant minimum enseignant invalide.';
                }
                if ($referralVal < 0) {
                    $errors[] = 'Montant minimum parrainage invalide.';
                }
                if ($errors === []) {
                    $teacherMinWithdrawal = $teacherVal;
                    $referralMinWithdrawal = $referralVal;
                    $minWithdrawal = $teacherVal;
                    $this->settings->set('teacher_min_withdrawal_xof', number_format($teacherVal, 0, '.', ''));
                    $this->settings->set('referral_min_withdrawal_xof', number_format($referralVal, 0, '.', ''));
                    // Backward compatibility key.
                    $this->settings->set('min_withdrawal_xof', number_format($teacherVal, 0, '.', ''));
                    $success = 'Seuils de retrait mis à jour.';
                }
            } elseif ($action === 'update_withdrawal_status') {
                $withdrawalId = (int) ($post['withdrawal_id'] ?? 0);
                $targetStatus = strtolower(trim((string) ($post['target_status'] ?? '')));
                $payoutReference = trim((string) ($post['payout_reference'] ?? ''));
                $reason = trim((string) ($post['reason'] ?? ''));
                if ($withdrawalId < 1) {
                    $errors[] = 'Demande de retrait introuvable.';
                } elseif (!in_array($targetStatus, ['approved', 'rejected', 'paid'], true)) {
                    $errors[] = 'Statut de retrait invalide.';
                } else {
                    $withdrawal = $this->model->findWithdrawalById($withdrawalId);
                    if (!$withdrawal) {
                        $errors[] = 'Demande de retrait inexistante.';
                    } else {
                        $currentStatus = strtolower((string) ($withdrawal['status'] ?? 'requested'));
                        $allowedTransitions = [
                            'requested' => ['approved', 'rejected', 'paid'],
                            'approved' => ['paid', 'rejected'],
                            'paid' => [],
                            'rejected' => [],
                        ];
                        $allowed = $allowedTransitions[$currentStatus] ?? [];
                        if (!in_array($targetStatus, $allowed, true)) {
                            $errors[] = 'Transition de statut non autorisée.';
                        } elseif ($targetStatus === 'rejected' && $reason === '') {
                            $errors[] = 'Le motif est obligatoire pour un rejet.';
                        } else {
                            $changed = $this->model->updateWithdrawalStatus(
                                $withdrawalId,
                                $targetStatus,
                                (int) ($adminUser['id'] ?? 0),
                                $payoutReference,
                                $reason
                            );
                            if (!$changed) {
                                $errors[] = 'Mise à jour de la demande impossible.';
                            } else {
                                $withdrawType = (string) ($withdrawal['withdrawal_type'] ?? 'teacher_sales');
                                $isReferral = $withdrawType === 'referral';
                                $amount = (int) ($withdrawal['amount_xof'] ?? 0);
                                $notifTitle = match ($targetStatus) {
                                    'approved' => 'Retrait approuvé',
                                    'paid' => 'Retrait payé',
                                    default => 'Retrait rejeté',
                                };
                                $notifBody = match ($targetStatus) {
                                    'approved' => 'Votre demande de retrait de ' . (string) $amount . ' F CFA a été approuvée.',
                                    'paid' => 'Votre retrait de ' . (string) $amount . ' F CFA a été marqué comme payé.',
                                    default => 'Votre demande de retrait de ' . (string) $amount . ' F CFA a été rejetée.' . ($reason !== '' ? ' Motif : ' . $reason : ''),
                                };
                                $notifUrl = $isReferral ? $this->siteUrl('student/affiliation') : $this->siteUrl('teacher/withdrawals');
                                $this->model->insertNotification((int) ($withdrawal['user_id'] ?? 0), $notifTitle, $notifBody, $notifUrl);
                                $success = 'Demande de retrait mise à jour.';
                            }
                        }
                    }
                }
            } elseif ($action === 'save_referral_rates') {
                $rawL1 = str_replace(',', '.', trim((string) ($post['referral_l1_rate'] ?? '')));
                $rawL2 = str_replace(',', '.', trim((string) ($post['referral_l2_rate'] ?? '')));
                $l1Rate = is_numeric($rawL1) ? (float) $rawL1 : -1;
                $l2Rate = is_numeric($rawL2) ? (float) $rawL2 : -1;
                if ($l1Rate < 0 || $l1Rate > 100) {
                    $errors[] = 'Taux L1 invalide (0-100).';
                }
                if ($l2Rate < 0 || $l2Rate > 100) {
                    $errors[] = 'Taux L2 invalide (0-100).';
                }
                if ($errors === []) {
                    $referralL1Rate = $l1Rate;
                    $referralL2Rate = $l2Rate;
                    $this->settings->set('referral_l1_rate', number_format($l1Rate, 2, '.', ''));
                    $this->settings->set('referral_l2_rate', number_format($l2Rate, 2, '.', ''));
                    $success = 'Taux de parrainage mis à jour.';
                }
            } elseif ($action === 'save_teacher_withdrawal_methods') {

                $postedMethods = $post['teacher_withdrawal_methods'] ?? [];

                $normalizedMethods = [];

                if (is_array($postedMethods)) {

                    foreach ($postedMethods as $postedMethod) {

                        $methodKey = strtolower(trim((string) $postedMethod));

                        if ($methodKey === '' || !isset($teacherWithdrawalMethodCatalog[$methodKey]) || in_array($methodKey, $normalizedMethods, true)) {

                            continue;

                        }

                        $normalizedMethods[] = $methodKey;

                    }

                }

                if ($normalizedMethods === []) {

                    $errors[] = 'Activez au moins une méthode de retrait.';

                } else {

                    $teacherWithdrawalMethodsEnabled = $normalizedMethods;

                    $this->settings->set('teacher_withdrawal_methods', implode(',', $normalizedMethods));

                    $success = 'Méthodes de retrait mises à jour.';

                }

            } elseif ($action === 'simulate_payout') {
                $teacherId = (int) ($post['teacher_id'] ?? 0);
                $totalSales = (int) ($post['total_sales_xof'] ?? 0);
                $platformFee = (int) ($post['platform_fee_xof'] ?? 0);
                $teacherAmount = (int) ($post['teacher_amount_xof'] ?? 0);
                $percent = (float) ($post['commission_percent'] ?? $commissionPercent);
                if ($teacherId < 1 || $totalSales < 0 || $platformFee < 0 || $teacherAmount < 0) {
                    $errors[] = 'Données de paiement invalides.';
                } else {
                    $this->model->insertTeacherPayout(
                        $teacherId,
                        $totalSales,
                        $platformFee,
                        $teacherAmount,
                        $percent,
                        (int) ($adminUser['id'] ?? 0)
                    );
                    $success = 'Paiement simulé enregistré.';
                }
            } elseif ($action === 'mark_order_paid') {
                $orderId = (int) ($post['order_id'] ?? 0);
                $paymentProvider = (string) ($post['payment_provider'] ?? '');
                $paymentRef = (string) ($post['payment_ref'] ?? '');
                if ($orderId < 1) {
                    $errors[] = 'Commande introuvable.';
                } else {
                    $ok = $this->paymentOps->finalizeOrderPayment($orderId);
                    if ($ok) {
                        if ($paymentProvider !== '' && $paymentRef !== '') {
                            $this->paymentOps->upsert($orderId, $paymentProvider, $paymentRef, 'paid', [
                                'manual' => true,
                                'admin_id' => (int) ($adminUser['id'] ?? 0),
                            ]);
                        } else {
                            $this->model->markPaymentsPaidByOrderId($orderId);
                        }
                        $success = 'Commande marquée comme payée.';
                    } else {
                        $errors[] = 'Erreur lors du paiement.';
                    }
                }
            } elseif ($action === 'mark_paid') {
                $payoutId = (int) ($post['payout_id'] ?? 0);
                if ($payoutId < 1) {
                    $errors[] = 'Paiement introuvable.';
                } else {
                    $this->model->markTeacherPayoutPaid($payoutId);
                    $success = 'Paiement marqué comme payé.';
                }
            }
        }

        if ($method === 'POST' && $errors === [] && $success !== '') {
            $this->flashSet('success', $success);
            return [
                'redirectTo' => $this->siteUrl('admin/sales'),
                'view' => [],
            ];
        }

        $sales = [];
        $pendingOrders = [];
        try {
            $sales = $this->model->fetchSalesPaidApproved();
        } catch (\Throwable $e) {
            $sales = [];
        }
        try {
            $pendingOrders = $this->model->fetchPendingOrders();
        } catch (\Throwable $e) {
            $pendingOrders = [];
        }

        $referralByItem = [];
        $referralByTeacher = [];
        $referralByCourse = [];
        $totalReferral = 0;
        try {
            $this->referrals->ensureReferralCommissionsTable();
            foreach ($this->model->fetchReferralCommissionsByItemTeacherCourse() as $row) {
                $itemId = (int) ($row['order_item_id'] ?? 0);
                $teacherId = (int) ($row['teacher_id'] ?? 0);
                $courseId = (int) ($row['course_id'] ?? 0);
                $amount = (int) ($row['total_amount'] ?? 0);
                if ($itemId > 0 && $amount > 0) {
                    $referralByItem[$itemId] = ($referralByItem[$itemId] ?? 0) + $amount;
                    $totalReferral += $amount;
                }
                if ($teacherId > 0 && $amount > 0) {
                    $referralByTeacher[$teacherId] = ($referralByTeacher[$teacherId] ?? 0) + $amount;
                }
                if ($courseId > 0 && $amount > 0) {
                    $referralByCourse[$courseId] = ($referralByCourse[$courseId] ?? 0) + $amount;
                }
            }
        } catch (\Throwable $e) {
            $referralByItem = [];
            $referralByTeacher = [];
            $referralByCourse = [];
            $totalReferral = 0;
        }

        $totalGross = 0;
        $totalPlatform = 0;
        $totalTeacher = 0;
        $teacherTotals = [];
        foreach ($sales as $row) {
            $qty = (int) ($row['quantity'] ?? 1);
            $unit = (int) ($row['unit_price_xof'] ?? 0);
            $gross = $qty * $unit;
            $platformFee = (int) round($gross * $commissionPercent / 100);
            $itemId = (int) ($row['item_id'] ?? 0);
            $referralFee = (int) ($referralByItem[$itemId] ?? 0);
            $teacherAmount = $gross - $platformFee - $referralFee;
            if ($teacherAmount < 0) {
                $teacherAmount = 0;
            }

            $totalGross += $gross;
            $totalPlatform += $platformFee;
            $totalTeacher += $teacherAmount;

            $teacherId = (int) ($row['teacher_id'] ?? 0);
            if (!isset($teacherTotals[$teacherId])) {
                $teacherTotals[$teacherId] = [
                    'teacher_id' => $teacherId,
                    'teacher_name' => trim((string) ($row['teacher_first'] ?? '') . ' ' . (string) ($row['teacher_last'] ?? '')),
                    'total_sales' => 0,
                    'platform_fee' => 0,
                    'teacher_amount' => 0,
                    'sales_count' => 0,
                ];
            }
            $teacherTotals[$teacherId]['total_sales'] += $gross;
            $teacherTotals[$teacherId]['platform_fee'] += $platformFee;
            $teacherTotals[$teacherId]['teacher_amount'] += $teacherAmount;
            $teacherTotals[$teacherId]['sales_count'] += 1;
        }

        $paidTotals = [];
        try {
            foreach ($this->model->fetchPaidWithdrawalTotalsByTeacher() as $row) {
                $paidTotals[(int) ($row['teacher_id'] ?? 0)] = (int) ($row['total_paid'] ?? 0);
            }
        } catch (\Throwable $e) {
            $paidTotals = [];
        }
        try {
            foreach ($this->model->fetchPaidTeacherPayoutTotalsByTeacher() as $row) {
                $teacherId = (int) ($row['teacher_id'] ?? 0);
                $paidTotals[$teacherId] = ($paidTotals[$teacherId] ?? 0) + (int) ($row['total_paid'] ?? 0);
            }
        } catch (\Throwable $e) {
            // Keep already computed values.
        }

        $payouts = [];
        $withdrawals = [];
        $teacherCourses = [];
        try {
            $payouts = $this->model->fetchRecentPayouts();
        } catch (\Throwable $e) {
            $payouts = [];
        }
        try {
            $withdrawals = $this->model->fetchRecentWithdrawals();
        } catch (\Throwable $e) {
            $withdrawals = [];
        }
        try {
            foreach ($this->model->fetchTeacherCourseSalesRows() as $row) {
                $teacherId = (int) ($row['teacher_id'] ?? 0);
                $teacherName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                if (!isset($teacherCourses[$teacherId])) {
                    $teacherCourses[$teacherId] = [
                        'teacher_name' => $teacherName !== '' ? $teacherName : 'Enseignant',
                        'total_gross' => 0,
                        'total_platform' => 0,
                        'total_teacher' => 0,
                        'courses' => [],
                    ];
                }

                $courseId = (int) ($row['course_id'] ?? 0);
                $gross = (int) ($row['gross_amount'] ?? 0);
                $platformFee = (int) round($gross * $commissionPercent / 100);
                $referralFee = (int) ($referralByCourse[$courseId] ?? 0);
                $teacherAmount = $gross - $platformFee - $referralFee;
                if ($teacherAmount < 0) {
                    $teacherAmount = 0;
                }

                $teacherCourses[$teacherId]['total_gross'] += $gross;
                $teacherCourses[$teacherId]['total_platform'] += $platformFee;
                $teacherCourses[$teacherId]['total_teacher'] += $teacherAmount;
                $teacherCourses[$teacherId]['courses'][] = [
                    'course_id' => $courseId,
                    'course_title' => (string) ($row['course_title'] ?? ''),
                    'gross' => $gross,
                    'platform_fee' => $platformFee,
                    'referral_fee' => $referralFee,
                    'teacher_amount' => $teacherAmount,
                    'sales_count' => (int) ($row['sales_count'] ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            $teacherCourses = [];
        }

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'success' => $success,
                'commissionPercent' => $commissionPercent,
                'minWithdrawal' => $minWithdrawal,
                'teacherMinWithdrawal' => $teacherMinWithdrawal,
                'referralMinWithdrawal' => $referralMinWithdrawal,
                'referralL1Rate' => $referralL1Rate,
                'referralL2Rate' => $referralL2Rate,

                'teacherWithdrawalMethodCatalog' => $teacherWithdrawalMethodCatalog,

                'teacherWithdrawalMethodsEnabled' => $teacherWithdrawalMethodsEnabled,
                'sales' => $sales,
                'pendingOrders' => $pendingOrders,
                'referralByItem' => $referralByItem,
                'referralByTeacher' => $referralByTeacher,
                'referralByCourse' => $referralByCourse,
                'totalReferral' => $totalReferral,
                'totalGross' => $totalGross,
                'totalPlatform' => $totalPlatform,
                'totalTeacher' => $totalTeacher,
                'teacherTotals' => $teacherTotals,
                'paidTotals' => $paidTotals,
                'payouts' => $payouts,
                'withdrawals' => $withdrawals,
                'teacherCourses' => $teacherCourses,
            ],
        ];
    }
}

