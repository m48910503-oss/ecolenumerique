<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Page de gestion des signalements (admin uniquement).
$user = require_authenticated(true, site_url('admin/reports.php'));
$roles = json_decode($user['roles_json'] ?? '[]', true) ?: [];
if (!in_array('admin', $roles, true)) {
    header('Location: ' . site_url('index.php'));
    exit;
}

$statusLabels = [
    'open' => 'Ouvert',
    'in_progress' => 'En cours',
    'resolved' => 'Résolu',
    'rejected' => 'Rejeté',
];

$errors = [];
$success = (string) flash_get('success', '');
$filter = $_GET['status'] ?? 'open';
$allowedFilters = ['all', 'open', 'in_progress', 'resolved', 'rejected'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'open';
}
$filterCourse = (int) ($_GET['course_id'] ?? 0);
$filterTeacher = (int) ($_GET['teacher_id'] ?? 0);
$searchTerm = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();
    $reportId = (int) ($_POST['report_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');
    $action = $_POST['action'] ?? 'update';
    $isAjax = isset($_POST['ajax']);

    if (!$reportId || (!isset($statusLabels[$newStatus]) && $action !== 'hide_course')) {
        $errors[] = 'Signalement introuvable ou statut invalide.';
    } else {
        $stmt = $pdo->prepare('SELECT r.*, c.title AS course_title, c.slug, c.id AS course_id, c.teacher_id, u.id AS reporter_id, u.first_name, u.last_name, u.email FROM course_reports r JOIN courses c ON c.id = r.course_id JOIN users u ON u.id = r.user_id WHERE r.id = :id LIMIT 1');
        $stmt->execute([':id' => $reportId]);
        $report = $stmt->fetch();
        if (!$report) {
            $errors[] = 'Signalement introuvable.';
        } else {
            if ($action === 'hide_course') {
                $hide = $pdo->prepare('UPDATE courses SET deleted_at = NOW() WHERE id = :cid AND deleted_at IS NULL');
                $hide->execute([':cid' => $report['course_id']]);
            } else {
                $upd = $pdo->prepare('UPDATE course_reports SET status = :status, admin_note = :note, updated_at = NOW() WHERE id = :id');
                $upd->execute([
                    ':status' => $newStatus,
                    ':note' => $adminNote !== '' ? $adminNote : null,
                    ':id' => $reportId,
                ]);
            }

            // Notifications
            $notif = $pdo->prepare('INSERT INTO notifications (user_id, title, body, url, created_at) VALUES (:uid, :title, :body, :url, NOW())');
            $notif->execute([
                ':uid' => $report['reporter_id'],
                ':title' => $action === 'hide_course' ? 'Cours masqué' : 'Signalement mis à jour',
                ':body' => $action === 'hide_course'
                    ? 'Le cours "' . ($report['course_title'] ?? 'Cours') . '" a été masqué du catalogue.'
                    : 'Votre signalement sur "' . ($report['course_title'] ?? 'Cours') . '" est maintenant : ' . ($statusLabels[$newStatus] ?? $newStatus),
                ':url' => site_url('course.php?slug=' . urlencode($report['slug'] ?? '')),
            ]);
            if (!empty($report['teacher_id'])) {
                $notif->execute([
                    ':uid' => $report['teacher_id'],
                    ':title' => $action === 'hide_course' ? 'Cours masqué' : 'Signalement sur votre cours',
                    ':body' => $action === 'hide_course'
                        ? 'Le cours "' . ($report['course_title'] ?? 'Cours') . '" a été masqué par l\'admin.'
                        : 'Le signalement sur "' . ($report['course_title'] ?? 'Cours') . '" est désormais : ' . ($statusLabels[$newStatus] ?? $newStatus),
                    ':url' => site_url('teacher/dashboard.php'),
                ]);
            }

            // Audit
            $audit = $pdo->prepare('INSERT INTO audit_logs (user_id, action, target_type, target_id, created_at) VALUES (:uid, :action, :type, :tid, NOW())');
            $audit->execute([
                ':uid' => $user['id'],
                ':action' => $action === 'hide_course' ? 'course_hide' : 'report_status_update',
                ':type' => $action === 'hide_course' ? 'course' : 'report',
                ':tid' => $action === 'hide_course' ? $report['course_id'] : $reportId,
            ]);

            $success = $action === 'hide_course'
                ? 'Cours masqué du catalogue.'
                : 'Statut mis à jour (' . ($statusLabels[$newStatus] ?? $newStatus) . ').';
        }
    }

    if (!empty($isAjax)) {
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => empty($errors),
            'errors' => $errors,
            'message' => $success,
            'status_label' => $statusLabels[$newStatus] ?? $newStatus,
        ]);
        exit;
    }
}

// POST/Redirect/GET : evite la re-soumission au refresh.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors) && $success !== '') {
    flash_set('success', $success);
    redirect_self('admin/reports.php');
}

// Compteurs par statut
$countRows = $pdo->query('SELECT status, COUNT(*) AS cnt FROM course_reports GROUP BY status')->fetchAll() ?: [];
$counts = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0];
foreach ($countRows as $row) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = (int) $row['cnt'];
    }
}

// Liste des signalements avec filtres
$sql = 'SELECT r.*, c.title AS course_title, c.slug, c.id AS course_id, c.teacher_id, u.first_name, u.last_name, u.email
        FROM course_reports r
        JOIN courses c ON c.id = r.course_id
        JOIN users u ON u.id = r.user_id';
$params = [];
$clauses = [];
if ($filter !== 'all') {
    $clauses[] = 'r.status = :status';
    $params[':status'] = $filter;
}
if ($filterCourse > 0) {
    $clauses[] = 'r.course_id = :fcourse';
    $params[':fcourse'] = $filterCourse;
}
if ($filterTeacher > 0) {
    $clauses[] = 'c.teacher_id = :fteacher';
    $params[':fteacher'] = $filterTeacher;
}
if ($searchTerm !== '') {
    $clauses[] = '(c.title LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR r.reason LIKE :search OR COALESCE(r.admin_note, \'\') LIKE :search)';
    $params[':search'] = '%' . $searchTerm . '%';
}
if ($clauses) {
    $sql .= ' WHERE ' . implode(' AND ', $clauses);
}
$sql .= ' ORDER BY r.created_at DESC';
$stmtList = $pdo->prepare($sql);
$stmtList->execute($params);
$reports = $stmtList->fetchAll() ?: [];

// Options filtres
$filterCourses = $pdo->query('SELECT DISTINCT c.id, c.title FROM course_reports r JOIN courses c ON c.id = r.course_id ORDER BY c.title ASC')->fetchAll() ?: [];
$filterTeachers = $pdo->query('SELECT DISTINCT u.id, u.first_name, u.last_name FROM course_reports r JOIN courses c ON c.id = r.course_id JOIN users u ON u.id = c.teacher_id ORDER BY u.first_name ASC')->fetchAll() ?: [];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Signalements</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/vendor/fontawesome/all.min.css?v=' . (@filemtime(__DIR__ . '/../assets/vendor/fontawesome/all.min.css') ?: time()))); ?>" data-app-icons="1">
    <link rel="stylesheet" href="<?php echo e(site_url('assets/css/app-typography.css?v=' . (@filemtime(__DIR__ . '/../assets/css/app-typography.css') ?: time()))); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo e(site_url('assets/js/theme.js?v=' . (@filemtime(__DIR__ . '/../assets/js/theme.js') ?: time()))); ?>"></script>
    <script src="<?php echo e(site_url('assets/js/reports_admin.js')); ?>" defer></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 font-body">
    <header class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl border border-slate-700 bg-slate-900 flex items-center justify-center font-bold">
                <?php if ($siteLogoUrl !== ''): ?>
                    <img src="<?php echo e($siteLogoUrl); ?>" alt="Logo" class="h-full w-full object-contain rounded-lg">
                <?php else: ?>
                    ADM
                <?php endif; ?>
            </div>
            <div>
                <p class="text-lg font-display font-semibold">Signalements</p>
                <p class="text-xs text-slate-400">Modération des alertes utilisateurs</p>
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <a class="px-4 py-2 rounded-lg border border-slate-700 hover:border-slate-500 transition" href="<?php echo e(site_url('admin/dashboard.php')); ?>">Tableau de bord</a>
            <a class="px-4 py-2 rounded-lg border border-slate-700 hover:border-slate-500 transition" href="<?php echo e(site_url('admin/courses.php')); ?>">Cours</a>
            <a class="px-4 py-2 rounded-lg border border-slate-700 hover-border-slate-500 transition" href="<?php echo e(site_url('notifications.php')); ?>">Notifications</a>
            <a class="px-4 py-2 rounded-lg border border-slate-700 hover-border-slate-500 transition" href="<?php echo e(site_url('logout.php')); ?>">Déconnexion</a>
        </div>
    </header>

    <main class="px-6 py-6 max-w-6xl mx-auto space-y-6">
        <?php if ($errors): ?>
            <div class="p-4 rounded-xl bg-red-900/40 border border-red-700 text-sm">
                <?php foreach ($errors as $err): ?>
                    <p>- <?php echo e($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="p-4 rounded-xl bg-emerald-900/40 border border-emerald-700 text-sm text-emerald-50">
                <?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <section class="flex flex-wrap items-center gap-3">
            <?php
            $filterLinks = [
                'all' => 'Tous',
                'open' => 'Ouverts (' . $counts['open'] . ')',
                'in_progress' => 'En cours (' . $counts['in_progress'] . ')',
                'resolved' => 'Résolus (' . $counts['resolved'] . ')',
                'rejected' => 'Rejetés (' . $counts['rejected'] . ')',
            ];
            foreach ($filterLinks as $key => $label):
                $active = $filter === $key;
            ?>
                <a href="<?php echo e(site_url('admin/reports.php?status=' . $key)); ?>"
                   class="px-4 py-2 rounded-lg border text-sm <?php echo $active ? 'border-emerald-500 bg-emerald-900/30' : 'border-slate-700 hover:border-slate-500'; ?>">
                    <?php echo e($label); ?>
                </a>
            <?php endforeach; ?>
        </section>

        <form method="get" class="flex flex-wrap items-end gap-3 bg-slate-900 border border-slate-800 p-4 rounded-xl text-sm">
            <div>
                <label class="block text-slate-400 mb-1" for="fcourse">Cours</label>
                <select id="fcourse" name="course_id" class="rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">
                    <option value="0">Tous</option>
                    <?php foreach ($filterCourses as $c): ?>
                        <option value="<?php echo e($c['id']); ?>" <?php echo $filterCourse === (int)$c['id'] ? 'selected' : ''; ?>><?php echo e($c['title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1" for="fteacher">Enseignant</label>
                <select id="fteacher" name="teacher_id" class="rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">
                    <option value="0">Tous</option>
                    <?php foreach ($filterTeachers as $t): ?>
                        <option value="<?php echo e($t['id']); ?>" <?php echo $filterTeacher === (int)$t['id'] ? 'selected' : ''; ?>><?php echo e(trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''))); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1" for="fstatus">Statut</label>
                <select id="fstatus" name="status" class="rounded-lg bg-slate-800 border border-slate-700 px-3 py-2">
                    <?php foreach ($filterLinks as $key => $label): ?>
                        <option value="<?php echo e($key); ?>" <?php echo $filter === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-slate-900 font-semibold">Filtrer</button>
        </form>

        <?php if (!$reports): ?>
            <p class="text-slate-400 text-sm">Aucun signalement pour le moment.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($reports as $rep): ?>
                    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm text-slate-400">Cours</p>
                                <p class="text-base font-semibold"><?php echo e($rep['course_title'] ?? ''); ?></p>
                                <p class="text-xs text-slate-500">Reporté par <?php echo e(trim(($rep['first_name'] ?? '') . ' ' . ($rep['last_name'] ?? ''))); ?> (<?php echo e($rep['email'] ?? ''); ?>) · <?php echo e($rep['created_at']); ?></p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs border" data-status-badge <?php
                                $color = [
                                    'open' => 'border-amber-500 text-amber-300 bg-amber-900/30',
                                    'in_progress' => 'border-sky-500 text-sky-200 bg-sky-900/30',
                                    'resolved' => 'border-emerald-500 text-emerald-200 bg-emerald-900/30',
                                    'rejected' => 'border-red-500 text-red-200 bg-red-900/30',
                                ][$rep['status']] ?? 'border-slate-600 text-slate-300 bg-slate-800';
                                echo $color;
                            ?>>
                                <?php echo e($statusLabels[$rep['status']] ?? $rep['status']); ?>
                            </span>
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-slate-400">Motif</p>
                            <p class="text-sm leading-relaxed"><?php echo nl2br(e($rep['reason'] ?? '')); ?></p>
                        </div>
                        <?php if (!empty($rep['admin_note'])): ?>
                            <div class="space-y-1">
                                <p class="text-sm text-slate-400">Note admin</p>
                                <p class="text-sm leading-relaxed"><?php echo nl2br(e($rep['admin_note'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <a class="px-4 py-2 rounded-lg border border-slate-700 hover-border-slate-500 transition" href="<?php echo e(site_url('admin/course_preview.php?id=' . (int) $rep['course_id'])); ?>">Voir le cours</a>
                            <a class="px-4 py-2 rounded-lg border border-slate-700 hover-border-slate-500 transition" href="<?php echo e(site_url('course.php?slug=' . urlencode($rep['slug'] ?? ''))); ?>" target="_blank" rel="noopener">Fiche publique</a>
                            <a class="px-4 py-2 rounded-lg border border-slate-700 hover-border-slate-500 transition" href="<?php echo e(site_url('admin/courses.php?course_id=' . (int) $rep['course_id'])); ?>">Historique/Audit</a>
                            <a class="px-4 py-2 rounded-lg border border-slate-700 hover-border-slate-500 transition" href="<?php echo e(site_url('notifications.php')); ?>">Notifications envoyées</a>
                        </div>
                        <form method="post" class="grid gap-3 md:grid-cols-[1fr,180px,120px] items-start report-status-form">
                            <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="report_id" value="<?php echo e((int) $rep['id']); ?>">
                            <input type="hidden" name="ajax" value="1">
                            <input type="hidden" name="action" value="update">
                            <div>
                                <label class="block text-sm text-slate-400 mb-1" for="note-<?php echo e($rep['id']); ?>">Note admin (optionnel)</label>
                                <textarea id="note-<?php echo e($rep['id']); ?>" name="admin_note" rows="2" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm"><?php echo e($rep['admin_note'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm text-slate-400 mb-1" for="status-<?php echo e($rep['id']); ?>">Statut</label>
                                <select id="status-<?php echo e($rep['id']); ?>" name="status" class="w-full rounded-lg bg-slate-800 border border-slate-700 px-3 py-2 text-sm">
                                    <?php foreach ($statusLabels as $key => $label): ?>
                                        <option value="<?php echo e($key); ?>" <?php echo $rep['status'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-slate-900 font-semibold text-sm transition">Mettre à jour</button>
                            </div>
                        </form>
                        <form method="post" class="mt-3 flex items-center gap-3">
                            <input type="hidden" name="_csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="report_id" value="<?php echo e((int) $rep['id']); ?>">
                            <input type="hidden" name="action" value="hide_course">
                            <button type="submit" class="px-4 py-2 rounded-lg bg-red-700 hover:bg-red-600 text-slate-100 text-sm transition" onclick="return confirm('Masquer ce cours du catalogue ? Les inscrits conserveront l’accès.');">Masquer du catalogue</button>
                            <span class="text-xs text-slate-500">Soft-delete : le cours disparaît du catalogue public, accès conservé pour les inscrits.</span>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
