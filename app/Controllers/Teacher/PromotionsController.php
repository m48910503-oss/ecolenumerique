<?php
declare(strict_types=1);

namespace App\Controllers\Teacher;

use App\Core\Controller;
use App\Models\SharedCompanySchemaModel;
use App\Models\SharedPromotionModel;
use App\Models\TeacherPromotionsModel;
use App\Services\Shared\CompanySchemaService;
use App\Services\Shared\PromotionsService as SharedPromotionsService;
use App\Services\Teacher\PromotionsService;

final class PromotionsController extends Controller
{
    private const WIZARD_SESSION_KEY = 'teacher_promo_offer_wizard';

    public function __invoke(): void
    {
        /** @var \PDO $pdo */
        $pdo = $this->pdo();

        $siteLogoUrl = $this->siteLogoUrl();
        $formMode = strtolower(trim((string) ($_GET['form'] ?? '')));
        if (!in_array($formMode, ['', 'promotion', 'coupon'], true)) {
            $formMode = '';
        }
        $pageMode = $formMode === 'coupon' ? 'coupons' : ($formMode === 'promotion' ? 'promotions' : 'all');

        $user = $this->requireAuth(true, $this->siteUrl('teacher/promotions'));
        $roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
        $isTeacher = in_array('teacher', $roles, true);
        if (!$isTeacher) {
            header('Location: ' . $this->siteUrl(''));
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->requireCsrf();
        }

        $service = new PromotionsService(
            new TeacherPromotionsModel($pdo),
            new SharedPromotionsService(
                new SharedPromotionModel($pdo),
                new CompanySchemaService(new SharedCompanySchemaModel($pdo))
            )
        );

        if ($formMode === '') {
            $result = $service->handle(
                $user,
                true,
                (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
                $_POST,
                'teacher/promotions',
                $pageMode,
                $_GET
            );

            if (!empty($result['redirectTo'])) {
                header('Location: ' . (string) $result['redirectTo']);
                exit;
            }

            $this->render('teacher/promotions', array_merge(
                [
                    'siteLogoUrl' => $siteLogoUrl,
                ],
                is_array($result['view'] ?? null) ? $result['view'] : []
            ));
            return;
        }

        $wizardStep = $this->resolveWizardStep((string) ($_GET['step'] ?? '1'));
        $wizardDraft = $this->loadWizardDraft($formMode);
        $wizardErrors = [];
        $baseView = [];

        if ($wizardStep > 1 && !$this->wizardHasBaseData($wizardDraft)) {
            header('Location: ' . $this->wizardStepUrl($formMode, 1));
            exit;
        }
        if ($wizardStep > 2 && !$this->wizardHasScheduleData($wizardDraft)) {
            header('Location: ' . $this->wizardStepUrl($formMode, 2));
            exit;
        }

        $isPost = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $shouldLoadBaseView = !$isPost || $wizardStep < 3;
        if ($shouldLoadBaseView) {
            $baseResult = $service->handle(
                $user,
                true,
                'GET',
                [],
                'teacher/promotions',
                $pageMode,
                $_GET
            );
            $baseView = is_array($baseResult['view'] ?? null) ? $baseResult['view'] : [];
        }

        if ($isPost) {
            if ($wizardStep === 1) {
                $validation = $this->validateWizardStepOne($_POST, $wizardDraft, $baseView);
                $wizardDraft = $validation['draft'];
                $wizardErrors = $validation['errors'];
                $this->saveWizardDraft($formMode, $wizardDraft);

                if ($wizardErrors === []) {
                    header('Location: ' . $this->wizardStepUrl($formMode, 2));
                    exit;
                }
            } elseif ($wizardStep === 2) {
                $validation = $this->validateWizardStepTwo($_POST, $wizardDraft, $baseView, $formMode);
                $wizardDraft = $validation['draft'];
                $wizardErrors = $validation['errors'];
                $this->saveWizardDraft($formMode, $wizardDraft);

                if ($wizardErrors === []) {
                    header('Location: ' . $this->wizardStepUrl($formMode, 3));
                    exit;
                }
            } else {
                if ((string) ($_POST['action'] ?? '') === 'create_promo') {
                    $finalPost = array_merge($wizardDraft, $_POST);
                    $finalPost['action'] = 'create_promo';
                    $finalPost['promo_mode'] = $formMode;
                    $finalPost['offer'] = $formMode;

                    $result = $service->handle(
                        $user,
                        true,
                        'POST',
                        $finalPost,
                        'teacher/promotions',
                        $pageMode,
                        $_GET
                    );

                    if (!empty($result['redirectTo'])) {
                        $this->clearWizardDraft($formMode);
                        header('Location: ' . $this->siteUrl('teacher/promotions'));
                        exit;
                    }

                    $baseView = is_array($result['view'] ?? null) ? $result['view'] : [];
                    $wizardErrors = array_values(is_array($baseView['errors'] ?? null) ? $baseView['errors'] : []);
                    $wizardDraft = $this->normalizeWizardDraft($finalPost);
                    $this->saveWizardDraft($formMode, $wizardDraft);
                } else {
                    $wizardErrors[] = 'Action de validation invalide.';
                }
            }
        }

        $renderData = array_merge(
            [
                'siteLogoUrl' => $siteLogoUrl,
                'formMode' => $formMode,
                'wizardStep' => $wizardStep,
                'wizardBaseUrl' => $this->wizardBaseUrl($formMode),
                'wizardStepUrl' => fn (int $step): string => $this->wizardStepUrl($formMode, $step),
                'wizardDraft' => $wizardDraft,
                'wizardErrors' => $wizardErrors,
            ],
            $baseView
        );
        $renderData['errors'] = $wizardErrors !== [] ? $wizardErrors : array_values(is_array($renderData['errors'] ?? null) ? $renderData['errors'] : []);
        if ($wizardErrors !== []) {
            $renderData['success'] = '';
        }

        $this->render('teacher/promotion_offer_form', $renderData);
    }

    private function resolveWizardStep(string $step): int
    {
        $step = strtolower(trim($step));
        if ($step === 'review') {
            return 3;
        }
        $stepInt = (int) $step;
        if ($stepInt < 1 || $stepInt > 3) {
            return 1;
        }
        return $stepInt;
    }

    private function wizardBaseUrl(string $formMode): string
    {
        return $this->siteUrl('teacher/promotions?form=' . rawurlencode($formMode));
    }

    private function wizardStepUrl(string $formMode, int $step): string
    {
        $step = max(1, min(3, $step));
        return $this->wizardBaseUrl($formMode) . '&step=' . $step;
    }

    private function wizardSessionDrafts(): array
    {
        $drafts = $this->session->get(self::WIZARD_SESSION_KEY, []);
        return is_array($drafts) ? $drafts : [];
    }

    private function loadWizardDraft(string $formMode): array
    {
        $drafts = $this->wizardSessionDrafts();
        $draft = is_array($drafts[$formMode] ?? null) ? $drafts[$formMode] : [];
        return $this->normalizeWizardDraft($draft);
    }

    private function saveWizardDraft(string $formMode, array $draft): void
    {
        $drafts = $this->wizardSessionDrafts();
        $drafts[$formMode] = $this->normalizeWizardDraft($draft);
        $this->session->set(self::WIZARD_SESSION_KEY, $drafts);
    }

    private function clearWizardDraft(string $formMode): void
    {
        $drafts = $this->wizardSessionDrafts();
        unset($drafts[$formMode]);
        if ($drafts === []) {
            $this->session->remove(self::WIZARD_SESSION_KEY);
            return;
        }
        $this->session->set(self::WIZARD_SESSION_KEY, $drafts);
    }

    private function normalizeWizardDraft(array $input): array
    {
        $draft = [
            'course_id' => '',
            'promo_type' => 'percent',
            'promo_amount' => '',
            'promo_percent' => '',
            'promo_schedule_mode' => 'duration',
            'promo_duration_days' => '',
            'promo_start_at' => '',
            'promo_end_at' => '',
            'promo_target_type' => 'public',
            'promo_targets' => '',
            'promo_company_id' => '',
            'promo_company_seats' => '',
            'coupon_code_mode' => 'auto',
            'coupon_code' => '',
        ];

        foreach (array_keys($draft) as $key) {
            if (!array_key_exists($key, $input)) {
                continue;
            }
            $draft[$key] = trim((string) $input[$key]);
        }

        if (!in_array($draft['promo_type'], ['amount', 'percent'], true)) {
            $draft['promo_type'] = 'percent';
        }
        if (!in_array($draft['promo_schedule_mode'], ['duration', 'custom'], true)) {
            $draft['promo_schedule_mode'] = 'duration';
        }
        if (!in_array($draft['promo_target_type'], ['public', 'individual', 'company'], true)) {
            $draft['promo_target_type'] = 'public';
        }
        if (!in_array($draft['coupon_code_mode'], ['auto', 'manual'], true)) {
            $draft['coupon_code_mode'] = 'auto';
        }

        return $draft;
    }

    private function wizardHasBaseData(array $draft): bool
    {
        $courseId = (int) ($draft['course_id'] ?? 0);
        $promoType = (string) ($draft['promo_type'] ?? '');
        if ($courseId < 1 || !in_array($promoType, ['amount', 'percent'], true)) {
            return false;
        }
        if ($promoType === 'amount') {
            return trim((string) ($draft['promo_amount'] ?? '')) !== '';
        }
        return trim((string) ($draft['promo_percent'] ?? '')) !== '';
    }

    private function wizardHasScheduleData(array $draft): bool
    {
        if (!$this->wizardHasBaseData($draft)) {
            return false;
        }

        $mode = (string) ($draft['promo_schedule_mode'] ?? 'duration');
        if ($mode === 'custom') {
            return trim((string) ($draft['promo_start_at'] ?? '')) !== '' && trim((string) ($draft['promo_end_at'] ?? '')) !== '';
        }

        return trim((string) ($draft['promo_duration_days'] ?? '')) !== '';
    }

    /**
     * @param array<int,mixed> $courses
     * @return array{errors:array<int,string>,draft:array<string,string>}
     */
    private function validateWizardStepOne(array $post, array $draft, array $baseView): array
    {
        $errors = [];
        $rawPromoType = strtolower(trim((string) ($post['promo_type'] ?? 'percent')));
        if (!in_array($rawPromoType, ['amount', 'percent'], true)) {
            $errors[] = 'Type de réduction invalide.';
        }

        $draft = $this->normalizeWizardDraft(array_merge($draft, $post));
        if (in_array($rawPromoType, ['amount', 'percent'], true)) {
            $draft['promo_type'] = $rawPromoType;
        }

        $courses = array_values(is_array($baseView['courses'] ?? null) ? $baseView['courses'] : []);
        $courseMap = $this->buildCourseMap($courses);

        $courseId = (int) ($draft['course_id'] ?? 0);
        if ($courseId < 1) {
            $errors[] = 'Sélectionnez un cours.';
        } elseif (!isset($courseMap[$courseId])) {
            $errors[] = 'Cours introuvable ou non publié.';
        }

        $promoType = in_array($rawPromoType, ['amount', 'percent'], true) ? $rawPromoType : (string) ($draft['promo_type'] ?? '');
        if ($courseId > 0 && isset($courseMap[$courseId])) {
            $basePrice = (int) ($courseMap[$courseId]['price_xof'] ?? 0);
            if ($promoType === 'amount') {
                $amountRaw = trim((string) ($draft['promo_amount'] ?? ''));
                if ($amountRaw === '') {
                    $errors[] = 'Nouveau prix obligatoire.';
                } elseif (!is_numeric($amountRaw)) {
                    $errors[] = 'Nouveau prix invalide.';
                } else {
                    $amount = (int) $amountRaw;
                    if ($amount < 0) {
                        $errors[] = 'Nouveau prix invalide.';
                    } elseif ($basePrice <= 0) {
                        $errors[] = 'Prix du cours invalide.';
                    } elseif ($amount >= $basePrice) {
                        $errors[] = 'Le nouveau prix doit être inférieur au prix actuel.';
                    }
                }
            } else {
                $percentRaw = str_replace(',', '.', trim((string) ($draft['promo_percent'] ?? '')));
                if ($percentRaw === '') {
                    $errors[] = 'Réduction obligatoire.';
                } elseif (!is_numeric($percentRaw)) {
                    $errors[] = 'Réduction invalide.';
                } else {
                    $percent = (float) $percentRaw;
                    if ($percent <= 0 || $percent > 100) {
                        $errors[] = 'Réduction invalide (1-100).';
                    } elseif ($basePrice <= 0) {
                        $errors[] = 'Prix du cours invalide.';
                    } elseif ((int) round($basePrice * (1 - ($percent / 100))) >= $basePrice) {
                        $errors[] = 'Réduction trop faible pour modifier le prix.';
                    }
                }
            }
        }

        return [
            'errors' => $errors,
            'draft' => $draft,
        ];
    }

    /**
     * @param array<int,mixed> $draft
     * @param array<int,mixed> $baseView
     * @return array{errors:array<int,string>,draft:array<string,string>}
     */
    private function validateWizardStepTwo(array $post, array $draft, array $baseView, string $formMode): array
    {
        $errors = [];
        $rawScheduleMode = strtolower(trim((string) ($post['promo_schedule_mode'] ?? 'duration')));
        if (!in_array($rawScheduleMode, ['duration', 'custom'], true)) {
            $errors[] = 'Mode de période invalide.';
        }
        $rawTargetType = strtolower(trim((string) ($post['promo_target_type'] ?? 'public')));
        if (!in_array($rawTargetType, ['public', 'individual', 'company'], true)) {
            $errors[] = 'Cible invalide.';
        }
        $rawCouponMode = strtolower(trim((string) ($post['coupon_code_mode'] ?? 'auto')));
        if ($formMode === 'coupon' && !in_array($rawCouponMode, ['auto', 'manual'], true)) {
            $errors[] = 'Mode de code coupon invalide.';
        }

        $draft = $this->normalizeWizardDraft(array_merge($draft, $post));
        if (in_array($rawScheduleMode, ['duration', 'custom'], true)) {
            $draft['promo_schedule_mode'] = $rawScheduleMode;
        }
        if (in_array($rawTargetType, ['public', 'individual', 'company'], true)) {
            $draft['promo_target_type'] = $rawTargetType;
        }
        if ($formMode === 'coupon' && in_array($rawCouponMode, ['auto', 'manual'], true)) {
            $draft['coupon_code_mode'] = $rawCouponMode;
        }

        $courses = array_values(is_array($baseView['courses'] ?? null) ? $baseView['courses'] : []);
        $courseMap = $this->buildCourseMap($courses);
        $courseId = (int) ($draft['course_id'] ?? 0);
        $courseRow = $courseMap[$courseId] ?? null;

        if ($courseId < 1 || $courseRow === null) {
            $errors[] = 'Choisissez un cours valide avant de poursuivre.';
        }

        $canTargetPromos = !empty($baseView['canTargetPromos']);
        $couponCodeSupported = !empty($baseView['couponCodeSupported']);
        $promoTargetCandidates = array_values(is_array($baseView['promoTargetCandidates'] ?? null) ? $baseView['promoTargetCandidates'] : []);
        $promoCompanyCandidates = array_values(is_array($baseView['promoCompanyCandidates'] ?? null) ? $baseView['promoCompanyCandidates'] : []);
        $targetEmailMap = $this->buildEmailMap($promoTargetCandidates);
        $companyMap = $this->buildCompanyMap($promoCompanyCandidates);

        if ($formMode === 'coupon' && !$couponCodeSupported) {
            $errors[] = 'Le module code promo n’est pas activé sur cette base.';
        }

        $scheduleMode = in_array($rawScheduleMode, ['duration', 'custom'], true)
            ? $rawScheduleMode
            : (string) ($draft['promo_schedule_mode'] ?? 'duration');
        if ($scheduleMode === 'custom') {
            $startRaw = trim((string) ($draft['promo_start_at'] ?? ''));
            $endRaw = trim((string) ($draft['promo_end_at'] ?? ''));
            if ($startRaw === '' || $endRaw === '') {
                $errors[] = 'Les dates de début et de fin sont obligatoires.';
            } else {
                $normalizedStart = $this->normalizeWizardDateTimeInput($startRaw, false);
                $normalizedEnd = $this->normalizeWizardDateTimeInput($endRaw, true);
                $startTs = $normalizedStart !== null ? strtotime($normalizedStart) : false;
                $endTs = $normalizedEnd !== null ? strtotime($normalizedEnd) : false;
                if ($normalizedStart === null || $normalizedEnd === null || $startTs === false || $endTs === false) {
                    $errors[] = 'Les dates de période sont invalides.';
                } elseif ($endTs < $startTs) {
                    $errors[] = 'La date de fin doit être postérieure à la date de début.';
                } else {
                    $draft['promo_start_at'] = $normalizedStart;
                    $draft['promo_end_at'] = $normalizedEnd;
                }
            }
        } else {
            $daysRaw = trim((string) ($draft['promo_duration_days'] ?? ''));
            if ($daysRaw === '') {
                $errors[] = 'La durée en jours est obligatoire.';
            } elseif (!is_numeric($daysRaw)) {
                $errors[] = 'La durée en jours est invalide.';
            } else {
                $days = (int) $daysRaw;
                if ($days < 1) {
                    $errors[] = 'La durée en jours est invalide.';
                }
            }
        }

        if ($formMode === 'coupon') {
            $couponMode = (string) ($draft['coupon_code_mode'] ?? 'auto');
            if (!in_array($couponMode, ['auto', 'manual'], true)) {
                $couponMode = 'auto';
            }
            $draft['coupon_code_mode'] = $couponMode;
            if ($couponMode === 'manual') {
                $couponCodeRaw = trim((string) ($draft['coupon_code'] ?? ''));
                if ($couponCodeRaw === '') {
                    $errors[] = 'Code coupon obligatoire en mode manuel.';
                } elseif (strlen($couponCodeRaw) < 3 || strlen($couponCodeRaw) > 32) {
                    $errors[] = 'Le code coupon doit contenir entre 3 et 32 caractères.';
                } elseif (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{1,30}[A-Za-z0-9])?$/', $couponCodeRaw)) {
                    $errors[] = 'Le code coupon doit contenir uniquement des lettres, chiffres et tirets, sans tiret au début ni à la fin.';
                } else {
                    $normalizedCode = SharedPromotionsService::normalizeCouponCode($couponCodeRaw);
                    if ($normalizedCode === '') {
                        $errors[] = 'Code coupon invalide.';
                    } else {
                        $draft['coupon_code'] = $normalizedCode;
                    }
                }
            } else {
                $draft['coupon_code'] = '';
            }
        }

        $targetType = in_array($rawTargetType, ['public', 'individual', 'company'], true)
            ? $rawTargetType
            : (string) ($draft['promo_target_type'] ?? 'public');
        if (!$canTargetPromos) {
            if ($targetType !== 'public') {
                $errors[] = 'Le ciblage avancé n’est pas disponible sur ce compte.';
            }
            $targetType = 'public';
        }

        if (!in_array($targetType, ['public', 'individual', 'company'], true)) {
            $errors[] = 'Cible invalide.';
            $targetType = 'public';
        }

        if ($targetType === 'individual') {
            $targetRaw = trim((string) ($draft['promo_targets'] ?? ''));
            if ($targetRaw === '') {
                $errors[] = 'Sélectionnez au moins un utilisateur.';
            } else {
                $targetEmails = $this->parseWizardTargetEmails($targetRaw);
                $invalidEmails = [];
                foreach ($targetEmails as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $invalidEmails[] = $email;
                    }
                }
                if ($invalidEmails !== []) {
                    $errors[] = 'Emails invalides: ' . implode(', ', $invalidEmails);
                } else {
                    $missing = array_diff($targetEmails, array_keys($targetEmailMap));
                    if ($missing !== []) {
                        $errors[] = 'Utilisateurs non éligibles: ' . implode(', ', $missing);
                    }
                }
            }
        } elseif ($targetType === 'company') {
            $companyId = (int) ($draft['promo_company_id'] ?? 0);
            $companySeatsRaw = trim((string) ($draft['promo_company_seats'] ?? ''));
            if ($companyId < 1 || !isset($companyMap[$companyId])) {
                $errors[] = 'Entreprise invalide.';
            }
            if ($companySeatsRaw === '') {
                $errors[] = 'Nombre de places obligatoire.';
            } elseif (!is_numeric($companySeatsRaw)) {
                $errors[] = 'Nombre de places invalide.';
            } else {
                $companySeats = (int) $companySeatsRaw;
                if ($companySeats < 1) {
                    $errors[] = 'Nombre de places invalide.';
                }
            }
        }

        $draft['promo_target_type'] = $targetType;

        return [
            'errors' => $errors,
            'draft' => $draft,
        ];
    }

    /**
     * @param array<int,mixed> $courses
     * @return array<int,array<string,mixed>>
     */
    private function buildCourseMap(array $courses): array
    {
        $map = [];
        foreach ($courses as $course) {
            $courseId = (int) ($course['id'] ?? 0);
            if ($courseId > 0) {
                $map[$courseId] = $course;
            }
        }
        return $map;
    }

    /**
     * @param array<int,array<string,mixed>> $candidates
     * @return array<string,int>
     */
    private function buildEmailMap(array $candidates): array
    {
        $map = [];
        foreach ($candidates as $candidate) {
            $email = strtolower(trim((string) ($candidate['email'] ?? '')));
            $id = (int) ($candidate['id'] ?? 0);
            if ($email !== '' && $id > 0) {
                $map[$email] = $id;
            }
        }
        return $map;
    }

    /**
     * @param array<int,array<string,mixed>> $companies
     * @return array<int,array<string,mixed>>
     */
    private function buildCompanyMap(array $companies): array
    {
        $map = [];
        foreach ($companies as $company) {
            $companyId = (int) ($company['id'] ?? 0);
            if ($companyId > 0) {
                $map[$companyId] = $company;
            }
        }
        return $map;
    }

    /**
     * @return array<int,string>
     */
    private function parseWizardTargetEmails(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($parts)) {
            return [];
        }

        $emails = [];
        foreach ($parts as $part) {
            $email = strtolower(trim((string) $part));
            if ($email !== '') {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }

    private function normalizeWizardDateTimeInput(?string $rawValue, bool $isEnd): ?string
    {
        $raw = trim((string) $rawValue);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            $raw .= $isEnd ? ' 23:59:59' : ' 00:00:00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $raw) === 1) {
            $raw = str_replace('T', ' ', $raw) . ':00';
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}$/', $raw) === 1) {
            $raw = str_replace('T', ' ', $raw);
        } else {
            return null;
        }

        $ts = strtotime($raw);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }
}
