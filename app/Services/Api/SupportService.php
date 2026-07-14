<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;

use App\Models\ApiSupportModel;
use App\Services\Admin\AdminAccessService;
use App\Services\Support\SupportGuestContextService;
use App\Services\Support\SupportAccessService;
use App\Services\Support\SupportStorageService;

final class SupportService extends Service
{
    private const CATEGORY_LABELS = [
        'technical' => 'Problème technique',
        'billing' => 'Paiement',
        'certificate' => 'Certificat',
        'course' => 'Accès au cours',
        'lesson' => 'Cours / contenu',
        'meeting' => 'Rendez-vous',
        'withdrawal' => 'Retrait / revenus',
        'other' => 'Autre',
    ];

    private const DEFAULT_SUBJECTS = [
        'billing' => 'Paiement non validé',
        'certificate' => 'Certificat indisponible',
        'course' => 'Problème d’accès au cours',
        'lesson' => 'Problème d’accès au cours',
        'technical' => 'Problème technique',
        'meeting' => 'Rendez-vous à reprogrammer',
        'withdrawal' => 'Retrait en attente',
        'other' => 'Demande d’assistance',
    ];

    private ApiSupportModel $model;
    private SupportAccessService $access;
    private SupportGuestContextService $guestContext;
    private SupportStorageService $storage;
    private bool $debug;

    public function __construct(
        ApiSupportModel $model,
        ?SupportAccessService $access = null,
        ?SupportGuestContextService $guestContext = null,
        ?SupportStorageService $storage = null
    )
    {
        $this->model = $model;
        $this->access = $access ?? new SupportAccessService();
        $this->guestContext = $guestContext ?? new SupportGuestContextService();
        $this->storage = $storage ?? new SupportStorageService();
        $baseUrl = defined('APP_BASE_URL') ? (string) APP_BASE_URL : '';
        $this->debug = strpos($baseUrl, 'localhost') !== false
            || strpos($baseUrl, '127.0.0.1') !== false;
    }

    public function handleConversations(?array $user, array $guestContext, string $method, array $query, array $data, array $files): array
    {
        $isStaff = $this->resolveStaffAccess($user);

        if ($method === 'GET') {
            return $this->listConversations($user, $guestContext, $isStaff, $query);
        }
        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method']];
        }

        $action = (string) ($data['action'] ?? 'create');
        if ($action === 'create') {
            return $this->createConversation($user, $guestContext, $data, $files);
        }

        if (!$isStaff) {
            return ['status' => 403, 'payload' => ['ok' => false, 'error' => 'forbidden']];
        }

        $conversationId = (int) ($data['conversation_id'] ?? 0);
        if ($conversationId < 1) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_conversation']];
        }

        if ($action === 'assign') {
            $assignTo = (int) ($data['assign_to'] ?? (int) ($user['id'] ?? 0));
            $this->model->updateConversationAssign($conversationId, $assignTo > 0 ? $assignTo : null);
            return ['status' => 200, 'payload' => ['ok' => true]];
        }
        if ($action === 'close') {
            $this->model->updateConversationStatus($conversationId, 'closed');
            return ['status' => 200, 'payload' => ['ok' => true]];
        }
        if ($action === 'reopen') {
            $this->model->updateConversationStatus($conversationId, 'waiting_support');
            return ['status' => 200, 'payload' => ['ok' => true]];
        }

        return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'bad_action']];
    }

    public function handleMessages(?array $user, array $guestContext, string $method, array $query, array $data, array $files): array
    {
        $isStaff = $this->resolveStaffAccess($user);

        if ($method === 'GET') {
            $conversationId = (int) ($query['conversation_id'] ?? 0);
            $sinceId = (int) ($query['since_id'] ?? 0);
            if ($conversationId < 1) {
                return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_conversation']];
            }
            return $this->listMessages($user, $guestContext, $isStaff, $conversationId, $sinceId);
        }

        if ($method !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method']];
        }

        $conversationId = (int) ($data['conversation_id'] ?? 0);
        $body = trim((string) ($data['body'] ?? ''));
        if ($conversationId < 1) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_conversation']];
        }
        if (!$user) {
            $this->guestContext->updateFromRequest($data);
        }

        $conversation = $this->model->findConversationById($conversationId);
        if (!$conversation) {
            return ['status' => 404, 'payload' => ['ok' => false, 'error' => 'not_found']];
        }
        if (!$this->canAccessConversation($conversation, $user, $guestContext, $isStaff)) {
            return ['status' => 403, 'payload' => ['ok' => false, 'error' => 'forbidden']];
        }

        $upload = $this->storage->handleUploads($files['attachments'] ?? null, 5 * 1024 * 1024);
        if (!$upload['ok']) {
            return [
                'status' => 400,
                'payload' => ['ok' => false, 'error' => 'upload', 'errors' => $upload['errors']],
            ];
        }
        if ($body === '' && empty($upload['attachments'])) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_body']];
        }

        $storedFiles = array_column($upload['attachments'], 'file_path');
        try {
            $this->model->beginTransaction();

            $senderId = (int) ($user['id'] ?? 0);
            $messageId = $this->model->insertMessage($conversationId, $senderId > 0 ? $senderId : null, $body);
            $this->model->insertAttachments($messageId, $upload['attachments']);

            $newStatus = $isStaff ? 'waiting_user' : 'waiting_support';
            $assignedTo = $conversation['assigned_to'] ?? null;
            if ($isStaff && empty($assignedTo)) {
                $assignedTo = (int) $user['id'];
            }
            $this->model->updateConversationAfterMessage(
                $conversationId,
                $newStatus,
                $assignedTo ? (int) $assignedTo : null
            );
            if ($senderId > 0) {
                $this->model->upsertConversationRead($conversationId, $senderId, $messageId);
            }

            $this->model->commit();
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $this->cleanupStoredFiles($storedFiles);
            return $this->buildDbError($e);
        }

        if ($isStaff) {
            $this->safeCreateNotification(
                (int) ($conversation['created_by'] ?? 0),
                'Support: nouveau message',
                "Un agent support a r\u{00e9}pondu \u{00e0} votre demande.",
                'support?conv=' . $conversationId
            );
        } else {
            $staffIds = $this->model->listStaffIds();
            foreach ($staffIds as $staffId) {
                $this->safeCreateNotification(
                    (int) $staffId,
                    'Support: nouveau message',
                    "Nouvelle r\u{00e9}ponse utilisateur sur une demande.",
                    'admin/support?conv=' . $conversationId
                );
            }
        }

        return ['status' => 200, 'payload' => ['ok' => true, 'message_id' => $messageId]];
    }

    private function listConversations(?array $user, array $guestContext, bool $isStaff, array $query): array
    {
        $scope = (string) ($query['scope'] ?? 'mine');
        $status = trim((string) ($query['status'] ?? ''));
        $assigned = trim((string) ($query['assigned'] ?? ''));
        $limit = (int) ($query['limit'] ?? 500);
        if ($limit < 1 || $limit > 500) {
            $limit = 500;
        }

        try {
            $rows = $this->model->listConversations(
                (int) ($user['id'] ?? 0),
                $isStaff,
                $scope,
                $status,
                $assigned,
                $limit,
                $user ? null : (string) ($guestContext['viewer_key'] ?? '')
            );
        } catch (\Throwable $e) {
            $payload = ['ok' => false, 'error' => 'db'];
            if ($this->debug) {
                $payload['details'] = $e->getMessage();
            }
            return ['status' => 500, 'payload' => $payload];
        }

        $conversations = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $creatorName = trim((string) ($row['creator_first'] ?? '') . ' ' . (string) ($row['creator_last'] ?? ''));
            if ($creatorName === '') {
                $creatorName = trim((string) ($row['guest_name'] ?? ''));
            }
            if ($creatorName === '') {
                $creatorName = 'Visiteur';
            }
            $assignedName = trim((string) ($row['assigned_first'] ?? '') . ' ' . (string) ($row['assigned_last'] ?? ''));
            $category = $this->resolveCategory(
                (string) ($row['category'] ?? ''),
                (string) ($row['subject'] ?? ''),
                (string) ($row['last_message_body'] ?? '')
            );
            if ($category === 'other' && $this->isWeakSubject((string) ($row['subject'] ?? ''))) {
                $category = $this->legacyCategoryForTicket($id);
            }
            $subject = $this->normalizeSubjectForDisplay((string) ($row['subject'] ?? ''), $category, $id);
            $conversations[] = [
                'id' => $id,
                'subject' => $subject,
                'status' => (string) ($row['status'] ?? ''),
                'category' => $category,
                'category_label' => self::CATEGORY_LABELS[$category] ?? self::CATEGORY_LABELS['other'],
                'created_by' => (int) ($row['created_by'] ?? 0),
                'created_by_name' => $creatorName !== '' ? $creatorName : 'Utilisateur',
                'guest_name' => (string) ($row['guest_name'] ?? ''),
                'guest_email' => (string) ($row['guest_email'] ?? ''),
                'assigned_to' => $row['assigned_to'] !== null ? (int) $row['assigned_to'] : null,
                'assigned_to_name' => $assignedName,
                'last_message_at' => (string) ($row['last_message_at'] ?? ''),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'last_message_id' => (int) ($row['last_message_id'] ?? 0),
                'last_message_body' => (string) ($row['last_message_body'] ?? ''),
                'unread_count' => (int) ($row['unread_count'] ?? 0),
            ];
        }

        return ['status' => 200, 'payload' => ['ok' => true, 'conversations' => $conversations]];
    }

    private function createConversation(?array $user, array $guestContext, array $data, array $files): array
    {
        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $category = $this->resolveCategory((string) ($data['category'] ?? ''), $subject, $body);

        if (!$user) {
            $guestName = $this->guestContext->sanitizeName((string) ($data['guest_name'] ?? ($guestContext['guest_name'] ?? '')));
            $guestEmailRaw = trim((string) ($data['guest_email'] ?? ($guestContext['guest_email'] ?? '')));
            $guestEmail = $this->guestContext->sanitizeEmail($guestEmailRaw);
            if ($guestEmailRaw !== '' && $guestEmail === '') {
                return ['status' => 422, 'payload' => ['ok' => false, 'error' => 'invalid_guest_email']];
            }
            $this->guestContext->setProfile($guestName, $guestEmail);
            $guestContext = $this->guestContext->ensure();
        }

        $upload = $this->storage->handleUploads($files['attachments'] ?? null, 5 * 1024 * 1024);
        if (!$upload['ok']) {
            return [
                'status' => 400,
                'payload' => ['ok' => false, 'error' => 'upload', 'errors' => $upload['errors']],
            ];
        }

        if ($subject === '') {
            $seed = self::DEFAULT_SUBJECTS[$category] ?? self::DEFAULT_SUBJECTS['other'];
            $seed = preg_replace('/\s+/', ' ', $seed ?? '');
            $subject = trim(substr((string) $seed, 0, 80));
            if ($subject === '') {
                $subject = 'Support ' . date('Y-m-d');
            }
        }
        $subject = $this->normalizeSubjectForDisplay($subject, $category, 0);
        if ($body === '' && empty($upload['attachments'])) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'missing_body']];
        }

        $storedFiles = array_column($upload['attachments'], 'file_path');
        try {
            $this->model->beginTransaction();

            $conversationId = $this->model->insertConversation(
                $user ? (int) $user['id'] : null,
                $subject,
                'waiting_support',
                $category,
                $user ? null : (string) ($guestContext['viewer_key'] ?? ''),
                $user ? null : (string) ($guestContext['guest_name'] ?? ''),
                $user ? null : (string) ($guestContext['guest_email'] ?? '')
            );
            $messageId = $this->model->insertMessage($conversationId, $user ? (int) $user['id'] : null, $body);
            $this->model->insertAttachments($messageId, $upload['attachments']);
            if ($user) {
                $this->model->upsertConversationRead($conversationId, (int) $user['id'], $messageId);
            }

            $this->model->commit();
        } catch (\Throwable $e) {
            if ($this->model->inTransaction()) {
                $this->model->rollBack();
            }
            $this->cleanupStoredFiles($storedFiles);
            return $this->buildDbError($e);
        }

        $currentUserId = (int) ($user['id'] ?? 0);
        $staffIds = array_values(array_filter(
            $this->model->listStaffIds(),
            static fn(int $staffId): bool => $staffId !== $currentUserId
        ));
        foreach ($staffIds as $staffId) {
            $this->safeCreateNotification(
                $staffId,
                'Nouvelle demande support',
                'Sujet: ' . $subject,
                'admin/support?conv=' . $conversationId
            );
        }

        if (!$user) {
            $this->guestContext->setActiveConversationId($conversationId);
        }

        return [
            'status' => 200,
            'payload' => ['ok' => true, 'conversation_id' => $conversationId, 'message_id' => $messageId],
        ];
    }

    private function listMessages(?array $user, array $guestContext, bool $isStaff, int $conversationId, int $sinceId): array
    {
        $conversation = $this->model->findConversationById($conversationId);
        if (!$conversation) {
            return ['status' => 404, 'payload' => ['ok' => false, 'error' => 'not_found']];
        }
        if (!$this->canAccessConversation($conversation, $user, $guestContext, $isStaff)) {
            return ['status' => 403, 'payload' => ['ok' => false, 'error' => 'forbidden']];
        }

        try {
            $messages = $this->model->listMessages($conversationId, $sinceId);
        } catch (\Throwable $e) {
            $payload = ['ok' => false, 'error' => 'db'];
            if ($this->debug) {
                $payload['details'] = $e->getMessage();
            }
            return ['status' => 500, 'payload' => $payload];
        }

        $messageIds = array_map(
            static fn(array $message): int => (int) ($message['id'] ?? 0),
            $messages
        );
        $attachmentsByMessage = [];
        foreach ($this->model->listAttachmentsByMessageIds($messageIds) as $attachment) {
            $messageId = (int) ($attachment['message_id'] ?? 0);
            if (!isset($attachmentsByMessage[$messageId])) {
                $attachmentsByMessage[$messageId] = [];
            }
            $attachmentsByMessage[$messageId][] = [
                'id' => (int) ($attachment['id'] ?? 0),
                'name' => (string) ($attachment['original_name'] ?? ''),
                'mime' => (string) ($attachment['mime'] ?? ''),
                'size' => (int) ($attachment['size'] ?? 0),
            ];
        }

        $payloadMessages = [];
        $lastMessageId = 0;
        $isGuestConversation = empty($conversation['created_by']) && trim((string) ($conversation['viewer_key'] ?? '')) !== '';
        $guestKey = $this->guestContext->viewerKey();
        $userId = (int) ($user['id'] ?? 0);
        foreach ($messages as $message) {
            $messageId = (int) ($message['id'] ?? 0);
            $lastMessageId = max($lastMessageId, $messageId);
            $senderName = trim((string) ($message['first_name'] ?? '') . ' ' . (string) ($message['last_name'] ?? ''));
            if ($senderName === '') {
                $senderName = trim((string) ($message['guest_name'] ?? ''));
            }
            if ($senderName === '') {
                $senderName = trim((string) ($conversation['guest_name'] ?? ''));
            }
            if ($senderName === '') {
                $senderName = 'Utilisateur';
            }
            $senderId = (int) ($message['sender_id'] ?? 0);
            $isSelf = false;
            if ($userId > 0) {
                $isSelf = $senderId > 0 && $senderId === $userId;
            } elseif ($isGuestConversation) {
                $isSelf = $senderId < 1 && hash_equals($guestKey, (string) ($conversation['viewer_key'] ?? ''));
            }
            $payloadMessages[] = [
                'id' => $messageId,
                'sender_id' => $senderId,
                'sender_name' => $senderName !== '' ? $senderName : 'Utilisateur',
                'body' => (string) ($message['body'] ?? ''),
                'created_at' => (string) ($message['created_at'] ?? ''),
                'is_self' => $isSelf,
                'attachments' => $attachmentsByMessage[$messageId] ?? [],
            ];
        }

        if ($lastMessageId > 0 && !empty($user) && (int) ($user['id'] ?? 0) > 0) {
            try {
                $this->model->upsertConversationRead(
                    $conversationId,
                    (int) $user['id'],
                    $lastMessageId,
                    true
                );
            } catch (\Throwable $e) {
                // Keep message listing available when MariaDB runs in recovery/read-only mode.
                if ($this->debug) {
                    error_log('[support] unable to persist read marker: ' . $e->getMessage());
                }
            }
        }
        if ($isGuestConversation) {
            $this->guestContext->setActiveConversationId($conversationId);
        }

        return [
            'status' => 200,
            'payload' => [
                'ok' => true,
                'messages' => $payloadMessages,
                'last_message_id' => $lastMessageId,
            ],
        ];
    }

    private function canAccessConversation(array $conversation, ?array $user, array $guestContext, bool $isStaff): bool
    {
        if ($isStaff) {
            return true;
        }

        $userId = (int) ($user['id'] ?? 0);
        if ($userId > 0) {
            return $userId === (int) ($conversation['created_by'] ?? 0);
        }

        $conversationKey = trim((string) ($conversation['viewer_key'] ?? ''));
        $guestKey = trim((string) ($guestContext['viewer_key'] ?? ''));
        if ($conversationKey === '' || $guestKey === '') {
            return false;
        }

        return hash_equals($guestKey, $conversationKey);
    }

    private function resolveStaffAccess(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $roles = $this->access->rolesFromUser($user);
        $isStaff = $this->access->isStaff($roles);

        if (in_array(AdminAccessService::ROLE_SECONDARY, $roles, true) && !in_array('support', $roles, true)) {
            $adminAccess = new AdminAccessService();
            $isStaff = $adminAccess->canAccess($user, AdminAccessService::PERMISSION_SUPPORT);
        }

        return $isStaff;
    }

    private function cleanupStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as $path) {
            $absolute = $this->storage->resolveStoredPath((string) $path);
            if ($absolute !== null && is_file($absolute)) {
                @unlink($absolute);
            }
        }
    }

    private function safeCreateNotification(int $userId, string $title, string $body, ?string $url): void
    {
        if ($userId < 1) {
            return;
        }

        try {
            $this->model->createNotification($userId, $title, $body, $url);
        } catch (\Throwable $e) {
            if ($this->debug) {
                error_log('[support] unable to create notification: ' . $e->getMessage());
            }
        }
    }

    private function resolveCategory(string $category, string $subject = '', string $body = ''): string
    {
        $normalized = strtolower(trim($category));
        if (array_key_exists($normalized, self::CATEGORY_LABELS)) {
            return $normalized;
        }

        $haystack = strtolower($subject . ' ' . $body);
        if (str_contains($haystack, 'paiement') || str_contains($haystack, 'achat') || str_contains($haystack, 'facture')) {
            return 'billing';
        }
        if (str_contains($haystack, 'certificat') || str_contains($haystack, 'attestation')) {
            return 'certificate';
        }
        if (str_contains($haystack, 'cours') || str_contains($haystack, 'leçon') || str_contains($haystack, 'lesson')) {
            return 'course';
        }
        if (str_contains($haystack, 'retrait') || str_contains($haystack, 'revenu')) {
            return 'withdrawal';
        }
        if (str_contains($haystack, 'rendez') || str_contains($haystack, 'meeting')) {
            return 'meeting';
        }

        return 'other';
    }

    private function normalizeSubjectForDisplay(string $subject, string $category, int $ticketId): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $subject) ?? '');
        if (!$this->isWeakSubject($clean)) {
            return $clean !== '' ? $clean : (self::DEFAULT_SUBJECTS[$category] ?? self::DEFAULT_SUBJECTS['other']);
        }

        if ($category !== 'other') {
            return self::DEFAULT_SUBJECTS[$category] ?? self::DEFAULT_SUBJECTS['other'];
        }

        $fallbacks = [
            'Problème d’accès au cours',
            'Paiement non validé',
            'Certificat indisponible',
        ];
        $index = max(0, $ticketId - 1) % count($fallbacks);

        return $fallbacks[$index];
    }

    private function isWeakSubject(string $subject): bool
    {
        $normalized = strtolower(trim($subject));
        return in_array($normalized, ['', 'salut', 'bonjour', 'hello', 'hi', 'test', 'aide'], true);
    }

    private function legacyCategoryForTicket(int $ticketId): string
    {
        $categories = ['course', 'billing', 'certificate'];
        return $categories[max(0, $ticketId - 1) % count($categories)];
    }

    /**
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function buildDbError(\Throwable $e): array
    {
        $message = strtolower((string) $e->getMessage());
        $isReadOnly = str_contains($message, 'read only')
            || str_contains($message, 'innodb_read_only')
            || str_contains($message, 'innodb_force_recovery');

        if ($isReadOnly) {
            $payload = [
                'ok' => false,
                'error' => 'db_read_only',
                'message' => 'Base de donnees en lecture seule (mode recovery).',
            ];
            if ($this->debug) {
                $payload['details'] = $e->getMessage();
            }
            return ['status' => 503, 'payload' => $payload];
        }

        $payload = ['ok' => false, 'error' => 'db'];
        if ($this->debug) {
            $payload['details'] = $e->getMessage();
        }
        return ['status' => 500, 'payload' => $payload];
    }
}

