<?php
$user = is_array($user ?? null) ? $user : [];
$studentProfile = is_array($studentProfile ?? null) ? $studentProfile : [];
$teacherProfile = is_array($teacherProfile ?? null) ? $teacherProfile : [];
$notificationPreferences = is_array($notificationPreferences ?? null) ? $notificationPreferences : [];

$isTeacher = !empty($isTeacher);
$isStudent = !empty($isStudent);
$hasSignatureColumn = !empty($hasSignatureColumn);
$needsAvatar = !empty($needsAvatar);
$isGoogleAccount = !empty($isGoogleAccount);

$profileErrors = is_array($profileErrors ?? null) ? $profileErrors : [];
$passwordErrors = is_array($passwordErrors ?? null) ? $passwordErrors : [];
$profileMessage = trim((string) ($profileMessage ?? ''));
$passwordMessage = trim((string) ($passwordMessage ?? ''));

$avatarMaxMb = max(1, (int) ($avatarMaxMb ?? 2));
$signatureMaxMb = max(1, (int) ($signatureMaxMb ?? 2));
 
$avatarSrc = trim((string) ($avatarSrc ?? ''));
$signatureUrl = trim((string) ($signatureUrl ?? ''));
$teacherCvUrl = trim((string) ($teacherCvUrl ?? ''));

$firstName = trim((string) ($user['first_name'] ?? ''));
$lastName = trim((string) ($user['last_name'] ?? ''));
$email = trim((string) ($user['email'] ?? '')); 
$phone = trim((string) ($user['phone'] ?? ''));
$phoneCode = trim((string) ($user['phone_country_code'] ?? ''));
$city = trim((string) ($user['city'] ?? ''));
$country = trim((string) ($user['country'] ?? ''));
$status = strtolower(trim((string) ($user['status'] ?? '')));
$emailVerifiedAt = trim((string) ($user['email_verified_at'] ?? ''));

$unreadCount = max(0, (int) ($unreadCount ?? 0));
$supportUnreadCount = max(0, (int) ($supportUnreadCount ?? 0));
$pendingMeetingsCount = max(0, (int) ($pendingMeetingsCount ?? 0));
$dashboardSpace = trim((string) ($dashboardSpace ?? ''));
$siteLogoUrl = trim((string) ($siteLogoUrl ?? ''));
$roles = dashboard_roles_from_user($user);
$isAdminProfile = in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true) || in_array('support', $roles, true);
$mainClass = 'profile-page mx-auto w-full max-w-5xl px-4 py-4 space-y-6 sm:px-6 lg:px-8';
$notificationDefaults = [
    'email_announcements' => true,
    'email_support' => true,
    'email_meetings' => true,
    'popup_announcements' => true,
    'popup_support' => true,
];
foreach ($notificationDefaults as $prefKey => $prefDefault) {
    if (!array_key_exists($prefKey, $notificationPreferences)) {
        $notificationPreferences[$prefKey] = $prefDefault;
    }
}
$fullName = trim($firstName . ' ' . $lastName);
$displayName = $fullName !== '' ? $fullName : 'Profil utilisateur';
$initials = strtoupper(($firstName !== '' ? $firstName[0] : '') . ($lastName !== '' ? $lastName[0] : ''));
$initials = $initials !== '' ? $initials : 'EN';

$roleChips = [];
if (in_array('admin', $roles, true) || in_array('admin_secondary', $roles, true)) {
    $roleChips[] = ['label' => 'Admin', 'class' => 'profile-modern-chip profile-modern-chip--amber', 'icon' => 'fa-user-shield'];
}
if (in_array('support', $roles, true)) {
    $roleChips[] = ['label' => 'Support', 'class' => 'profile-modern-chip profile-modern-chip--cyan', 'icon' => 'fa-headset'];
}
if (in_array('company_admin', $roles, true) || in_array('company_member', $roles, true)) {
    $roleChips[] = ['label' => 'Entreprise', 'class' => 'profile-modern-chip profile-modern-chip--cyan', 'icon' => 'fa-building'];
}
if ($isTeacher) {
    $roleChips[] = ['label' => 'Enseignant', 'class' => 'profile-modern-chip profile-modern-chip--violet', 'icon' => 'fa-chalkboard-teacher'];
}
if ($isStudent) {
    $roleChips[] = ['label' => 'Étudiant', 'class' => 'profile-modern-chip profile-modern-chip--blue', 'icon' => 'fa-graduation-cap'];
}
if ($roleChips === []) {
    $roleChips[] = ['label' => 'Apprenant', 'class' => 'profile-modern-chip profile-modern-chip--slate', 'icon' => 'fa-user'];
}
$completionTotal = 2;
$completionDone = 0;
if ($firstName !== '' && $lastName !== '') {
    $completionDone++;
}
if ($email !== '') {
    $completionDone++;
}
if ($isTeacher) {
    $completionTotal += 3;
    $completionDone += (trim((string) ($teacherProfile['professional_title'] ?? '')) !== '') ? 1 : 0;
    $completionDone += (trim((string) ($teacherProfile['short_bio'] ?? '')) !== '') ? 1 : 0;
    $completionDone += ($phone !== '' && $phoneCode !== '') ? 1 : 0;
}
$completionPct = (int) round(($completionDone / max(1, $completionTotal)) * 100);
$completionLabel = $completionPct >= 80 ? 'Profil solide' : 'Profil &agrave; compl&eacute;ter';
$completionClass = $completionPct >= 80
    ? 'profile-modern-chip profile-modern-chip--emerald'
    : 'profile-modern-chip profile-modern-chip--amber';

$dashboardUrl = dashboard_dashboard_url_for_user($user, $dashboardSpace !== '' ? $dashboardSpace : null);
$homeUrl = site_url('');
$notificationsUrl = site_url('notifications');
$supportUrl = site_url('support');
$profileUrl = site_url('profile');

$appendSpace = static function (string $url) use ($dashboardSpace): string {
    if ($dashboardSpace === '') {
        return $url;
    }
return $url . (str_contains($url, '?') ? '&' : '?') . 'space=' . urlencode($dashboardSpace);
};

$notificationsUrl = $appendSpace($notificationsUrl);
$supportUrl = $appendSpace($supportUrl);
$profileUrl = $appendSpace($profileUrl);
if ($isAdminProfile && strtolower($dashboardSpace) === 'admin') {
    $notificationsUrl = site_url('admin/notifications');
    $supportUrl = site_url('admin/support');
    $profileUrl = site_url('profile?space=admin');
}

$formAction = $dashboardSpace !== '' ? site_url('profile') . '?space=' . urlencode($dashboardSpace) : site_url('profile');

$contextOptions = dashboard_context_switch_options($user, 'profile', $_GET ?? [], $dashboardSpace !== '' ? $dashboardSpace : null);

$emailStatusLabel = $status === 'pending_email' ? 'Email &agrave; v&eacute;rifier' : ($emailVerifiedAt !== '' ? 'Email v&eacute;rifi&eacute;' : 'Email non v&eacute;rifi&eacute;');
$emailStatusClass = $status === 'pending_email'
    ? 'profile-chip profile-chip--amber'
    : ($emailVerifiedAt !== ''
        ? 'profile-chip profile-chip--emerald'
        : 'profile-chip profile-chip--rose');

$profileSpaceLabel = $dashboardSpace === 'teacher'
    ? 'Espace enseignant'
    : ($dashboardSpace === 'student'
        ? 'Espace étudiant'
        : ($dashboardSpace === 'company' ? 'Espace entreprise' : ($isAdminProfile ? 'Espace administrateur' : 'Espace personnel')));
$profileIntro = $isAdminProfile
    ? 'Gérez votre identité administrateur, vos préférences et la sécurité du compte dans une interface claire inspirée de l’espace étudiant.'
    : ($isTeacher
    ? 'Gérez vos informations, vos fichiers et vos préférences pédagogiques dans une interface claire inspirée de l’espace étudiant.'
    : 'Gérez vos informations, vos fichiers et vos préférences dans une interface inspirée de l’espace étudiant.');
$profileCardLabel = $completionPct >= 80 ? 'Profil solide' : 'Profil en cours';
$profileMemberSince = trim((string) ($user['created_at'] ?? ''));
$profileMemberSinceLabel = $profileMemberSince !== ''
    ? view_format_date_label($profileMemberSince, false, '')
    : '';
$studentDashboardUrl = site_url('student/dashboard');
$studentCoursesUrl = site_url('student/my_courses');
$studentCalendarUrl = site_url('student/meetings');
$studentCertificatesUrl = site_url('student/certificates');
$studentFavoritesUrl = site_url('student/favorites');
$studentOrdersUrl = site_url('student/orders');
$studentReferralsUrl = site_url('student/affiliation');
$studentMessagesUrl = site_url('support?space=student');
$studentSupportUrl = site_url('support?space=student');
$logoutUrl = site_url('logout');
$sidebarLinks = [
    ['key' => 'dashboard', 'label' => 'Tableau de bord', 'url' => $studentDashboardUrl, 'icon' => 'home', 'badge' => null],
    ['key' => 'courses', 'label' => 'Mes cours', 'url' => $studentCoursesUrl, 'icon' => 'book-open', 'badge' => null],
    ['key' => 'favorites', 'label' => 'Mes favoris', 'url' => $studentFavoritesUrl, 'icon' => 'heart', 'badge' => null],
    ['key' => 'orders', 'label' => 'Mes commandes', 'url' => $studentOrdersUrl, 'icon' => 'shopping-bag', 'badge' => null],
    ['key' => 'meetings', 'label' => 'Rendez-vous', 'url' => $studentCalendarUrl, 'icon' => 'handshake', 'badge' => null],
    ['key' => 'certificates', 'label' => 'Certificats', 'url' => $studentCertificatesUrl, 'icon' => 'certificate', 'badge' => null],
    ['key' => 'referrals', 'label' => 'Parrainage', 'url' => $studentReferralsUrl, 'icon' => 'users', 'badge' => null],
    ['key' => 'messages', 'label' => 'Messages', 'url' => $studentMessagesUrl, 'icon' => 'headset', 'badge' => 'messages'],
    ['key' => 'support', 'label' => 'Support', 'url' => $studentSupportUrl, 'icon' => 'headset', 'badge' => 'support'],
    ['key' => 'profile', 'label' => 'Mon profil', 'url' => $profileUrl, 'icon' => 'user-circle', 'badge' => null],
];
$profileAvatarUrl = $avatarSrc;
$profileVerifiedLabel = $emailVerifiedAt !== '' ? 'Vérifié' : 'À vérifier';
$profileVerifiedClass = $emailVerifiedAt !== ''
    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
$profileNotificationCount = $unreadCount + $supportUnreadCount + $pendingMeetingsCount;
$profileCardClass = 'profile-modern-card rounded-2xl border border-gray-100 bg-white p-6 shadow-card dark:border-gray-700 dark:bg-gray-800';
$profileFieldClass = 'profile-modern-field mt-1 w-full rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100';
$profileLabelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
$profileSubtleCardClass = 'rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40';
$profilePrimaryButtonClass = 'profile-modern-button profile-modern-button--primary inline-flex items-center justify-center rounded-xl bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-primary-700 shadow-sm';
$profileSecondaryButtonClass = 'profile-modern-button profile-modern-button--secondary inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600';
?>
<?php require __DIR__ . '/profile_modern.php'; return; ?>
<!doctype html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - &Eacute;cole Num&eacute;rique</title>
    <meta name="app-global-assets" content="off">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-tailwind.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-tailwind.css') ?: time()))); ?>">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="<?php echo e(site_url('assets/js/profile_form.js')); ?>" defer></script>
    <script src="<?php echo e(site_url('assets/js/togglePassword.js')); ?>" defer></script>
    <style>
        :root {
            color-scheme: light;
        }

        html.dark {
            color-scheme: dark;
        }

        html, body {
            margin: 0;
            min-height: 100%;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        body[data-view="web/profile"] {
            background: #f8fafc;
            color: #0f172a;
        }

        html.dark body[data-view="web/profile"] {
            background: #020617;
            color: #e2e8f0;
        }

        .profile-shell {
            position: relative;
            isolation: isolate;
        }

        .profile-bg {
            pointer-events: none;
            position: absolute;
            z-index: 0;
        }

        .profile-bg--grid {
            inset: 0;
            opacity: 0.04;
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.12) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.12) 1px, transparent 1px);
            background-size: 56px 56px;
        }

        html.dark .profile-bg--grid {
            opacity: 0.025;
        }

        .profile-shell__inner {
            position: relative;
            z-index: 1;
            width: min(64rem, calc(100% - 1.5rem));
            padding-block: 1rem 5rem;
        }

        .profile-topbar {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            border-radius: 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.88);
            padding: 0.9rem 1rem;
            box-shadow: 0 12px 40px -18px rgba(15, 23, 42, 0.28);
            backdrop-filter: blur(18px);
        }

        .profile-topbar > div:first-child {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .profile-topbar > div:last-child {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        html.dark .profile-topbar {
            border-color: rgba(51, 65, 85, 0.95);
            background: rgba(15, 23, 42, 0.82);
            box-shadow: 0 20px 44px -26px rgba(2, 6, 23, 0.95);
        }

        .profile-topbar a,
        .profile-topbar button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 1rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .profile-topbar a {
            width: 2.8rem;
            height: 2.8rem;
            background: rgba(255, 255, 255, 0.92);
            color: #475569;
        }

        .profile-topbar button {
            background: rgba(255, 255, 255, 0.92);
            color: #475569;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
        }

        .profile-topbar__pdf {
            border-color: transparent;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 14px 26px -18px rgba(37, 99, 235, 0.85);
        }

        .profile-topbar__pdf:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: #ffffff;
        }

        html.dark .profile-topbar a,
        html.dark .profile-topbar button {
            border-color: rgba(71, 85, 105, 0.92);
            background: rgba(2, 6, 23, 0.92);
            color: #cbd5e1;
        }

        html.dark .profile-topbar__pdf {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #ffffff;
        }

        .profile-topbar a:hover,
        .profile-topbar button:hover {
            transform: translateY(-1px);
            border-color: rgba(59, 130, 246, 0.55);
            color: #2563eb;
        }

        html.dark .profile-topbar a:hover,
        html.dark .profile-topbar button:hover {
            color: #93c5fd;
        }

        .profile-topbar .app-icon,
        .profile-panel .app-icon,
        .profile-certificate .app-icon {
            display: inline-flex;
            width: 1rem;
            height: 1rem;
            flex: 0 0 auto;
        }

        .profile-certificate__logo .app-icon {
            width: 1.4rem;
            height: 1.4rem;
        }

        .profile-seal .app-icon {
            width: 1.55rem;
            height: 1.55rem;
        }

        .profile-topbar [data-theme-icon-dark],
        .profile-topbar [data-theme-icon-light] {
            display: none;
        }

        html:not(.dark) .profile-topbar [data-theme-icon-dark] {
            display: inline-flex;
        }

        html.dark .profile-topbar [data-theme-icon-light] {
            display: inline-flex;
        }

        .profile-topbar__print {
            display: none;
        }

        @media (min-width: 640px) {
            .profile-topbar__print {
                display: inline-flex !important;
            }
        }

        .profile-main {
            margin-top: 1rem;
        }

        .profile-certificate,
        .profile-panel,
        .profile-feedback,
        [data-profile-action-bar] > .profile-action-shell {
            box-shadow: 0 18px 40px -28px rgba(15, 23, 42, 0.22);
        }

        .profile-certificate {
            position: relative;
            overflow: hidden;
            border-radius: 2.25rem;
            border: 8px double #f59e0b;
            background: rgba(255, 255, 255, 0.96);
        }

        html.dark .profile-certificate {
            background: rgba(15, 23, 42, 0.96);
        }

        .profile-certificate__body {
            position: relative;
            overflow: hidden;
        }

        .profile-certificate__glow--left {
            left: -3rem;
            top: 0;
            width: 10rem;
            height: 10rem;
        }

        .profile-certificate__glow--right {
            right: -2rem;
            bottom: 0;
            width: 13rem;
            height: 13rem;
        }

        .profile-certificate__watermark {
            left: 50%;
            top: 50%;
            width: 18rem;
            height: 18rem;
            transform: translate(-50%, -50%);
            opacity: 0.12;
        }

        .profile-certificate__ribbon {
            background: repeating-linear-gradient(45deg, #fbbf24 0, #fbbf24 2px, transparent 2px, transparent 8px);
        }

        html.dark .profile-certificate__ribbon {
            background: repeating-linear-gradient(45deg, #f59e0b 0, #f59e0b 2px, transparent 2px, transparent 8px);
        }

        .profile-certificate__glow,
        .profile-certificate__watermark {
            pointer-events: none;
        }

        .profile-watermark-icon {
            width: 100%;
            height: 100%;
            opacity: 0.18;
        }

        .profile-certificate__brandrow,
        .profile-certificate__hero,
        .profile-certificate__footer {
            position: relative;
            z-index: 1;
        }

        .profile-certificate__brandrow {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .profile-certificate__brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
        }

        .profile-certificate__logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            color: #ffffff;
            box-shadow: 0 16px 24px -18px rgba(37, 99, 235, 0.8);
        }

        .profile-certificate__space {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
            min-width: 10rem;
            padding: 0.75rem 1rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.32);
            background: rgba(255, 255, 255, 0.8);
            color: #64748b;
        }

        html.dark .profile-certificate__space {
            background: rgba(2, 6, 23, 0.66);
            color: #cbd5e1;
            border-color: rgba(71, 85, 105, 0.9);
        }

        .profile-certificate__space {
            min-width: 10rem;
        }

        .profile-kicker {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            border: 1px solid rgba(251, 191, 36, 0.3);
            background: rgba(254, 243, 199, 0.96);
            color: #a16207;
            letter-spacing: 0.28em;
        }

        html.dark .profile-kicker {
            background: rgba(120, 53, 15, 0.36);
            color: #fcd34d;
            border-color: rgba(245, 158, 11, 0.24);
        }

        .profile-certificate__title {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            font-weight: 800;
            line-height: 1.05;
        }

        .profile-gradient-text {
            background: linear-gradient(135deg, #2563eb 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-certificate__summary {
            max-width: 48rem;
        }

        .profile-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 7rem;
            height: 7rem;
            overflow: hidden;
            border-radius: 999px;
            border: 4px solid #f59e0b;
            background: #ffffff;
            box-shadow: 0 18px 35px -22px rgba(15, 23, 42, 0.4);
        }

        html.dark .profile-avatar {
            background: #020617;
        }

        .profile-chip-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .profile-chip,
        .app-status-pill,
        .profile-completion-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            padding: 0.34rem 0.78rem;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }

        .profile-chip--amber {
            border-color: rgba(251, 191, 36, 0.35);
            background: rgba(254, 243, 199, 0.92);
            color: #a16207;
        }

        .profile-chip--cyan {
            border-color: rgba(34, 211, 238, 0.28);
            background: rgba(207, 250, 254, 0.92);
            color: #0e7490;
        }

        .profile-chip--violet {
            border-color: rgba(167, 139, 250, 0.28);
            background: rgba(237, 233, 254, 0.92);
            color: #6d28d9;
        }

        .profile-chip--blue {
            border-color: rgba(96, 165, 250, 0.28);
            background: rgba(219, 234, 254, 0.92);
            color: #1d4ed8;
        }

        .profile-chip--slate {
            border-color: rgba(148, 163, 184, 0.35);
            background: rgba(241, 245, 249, 0.96);
            color: #334155;
        }

        .profile-chip--emerald {
            border-color: rgba(16, 185, 129, 0.25);
            background: rgba(209, 250, 229, 0.92);
            color: #047857;
        }

        .profile-chip--rose {
            border-color: rgba(251, 113, 133, 0.25);
            background: rgba(255, 228, 230, 0.92);
            color: #be123c;
        }

        html.dark .profile-chip--amber {
            background: rgba(146, 64, 14, 0.35);
            color: #fcd34d;
        }

        html.dark .profile-chip--cyan {
            background: rgba(8, 145, 178, 0.28);
            color: #67e8f9;
        }

        html.dark .profile-chip--violet {
            background: rgba(91, 33, 182, 0.28);
            color: #c4b5fd;
        }

        html.dark .profile-chip--blue {
            background: rgba(29, 78, 216, 0.28);
            color: #bfdbfe;
        }

        html.dark .profile-chip--slate {
            background: rgba(30, 41, 59, 0.8);
            color: #cbd5e1;
        }

        html.dark .profile-chip--emerald {
            background: rgba(6, 95, 70, 0.38);
            color: #6ee7b7;
        }

        html.dark .profile-chip--rose {
            background: rgba(159, 18, 57, 0.36);
            color: #fda4af;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .profile-stat {
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.8);
            padding: 1rem;
        }

        html.dark .profile-stat {
            background: rgba(2, 6, 23, 0.5);
            border-color: rgba(51, 65, 85, 0.85);
        }

        .profile-certificate__footer {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1.25rem;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px dashed rgba(148, 163, 184, 0.35);
        }

        .profile-seal {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 6rem;
            height: 6rem;
            padding: 0.75rem;
            border-radius: 999px;
            border: 4px solid #f59e0b;
            background: rgba(255, 255, 255, 0.85);
            transform: rotate(-12deg);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            box-shadow: 0 18px 30px -20px rgba(15, 23, 42, 0.45);
        }

        .profile-seal:hover {
            transform: rotate(-6deg) scale(1.03);
        }

        .profile-context-switch {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            border-radius: 1.75rem;
            border: 1px solid rgba(148, 163, 184, 0.32);
            background: rgba(255, 255, 255, 0.72);
            padding: 1rem;
            backdrop-filter: blur(16px);
        }

        html.dark .profile-context-switch {
            background: rgba(15, 23, 42, 0.56);
            border-color: rgba(51, 65, 85, 0.85);
        }

        .profile-feedback {
            border-radius: 1.35rem;
            padding: 0.95rem 1rem;
        }

        .app-feedback {
            display: block;
            border-radius: 1.35rem;
            padding: 0.95rem 1rem;
        }

        .app-feedback--success {
            border: 1px solid rgba(74, 222, 128, 0.2);
            background: rgba(220, 252, 231, 0.88);
            color: #166534;
        }

        .app-feedback--error {
            border: 1px solid rgba(251, 113, 133, 0.2);
            background: rgba(254, 226, 226, 0.9);
            color: #991b1b;
        }

        .profile-feedback--success {
            border: 1px solid rgba(74, 222, 128, 0.2);
            background: rgba(220, 252, 231, 0.88);
            color: #166534;
        }

        .profile-feedback--error {
            border: 1px solid rgba(251, 113, 133, 0.2);
            background: rgba(254, 226, 226, 0.9);
            color: #991b1b;
        }

        html.dark .profile-feedback--success {
            background: rgba(20, 83, 45, 0.42);
            color: #bbf7d0;
        }

        html.dark .profile-feedback--error {
            background: rgba(127, 29, 29, 0.42);
            color: #fecaca;
        }

        html.dark .app-feedback--success {
            background: rgba(20, 83, 45, 0.42);
            color: #bbf7d0;
        }

        html.dark .app-feedback--error {
            background: rgba(127, 29, 29, 0.42);
            color: #fecaca;
        }

        .profile-form {
            margin-top: 0.5rem;
        }

        .profile-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.75fr);
            align-items: start;
            gap: 1.25rem;
        }

        .profile-column {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            min-width: 0;
        }

        .profile-panel {
            border-radius: 1.65rem;
            border: 1px solid rgba(148, 163, 184, 0.34);
            background: rgba(255, 255, 255, 0.9);
            padding: 1.35rem;
        }

        html.dark .profile-panel {
            background: rgba(15, 23, 42, 0.7);
            border-color: rgba(51, 65, 85, 0.88);
        }

        .profile-panel > p:first-child,
        .profile-panel > p:nth-child(1) {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            color: #64748b;
        }

        html.dark .profile-panel > p:first-child,
        html.dark .profile-panel > p:nth-child(1) {
            color: #94a3b8;
        }

        .profile-panel h2 {
            margin-top: 0.3rem;
            margin-bottom: 0;
            font-size: 1.08rem;
            font-weight: 800;
            color: #0f172a;
        }

        html.dark .profile-panel h2 {
            color: #f8fafc;
        }

        .profile-panel > p + h2 {
            margin-top: 0.3rem;
        }

        .profile-panel > p + h2 + p,
        .profile-panel > p + h2 + div + p,
        .profile-panel > p + h2 + label + p {
            margin-top: 0.4rem;
            color: #64748b;
        }

        html.dark .profile-panel > p + h2 + p,
        html.dark .profile-panel > p + h2 + div + p,
        html.dark .profile-panel > p + h2 + label + p {
            color: #94a3b8;
        }

        .profile-panel__grid {
            display: grid;
            gap: 1rem;
        }

        .profile-panel__grid--identity,
        .profile-panel__grid--location,
        .profile-panel__grid--teacher,
        .profile-panel__grid--choices,
        .profile-panel__grid--password,
        .profile-panel__grid--danger {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .profile-panel__grid--identity > label[for="email"],
        .profile-panel__grid--identity > div,
        .profile-panel__grid--teacher > label[for="professional_title"],
        .profile-panel__grid--teacher > label[for="short_bio"],
        .profile-panel__grid--choices > label:last-child {
            grid-column: 1 / -1;
        }

        .profile-panel__grid--identity > div {
            display: grid;
            grid-template-columns: 140px minmax(0, 1fr);
            gap: 0.75rem;
        }

        .profile-panel label {
            display: block;
            color: #334155;
        }

        html.dark .profile-panel label {
            color: #cbd5e1;
        }

        .profile-panel input,
        .profile-panel textarea {
            width: 100%;
            margin-top: 0.45rem;
            border-radius: 1rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(255, 255, 255, 0.94);
            padding: 0.8rem 0.95rem;
            font: inherit;
            color: #0f172a;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        html.dark .profile-panel input,
        html.dark .profile-panel textarea {
            border-color: rgba(71, 85, 105, 0.9);
            background: rgba(2, 6, 23, 0.62);
            color: #e2e8f0;
        }

        .profile-panel input:focus,
        .profile-panel textarea:focus {
            border-color: rgba(59, 130, 246, 0.82);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.14);
        }

        .profile-panel textarea {
            min-height: 7rem;
            resize: vertical;
        }

        .profile-panel .app-checkbox,
        .profile-panel input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            margin: 0.2rem 0 0;
            accent-color: #2563eb;
            flex: 0 0 auto;
        }

        .profile-panel .app-status-pill {
            border-color: rgba(59, 130, 246, 0.2);
            background: rgba(219, 234, 254, 0.9);
            color: #1d4ed8;
        }

        .app-status-pill--info {
            border: 1px solid rgba(59, 130, 246, 0.2);
            background: rgba(219, 234, 254, 0.9);
            color: #1d4ed8;
        }

        html.dark .profile-panel .app-status-pill {
            background: rgba(30, 64, 175, 0.28);
            color: #bfdbfe;
        }

        html.dark .app-status-pill--info {
            background: rgba(30, 64, 175, 0.28);
            color: #bfdbfe;
        }

        .profile-panel .app-segmented-link,
        .profile-context-switch .app-segmented-link {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: rgba(255, 255, 255, 0.92);
            padding: 0.55rem 0.95rem;
            color: #475569;
            text-decoration: none;
            transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        }

        html.dark .profile-panel .app-segmented-link,
        html.dark .profile-context-switch .app-segmented-link {
            background: rgba(2, 6, 23, 0.66);
            color: #cbd5e1;
            border-color: rgba(71, 85, 105, 0.95);
        }

        .profile-panel .app-segmented-link:hover,
        .profile-context-switch .app-segmented-link:hover {
            transform: translateY(-1px);
            border-color: rgba(59, 130, 246, 0.55);
            color: #2563eb;
        }

        .profile-panel .app-segmented-link--active {
            border-color: rgba(37, 99, 235, 0.72);
            background: rgba(37, 99, 235, 0.98);
            color: #ffffff;
            box-shadow: 0 12px 24px -18px rgba(37, 99, 235, 0.9);
        }

        .profile-panel .app-btn-primary,
        .profile-panel button[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            border: 1px solid transparent;
            border-radius: 1rem;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            padding: 0.65rem 1rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .profile-panel button[type="submit"]:not(:disabled):hover,
        .profile-panel .app-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 28px -20px rgba(37, 99, 235, 0.75);
        }

        .profile-panel button:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: none;
        }

        .profile-panel .profile-password-field__control {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .profile-panel .profile-password-field__control input {
            flex: 1 1 auto;
        }

        .profile-panel .profile-password-field__toggle {
            flex: 0 0 auto;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(255, 255, 255, 0.95);
            color: #334155;
            padding: 0.7rem 0.95rem;
            border-radius: 0.95rem;
            font-size: 0.78rem;
            font-weight: 800;
        }

        html.dark .profile-panel .profile-password-field__toggle {
            background: rgba(2, 6, 23, 0.72);
            color: #cbd5e1;
            border-color: rgba(71, 85, 105, 0.9);
        }

        .profile-panel [data-upload-widget] {
            display: block;
        }

        .profile-upload__meta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-panel [data-upload-dropzone] {
            margin-top: 1rem;
            border-radius: 1.25rem;
            border: 1px dashed rgba(148, 163, 184, 0.5);
            background: rgba(255, 255, 255, 0.72);
            padding: 1rem;
            color: #64748b;
        }

        html.dark .profile-panel [data-upload-dropzone] {
            border-color: rgba(71, 85, 105, 0.9);
            background: rgba(2, 6, 23, 0.55);
            color: #94a3b8;
        }

        .profile-panel [data-upload-trigger] {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border-radius: 999px;
            border: 1px solid rgba(59, 130, 246, 0.35);
            background: rgba(219, 234, 254, 0.95);
            padding: 0.55rem 0.9rem;
            color: #1d4ed8;
            font-weight: 700;
            cursor: pointer;
        }

        html.dark .profile-panel [data-upload-trigger] {
            border-color: rgba(96, 165, 250, 0.35);
            background: rgba(30, 64, 175, 0.26);
            color: #bfdbfe;
        }

        .profile-panel [data-upload-state] {
            font-size: 0.72rem;
            color: #64748b;
        }

        .profile-panel [data-tone="success"],
        .profile-panel [data-state="success"] {
            color: #2563eb;
        }

        .profile-panel [data-tone="error"],
        .profile-panel [data-state="error"] {
            color: #dc2626;
        }

        .profile-panel [data-tone="neutral"],
        .profile-panel [data-state="neutral"] {
            color: #64748b;
        }

        html.dark .profile-panel [data-tone="success"],
        html.dark .profile-panel [data-state="success"] {
            color: #93c5fd;
        }

        html.dark .profile-panel [data-tone="error"],
        html.dark .profile-panel [data-state="error"] {
            color: #fca5a5;
        }

        html.dark .profile-panel [data-tone="neutral"],
        html.dark .profile-panel [data-state="neutral"] {
            color: #94a3b8;
        }

        html.dark .profile-panel [data-upload-state] {
            color: #94a3b8;
        }

        .profile-panel--danger {
            border-color: rgba(251, 113, 133, 0.26);
            background: rgba(255, 241, 242, 0.92);
        }

        html.dark .profile-panel--danger {
            border-color: rgba(190, 18, 60, 0.34);
            background: rgba(69, 10, 26, 0.35);
        }

        .profile-panel--danger button[type="submit"] {
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
        }

        .profile-panel--danger button[type="submit"]:hover {
            box-shadow: 0 18px 28px -20px rgba(190, 18, 60, 0.75);
        }

        .font-display {
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            letter-spacing: 0;
        }

        .shadow-certificate {
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25);
        }

        .profile-completion-chip--pending {
            border: 1px solid rgba(251, 191, 36, 0.28);
            background: rgba(254, 243, 199, 0.92);
            color: #a16207;
        }

        .profile-completion-chip--done {
            border: 1px solid rgba(16, 185, 129, 0.25);
            background: rgba(209, 250, 229, 0.92);
            color: #047857;
        }

        .profile-action-bar {
            position: fixed;
            left: 50%;
            bottom: 1.25rem;
            z-index: 60;
            width: min(calc(100vw - 1.5rem), 72rem);
            transform: translate(-50%, 1rem);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .profile-action-bar.is-visible {
            opacity: 1;
            pointer-events: auto;
            transform: translate(-50%, 0);
        }

        .profile-action-shell {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 1.25rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(18px);
        }

        html.dark .profile-action-shell {
            background: rgba(2, 6, 23, 0.86);
            border-color: rgba(51, 65, 85, 0.9);
        }

        [data-profile-dirty][data-state="dirty"] {
            color: #92400e;
        }

        html.dark [data-profile-dirty][data-state="dirty"] {
            color: #fcd34d;
        }

        .hidden,
        [hidden] {
            display: none !important;
        }

        .profile-print-card .mt-4.grid,
        .profile-print-card .mt-8.grid {
            align-items: stretch;
        }

        @media (min-width: 640px) {
            .profile-action-bar {
                bottom: 1.5rem;
            }
        }

        @media (max-width: 1023px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .profile-topbar {
                border-radius: 1.35rem;
            }

            .profile-certificate {
                border-width: 6px;
                border-radius: 1.75rem;
            }

            .profile-panel {
                padding: 1rem;
                border-radius: 1.35rem;
            }

            .profile-stats {
                grid-template-columns: 1fr;
            }

            .profile-panel__grid--identity,
            .profile-panel__grid--location,
            .profile-panel__grid--teacher {
                grid-template-columns: 1fr;
            }

            .profile-panel__grid--identity > div {
                grid-template-columns: 1fr;
            }

            .profile-topbar .hidden.sm\\:inline-flex {
                display: none !important;
            }

            .profile-topbar > div:last-child {
                gap: 0.5rem;
            }
        }

        @media print {
            body[data-view="web/profile"] {
                background: #fff !important;
                color: #111827 !important;
            }

            .profile-bg,
            .no-print,
            .profile-action-bar {
                display: none !important;
            }

            .profile-certificate {
                box-shadow: none !important;
                background: #fff !important;
                border-color: #d4af37 !important;
            }

            .profile-panel,
            .profile-feedback,
            .profile-topbar {
                box-shadow: none !important;
            }

            a,
            button {
                text-decoration: none !important;
            }
        }
    </style>
<link rel="stylesheet" href="<?php echo e(site_url('assets/css/public-teacher-scale.css?v=' . (@filemtime(__DIR__ . '/../../../assets/css/public-teacher-scale.css') ?: time()))); ?>">
</head>
<body data-view="web/profile" class="profile-page-shell min-h-screen text-slate-900 transition-colors dark:text-slate-100">
    <div class="profile-shell relative min-h-screen overflow-hidden">
        <div class="profile-bg profile-bg--grid pointer-events-none absolute inset-0 opacity-40"></div>
        <div class="profile-bg profile-bg--glow profile-bg--left pointer-events-none absolute -left-24 top-12 h-72 w-72 rounded-full bg-blue-500/10 blur-3xl dark:bg-blue-400/10"></div>
        <div class="profile-bg profile-bg--glow profile-bg--right pointer-events-none absolute -right-24 top-44 h-80 w-80 rounded-full bg-cyan-400/10 blur-3xl dark:bg-cyan-300/10"></div>
        <div class="profile-bg profile-bg--glow profile-bg--center pointer-events-none absolute left-1/2 top-44 h-80 w-80 -translate-x-1/2 rounded-full bg-amber-400/6 blur-3xl dark:bg-amber-300/10"></div>

        <div class="profile-shell__inner relative mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-4 sm:px-6 lg:px-8">
            <header class="profile-topbar no-print flex items-center justify-between gap-4 rounded-[1.75rem] border border-slate-200/80 bg-white/90 px-4 py-3 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="<?php echo e($dashboardUrl); ?>" class="profile-topbar__back inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 transition hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:text-blue-300" aria-label="Retour">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('chevron-left'); ?></span>
                    </a>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400"><?php echo e($profileSpaceLabel); ?></p>
                        <h1 class="truncate text-lg font-semibold text-slate-900 dark:text-slate-100">Mon profil</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="theme-toggle" data-web-theme-toggle class="profile-topbar__theme inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:text-white" aria-label="Changer le thème">
                        <span class="app-icon" data-theme-icon-dark aria-hidden="true"><?php echo dashboard_icon_svg('moon'); ?></span>
                        <span class="app-icon" data-theme-icon-light aria-hidden="true"><?php echo dashboard_icon_svg('sun'); ?></span>
                    </button>
                    <button type="button" onclick="window.print()" class="profile-topbar__print hidden sm:inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:text-white">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('printer'); ?></span>
                        <span>Imprimer</span>
                    </button>
                    <button type="button" onclick="window.print()" class="profile-topbar__pdf inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-md transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('download'); ?></span>
                        <span>PDF</span>
                    </button>
                </div>
            </header>

            <main class="<?php echo e($mainClass); ?>">
                <section class="profile-certificate profile-print-card relative overflow-hidden rounded-[2.25rem] border-8 border-double border-amber-400 bg-white/95 shadow-certificate dark:border-amber-500 dark:bg-slate-900">
                    <div class="profile-certificate__body relative p-5 sm:p-8 lg:p-10">
                        <div class="profile-certificate__ribbon profile-certificate-border absolute inset-x-0 top-0 h-2"></div>
                        <div class="profile-certificate__glow profile-certificate__glow--left pointer-events-none absolute -left-12 top-0 h-40 w-40 rounded-full bg-blue-100/40 blur-2xl dark:bg-blue-900/20"></div>
                        <div class="profile-certificate__glow profile-certificate__glow--right pointer-events-none absolute -right-8 bottom-0 h-52 w-52 rounded-full bg-cyan-100/30 blur-3xl dark:bg-cyan-900/20"></div>
                        <div class="profile-certificate__watermark pointer-events-none absolute left-1/2 top-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 opacity-10 text-blue-600 dark:text-blue-300">
                            <span class="app-icon profile-watermark-icon" aria-hidden="true"><?php echo dashboard_icon_svg('user-graduate'); ?></span>
                        </div>

                        <div class="profile-certificate__brandrow flex flex-wrap items-start justify-between gap-4">
                            <div class="profile-certificate__brand flex items-center gap-3">
                                <div class="profile-certificate__logo flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-white shadow-md">
                                    <?php if ($siteLogoUrl !== ''): ?>
                                        <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo École Numérique" class="h-full w-full object-contain p-1.5">
                                    <?php else: ?>
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('graduation-cap'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">École Numérique</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Fiche de profil personnalisée</p>
                                </div>
                            </div>
                            <div class="profile-certificate__space rounded-full border border-slate-200 bg-white/80 px-4 py-2 text-right text-xs font-semibold text-slate-500 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-950/60 dark:text-slate-300">
                                <p class="uppercase tracking-[0.24em]">Espace</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-100"><?php echo e($profileSpaceLabel); ?></p>
                            </div>
                        </div>

                        <div class="profile-certificate__hero mt-10 text-center">
                            <div class="profile-kicker inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-1 text-[11px] font-semibold uppercase tracking-[0.32em] text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('award'); ?></span>
                                <span><?php echo e($profileCardLabel); ?></span>
                            </div>

                            <h2 class="profile-certificate__title mt-5 text-3xl font-serif font-bold tracking-wide text-slate-900 dark:text-white md:text-5xl">
                                Mon profil
                            </h2>

                            <p class="profile-certificate__summary mt-3 max-w-3xl mx-auto text-base text-slate-600 dark:text-slate-300 md:text-lg">
                                <?php echo e($profileIntro); ?>
                            </p>

                            <div class="profile-hero__avatar mt-8 flex flex-col items-center gap-5">
                                <div class="profile-avatar flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-4 border-amber-400 bg-white shadow-xl dark:border-amber-500 dark:bg-slate-950">
                                    <?php if ($avatarSrc !== ''): ?>
                                        <img src="<?php echo e($avatarSrc); ?>" alt="Photo de profil" class="h-full w-full object-cover">
                                    <?php else: ?>
                                        <span class="text-3xl font-bold text-slate-700 dark:text-slate-100"><?php echo e($initials); ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="max-w-4xl">
                                    <h3 class="profile-gradient-text text-4xl font-bold leading-tight md:text-6xl">
                                        <?php echo e($displayName); ?>
                                    </h3>
                                    <div class="mx-auto mt-4 h-1 w-28 rounded-full bg-gradient-to-r from-blue-500 to-cyan-500"></div>
                                </div>
                            </div>

                            <div class="profile-chip-row mt-8 flex flex-wrap items-center justify-center gap-2 text-xs font-semibold">
                                <?php foreach ($roleChips as $chip): ?>
                                    <span class="<?php echo e($chip['class']); ?>"><?php echo $chip['label']; ?></span>
                                <?php endforeach; ?>
                                <span class="<?php echo e($completionClass); ?>"><?php echo e((string) $completionPct); ?>% &middot; <?php echo $completionLabel; ?></span>
                                <span class="<?php echo e($emailStatusClass); ?>"><?php echo $emailStatusLabel; ?></span>
                            </div>

                            <div class="profile-stats mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div class="profile-stat rounded-2xl border border-slate-200/80 bg-white/80 p-4 text-left shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[11px] uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Nom affiché</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white"><?php echo e($displayName); ?></p>
                                </div>
                                <div class="profile-stat rounded-2xl border border-slate-200/80 bg-white/80 p-4 text-left shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[11px] uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Email</p>
                                    <p class="mt-2 break-words text-lg font-semibold text-slate-900 dark:text-white"><?php echo e($email !== '' ? $email : 'Non renseigné'); ?></p>
                                </div>
                                <div class="profile-stat rounded-2xl border border-slate-200/80 bg-white/80 p-4 text-left shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[11px] uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Localisation</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white"><?php echo e(trim($city . ' ' . $country) !== '' ? trim($city . ' ' . $country) : 'Non renseignée'); ?></p>
                                </div>
                                <div class="profile-stat rounded-2xl border border-slate-200/80 bg-white/80 p-4 text-left shadow-sm dark:border-slate-800 dark:bg-slate-950/50">
                                    <p class="text-[11px] uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">Compte</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900 dark:text-white"><?php echo e($isGoogleAccount ? 'Google' : 'Local'); ?></p>
                                </div>
                            </div>

                            <div class="profile-certificate__footer mt-10 flex flex-wrap items-end justify-between gap-6 border-t border-dashed border-slate-200/80 pt-6 text-left dark:border-slate-700/80">
                                <div class="max-w-xl">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">Signature de profil</p>
                                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                        Avatar, signature et préférences restent alignés à votre compte pour toutes les actions importantes.
                                    </p>
                                </div>
                                <div class="profile-seal flex h-24 w-24 flex-col items-center justify-center rounded-full border-4 border-amber-400 bg-white/80 text-amber-600 shadow-lg backdrop-blur dark:border-amber-500 dark:bg-slate-950/60 dark:text-amber-300">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('award'); ?></span>
                                    <span class="mt-1 text-[10px] font-bold uppercase tracking-[0.32em]"><?php echo e((string) $completionPct); ?>%</span>
                                </div>
                                <div class="text-right">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">Statut du compte</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-800 dark:text-slate-100"><?php echo $emailStatusLabel; ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($contextOptions)): ?>
                            <div class="profile-context-switch mt-8 flex flex-wrap items-center gap-2 rounded-3xl border border-slate-200/80 bg-white/70 p-4 text-xs font-semibold shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <span class="text-[11px] uppercase tracking-[0.32em] text-slate-500 dark:text-slate-400">Changer d'espace</span>
                                <?php foreach ($contextOptions as $option): ?>
                                    <a class="inline-flex items-center gap-2 rounded-full border px-4 py-2 transition <?php echo !empty($option['active']) ? 'border-blue-500 bg-blue-500 text-white shadow-sm' : 'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:text-blue-300'; ?>" href="<?php echo e((string) ($option['url'] ?? $profileUrl)); ?>">
                                        <span><?php echo e((string) ($option['label'] ?? '')); ?></span>
                                    </a>
                                <?php endforeach; ?>
                                <a class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-slate-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-950/70 dark:text-slate-300 dark:hover:border-blue-500 dark:hover:text-blue-300" href="<?php echo e($homeUrl); ?>">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('home'); ?></span>
                                    <span>Accueil</span>
                                </a>
                            </div>
                        <?php endif; ?>

        <?php if ($profileMessage !== ''): ?>
            <section class="profile-feedback app-feedback app-feedback--success" role="status" aria-live="polite">
                <?php echo e($profileMessage); ?>
            </section>
        <?php endif; ?>

        <?php if ($profileErrors !== []): ?>
            <section class="profile-feedback app-feedback app-feedback--error" role="alert">
                <?php foreach ($profileErrors as $error): ?>
                    <p>- <?php echo e((string) $error); ?></p>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
        <form method="post" action="<?php echo e($formAction); ?>" enctype="multipart/form-data" data-profile-form class="profile-form space-y-5">
            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
            <input type="hidden" name="action" value="profile">
            <input type="hidden" name="notification_preferences_present" value="1">

            <div class="profile-layout grid gap-5 lg:grid-cols-[1.25fr_0.75fr]">
                <div class="profile-column profile-column--main space-y-5">
                    <section class="profile-panel profile-panel--identity rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Identit&eacute;</p>
                                <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Informations personnelles</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ces donn&eacute;es sont visibles sur votre espace.</p>
                            </div>
                            <span class="<?php echo e($emailStatusClass); ?>"><?php echo $emailStatusLabel; ?></span>
                        </div>
                        <div class="profile-panel__grid profile-panel__grid--identity mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="first_name">
                                Pr&eacute;nom <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="first_name" name="first_name" autocomplete="given-name" value="<?php echo e($firstName); ?>" required>
                            </label>
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="last_name">
                                Nom <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="last_name" name="last_name" autocomplete="family-name" value="<?php echo e($lastName); ?>" required>
                            </label>
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200 sm:col-span-2" for="email">
                                Email <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="email" id="email" name="email" autocomplete="email" value="<?php echo e($email); ?>" required>
                                <span id="email_inline_error" class="mt-1 block text-xs text-slate-500 dark:text-slate-400"></span>
                                <?php if ($status === 'pending_email'): ?>
                                    <span class="mt-1 block text-xs text-amber-600 dark:text-amber-300">V&eacute;rifiez votre bo&icirc;te mail pour confirmer le nouvel email.</span>
                                <?php endif; ?>
                            </label>
                            <div class="sm:col-span-2 grid gap-3 sm:grid-cols-[140px_1fr]">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="phone_code">
                                    Indicatif <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="phone_code" name="phone_code" autocomplete="tel-country-code" placeholder="+229" value="<?php echo e($phoneCode); ?>">
                                </label>
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="phone">
                                    Num&eacute;ro de t&eacute;l&eacute;phone<?php echo $isTeacher ? ' (retraits)' : ''; ?>
                                    <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="phone" name="phone" autocomplete="tel" placeholder="90 00 00 00" value="<?php echo e($phone); ?>">
                                </label>
                            </div>
                            <span id="phone_inline_error" class="text-xs text-slate-500 dark:text-slate-400 sm:col-span-2"></span>
                        </div>
                    </section>

                    <section class="profile-panel profile-panel--location rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Localisation</p>
                        <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Coordonn&eacute;es g&eacute;ographiques</h2>
                        <div class="profile-panel__grid profile-panel__grid--location mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="city">
                                Ville <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="city" name="city" autocomplete="address-level2" value="<?php echo e($city); ?>">
                            </label>
                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="country">
                                Pays <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="country" name="country" autocomplete="country-name" value="<?php echo e($country); ?>">
                            </label>
                        </div>
                    </section>

                    <?php if ($isStudent): ?>
                        <section class="profile-panel profile-panel--student rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Profil &eacute;tudiant</p>
                            <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Nom affich&eacute; sur les certificats</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Indiquez le nom tel qu'il appara&icirc;tra sur vos attestations.</p>
                            <label class="mt-4 block text-sm font-semibold text-slate-700 dark:text-slate-200" for="certificate_display_name">
                                Nom sur le certificat <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="certificate_display_name" name="certificate_display_name" maxlength="180" placeholder="Ex : Pr&eacute;nom Nom" value="<?php echo e((string) ($studentProfile['certificate_display_name'] ?? '')); ?>">
                            </label>
                        </section>
                    <?php endif; ?>

                    <?php if ($isTeacher): ?>
                        <section class="profile-panel profile-panel--teacher rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Profil enseignant</p>
                            <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">D&eacute;tails professionnels</h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Compl&eacute;tez votre profil enseignant.</p>
                            <div class="profile-panel__grid profile-panel__grid--teacher mt-4 grid gap-4 sm:grid-cols-2">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200 sm:col-span-2" for="professional_title">
                                    Titre professionnel <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="professional_title" name="professional_title" maxlength="150" placeholder="Ex : Formateur en d&eacute;veloppement web" value="<?php echo e((string) ($teacherProfile['professional_title'] ?? '')); ?>" required>
                                </label>
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200 sm:col-span-2" for="short_bio">
                                    Courte biographie <textarea class="mt-1 min-h-[120px] w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" id="short_bio" name="short_bio" maxlength="1200" placeholder="R&eacute;sumez votre parcours et votre approche." required><?php echo e((string) ($teacherProfile['short_bio'] ?? '')); ?></textarea>
                                </label>
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="portfolio_url">
                                    Portfolio (optionnel) <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="url" id="portfolio_url" name="portfolio_url" placeholder="https://..." value="<?php echo e((string) ($teacherProfile['portfolio_url'] ?? '')); ?>">
                                </label>
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="linkedin_url">
                                    LinkedIn (optionnel) <input class="mt-1 w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="url" id="linkedin_url" name="linkedin_url" placeholder="https://linkedin.com/in/..." value="<?php echo e((string) ($teacherProfile['linkedin_url'] ?? '')); ?>">
                                </label>
                            </div>
                            <?php if ($teacherCvUrl !== ''): ?>
                                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-semibold">
                                    <span class="app-status-pill app-status-pill--info">CV enregistr&eacute;</span>
                                    <a class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold" href="<?php echo e($teacherCvUrl); ?>" target="_blank" rel="noopener">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('external-link'); ?></span>
                                        <span>Voir le CV actuel</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                    <section class="profile-panel profile-panel--notifications rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Notifications</p>
                        <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Vos pr&eacute;f&eacute;rences</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choisissez o&ugrave; recevoir les alertes importantes.</p>
                        <div class="profile-panel__grid profile-panel__grid--choices mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <input class="app-checkbox mt-1" type="checkbox" name="notification_preferences[email_announcements]" value="1" <?php echo !empty($notificationPreferences['email_announcements']) ? 'checked' : ''; ?>>
                                <span>
                                    <strong class="block text-slate-900 dark:text-slate-100">Annonces par email</strong>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Recevoir les communications officielles.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <input class="app-checkbox mt-1" type="checkbox" name="notification_preferences[email_support]" value="1" <?php echo !empty($notificationPreferences['email_support']) ? 'checked' : ''; ?>>
                                <span>
                                    <strong class="block text-slate-900 dark:text-slate-100">Support par email</strong>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">R&eacute;ponses et suivis du support.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <input class="app-checkbox mt-1" type="checkbox" name="notification_preferences[email_meetings]" value="1" <?php echo !empty($notificationPreferences['email_meetings']) ? 'checked' : ''; ?>>
                                <span>
                                    <strong class="block text-slate-900 dark:text-slate-100">Rendez-vous par email</strong>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Confirmations et rappels.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <input class="app-checkbox mt-1" type="checkbox" name="notification_preferences[popup_announcements]" value="1" <?php echo !empty($notificationPreferences['popup_announcements']) ? 'checked' : ''; ?>>
                                <span>
                                    <strong class="block text-slate-900 dark:text-slate-100">Annonces dans l'application</strong>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Notifications visibles dans votre espace.</span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white/70 p-3 text-sm dark:border-slate-800 dark:bg-slate-950/40 sm:col-span-2">
                                <input class="app-checkbox mt-1" type="checkbox" name="notification_preferences[popup_support]" value="1" <?php echo !empty($notificationPreferences['popup_support']) ? 'checked' : ''; ?>>
                                <span>
                                    <strong class="block text-slate-900 dark:text-slate-100">Support dans l'application</strong>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">Alertes lorsque le support vous r&eacute;pond.</span>
                                </span>
                            </label>
                        </div>
                    </section>
                </div>

                <div class="profile-column profile-column--aside space-y-5">
                    <section class="profile-panel profile-panel--summary rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                        <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Aper&ccedil;u</p>
                        <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Synth&egrave;se du compte</h2>
                        <div class="profile-summary-list mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                            <div class="flex items-center gap-2">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('user'); ?></span>
                                <span><?php echo e($displayName); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('mail'); ?></span>
                                <span><?php echo e($email !== '' ? $email : 'Email manquant'); ?></span>
                            </div>
                            <?php if ($phone !== '' || $phoneCode !== ''): ?>
                                <div class="flex items-center gap-2">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('message-circle'); ?></span>
                                    <span><?php echo e(trim($phoneCode . ' ' . $phone)); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($city !== '' || $country !== ''): ?>
                                <div class="flex items-center gap-2">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('search'); ?></span>
                                    <span><?php echo e(trim($city . ' ' . $country)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-semibold">
                            <?php foreach ($roleChips as $chip): ?>
                                <span class="<?php echo e($chip['class']); ?>"><?php echo $chip['label']; ?></span>
                            <?php endforeach; ?>
                            <span class="<?php echo e($emailStatusClass); ?>"><?php echo $emailStatusLabel; ?></span>
                        </div>
                    </section>

                    <section class="profile-panel profile-panel--avatar rounded-3xl border border-slate-200/70 bg-white/90 p-5 dark:border-slate-800 dark:bg-slate-950/40" data-upload-widget>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Photo</p>
                                <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Avatar</h2>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Formats JPG, PNG ou WebP (<?php echo e((string) $avatarMaxMb); ?> Mo max).</p>
                            </div>
                            <?php if ($needsAvatar): ?>
                                <span class="profile-completion-chip profile-completion-chip--pending">Recommand&eacute;</span>
                            <?php endif; ?>
                        </div>
                        <div class="profile-upload__meta mt-4 flex items-center gap-4">
                            <div class="h-16 w-16 rounded-2xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                                <img id="avatar_preview" src="<?php echo e($avatarSrc); ?>" alt="Photo de profil" class="h-full w-full object-cover<?php echo $avatarSrc !== '' ? '' : ' hidden'; ?>">
                                <div id="avatar_placeholder" class="<?php echo $avatarSrc !== '' ? 'hidden ' : ''; ?>flex h-full w-full items-center justify-center bg-slate-100 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                    <?php echo e($initials); ?>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Nouvelle photo</p>
                                <p id="avatar_filename" class="text-xs text-slate-500 dark:text-slate-400">Aucun nouveau fichier choisi</p>
                                <p id="avatar_feedback" class="text-xs text-slate-500 dark:text-slate-400"></p>
                                <p data-upload-state="avatar_file" class="text-[11px] text-slate-500 dark:text-slate-400">&Eacute;tat: <?php echo $avatarSrc !== '' ? 'fichier actuel charg&eacute;.' : 'aucune s&eacute;lection.'; ?></p>
                            </div>
                        </div>
                        <div class="profile-upload__dropzone mt-4 rounded-2xl border border-dashed border-slate-200/80 bg-white/70 p-4 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-400" data-upload-dropzone="avatar_file">
                            <p>Glissez-d&eacute;posez l'image ou cliquez pour s&eacute;lectionner un fichier.</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <button type="button" class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold" data-upload-trigger="avatar_file">
                                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span>
                                    <span>Choisir un fichier</span>
                                </button>
                                <span>Max <?php echo e((string) $avatarMaxMb); ?> Mo</span>
                            </div>
                            <input class="sr-only" type="file" id="avatar_file" name="avatar_file" accept=".jpg,.jpeg,.png,.webp" data-max-mb="<?php echo e((string) $avatarMaxMb); ?>" data-upload-feedback="avatar_feedback" data-upload-name="avatar_filename" data-upload-preview="avatar_preview" data-upload-placeholder="avatar_placeholder">
                        </div>
                    </section>
                    <?php if ($isTeacher && $hasSignatureColumn): ?>
                        <section id="teacher-signature-section" class="profile-panel profile-panel--signature rounded-3xl border border-slate-200/70 bg-white/90 p-5 scroll-mt-28 dark:border-slate-800 dark:bg-slate-950/40" data-upload-widget>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">Signature</p>
                                    <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Signature enseignant</h2>
                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Utilis&eacute;e automatiquement sur les certificats.</p>
                                </div>
                            </div>
                            <div class="profile-upload__meta mt-4 flex items-center gap-4">
                                <div class="h-16 w-24 rounded-2xl border border-slate-200 bg-white flex items-center justify-center overflow-hidden text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                    <img id="signature_preview" src="<?php echo e($signatureUrl); ?>" alt="Signature enseignant" class="h-full w-full object-contain<?php echo $signatureUrl !== '' ? '' : ' hidden'; ?>">
                                    <div id="signature_placeholder" class="<?php echo $signatureUrl !== '' ? 'hidden ' : ''; ?>flex h-full w-full items-center justify-center bg-slate-100 text-xs text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                        Aucune signature
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Fichier signature</p>
                                    <p id="signature_filename" class="text-xs text-slate-500 dark:text-slate-400">Aucun nouveau fichier choisi</p>
                                    <p id="signature_feedback" class="text-xs text-slate-500 dark:text-slate-400"></p>
                                    <p data-upload-state="signature_file" class="text-[11px] text-slate-500 dark:text-slate-400">&Eacute;tat: <?php echo $signatureUrl !== '' ? 'fichier actuel charg&eacute;.' : 'aucune s&eacute;lection.'; ?></p>
                                </div>
                            </div>
                            <div class="profile-upload__dropzone mt-4 rounded-2xl border border-dashed border-slate-200/80 bg-white/70 p-4 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-400" data-upload-dropzone="signature_file">
                                <p>Glissez-d&eacute;posez la signature ou cliquez pour s&eacute;lectionner un fichier.</p>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <button type="button" class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold" data-upload-trigger="signature_file">
                                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('upload'); ?></span>
                                        <span>Choisir un fichier</span>
                                    </button>
                                    <span>Max <?php echo e((string) $signatureMaxMb); ?> Mo</span>
                                </div>
                                <input class="sr-only" type="file" id="signature_file" name="signature_file" accept=".jpg,.jpeg,.png,.webp" data-max-mb="<?php echo e((string) $signatureMaxMb); ?>" data-upload-feedback="signature_feedback" data-upload-name="signature_filename" data-upload-preview="signature_preview" data-upload-placeholder="signature_placeholder">
                            </div>
                            <?php if ($signatureUrl !== ''): ?>
                                <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                                    <input class="app-checkbox" type="checkbox" name="remove_signature" value="1">
                                    Supprimer la signature actuelle
                                </label>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-action-bar mt-2" data-profile-action-bar aria-hidden="true">
                <div class="profile-action-shell mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-3 text-xs font-semibold shadow-sm dark:border-slate-800 dark:bg-slate-950/80">
                    <span data-profile-dirty class="text-slate-600 dark:text-slate-300">Aucune modification en attente.</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" data-profile-cancel="<?php echo e($dashboardUrl); ?>" class="app-segmented-link inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold">
                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('x'); ?></span>
                            <span>Annuler</span>
                        </button>
                        <button type="submit" data-profile-save class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold">
                            <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('check-circle'); ?></span>
                            <span>Enregistrer</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <?php if ($passwordMessage !== ''): ?>
            <section class="profile-feedback app-feedback app-feedback--success" role="status" aria-live="polite">
                <?php echo e($passwordMessage); ?>
            </section>
        <?php endif; ?>

        <?php if ($passwordErrors !== []): ?>
            <section class="profile-feedback app-feedback app-feedback--error" role="alert">
                <?php foreach ($passwordErrors as $error): ?>
                    <p>- <?php echo e((string) $error); ?></p>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($isGoogleAccount): ?>
            <section class="profile-panel profile-panel--security rounded-3xl border border-slate-200/70 bg-white/90 p-5 text-sm text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-300">
                <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">S&eacute;curit&eacute;</p>
                <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Mot de passe</h2>
                <p class="mt-2">Compte connect&eacute; &agrave; Google. Mot de passe g&eacute;r&eacute; par Google.</p>
            </section>
        <?php else: ?>
            <form method="post" action="<?php echo e($formAction); ?>" class="profile-panel profile-panel--security profile-password-card rounded-3xl border border-slate-200/70 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
                <input type="hidden" name="action" value="password">
                <p class="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">S&eacute;curit&eacute;</p>
                <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Mettre &agrave; jour le mot de passe</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choisissez un mot de passe fort et unique.</p>
                <div class="profile-panel__grid profile-panel__grid--password mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="profile-password-field sm:col-span-2">
                        <label class="profile-password-field__label text-sm font-semibold text-slate-700 dark:text-slate-200" for="current_password">
                            Mot de passe actuel
                        </label>
                        <div class="profile-password-field__control">
                            <input class="profile-password-field__input w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                            <button type="button" class="profile-password-field__toggle inline-flex items-center justify-center rounded-xl border px-3 py-1.5 text-xs font-semibold" data-password-toggle="#current_password" data-label-show="Afficher" data-label-hide="Masquer">
                                <span data-password-toggle-label>Afficher</span>
                            </button>
                        </div>
                        <span id="current_password_inline_error" class="mt-1 block text-xs text-slate-500 dark:text-slate-400"></span>
                    </div>
                    <div class="profile-password-field">
                        <label class="profile-password-field__label text-sm font-semibold text-slate-700 dark:text-slate-200" for="new_password">
                            Nouveau mot de passe
                        </label>
                        <div class="profile-password-field__control">
                            <input class="profile-password-field__input w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="profile-password-field__toggle inline-flex items-center justify-center rounded-xl border px-3 py-1.5 text-xs font-semibold" data-password-toggle="#new_password" data-label-show="Afficher" data-label-hide="Masquer">
                                <span data-password-toggle-label>Afficher</span>
                            </button>
                        </div>
                        <span id="new_password_inline_error" class="mt-1 block text-xs text-slate-500 dark:text-slate-400"></span>
                    </div>
                    <div class="profile-password-field">
                        <label class="profile-password-field__label text-sm font-semibold text-slate-700 dark:text-slate-200" for="confirm_password">
                            Confirmer le mot de passe
                        </label>
                        <div class="profile-password-field__control">
                            <input class="profile-password-field__input w-full rounded-xl border border-slate-200 bg-white/70 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-100" type="password" id="confirm_password" name="confirm_password" minlength="8" autocomplete="new-password" required>
                            <button type="button" class="profile-password-field__toggle inline-flex items-center justify-center rounded-xl border px-3 py-1.5 text-xs font-semibold" data-password-toggle="#confirm_password" data-label-show="Afficher" data-label-hide="Masquer">
                                <span data-password-toggle-label>Afficher</span>
                            </button>
                        </div>
                        <span id="confirm_password_inline_error" class="mt-1 block text-xs text-slate-500 dark:text-slate-400"></span>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button type="submit" class="app-btn-primary inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold">
                        <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('shield'); ?></span>
                        <span>Mettre &agrave; jour</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <form method="post" action="<?php echo e($formAction); ?>" class="profile-panel profile-panel--danger rounded-3xl border border-rose-200/80 bg-rose-50/80 p-5 shadow-sm dark:border-rose-900/50 dark:bg-rose-950/20">
            <input type="hidden" name="_csrf" value="<?php echo e(view_csrf_token()); ?>">
            <input type="hidden" name="action" value="delete_account">
            <p class="text-[11px] uppercase tracking-wide text-rose-700 dark:text-rose-300">Zone de danger</p>
            <h2 class="mt-1 text-lg font-display font-semibold text-slate-900 dark:text-slate-100">Supprimer d&eacute;finitivement le compte</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                Cette action est irr&eacute;versible. Vos acc&egrave;s seront coup&eacute;s, vos donn&eacute;es de profil seront effac&eacute;es, et les historiques strictement n&eacute;cessaires seront conserv&eacute;s uniquement sous forme anonymis&eacute;e.
            </p>
            <div class="profile-panel__grid profile-panel__grid--danger mt-4 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="delete_confirmation">
                    Tapez SUPPRIMER
                    <input class="mt-1 w-full rounded-xl border border-rose-200 bg-white/90 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-rose-900/60 dark:bg-slate-900/70 dark:text-slate-100" type="text" id="delete_confirmation" name="delete_confirmation" autocomplete="off" placeholder="SUPPRIMER" required>
                </label>
                <?php if ($isGoogleAccount): ?>
                    <div class="rounded-2xl border border-rose-200/80 bg-white/80 px-4 py-3 text-sm text-slate-600 dark:border-rose-900/60 dark:bg-slate-900/60 dark:text-slate-300">
                        Compte Google : la confirmation se fait sans mot de passe local suppl&eacute;mentaire.
                    </div>
                <?php else: ?>
                    <label class="text-sm font-semibold text-slate-700 dark:text-slate-200" for="delete_account_password">
                        Mot de passe de confirmation
                        <input class="mt-1 w-full rounded-xl border border-rose-200 bg-white/90 px-4 py-2.5 text-sm text-slate-700 outline-none dark:border-rose-900/60 dark:bg-slate-900/70 dark:text-slate-100" type="password" id="delete_account_password" name="delete_account_password" autocomplete="current-password" required>
                    </label>
                <?php endif; ?>
                <label class="sm:col-span-2 inline-flex items-start gap-3 rounded-2xl border border-rose-200/80 bg-white/80 px-4 py-3 text-sm text-slate-700 dark:border-rose-900/60 dark:bg-slate-900/60 dark:text-slate-200">
                    <input class="app-checkbox mt-0.5" type="checkbox" name="delete_acknowledge" value="1" required>
                    <span>Je confirme vouloir supprimer mon compte de mani&egrave;re irr&eacute;versible et perdre l'acc&egrave;s &agrave; cet espace.</span>
                </label>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 dark:focus:ring-offset-slate-950">
                    <span class="app-icon" aria-hidden="true"><?php echo dashboard_icon_svg('trash-2'); ?></span>
                    <span>Supprimer mon compte</span>
                </button>
            </div>
        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script>
        (function () {
            const storageKey = 'theme';
            const html = document.documentElement;
            const toggle = document.getElementById('theme-toggle');

            const safeGet = () => {
                try {
                    return localStorage.getItem(storageKey);
                } catch (_error) {
                    return null;
                }
            };

            const safeSet = (value) => {
                try {
                    localStorage.setItem(storageKey, value);
                } catch (_error) {
                    /* Storage unavailable. */
                }
            };

            const applyTheme = (isDark) => {
                html.classList.toggle('dark', isDark);
                html.classList.toggle('light', !isDark);
                html.dataset.theme = isDark ? 'dark' : 'light';
                safeSet(isDark ? 'dark' : 'light');
            };

            const stored = safeGet();
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            applyTheme(stored === 'dark' || (!stored && prefersDark));

            if (toggle) {
                toggle.addEventListener('click', () => {
                    applyTheme(!html.classList.contains('dark'));
                });
            }
        })();
    </script>
</body>
</html>

