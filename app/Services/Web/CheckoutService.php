<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\WebCheckoutModel;
use App\Models\StudentDashboardModel;
use App\Models\TeacherWithdrawalsModel;
use App\Models\ReferralWalletModel;
use App\Services\Shared\FedapayService;
use App\Services\Shared\PaymentOpsService;
use App\Services\Shared\PromotionsService;

final class CheckoutService extends Service
{
    private WebCheckoutModel $courses;
    private PaymentOpsService $paymentOps;
    private FedapayService $fedapay;
    private PromotionsService $promotions;
    private StudentDashboardModel $students;
    private TeacherWithdrawalsModel $withdrawals;
    private ReferralWalletModel $wallet;

    public function __construct(
        WebCheckoutModel $courses,
        PaymentOpsService $paymentOps,
        FedapayService $fedapay,
        PromotionsService $promotions,
        StudentDashboardModel $students,
        TeacherWithdrawalsModel $withdrawals,
        ReferralWalletModel $wallet
    )
    {
        $this->courses = $courses;
        $this->paymentOps = $paymentOps;
        $this->fedapay = $fedapay;
        $this->promotions = $promotions;
        $this->students = $students;
        $this->withdrawals = $withdrawals;
        $this->wallet = $wallet;
    }

    public function handle(array $user, string $requestMethod, string $requestUri, array $query, array $post): array
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (!isset($_SESSION['cart_meta']) || !is_array($_SESSION['cart_meta'])) {
            $_SESSION['cart_meta'] = [];
        }
        if (!isset($_SESSION['cart_coupon_code']) || !is_string($_SESSION['cart_coupon_code'])) {
            $_SESSION['cart_coupon_code'] = '';
        }
        if (!isset($_SESSION['cart_company_seats']) || !is_array($_SESSION['cart_company_seats'])) {
            $_SESSION['cart_company_seats'] = [];
        }

        $errors = [];
        $warnings = [];
        $success = (string) $this->flashGet('success', '');
        $checkoutCompleted = (bool) $this->flashGet('checkout_completed', false);
        $flashErrors = $this->flashGet('errors', []);
        if (is_array($flashErrors) && $flashErrors !== []) {
            $errors = array_merge($errors, $flashErrors);
        }

        $availableMethods = $this->paymentOps->methodsAvailable();
        $defaultMethod = $availableMethods[0] ?? '';
        $selectedMethod = $defaultMethod;
        if ($selectedMethod !== '' && !in_array($selectedMethod, $availableMethods, true)) {
            $selectedMethod = $defaultMethod;
        }
        $fedapayCheckout = null;
        $isPost = strtoupper($requestMethod) === 'POST';
        $checkoutAction = $isPost ? strtolower(trim((string) ($post['checkout_action'] ?? 'pay'))) : '';
        if (!in_array($checkoutAction, ['pay', 'apply_coupon', 'clear_coupon', 'refresh'], true)) {
            $checkoutAction = 'pay';
        }
        $postedCouponRaw = $isPost ? trim((string) ($post['coupon_code'] ?? '')) : '';
        $postedCouponCode = $isPost ? PromotionsService::normalizeCouponCode($postedCouponRaw) : '';
        $postedCouponFormatValid = !$isPost || $this->isCouponFormatValid($postedCouponRaw);
        $couponCodeInput = PromotionsService::normalizeCouponCode((string) ($_SESSION['cart_coupon_code'] ?? ''));

        $companyContext = null;
        try {
            $companyContext = $this->courses->findActiveCompanyAdminContext((int) ($user['id'] ?? 0));
        } catch (\Throwable $e) {
            $companyContext = null;
        }
        $hasCompanyContext = is_array($companyContext) && (int) ($companyContext['company_id'] ?? 0) > 0;
        if (!array_key_exists('checkout_enterprise_mode', $_SESSION)) {
            $_SESSION['checkout_enterprise_mode'] = $hasCompanyContext ? 1 : 0;
        }
        if (!$hasCompanyContext) {
            $_SESSION['checkout_enterprise_mode'] = 0;
        }
        if ($isPost) {
            $postedEnterpriseMode = trim((string) ($post['enterprise_mode'] ?? '0')) === '1';
            $_SESSION['checkout_enterprise_mode'] = ($hasCompanyContext && $postedEnterpriseMode) ? 1 : 0;
        }
        $enterpriseCheckout = $hasCompanyContext && !empty($_SESSION['checkout_enterprise_mode']);
        $companyId = $enterpriseCheckout ? (int) ($companyContext['company_id'] ?? 0) : 0;

        $addId = isset($query['add']) ? (int) $query['add'] : (isset($query['course']) ? (int) $query['course'] : 0);
        if ($addId > 0) {
            if (!in_array($addId, $_SESSION['cart'], true)) {
                $_SESSION['cart'][] = $addId;
            }
            $originBlock = $this->normalizeCatalogBlockKey((string) ($query['block'] ?? ''));
            if ($originBlock !== '') {
                $_SESSION['cart_meta'][(string) $addId] = [
                    'origin_block' => $originBlock,
                    'updated_at' => time(),
                ];
            }
            return ['redirect' => $this->siteUrl('checkout')];
        }

        $removeId = isset($query['remove']) ? (int) $query['remove'] : 0;
        if ($removeId > 0 && in_array($removeId, $_SESSION['cart'], true)) {
            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], static fn($id): bool => (int) $id !== $removeId));
            unset($_SESSION['cart_meta'][(string) $removeId]);
            unset($_SESSION['cart_company_seats'][(string) $removeId]);
            return ['redirect' => $this->siteUrl('checkout')];
        }

        if ($isPost) {
            $selectedMethod = trim((string) ($post['payment_method'] ?? $defaultMethod));
            if ($selectedMethod === '') {
                $selectedMethod = $defaultMethod;
            }

            if ($checkoutAction === 'clear_coupon') {
                $_SESSION['cart_coupon_code'] = '';
                $couponCodeInput = '';
                if ($success === '') {
                    $success = "Coupon retir\u{00e9} du panier.";
                }
            } elseif ($checkoutAction === 'apply_coupon') {
                $couponCodeInput = $postedCouponCode;
                if ($postedCouponRaw === '') {
                    $errors[] = 'Entrez un code coupon.';
                } elseif (!$postedCouponFormatValid) {
                    $_SESSION['cart_coupon_code'] = '';
                    $errors[] = "Format de code coupon invalide. Utilisez uniquement des lettres, chiffres et tirets.";
                } else {
                    $_SESSION['cart_coupon_code'] = $postedCouponCode;
                }
            } elseif ($checkoutAction === 'pay') {
                if (array_key_exists('coupon_code', $post)) {
                    if ($postedCouponRaw !== '' && !$postedCouponFormatValid) {
                        $warnings[] = "Format de code coupon invalide : le code a \u{00e9}t\u{00e9} ignor\u{00e9}.";
                        $_SESSION['cart_coupon_code'] = '';
                        $couponCodeInput = '';
                    } else {
                        $_SESSION['cart_coupon_code'] = $postedCouponCode;
                        $couponCodeInput = $postedCouponCode;
                    }
                }
            } elseif ($checkoutAction === 'refresh' && $postedCouponRaw !== '') {
                $couponCodeInput = $postedCouponCode !== '' ? $postedCouponCode : $couponCodeInput;
            }
        }

        $activeCouponCode = PromotionsService::normalizeCouponCode((string) ($_SESSION['cart_coupon_code'] ?? ''));
        if ($couponCodeInput === '') {
            $couponCodeInput = $activeCouponCode;
        }

        $cartCourseIds = array_map('intval', $_SESSION['cart']);
        $courses = [];
        if ($cartCourseIds !== []) {
            $this->promotions->ensureCoursesReferralSettingsColumns();
            $this->promotions->ensureCoursesPromoColumns();

            $courses = $this->courses->findApprovedCoursesByIds($cartCourseIds);
            $this->normalizeCheckoutCourseMedia($courses);
            $foundIds = array_map(static fn(array $row): int => (int) $row['id'], $courses);

            $missing = array_diff($cartCourseIds, $foundIds);
            if ($missing !== []) {
                $_SESSION['cart'] = $foundIds;
                foreach ($missing as $missingId) {
                    unset($_SESSION['cart_meta'][(string) ((int) $missingId)]);
                    unset($_SESSION['cart_company_seats'][(string) ((int) $missingId)]);
                }
                $cartCourseIds = $foundIds;
                if ($courses === []) {
                    $errors[] = "Les cours de votre panier ne sont plus disponibles (non approuv\u{00e9}s ou supprim\u{00e9}s).";
                } else {
                    $errors[] = "Certains cours ne sont plus disponibles et ont \u{00e9}t\u{00e9} retir\u{00e9}s du panier.";
                }
            }
        }

        if ($courses !== [] && !$enterpriseCheckout) {
            $courseIds = array_map(static fn(array $row): int => (int) $row['id'], $courses);
            $already = $this->courses->findActiveEnrollmentCourseIds((int) $user['id'], $courseIds);
            if ($already !== []) {
                $courses = array_values(array_filter(
                    $courses,
                    static fn(array $course): bool => !in_array((int) $course['id'], $already, true)
                ));
                foreach ($already as $ownedId) {
                    unset($_SESSION['cart_meta'][(string) ((int) $ownedId)]);
                }
                $foundIds = array_map(static fn(array $row): int => (int) $row['id'], $courses);
                $_SESSION['cart'] = $foundIds;
                $cartCourseIds = $foundIds;
                $warnings[] = "Certains cours d\u{00e9}j\u{00e0} achet\u{00e9}s ont \u{00e9}t\u{00e9} retir\u{00e9}s du panier.";
            }
        }

        $couponApplied = null;
        if ($courses !== []) {
            $couponApplied = $this->hydrateCheckoutCourses(
                $courses,
                (int) ($user['id'] ?? 0),
                $activeCouponCode,
                $warnings,
                true
            );
            if ($activeCouponCode !== '' && $couponApplied === null) {
                $_SESSION['cart_coupon_code'] = '';
                $activeCouponCode = '';
            } elseif (is_array($couponApplied)) {
                $activeCouponCode = (string) ($couponApplied['coupon_code'] ?? $activeCouponCode);
                $_SESSION['cart_coupon_code'] = $activeCouponCode;
            }
        }

        if ($courses !== []) {
            $postedSeatQty = [];
            if ($isPost && isset($post['seat_qty']) && is_array($post['seat_qty'])) {
                $postedSeatQty = $post['seat_qty'];
            }
            $this->applyEnterpriseSeatContextToCourses(
                $courses,
                $cartCourseIds,
                $enterpriseCheckout,
                $companyId,
                $postedSeatQty,
                $checkoutAction === 'pay',
                $errors,
                $warnings
            );
        }

        if ($checkoutAction === 'apply_coupon' && $postedCouponCode !== '' && $errors === []) {
            if (is_array($couponApplied) && !empty($couponApplied['applied'])) {
                $courseTitle = (string) ($couponApplied['course_title'] ?? '');
                $success = "Coupon appliqu\u{00e9}";
                if ($courseTitle !== '') {
                    $success .= ' sur "' . $courseTitle . '"';
                }
                $success .= '.';
            }
        }

        $cartTotalPreview = $courses !== [] ? $this->cartTotal($courses) : 0;
        $walletInfo = $this->buildReferralWallet((int) ($user['id'] ?? 0));
        $referralWalletAvailable = (int) ($walletInfo['available'] ?? 0);
        $referralWalletUse = $isPost && !empty($post['use_referral_wallet']) && !$enterpriseCheckout;
        $postedWalletAmount = $isPost ? (int) ($post['referral_wallet_amount'] ?? 0) : 0;
        $walletMax = $referralWalletAvailable > 0 ? min($referralWalletAvailable, $cartTotalPreview) : 0;
        $referralWalletAmountInput = $postedWalletAmount > 0 ? $postedWalletAmount : $walletMax;

        if ($isPost && $checkoutAction === 'pay') {
            if ($courses === []) {
                $errors[] = 'Votre sélection de formation est vide.';
            }
            if (empty($post['terms'])) {
                $errors[] = "Vous devez accepter les conditions d'achat avant de finaliser l'inscription.";
            }

            if ($errors === []) {
                if ($enterpriseCheckout) {
                    $coursesToBuy = $courses;
                } else {
                    $courseIds = array_map(static fn(array $row): int => (int) $row['id'], $courses);
                    $already = $this->courses->findActiveEnrollmentCourseIds((int) $user['id'], $courseIds);
                    $coursesToBuy = array_values(array_filter(
                        $courses,
                        static fn(array $course): bool => !in_array((int) $course['id'], $already, true)
                    ));
                }

                if ($coursesToBuy === []) {
                    $errors[] = $enterpriseCheckout
                        ? 'Votre sélection entreprise est vide.'
                        : "Vous \u{00ea}tes d\u{00e9}j\u{00e0} inscrit \u{00e0} ces cours.";
                } else {
                    $couponAppliedForBuy = $this->hydrateCheckoutCourses(
                        $coursesToBuy,
                        (int) ($user['id'] ?? 0),
                        $activeCouponCode,
                        $warnings,
                        false
                    );
                    if ($activeCouponCode !== '' && $couponAppliedForBuy === null) {
                        $_SESSION['cart_coupon_code'] = '';
                        $activeCouponCode = '';
                    }

                    $postedSeatQty = isset($post['seat_qty']) && is_array($post['seat_qty']) ? $post['seat_qty'] : [];
                    $this->applyEnterpriseSeatContextToCourses(
                        $coursesToBuy,
                        array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $coursesToBuy),
                        $enterpriseCheckout,
                        $companyId,
                        $postedSeatQty,
                        true,
                        $errors,
                        $warnings
                    );
                    if ($errors !== []) {
                        $courses = $coursesToBuy;
                    }

                    if ($errors === []) {
                        $finalTotal = $this->cartTotal($coursesToBuy);
                        $walletToUse = $this->resolveReferralWalletAmount(
                            $referralWalletUse,
                            $postedWalletAmount,
                            $referralWalletAvailable,
                            $finalTotal,
                            $selectedMethod,
                            $errors
                        );
                        $payableTotal = max(0, $finalTotal - $walletToUse);
                        if ($payableTotal > 0 && ($selectedMethod === '' || !$this->paymentOps->methodEnabled($selectedMethod))) {
                            $errors[] = "M\u{00e9}thode de paiement indisponible.";
                        }

                        if ($errors === []) {
                            $orderProvider = $payableTotal <= 0
                                ? ($walletToUse > 0 ? 'referral' : 'free')
                                : ($selectedMethod !== '' ? $selectedMethod : 'fedapay');

                            $orderInfo = $this->paymentOps->createPendingOrder((int) $user['id'], $coursesToBuy, $orderProvider);
                            if (!$orderInfo) {
                                $errors[] = "Erreur lors de la cr\u{00e9}ation de la commande.";
                            } else {
                                $orderId = (int) ($orderInfo['order_id'] ?? 0);
                                $orderRef = (string) ($orderInfo['order_ref'] ?? '');
                                if ($walletToUse > 0) {
                                    if (!$this->wallet->insertRedemption((int) $user['id'], $orderId, $walletToUse, 'reserved')) {
                                        $this->paymentOps->updateOrderStatus($orderId, 'failed');
                                        $errors[] = 'Module solde parrainage indisponible. Appliquez database/schema_v58.sql.';
                                    }
                                }

                                if ($errors === []) {
                                    if ($payableTotal <= 0) {
                                        $finalized = $this->paymentOps->finalizeOrderPayment($orderId);
                                        if ($finalized) {
                                            $_SESSION['cart'] = [];
                                            $_SESSION['cart_meta'] = [];
                                            $_SESSION['cart_coupon_code'] = '';
                                            $_SESSION['cart_company_seats'] = [];
                                            $this->flashSet('checkout_completed', 1);
                                            if ($enterpriseCheckout) {
                                                $this->flashSet('success', "Commande entreprise valid\u{00e9}e : les places sont disponibles dans le dashboard entreprise.");
                                            } elseif ($walletToUse > 0) {
                                                $this->flashSet('success', "Commande valid\u{00e9}e : vos gains de parrainage ont \u{00e9}t\u{00e9} utilis\u{00e9}s.");
                                            } else {
                                                $this->flashSet('success', "Inscription valid\u{00e9}e : vos cours gratuits ont \u{00e9}t\u{00e9} ajout\u{00e9}s \u{00e0} votre espace.");
                                            }
                                            return ['redirect' => $this->siteUrl('checkout')];
                                        }
                                        $this->wallet->updateRedemptionStatusByOrder($orderId, 'cancelled');
                                        $errors[] = 'Erreur lors de la validation de la commande.';
                                    } elseif ($selectedMethod === 'fedapay') {
                                        if ($payableTotal < 100) {
                                            $this->paymentOps->updateOrderStatus($orderId, 'failed');
                                            $errors[] = 'Le montant minimum FedaPay est 100 F CFA.';
                                        } else {
                                            $payload = [
                                                'description' => 'Commande ' . $orderRef,
                                                'amount' => $payableTotal,
                                                'currency' => ['iso' => 'XOF'],
                                                'callback_url' => $this->absUrl('payment_return'),
                                                'customer' => [
                                                    'firstname' => (string) ($user['first_name'] ?? ''),
                                                    'lastname' => (string) ($user['last_name'] ?? ''),
                                                    'email' => (string) ($user['email'] ?? ''),
                                                ],
                                            ];
                                            $rawPhone = trim((string) ($user['phone'] ?? ''));
                                            if ($rawPhone !== '') {
                                                $payload['customer']['phone_number'] = [
                                                    'number' => preg_replace('/\s+/', '', $rawPhone),
                                                    'country' => 'bj',
                                                ];
                                            }

                                            $response = $this->fedapay->createTransaction($payload);
                                            if (empty($response['ok'])) {
                                                $this->paymentOps->updateOrderStatus($orderId, 'failed');
                                                if (function_exists('app_log_error')) {
                                                    \app_log_error('fedapay.checkout', 'Creation de transaction FedaPay echouee.', [
                                                        'order_id' => $orderId,
                                                        'order_ref' => $orderRef,
                                                        'user_id' => (int) ($user['id'] ?? 0),
                                                        'amount' => $payableTotal,
                                                        'response' => $response,
                                                    ]);
                                                }
                                                $errors[] = "Erreur lors de la cr\u{00e9}ation du paiement FedaPay.";
                                                $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
                                                if ($host === '' || stripos($host, 'localhost') !== false || $host === '127.0.0.1') {
                                                    $errors[] = "D\u{00e9}tail : " . json_encode($response);
                                                    $errors[] = "Montant envoy\u{00e9} : " . $payableTotal . ' F CFA';
                                                }
                                            } else {
                                                $transaction = $this->fedapay->extractTransaction((array) ($response['data'] ?? []));
                                                $transactionId = (int) ($transaction['id'] ?? 0);
                                                if ($transactionId < 1) {
                                                    $this->paymentOps->updateOrderStatus($orderId, 'failed');
                                                    if (function_exists('app_log_error')) {
                                                        \app_log_error('fedapay.checkout', 'Reponse FedaPay sans identifiant de transaction.', [
                                                            'order_id' => $orderId,
                                                            'order_ref' => $orderRef,
                                                            'user_id' => (int) ($user['id'] ?? 0),
                                                            'amount' => $payableTotal,
                                                            'response' => $response,
                                                        ]);
                                                    }
                                                    $errors[] = "R\u{00e9}ponse FedaPay invalide.";
                                                } else {
                                                    $paymentReference = (string) $transactionId;
                                                    $this->paymentOps->upsert($orderId, 'fedapay', $paymentReference, 'pending', [
                                                        'transaction' => $transaction,
                                                    ]);
                                                    $fedapayCheckout = [
                                                        'transaction_id' => $transactionId,
                                                        'order_id' => $orderId,
                                                        'order_ref' => $orderRef,
                                                        'amount' => $payableTotal,
                                                        'customer' => $payload['customer'] ?? [],
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $isFreeCheckout = $courses !== [] && $this->cartTotal($courses) <= 0;
        $checkoutProgressStep = $checkoutCompleted ? 3 : ($courses === [] ? 1 : 2);

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'success' => $success,
            'checkoutProgressStep' => $checkoutProgressStep,
            'courses' => $courses,
            'selectedMethod' => $selectedMethod,
            'availableMethods' => $availableMethods,
            'isFreeCheckout' => $isFreeCheckout,
            'fedapayCheckout' => $fedapayCheckout,
            'fedapayPublicKey' => $this->fedapay->publicKey(),
            'referralWalletAvailable' => $referralWalletAvailable,
            'referralWalletUse' => $referralWalletUse,
            'referralWalletAmountInput' => $referralWalletAmountInput,
            'siteLogoUrl' => $this->siteLogoUrl(),
            'redirect' => null,
            'returnToCatalogUrl' => $this->siteUrl('catalogue'),
            'myCoursesUrl' => $this->siteUrl('student/my_courses'),
            'ordersUrl' => $this->siteUrl('student/orders'),
            'removeBaseUrl' => $this->siteUrl('checkout'),
            'couponCodeInput' => $couponCodeInput,
            'activeCouponCode' => $activeCouponCode,
            'enterpriseCheckout' => $enterpriseCheckout,
            'hasCompanyContext' => $hasCompanyContext,
            'companyContext' => $companyContext,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @param array<int,string> $warnings
     * @return array<string,mixed>|null
     */
    private function hydrateCheckoutCourses(
        array &$courses,
        int $userId,
        string $couponCode,
        array &$warnings,
        bool $emitCouponWarnings
    ): ?array {
        foreach ($courses as &$course) {
            $promo = $this->promotions->coursePromoData($course, null, $userId);
            $course['price_final'] = (int) $promo['price_xof'];
            $course['price_base'] = (int) $promo['base_price_xof'];
            $course['promo_active'] = !empty($promo['active']) ? 1 : 0;
            $course['promo_end_at'] = $promo['end_at'] ?? null;
            $course['promo_type'] = $promo['promo_type'] ?? '';
            $course['promo_value'] = $promo['promo_value'] ?? 0;
            $course['promo_scope'] = $promo['promo_scope'] ?? 'public';
            $course['promo_id'] = $promo['promo_id'] ?? null;
            $course['promo_company_id'] = $promo['promo_company_id'] ?? null;
            $course['coupon_code'] = '';
            $courseId = (int) ($course['id'] ?? 0);
            $course['catalog_block_key'] = $this->cartBlockForCourseId($courseId);
        }
        unset($course);

        $normalizedCouponCode = PromotionsService::normalizeCouponCode($couponCode);
        if ($normalizedCouponCode === '') {
            return null;
        }

        return $this->applyCouponCodeToCourses(
            $courses,
            $userId,
            $normalizedCouponCode,
            $warnings,
            $emitCouponWarnings
        );
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @param array<int,string> $warnings
     * @return array<string,mixed>|null
     */
    private function applyCouponCodeToCourses(
        array &$courses,
        int $userId,
        string $couponCode,
        array &$warnings,
        bool $emitWarnings
    ): ?array {
        if ($courses === []) {
            if ($emitWarnings) {
                $warnings[] = 'Aucun cours dans le panier pour appliquer ce coupon.';
            }
            return null;
        }

        $courseIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $courses);
        $inspection = $this->promotions->inspectCouponForUser($couponCode, $userId, $courseIds);
        $couponStatus = (string) ($inspection['status'] ?? '');
        $coupon = is_array($inspection['coupon'] ?? null) ? $inspection['coupon'] : null;

        if ($couponStatus !== 'valid' || !is_array($coupon)) {
            if ($emitWarnings) {
                $warnings[] = $this->couponStatusMessage($couponStatus);
            }
            return null;
        }

        $couponCourseId = (int) ($coupon['course_id'] ?? 0);
        $promoType = (string) ($coupon['promo_type'] ?? '');
        $promoValue = is_numeric($coupon['promo_value'] ?? null) ? (float) $coupon['promo_value'] : 0.0;
        $couponApplied = false;
        $courseMatched = false;
        $couponCourseTitle = '';

        foreach ($courses as &$course) {
            if ((int) ($course['id'] ?? 0) !== $couponCourseId) {
                continue;
            }
            $courseMatched = true;
            $couponCourseTitle = (string) ($course['title'] ?? '');

            $basePrice = (int) ($course['price_base'] ?? $course['price_xof'] ?? 0);
            $couponPrice = $this->discountedPrice($basePrice, $promoType, $promoValue);
            if ($couponPrice >= 0 && $couponPrice < (int) ($course['price_final'] ?? $basePrice)) {
                $course['price_final'] = $couponPrice;
                $course['promo_active'] = 1;
                $course['promo_type'] = $promoType;
                $course['promo_value'] = $promoValue;
                $course['promo_scope'] = 'coupon';
                $course['promo_id'] = $coupon['id'] ?? null;
                $course['promo_end_at'] = $coupon['promo_end_at'] ?? ($coupon['end_at'] ?? null);
                $course['coupon_code'] = $couponCode;
                $couponApplied = true;
            } else {
                $course['coupon_code'] = $couponCode;
            }
        }
        unset($course);

        if (!$courseMatched) {
            if ($emitWarnings) {
                $warnings[] = "Ce coupon ne correspond \u{00e0} aucun cours de votre panier.";
            }
            return null;
        }

        if (!$couponApplied && $emitWarnings) {
            $warnings[] = "Coupon valide, mais un meilleur prix est d\u{00e9}j\u{00e0} appliqu\u{00e9}.";
        }

        $coupon['applied'] = $couponApplied ? 1 : 0;
        $coupon['coupon_code'] = $couponCode;
        $coupon['course_title'] = $couponCourseTitle;
        return $coupon;
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     */
    private function normalizeCheckoutCourseMedia(array &$courses): void
    {
        foreach ($courses as &$course) {
            $title = trim((string) ($course['title'] ?? 'Cours numerique'));
            $coverUrl = trim((string) ($course['cover'] ?? ''));
            if ($coverUrl === '') {
                $coverUrl = trim((string) ($course['cover_path'] ?? ''));
                if ($coverUrl !== '' && preg_match('#^(?:https?:)?//#i', $coverUrl) !== 1 && !str_starts_with(strtolower($coverUrl), 'data:')) {
                    $coverUrl = $this->siteUrl(ltrim($coverUrl, '/'));
                }
            }

            if ($coverUrl !== '') {
                $course['cover'] = $coverUrl;
            }

            $coverAlt = trim((string) ($course['coverAlt'] ?? ($course['cover_alt'] ?? '')));
            if ($coverAlt === '') {
                $coverAlt = 'Couverture du cours ' . $title;
            }
            $course['coverAlt'] = $coverAlt;
        }
        unset($course);
    }

    private function discountedPrice(int $basePrice, string $promoType, float $promoValue): int
    {
        if ($basePrice <= 0) {
            return $basePrice;
        }
        if ($promoType === 'amount') {
            $value = (int) round($promoValue);
            return $value < 0 ? 0 : $value;
        }
        if ($promoType === 'percent') {
            if ($promoValue <= 0 || $promoValue > 100) {
                return $basePrice;
            }
            $price = (int) round($basePrice * (1 - ($promoValue / 100)));
            return $price < 0 ? 0 : $price;
        }
        return $basePrice;
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @param array<int,int|string> $cartCourseIds
     * @param array<string,mixed> $postedSeatQty
     * @param array<int,string> $errors
     * @param array<int,string> $warnings
     */
    private function applyEnterpriseSeatContextToCourses(
        array &$courses,
        array $cartCourseIds,
        bool $enterpriseCheckout,
        int $companyId,
        array $postedSeatQty,
        bool $strictValidation,
        array &$errors,
        array &$warnings
    ): void {
        $seatSession = $_SESSION['cart_company_seats'] ?? [];
        if (!is_array($seatSession)) {
            $seatSession = [];
        }

        $validMap = [];
        foreach ($cartCourseIds as $courseId) {
            $validMap[(string) ((int) $courseId)] = true;
        }
        foreach (array_keys($seatSession) as $key) {
            if (!isset($validMap[(string) $key])) {
                unset($seatSession[$key]);
            }
        }

        foreach ($courses as &$course) {
            $courseId = (int) ($course['id'] ?? 0);
            $courseTitle = (string) ($course['title'] ?? ('Cours #' . $courseId));
            $quantity = 1;
            $course['company_id'] = null;
            $course['quantity'] = 1;
            $course['price_enterprise_applied'] = 0;
            $course['enterprise_unit_price'] = null;

            if (!$enterpriseCheckout || $companyId < 1 || $courseId < 1) {
                unset($seatSession[(string) $courseId]);
                continue;
            }

            $postedRaw = null;
            if (array_key_exists((string) $courseId, $postedSeatQty)) {
                $postedRaw = $postedSeatQty[(string) $courseId];
            } elseif (array_key_exists($courseId, $postedSeatQty)) {
                $postedRaw = $postedSeatQty[$courseId];
            }

            if ($postedRaw !== null) {
                $parsed = $this->parseSeatQuantity((string) $postedRaw);
                if ($parsed === null) {
                    $msg = 'Nombre de places invalide pour "' . $courseTitle . '".';
                    if ($strictValidation) {
                        $errors[] = $msg;
                    } else {
                        $warnings[] = $msg;
                    }
                    $parsed = max(1, (int) ($seatSession[(string) $courseId] ?? 1));
                }
                $quantity = $parsed;
            } else {
                $quantity = max(1, (int) ($seatSession[(string) $courseId] ?? 1));
            }

            if ($quantity > 5000) {
                $quantity = 5000;
            }
            $seatSession[(string) $courseId] = $quantity;

            $course['company_id'] = $companyId;
            $course['quantity'] = $quantity;
            $this->applyEnterprisePriceToCourse($course);
        }
        unset($course);

        $_SESSION['cart_company_seats'] = $seatSession;
    }

    /**
     * @param array<string,mixed> $course
     */
    private function applyEnterprisePriceToCourse(array &$course): void
    {
        $basePrice = max(0, (int) ($course['price_base'] ?? $course['price_xof'] ?? 0));
        $currentPrice = max(0, (int) ($course['price_final'] ?? $basePrice));
        $enterprisePriceRaw = $course['enterprise_price_xof'] ?? null;
        $enterpriseDiscountRaw = $course['enterprise_discount_percent'] ?? null;

        $candidatePrices = [];
        if (is_numeric($enterprisePriceRaw)) {
            $value = (int) $enterprisePriceRaw;
            if ($value > 0) {
                $candidatePrices[] = $value;
            }
        }
        if (is_numeric($enterpriseDiscountRaw) && $basePrice > 0) {
            $discount = (float) $enterpriseDiscountRaw;
            if ($discount > 0.0 && $discount <= 100.0) {
                $discounted = (int) round($basePrice * (1 - ($discount / 100)));
                $candidatePrices[] = max(0, $discounted);
            }
        }

        if ($candidatePrices === []) {
            return;
        }

        $enterpriseBest = min($candidatePrices);
        $course['enterprise_unit_price'] = $enterpriseBest;
        if ($enterpriseBest < $currentPrice) {
            $course['price_final'] = $enterpriseBest;
            $course['price_enterprise_applied'] = 1;
            if ((string) ($course['promo_scope'] ?? '') !== 'coupon') {
                $course['promo_scope'] = 'enterprise';
            }
        }
    }

    private function parseSeatQuantity(string $raw): ?int
    {
        $value = trim($raw);
        if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
            return null;
        }
        $qty = (int) $value;
        return $qty > 0 ? $qty : null;
    }

    private function cartTotal(array $courses): int
    {
        $total = 0;
        foreach ($courses as $course) {
            $unit = (int) ($course['price_final'] ?? $course['price_xof'] ?? 0);
            $quantity = max(1, (int) ($course['quantity'] ?? 1));
            $total += ($unit * $quantity);
        }
        return $total;
    }

    /**
     * @return array{available:int,locked:int}
     */
    private function buildReferralWallet(int $userId): array
    {
        if ($userId < 1) {
            return ['available' => 0, 'locked' => 0];
        }

        $earned = 0;
        try {
            $earned = $this->students->sumReferralEarnings($userId);
        } catch (\Throwable $e) {
            $earned = 0;
        }

        $paid = 0;
        $pending = 0;
        try {
            $paid = $this->withdrawals->sumPaidWithdrawals($userId, 'referral');
            $pending = $this->withdrawals->sumPendingWithdrawals($userId, 'referral');
        } catch (\Throwable $e) {
            $paid = 0;
            $pending = 0;
        }

        $lockedRedemptions = $this->wallet->sumByUserAndStatuses($userId, ['reserved', 'used']);
        $locked = $paid + $pending + $lockedRedemptions;
        $available = $earned - $locked;
        if ($available < 0) {
            $available = 0;
        }

        return ['available' => $available, 'locked' => $locked];
    }

    /**
     * @param array<int,string> $errors
     */
    private function resolveReferralWalletAmount(
        bool $useWallet,
        int $requestedAmount,
        int $available,
        int $total,
        string $selectedMethod,
        array &$errors
    ): int {
        if (!$useWallet) {
            return 0;
        }
        if ($available <= 0) {
            $errors[] = 'Solde parrainage indisponible.';
            return 0;
        }
        if ($total <= 0) {
            return 0;
        }
        if ($requestedAmount <= 0) {
            $errors[] = 'Montant parrainage invalide.';
            return 0;
        }
        if ($requestedAmount > $available) {
            $errors[] = 'Montant parrainage sup\u{00e9}rieur au solde disponible.';
            return 0;
        }

        $amount = min($requestedAmount, $available, $total);
        $remaining = $total - $amount;
        if ($remaining > 0 && $selectedMethod === 'fedapay' && $remaining < 100) {
            $errors[] = 'Le reste \u{00e0} payer doit \u{00ea}tre au moins de 100 F CFA.';
        }
        return $amount;
    }

    private function isCouponFormatValid(string $raw): bool
    {
        $value = strtoupper(trim($raw));
        if ($value === '') {
            return true;
        }
        return preg_match('/^[A-Z0-9-]{1,32}$/', $value) === 1;
    }

    private function couponStatusMessage(string $status): string
    {
        return match ($status) {
            'empty_code' => 'Entrez un code coupon.',
            'not_found' => 'Code coupon incorrect.',
            'expired' => "Ce coupon est expir\u{00e9}.",
            'not_started' => "Ce coupon n'est pas encore actif.",
            'course_mismatch' => "Ce coupon ne correspond \u{00e0} aucun cours de votre panier.",
            'not_eligible' => "Ce coupon n'est pas valable pour votre compte.",
            'company_only' => "Ce coupon est r\u{00e9}serv\u{00e9} \u{00e0} une offre entreprise.",
            'inactive' => "Ce coupon n'est plus valide.",
            'schema_unavailable' => 'Module coupon indisponible pour le moment.',
            default => 'Code coupon invalide.',
        };
    }

    private function cartBlockForCourseId(int $courseId): string
    {
        if ($courseId < 1) {
            return '';
        }
        $meta = $_SESSION['cart_meta'] ?? null;
        if (!is_array($meta)) {
            return '';
        }
        $row = $meta[(string) $courseId] ?? null;
        if (!is_array($row)) {
            return '';
        }
        return $this->normalizeCatalogBlockKey((string) ($row['origin_block'] ?? ''));
    }

    private function normalizeCatalogBlockKey(string $raw): string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return '';
        }
        $value = preg_replace('/[^a-z0-9_-]/', '-', $value);
        $value = trim((string) $value, '-_');
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 64) {
            $value = substr($value, 0, 64);
        }
        return $value;
    }
}

