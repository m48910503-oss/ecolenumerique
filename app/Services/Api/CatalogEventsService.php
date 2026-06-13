<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Core\Service;
use App\Models\ApiCatalogEventsModel;

final class CatalogEventsService extends Service
{
    private const ALLOWED_EVENTS = [
        'course_click',
        'preview_open',
        'add_to_cart',
        'buy_now',
        'purchase',
        'density_change',
        'empty_state_cta',
        'owned_toggle',
        'favorites_filter_toggle',
        'sort_change',
        'filter_change',
        'filters_reset',
        'priority_open',
        'widgets_open',
        'widgets_reset',
        'widgets_save',
        'command_used',
        'command_palette_open',
        'section_view',
    ];

    private ApiCatalogEventsModel $model;

    public function __construct(ApiCatalogEventsModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param array<string,mixed>|null $user
     * @param array<string,mixed> $data
     * @return array{status:int,payload:array<string,mixed>}
     */
    public function handle(?array $user, string $method, array $data): array
    {
        if (strtoupper($method) !== 'POST') {
            return ['status' => 405, 'payload' => ['ok' => false, 'error' => 'method_not_allowed']];
        }

        if (!$this->model->schemaReady()) {
            return ['status' => 200, 'payload' => ['ok' => true, 'tracked' => false]];
        }

        $eventType = trim((string) ($data['event_type'] ?? ''));
        if (!in_array($eventType, self::ALLOWED_EVENTS, true)) {
            return ['status' => 400, 'payload' => ['ok' => false, 'error' => 'event_type_invalid']];
        }

        $blockKey = $this->normalizeBlockKey((string) ($data['block_key'] ?? ''));
        $courseId = (int) ($data['course_id'] ?? 0);
        if ($courseId < 1) {
            $courseId = 0;
        }

        $sessionKey = $this->buildSessionKey();
        $userId = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
        $meta = $data['meta'] ?? null;
        $metaJson = null;
        if (is_array($meta) && $meta !== []) {
            $cleanMeta = [];
            foreach ($meta as $k => $v) {
                $key = trim((string) $k);
                if ($key === '' || strlen($key) > 40) {
                    continue;
                }
                if (is_scalar($v) || $v === null) {
                    $cleanMeta[$key] = $v;
                }
            }
            if ($cleanMeta !== []) {
                $metaJson = json_encode($cleanMeta, JSON_UNESCAPED_UNICODE);
            }
        }

        $this->model->insertEvent(
            $eventType,
            $blockKey,
            $courseId > 0 ? $courseId : null,
            $userId > 0 ? $userId : null,
            $sessionKey,
            $metaJson ?: null
        );

        return ['status' => 200, 'payload' => ['ok' => true, 'tracked' => true]];
    }

    private function normalizeBlockKey(string $raw): string
    {
        $value = strtolower(trim($raw));
        if ($value === '') {
            return 'unknown';
        }
        $value = preg_replace('/[^a-z0-9_-]/', '-', $value);
        $value = trim((string) $value, '-_');
        if ($value === '') {
            return 'unknown';
        }
        if (strlen($value) > 64) {
            $value = substr($value, 0, 64);
        }
        return $value;
    }

    private function buildSessionKey(): ?string
    {
        $sid = (string) session_id();
        if ($sid === '') {
            return null;
        }
        return 's:' . substr(hash('sha256', $sid), 0, 48);
    }
}
