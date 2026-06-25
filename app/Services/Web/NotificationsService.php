<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\NotificationRouting;
use App\Core\Service;
use App\Models\WebNotificationsModel;

final class NotificationsService extends Service
{
    private WebNotificationsModel $notifications;

    public function __construct(WebNotificationsModel $notifications)
    {
        $this->notifications = $notifications;
    }

    public function handle(int $userId, string $method, array $get, array $post): array
    {
        $success = (string) $this->flashGet('success', '');
        $redirectTo = null;
        $current = $this->currentUser();
        $roles = NotificationRouting::normalizeRoles((string) ($current['roles_json'] ?? '[]'));
        $excludeAnnouncements = NotificationRouting::shouldExcludeAnnouncements($roles);
        $scope = $this->normalizeScope((string) ($get['scope'] ?? 'all'));
        $space = strtolower(trim((string) ($get['space'] ?? '')));
        $page = max(1, (int) ($get['page'] ?? 1));
        $query = strtolower(trim((string) ($get['q'] ?? '')));
        $perPage = 10;

        // Click "open": mark as read and redirect to target if present.
        if (isset($get['open'])) {
            $notificationId = (int) $get['open'];
            if ($notificationId > 0) {
                $notification = $this->notifications->findByIdForUser($notificationId, $userId, $excludeAnnouncements);
                if ($notification) {
                    $wasRead = !empty($notification['is_read']);
                    if (!$wasRead) {
                        $this->notifications->markRead($notificationId, $userId);
                        $this->incrementAnnouncementRead($notification, 1);
                    }
                    if (empty($notification['clicked_at'])) {
                        $this->notifications->markClicked($notificationId, $userId);
                        $this->incrementAnnouncementClick($notification, 1);
                    }
                    $url = $this->resolveNotificationUrl($notification, $roles, $space);
                    if (NotificationRouting::hasMeaningfulTarget($url, $this->siteUrl('notifications')) && strpos($url, 'notifications?open=') === false) {
                        return ['redirect' => $url];
                    }
                }
            }
            return ['redirect' => $this->buildNotificationsUrl($scope, $space, $page, $query)];
        }

        if (strtoupper($method) === 'POST') {
            $action = (string) ($post['action'] ?? '');
            if ($action === 'mark_all_read') {
                $counts = $excludeAnnouncements ? [] : $this->notifications->fetchUnreadAnnouncementCounts($userId);
                $this->notifications->markAllRead($userId, $excludeAnnouncements);
                if (!$excludeAnnouncements) {
                    foreach ($counts as $row) {
                        $announcementId = (int) ($row['announcement_id'] ?? 0);
                        if ($announcementId < 1) {
                            continue;
                        }
                        $delta = (int) ($row['total'] ?? 0);
                        if ($delta > 0) {
                            $this->notifications->incrementAnnouncementReadCount($announcementId, $delta);
                        }
                    }
                }
                $success = 'Notifications marquées comme lues.';
            } elseif ($action === 'mark_read') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $notification = $this->notifications->findByIdForUser($notificationId, $userId, $excludeAnnouncements);
                    if ($notification && empty($notification['is_read'])) {
                        $this->notifications->markRead($notificationId, $userId);
                        $this->incrementAnnouncementRead($notification, 1);
                    }
                    $success = 'Notification marquée comme lue.';
                }
            } elseif ($action === 'mark_unread') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $this->notifications->markUnread($notificationId, $userId);
                    $success = 'Notification marquée comme non lue.';
                }
            } elseif ($action === 'mark_important') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $this->notifications->setImportanceForUser($notificationId, $userId, 'important');
                    $success = 'Notification marquée comme importante.';
                }
            } elseif ($action === 'clear_importance') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $this->notifications->setImportanceForUser($notificationId, $userId, 'info');
                    $success = "L'importance de la notification a été retirée.";
                }
            } elseif ($action === 'archive') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $notification = $this->notifications->findByIdForUser($notificationId, $userId, $excludeAnnouncements);
                    if ($notification && empty($notification['is_read'])) {
                        $this->incrementAnnouncementRead($notification, 1);
                    }
                    $this->notifications->archiveByIdForUser($notificationId, $userId);
                    $success = 'Notification archivée.';
                }
            } elseif ($action === 'restore') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $this->notifications->restoreByIdForUser($notificationId, $userId);
                    $success = 'Notification restaurée.';
                }
            } elseif ($action === 'delete') {
                $notificationId = (int) ($post['notif_id'] ?? 0);
                if ($notificationId > 0) {
                    $this->notifications->deleteByIdForUser($notificationId, $userId);
                    $success = 'Notification supprimée.';
                }
            }

            $this->flashSet('success', $success);
            $redirectScope = $this->normalizeScope((string) ($post['scope'] ?? $scope));
            $redirectSpace = strtolower(trim((string) ($post['space'] ?? $space)));
            $redirectPage = max(1, (int) ($post['page'] ?? $page));
            $redirectTo = $this->buildNotificationsUrl($redirectScope, $redirectSpace, $redirectPage, $query);
        }

        $notifications = $this->notifications->findRecentForUser($userId, 5000, $excludeAnnouncements);
        $unreadCount = $this->notifications->countUnreadForUser($userId, $excludeAnnouncements);
        $notifications = $this->attachResolvedLinks($notifications, $roles, $space);
        $latestNotification = [];
        foreach ($notifications as $candidate) {
            if (!$this->isArchived($candidate)) {
                $latestNotification = $candidate;
                break;
            }
        }
        if ($latestNotification === [] && is_array($notifications[0] ?? null)) {
            $latestNotification = $notifications[0];
        }
        if ($latestNotification !== []) {
            $latestNotification = $this->applyStatusMetadata($latestNotification);
        }
        $notifications = $this->decorateNotifications($notifications);
        $scopeCounts = $this->buildScopeCounts($notifications, $unreadCount);
        $actionableNotifications = array_values(array_slice(array_values(array_filter(
            $notifications,
            fn (array $notification): bool => (string) ($notification['status_key'] ?? '') === 'todo'
        )), 0, 3));

        if ($query !== '') {
            $notifications = array_values(array_filter(
                $notifications,
                fn (array $notification): bool => $this->matchesQuery($notification, $query)
            ));
        }

        if ($scope !== 'all') {
            $notifications = array_values(array_filter(
                $notifications,
                fn (array $notification): bool => $this->matchesScope($notification, $scope)
            ));
        }

        $totalItems = count($notifications);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;
        $notifications = array_slice($notifications, $offset, $perPage);

        return [
            'redirect' => $redirectTo,
            'success' => $success,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'scope' => $scope,
            'scopeCounts' => $scopeCounts,
            'latestNotification' => $latestNotification,
            'actionableNotifications' => $actionableNotifications,
            'page' => $page,
            'perPage' => $perPage,
            'totalItems' => $totalItems,
            'totalPages' => $totalPages,
        ];
    }

    private function attachResolvedLinks(array $notifications, array $roles, string $space): array
    {
        foreach ($notifications as $index => $notification) {
            $resolvedUrl = $this->resolveNotificationUrl($notification, $roles, $space);
            $notifications[$index]['resolved_url'] = $resolvedUrl;
            $notifications[$index]['has_open_action'] = NotificationRouting::hasMeaningfulTarget($resolvedUrl, $this->siteUrl('notifications'));
            $notifications[$index]['scope_key'] = $this->classifyScope($notifications[$index]);
        }
        return $notifications;
    }

    /**
     * @param array<int,array<string,mixed>> $notifications
     * @return array<int,array<string,mixed>>
     */
    private function decorateNotifications(array $notifications): array
    {
        foreach ($notifications as $index => $notification) {
            $notifications[$index] = $this->applyStatusMetadata($notification);
        }

        usort($notifications, function (array $left, array $right): int {
            $leftRank = (int) ($left['status_rank'] ?? 2);
            $rightRank = (int) ($right['status_rank'] ?? 2);
            if ($leftRank !== $rightRank) {
                return $leftRank <=> $rightRank;
            }

            $leftTs = $this->notificationTimestamp($left);
            $rightTs = $this->notificationTimestamp($right);
            if ($leftTs !== $rightTs) {
                return $rightTs <=> $leftTs;
            }

            return (int) ($right['id'] ?? 0) <=> (int) ($left['id'] ?? 0);
        });

        return $notifications;
    }

    /**
     * @param array<string,mixed> $notification
     * @return array<string,mixed>
     */
    private function applyStatusMetadata(array $notification): array
    {
        $isArchived = $this->isArchived($notification);
        $needsAction = $this->needsAction($notification);
        $isRead = !empty($notification['is_read']);

        if ($isArchived) {
            $statusKey = 'archived';
            $statusLabel = 'Archivée';
            $statusRank = 3;
        } elseif ($needsAction) {
            $statusKey = 'todo';
            $statusLabel = 'À traiter';
            $statusRank = 0;
        } elseif (!$isRead) {
            $statusKey = 'unread';
            $statusLabel = 'Non lue';
            $statusRank = 1;
        } else {
            $statusKey = 'read';
            $statusLabel = 'Lue';
            $statusRank = 2;
        }

        $notification['is_archived'] = $isArchived;
        $notification['needs_action'] = $needsAction;
        $notification['is_priority'] = $this->isImportant($notification) || $needsAction;
        $notification['status_key'] = $statusKey;
        $notification['status_label'] = $statusLabel;
        $notification['status_rank'] = $statusRank;

        return $notification;
    }

    private function resolveNotificationUrl(array $notification, array $roles, string $space = ''): string
    {
        return NotificationRouting::resolveActionUrl(
            $notification,
            $roles,
            fn (string $path): string => $this->absUrl($path),
            $space
        );
    }

    private function normalizeScope(string $scope): string
    {
        $scope = strtolower(trim($scope));
        $allowed = ['all', 'unread', 'read', 'todo', 'archived', 'important', 'support', 'learning', 'business', 'admin', 'other'];
        if (!in_array($scope, $allowed, true)) {
            return 'all';
        }
        return $scope;
    }

    /**
     * @param array<int,array<string,mixed>> $notifications
     * @return array<string,int>
     */
    private function buildScopeCounts(array $notifications, int $unreadCount): array
    {
        $counts = [
            'all' => count($notifications),
            'unread' => $unreadCount,
            'read' => 0,
            'todo' => 0,
            'archived' => 0,
            'important' => 0,
            'support' => 0,
            'learning' => 0,
            'business' => 0,
            'admin' => 0,
            'other' => 0,
        ];

        foreach ($notifications as $notification) {
            if ($this->isImportant($notification)) {
                $counts['important'] += 1;
            }
            $statusKey = (string) ($notification['status_key'] ?? '');
            if ($statusKey === 'archived' || $this->isArchived($notification)) {
                $counts['archived'] += 1;
            }
            if ($statusKey === 'todo' || $this->needsAction($notification)) {
                $counts['todo'] += 1;
            }
            if ($statusKey === 'read') {
                $counts['read'] += 1;
            } elseif ($statusKey === '' && !empty($notification['is_read']) && !$this->needsAction($notification) && !$this->isArchived($notification)) {
                $counts['read'] += 1;
            } else {
                // unread total is tracked separately so the tab count can stay aligned
                // with the top KPI even when "to process" overlaps with unread items.
            }
            $scope = (string) ($notification['scope_key'] ?? $this->classifyScope($notification));
            if (!array_key_exists($scope, $counts)) {
                $scope = 'other';
            }
            $counts[$scope] += 1;
        }

        return $counts;
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function classifyScope(array $notification): string
    {
        $title = strtolower((string) ($notification['title'] ?? ''));
        $body = strtolower((string) ($notification['body'] ?? ''));
        $url = strtolower((string) (($notification['resolved_url'] ?? '') ?: ($notification['url'] ?? '')));
        $text = $title . ' ' . $body . ' ' . $url;

        $supportKeywords = ['support', 'ticket', 'agent'];
        foreach ($supportKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'support';
            }
        }

        if (str_contains($url, '/admin') || str_contains($text, 'admin')) {
            return 'admin';
        }

        $learningKeywords = ['cours', 'lesson', 'lecon', 'examen', 'certificat', 'meeting', 'rendez-vous', 'note'];
        foreach ($learningKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'learning';
            }
        }

        $businessKeywords = ['commande', 'paiement', 'achat', 'vente', 'promo', 'promotion', 'coupon', 'retrait'];
        foreach ($businessKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'business';
            }
        }

        return 'other';
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function matchesScope(array $notification, string $scope): bool
    {
        if ($scope === 'all') {
            return true;
        }
        if ($scope === 'unread') {
            return empty($notification['is_read']) && !$this->isArchived($notification);
        }
        if ($scope === 'read') {
            return (string) ($notification['status_key'] ?? '') === 'read';
        }
        if ($scope === 'todo') {
            return (string) ($notification['status_key'] ?? '') === 'todo' || $this->needsAction($notification);
        }
        if ($scope === 'archived') {
            return (string) ($notification['status_key'] ?? '') === 'archived' || $this->isArchived($notification);
        }
        if ($scope === 'important') {
            return $this->isImportant($notification);
        }

        $notifScope = (string) ($notification['scope_key'] ?? $this->classifyScope($notification));
        return $notifScope === $scope;
    }

    private function buildNotificationsUrl(string $scope, string $space, int $page = 1, string $query = ''): string
    {
        $params = [];
        if ($scope !== 'all') {
            $params['scope'] = $scope;
        }
        if (in_array($space, ['teacher', 'student'], true)) {
            $params['space'] = $space;
        }
        if ($page > 1) {
            $params['page'] = $page;
        }
        if ($query !== '') {
            $params['q'] = $query;
        }
        $query = http_build_query($params);
        return $this->siteUrl('notifications') . ($query !== '' ? '?' . $query : '');
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function matchesQuery(array $notification, string $query): bool
    {
        $query = strtolower(trim($query));
        if ($query === '') {
            return true;
        }

        $haystack = strtolower(trim(
            (string) ($notification['title'] ?? '') . ' ' .
            (string) ($notification['summary'] ?? '') . ' ' .
            (string) ($notification['body'] ?? '') . ' ' .
            (string) ($notification['scope_key'] ?? '') . ' ' .
            (string) ($notification['importance'] ?? '') . ' ' .
            (string) ($notification['status_label'] ?? '') . ' ' .
            (string) ($notification['resolved_url'] ?? '')
        ));

        return str_contains($haystack, $query);
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function isImportant(array $notification): bool
    {
        $importance = strtolower((string) ($notification['importance'] ?? 'info'));
        return in_array($importance, ['important', 'urgent'], true);
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function isArchived(array $notification): bool
    {
        return !empty($notification['is_archived']) || !empty($notification['archived_at']);
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function needsAction(array $notification): bool
    {
        if ($this->isArchived($notification)) {
            return false;
        }

        if ($this->isImportant($notification)) {
            return true;
        }

        $text = strtolower(trim(
            (string) ($notification['title'] ?? '') . ' ' .
            (string) ($notification['summary'] ?? '') . ' ' .
            (string) ($notification['body'] ?? '')
        ));

        foreach ([
            'action requise',
            'a traiter',
            'à traiter',
            'a valider',
            'à valider',
            'a confirmer',
            'à confirmer',
            'a completer',
            'à compléter',
            'requiert votre action',
            'veuillez repondre',
            'veuillez répondre',
            'reponse attendue',
            'réponse attendue',
        ] as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function notificationTimestamp(array $notification): int
    {
        $createdAt = (string) ($notification['created_at'] ?? '');
        $ts = $createdAt !== '' ? strtotime($createdAt) : false;
        return $ts ? (int) $ts : 0;
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function incrementAnnouncementRead(array $notification, int $delta): void
    {
        $announcementId = (int) ($notification['announcement_id'] ?? 0);
        if ($announcementId < 1) {
            return;
        }
        $this->notifications->incrementAnnouncementReadCount($announcementId, $delta);
    }

    /**
     * @param array<string,mixed> $notification
     */
    private function incrementAnnouncementClick(array $notification, int $delta): void
    {
        $announcementId = (int) ($notification['announcement_id'] ?? 0);
        if ($announcementId < 1) {
            return;
        }
        $this->notifications->incrementAnnouncementClickCount($announcementId, $delta);
    }

}

