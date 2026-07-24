<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\NotificationRouting;
use App\Core\Service;
use App\Models\ApiNotificationsFeedModel;

final class NotificationsFeedService extends Service
{
    private ApiNotificationsFeedModel $model;

    public function __construct(ApiNotificationsFeedModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed> $user
     * @param array<string,mixed> $get
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function handle(array $user, array $get): array
    {
        $userId = (int) ($user['id'] ?? 0);
        if ($userId < 1) {
            return [
                'status' => 401,
                'payload' => ['ok' => false, 'error' => 'auth'],
            ];
        }

        $afterId = max(0, (int) ($get['after_id'] ?? 0));
        $limit = max(1, min(50, (int) ($get['limit'] ?? 20)));
        $bootstrap = (string) ($get['bootstrap'] ?? '') === '1';
        $roles = NotificationRouting::normalizeRoles((string) ($user['roles_json'] ?? '[]'));
        $excludeAnnouncements = NotificationRouting::shouldExcludeAnnouncements($roles);
        $unreadCount = $this->model->countUnreadForUser($userId, $excludeAnnouncements);
        $supportUnreadReplies = 0;
        $supportUnreadNotifications = 0;
        try {
            $supportUnreadReplies = $this->model->countUnreadSupportRepliesForUser($userId);
        } catch (\Throwable $e) {
            $supportUnreadReplies = 0;
        }
        try {
            $supportUnreadNotifications = $this->model->countUnreadSupportNotificationsForUser($userId);
        } catch (\Throwable $e) {
            $supportUnreadNotifications = 0;
        }
        $supportUnreadCount = max($supportUnreadReplies, $supportUnreadNotifications);

        if ($bootstrap) {
            $latestDbId = $this->model->findLatestNotificationIdForUser($userId, $excludeAnnouncements);
            return [
                'status' => 200,
                'payload' => [
                    'ok' => true,
                    'items' => [],
                    'latest_id' => $latestDbId,
                    'latest_db_id' => $latestDbId,
                    'unread_count' => $unreadCount,
                    'support_unread_count' => $supportUnreadCount,
                    'has_more' => false,
                ],
            ];
        }

        $rows = $this->model->findAfterIdForUser($userId, $afterId, $limit, $excludeAnnouncements);
        $items = [];
        $latestSeenId = $afterId;
        foreach ($rows as $row) {
            $notificationId = (int) ($row['id'] ?? 0);
            if ($notificationId < 1) {
                continue;
            }
            if ($notificationId > $latestSeenId) {
                $latestSeenId = $notificationId;
            }
            $items[] = [
                'id' => $notificationId,
                'title' => trim((string) ($row['title'] ?? 'Notification')),
                'body' => trim((string) ($row['body'] ?? '')),
                'summary' => trim((string) ($row['summary'] ?? '')),
                'importance' => (string) ($row['importance'] ?? 'info'),
                'url' => $this->resolveNotificationUrl($row, $roles),
                'is_read' => !empty($row['is_read']),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }

        $latestDbId = $this->model->findLatestNotificationIdForUser($userId, $excludeAnnouncements);
        $hasMore = $latestDbId > $latestSeenId;

        return [
            'status' => 200,
            'payload' => [
                'ok' => true,
                'items' => $items,
                'latest_id' => $latestSeenId,
                'latest_db_id' => $latestDbId,
                'unread_count' => $unreadCount,
                'support_unread_count' => $supportUnreadCount,
                'has_more' => $hasMore,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $row
     */
    private function resolveNotificationUrl(array $row, array $roles): string
    {
        $resolved = NotificationRouting::resolveActionUrl(
            $row,
            $roles,
            fn (string $path): string => $this->siteUrl($path)
        );

        if (!NotificationRouting::hasMeaningfulTarget($resolved, $this->siteUrl('notifications'))) {
            return '';
        }

        return $resolved;
    }
}
