<?php
declare(strict_types=1);

namespace App\Services\Admin;

use App\Core\Service;
use App\Models\AdminAnnouncementsModel;
use App\Services\Admin\AdminAccessService;

final class AnnouncementsService extends Service
{
    private AdminAnnouncementsModel $model;
    private int $autoArchiveDays = 90;

    public function __construct(AdminAnnouncementsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $post
     * @param array<string,mixed> $get
     * @return array{redirectTo:?string,view:array<string,mixed>}
     */
    public function handle(array $user, string $method, array $post, array $get = []): array
    {
        $errors = [];
        $successMessage = (string) $this->flashGet('success', '');
        $schemaReady = $this->model->announcementsSchemaReady();
        $access = new AdminAccessService();
        $isGeneralAdmin = $access->isGeneralAdmin($user);

        if (!$schemaReady) {
            $errors[] = 'Migration manquante ou incomplète : appliquez database/schema_v46.sql puis database/schema_v57.sql.';
        }

        if ($schemaReady) {
            $this->model->autoArchiveOldAnnouncements($this->autoArchiveDays);
        }

        if (strtoupper($method) === 'POST') {
            $action = (string) ($post['action'] ?? '');
            $errors = $this->handleAction($action, $post, $user, $isGeneralAdmin, $schemaReady, $successMessage);
        }

        if (strtoupper($method) === 'POST' && $errors === [] && $successMessage !== '') {
            $this->flashSet('success', $successMessage);
            return [
                'redirectTo' => $this->buildAnnouncementsUrl(array_merge($get, $post)),
                'view' => [],
            ];
        }

        $filters = $this->normalizeFilters($get);
        $page = max(1, (int) ($get['page'] ?? 1));
        $perPage = 20;
        $totalItems = $schemaReady ? $this->model->countAnnouncements($filters) : 0;
        $totalPages = max(1, (int) ceil(max(1, $totalItems) / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $announcements = $schemaReady ? $this->model->fetchAnnouncements($filters, $perPage, $offset) : [];
        $announcementIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $announcements);
        $eventsByAnnouncement = $schemaReady ? $this->model->fetchAnnouncementEvents($announcementIds, 3) : [];

        $totalSent = $schemaReady ? $this->model->sumAnnouncementSentCount($filters) : 0;

        return [
            'redirectTo' => null,
            'view' => [
                'errors' => $errors,
                'successMessage' => $successMessage,
                'announcements' => $announcements,
                'announcementEvents' => $eventsByAnnouncement,
                'totalAnnouncements' => $totalItems,
                'totalSent' => $totalSent,
                'filters' => $filters,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'notificationsUrl' => $this->siteUrl('admin/notifications?scope=admin'),
                'adminDashboardUrl' => $this->siteUrl('admin/dashboard'),
                'autoArchiveDays' => $this->autoArchiveDays,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,mixed> $user
     * @return array<int,string>
     */
    private function handleAction(
        string $action,
        array $post,
        array $user,
        bool $isGeneralAdmin,
        bool $schemaReady,
        string &$successMessage
    ): array {
        $errors = [];
        if (!$schemaReady) {
            return ['Migration manquante ou incomplète : appliquez database/schema_v46.sql puis database/schema_v57.sql.'];
        }

        $action = strtolower(trim($action));

        if (in_array($action, ['save_draft', 'submit_review', 'publish'], true)) {
            $payload = $this->normalizeAnnouncementInput($post);
            $errors = $this->validateAnnouncementInput($payload, $action === 'publish');
            if ($errors !== []) {
                return $errors;
            }

            if ($action === 'publish' && !$isGeneralAdmin) {
                return ['Vous devez être administrateur général pour publier.'];
            }

            $status = match ($action) {
                'save_draft' => 'draft',
                'submit_review' => 'pending_review',
                default => 'published',
            };

            try {
                $this->model->beginTransaction();

                $announcementId = $this->model->createAnnouncement(
                    (int) ($user['id'] ?? 0),
                    $payload['title'],
                    $payload['body'],
                    $payload['action_url'],
                    $status,
                    $payload['audience'],
                    $payload['category'],
                    $payload['importance'],
                    $payload['summary'],
                    $payload['ab_enabled'],
                    $payload['title_b']
                );

                $this->model->logEvent($announcementId, (int) ($user['id'] ?? 0), 'created');

                if ($status === 'draft') {
                    $this->model->logEvent($announcementId, (int) ($user['id'] ?? 0), 'draft_saved');
                    $successMessage = 'Brouillon enregistré.';
                } elseif ($status === 'pending_review') {
                    $this->model->logEvent($announcementId, (int) ($user['id'] ?? 0), 'submitted');
                    $successMessage = 'Annonce soumise pour validation.';
                } else {
                    $sent = $this->publishAnnouncement($announcementId, $payload, (int) ($user['id'] ?? 0), $isGeneralAdmin);
                    $successMessage = 'Annonce publiée. Notifications envoyées à ' . $sent['total'] . ' utilisateur(s).';
                }

                $this->model->commit();
            } catch (\Throwable $e) {
                if ($this->model->inTransaction()) {
                    $this->model->rollBack();
                }
                $errors[] = $this->buildPersistenceError($e);
            }

            return $errors;
        }

        if (in_array($action, ['approve_publish', 'archive', 'restore', 'submit_draft', 'publish_existing'], true)) {
            $announcementId = (int) ($post['announcement_id'] ?? 0);
            if ($announcementId < 1) {
                return ['Annonce invalide.'];
            }

            $announcement = $this->model->findAnnouncementById($announcementId);
            if (!$announcement) {
                return ['Annonce introuvable.'];
            }

            if ($action === 'submit_draft') {
                $payload = $this->normalizeAnnouncementInput($announcement);
                $errors = $this->validateAnnouncementInput($payload, false);
                if ($errors !== []) {
                    return $errors;
                }

                $this->model->updateAnnouncementStatus($announcementId, 'pending_review');
                $this->model->logEvent($announcementId, (int) ($user['id'] ?? 0), 'submitted');
                $successMessage = 'Annonce soumise pour validation.';
                return [];
            }

            if ($action === 'publish_existing') {
                if (!$isGeneralAdmin) {
                    return ['Vous devez être administrateur général pour publier.'];
                }

                $payload = $this->normalizeAnnouncementInput($announcement);
                $errors = $this->validateAnnouncementInput($payload, true);
                if ($errors !== []) {
                    return $errors;
                }

                try {
                    $this->model->beginTransaction();
                    $sent = $this->publishAnnouncement($announcementId, $payload, (int) ($user['id'] ?? 0), true);
                    $successMessage = 'Annonce publiée. Notifications envoyées à ' . $sent['total'] . ' utilisateur(s).';
                    $this->model->commit();
                } catch (\Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    $errors[] = $this->buildPersistenceError($e);
                }
                return $errors;
            }

            if ($action === 'approve_publish') {
                if (!$isGeneralAdmin) {
                    return ['Vous devez être administrateur général pour valider.'];
                }

                try {
                    $this->model->beginTransaction();
                    $this->model->updateAnnouncementApproval($announcementId, (int) ($user['id'] ?? 0));
                    $sent = $this->publishAnnouncement($announcementId, $announcement, (int) ($user['id'] ?? 0), true);
                    $successMessage = 'Annonce validée et publiée. Notifications envoyées à ' . $sent['total'] . ' utilisateur(s).';
                    $this->model->commit();
                } catch (\Throwable $e) {
                    if ($this->model->inTransaction()) {
                        $this->model->rollBack();
                    }
                    $errors[] = $this->buildPersistenceError($e, 'Validation impossible pour le moment.');
                }
                return $errors;
            }

            if ($action === 'archive') {
                $this->model->updateAnnouncementArchive($announcementId, true);
                $this->model->logEvent($announcementId, (int) ($user['id'] ?? 0), 'archived');
                $successMessage = 'Annonce archivée.';
                return [];
            }

            if ($action === 'restore') {
                $this->model->updateAnnouncementArchive($announcementId, false);
                $this->model->logEvent($announcementId, (int) ($user['id'] ?? 0), 'restored');
                $successMessage = 'Annonce restaurée.';
                return [];
            }
        }

        if ($action === 'delete') {
            $announcementId = (int) ($post['announcement_id'] ?? 0);
            if ($announcementId < 1) {
                return ['Annonce invalide.'];
            }

            if (!$isGeneralAdmin) {
                return ['Vous devez être administrateur général pour supprimer définitivement une annonce.'];
            }

            $announcement = $this->model->findAnnouncementById($announcementId);
            if (!$announcement) {
                return ['Annonce introuvable.'];
            }

            try {
                $this->model->beginTransaction();
                $this->model->deleteAnnouncement($announcementId);
                $this->model->commit();
                $successMessage = 'Annonce supprimée définitivement.';
            } catch (\Throwable $e) {
                if ($this->model->inTransaction()) {
                    $this->model->rollBack();
                }
                return [$this->buildPersistenceError($e, 'Suppression impossible pour le moment.')];
            }

            return [];
        }

        return ['Action invalide.'];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{total:int,sentB:int}
     */
    private function publishAnnouncement(int $announcementId, array $payload, int $actorId, bool $isGeneralAdmin): array
    {
        $audience = (string) ($payload['audience'] ?? 'all');
        $audience = $this->normalizeAudience($audience);
        $userIds = $this->model->fetchUserIdsForAudience($audience);
        $title = (string) ($payload['title'] ?? 'Annonce');
        $titleB = trim((string) ($payload['title_b'] ?? ''));
        $abEnabled = !empty($payload['ab_enabled']) && $titleB !== '';
        $body = (string) ($payload['body'] ?? '');
        $summary = trim((string) ($payload['summary'] ?? ''));
        if ($summary === '') {
            $summary = $this->buildSummary($body);
        }
        $importance = (string) ($payload['importance'] ?? 'info');
        $actionUrl = (string) ($payload['action_url'] ?? '');

        $sentTotal = 0;
        $sentB = 0;

        if ($abEnabled) {
            $groupA = [];
            $groupB = [];
            foreach ($userIds as $userId) {
                if ($userId % 2 === 0) {
                    $groupB[] = $userId;
                } else {
                    $groupA[] = $userId;
                }
            }
            $sentA = $this->model->insertNotificationsForUsers($groupA, $title, $body, $actionUrl, $summary, $importance, $announcementId, 'A');
            $sentB = $this->model->insertNotificationsForUsers($groupB, $titleB, $body, $actionUrl, $summary, $importance, $announcementId, 'B');
            $sentTotal = $sentA + $sentB;
        } else {
            $sentTotal = $this->model->insertNotificationsForUsers($userIds, $title, $body, $actionUrl, $summary, $importance, $announcementId, 'A');
        }

        $this->model->updateAnnouncementSentCounts($announcementId, $sentTotal, $sentB);
        $approverId = $isGeneralAdmin ? $actorId : null;
        $this->model->updateAnnouncementPublish($announcementId, $actorId, $approverId);
        $this->model->logEvent($announcementId, $actorId, 'published', 'Audience: ' . $audience);

        return ['total' => $sentTotal, 'sentB' => $sentB];
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function normalizeAnnouncementInput(array $post): array
    {
        $title = trim((string) ($post['title'] ?? ''));
        $titleB = '';
        $body = trim((string) ($post['body'] ?? ''));
        $summary = trim((string) ($post['summary'] ?? ''));
        $actionUrl = $this->normalizeActionUrl((string) ($post['action_url'] ?? ''));
        $audience = $this->normalizeAudience((string) ($post['audience'] ?? 'all'));
        $category = $this->normalizeCategory((string) ($post['category'] ?? 'general'));
        $importance = $this->normalizeImportance((string) ($post['importance'] ?? 'info'));
        $abEnabled = false;

        if ($summary === '') {
            $summary = $this->buildSummary($body);
        }

        return [
            'title' => $title,
            'title_b' => $titleB,
            'body' => $body,
            'summary' => $summary,
            'action_url' => $actionUrl,
            'audience' => $audience,
            'category' => $category,
            'importance' => $importance,
            'ab_enabled' => $abEnabled,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,string>
     */
    private function validateAnnouncementInput(array $payload, bool $mustPublish): array
    {
        $errors = [];
        $title = (string) ($payload['title'] ?? '');
        $body = (string) ($payload['body'] ?? '');
        $summary = (string) ($payload['summary'] ?? '');

        if ($title === '') {
            $errors[] = 'Le titre de l\'annonce est obligatoire.';
        } elseif ($this->strLen($title) > 190) {
            $errors[] = 'Le titre est trop long (190 caractères max).';
        }

        if ($body === '') {
            $errors[] = 'Le message de l\'annonce est obligatoire.';
        }

        if ($summary !== '' && $this->strLen($summary) > 140) {
            $errors[] = 'Le résumé est trop long (140 caractères max).';
        }

        return $errors;
    }

    /**
     * @param array<string,mixed> $get
     * @return array<string,string>
     */
    private function normalizeFilters(array $get): array
    {
        return [
            'status' => strtolower(trim((string) ($get['status'] ?? ''))),
            'audience' => strtolower(trim((string) ($get['audience'] ?? ''))),
            'category' => strtolower(trim((string) ($get['category'] ?? ''))),
            'importance' => strtolower(trim((string) ($get['importance'] ?? ''))),
            'search' => trim((string) ($get['q'] ?? '')),
        ];
    }

    private function normalizeActionUrl(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $this->siteUrl('notifications?scope=admin');
        }
        if (preg_match('~^https?://~i', $value) === 1) {
            return $value;
        }
        return $this->siteUrl(ltrim($value, '/'));
    }

    private function normalizeAudience(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['all', 'student', 'teacher'], true)) {
            return 'all';
        }
        return $value;
    }

    private function normalizeCategory(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['general', 'system', 'learning', 'support', 'promo'], true)) {
            return 'general';
        }
        return $value;
    }

    private function normalizeImportance(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['info', 'important', 'urgent'], true)) {
            return 'info';
        }
        return $value;
    }

    private function buildSummary(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }
        $summary = $this->truncate($body, 140);
        return trim($summary);
    }

    /**
     * @param array<string,mixed> $state
     */
    private function buildAnnouncementsUrl(array $state): string
    {
        $filters = $this->normalizeFilters($state);
        $params = [];

        if ($filters['search'] !== '') {
            $params['q'] = $filters['search'];
        }
        if ($filters['status'] !== '') {
            $params['status'] = $filters['status'];
        }
        if ($filters['audience'] !== '') {
            $params['audience'] = $filters['audience'];
        }
        if ($filters['category'] !== '') {
            $params['category'] = $filters['category'];
        }
        if ($filters['importance'] !== '') {
            $params['importance'] = $filters['importance'];
        }

        $page = max(1, (int) ($state['page'] ?? 1));
        if ($page > 1) {
            $params['page'] = $page;
        }

        $query = http_build_query($params);
        return $this->siteUrl('admin/announcements') . ($query !== '' ? '?' . $query : '');
    }

    private function buildPersistenceError(\Throwable $error, string $fallback = 'Publication impossible pour le moment.'): string
    {
        if ($error instanceof \PDOException) {
            $sqlState = (string) $error->getCode();
            if (in_array($sqlState, ['42S22', '42S02'], true)) {
                return 'Schéma base de données incomplet : appliquez database/schema_v46.sql puis database/schema_v57.sql.';
            }
            if ($sqlState === '23000') {
                return 'Conflit de base de données : vérifiez les clés étrangères ou les données manquantes.';
            }
        }

        return $fallback;
    }

    private function strLen(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }

    private function truncate(string $value, int $maxChars): string
    {
        if ($maxChars < 1) {
            return '';
        }
        if ($this->strLen($value) <= $maxChars) {
            return $value;
        }
        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, 0, $maxChars, 'UTF-8');
        }
        return substr($value, 0, $maxChars);
    }
}
?>
