<?php
declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Service;

use App\Models\ApiSupportModel;
use App\Services\Support\SupportAccessService;

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

    private const PRIORITY_LABELS = [
        'low' => 'Faible',
        'normal' => 'Normal',
        'high' => 'Urgent',
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

    private SupportAccessService $access;

    public function __construct(?SupportAccessService $access = null)
    {
        $this->access = $access ?? new SupportAccessService();
    }

    public function buildPageData(array $user, array $query): array
    {
        $roles = $this->access->rolesFromUser($user);
        $isStaff = $this->access->isStaff($roles);
        $initialConversation = isset($query['conv']) ? (int) $query['conv'] : 0;

        return [
            'roles' => $roles,
            'isStaff' => $isStaff,
            'initialConversation' => $initialConversation,
            'basePath' => rtrim(BASE_PATH, '/'),
        ];
    }

    /**
     * @param array<string,mixed> $user
     * @return array{tickets:array<int,array<string,mixed>>,stats:array{open:int,waiting:int,resolved:int}}
     */
    public function buildTicketFeed(array $user, bool $isStaff, ?string $dashboardSpace = null, int $limit = 200): array
    {
        $limit = max(1, min(200, $limit));
        $tickets = [];
        $userId = max(0, (int) ($user['id'] ?? 0));
        unset($dashboardSpace);

        if ($userId < 1) {
            return [
                'tickets' => [],
                'stats' => [
                    'open' => 0,
                    'waiting' => 0,
                    'resolved' => 0,
                ],
            ];
        }

        try {
            $rows = (new ApiSupportModel($this->pdo()))->listConversations(
                $userId,
                $isStaff,
                $isStaff ? 'all' : 'mine',
                '',
                '',
                $limit
            );

            foreach ($rows as $row) {
                $ticket = $this->mapConversationToTicket($row);
                if ($ticket !== []) {
                    $tickets[] = $ticket;
                }
            }
        } catch (\Throwable $e) {
            $tickets = [];
        }

        return [
            'tickets' => $tickets,
            'stats' => $this->summarizeTickets($tickets),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapConversationToTicket(array $row): array
    {
        $status = strtolower(trim((string) ($row['status'] ?? 'open')));
        if (!in_array($status, ['open', 'waiting_support', 'waiting_user', 'closed'], true)) {
            $status = 'open';
        }

        $unreadCount = max(0, (int) ($row['unread_count'] ?? 0));
        $dateRaw = trim((string) ($row['last_message_at'] ?? ''));
        $dateLabel = $this->formatTicketDate($dateRaw);
        $dateIso = $this->formatTicketDateIso($dateRaw);
        $preview = $this->normalizeTicketText((string) ($row['last_message_body'] ?? ''));
        $ticketId = (int) ($row['id'] ?? 0);
        $category = $this->resolveCategory(
            (string) ($row['category'] ?? ''),
            (string) ($row['subject'] ?? ''),
            $preview
        );
        if ($category === 'other' && $this->isWeakSubject((string) ($row['subject'] ?? ''))) {
            $category = $this->legacyCategoryForTicket($ticketId);
        }
        $subject = $this->normalizeSubjectForDisplay((string) ($row['subject'] ?? ''), $category, $ticketId);
        $priority = (string) ($row['priority'] ?? 'normal');
        $priority = in_array($priority, ['low', 'normal', 'high'], true) ? $priority : 'normal';
        $updatedRaw = trim((string) ($row['updated_at'] ?? ''));
        if ($updatedRaw === '') {
            $updatedRaw = $dateRaw;
        }
        $updatedLabel = $this->formatTicketDate($updatedRaw);
        $updatedIso = $this->formatTicketDateIso($updatedRaw);

        if ($preview === '') {
            $preview = 'Aperçu indisponible.';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($subject) > 80) {
                $subject = rtrim((string) mb_substr($subject, 0, 77)) . '...';
            }
            if (mb_strlen($preview) > 120) {
                $preview = rtrim((string) mb_substr($preview, 0, 117)) . '...';
            }
        } else {
            if (strlen($subject) > 80) {
                $subject = rtrim(substr($subject, 0, 77)) . '...';
            }
            if (strlen($preview) > 120) {
                $preview = rtrim(substr($preview, 0, 117)) . '...';
            }
        }

        $noteTone = 'amber';
        $note = 'En attente de réponse';
        if ($status === 'closed') {
            $note = 'Clôturé';
            $noteTone = 'green';
        } elseif ($status === 'waiting_user') {
            $note = 'Votre réponse est attendue';
            $noteTone = 'action';
        } elseif ($status === 'waiting_support') {
            $note = 'En attente du support';
        } elseif ($unreadCount > 0) {
            $relative = $this->relativeTime($dateRaw);
            $note = $relative !== '' ? 'Réponse du support ' . $relative : 'Réponse du support';
            $noteTone = 'blue';
        }

        if ($status === 'closed') {
            $statusLabel = 'Résolu';
            $statusTone = 'green';
        } elseif ($status === 'waiting_user') {
            $statusLabel = 'Réponse requise';
            $statusTone = 'action';
        } elseif ($status === 'waiting_support') {
            $statusLabel = 'Support';
            $statusTone = 'amber';
        } else {
            $statusLabel = 'Ouvert';
            $statusTone = 'blue';
        }
        $statusGroup = $status === 'closed' ? 'closed' : 'open';

        return [
            'id' => $ticketId,
            'subject' => $subject,
            'preview' => $preview,
            'date' => $dateLabel,
            'date_iso' => $dateIso,
            'updated_label' => $updatedLabel,
            'updated_iso' => $updatedIso,
            'category' => $category,
            'category_label' => self::CATEGORY_LABELS[$category] ?? self::CATEGORY_LABELS['other'],
            'priority' => $priority,
            'priority_label' => self::PRIORITY_LABELS[$priority] ?? self::PRIORITY_LABELS['normal'],
            'status' => $status,
            'status_group' => $statusGroup,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'note' => $note,
            'note_tone' => $noteTone,
            'dimmed' => $status === 'closed',
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $tickets
     * @return array{open:int,waiting:int,resolved:int}
     */
    private function summarizeTickets(array $tickets): array
    {
        $open = 0;
        $waiting = 0;
        $resolved = 0;
        $now = new \DateTimeImmutable('now');

        foreach ($tickets as $ticket) {
            $statusGroup = (string) ($ticket['status_group'] ?? 'open');
            $noteTone = (string) ($ticket['note_tone'] ?? 'amber');

            if ($statusGroup === 'closed') {
                $dateIso = trim((string) ($ticket['date_iso'] ?? ''));
                if ($dateIso !== '') {
                    try {
                        $resolvedDate = new \DateTimeImmutable($dateIso);
                        if ($resolvedDate->format('Y-m') === $now->format('Y-m')) {
                            $resolved++;
                        }
                    } catch (\Throwable $e) {
                        $resolved++;
                    }
                } else {
                    $resolved++;
                }
                continue;
            }

            $open++;
            if ($noteTone === 'amber') {
                $waiting++;
            }
        }

        return [
            'open' => $open,
            'waiting' => $waiting,
            'resolved' => $resolved,
        ];
    }

    private function formatTicketDate(string $raw): string
    {
        $parsed = $this->parseDateTime($raw);
        if ($parsed === null) {
            return $raw !== '' ? $raw : '—';
        }

        return $parsed->format('d/m/Y');
    }

    private function formatTicketDateIso(string $raw): string
    {
        $parsed = $this->parseDateTime($raw);
        if ($parsed === null) {
            return '';
        }

        return $parsed->format('Y-m-d');
    }

    private function relativeTime(string $raw): string
    {
        $parsed = $this->parseDateTime($raw);
        if ($parsed === null) {
            return '';
        }

        $now = new \DateTimeImmutable('now');
        $diffSeconds = max(0, $now->getTimestamp() - $parsed->getTimestamp());
        if ($diffSeconds < 60) {
            return 'à l’instant';
        }

        $minutes = (int) floor($diffSeconds / 60);
        if ($minutes < 60) {
            return 'il y a ' . $minutes . ' min';
        }

        $hours = (int) floor($minutes / 60);
        if ($hours < 24) {
            return 'il y a ' . $hours . 'h';
        }

        $days = (int) floor($hours / 24);
        if ($days < 7) {
            return 'il y a ' . $days . ' j';
        }

        return $parsed->format('d/m/Y');
    }

    private function normalizeTicketText(string $value): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return $clean;
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
        $clean = $this->normalizeTicketText($subject);
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

    private function parseDateTime(string $raw): ?\DateTimeImmutable
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        $normalized = str_contains($value, 'T') ? $value : str_replace(' ', 'T', $value);
        try {
            return new \DateTimeImmutable($normalized);
        } catch (\Throwable $e) {
            return null;
        }
    }
}

